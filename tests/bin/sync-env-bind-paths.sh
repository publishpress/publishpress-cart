#!/usr/bin/env bash
# Write REPO_ROOT, DEV_WORKSPACE_REAL, and absolute CACHE_PATH into .env.
#
# Docker Compose v5 interpolates compose.yaml from the env file, not the
# process environment. Fake placeholders such as /absolute/path/to/... must
# not survive — CI copies .env.example and `mkdir /absolute` fails.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd -L)"
ENV_FILE="${ENV_FILE:-$ROOT/.env}"

if [[ ! -f "$ENV_FILE" ]]; then
    exit 0
fi

DEV_WS="$ROOT/vendor/publishpress/dev-workspace"
if [[ -d "$DEV_WS" ]]; then
    DEV_WORKSPACE_REAL="$(cd "$DEV_WS" && pwd -P)"
else
    DEV_WORKSPACE_REAL="$DEV_WS"
fi

CACHE_PATH="$(awk -F= '
    $1 == "CACHE_PATH" {
        val = substr($0, index($0, "=") + 1)
        gsub(/^["[:space:]]+|["[:space:]]+$/, "", val)
        print val
        exit
    }
' "$ENV_FILE")"

if [[ -z "$CACHE_PATH" ]]; then
    CACHE_PATH="./dev-workspace-cache"
fi

if [[ "$CACHE_PATH" != /* ]]; then
    CACHE_PATH="$ROOT/${CACHE_PATH#./}"
fi

upsert_dotenv() {
    local file="$1" key="$2" value="$3"
    local tmp
    tmp="$(mktemp)"
    awk -v k="$key" -v v="$value" '
        BEGIN { done = 0 }
        $0 ~ "^" k "=" {
            print k "=\"" v "\""
            done = 1
            next
        }
        { print }
        END { if (!done) print k "=\"" v "\"" }
    ' "$file" > "$tmp"
    # Rewrite in place when we can. `mv` replaces the inode, so a container
    # user that maps to nobody:nogroup leaves mode 600 and the host cannot
    # read .env (Codeception params then fail).
    if [[ -w "$file" ]]; then
        cat "$tmp" > "$file"
        rm -f "$tmp"
    else
        mv "$tmp" "$file"
    fi
    # `mv` replaces the inode. The dev-workspace terminal runs as container
    # root, which is nobody:nogroup on the host, so .env becomes mode 600
    # and Codeception cannot load params. Give the file back to the repo owner.
    local repo_uid repo_gid file_uid
    repo_uid="$(stat -c '%u' "$(dirname "$file")")"
    repo_gid="$(stat -c '%g' "$(dirname "$file")")"
    file_uid="$(stat -c '%u' "$file")"
    if [[ "$file_uid" != "$repo_uid" ]]; then
        chown "${repo_uid}:${repo_gid}" "$file" 2>/dev/null || true
    fi
}

upsert_dotenv "$ENV_FILE" REPO_ROOT "$ROOT"
upsert_dotenv "$ENV_FILE" DEV_WORKSPACE_REAL "$DEV_WORKSPACE_REAL"
upsert_dotenv "$ENV_FILE" CACHE_PATH "$CACHE_PATH"
