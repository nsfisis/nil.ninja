#!/bin/bash
# Main test runner for http_server.php

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SERVER_PID=""
PORT=8080
HOST=127.0.0.1
BASE_URL="http://${HOST}:${PORT}/phpcon-kagawa-2025"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

cleanup() {
    if [ -n "$SERVER_PID" ]; then
        echo ""
        echo "Stopping server (PID: $SERVER_PID)..."
        kill $SERVER_PID 2>/dev/null || true
        wait $SERVER_PID 2>/dev/null || true
        echo "Server stopped."
    fi
}

trap cleanup EXIT

echo "================================"
echo "HTTP Server Test Suite"
echo "================================"
echo ""

# Check if port is already in use
if lsof -i :$PORT -t >/dev/null 2>&1; then
    echo -e "${RED}Error: Port $PORT is already in use${NC}"
    exit 1
fi

# Start the server
echo "Starting HTTP server..."
php "$SCRIPT_DIR/index.php" > /dev/null 2>&1 &
SERVER_PID=$!

# Wait for server to start using health check endpoint
echo -n "Waiting for server to be ready"
for i in {1..30}; do
    if curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/health" 2>/dev/null | grep -q "200"; then
        echo " OK"
        break
    fi
    echo -n "."
    sleep 0.2
done

# Verify server is running
if ! curl -s -o /dev/null "$BASE_URL/health" 2>/dev/null; then
    echo -e "${RED}Error: Server failed to start${NC}"
    exit 1
fi

echo ""
echo "Server started on $BASE_URL (PID: $SERVER_PID)"
echo ""

# Export BASE_URL for test scripts
export BASE_URL

# Run tests
TOTAL_PASSED=0
TOTAL_FAILED=0
TESTS_RUN=0

run_test() {
    local test_file="$1"
    local test_name=$(basename "$test_file" .sh)

    echo "----------------------------------------"

    if bash "$test_file"; then
        ((TOTAL_PASSED++))
    else
        ((TOTAL_FAILED++))
    fi
    ((TESTS_RUN++))
    echo ""
}

# Run all test files
for test_file in "$SCRIPT_DIR/tests"/test_*.sh; do
    if [ -f "$test_file" ]; then
        run_test "$test_file"
    fi
done

# Summary
echo "========================================"
echo "Test Summary"
echo "========================================"
echo "Total test files: $TESTS_RUN"
echo -e "Passed: ${GREEN}$TOTAL_PASSED${NC}"
echo -e "Failed: ${RED}$TOTAL_FAILED${NC}"
echo ""

if [ $TOTAL_FAILED -gt 0 ]; then
    echo -e "${RED}SOME TESTS FAILED${NC}"
    exit 1
else
    echo -e "${GREEN}ALL TESTS PASSED${NC}"
    exit 0
fi
