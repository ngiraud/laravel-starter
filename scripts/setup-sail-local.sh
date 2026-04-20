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

[[ -d "$PROJECT_DIR" ]] || { echo "Error: project not found at $PROJECT_DIR"; exit 1; }

# Find docker-compose file (Sail uses docker-compose.yml or compose.yml)
COMPOSE_FILE=""
for candidate in "docker-compose.yml" "compose.yml" "docker-compose.yaml" "compose.yaml"; do
    if [[ -f "$PROJECT_DIR/$candidate" ]]; then
        COMPOSE_FILE="$PROJECT_DIR/$candidate"
        break
    fi
done

[[ -n "$COMPOSE_FILE" ]] || { echo "Error: no compose file found in $PROJECT_DIR"; exit 1; }

VOLUME_ENTRY="../laravel-packages/$PACKAGE_NAME:/var/www/laravel-packages/$PACKAGE_NAME"

# Check if volume already present
if grep -qF "$VOLUME_ENTRY" "$COMPOSE_FILE"; then
    echo "Volume already present in $COMPOSE_FILE, nothing to do."
    exit 0
fi

echo "Adding volume to $COMPOSE_FILE..."

python3 - "$COMPOSE_FILE" "$VOLUME_ENTRY" <<'EOF'
import sys

compose_path = sys.argv[1]
volume_entry = sys.argv[2]

with open(compose_path, 'r') as f:
    lines = f.readlines()

new_lines = []
i = 0
inserted = False

while i < len(lines):
    line = lines[i]
    new_lines.append(line)

    # Find first volume line under services (e.g. "            - '.:/var/www/html'")
    if not inserted and line.strip().startswith("- '.:/var/www/html'"):
        indent = len(line) - len(line.lstrip())
        new_lines.append(' ' * indent + f"- '{volume_entry}'\n")
        inserted = True

    i += 1

if not inserted:
    print("Warning: could not find '- '.:/var/www/html'' anchor. Appending volume manually is required.", file=sys.stderr)
    sys.exit(1)

with open(compose_path, 'w') as f:
    f.writelines(new_lines)

print(f"Volume '{volume_entry}' added successfully.")
EOF

echo "Done. You can now run: cd $PROJECT_DIR && ./vendor/bin/sail up -d"
