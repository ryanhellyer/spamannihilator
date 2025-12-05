#!/bin/bash

# Laravel sync script between prod and local

# Git operations and build
echo "Committing and pushing changes..."
git add --all
git commit -m 'x'
git push origin main
echo "Building assets..."
npm run build

PROD_HOST="ryan@hellyer.kiwi"
PROD_PATH="/var/www/spamannihilator.com/"
LOCAL_PATH="/var/www/personal/dev-spamannihilator.com/"

EXCLUDES=(
    --exclude='.env'
    --exclude='.env.*'
    --exclude='.git/'
    --exclude='.gitignore'
    --exclude='storage/'
    --exclude='logs/'
    --exclude='database/'
    --exclude='bootstrap/cache/'
    --exclude='public/storage/'
    --exclude='public/storage'
    --exclude='node_modules/'
    --exclude='vendor/'
    --exclude='public/files/'
    --exclude='.phpunit.result.cache'
    --exclude='access.log'
)

echo "==================================="
echo "Laravel Site Sync"
echo "==================================="
echo "1) Prod -> Local (download)"
echo "2) Local -> Prod (upload)"
echo "3) Prod -> Local (dry run)"
echo "4) Local -> Prod (dry run)"
echo "5) Exit"
echo "==================================="
read -p "Select option: " choice

case $choice in
    1)
        echo "Syncing from PROD to LOCAL..."
        rsync -avz --delete "${EXCLUDES[@]}" \
            ${PROD_HOST}:${PROD_PATH} \
            ${LOCAL_PATH}
        echo ""
        echo "✓ Sync complete!"
        echo "Don't forget to run: composer install"
        ;;
    2)
        echo "Syncing from LOCAL to PROD..."
        read -p "Are you sure you want to upload to production? (yes/no): " confirm
        if [ "$confirm" == "yes" ]; then
            rsync -avz --delete "${EXCLUDES[@]}" \
                ${LOCAL_PATH} \
                ${PROD_HOST}:${PROD_PATH}
            echo ""
            echo "✓ Sync complete!"
            echo "Don't forget to SSH to prod and run:"
            echo "  composer install --no-dev --optimize-autoloader"
            echo "  php artisan config:cache"
            echo "  php artisan route:cache"
            echo "  php artisan view:cache"
        else
            echo "Upload cancelled."
        fi
        ;;
    3)
        echo "DRY RUN: Prod -> Local"
        rsync -avzn --delete "${EXCLUDES[@]}" \
            ${PROD_HOST}:${PROD_PATH} \
            ${LOCAL_PATH}
        ;;
    4)
        echo "DRY RUN: Local -> Prod"
        rsync -avzn --delete "${EXCLUDES[@]}" \
            ${LOCAL_PATH} \
            ${PROD_HOST}:${PROD_PATH}
        ;;
    5)
        echo "Exiting..."
        exit 0
        ;;
    *)
        echo "Invalid option"
        exit 1
        ;;
esac
