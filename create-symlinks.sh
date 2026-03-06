#!/bin/bash
#
# This is for developing purpose only.
#
# Creates symlinks in an OpenCart 3 upload/ directory for the Newsman plugin.
# For every file/dir in newsman/src/, if the corresponding path in upload/ is
# a real directory (shared with OC3 core), it recurses into it. Otherwise it
# creates an absolute symlink pointing to the newsman/src/ entry.
#
# Usage:
#   ./create-symlinks.sh <newsman_dir> <opencart_upload_dir>
#
# Example:
#   ./create-symlinks.sh /var/share/www/opencart3/newsman /var/share/www/opencart3/upload

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

# Recursively walk src_dir. For each entry:
#   - if it maps to a real directory in upload_dir, recurse
#   - otherwise remove any existing symlink and create a new one
process_dir() {
    local src_dir="$1"
    local upload_dir="$2"

    for src_entry in "$src_dir"/*; do
        [ -e "$src_entry" ] || continue  # skip if glob matched nothing

        local name
        name="$(basename "$src_entry")"
        local upload_entry="$upload_dir/$name"

        if [ -d "$upload_entry" ] && [ ! -L "$upload_entry" ]; then
            # Real directory exists in upload/ — shared with OC3 core, recurse into it
            process_dir "$src_entry" "$upload_entry"
        else
            # Not a real directory in upload/ — create symlink
            if [ -L "$upload_entry" ]; then
                rm "$upload_entry"
                echo "Replaced: $upload_entry"
            else
                echo "Created:  $upload_entry"
            fi
            ln -s "$src_entry" "$upload_entry"
            link_count=$((link_count + 1))
        fi
    done
}

echo "Newsman src : $NEWSMAN_SRC"
echo "OC3 upload  : $UPLOAD_DIR"
echo ""

process_dir "$NEWSMAN_SRC" "$UPLOAD_DIR"

echo ""
echo "Done. $link_count symlink(s) created/replaced."
