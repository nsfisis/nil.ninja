#!/bin/bash
# Test for 404 Not Found response

BASE_URL="${BASE_URL:-http://127.0.0.1:8080}"
PASSED=0
FAILED=0

echo "Testing 404 Not Found"
echo "====================="

# Test 1: GET / returns 404
echo -n "Test 1: GET / returns 404... "
response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/")
if [ "$response" = "404" ]; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED (got $response)"
    ((FAILED++))
fi

# Test 2: GET /nonexistent returns 404
echo -n "Test 2: GET /nonexistent returns 404... "
response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/nonexistent")
if [ "$response" = "404" ]; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED (got $response)"
    ((FAILED++))
fi

# Test 3: GET /foo/bar returns 404
echo -n "Test 3: GET /foo/bar returns 404... "
response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/foo/bar")
if [ "$response" = "404" ]; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED (got $response)"
    ((FAILED++))
fi

# Test 4: 404 response body contains "404 Not Found"
echo -n "Test 4: 404 response body contains '404 Not Found'... "
content=$(curl -s "$BASE_URL/nonexistent")
if [ "$content" = "404 Not Found" ]; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED (got '$content')"
    ((FAILED++))
fi

echo ""
echo "Results: $PASSED passed, $FAILED failed"

if [ $FAILED -gt 0 ]; then
    exit 1
fi
exit 0
