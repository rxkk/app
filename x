#!/usr/bin/env bash
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
exec php -d xdebug.log_level=0 "$DIR/cli/index.php" "$@"
