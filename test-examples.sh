#!/bin/bash

# Contact Form Anti-Spam Test Examples
# 
# This script provides curl examples to test the contact form
# Replace YOUR_SITE_URL with your actual site URL
# 
# Usage: bash test-examples.sh

SITE_URL="https://yoursite.com"
AJAX_URL="${SITE_URL}/ajax.php?action=contact"

echo "========================================="
echo "Contact Form Anti-Spam Test Suite"
echo "========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test 1: Valid Submission
echo -e "${YELLOW}Test 1: Valid Submission${NC}"
echo "Testing with legitimate data..."
curl -X POST "$AJAX_URL" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "name=John Smith" \
  -d "email=john.smith@gmail.com" \
  -d "phone=+1-555-1234" \
  -d "message=This is a test message. It contains three sentences. Please ignore this submission." \
  -d "g-recaptcha-response=test_token" \
  -d "form_timestamp=$(date +%s)" \
  -d "website=" \
  -d "company=" | jq '.'
echo ""
echo "Expected: success=true"
echo "========================================="
echo ""

# Test 2: Gibberish Name
echo -e "${YELLOW}Test 2: Gibberish Name (JYupWMLW)${NC}"
echo "Testing with random character name..."
curl -X POST "$AJAX_URL" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "name=JYupWMLW" \
  -d "email=test@gmail.com" \
  -d "phone=+1-555-1234" \
  -d "message=This is a test message. It contains three sentences. Please ignore this submission." \
  -d "g-recaptcha-response=test_token" \
  -d "form_timestamp=$(date +%s)" | jq '.'
echo ""
echo "Expected: success=false, reason about invalid name"
echo "========================================="
echo ""

# Test 3: Short Message
echo -e "${YELLOW}Test 3: Short Message${NC}"
echo "Testing with message '20'..."
curl -X POST "$AJAX_URL" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "name=John Smith" \
  -d "email=test@gmail.com" \
  -d "phone=+1-555-1234" \
  -d "message=20" \
  -d "g-recaptcha-response=test_token" \
  -d "form_timestamp=$(date +%s)" | jq '.'
echo ""
echo "Expected: success=false, message too short"
echo "========================================="
echo ""

# Test 4: SQL Injection in Email
echo -e "${YELLOW}Test 4: SQL Injection in Email${NC}"
echo "Testing with SQL injection pattern..."
curl -X POST "$AJAX_URL" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "name=John Smith" \
  -d "email=(select(0)from(select(sleep(15)))v)/*'+(select(0)from(select(sleep(15)))v)+\"*/" \
  -d "phone=+1-555-1234" \
  -d "message=This is a test message. It contains three sentences. Please ignore this submission." \
  -d "g-recaptcha-response=test_token" \
  -d "form_timestamp=$(date +%s)" | jq '.'
echo ""
echo "Expected: success=false, invalid email"
echo "========================================="
echo ""

# Test 5: Fake Domain
echo -e "${YELLOW}Test 5: Fake Email Domain${NC}"
echo "Testing with tempmail.com domain..."
curl -X POST "$AJAX_URL" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "name=John Smith" \
  -d "email=test@tempmail.com" \
  -d "phone=+1-555-1234" \
  -d "message=This is a test message. It contains three sentences. Please ignore this submission." \
  -d "g-recaptcha-response=test_token" \
  -d "form_timestamp=$(date +%s)" | jq '.'
echo ""
echo "Expected: success=false, blocked domain"
echo "========================================="
echo ""

# Test 6: Honeypot Trigger
echo -e "${YELLOW}Test 6: Honeypot Field Filled${NC}"
echo "Testing with honeypot field filled..."
curl -X POST "$AJAX_URL" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "name=John Smith" \
  -d "email=test@gmail.com" \
  -d "phone=+1-555-1234" \
  -d "message=This is a test message. It contains three sentences. Please ignore this submission." \
  -d "website=http://spam.com" \
  -d "g-recaptcha-response=test_token" \
  -d "form_timestamp=$(date +%s)" | jq '.'
echo ""
echo "Expected: success=false, silent rejection"
echo "========================================="
echo ""

# Test 7: Too Fast Submission
echo -e "${YELLOW}Test 7: Form Filled Too Quickly${NC}"
echo "Testing with timestamp from 1 second ago..."
PAST_TIMESTAMP=$(($(date +%s) - 1))
curl -X POST "$AJAX_URL" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "name=John Smith" \
  -d "email=test@gmail.com" \
  -d "phone=+1-555-1234" \
  -d "message=This is a test message. It contains three sentences. Please ignore this submission." \
  -d "g-recaptcha-response=test_token" \
  -d "form_timestamp=${PAST_TIMESTAMP}" | jq '.'
echo ""
echo "Expected: success=false, filled too quickly"
echo "========================================="
echo ""

# Test 8: Less Than 3 Sentences
echo -e "${YELLOW}Test 8: Message with Only 2 Sentences${NC}"
echo "Testing message with insufficient sentences..."
curl -X POST "$AJAX_URL" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "name=John Smith" \
  -d "email=test@gmail.com" \
  -d "phone=+1-555-1234" \
  -d "message=This is sentence one. This is sentence two that is long enough." \
  -d "g-recaptcha-response=test_token" \
  -d "form_timestamp=$(date +%s)" | jq '.'
echo ""
echo "Expected: success=false, minimum 3 sentences required"
echo "========================================="
echo ""

# Test 9: XSS Attempt
echo -e "${YELLOW}Test 9: XSS in Message${NC}"
echo "Testing with <script> tag..."
curl -X POST "$AJAX_URL" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "name=John Smith" \
  -d "email=test@gmail.com" \
  -d "phone=+1-555-1234" \
  -d "message=<script>alert('xss')</script>. This is a test. Please respond." \
  -d "g-recaptcha-response=test_token" \
  -d "form_timestamp=$(date +%s)" | jq '.'
echo ""
echo "Expected: success=false, invalid content detected"
echo "========================================="
echo ""

# Test 10: Name with Numbers
echo -e "${YELLOW}Test 10: Name with Numbers${NC}"
echo "Testing name containing digits..."
curl -X POST "$AJAX_URL" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "name=John123" \
  -d "email=test@gmail.com" \
  -d "phone=+1-555-1234" \
  -d "message=This is a test message. It contains three sentences. Please ignore this submission." \
  -d "g-recaptcha-response=test_token" \
  -d "form_timestamp=$(date +%s)" | jq '.'
echo ""
echo "Expected: success=false, name cannot contain numbers"
echo "========================================="
echo ""

echo -e "${GREEN}All tests completed!${NC}"
echo ""
echo "Next steps:"
echo "1. Review the responses above"
echo "2. Check server logs for blocked attempts"
echo "3. Verify database entries for successful submissions"
echo ""
echo "Log locations:"
echo "  - site/assets/logs/contact-spam.txt"
echo "  - site/assets/logs/contact-submissions.txt"
echo "  - site/assets/logs/contact-errors.txt"
echo ""
echo "Database queries:"
echo "  SELECT * FROM contact_submissions ORDER BY created_at DESC LIMIT 10;"
echo "  SELECT * FROM blocked_names;"
echo ""