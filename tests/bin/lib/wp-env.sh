#!/usr/bin/env bash
# Shared WordPress / WP-CLI helpers for regression site scripts.

# Capture this file's directory at source time. Inside functions, BASH_SOURCE[0]
# is the caller, so dirname/BASH_SOURCE cannot be used later to find the repo.
_REGRESSION_LIB_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

regression_plugin_root() {
	cd "$_REGRESSION_LIB_DIR/../../.." && pwd
}

regression_load_dotenv() {
	local root env_file key value current_value
	root="$(regression_plugin_root)"
	env_file="$root/.env"

	[ -f "$env_file" ] || return 0

	# Parse dotenv as data rather than sourcing it as shell code. Besides being
	# safer, this keeps values such as "$19.00" and passwords containing "$1"
	# literal when callers enable `set -u`.
	while IFS= read -r -d '' key && IFS= read -r -d '' value; do
		current_value="$(printenv "$key" 2>/dev/null || true)"

		if [ -n "$current_value" ]; then
			continue
		fi

		printf -v "$key" '%s' "$value"
		export "$key"
	done < <(
		php -r '
			$path = $argv[1];
			$lines = file($path, FILE_IGNORE_NEW_LINES);

			if (false === $lines) {
				fwrite(STDERR, "Unable to read dotenv file: {$path}\n");
				exit(1);
			}

			foreach ($lines as $line) {
				$trimmed = trim($line);

				if ("" === $trimmed || str_starts_with($trimmed, "#")) {
					continue;
				}

				if (str_starts_with($trimmed, "export ")) {
					$trimmed = ltrim(substr($trimmed, 7));
				}

				if (!preg_match("/^([A-Za-z_][A-Za-z0-9_]*)\s*=(.*)$/s", $trimmed, $matches)) {
					continue;
				}

				$key = $matches[1];
				$value = trim($matches[2]);
				$length = strlen($value);

				if ($length >= 2 && (
					("\"" === $value[0] && "\"" === $value[$length - 1])
					|| ("'\''" === $value[0] && "'\''" === $value[$length - 1])
				)) {
					$quote = $value[0];
					$value = substr($value, 1, -1);

					if ("\"" === $quote) {
						$value = preg_replace("/\\\\\\\\([\"\\\\\\\\$])/", "$1", $value);
					}
				} else {
					$value = preg_replace("/\s+#.*$/", "", $value);
					$value = rtrim((string) $value);
				}

				echo $key, "\0", $value, "\0";
			}
		' "$env_file"
	)
}

regression_target_host() {
	local host="${PUBLISHPRESS_CART_HOST:-http://tests.local}"
	host="${host#*://}"
	host="${host%%/*}"
	host="${host%%:*}"
	echo "$host"
}

regression_resolve_wp_path_from_local_sites() {
	local host="$1"
	local sites_json="${HOME}/Library/Application Support/Local/sites.json"

	if [ ! -f "$sites_json" ]; then
		return 1
	fi

	php -r '
		$host = $argv[1] ?? "";
		$sitesJson = $argv[2] ?? "";
		$home = $argv[3] ?? (getenv("HOME") ?: "");
		$data = json_decode((string) file_get_contents($sitesJson), true);
		if (!is_array($data)) {
			exit(1);
		}
		foreach ($data as $site) {
			if (!is_array($site)) {
				continue;
			}
			if (($site["domain"] ?? "") !== $host) {
				continue;
			}
			$path = (string) ($site["path"] ?? "");
			if ($path === "") {
				exit(1);
			}
			if (str_starts_with($path, "~/")) {
				$path = $home . substr($path, 1);
			}
			echo rtrim($path, "/") . "/app/public";
			exit(0);
		}
		exit(1);
	' "$host" "$sites_json" "${HOME:-}"
}

