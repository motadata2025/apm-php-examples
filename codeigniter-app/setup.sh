#!/bin/bash

# CodeIgniter Application - Standardized Setup Script
# APM PHP Examples - Clean Sample Applications

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 CodeIgniter Application Setup${NC}"
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
    if [ -f config/app.env.example ]; then
        cp config/app.env.example .env
        echo -e "${GREEN}✅ Environment file created${NC}"
    else
        echo -e "${BLUE}ℹ️  Using default CodeIgniter environment${NC}"
    fi
else
    echo -e "${BLUE}ℹ️  Environment file already exists${NC}"
fi
echo ""

# Step 4: Set up writable directories
echo -e "${YELLOW}4. Setting up writable directories...${NC}"
chmod -R 755 writable/
echo -e "${GREEN}✅ Writable directories configured${NC}"
echo ""

# Step 5: Verify setup
echo -e "${YELLOW}5. Verifying setup...${NC}"
if [ -f "public/index.php" ]; then
    echo -e "${GREEN}✅ Application files verified${NC}"
else
    echo -e "${RED}❌ Application files missing${NC}"
    exit 1
fi
echo ""

echo -e "${GREEN}🎉 CodeIgniter Application Setup Complete!${NC}"
echo ""
echo -e "${BLUE}To start the application:${NC}"
echo "  php spark serve --port=8003"
echo "  # OR"
echo "  php -S localhost:8003 -t public"
echo ""
echo -e "${BLUE}To test the application:${NC}"
echo "  curl http://localhost:8003"
echo ""
echo -e "${BLUE}To access Adminer (database management):${NC}"
echo "  http://localhost:8083"
echo ""
