#!/bin/bash

# Configuration
DIST_DIR="dist"
ZIP_NAME="deploy.zip"
APP_URL="https://dglab.42web.io"

echo "Building deployment package for $APP_URL..."

# 1. Clean up
rm -rf "$DIST_DIR"
rm -f "$ZIP_NAME"
mkdir "$DIST_DIR"

# 2. Copy application files
cp -r app "$DIST_DIR/"
cp composer.json composer.lock "$DIST_DIR/"

# 3. Copy public files to root (Option B for InfinityFree)
cp public/index.php "$DIST_DIR/"
cp public/manifest.json "$DIST_DIR/"
cp public/sw.js "$DIST_DIR/"
cp -r public/assets "$DIST_DIR/"

# 4. Prepare storage directory
mkdir -p "$DIST_DIR/storage/logs"
mkdir -p "$DIST_DIR/storage/chunks"
mkdir -p "$DIST_DIR/storage/uploads"
touch "$DIST_DIR/storage/logs/.gitkeep"
touch "$DIST_DIR/storage/chunks/.gitkeep"
touch "$DIST_DIR/storage/uploads/.gitkeep"

# 5. Fix index.php path
sed -i "s|require_once __DIR__ . '/../vendor/autoload.php'|require_once __DIR__ . '/vendor/autoload.php'|" "$DIST_DIR/index.php"

# 6. Create production .env
cat <<EOT > "$DIST_DIR/.env"
APP_ENV=production
APP_DEBUG=false
APP_URL=$APP_URL
CHUNK_UPLOAD_DIR=chunks
FINAL_UPLOAD_DIR=uploads
CHUNK_LIFETIME_SECONDS=86400
EOT

# 7. Create .htaccess
cat <<'EOT' > "$DIST_DIR/.htaccess"
RewriteEngine On

# Prevent directory browsing
Options -Indexes

# Block access to sensitive directories and files
RewriteRule ^(app|vendor|storage|(\.env)) - [F,L]

# Handle Front Controller
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
EOT

# 8. Install production dependencies
cd "$DIST_DIR"
composer install --no-dev --optimize-autoloader
cd ..

# 9. Create Zip
cd "$DIST_DIR"
zip -r "../$ZIP_NAME" . -x "*.git*"
cd ..

echo "Build complete: $ZIP_NAME"
