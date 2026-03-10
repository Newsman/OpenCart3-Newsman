#!/bin/bash
#
# Builds an .ocmod.zip archive for installing the Newsman extension
# via OpenCart 3 Admin > Extensions > Installer.
#
# Steps:
#   1. Deletes the existing vendor/ dir in src/system/library/newsman/
#   2. Runs composer install --no-dev to get production dependencies only
#   3. Removes non-runtime files from vendor packages (tests, examples, etc.)
#   4. Creates the .ocmod.zip with the upload/ directory structure
#
# Usage:
#   ./build-ocmod-zip.sh <extension_dir> <php_binary> <composer_path> <output_zip>
#
# Example:
#   ./build-ocmod-zip.sh /path/to/opencart/newsman php8.2 /usr/local/bin/composer ./newsman.ocmod.zip

set -e

if [ $# -ne 4 ]; then
    echo "Usage: $0 <extension_dir> <php_binary> <composer_path> <output_zip>"
    echo "  extension_dir  — path to the Newsman plugin root (contains src/)"
    echo "  php_binary     — PHP binary to use (e.g. php8.2)"
    echo "  composer_path  — path to the Composer binary (e.g. /usr/local/bin/composer)"
    echo "  output_zip     — output path for the .ocmod.zip archive"
    exit 1
fi

EXTENSION_DIR="$(realpath "$1")"
PHP_BIN="$2"
COMPOSER_PATH="$3"
OUTPUT_ZIP="$4"

SRC_DIR="$EXTENSION_DIR/src"
LIBRARY_DIR="$SRC_DIR/system/library/newsman"

if [ ! -d "$SRC_DIR" ]; then
    echo "Error: $SRC_DIR does not exist."
    exit 1
fi

if [ ! -f "$LIBRARY_DIR/composer.json" ]; then
    echo "Error: $LIBRARY_DIR/composer.json does not exist."
    exit 1
fi

# Resolve output path to absolute
case "$OUTPUT_ZIP" in
    /*) ;;
    *) OUTPUT_ZIP="$(pwd)/$OUTPUT_ZIP" ;;
esac

# Ensure output ends with .ocmod.zip
if [[ "$OUTPUT_ZIP" != *.ocmod.zip ]]; then
    echo "Warning: Output file should end with .ocmod.zip for OpenCart to accept it."
fi

echo "Extension dir : $EXTENSION_DIR"
echo "PHP binary    : $PHP_BIN"
echo "Composer      : $COMPOSER_PATH"
echo "Output        : $OUTPUT_ZIP"
echo ""

# Step 1: Delete existing vendor dir and composer.lock
echo "=== Step 1: Cleaning vendor directory and composer.lock ==="
if [ -d "$LIBRARY_DIR/vendor" ]; then
    rm -rf "$LIBRARY_DIR/vendor"
    echo "Deleted: $LIBRARY_DIR/vendor"
else
    echo "No vendor directory found, skipping."
fi
if [ -f "$LIBRARY_DIR/composer.lock" ]; then
    rm "$LIBRARY_DIR/composer.lock"
    echo "Deleted: $LIBRARY_DIR/composer.lock"
fi
echo ""

# Step 2: Run composer install --no-dev
echo "=== Step 2: Running composer install --no-dev ==="
cd "$LIBRARY_DIR"
"$PHP_BIN" "$COMPOSER_PATH" install --no-dev -vvv

# Remove composer.lock — not needed in the archive
if [ -f "$LIBRARY_DIR/composer.lock" ]; then
    rm "$LIBRARY_DIR/composer.lock"
    echo "Deleted: $LIBRARY_DIR/composer.lock"
fi
echo ""

# Step 3: Remove non-runtime files from vendor packages
echo "=== Step 3: Cleaning non-runtime files from vendor ==="
VENDOR_DIR="$LIBRARY_DIR/vendor"

# Remove known non-runtime directories from packages
for dir in tests test examples example libs wiki doc docs .github .settings; do
    find "$VENDOR_DIR" -mindepth 2 -maxdepth 3 -type d -name "$dir" | while read -r d; do
        rm -rf "$d"
        echo "Removed: $d"
    done
done

# Remove non-runtime files from package roots
for pattern in phpunit.xml phpunit.xml.dist phpunit.xml.dist.bak .travis.yml .gitignore .gitattributes .gitmodules .buildpath .project .eclipse-PHP-formatter.xml runtest.sh README.md CHANGELOG.md CONTRIBUTING.md; do
    find "$VENDOR_DIR" -mindepth 2 -maxdepth 3 -type f -name "$pattern" | while read -r f; do
        rm "$f"
        echo "Removed: $f"
    done
done
echo ""

# Step 4: Build the .ocmod.zip
echo "=== Step 4: Creating .ocmod.zip ==="

# Remove previous output if it exists
[ -f "$OUTPUT_ZIP" ] && rm "$OUTPUT_ZIP"

# Create zip from src/ with upload/ as the root directory
cd "$SRC_DIR"
zip -r "$OUTPUT_ZIP" . --exclude '*.idea*' | tail -1
# Rename the root from . to upload/ inside the zip
# Actually, we need to create a temp dir with upload/ structure

# The zip needs upload/ as root dir, so we use a temp dir
TEMP_DIR=$(mktemp -d)
trap 'rm -rf "$TEMP_DIR"' EXIT

mkdir -p "$TEMP_DIR/upload"
cp -a "$SRC_DIR"/. "$TEMP_DIR/upload/"

# Remove .idea if it got copied
rm -rf "$TEMP_DIR/upload/.idea"

cd "$TEMP_DIR"
[ -f "$OUTPUT_ZIP" ] && rm "$OUTPUT_ZIP"
zip -r "$OUTPUT_ZIP" upload/

echo ""
echo "=== Done ==="
echo "Archive created: $OUTPUT_ZIP"
echo "Size: $(du -h "$OUTPUT_ZIP" | cut -f1)"