regression_resolve_wp_path_from_plugin_tree() {
	local root candidate
	root="$(regression_plugin_root)"
	candidate="$(cd "$root/../../.." && pwd)"

	if [[ "$root" == */wp-content/plugins/* ]] && [ -f "$candidate/wp-config.php" ]; then
		echo "$candidate"
		return 0
	fi

	return 1
}

regression_resolve_wp_path_from_dev_workspace() {
	local root cache_path wp_path
	root="$(regression_plugin_root)"

	cache_path="${CACHE_PATH:-}"
	if [ -z "$cache_path" ]; then
		cache_path="$root/dev-workspace-cache"
	elif [[ "$cache_path" != /* ]]; then
		cache_path="$root/${cache_path#./}"
	fi

	wp_path="${WP_TESTS_ROOT_DIR:-}"
	if [ -n "$wp_path" ]; then
		if [[ "$wp_path" != /* ]]; then
			wp_path="$root/${wp_path#./}"
		fi
		if [ -f "$wp_path/wp-config.php" ]; then
			echo "$wp_path"
			return 0
		fi
	fi

	wp_path="$cache_path/wp_test"
	if [ -f "$wp_path/wp-config.php" ]; then
		echo "$wp_path"
		return 0
	fi

	return 1
}

# Prints: container_name<TAB>wp_path_in_container<TAB>plugin_path_in_container
regression_detect_docker_wp() {
	local plugin_root host slug ids

	if ! command -v docker >/dev/null 2>&1; then
		return 1
	fi

	plugin_root="$(regression_plugin_root)"
	host="$(regression_target_host)"
	slug="$(basename "$plugin_root")"

	ids="$(docker ps -q 2>/dev/null || true)"
	if [ -z "$ids" ]; then
		return 1
	fi

	# shellcheck disable=SC2086
	docker inspect $ids 2>/dev/null | php "$_REGRESSION_LIB_DIR/detect-docker-wp.php" "$plugin_root" "$host" "$slug"
}

# True when REGRESSION_WP_CONTAINER names a currently running Docker container.
# A leftover name from .env.example (CI copies that file) must not force Docker WP-CLI.
regression_wp_container_running() {
	[ -n "${REGRESSION_WP_CONTAINER:-}" ] \
		&& command -v docker >/dev/null 2>&1 \
		&& docker inspect -f '{{.State.Running}}' "$REGRESSION_WP_CONTAINER" 2>/dev/null | grep -qx true
}

regression_configured_wp_path() {
	local candidate

	for candidate in "${REGRESSION_WP_PATH:-}" "${WP_PATH:-}"; do
		if [ -z "$candidate" ]; then
			continue
		fi

		if [ -f "$candidate/wp-config.php" ]; then
			echo "$candidate"
			return 0
		fi

		echo "WARNING: Configured WordPress path does not exist on this machine: $candidate" >&2
	done

	return 1
}

regression_resolve_wp_path() {
	local host wp_path

	if [ -n "${REGRESSION_WP_RUNTIME:-}" ] && [ -n "${REGRESSION_WP_PATH_RESOLVED:-}" ]; then
		echo "$REGRESSION_WP_PATH_RESOLVED"
		return 0
	fi

	# Container paths are not host filesystem paths. Check this before
	# regression_configured_wp_path() so DDEV's /var/www/html/.web does not
	# emit a false "path does not exist on this machine" warning.
	if regression_wp_container_running; then
		echo "${REGRESSION_WP_PATH:-/var/www/html}"
		return 0
	fi

	if wp_path="$(regression_configured_wp_path)"; then
		echo "$wp_path"
		return 0
	fi

	host="$(regression_target_host)"
	if wp_path="$(regression_resolve_wp_path_from_local_sites "$host" 2>/dev/null)" \
		&& [ -f "$wp_path/wp-config.php" ]; then
		echo "$wp_path"
		return 0
	fi

	if wp_path="$(regression_resolve_wp_path_from_plugin_tree 2>/dev/null)"; then
		echo "$wp_path"
		return 0
	fi

	if wp_path="$(regression_resolve_wp_path_from_dev_workspace 2>/dev/null)"; then
		echo "$wp_path"
		return 0
	fi

	if wp_path="$(regression_detect_docker_wp 2>/dev/null)"; then
		echo "$(printf '%s' "$wp_path" | cut -f2)"
		return 0
	fi

	return 1
}

# Strip placeholder Stripe/PayPal credentials copied from .env.example so CI
# does not attempt real gateway API calls with an all-zero test key.
regression_sanitize_placeholder_gateway_credentials() {
	local env_file="$1"
	local key value

	if [ ! -f "$env_file" ]; then
		return 0
	fi

	for key in STRIPE_TEST_PUBLISHABLE_KEY STRIPE_TEST_SECRET_KEY; do
		value="$(grep -E "^${key}=" "$env_file" 2>/dev/null | tail -n 1 | sed -E "s/^${key}=//; s/^[\"']//; s/[\"']$//" || true)"

		if [ -n "$value" ]; then
			case "$value" in
				pk_test_0*|sk_test_0*|rk_test_0*)
					if [[ "${value#*_test_}" =~ ^0+$ ]]; then
						regression_set_env_var "$env_file" "$key" ""
					fi
					;;
			esac
		fi
	done
}

# Provisions a throwaway WordPress install for CI when no local site is found.
# Writes the local target URL/credentials into .env and starts wp server.
regression_ensure_wordpress_provisioned() {
	local wp_path="$1"
	local plugin_root env_file
	plugin_root="$(regression_plugin_root)"
	env_file="$plugin_root/.env"

	if [ -f "$wp_path/wp-config.php" ]; then
		return 0
	fi

	# Container paths (Docker Compose, DDEV, etc.) are not host filesystem paths.
	# Skip CI provisioning when an explicit running container is configured.
	if regression_wp_container_running; then
		echo "==> Using WordPress in Docker container: $REGRESSION_WP_CONTAINER ($wp_path)"
		return 0
	fi

	if [ -n "${REGRESSION_WP_CONTAINER:-}" ]; then
		# GitHub Actions copies .env.example (or a secret) with a DDEV name that
		# is not running. Local leftover names should still fail loud so setup
		# does not provision .ci-wordpress and wipe REGRESSION_WP_* in .env.
		if [ "${GITHUB_ACTIONS:-}" = "true" ]; then
			echo "WARNING: REGRESSION_WP_CONTAINER=$REGRESSION_WP_CONTAINER is not running; provisioning a host WordPress instead." >&2
			unset REGRESSION_WP_CONTAINER
		else
			echo "REGRESSION_WP_CONTAINER is set to $REGRESSION_WP_CONTAINER but that container is not running." >&2
			return 1
		fi
	fi

	echo "==> No WordPress found at $wp_path - provisioning one for testing"

	local host="127.0.0.1"
	local port="${WP_CI_PORT:-8080}"
	local url="http://${host}:${port}"
	local admin_user="${WP_CI_ADMIN_USER:-admin}"
	local admin_password="${WP_CI_ADMIN_PASSWORD:-admin}"
	local admin_email="${WP_CI_ADMIN_EMAIL:-test@example.com}"
	local db_host="${DB_HOST:-127.0.0.1}"
	local db_port="${DB_PORT:-3306}"
	local db_name="${DB_NAME:-wordpress}"
	local db_user="${DB_USER:-wordpress}"
	local db_password="${DB_PASSWORD:-wordpress}"
	local wp_cli="${WP_CLI:-wp}"

	mkdir -p "$wp_path"

	echo "==> Downloading WordPress core to $wp_path"
	"$wp_cli" core download --path="$wp_path" --quiet

	echo "==> Creating wp-config.php"
	"$wp_cli" config create \
		--path="$wp_path" \
		--dbname="$db_name" \
		--dbuser="$db_user" \
		--dbpass="$db_password" \
		--dbhost="${db_host}:${db_port}" \
		--skip-check \
		--quiet

	echo "==> Waiting for the database"
	for _ in $(seq 1 30); do
		if "$wp_cli" db check --path="$wp_path" >/dev/null 2>&1; then
			break
		fi
		sleep 1
	done

	echo "==> Installing WordPress"
	"$wp_cli" core install \
		--path="$wp_path" \
		--url="$url" \
		--title="PublishPress Cart CI" \
		--admin_user="$admin_user" \
		--admin_password="$admin_password" \
		--admin_email="$admin_email" \
		--skip-email

	echo "==> Enabling WP_DEBUG"
	"$wp_cli" config set WP_DEBUG true --raw --path="$wp_path"
	"$wp_cli" config set WP_DEBUG_LOG true --raw --path="$wp_path"
	"$wp_cli" config set WP_DEBUG_DISPLAY false --raw --path="$wp_path"

	echo "==> Creating empty debug log so the admin debug-log viewer link is visible"
	mkdir -p "$wp_path/wp-content"
	touch "$wp_path/wp-content/debug.log"

	echo "==> Enabling the plugin debug log option"
	"$wp_cli" option update _sc_enable_debug 1 --path="$wp_path"

	echo "==> Configuring permalinks"
	"$wp_cli" rewrite structure '/%postname%/' --path="$wp_path"

	echo "==> Dismissing the Gutenberg first-visit welcome guide"
	"$wp_cli" eval '
		$user = get_user_by( "login", "'"$admin_user"'" );
		wp_set_current_user( $user->ID );

		$request = new WP_REST_Request( "PUT", "/wp/v2/users/me" );
		$request->set_body_params(
			array(
				"meta" => array(
					"persisted_preferences" => array(
						"core"           => array( "isComplementaryAreaVisible" => true ),
						"core/edit-post" => array( "welcomeGuide" => false ),
						"core/edit-site" => array( "welcomeGuide" => false ),
						"_modified"      => gmdate( "Y-m-d\TH:i:s.v\Z" ),
					),
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$status   = $response->get_status();

		if ( $status >= 400 ) {
			fwrite( STDERR, "Failed to dismiss welcome guide (status {$status}): " . wp_json_encode( $response->get_data() ) . "\n" );
			exit( 1 );
		}

		echo "Welcome guide dismissed for {$user->user_login} (status {$status})\n";
	' --path="$wp_path"

	echo "==> Writing the local target into .env"
	if [ ! -f "$env_file" ]; then
		cp "$plugin_root/.env.example" "$env_file"
	fi

	# Strip placeholder gateway credentials that ship in .env.example so the
	# regression setup does not try to create real Stripe/PayPal resources with
	# an all-zero test key in CI.
	regression_sanitize_placeholder_gateway_credentials "$env_file"

	regression_set_env_var "$env_file" PUBLISHPRESS_CART_HOST "$url"
	regression_set_env_var "$env_file" WP_TESTS_URL "$url"
	regression_set_env_var "$env_file" WP_TESTS_ADMIN_USER "$admin_user"
	regression_set_env_var "$env_file" WP_TESTS_ADMIN_PASSWORD "$admin_password"
	regression_set_env_var "$env_file" REGRESSION_WP_PATH "$wp_path"
	# Host WP-CLI serves this throwaway site. Drop DDEV/Docker names copied from
	# .env.example so the post-provision dotenv reload does not switch runtimes.
	regression_set_env_var "$env_file" REGRESSION_WP_CONTAINER ""
	regression_set_env_var "$env_file" REGRESSION_PLUGIN_CONTAINER_PATH ""
	unset REGRESSION_WP_CONTAINER REGRESSION_PLUGIN_CONTAINER_PATH REGRESSION_WP_RUNTIME REGRESSION_WP_PATH_RESOLVED

	echo "==> Starting the WP-CLI dev server on $url"
	(PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}" \
		"$wp_cli" server --host="$host" --port="$port" --path="$wp_path" \
		> "$plugin_root/.ci-wp-server.log" 2>&1 &
	echo $! > "$plugin_root/.ci-wp-server.pid")
	disown -a || true

	echo "==> Waiting for the server to respond"
	for _ in $(seq 1 30); do
		if curl -sSf -o /dev/null "$url"; then
			echo "WordPress is up at $url"
			break
		fi
		sleep 1
	done
}

# Map a host path under the plugin repo to the matching path inside a container
# that bind-mounts the repo (DDEV docroot, in-repo CI WordPress, etc.).
regression_container_path_from_host_repo() {
	local host_path="$1"
	local plugin_root="$2"
	local plugin_in_container="$3"

	if [ -z "$host_path" ] || [ -z "$plugin_root" ] || [ -z "$plugin_in_container" ]; then
		echo "$host_path"
		return 0
	fi

	if [ "$host_path" = "$plugin_root" ]; then
		echo "$plugin_in_container"
		return 0
	fi

	if [[ "$host_path" == "$plugin_root/"* ]]; then
		echo "${plugin_in_container}${host_path#"$plugin_root"}"
		return 0
	fi

	echo "$host_path"
}

regression_detect_runtime() {
	local detection plugin_root configured_host prefer_docker

	REGRESSION_WP_RUNTIME="${REGRESSION_WP_RUNTIME:-}"
	REGRESSION_WP_PATH_RESOLVED="${REGRESSION_WP_PATH_RESOLVED:-}"
	REGRESSION_PLUGIN_CONTAINER_PATH="${REGRESSION_PLUGIN_CONTAINER_PATH:-}"

	if [ -n "$REGRESSION_WP_RUNTIME" ] && [ -n "$REGRESSION_WP_PATH_RESOLVED" ]; then
		return 0
	fi

	plugin_root="$(regression_plugin_root)"

	if [ -n "${REGRESSION_WP_CONTAINER:-}" ]; then
		if regression_wp_container_running; then
			REGRESSION_WP_RUNTIME="docker"
			if [[ "$REGRESSION_WP_CONTAINER" == ddev-*-web ]]; then
				REGRESSION_PLUGIN_CONTAINER_PATH="${REGRESSION_PLUGIN_CONTAINER_PATH:-/var/www/html}"
				REGRESSION_WP_PATH_RESOLVED="$(regression_container_path_from_host_repo "${REGRESSION_WP_PATH:-}" "$plugin_root" "$REGRESSION_PLUGIN_CONTAINER_PATH")"
				if [ -z "$REGRESSION_WP_PATH_RESOLVED" ]; then
					REGRESSION_WP_PATH_RESOLVED="/var/www/html"
				fi
			else
				REGRESSION_WP_PATH_RESOLVED="${REGRESSION_WP_PATH:-/var/www/html}"
				REGRESSION_PLUGIN_CONTAINER_PATH="${REGRESSION_PLUGIN_CONTAINER_PATH:-$REGRESSION_WP_PATH_RESOLVED/wp-content/plugins/$(basename "$plugin_root")}"
			fi
			return 0
		fi
		echo "WARNING: REGRESSION_WP_CONTAINER=$REGRESSION_WP_CONTAINER is not running; using host WP-CLI." >&2
		unset REGRESSION_WP_CONTAINER
	fi

	configured_host=""
	if configured_host="$(regression_configured_wp_path 2>/dev/null)"; then
		:
	else
		configured_host=""
	fi

	# DDEV bind-mounts the project so wp-config.php is visible on the host, but
	# WP-CLI still has to run in the web container (database host is `db`).
	# Prefer Docker whenever this plugin is mounted in a running WP container,
	# unless REGRESSION_WP_PATH points at a WordPress tree *outside* the repo
	# (Local WP, a separate site checkout).
	if detection="$(regression_detect_docker_wp 2>/dev/null)"; then
		prefer_docker=1
		if [ -n "$configured_host" ] \
			&& [ "$configured_host" != "$plugin_root" ] \
			&& [[ "$configured_host" != "$plugin_root/"* ]]; then
			prefer_docker=0
		fi
		if [ "$prefer_docker" = 1 ]; then
			REGRESSION_WP_RUNTIME="docker"
			REGRESSION_WP_CONTAINER="$(printf '%s' "$detection" | cut -f1)"
			REGRESSION_WP_PATH_RESOLVED="$(printf '%s' "$detection" | cut -f2)"
			REGRESSION_PLUGIN_CONTAINER_PATH="$(printf '%s' "$detection" | cut -f3)"
			return 0
		fi
	fi

	if [ -n "$configured_host" ]; then
		REGRESSION_WP_PATH_RESOLVED="$configured_host"
		REGRESSION_WP_RUNTIME="host"
		REGRESSION_PLUGIN_CONTAINER_PATH="$plugin_root"
		return 0
	fi

	if REGRESSION_WP_PATH_RESOLVED="$(regression_resolve_wp_path_from_local_sites "$(regression_target_host)" 2>/dev/null)" \
		&& [ -f "$REGRESSION_WP_PATH_RESOLVED/wp-config.php" ]; then
		REGRESSION_WP_RUNTIME="host"
		REGRESSION_PLUGIN_CONTAINER_PATH="$plugin_root"
		return 0
	fi

	if REGRESSION_WP_PATH_RESOLVED="$(regression_resolve_wp_path_from_plugin_tree 2>/dev/null)"; then
		REGRESSION_WP_RUNTIME="host"
		REGRESSION_PLUGIN_CONTAINER_PATH="$plugin_root"
		return 0
	fi

	if REGRESSION_WP_PATH_RESOLVED="$(regression_resolve_wp_path_from_dev_workspace 2>/dev/null)"; then
		REGRESSION_WP_RUNTIME="host"
		REGRESSION_PLUGIN_CONTAINER_PATH="$plugin_root"
		return 0
	fi

	return 1
}

regression_resolve_local_mysql_socket() {
	local wp_path="$1"
	local run_dir conf socket

	for run_dir in "${HOME}/Library/Application Support/Local/run"/*/; do
		[ -d "$run_dir" ] || continue
		conf="${run_dir}conf/nginx/site.conf"
		if [ -f "$conf" ] && grep -Fq "$wp_path" "$conf"; then
			socket="${run_dir}mysql/mysqld.sock"
			if [ -S "$socket" ]; then
				echo "$socket"
				return 0
			fi
		fi
	done

	return 1
}

regression_ensure_db_host() {
	local wp_path="$1"
	local socket="$2"
	local db_host="localhost:${socket}"
	local current_host

	current_host="$("$WP_CLI" config get DB_HOST --path="$wp_path" --allow-root 2>/dev/null || true)"

	if [ "$current_host" = "$db_host" ]; then
		return 0
	fi

	echo "==> Configuring WP-CLI database socket for Local"
	echo "    DB_HOST=$db_host"
	"$WP_CLI" config set DB_HOST "$db_host" --path="$wp_path" --allow-root
}

regression_silence_vendor_php_deprecations() {
	# NOTE: thecodingmachine/safe v1.x (transitive via dompdf → sabberworm/php-css-parser)
	# emits hundreds of PHP 8.4 "implicitly nullable parameter" deprecations when
	# autoloaded. WP_DEBUG resets error_reporting during bootstrap; those
	# deprecations also bypass set_error_handler. Primary fix is the
	# mu-plugin linked by regression_ensure_silence_deprecations_mu_plugin();
	# WP_CLI_PHP_ARGS + regression_wp() stderr filter are backups.
	# See docs/testing/regression-tests.md (Troubleshooting).
	local mask
	mask="$(php -r 'echo E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED;' 2>/dev/null || true)"

	if [ -z "$mask" ]; then
		# PHP 8.4 E_ALL without E_DEPRECATED|E_USER_DEPRECATED (fallback if host php missing).
		mask=6143
	fi

	case " ${WP_CLI_PHP_ARGS:-} " in
		*" error_reporting="*)
			;;
		*)
			export WP_CLI_PHP_ARGS="${WP_CLI_PHP_ARGS:+${WP_CLI_PHP_ARGS} }-d error_reporting=${mask}"
			;;
	esac
}

