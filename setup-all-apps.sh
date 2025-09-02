#!/bin/bash

# APM PHP Examples - Complete Repository Setup Script
# Sets up all applications with standardized process

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Applications to setup
APPLICATIONS=("simple-php" "laravel-app" "symfony-app" "slim-framework" "codeigniter-app")

echo -e "${BLUE}🚀 APM PHP Examples - Complete Repository Setup${NC}"
echo "================================================"
echo ""

echo -e "${CYAN}This script will set up all PHP framework applications:${NC}"
for app in "${APPLICATIONS[@]}"; do
    echo "  • $app"
done
echo ""

read -p "Continue with setup? (y/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Setup cancelled."
    exit 0
fi

echo ""

# Function to setup application
setup_application() {
    local app="$1"
    
    echo -e "${YELLOW}🔧 Setting up ${app}...${NC}"
    echo "=========================="
    
    if [ -f "${app}/setup.sh" ]; then
        cd "$app"
        echo "Running setup script for ${app}..."
        ./setup.sh
        cd ..
        echo -e "${GREEN}✅ ${app} setup complete${NC}"
    else
        echo -e "${RED}❌ Setup script not found for ${app}${NC}"
        return 1
    fi
    echo ""
}

# Setup each application
for app in "${APPLICATIONS[@]}"; do
    setup_application "$app"
done

echo -e "${PURPLE}📋 Setup Summary${NC}"
echo "================"
echo ""

# Verify all applications
echo -e "${YELLOW}🔍 Verifying all applications...${NC}"
echo ""

# Check if all setup scripts exist
all_ready=true
for app in "${APPLICATIONS[@]}"; do
    if [ -f "${app}/setup.sh" ] && [ -x "${app}/setup.sh" ]; then
        echo -e "${GREEN}✅ ${app} - Ready${NC}"
    else
        echo -e "${RED}❌ ${app} - Not ready${NC}"
        all_ready=false
    fi
done

echo ""

if [ "$all_ready" = true ]; then
    echo -e "${GREEN}🎉 All applications are set up and ready!${NC}"
    echo ""
    echo -e "${BLUE}Next steps:${NC}"
    echo "1. Run individual applications:"
    echo "   cd simple-php && ./setup.sh"
    echo "   cd laravel-app && ./setup.sh"
    echo "   cd symfony-app && ./setup.sh"
    echo "   cd slim-framework && ./setup.sh"
    echo "   cd codeigniter-app && ./setup.sh"
    echo ""
    echo "2. Test all applications:"
    echo "   ./test-all-apps.sh"
    echo ""
    echo -e "${BLUE}Application URLs:${NC}"
    echo "• Simple PHP: http://localhost:8000"
    echo "• Laravel: http://localhost:8004"
    echo "• Symfony: http://localhost:8002"
    echo "• Slim Framework: http://localhost:8001"
    echo "• CodeIgniter: http://localhost:8003"
    echo ""
    echo -e "${BLUE}Documentation:${NC}"
    echo "• README.md - Quick start guide"
    echo "• requirements_overview.md - Detailed requirements"
    echo "• DOCKER_PORTS.md - Port allocation details"
else
    echo -e "${RED}❌ Some applications are not ready. Please check the setup.${NC}"
    exit 1
fi
