#!/bin/bash

# Test Runner Script for Singularity DI
# This script helps run tests with different configurations

set -e

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}Singularity DI Test Runner${NC}"
echo "================================"
echo ""

# Check if composer dependencies are installed
if [ ! -d "vendor" ]; then
    echo -e "${RED}Error: vendor directory not found. Please run 'composer install' first.${NC}"
    exit 1
fi

# Function to run tests
run_tests() {
    local framework=$1
    local args=$2
    
    echo -e "${BLUE}Running $framework tests...${NC}"
    
    if [ "$framework" == "phpunit" ]; then
        vendor/bin/phpunit $args
    elif [ "$framework" == "pest" ]; then
        vendor/bin/pest $args
    fi
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ $framework tests passed${NC}"
    else
        echo -e "${RED}✗ $framework tests failed${NC}"
        exit 1
    fi
    echo ""
}

# Parse command line arguments
case "$1" in
    phpunit)
        run_tests "phpunit" "${@:2}"
        ;;
    pest)
        run_tests "pest" "${@:2}"
        ;;
    unit)
        echo -e "${BLUE}Running Unit Tests${NC}"
        run_tests "phpunit" "--testsuite Unit"
        ;;
    integration)
        echo -e "${BLUE}Running Integration Tests${NC}"
        run_tests "phpunit" "--testsuite Integration"
        ;;
    coverage)
        echo -e "${BLUE}Generating Coverage Report${NC}"
        run_tests "phpunit" "--coverage-html coverage/"
        echo -e "${GREEN}Coverage report generated in coverage/index.html${NC}"
        ;;
    all|"")
        echo -e "${BLUE}Running All Tests${NC}"
        run_tests "phpunit" ""
        run_tests "pest" ""
        ;;
    help|--help|-h)
        echo "Usage: $0 [command] [options]"
        echo ""
        echo "Commands:"
        echo "  phpunit       Run PHPUnit tests"
        echo "  pest          Run PEST tests"
        echo "  unit          Run unit tests only"
        echo "  integration   Run integration tests only"
        echo "  coverage      Generate coverage report"
        echo "  all           Run all tests (default)"
        echo "  help          Show this help message"
        echo ""
        echo "Examples:"
        echo "  $0                    # Run all tests"
        echo "  $0 phpunit            # Run PHPUnit tests"
        echo "  $0 pest               # Run PEST tests"
        echo "  $0 unit               # Run unit tests"
        echo "  $0 coverage           # Generate coverage"
        exit 0
        ;;
    *)
        echo -e "${RED}Unknown command: $1${NC}"
        echo "Run '$0 help' for usage information"
        exit 1
        ;;
esac

echo -e "${GREEN}All tests completed successfully!${NC}"