# True when the WordPress container already ships WP-CLI (DDEV web, some custom images).
regression_container_has_wp_cli() {
	[ -n "${REGRESSION_WP_CONTAINER:-}" ] \
		&& command -v docker >/dev/null 2>&1 \
		&& docker exec "$REGRESSION_WP_CONTAINER" sh -c 'command -v wp >/dev/null 2>&1' >/dev/null 2>&1
}

regression_ensure_docker_wp_cli_image() {
	local image="${REGRESSION_WP_CLI_IMAGE:-wordpress:cli}"

	# Containers that already include `wp` (e.g. DDEV) do not need the sidecar image.
	if regression_container_has_wp_cli; then
		return 0
	fi

	if docker image inspect "$image" >/dev/null 2>&1; then
		return 0
	fi

	echo "==> Pulling $image for Docker WP-CLI"
	docker pull "$image"
}

regression_docker_wp_env_args() {
	local env_line key
	REGRESSION_DOCKER_WP_ENV_ARGS=()

	while IFS= read -r env_line; do
		key="${env_line%%=*}"
		case "$key" in
			WORDPRESS_DB_HOST|WORDPRESS_DB_USER|WORDPRESS_DB_PASSWORD|WORDPRESS_DB_NAME|WORDPRESS_DB_CHARSET|WORDPRESS_DB_COLLATE|WORDPRESS_TABLE_PREFIX)
				REGRESSION_DOCKER_WP_ENV_ARGS+=(-e "$env_line")
				;;
		esac
	done < <(docker inspect "$REGRESSION_WP_CONTAINER" --format '{{range .Config.Env}}{{println .}}{{end}}')
}

