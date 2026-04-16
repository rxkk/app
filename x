#!/usr/bin/env bash

# readlink for working by symlinks
DIR="$(dirname "$(readlink -f "${BASH_SOURCE[0]}")")"
exec php -d xdebug.log_level=0 "$DIR/cli/index.php" "$@"
