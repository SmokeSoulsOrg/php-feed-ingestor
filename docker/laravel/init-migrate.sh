#!/bin/bash

set -e
cd /var/www/html

echo "🔧 Ensuring SQLite database file exists..."
mkdir -p database
touch database/database.sqlite
chown www-data:www-data database/database.sqlite

echo "🔧 Changing .env ownership to www-data"
chown www-data:www-data /var/www/html/.env

echo "🔧 Ensuring .env is writable..."
chmod +w /var/www/html/.env || echo "⚠️  .env not writable and chmod failed"

echo "🔑 Generating app key..."
php artisan key:generate

echo "📦 Caching config..."
php artisan config:cache

echo "🛠 Running migrations on primary..."
php artisan migrate:fresh --force --database=sqlite

echo "🛠 Running queues in background..."
nohup php artisan queue:work > storage/logs/queue.log 2>&1 &

echo "🛠 Running scheduled tasks..."
php artisan schedule:run

echo "✅ Done. Tailing application logs using Pail..."
php artisan pail