regression_write_docker_wp_cli_wrapper() {
	local image="${REGRESSION_WP_CLI_IMAGE:-wordpress:cli}"
	local wrapper env_arg quoted_env_args=""

	wrapper="$(mktemp "${TMPDIR:-/tmp}/publishpress-cart-wp-cli.XXXXXX")"

	# Prefer exec into the site container when it already has WP-CLI. That covers
	# DDEV and custom images without WORDPRESS_DB_* env vars. Legacy official
	# wordpress images keep the wordpress:cli sidecar + shared network/volumes.
	if regression_container_has_wp_cli; then
		cat >"$wrapper" <<EOF
#!/usr/bin/env bash
set -euo pipefail
exec docker exec -i \\
	-e WP_CLI_PHP_ARGS=$(printf '%q' "${WP_CLI_PHP_ARGS:-}") \\
	$(printf '%q' "$REGRESSION_WP_CONTAINER") wp "\$@"
EOF
		chmod +x "$wrapper"
		_REGRESSION_WP_CLI_WRAPPER="$wrapper"
		WP_CLI="$wrapper"
		return 0
	fi

	regression_docker_wp_env_args
	if [ "${#REGRESSION_DOCKER_WP_ENV_ARGS[@]}" -eq 0 ]; then
		echo "WARNING: No WORDPRESS_DB_* environment variables found on $REGRESSION_WP_CONTAINER." >&2
		echo "WP-CLI may be unable to connect to the database." >&2
		echo "If this container includes WP-CLI (e.g. DDEV), ensure \`wp\` is on PATH inside it." >&2
	fi

	for env_arg in "${REGRESSION_DOCKER_WP_ENV_ARGS[@]+"${REGRESSION_DOCKER_WP_ENV_ARGS[@]}"}"; do
		quoted_env_args+=" $(printf '%q' "$env_arg")"
	done

	cat >"$wrapper" <<EOF
#!/usr/bin/env bash
set -euo pipefail
exec docker run --rm -i \\
	--volumes-from $(printf '%q' "$REGRESSION_WP_CONTAINER") \\
	--network $(printf '%q' "container:$REGRESSION_WP_CONTAINER") \\
	-e HOME=/tmp \\
	-e WP_CLI_PHP_ARGS=$(printf '%q' "${WP_CLI_PHP_ARGS:-}") \\
	${quoted_env_args} \\
	$(printf '%q' "$image") \\
	"\$@"
EOF
	chmod +x "$wrapper"
	_REGRESSION_WP_CLI_WRAPPER="$wrapper"
	WP_CLI="$wrapper"
}

