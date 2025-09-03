#!/bin/sh
set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
ORANGE='\033[0;33m'
GREY='\033[0;37m'
NC='\033[0m'

PREFIX="${PURPLE}[QFQQ]${NC}"

# Ensure APP_ENV is set
APP_ENV=${APP_ENV:-dev}

# Ensure DB_PORT is set
DB_PORT=${DB_PORT:-5432}

# Wait for database to be ready
echo -e "${PREFIX} ${BLUE}Waiting for database to be ready...${NC}"
until pg_isready -h database -p "$DB_PORT" -q; do
    echo -e "${PREFIX} ${ORANGE}Postgres is unavailable - waiting${NC}"
    sleep 2
done
echo -e "${PREFIX} ${GREEN}Postgres is ready!${NC}"

# Install Symfony dependencies
echo -e "${PREFIX} ${BLUE}Installing Symfony dependencies...${NC}"
if [ "$APP_ENV" = "dev" ]; then
    composer install
else
    composer install --no-dev --optimize-autoloader
fi
echo -e "${PREFIX} ${GREEN}Symfony dependencies have been installed.${NC}"

# Create cache directory with correct permissions
echo -e "${PREFIX} ${BLUE}Setting up cache directory...${NC}"
mkdir -p /var/www/html/var/cache
mkdir -p /var/www/html/var/log
chown -R www-data:www-data /var/www/html/var
chmod -R 777 /var/www/html/var
echo -e "${PREFIX} ${GREEN}Cache directory setup complete.${NC}"

# Database operations
if [ "$APP_ENV" = "dev" ]; then
    echo -e "${PREFIX} ${BLUE}Creating database (if it doesn't exist)...${NC}"
    php bin/console doctrine:database:create --if-not-exists --no-interaction
    echo -e "${PREFIX} ${GREEN}Database created (if necessary).${NC}"
fi

# Migrations
if [ -d "migrations" ] && [ "$(ls -A migrations)" ]; then
    echo -e "${PREFIX} ${BLUE}Running migrations...${NC}"
    php bin/console doctrine:migrations:migrate --no-interaction
    echo -e "${PREFIX} ${GREEN}All migrations have been run.${NC}"
else
    if [ ! -d "migrations" ]; then
        echo -e "${PREFIX} ${GREY}Migration skipped because the directory 'migrations' does not exist.${NC}"
    elif [ -z "$(ls -A migrations)" ]; then
        echo -e "${PREFIX} ${GREY}Migration skipped because the 'migrations' directory is empty.${NC}"
    fi
fi

# Clear and warmup cache
if [ "$APP_ENV" = "prod" ]; then
    echo -e "${PREFIX} ${BLUE}Clearing and warming up cache for production...${NC}"
    php bin/console cache:clear --env=prod --no-debug
    php bin/console cache:warmup --env=prod --no-debug
    chmod -R 777 /var/www/html/var
    echo -e "${PREFIX} ${GREEN}Cache cleared and warmed up for production.${NC}"
fi

echo -e "${PREFIX} ${PURPLE}API successfully loaded.${NC}"

# Start PHP-FPM
exec "$@"
