#!/bin/sh

# Attendre que la base de données soit prête
echo "Waiting for database to be ready..."
while ! pg_isready -h $DB_HOST -p $DB_PORT -U $DB_USERNAME; do
  echo "Database is unavailable - sleeping"
  sleep 1
done

echo "Database is up - executing migrations"
# Clear caches to avoid serving stale routes/config from image build
php artisan route:clear || true
php artisan config:clear || true
php artisan cache:clear || true

# Run migrations
php artisan migrate --force

echo "Starting Laravel application..."
exec "$@"