regression_wp_visible_path() {
	local host_path="$1"
	local root

	if [ "${REGRESSION_WP_RUNTIME:-host}" != "docker" ]; then
		echo "$host_path"
		return 0
	fi

	root="$(regression_plugin_root)"
	if [[ "$host_path" == "$root" || "$host_path" == "$root/"* ]]; then
		echo "${REGRESSION_PLUGIN_CONTAINER_PATH}${host_path#"$root"}"
		return 0
	fi

	echo "$host_path"
}

# Run WP-CLI against the resolved runtime (host binary or Docker wrapper).
# Example: regression_wp eval-file /host/path/to/script.php
regression_wp() {
	local args=()
	local prev=""
	local arg
	local status=0
	local err_file

	for arg in "$@"; do
		if [ "$prev" = "eval-file" ]; then
			args+=("$(regression_wp_visible_path "$arg")")
		else
			args+=("$arg")
		fi
		prev="$arg"
	done

	# Belt-and-suspenders: WP_DEBUG resets error_reporting; --require silence
	# file should stop emission. Still strip any leftover Deprecated lines from
	# stderr (Safe v1.x on PHP 8.4).
	err_file="$(mktemp "${TMPDIR:-/tmp}/ppcart-wp-cli-err.XXXXXX")"
	status=0
	"$WP_CLI" "${args[@]}" "${WP_ARGS[@]}" 2>"$err_file" || status=$?

	if [ -s "$err_file" ]; then
		grep -vE 'Deprecated:' "$err_file" >&2 || true
	fi
	rm -f "$err_file"

	return "$status"
}

