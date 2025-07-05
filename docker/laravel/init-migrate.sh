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

echo "🛠 Creating queues by dispatching feed ingest job..."
php artisan ingest-pornstar-feed

echo "🛠 Running RabbitMQ queues in background..."
php artisan queue:work rabbitmq

echo "✅ Done. Tailing application logs using Pail..."
php artisan pail
