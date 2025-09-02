#!/bin/bash

# APM PHP Examples - Complete Application Standardization
# Implements zero-conflict, enterprise-grade standardization

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 APM PHP Examples - Complete Standardization${NC}"
echo "================================================="
echo ""

# Applications to standardize
APPLICATIONS=("simple-php" "laravel-app" "symfony-app" "slim-framework" "codeigniter-app")

# Function to check PHP version compatibility
check_php_version() {
    echo -e "${CYAN}🐘 Checking PHP Version Compatibility${NC}"
    echo "------------------------------------"
    
    local php_version=$(php -v | head -n1 | cut -d' ' -f2 | cut -d'-' -f1)
    local major_minor=$(echo $php_version | cut -d'.' -f1,2)
    
    echo "Current PHP Version: $php_version"
    
    case $major_minor in
        "8.1"|"8.2"|"8.3"|"8.4")
            echo -e "${GREEN}✅ PHP version $major_minor is supported${NC}"
            ;;
        *)
            echo -e "${RED}❌ PHP version $major_minor is not supported${NC}"
            echo -e "${RED}   Supported versions: 8.1, 8.2, 8.3, 8.4${NC}"
            exit 1
            ;;
    esac
    
    # Check required extensions
    echo -e "${YELLOW}Checking required PHP extensions:${NC}"
    local required_extensions=("pdo" "redis" "curl" "json" "mbstring" "openssl")
    local missing_extensions=()
    
    for ext in "${required_extensions[@]}"; do
        if php -m | grep -qi "^$ext$"; then
            echo -e "  ✅ $ext"
        else
            echo -e "  ❌ $ext (missing)"
            missing_extensions+=("$ext")
        fi
    done
    
    if [ ${#missing_extensions[@]} -gt 0 ]; then
        echo -e "${RED}❌ Missing required extensions: ${missing_extensions[*]}${NC}"
        echo -e "${YELLOW}Please install missing extensions before continuing${NC}"
        exit 1
    fi
    
    echo ""
}

# Function to standardize composer.json
standardize_composer() {
    local app="$1"
    echo -e "${CYAN}📦 Standardizing $app composer.json${NC}"
    echo "--------------------------------"
    
    if [ ! -f "$app/composer.json" ]; then
        echo -e "${RED}❌ No composer.json found in $app${NC}"
        return 1
    fi
    
    # Backup original
    cp "$app/composer.json" "$app/composer.json.backup"
    
    # Update PHP version requirement to ^8.1
    jq '.require.php = "^8.1"' "$app/composer.json" > "$app/composer.json.tmp" && mv "$app/composer.json.tmp" "$app/composer.json"
    
    # Add required extensions
    jq '.require["ext-pdo"] = "*"' "$app/composer.json" > "$app/composer.json.tmp" && mv "$app/composer.json.tmp" "$app/composer.json"
    jq '.require["ext-redis"] = "*"' "$app/composer.json" > "$app/composer.json.tmp" && mv "$app/composer.json.tmp" "$app/composer.json"
    jq '.require["ext-curl"] = "*"' "$app/composer.json" > "$app/composer.json.tmp" && mv "$app/composer.json.tmp" "$app/composer.json"
    jq '.require["ext-json"] = "*"' "$app/composer.json" > "$app/composer.json.tmp" && mv "$app/composer.json.tmp" "$app/composer.json"
    jq '.require["ext-mbstring"] = "*"' "$app/composer.json" > "$app/composer.json.tmp" && mv "$app/composer.json.tmp" "$app/composer.json"
    jq '.require["ext-openssl"] = "*"' "$app/composer.json" > "$app/composer.json.tmp" && mv "$app/composer.json.tmp" "$app/composer.json"
    
    # Add development dependencies
    jq '.["require-dev"]["phpunit/phpunit"] = "^10.0"' "$app/composer.json" > "$app/composer.json.tmp" && mv "$app/composer.json.tmp" "$app/composer.json"
    jq '.["require-dev"]["phpstan/phpstan"] = "^1.10"' "$app/composer.json" > "$app/composer.json.tmp" && mv "$app/composer.json.tmp" "$app/composer.json"
    jq '.["require-dev"]["friendsofphp/php-cs-fixer"] = "^3.0"' "$app/composer.json" > "$app/composer.json.tmp" && mv "$app/composer.json.tmp" "$app/composer.json"
    
    # Add PSR-4 autoloading if not present
    if ! jq -e '.autoload.["psr-4"]' "$app/composer.json" > /dev/null; then
        jq '.autoload.["psr-4"] = {}' "$app/composer.json" > "$app/composer.json.tmp" && mv "$app/composer.json.tmp" "$app/composer.json"
    fi
    
    # Add test autoloading
    jq '.["autoload-dev"].["psr-4"]["Tests\\\\"] = "tests/"' "$app/composer.json" > "$app/composer.json.tmp" && mv "$app/composer.json.tmp" "$app/composer.json"
    
    echo -e "${GREEN}✅ Composer.json standardized${NC}"
    echo ""
}

# Function to install quality tools
install_quality_tools() {
    local app="$1"
    echo -e "${CYAN}🔧 Installing Quality Tools for $app${NC}"
    echo "------------------------------------"
    
    cd "$app"

    # Update composer.lock to match composer.json changes
    composer update --no-scripts

    # Install/update dependencies
    composer install
    
    # Copy configuration templates
    if [ ! -f "phpunit.xml" ]; then
        cp "../_templates/phpunit.xml.template" "phpunit.xml"
        echo "✅ PHPUnit configuration added"
    fi
    
    if [ ! -f "phpstan.neon" ]; then
        cp "../_templates/phpstan.neon.template" "phpstan.neon"
        echo "✅ PHPStan configuration added"
    fi
    
    if [ ! -f ".php-cs-fixer.php" ]; then
        cp "../_templates/.php-cs-fixer.php.template" ".php-cs-fixer.php"
        echo "✅ PHP CS Fixer configuration added"
    fi
    
    cd ..
    echo ""
}

# Function to create test structure
create_test_structure() {
    local app="$1"
    echo -e "${CYAN}🧪 Creating Test Structure for $app${NC}"
    echo "------------------------------------"
    
    # Create test directories
    mkdir -p "$app/tests/Unit"
    mkdir -p "$app/tests/Feature"
    mkdir -p "$app/tests/Integration"
    
    # Create base test case if it doesn't exist
    if [ ! -f "$app/tests/TestCase.php" ]; then
        cat > "$app/tests/TestCase.php" << 'EOF'
<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Add common setup logic here
    }
    
    protected function tearDown(): void
    {
        // Add common cleanup logic here
        parent::tearDown();
    }
}
EOF
        echo "✅ Base TestCase created"
    fi
    
    # Create sample unit test if none exist
    if [ ! -f "$app/tests/Unit/ExampleTest.php" ]; then
        cat > "$app/tests/Unit/ExampleTest.php" << 'EOF'
