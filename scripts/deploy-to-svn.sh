#!/usr/bin/env bash
set -euo pipefail

# Deploy Lunexia Live Feedback to WordPress.org SVN Repository
# SVN URL: https://plugins.svn.wordpress.org/lunexia-live-feedback

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
SVN_URL="https://plugins.svn.wordpress.org/lunexia-live-feedback"
SVN_DIR="$ROOT_DIR/build/svn-checkout"
PACKAGE_DIR="$ROOT_DIR/build/package/lunexia-live-feedback"
ASSETS_DIR="$ROOT_DIR/wporg-assets"
DEFAULT_USER="lunexiait"

echo "=================================================="
echo " Lunexia Live Feedback — WordPress.org SVN Deploy"
echo "=================================================="

# 1. Check for svn binary
if ! command -v svn >/dev/null 2>&1; then
    echo ""
    echo "❌ Error: Subversion (svn) is not installed on this system."
    echo ""
    echo "How to install Subversion on macOS:"
    echo "  1) If you use Homebrew, run:"
    echo "     brew install subversion"
    echo ""
    echo "  2) Or install Homebrew first (if needed):"
    echo '     /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"'
    echo "     brew install subversion"
    echo ""
    echo "  3) Alternatively, use a GUI SVN client:"
    echo "     - SnailSVN (Mac App Store)"
    echo "     - SmartSVN (https://www.smartsvn.com/)"
    echo ""
    exit 1
fi

# 2. Extract plugin version from main PHP file
VERSION=$(grep -m1 "Version:" "$ROOT_DIR/lunexia-live-feedback.php" | awk -F': ' '{print $2}' | tr -d '[:space:]')
if [ -z "$VERSION" ]; then
    VERSION="0.1.0"
fi
echo "Target Version: $VERSION"

# 3. Ensure clean build package
echo ""
echo "Step 1: Building clean runtime package..."
"$ROOT_DIR/scripts/package-plugin.sh"

if [ ! -d "$PACKAGE_DIR" ]; then
    echo "❌ Error: Package directory not found at $PACKAGE_DIR"
    exit 1
fi

# 4. Check out or update SVN repo
echo ""
echo "Step 2: Checking out SVN repository..."
mkdir -p "$ROOT_DIR/build"

if [ ! -d "$SVN_DIR/.svn" ]; then
    echo "Checking out $SVN_URL into $SVN_DIR..."
    svn co "$SVN_URL" "$SVN_DIR"
else
    echo "Updating existing SVN checkout..."
    svn up "$SVN_DIR"
fi

# 5. Populate trunk/
echo ""
echo "Step 3: Syncing files to trunk/..."
mkdir -p "$SVN_DIR/trunk"
rsync -av --delete \
    --exclude '.svn' \
    --exclude '.git' \
    --exclude '.DS_Store' \
    "$PACKAGE_DIR/" "$SVN_DIR/trunk/"

# 6. Populate tags/<version>/
echo ""
echo "Step 4: Syncing files to tags/$VERSION/..."
mkdir -p "$SVN_DIR/tags/$VERSION"
rsync -av --delete \
    --exclude '.svn' \
    --exclude '.git' \
    --exclude '.DS_Store' \
    "$PACKAGE_DIR/" "$SVN_DIR/tags/$VERSION/"

# 7. Populate WordPress.org SVN assets/
if [ -d "$ASSETS_DIR" ]; then
    echo ""
    echo "Step 5: Syncing WordPress.org directory assets (icons, banners)..."
    mkdir -p "$SVN_DIR/assets"
    rsync -av --delete \
        --exclude '.svn' \
        --exclude '.git' \
        --exclude '.DS_Store' \
        "$ASSETS_DIR/" "$SVN_DIR/assets/"
fi

# 8. Add and remove files in SVN tracking
echo ""
echo "Step 6: Staging files in Subversion..."
cd "$SVN_DIR"

# Add all untracked files
untracked=$(svn status | grep '^?' | awk '{print $2}' || true)
if [ -n "$untracked" ]; then
    echo "Adding new files to SVN tracking..."
    echo "$untracked" | while read -r item; do
        if [ -n "$item" ]; then
            svn add --force "$item"
        fi
    done
fi

# Remove deleted files
missing=$(svn status | grep '^!' | awk '{print $2}' || true)
if [ -n "$missing" ]; then
    echo "Removing deleted files from SVN tracking..."
    echo "$missing" | while read -r item; do
        if [ -n "$item" ]; then
            svn rm "$item"
        fi
    done
fi

echo ""
echo "Subversion status:"
svn status

# 9. Prompt for confirmation and credentials
echo ""
echo "=================================================="
echo " Ready to commit release $VERSION to WordPress.org"
echo "=================================================="
read -p "Do you want to commit to WordPress.org SVN now? (y/N): " -r CONFIRM
if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
    echo "Aborted. Files are staged in $SVN_DIR."
    echo "You can review them and commit manually whenever ready."
    exit 0
fi

read -p "SVN Username [$DEFAULT_USER]: " SVN_USER
SVN_USER=${SVN_USER:-$DEFAULT_USER}

echo "Enter your WordPress.org SVN password:"
echo "(Generate at https://profiles.wordpress.org/lunexiait/profile/edit/group/3/?screen=svn-password)"
read -s -p "Password: " SVN_PASS
echo ""

if [ -z "$SVN_PASS" ]; then
    echo "❌ Error: Password cannot be empty."
    exit 1
fi

echo ""
echo "Committing to WordPress.org SVN..."
svn ci -m "Release version $VERSION" \
    --username "$SVN_USER" \
    --password "$SVN_PASS" \
    --no-auth-cache \
    --non-interactive

echo ""
echo "🎉 SUCCESS! Version $VERSION has been committed to WordPress.org."
echo "Your plugin listing will update within a few minutes to hours at:"
echo "👉 https://wordpress.org/plugins/lunexia-live-feedback"
