<?php

declare(strict_types=1);

$pluginRoot = rtrim((string) ($argv[1] ?? ''), '/');
$host       = (string) ($argv[2] ?? '');
$slug       = (string) ($argv[3] ?? '');

if ($pluginRoot === '' || $slug === '') {
	fwrite(STDERR, "Usage: detect-docker-wp.php <plugin_root> <host> <slug>\n");
	exit(1);
}

$suffix     = '/wp-content/plugins/' . $slug;
$containers = json_decode((string) stream_get_contents(STDIN), true);
if (! is_array($containers)) {
	fwrite(STDERR, "Unable to parse docker inspect JSON.\n");
	exit(1);
}

/**
 * @param array<string, mixed> $container
 * @return array<string, string>
 */
function regression_detect_container_env(array $container): array {
	$map = array();

	foreach (($container['Config']['Env'] ?? array()) as $env) {
		if (! is_string($env) || ! str_contains($env, '=')) {
			continue;
		}

		[ $key, $value ] = explode('=', $env, 2);
		$map[ $key ]     = $value;
	}

	return $map;
}

/**
 * @param array<string, mixed> $container
 */
function regression_detect_wp_home_from_wordpress_extra(array $container): string {
	foreach (($container['Config']['Env'] ?? array()) as $env) {
		if (! is_string($env) || ! str_starts_with($env, 'WORDPRESS_CONFIG_EXTRA=')) {
			continue;
		}

		if (preg_match("/WP_HOME',\s*'([^']+)'/", $env, $homeMatch)) {
			return $homeMatch[1];
		}

		if (preg_match('/WP_HOME",\s*"([^"]+)"/', $env, $homeMatch)) {
			return $homeMatch[1];
		}
	}

	return '';
}

$candidates = array();

foreach ($containers as $container) {
	if (! is_array($container)) {
		continue;
	}

	$env        = regression_detect_container_env($container);
	$pluginDest = '';
	$wpPath     = '';
	$wpHome     = regression_detect_wp_home_from_wordpress_extra($container);

	foreach (($container['Mounts'] ?? array()) as $mount) {
		$source      = rtrim((string) ($mount['Source'] ?? ''), '/');
		$destination = (string) ($mount['Destination'] ?? '');
		if ($source === $pluginRoot && str_ends_with($destination, $suffix)) {
			$pluginDest = $destination;
			$wpPath     = substr($pluginDest, 0, -strlen($suffix));
			if ($wpPath === '') {
				$wpPath = '/';
			}
			break;
		}
	}

	if ($pluginDest === '' && ($env['IS_DDEV_PROJECT'] ?? '') === 'true') {
		foreach (($container['Mounts'] ?? array()) as $mount) {
			$source      = rtrim((string) ($mount['Source'] ?? ''), '/');
			$destination = rtrim((string) ($mount['Destination'] ?? ''), '/');
			if ($source !== $pluginRoot || $destination === '') {
				continue;
			}

			$docroot    = trim((string) ($env['DDEV_DOCROOT'] ?? $env['DOCROOT'] ?? ''), '/');
			$wpPath     = $docroot === '' ? $destination : $destination . '/' . $docroot;
			$pluginDest = $destination;
			if ($wpHome === '') {
				$wpHome = (string) ($env['DDEV_PRIMARY_URL'] ?? '');
			}
			if ($wpHome === '' && ($env['DDEV_HOSTNAME'] ?? '') !== '') {
				$scheme = (string) ($env['DDEV_SCHEME'] ?? 'https');
				$wpHome = $scheme . '://' . $env['DDEV_HOSTNAME'];
			}
			break;
		}
	}

	if ($pluginDest === '') {
		continue;
	}

	$candidates[] = array(
		'name'   => ltrim((string) ($container['Name'] ?? ''), '/'),
		'wp'     => $wpPath,
		'plugin' => $pluginDest,
		'home'   => $wpHome,
	);
}

if ($candidates === array()) {
	exit(1);
}

$selected = $candidates[0];
foreach ($candidates as $match) {
	$homeHost = parse_url((string) $match['home'], PHP_URL_HOST);
	if (is_string($homeHost) && $homeHost !== '' && $homeHost === $host) {
		$selected = $match;
		break;
	}
}

echo $selected['name'], "\t", $selected['wp'], "\t", $selected['plugin'], "\n";
