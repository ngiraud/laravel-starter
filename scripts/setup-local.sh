#!/usr/bin/env bash
set -euo pipefail

PACKAGE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PACKAGE_NAME="$(basename "$PACKAGE_DIR")"
PROJECTS_BASE="$(dirname "$(dirname "$PACKAGE_DIR")")"

usage() {
    echo "Usage: $0 <project-name>"
    echo "  project-name: name of a Laravel project in $PROJECTS_BASE"
    exit 1
}

[[ $# -eq 1 ]] || usage

PROJECT_NAME="$1"
PROJECT_DIR="$PROJECTS_BASE/$PROJECT_NAME"
COMPOSER_JSON="$PROJECT_DIR/composer.json"

[[ -d "$PROJECT_DIR" ]] || { echo "Error: project not found at $PROJECT_DIR"; exit 1; }
[[ -f "$COMPOSER_JSON" ]] || { echo "Error: no composer.json found in $PROJECT_DIR"; exit 1; }

RELATIVE_PATH="../laravel-packages/$PACKAGE_NAME"

echo "Configuring $COMPOSER_JSON..."

UPDATED=$(jq \
    --arg url "$RELATIVE_PATH" \
    '
    # Add/replace path repository
    .repositories = (
        (.repositories // [])
        | map(select(.type != "path" or .url != $url))
        | . + [{"type": "path", "url": $url}]
    )
    |
    # Add reset script
    .scripts.reset = [
        "git reset --hard",
        "rm -rf vendor",
        "@composer install"
    ]
    |
    # Allow dev packages from path repo
    ."minimum-stability" = "dev"
    |
    ."prefer-stable" = true
    ' \
    "$COMPOSER_JSON")

echo "$UPDATED" > "$COMPOSER_JSON"

echo "Done. Next steps:"
echo "  cd $PROJECT_DIR"
echo "  composer require nicolas-giraud/$PACKAGE_NAME:@dev"