regression_wp_args() {
	local wp_path socket
	WP_ARGS=()

	regression_silence_vendor_php_deprecations

	if ! regression_detect_runtime; then
		echo "Unable to resolve WordPress for regression setup." >&2
		echo "Options:" >&2
		echo "  - Set REGRESSION_WP_PATH to a WordPress root on this machine" >&2
		echo "  - Or mount this plugin into a running WordPress Docker container" >&2
		echo "  - Or set REGRESSION_WP_CONTAINER to that container name" >&2
		return 1
	fi

	wp_path="$REGRESSION_WP_PATH_RESOLVED"
	WP_PATH="$wp_path"
	WP_ARGS=(--path="$wp_path")

	if [ "$REGRESSION_WP_RUNTIME" = "docker" ]; then
		if [ -z "${REGRESSION_WP_CONTAINER:-}" ]; then
			echo "Docker WordPress runtime detected but REGRESSION_WP_CONTAINER is empty." >&2
			return 1
		fi

		if [ -z "${_REGRESSION_WP_CLI_WRAPPER:-}" ] || [ ! -x "${_REGRESSION_WP_CLI_WRAPPER:-}" ]; then
			regression_ensure_docker_wp_cli_image
			regression_write_docker_wp_cli_wrapper
			echo "==> Using Docker WP-CLI via container: $REGRESSION_WP_CONTAINER"
		else
			WP_CLI="$_REGRESSION_WP_CLI_WRAPPER"
		fi
	else
		WP_CLI="${WP_CLI:-wp}"

		if [ ! -f "$wp_path/wp-config.php" ]; then
			echo "WordPress not found at: $wp_path" >&2
			return 1
		fi

		if socket="$(regression_resolve_local_mysql_socket "$wp_path")"; then
			regression_ensure_db_host "$wp_path" "$socket"
		fi
	fi

	if [ -n "${WP_CLI_ARGS:-}" ]; then
		# shellcheck disable=SC2206
		WP_ARGS+=($WP_CLI_ARGS)
	fi

	WP_ARGS+=(--allow-root)

	# After WP_DEBUG resets error_reporting, silence Safe deprecations before
	# regular plugins load (see silence-php-deprecations.php).
	regression_ensure_silence_deprecations_mu_plugin
}

regression_print_target() {
	local wp_path host siteurl blogname

	regression_wp_args >/dev/null
	wp_path="$WP_PATH"
	host="$(regression_target_host)"

	siteurl="$(regression_wp option get siteurl 2>/dev/null || true)"
	blogname="$(regression_wp option get blogname 2>/dev/null || true)"

	echo "Target host:     $host"
	echo "WordPress path:  $wp_path"
	echo "Runtime:         ${REGRESSION_WP_RUNTIME:-unknown}"
	if [ "${REGRESSION_WP_RUNTIME:-}" = "docker" ]; then
		echo "WP container:    ${REGRESSION_WP_CONTAINER:-unknown}"
		echo "Plugin in WP:    ${REGRESSION_PLUGIN_CONTAINER_PATH:-unknown}"
	fi
	echo "Site URL:        ${siteurl:-unknown}"
	echo "Site name:       ${blogname:-unknown}"
}

regression_require_wp_cli() {
	if ! regression_wp_args; then
		exit 1
	fi

	if [ "$REGRESSION_WP_RUNTIME" = "host" ] && ! command -v "$WP_CLI" >/dev/null 2>&1; then
		echo "Unable to find WP-CLI. Set WP_CLI to the wp executable path, or use a Docker-mounted WordPress site." >&2
		exit 1
	fi

	if ! regression_wp core is-installed >/dev/null 2>&1; then
		echo "WordPress is not installed at the resolved path." >&2
		regression_print_target >&2
		exit 1
	fi

	local siteurl host
	siteurl="$(regression_wp option get siteurl 2>/dev/null || true)"
	host="$(regression_target_host)"

	if [ -n "$siteurl" ] && [[ "$siteurl" != *"$host"* ]]; then
		echo "WARNING: PUBLISHPRESS_CART_HOST is $host but WordPress siteurl is $siteurl." >&2
		echo "Point PUBLISHPRESS_CART_HOST at the site served by this WordPress install," >&2
		echo "or set REGRESSION_WP_CONTAINER / REGRESSION_WP_PATH to the matching site." >&2
	fi
}

regression_plugins_dir() {
	local wp_path
	wp_path="$(regression_resolve_wp_path)"

	if [ "${REGRESSION_WP_RUNTIME:-host}" = "docker" ]; then
		echo "$wp_path/wp-content/plugins"
		return 0
	fi

	echo "$(cd "$wp_path/wp-content/plugins" && pwd)"
}

regression_mu_plugins_dir() {
	local wp_path
	wp_path="$(regression_resolve_wp_path)"

	if [ "${REGRESSION_WP_RUNTIME:-host}" = "docker" ]; then
		echo "$wp_path/wp-content/mu-plugins"
		return 0
	fi

	echo "$(cd "$wp_path/wp-content" && mkdir -p mu-plugins && cd mu-plugins && pwd)"
}

# True when WP is this plugin's DDEV docroot (.web) — the local browsing site.
regression_is_plugin_ddev_web() {
	local root wp_path
	root="$(regression_plugin_root)"
	wp_path="$(regression_resolve_wp_path)"

	case "$wp_path" in
		*/.web|*/.web/)
			return 0
			;;
	esac

	if [ -d "$root/.web" ] && [ -f "$wp_path/wp-config.php" ]; then
		[ "$(cd "$wp_path" && pwd)" = "$(cd "$root/.web" && pwd)" ]
		return $?
	fi

	return 1
}

