#!/bin/bash

# Laravel Application - Standardized Setup Script
# APM PHP Examples - Clean Sample Applications

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Laravel Application Setup${NC}"
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
if [ ! -f .env ]; then
    cp .env.example .env
    echo -e "${GREEN}✅ Environment file created${NC}"
else
    echo -e "${BLUE}ℹ️  Environment file already exists${NC}"
fi
echo ""

# Step 4: Generate application key
echo -e "${YELLOW}4. Generating Laravel application key...${NC}"
php artisan key:generate --force
echo -e "${GREEN}✅ Application key generated${NC}"
echo ""

# Step 5: Create storage link
echo -e "${YELLOW}5. Creating storage link...${NC}"
php artisan storage:link
echo -e "${GREEN}✅ Storage link created${NC}"
echo ""

# Step 6: Cache configuration
echo -e "${YELLOW}6. Caching configuration...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✅ Configuration cached${NC}"
echo ""

echo -e "${GREEN}🎉 Laravel Application Setup Complete!${NC}"
echo ""
echo -e "${BLUE}To start the application:${NC}"
echo "  php artisan serve --port=8004"
echo ""
echo -e "${BLUE}To test the application:${NC}"
echo "  curl http://localhost:8004"
echo ""
echo -e "${BLUE}To access Adminer (database management):${NC}"
echo "  http://localhost:8084"
echo ""
