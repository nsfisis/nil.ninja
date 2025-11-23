#!/bin/bash
# Test for /cookie/eat endpoint

BASE_URL="${BASE_URL:-http://127.0.0.1:8080}"
PASSED=0
FAILED=0

echo "Testing /cookie/eat endpoint"
echo "============================"

# Test 1: GET /cookie/eat returns 200
echo -n "Test 1: GET /cookie/eat returns 200... "
response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/cookie/eat")
if [ "$response" = "200" ]; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED (got $response)"
    ((FAILED++))
fi

# Test 2: GET /cookie/eat without orders shows no order message
echo -n "Test 2: GET /cookie/eat without orders shows message... "
content=$(curl -s "$BASE_URL/cookie/eat")
if echo "$content" | grep -q "注文がありません"; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED"
    ((FAILED++))
fi

# Test 3: GET /cookie/eat with orders shows orders
echo -n "Test 3: GET /cookie/eat with orders shows orders... "
content=$(curl -s -b "order=%E3%81%8B%E3%81%91" "$BASE_URL/cookie/eat")
if echo "$content" | grep -q "かけ"; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED"
    ((FAILED++))
fi

# Test 4: GET /cookie/eat clears cookie
echo -n "Test 4: GET /cookie/eat clears cookie... "
response=$(curl -s -I -b "order=%E3%81%8B%E3%81%91" "$BASE_URL/cookie/eat" | grep -i "Set-Cookie")
if echo "$response" | grep -q "Max-Age=0"; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED"
    ((FAILED++))
fi

# Test 5: GET /cookie/eat shows success message
echo -n "Test 5: GET /cookie/eat shows success message... "
content=$(curl -s -b "order=%E3%81%8B%E3%81%91" "$BASE_URL/cookie/eat")
if echo "$content" | grep -q "ごちそうさまでした"; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED"
    ((FAILED++))
fi

# Test 6: GET /cookie/eat with multiple orders shows all
echo -n "Test 6: GET /cookie/eat with multiple orders shows all... "
content=$(curl -s -b "order=%E3%81%8B%E3%81%91%2C%E9%87%9C%E7%8E%89" "$BASE_URL/cookie/eat")
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