# Link mu-plugin that lowers error_reporting after wp_debug_mode().
# Isolated test WordPress and this plugin's DDEV .web site. DDEV uses a
# relative target so the host bind-mount does not see a dangling /var/www/html
# symlink.
regression_ensure_silence_deprecations_mu_plugin() {
	local root slug target link_path mu_dir source_file
	root="$(regression_plugin_root)"
	slug="$(basename "$root")"
	source_file="$_REGRESSION_LIB_DIR/silence-php-deprecations.php"
	mu_dir="$(regression_mu_plugins_dir)"
	link_path="$mu_dir/ppcart-silence-php-deprecations.php"
	target="${REGRESSION_PLUGIN_CONTAINER_PATH:-$slug}/tests/bin/lib/silence-php-deprecations.php"

	if [ ! -f "$source_file" ]; then
		return 0
	fi

	# DDEV docroot is .web; plugin repo is ../../../ from mu-plugins.
	if regression_is_plugin_ddev_web; then
		target="../../../tests/bin/lib/silence-php-deprecations.php"
	fi

	if [ "${REGRESSION_WP_RUNTIME:-host}" = "docker" ]; then
		regression_docker_exec mkdir -p "$mu_dir"
		regression_docker_exec ln -sfn "$target" "$link_path"
		return 0
	fi

	mkdir -p "$mu_dir"
	if regression_is_plugin_ddev_web; then
		:
	elif [ -e "$mu_dir/../plugins/$slug/tests/bin/lib/silence-php-deprecations.php" ]; then
		target="../plugins/$slug/tests/bin/lib/silence-php-deprecations.php"
	elif command -v realpath >/dev/null 2>&1; then
		target="$(realpath --relative-to="$mu_dir" "$source_file" 2>/dev/null || echo "$source_file")"
	else
		target="$source_file"
	fi
	ln -sfn "$target" "$link_path"
}

regression_ensure_plugin_active() {
	local slug="$1"

	if regression_wp plugin is-active "$slug" >/dev/null 2>&1; then
		return 0
	fi

	echo "Activating plugin: $slug"
	regression_wp plugin activate "$slug"
}

regression_docker_exec() {
	docker exec "$REGRESSION_WP_CONTAINER" "$@"
}

regression_link_fixtures_plugin() {
	local root fixtures_dir plugins_dir link_path plugin_fixture target slug legacy_file
	root="$(regression_plugin_root)"
	slug="$(basename "$root")"
	fixtures_dir="$root/tests/ppcart-fixtures"

	if [ ! -f "$fixtures_dir/ppcart-fixtures.php" ]; then
		echo "Fixtures plugin not found at: $fixtures_dir" >&2
		exit 1
	fi

	# Legacy Docker mounts: wp-content/plugins/<slug>/tests/ppcart-fixtures
	target="${slug}/tests/ppcart-fixtures"

	if [ "${REGRESSION_WP_RUNTIME:-host}" = "docker" ]; then
		link_path="$(regression_plugins_dir)/ppcart-fixtures"
		legacy_file="$(regression_plugins_dir)/ppcart-fixtures.php"
		# When the plugin lives outside wp-content/plugins (common with DDEV
		# project layouts), prefer the explicit container plugin path.
		if [ -n "${REGRESSION_PLUGIN_CONTAINER_PATH:-}" ]; then
			target="${REGRESSION_PLUGIN_CONTAINER_PATH}/tests/ppcart-fixtures"
		fi
		regression_docker_exec rm -f "$legacy_file"
		regression_docker_exec ln -sfn "$target" "$link_path"
		echo "Linked $link_path -> $target (in $REGRESSION_WP_CONTAINER)"
		return 0
	fi

	plugins_dir="$(regression_plugins_dir)"
	link_path="$plugins_dir/ppcart-fixtures"
	legacy_file="$plugins_dir/ppcart-fixtures.php"
	plugin_fixture="$plugins_dir/$slug/tests/ppcart-fixtures/ppcart-fixtures.php"

	if [ -e "$legacy_file" ] || [ -L "$legacy_file" ]; then
		rm -f "$legacy_file"
	fi

	if [ -e "$plugin_fixture" ]; then
		target="$slug/tests/ppcart-fixtures"
	elif command -v realpath >/dev/null 2>&1; then
		target="$(realpath --relative-to="$plugins_dir" "$fixtures_dir" 2>/dev/null || echo "$fixtures_dir")"
	else
		target="$fixtures_dir"
	fi

	ln -sfn "$target" "$link_path"
	echo "Linked $link_path -> $target"
}

regression_link_cart_plugin() {
	local root plugins_dir link_path target slug
	root="$(regression_plugin_root)"
	slug="$(basename "$root")"

	if [ "${REGRESSION_WP_RUNTIME:-host}" = "docker" ]; then
		echo "Plugin already available in container at ${REGRESSION_PLUGIN_CONTAINER_PATH:-$slug} (Docker volume/bind mount)"
		return 0
	fi

	plugins_dir="$(regression_plugins_dir)"
	link_path="$plugins_dir/$slug"

	if [ -e "$link_path" ] && [ ! -L "$link_path" ]; then
		if [ -d "$link_path" ] && [ -z "$(find "$link_path" -mindepth 1 -maxdepth 1 -print -quit 2>/dev/null)" ]; then
			rmdir "$link_path"
		else
			echo "Plugin directory already present at $link_path; leaving it unchanged."
			return 0
		fi
	fi

	target="$root"
	ln -sfn "$target" "$link_path"
	echo "Linked $link_path -> $target"
}

regression_stripe_env_credentials_usable() {
	local publishable_key="${STRIPE_TEST_PUBLISHABLE_KEY:-}"
	local secret_key="${STRIPE_TEST_SECRET_KEY:-}"

	[ -n "$publishable_key" ] && [[ "$publishable_key" == pk_test_* ]] && \
		[ -n "$secret_key" ] && [[ "$secret_key" == sk_test_* || "$secret_key" == rk_test_* ]]
}

