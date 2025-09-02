#!/bin/bash

# APM PHP Examples - Comprehensive Conflict Analysis
# Analyzes dependencies, versions, and architectural inconsistencies

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔍 APM PHP Examples - Comprehensive Conflict Analysis${NC}"
echo "====================================================="
echo ""

# Applications to analyze
APPLICATIONS=("simple-php" "laravel-app" "symfony-app" "slim-framework" "codeigniter-app")

# Function to analyze composer dependencies
analyze_composer_deps() {
    local app="$1"
    echo -e "${CYAN}📦 Analyzing $app Dependencies${NC}"
    echo "--------------------------------"
    
    if [ -f "$app/composer.json" ]; then
        echo -e "${YELLOW}Composer.json found${NC}"
        
        # Extract PHP version requirements
        php_version=$(jq -r '.require.php // "not specified"' "$app/composer.json" 2>/dev/null || echo "jq not available")
        echo "PHP Version Requirement: $php_version"
        
        # Count dependencies
        require_count=$(jq '.require | length' "$app/composer.json" 2>/dev/null || echo "0")
        require_dev_count=$(jq '.["require-dev"] | length' "$app/composer.json" 2>/dev/null || echo "0")
        echo "Production Dependencies: $require_count"
        echo "Development Dependencies: $require_dev_count"
        
        # Check for common conflicting packages
        echo -e "${YELLOW}Checking for potential conflicts:${NC}"
        
        # Check for multiple HTTP clients
        http_clients=0
        if grep -q "guzzlehttp/guzzle" "$app/composer.json"; then
            echo "  - Guzzle HTTP Client found"
            ((http_clients++))
        fi
        if grep -q "symfony/http-client" "$app/composer.json"; then
            echo "  - Symfony HTTP Client found"
            ((http_clients++))
        fi
        if [ $http_clients -gt 1 ]; then
            echo -e "  ${RED}⚠️  Multiple HTTP clients detected${NC}"
        fi
        
        # Check for multiple loggers
        loggers=0
        if grep -q "monolog/monolog" "$app/composer.json"; then
            echo "  - Monolog found"
            ((loggers++))
        fi
        if grep -q "psr/log" "$app/composer.json"; then
            echo "  - PSR Log found"
            ((loggers++))
        fi
        
        # Check for multiple database libraries
        db_libs=0
        if grep -q "doctrine/orm" "$app/composer.json"; then
            echo "  - Doctrine ORM found"
            ((db_libs++))
        fi
        if grep -q "illuminate/database" "$app/composer.json"; then
            echo "  - Laravel Eloquent found"
            ((db_libs++))
        fi
        
    else
        echo -e "${RED}❌ No composer.json found${NC}"
    fi
    echo ""
}

# Function to analyze PHP version compatibility
analyze_php_compatibility() {
    local app="$1"
    echo -e "${CYAN}🐘 Analyzing $app PHP Compatibility${NC}"
    echo "------------------------------------"
    
    # Check current PHP version
    current_php=$(php -v | head -n1 | cut -d' ' -f2 | cut -d'-' -f1)
    echo "Current PHP Version: $current_php"
    
    # Check for PHP version-specific code
    if [ -d "$app" ]; then
        # Check for PHP 8+ features
        php8_features=$(find "$app" -name "*.php" -exec grep -l "match\|nullsafe\|union.*types\|readonly\|enum" {} \; 2>/dev/null | wc -l)
        echo "Files using PHP 8+ features: $php8_features"
        
        # Check for deprecated features
        deprecated_features=$(find "$app" -name "*.php" -exec grep -l "create_function\|each\|mysql_" {} \; 2>/dev/null | wc -l)
        echo "Files using deprecated features: $deprecated_features"
        
        if [ $deprecated_features -gt 0 ]; then
            echo -e "${RED}⚠️  Deprecated PHP features detected${NC}"
        fi
    fi
    echo ""
}

# Function to analyze architectural consistency
analyze_architecture() {
    local app="$1"
    echo -e "${CYAN}🏗️  Analyzing $app Architecture${NC}"
    echo "--------------------------------"
    
    if [ -d "$app" ]; then
        # Check directory structure
        echo "Directory Structure:"
        if [ -d "$app/public" ]; then
            echo "  ✅ public/ directory found"
        else
            echo "  ❌ public/ directory missing"
        fi
        
        if [ -d "$app/src" ] || [ -d "$app/app" ]; then
            echo "  ✅ Source directory found"
        else
            echo "  ⚠️  No standard source directory"
        fi
        
        if [ -d "$app/config" ]; then
            echo "  ✅ config/ directory found"
        else
            echo "  ⚠️  No config directory"
        fi
        
        # Check for standard files
        echo "Standard Files:"
        [ -f "$app/composer.json" ] && echo "  ✅ composer.json" || echo "  ❌ composer.json missing"
        [ -f "$app/Dockerfile" ] && echo "  ✅ Dockerfile" || echo "  ❌ Dockerfile missing"
        [ -f "$app/docker-compose.yml" ] && echo "  ✅ docker-compose.yml" || echo "  ❌ docker-compose.yml missing"
        [ -f "$app/setup.sh" ] && echo "  ✅ setup.sh" || echo "  ❌ setup.sh missing"
        
        # Check for entry point
        if [ -f "$app/public/index.php" ]; then
            echo "  ✅ Entry point: public/index.php"
        else
            echo "  ❌ No standard entry point"
        fi
    fi
    echo ""
}

# Main analysis
echo -e "${PURPLE}Starting comprehensive analysis...${NC}"
echo ""

for app in "${APPLICATIONS[@]}"; do
    if [ -d "$app" ]; then
        analyze_composer_deps "$app"
        analyze_php_compatibility "$app"
        analyze_architecture "$app"
        echo -e "${BLUE}==================================================${NC}"
        echo ""
    else
        echo -e "${RED}❌ Application directory $app not found${NC}"
        echo ""
    fi
done

echo -e "${GREEN}🎯 Analysis Complete!${NC}"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo "1. Review dependency conflicts identified above"
echo "2. Standardize PHP version requirements"
echo "3. Unify architectural patterns"
echo "4. Implement consistent coding standards"
