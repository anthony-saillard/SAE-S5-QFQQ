#!/bin/bash
set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
RED='\033[0;31m'
NC='\033[0m'

PREFIX="${PURPLE}[QFQQ]${NC}"

echo -e "${PURPLE}Initializing QFQQ project...${NC}"

# Check if docker is installed
if ! command -v docker &> /dev/null; then
    echo -e "${PREFIX} ${RED}[ERROR] Docker is not installed. Please install Docker first.${NC}"
    exit 1
fi

# Create the main .env file if it doesn't exist
if [ ! -f ".env" ]; then
    echo -e "${PREFIX} ${BLUE}Creating root .env file...${NC}"
    cp .env.dist .env || echo "APP_ENV=dev" > .env
    echo -e "${PREFIX} ${GREEN}.env file has been created.${NC}"
fi

# Load environment variables from .env file
source .env

# Stop existing containers
echo -e "${PREFIX} ${BLUE}Stopping existing containers...${NC}"
docker compose down -v
echo -e "${PREFIX} ${GREEN}Existing containers have been stopped.${NC}"

# Rebuilding images
echo -e "${PREFIX} ${BLUE}Building containers...${NC}"
docker compose build
echo -e "${PREFIX} ${GREEN}Containers have been build.${NC}"

# Start containers
echo -e "${PREFIX} ${BLUE}Starting containers...${NC}"
docker compose up -d
echo -e "${PREFIX} ${GREEN}Containers have been start.${NC}"

# Waiting for services to be ready
echo -e "${PREFIX} ${BLUE}Waiting for services to be ready...${NC}"
sleep 10
echo -e "${PREFIX} ${GREEN}Services are ready.${NC}"

echo -e "${PURPLE}Installation complete!${NC}"
echo -e "${GREEN}You can now access:${NC}"
echo "- Frontend: https://${DOMAIN_NAME:-localhost}:${HTTPS_PORT:-443}"
echo "- API: https://${DOMAIN_NAME:-localhost}:${HTTPS_PORT:-443}/api"