regression_stripe_connect_configured() {
	local connect_account="${STRIPE_TEST_CONNECT_ACCOUNT_ID:-}"

	[ -n "$connect_account" ] && [[ "$connect_account" == acct_* ]]
}

regression_set_env_var() {
	local env_file="$1"
	local key="$2"
	local value="$3"

	php -r '
		$file  = $argv[1];
		$key   = $argv[2];
		$value = $argv[3];

		if (! file_exists($file)) {
			touch($file);
		}

		$line    = $key . "=\"" . addcslashes($value, "\\\"$") . "\"";
		$content = file_get_contents($file);
		$lines   = false === $content || "" === $content ? array() : preg_split("/\r?\n/", rtrim($content, "\r\n"));
		$updated = false;

		foreach ($lines as &$existingLine) {
			if (0 === strpos($existingLine, $key . "=")) {
				$existingLine = $line;
				$updated      = true;
			}
		}

		unset($existingLine);

		if (! $updated) {
			$lines[] = $line;
		}

		file_put_contents($file, implode(PHP_EOL, $lines) . PHP_EOL);
	' "$env_file" "$key" "$value"
}

regression_reset_url_env_vars() {
	local env_file="$1"
	local key

	for key in \
		URL_ONE_TIME_STRIPE \
		URL_ONE_TIME_STRIPE_WITH_SALE_PRICE \
		URL_SUBS_STRIPE \
		URL_SUBS_STRIPE_WITH_SALE_PRICE \
		URL_CUSTOM_PRICE_STRIPE \
		URL_ONE_TIME_PAYPAL \
		URL_ONE_TIME_PAYPAL_WITH_SALE_PRICE \
		URL_SUBS_PAYPAL \
		URL_SUBS_PAYPAL_WITH_SALE_PRICE \
		URL_CUSTOM_PRICE_PAYPAL \
		URL_PRODUCT_FREE \
		CHECKOUT_STRIPE_URL \
		CHECKOUT_PAYPAL_URL \
		CHECKOUT_SUBSCRIPTION_URL \
		CHECKOUT_COUPON_URL \
		CHECKOUT_TERMS_URL \
		CHECKOUT_TAX_URL \
		CHECKOUT_BUMP_URL \
		CHECKOUT_UPSELL_URL \
		SMOKE_ADMIN_PATH
	do
		regression_set_env_var "$env_file" "$key" "/"
	done

	for key in \
		COUPON_100_CODE \
		EXPECTED_PRODUCT_PRICE \
		TAXABLE_ADDRESS_LINE1 \
		TAXABLE_CITY \
		TAXABLE_POSTAL_CODE
	do
		regression_set_env_var "$env_file" "$key" ""
	done
}

regression_sync_smoke_env_vars() {
	local env_file="$1"
	local json="$2"

	php -r '
		$data = json_decode(stream_get_contents(STDIN), true);
		if (!is_array($data) || empty($data["smoke"]["env"]) || !is_array($data["smoke"]["env"])) {
			exit(0);
		}
		foreach ($data["smoke"]["env"] as $key => $value) {
			echo strtoupper((string) $key), "\t", (string) $value, "\n";
		}
	' <<<"$json" | while IFS=$'\t' read -r key value; do
		[ -z "$key" ] && continue
		regression_set_env_var "$env_file" "$key" "$value"
		echo "  $key=$value"
	done
}

regression_sync_url_env_vars() {
	local env_file="$1"
	local json="$2"

	php -r '
		$data = json_decode(stream_get_contents(STDIN), true);
		if (!is_array($data) || empty($data["paths"]) || !is_array($data["paths"])) {
			fwrite(STDERR, "Fixture setup did not return paths.\n");
			exit(1);
		}
		foreach ($data["paths"] as $key => $path) {
			echo strtoupper((string) $key), "\t", (string) $path, "\n";
		}
	' <<<"$json" | while IFS=$'\t' read -r key path; do
		regression_set_env_var "$env_file" "$key" "$path"
		echo "  $key=$path"
	done
}

regression_sync_admin_env_vars() {
	local env_file="$1"
	local json="$2"

	php -r '
		$data = json_decode(stream_get_contents(STDIN), true);
		$required = array(
			"admin_product_id",
			"admin_order_id",
			"admin_subscription_id",
			"admin_customer_email",
			"admin_email_product_id",
			"admin_email_order_id",
			"admin_email_subscription_id",
			"admin_email_customer_email",
			"admin_product_post_type",
			"admin_order_post_type",
			"admin_subscription_post_type",
			"admin_product_cat_taxonomy",
			"admin_product_tag_taxonomy",
		);
		if (!is_array($data)) {
			fwrite(STDERR, "Admin fixture setup did not return JSON.\n");
			exit(1);
		}
		foreach ($required as $key) {
			if (!isset($data[$key]) || "" === (string) $data[$key]) {
				fwrite(STDERR, "Admin fixture setup is missing {$key}.\n");
				exit(1);
			}
			echo strtoupper($key), "\t", (string) $data[$key], "\n";
		}
	' <<<"$json" | while IFS=$'\t' read -r key value; do
		[ -z "$key" ] && continue
		regression_set_env_var "$env_file" "$key" "$value"
		echo "  $key=$value"
	done
}

regression_reset_admin_env_vars() {
	local env_file="$1"
	local key

	for key in \
		ADMIN_PRODUCT_ID \
		ADMIN_ORDER_ID \
		ADMIN_SUBSCRIPTION_ID \
		ADMIN_CUSTOMER_EMAIL \
		ADMIN_EMAIL_PRODUCT_ID \
		ADMIN_EMAIL_ORDER_ID \
		ADMIN_EMAIL_SUBSCRIPTION_ID \
		ADMIN_EMAIL_CUSTOMER_EMAIL
	do
		regression_set_env_var "$env_file" "$key" ""
	done
}
