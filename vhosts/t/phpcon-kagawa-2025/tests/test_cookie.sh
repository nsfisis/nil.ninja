#!/bin/bash
# Test for /cookie endpoint

BASE_URL="${BASE_URL:-http://127.0.0.1:8080}"
PASSED=0
FAILED=0

echo "Testing /cookie endpoint"
echo "========================"

# Test 1: GET /cookie returns 200
echo -n "Test 1: GET /cookie returns 200... "
response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/cookie")
if [ "$response" = "200" ]; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED (got $response)"
    ((FAILED++))
fi

# Test 2: GET /cookie returns udon shop page
echo -n "Test 2: GET /cookie returns udon shop page... "
content=$(curl -s "$BASE_URL/cookie")
if echo "$content" | grep -q "うどん店"; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED"
    ((FAILED++))
fi

# Test 3: POST /cookie sets order cookie
echo -n "Test 3: POST /cookie sets order cookie... "
response=$(curl -s -i -X POST "$BASE_URL/cookie" \
    -d "udon=%E3%81%8B%E3%81%91" | grep -i "Set-Cookie")
if echo "$response" | grep -q "order="; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED"
    ((FAILED++))
fi

# Test 4: GET /cookie with existing order shows order
echo -n "Test 4: GET /cookie with existing order shows order... "
content=$(curl -s -b "order=%E3%81%8B%E3%81%91" "$BASE_URL/cookie")
if echo "$content" | grep -q "かけ"; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED"
    ((FAILED++))
fi

# Test 5: POST /cookie adds to existing orders
echo -n "Test 5: POST /cookie adds to existing orders... "
response=$(curl -s -i -X POST -b "order=%E3%81%8B%E3%81%91" "$BASE_URL/cookie" \
    -d "udon=%E9%87%9C%E7%8E%89" | grep -i "Set-Cookie")
if echo "$response" | grep -q "order="; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED"
    ((FAILED++))
fi

# Test 6: Multiple orders are stored in cookie
echo -n "Test 6: Multiple orders are displayed... "
content=$(curl -s -b "order=%E3%81%8B%E3%81%91%2C%E9%87%9C%E7%8E%89" "$BASE_URL/cookie")
if echo "$content" | grep -q "かけ" && echo "$content" | grep -q "釜玉"; then
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
