#!/bin/bash
#
# Deletes the old Newsman extension v1.0.0 files from an OpenCart 3 directory.
#
# The old extension was not installed via OpenCart admin, so its files are not
# tracked in the oc_extension_path table. This script removes the known files
# from the OpenCart directory.
#
# Directories are intentionally NOT deleted — only files are removed.
#
# Usage:
#   ./delete-old-v1.0.0-extension.sh <opencart_upload_dir>
#
# Example:
#   ./delete-old-v1.0.0-extension.sh /var/www/html/opencart/upload

set -e

if [ $# -ne 1 ]; then
    echo "Usage: $0 <opencart_upload_dir>"
    echo "  opencart_upload_dir — absolute path to the OpenCart 3 root directory"
    exit 1
fi

UPLOAD_DIR="$1"

if [ ! -d "$UPLOAD_DIR" ]; then
    echo "Error: $UPLOAD_DIR does not exist."
    exit 1
fi

FILES=(
    "admin/controller/extension/analytics/newsmanremarketing.php"
    "admin/controller/extension/module/newsman.php"
    "admin/language/en-gb/extension/analytics/newsmanremarketing.php"
    "admin/language/en-gb/extension/module/newsman.php"
    "admin/view/stylesheet/newsman.css"
    "admin/view/template/extension/analytics/newsmanremarketing.twig"
    "admin/view/template/extension/module/newsman.twig"
    "catalog/controller/extension/analytics/newsmanremarketing.php"
    "catalog/controller/extension/module/newsman.php"
    "catalog/model/extension/module/newsman.php"
    "catalog/view/theme/default/template/extension/module/newsman.twig"
    "system/library/Newsman/Client/Exception.php"
    "system/library/Newsman/Client.php"
)

file_count=0

echo "OpenCart dir: $UPLOAD_DIR"
echo ""

for file in "${FILES[@]}"; do
    filepath="$UPLOAD_DIR/$file"
    if [ -f "$filepath" ]; then
        rm "$filepath"
        echo "Deleted: $filepath"
        file_count=$((file_count + 1))
    else
        echo "Not found: $filepath"
    fi
done

echo ""
echo "Done. $file_count file(s) deleted."
