#!/bin/bash

# Symfony Application - Standardized Setup Script
# APM PHP Examples - Clean Sample Applications

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Symfony Application Setup${NC}"
echo "=================================="
echo ""

# Step 1: Start Docker services
echo -e "${YELLOW}1. Starting Docker services...${NC}"
docker compose up -d
echo -e "${GREEN}✅ Docker services started${NC}"
echo ""

# Step 2: Install dependencies
echo -e "${YELLOW}2. Installing PHP dependencies...${NC}"
composer install --optimize-autoloader
echo -e "${GREEN}✅ Dependencies installed${NC}"
echo ""

# Step 3: Setup environment
echo -e "${YELLOW}3. Setting up environment...${NC}"
if [ ! -f .env.local ]; then
    if [ -f config/app.env.example ]; then
        cp config/app.env.example .env.local
        echo -e "${GREEN}✅ Environment file created${NC}"
    else
        echo -e "${BLUE}ℹ️  Using default Symfony environment${NC}"
    fi
else
    echo -e "${BLUE}ℹ️  Environment file already exists${NC}"
fi
echo ""

# Step 4: Clear and warm up cache
echo -e "${YELLOW}4. Setting up Symfony cache...${NC}"
php bin/console cache:clear
php bin/console cache:warmup
echo -e "${GREEN}✅ Cache configured${NC}"
echo ""

# Step 5: Create database (if needed)
echo -e "${YELLOW}5. Setting up database...${NC}"
php bin/console doctrine:database:create --if-not-exists || true
php bin/console doctrine:migrations:migrate --no-interaction || true
echo -e "${GREEN}✅ Database configured${NC}"
echo ""

echo -e "${GREEN}🎉 Symfony Application Setup Complete!${NC}"
echo ""
echo -e "${BLUE}To start the application:${NC}"
echo "  symfony server:start --port=8002"
echo "  # OR"
echo "  php -S localhost:8002 -t public"
echo ""
echo -e "${BLUE}To test the application:${NC}"
echo "  curl http://localhost:8002"
echo ""
echo -e "${BLUE}To access Adminer (database management):${NC}"
echo "  http://localhost:8082"
echo ""
