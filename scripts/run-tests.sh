#!/usr/bin/env bash
set -euo pipefail

if ! command -v php >/dev/null 2>&1; then
  if [[ -f "${HOME}/.nix-profile/etc/profile.d/nix.sh" ]]; then
    # shellcheck source=/dev/null
    . "${HOME}/.nix-profile/etc/profile.d/nix.sh"
  fi
fi

if ! command -v php >/dev/null 2>&1; then
  echo "PHP is not installed. Install Nix, then run:" >&2
  echo "  nix-env -iA nixpkgs.php83 nixpkgs.php83Extensions.sqlite3 nixpkgs.php83Extensions.mbstring nixpkgs.php83Extensions.bcmath nixpkgs.php83Extensions.curl nixpkgs.php83Extensions.dom nixpkgs.php83Packages.composer" >&2
  exit 1
fi

cd "$(dirname "$0")/.."

if [[ ! -f vendor/autoload.php ]]; then
  composer install --no-interaction --prefer-dist
fi

php vendor/bin/phpunit "$@"
