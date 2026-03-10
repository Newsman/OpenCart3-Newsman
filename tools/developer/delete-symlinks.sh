#!/bin/bash
#
# This is for developing purpose only.
#
# Deletes symlinks in an OpenCart 3 upload/ directory that point to the
# Newsman plugin's src/ directory. Only removes symlinks whose target
# starts with the Newsman src/ path.
#
# Usage:
#   ./delete-symlinks.sh <newsman_dir> <opencart_upload_dir>
#
# Example:
#   ./delete-symlinks.sh /var/share/www/opencart3/newsman /var/share/www/opencart3/upload

set -e

if [ $# -ne 2 ]; then
    echo "Usage: $0 <newsman_dir> <opencart_upload_dir>"
    echo "  newsman_dir        — path to the Newsman plugin root (contains src/)"
    echo "  opencart_upload_dir — path to the OpenCart 3 upload/ directory"
    exit 1
fi

NEWSMAN_SRC="$(realpath "$1")/src"
UPLOAD_DIR="$(realpath "$2")"

if [ ! -d "$NEWSMAN_SRC" ]; then
    echo "Error: $NEWSMAN_SRC does not exist."
    exit 1
fi

if [ ! -d "$UPLOAD_DIR" ]; then
    echo "Error: $UPLOAD_DIR does not exist."
    exit 1
fi

link_count=0

# Recursively walk upload_dir. For each entry:
#   - if it is a symlink pointing into NEWSMAN_SRC, remove it
#   - if it is a real directory, recurse into it
process_dir() {
    local upload_dir="$1"

    for entry in "$upload_dir"/*; do
        [ -e "$entry" ] || [ -L "$entry" ] || continue  # handle broken symlinks too

        if [ -L "$entry" ]; then
            local target
            target="$(readlink -f "$entry" 2>/dev/null || readlink "$entry")"
            if [[ "$target" == "$NEWSMAN_SRC"* ]]; then
                rm "$entry"
                echo "Deleted: $entry"
                link_count=$((link_count + 1))
            fi
        elif [ -d "$entry" ]; then
            process_dir "$entry"
        fi
    done
}

echo "Newsman src : $NEWSMAN_SRC"
echo "OC3 upload  : $UPLOAD_DIR"
echo ""

process_dir "$UPLOAD_DIR"

echo ""
echo "Done. $link_count symlink(s) deleted."
