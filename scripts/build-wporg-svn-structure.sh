#!/usr/bin/env bash
set -euo pipefail

# Build the official WordPress.org SVN Upload Structure
# Output:
#   build/wordpress-org-svn/
#   ├── assets/
#   ├── branches/
#   ├── tags/
#   │   └── 0.1.0/
#   └── trunk/
#
# Also creates: release/wordpress-org-svn-upload.zip

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
SVN_OUT="$ROOT_DIR/build/wordpress-org-svn"
RELEASE_DIR="$ROOT_DIR/release"
PACKAGE_SRC="$ROOT_DIR/build/package/lunexia-live-feedback"
VERSION="0.1.0"

echo "========================================================"
echo " Building WordPress.org SVN Complete Upload Structure   "
echo "========================================================"

# 1. Regenerate icons, banners, and screenshots
echo "1. Generating WordPress.org directory assets & screenshots..."
python3 "$ROOT_DIR/scripts/generate-wporg-assets.py"
python3 "$ROOT_DIR/scripts/generate-screenshots.py"

# 2. Package plugin runtime files
echo "2. Packaging clean plugin runtime..."
"$ROOT_DIR/scripts/package-plugin.sh"

# 3. Create fresh SVN structure
echo "3. Creating WordPress.org SVN directory structure..."
rm -rf "$SVN_OUT"
mkdir -p "$SVN_OUT/assets"
mkdir -p "$SVN_OUT/branches"
mkdir -p "$SVN_OUT/trunk"
mkdir -p "$SVN_OUT/tags/$VERSION"

# 4. Copy assets (banners, icons, screenshots)
echo "4. Copying directory assets into assets/..."
cp -r "$ROOT_DIR/wporg-assets/"* "$SVN_OUT/assets/"

# 5. Copy plugin runtime to trunk/
echo "5. Copying plugin runtime files into trunk/..."
cp -r "$PACKAGE_SRC/"* "$SVN_OUT/trunk/"

# 6. Copy plugin runtime to tags/0.1.0/
echo "6. Copying plugin runtime files into tags/$VERSION/..."
cp -r "$PACKAGE_SRC/"* "$SVN_OUT/tags/$VERSION/"

# 7. Create ready-to-upload ZIP archive
mkdir -p "$RELEASE_DIR"
ZIP_FILE="$RELEASE_DIR/wordpress-org-svn-structure.zip"
rm -f "$ZIP_FILE"

echo "7. Creating ZIP: $ZIP_FILE..."
pushd "$SVN_OUT" >/dev/null
zip -r -X "$ZIP_FILE" . -x "*/.*" -x "*__MACOSX*" >/dev/null
popd >/dev/null

echo ""
echo "✅ SUCCESS! WordPress.org SVN structure prepared at:"
echo "   $SVN_OUT"
echo ""
echo "Structure layout:"
tree "$SVN_OUT" -L 2 2>/dev/null || find "$SVN_OUT" -maxdepth 2 -print | sort
echo ""
echo "📦 Downloadable ZIP created at:"
echo "   $ZIP_FILE"
