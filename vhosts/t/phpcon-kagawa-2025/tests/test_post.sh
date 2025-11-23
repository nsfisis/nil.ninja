#!/bin/bash
# Test for /post endpoint

BASE_URL="${BASE_URL:-http://127.0.0.1:8080}"
PASSED=0
FAILED=0

echo "Testing /post endpoint"
echo "======================"

# Test 1: GET /post returns 200
echo -n "Test 1: GET /post returns 200... "
response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/post")
if [ "$response" = "200" ]; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED (got $response)"
    ((FAILED++))
fi

# Test 2: GET /post returns quiz form
echo -n "Test 2: GET /post returns quiz form... "
content=$(curl -s "$BASE_URL/post")
if echo "$content" | grep -q "本州四国連絡橋クイズ"; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED"
    ((FAILED++))
fi

# Test 3: POST /post with correct answers returns all correct
echo -n "Test 3: POST /post with correct answers... "
response=$(curl -s -X POST "$BASE_URL/post" \
    -d "ehime=hiroshima&kagawa=okayama&tokushima=hyogo")
if echo "$response" | grep -q "スコア: 3 / 3"; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED"
    ((FAILED++))
fi

# Test 4: POST /post with wrong answers returns score 0
echo -n "Test 4: POST /post with wrong answers... "
response=$(curl -s -X POST "$BASE_URL/post" \
    -d "ehime=osaka&kagawa=osaka&tokushima=osaka")
if echo "$response" | grep -q "スコア: 0 / 3"; then
    echo "PASSED"
    ((PASSED++))
else
    echo "FAILED"
    ((FAILED++))
fi

# Test 5: POST /post with partial correct answers
echo -n "Test 5: POST /post with partial correct answers... "
response=$(curl -s -X POST "$BASE_URL/post" \
    -d "ehime=hiroshima&kagawa=osaka&tokushima=osaka")
if echo "$response" | grep -q "スコア: 1 / 3"; then
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
