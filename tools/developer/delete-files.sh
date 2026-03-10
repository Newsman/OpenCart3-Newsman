#!/bin/bash
#
# This is for developing purpose only.
#
# Deletes Newsman extension files from an OpenCart 3 upload/ directory.
#
# When a user installs the extension via OpenCart 3 admin (Extensions → Install),
# OpenCart copies the files from the extension archive into the upload/ directory.
# This script removes those copied files by matching them against the extension's
# src/ directory structure.
#
# Only real files are deleted (symlinks are skipped — use delete-symlinks.sh for those).
# After deleting files, empty directories left behind are cleaned up, but only if
# they are Newsman-specific (not shared OC3 core directories).
#
# Usage:
#   ./delete-files.sh <newsman_dir> <opencart_upload_dir>
#
# Example:
#   ./delete-files.sh /var/share/www/opencart3/newsman /var/share/www/opencart3/upload

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

# Use sudo if available — may prompt for password if needed.
if command -v sudo &>/dev/null; then
    SUDO="sudo"
else
    SUDO=""
fi

# Directories that should be deleted as a whole (not recursed into).
# Paths are relative to the upload dir (e.g. system/library/newsman).
DELETE_WHOLE_DIRS="system/library/newsman"

file_count=0
dir_count=0

# Phase 1: Delete files.
# Walk the extension's src/ tree. For each file in src/, check if the
# corresponding path in upload/ exists as a real file (not a symlink).
# If so, delete it. Directories in DELETE_WHOLE_DIRS are removed entirely.
delete_files() {
    local src_dir="$1"
    local upload_dir="$2"

    for src_entry in "$src_dir"/*; do
        [ -e "$src_entry" ] || continue

        local name
        name="$(basename "$src_entry")"
        local upload_entry="$upload_dir/$name"

        # Compute relative path from UPLOAD_DIR for whole-dir check.
        local rel_path="${upload_entry#"$UPLOAD_DIR"/}"

        if [ "$rel_path" = "$DELETE_WHOLE_DIRS" ]; then
            # Delete this directory entirely (real dir or symlink).
            if [ -d "$upload_entry" ] && [ ! -L "$upload_entry" ]; then
                $SUDO rm -rf "$upload_entry"
                echo "Deleted dir:  $upload_entry"
                dir_count=$((dir_count + 1))
            elif [ -L "$upload_entry" ]; then
                $SUDO rm -f "$upload_entry"
                echo "Deleted link: $upload_entry"
                dir_count=$((dir_count + 1))
            fi
        elif [ -d "$src_entry" ] && [ ! -L "$src_entry" ]; then
            # Source is a directory — recurse if the upload dir exists
            if [ -d "$upload_entry" ] && [ ! -L "$upload_entry" ]; then
                delete_files "$src_entry" "$upload_entry"
            fi
        elif [ -f "$upload_entry" ] && [ ! -L "$upload_entry" ]; then
            $SUDO rm -f "$upload_entry"
            echo "Deleted file: $upload_entry"
            file_count=$((file_count + 1))
        fi
    done
}

# Phase 2: Clean up empty directories.
# Walk the extension's src/ tree again. For each directory in src/ that is
# Newsman-specific (i.e., does not exist in upload/ as a shared OC3 core dir
# with non-Newsman content), remove it if it's now empty.
# We process depth-first so child dirs are removed before parents.
cleanup_dirs() {
    local src_dir="$1"
    local upload_dir="$2"

    for src_entry in "$src_dir"/*; do
        [ -e "$src_entry" ] || continue

        local name
        name="$(basename "$src_entry")"
        local upload_entry="$upload_dir/$name"

        if [ -d "$src_entry" ] && [ ! -L "$src_entry" ]; then
            if [ -d "$upload_entry" ] && [ ! -L "$upload_entry" ]; then
                # Recurse first (depth-first)
                cleanup_dirs "$src_entry" "$upload_entry"

                # Try to remove the directory if it's now empty
                if $SUDO rmdir "$upload_entry" 2>/dev/null; then
                    echo "Removed empty dir: $upload_entry"
                    dir_count=$((dir_count + 1))
                fi
            fi
        fi
    done
}

echo "Newsman src : $NEWSMAN_SRC"
echo "OC3 upload  : $UPLOAD_DIR"
echo ""

delete_files "$NEWSMAN_SRC" "$UPLOAD_DIR"
echo ""
cleanup_dirs "$NEWSMAN_SRC" "$UPLOAD_DIR"

echo ""
echo "Done. $file_count file(s) deleted, $dir_count empty dir(s) removed."
