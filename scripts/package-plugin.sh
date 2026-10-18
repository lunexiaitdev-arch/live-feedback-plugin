#!/usr/bin/env bash
set -euo pipefail

# Package a minimal plugin ZIP excluding dev files to reduce size.
# Result: release/lunexia-live-feedback.zip

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
BUILD_DIR="$ROOT_DIR/build/package"
RELEASE_DIR="$ROOT_DIR/release"
PKG_NAME="lunexia-live-feedback"

rm -rf "$BUILD_DIR" "$RELEASE_DIR"
mkdir -p "$BUILD_DIR/$PKG_NAME" "$RELEASE_DIR"

echo "Copying runtime files..."

# Core plugin files
cp "$ROOT_DIR/lunexia-live-feedback.php" "$BUILD_DIR/$PKG_NAME/"
cp "$ROOT_DIR/uninstall.php" "$BUILD_DIR/$PKG_NAME/" || true

# PHP source
rsync -a --delete --omit-dir-times --no-perms \
  --exclude 'tests' \
  --exclude '*.distignore' \
  --exclude 'admin/' \
  --exclude '.git/' \
  "$ROOT_DIR/src/" "$BUILD_DIR/$PKG_NAME/src/"

# Minimal vendor (autoload only)
mkdir -p "$BUILD_DIR/$PKG_NAME/vendor"
if [ -f "$ROOT_DIR/vendor/autoload.php" ]; then
  cp "$ROOT_DIR/vendor/autoload.php" "$BUILD_DIR/$PKG_NAME/vendor/"
fi

# Public runtime assets
mkdir -p "$BUILD_DIR/$PKG_NAME/assets"
rsync -a --delete "$ROOT_DIR/assets/css/" "$BUILD_DIR/$PKG_NAME/assets/css/" || true
rsync -a --delete "$ROOT_DIR/assets/img/" "$BUILD_DIR/$PKG_NAME/assets/img/" || true
rsync -a --delete "$ROOT_DIR/assets/js/" "$BUILD_DIR/$PKG_NAME/assets/js/" || true
if [ -d "$ROOT_DIR/assets/dist" ]; then
  mkdir -p "$BUILD_DIR/$PKG_NAME/assets/dist"
  rsync -a --delete "$ROOT_DIR/assets/dist/" "$BUILD_DIR/$PKG_NAME/assets/dist/" || true
fi

# Admin React source code (included to satisfy WordPress.org Guidelines 1 & 4)
if [ -d "$ROOT_DIR/admin" ]; then
  mkdir -p "$BUILD_DIR/$PKG_NAME/admin"
  rsync -a --delete \
    --exclude 'node_modules' \
    --exclude 'dist' \
    --exclude '.vite' \
    --exclude '*.log' \
    "$ROOT_DIR/admin/" "$BUILD_DIR/$PKG_NAME/admin/" || true
fi

# Documentation, translation and extras
cp -n "$ROOT_DIR/readme.txt" "$BUILD_DIR/$PKG_NAME/" || true
cp -n "$ROOT_DIR/LICENSE" "$BUILD_DIR/$PKG_NAME/" || true
cp -n "$ROOT_DIR/composer.json" "$BUILD_DIR/$PKG_NAME/" || true
if [ -d "$ROOT_DIR/languages" ]; then
  mkdir -p "$BUILD_DIR/$PKG_NAME/languages"
  rsync -a "$ROOT_DIR/languages/" "$BUILD_DIR/$PKG_NAME/languages/" || true
fi

echo "Removing dev and hidden files from package..."
find "$BUILD_DIR/$PKG_NAME" -name 'node_modules' -prune -exec rm -rf '{}' + || true
find "$BUILD_DIR/$PKG_NAME" -name '*.map' -type f -delete || true
rm -f "$BUILD_DIR/$PKG_NAME/admin/package-lock.json" || true
find "$BUILD_DIR/$PKG_NAME" -name '.*' -not -name '.' -exec rm -rf {} + 2>/dev/null || true

echo "Creating ZIP..."
pushd "$BUILD_DIR" >/dev/null
zip -r -X "$RELEASE_DIR/${PKG_NAME}.zip" "$PKG_NAME" -x "*/.*" -x "*__MACOSX*" >/dev/null
popd >/dev/null

echo "Package created: $RELEASE_DIR/${PKG_NAME}.zip"
du -h "$RELEASE_DIR/${PKG_NAME}.zip" | awk '{print $1 " - " $2}'

echo "Done."
