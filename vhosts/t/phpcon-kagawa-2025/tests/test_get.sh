#!/bin/bash
# Test for /get endpoint

BASE_URL="${BASE_URL:-http://127.0.0.1:8080}"
PASSED=0
FAILED=0

echo "Testing /get endpoint"
echo "====================="

# Test 1: GET /get returns 200
echo -n "Test 1: GET /get returns 200... "
response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/get")
if [ "$response" = "200" ]; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED (got $response)"
    ((FAILED++))
fi

# Test 2: GET /get returns phpinfo
echo -n "Test 2: GET /get returns phpinfo... "
content=$(curl -s "$BASE_URL/get")
if echo "$content" | grep -q "phpinfo()"; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED"
    ((FAILED++))
fi

# Test 3: GET /get contains PHP version
echo -n "Test 3: GET /get contains PHP Version... "
if echo "$content" | grep -q "PHP Version"; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED"
    ((FAILED++))
fi

echo ""
echo "Results: $PASSED passed, $FAILED failed"

if [ $FAILED -gt 0 ]; then
    exit 1
fi
exit 0
