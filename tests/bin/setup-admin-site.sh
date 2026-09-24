#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Delegate to the unified setup script so CI and local dev share the same
# provisioning, linking and fixture logic.
bash "$SCRIPT_DIR/setup-site.sh" admin