<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function testBasicAssertion(): void
    {
        $this->assertTrue(true);
    }
    
    public function testPhpVersion(): void
    {
        $this->assertGreaterThanOrEqual('8.1', PHP_VERSION);
    }
}
EOF
        echo "✅ Example unit test created"
    fi
    
    echo ""
}

# Function to run quality checks
run_quality_checks() {
    local app="$1"
    echo -e "${CYAN}✅ Running Quality Checks for $app${NC}"
    echo "------------------------------------"
    
    cd "$app"
    
    # Run PHP CS Fixer (dry run)
    if [ -f "vendor/bin/php-cs-fixer" ]; then
        echo "Running PHP CS Fixer..."
        vendor/bin/php-cs-fixer fix --dry-run --diff || echo "Code style issues found"
    fi
    
    # Run PHPStan
    if [ -f "vendor/bin/phpstan" ]; then
        echo "Running PHPStan..."
        vendor/bin/phpstan analyse --level=1 || echo "Static analysis issues found"
    fi
    
    # Run PHPUnit
    if [ -f "vendor/bin/phpunit" ]; then
        echo "Running PHPUnit..."
        vendor/bin/phpunit || echo "Test failures found"
    fi
    
    cd ..
    echo ""
}

# Main standardization process
main() {
    echo -e "${PURPLE}Starting comprehensive standardization...${NC}"
    echo ""
    
    # Check PHP version first
    check_php_version
    
    # Process each application
    for app in "${APPLICATIONS[@]}"; do
        if [ -d "$app" ]; then
            echo -e "${BLUE}Processing $app...${NC}"
            echo "===================="
            
            standardize_composer "$app"
            install_quality_tools "$app"
            create_test_structure "$app"
            run_quality_checks "$app"
            
            echo -e "${GREEN}✅ $app standardization complete${NC}"
            echo ""
        else
            echo -e "${RED}❌ Application directory $app not found${NC}"
            echo ""
        fi
    done
    
    echo -e "${GREEN}🎉 Complete standardization finished!${NC}"
    echo ""
    echo -e "${YELLOW}Next steps:${NC}"
    echo "1. Review and commit changes"
    echo "2. Run 'make validate' to verify all applications"
    echo "3. Proceed to Phase 2: Comprehensive Testing Framework"
}

# Run main function
main "$@"
