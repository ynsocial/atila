# Testing Guide - Contact Form Anti-Spam

Complete testing scenarios to verify your anti-spam implementation is working correctly.

---

## 🧪 Test Cases

### Test 1: Valid Submission ✅

**Purpose:** Verify legitimate users can submit successfully

**Steps:**
1. Open contact form
2. Fill in:
   ```
   Name: John Smith
   Email: john.smith@gmail.com
   Phone: +1-555-123-4567
   Message: Hello, I would like to inquire about your services. 
            I am interested in learning more about your pricing. 
            Please contact me at your earliest convenience.
   ```
3. Complete reCAPTCHA
4. Click Submit

**Expected Result:**
- ✅ Success message: "Mesajınız başarıyla gönderildi!"
- ✅ Form resets
- ✅ reCAPTCHA resets
- ✅ Entry in database with status='pending'
- ✅ Admin receives email notification
- ✅ Log entry in `contact-submissions.txt`

**Verification:**
```sql
SELECT * FROM contact_submissions 
WHERE email = 'john.smith@gmail.com' 
ORDER BY created_at DESC LIMIT 1;
```

---

### Test 2: Block Gibberish Name ❌

**Purpose:** Verify detection of random/fake names

**Steps:**
1. Name: `JYupWMLW`
2. Email: `test@gmail.com`
3. Message: Valid message (3 sentences)
4. Complete reCAPTCHA
5. Submit

**Expected Result:**
- ❌ Error: "Lütfen gerçek adınızı kullanın (geçersiz isim formatı)"
- ❌ Form not submitted
- ❌ Name field highlighted in red
- ❌ Log entry in `contact-spam.txt`

**Why it blocks:**
- Vowel ratio < 20% (only 1 vowel 'u' in 8 characters = 12.5%)
- Excessive consonant runs

**Additional Test Cases:**
```
asdfghjkl  → Blocked (keyboard spam)
XXXXXXXX   → Blocked (all same character)
qwerty123  → Blocked (contains numbers)
Test       → Blocked (too short, only 4 chars)
```

---

### Test 3: Block Short Message ❌

**Purpose:** Verify minimum message length and sentence requirements

**Steps:**
1. Name: `John Smith`
2. Email: `john@gmail.com`
3. Message: `20`
4. Submit

**Expected Result:**
- ❌ Error: "Mesajınız çok kısa. En az 30 karakter ve 3 cümle yazmalısınız"
- ❌ Message field highlighted
- ❌ Character count shown

**Additional Test Cases:**
```
"20"                    → Blocked (too short, only numbers)
"This is short"         → Blocked (< 30 chars, 1 sentence)
"One. Two."            → Blocked (2 sentences, but < 30 chars)
"This is long enough but only one sentence that is very very long"
                        → Blocked (1 sentence despite length)
```

---

### Test 4: Block SQL Injection in Email ❌

**Purpose:** Verify SQL injection pattern detection

**Steps:**
1. Name: `John Smith`
2. Email: `(select(0)from(select(sleep(15)))v)/*'+(select(0)from(select(sleep(15)))v)+"*/`
3. Message: Valid message
4. Submit

**Expected Result:**
- ❌ Error: "Email adresinde geçersiz karakterler tespit edildi"
- ❌ Email field highlighted
- ❌ Log entry with pattern detected

**Additional SQL Injection Test Cases:**
```
admin'--@test.com                     → Blocked (SQL comment)
test' OR '1'='1@example.com          → Blocked (OR injection)
union select@test.com                 → Blocked (UNION keyword)
sleep(10)@example.com                 → Blocked (sleep function)
```

---

### Test 5: Block SQL Injection in Message ❌

**Purpose:** Verify message content is scanned for malicious code

**Steps:**
1. Name: `John Smith`
2. Email: `john@gmail.com`
3. Message: `'; DROP TABLE users; -- This is a test. Please ignore this. Thank you.`
4. Submit

**Expected Result:**
- ❌ Error: "Mesajda geçersiz içerik tespit edildi"
- ❌ Message field highlighted
- ❌ Detailed log with redacted payload

**Additional Malicious Message Test Cases:**
```
<script>alert('xss')</script>. This is a test. Please help.
                        → Blocked (<script> tag)

<?php system('ls'); ?>. This is a test. Please respond.
                        → Blocked (PHP code)

${jndi:ldap://evil.com}. This is a test. Thank you.
                        → Blocked (JNDI injection)

SELECT * FROM users WHERE id=1. This is a test. Please call.
                        → Blocked (SELECT keyword)
```

---

### Test 6: Block Fake Email Domains ❌

**Purpose:** Verify disposable/fake email domains are rejected

**Steps:**
1. Name: `John Smith`
2. Email: `test@tempmail.com`
3. Message: Valid message
4. Submit

**Expected Result:**
- ❌ Error: "Bu email domaini kabul edilmemektedir. Lütfen gerçek email adresinizi kullanın"
- ❌ Email field highlighted

**Blocked Domains to Test:**
```
test@example.com        → Blocked
test@tempmail.com       → Blocked
test@guerrillamail.com  → Blocked
test@mailinator.com     → Blocked
test@10minutemail.com   → Blocked
test@yopmail.com        → Blocked
```

---

### Test 7: Rate Limiting (Client-Side) ❌

**Purpose:** Verify client-side rate limiting using localStorage

**Steps:**
1. Submit valid form successfully
2. Immediately try to submit again (within 5 minutes)
3. Observe error

**Expected Result:**
- ❌ Error: "Form gönderimi için X dakika beklemeniz gerekiyor"
- ❌ Countdown shown (e.g., "5 dakika")
- ❌ Submit button may be disabled

**Verification:**
```javascript
// Check localStorage
console.log(localStorage.getItem('lastContactSubmission'));
// Should show recent timestamp
```

**Bypass Test:**
```javascript
// Clear localStorage to reset (simulates different browser/device)
localStorage.removeItem('lastContactSubmission');
// Should now allow submission
```

---

### Test 8: Name Cooldown (30 Days) ❌

**Purpose:** Verify same name cannot submit twice within 30 days

**Steps:**
1. Submit valid form with Name: `John Smith`
2. Wait a few minutes
3. Try to submit again with same Name: `John Smith` (different email)
4. Observe error

**Expected Result:**
- ❌ Error: "Bu isimle yakın zamanda mesaj gönderilmiş. Lütfen gerçek adınızı kullanın."
- ❌ Days remaining shown

**Client-Side Storage:**
```javascript
// Check localStorage
console.log(JSON.parse(localStorage.getItem('contactSubmittedNames')));
// Should show: {"john smith": 1727702400000}
```

**Server-Side Verification:**
```sql
SELECT name, created_at FROM contact_submissions 
WHERE LOWER(name) = 'john smith' 
AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
```

**Edge Cases:**
```
"John Smith" first time      → Allowed
"John Smith" after 1 day     → Blocked (29 days remaining)
"john smith" (lowercase)     → Blocked (case-insensitive)
"John  Smith" (extra space)  → Blocked (normalized)
"Jane Smith" (different)     → Allowed (different name)
"John Smith" after 31 days   → Allowed (cooldown expired)
```

---

### Test 9: Honeypot Detection ❌

**Purpose:** Verify bots filling hidden fields are rejected

**Steps:**
1. Open browser console (F12)
2. Run:
   ```javascript
   document.querySelector('input[name="website"]').value = 'http://spam.com';
   ```
3. Fill rest of form validly
4. Submit

**Expected Result:**
- ❌ Error: "Bir hata oluştu. Lütfen daha sonra tekrar deneyin."
- ❌ No specific field highlighted (silent rejection)
- ❌ Log entry: "Blocked honeypot: website field filled"

**Additional Honeypot Fields:**
```javascript
document.querySelector('input[name="company"]').value = 'SpamCorp';
// Should also trigger rejection
```

---

### Test 10: Time-Based Detection ❌

**Purpose:** Verify forms filled too quickly are rejected

**Steps:**
1. Open form
2. Immediately fill all fields (within 2 seconds)
3. Submit

**Expected Result:**
- ❌ Error: "Lütfen formu doldurmak için biraz daha zaman ayırın."
- ❌ Form not submitted
- ❌ Log entry: "Blocked too_fast: time_diff=2"

**How it works:**
- Hidden field `form_timestamp` set on page load
- Server compares to current time
- If < 3 seconds, rejected as bot

---

### Test 11: Missing reCAPTCHA ❌

**Purpose:** Verify reCAPTCHA is required

**Steps:**
1. Fill form validly
2. Do NOT complete reCAPTCHA checkbox
3. Submit

**Expected Result:**
- ❌ Error: "Lütfen reCAPTCHA doğrulamasını tamamlayın."
- ❌ reCAPTCHA box highlighted
- ❌ Form not submitted

---

### Test 12: Invalid Email Format ❌

**Purpose:** Verify basic email format validation

**Steps:**
1. Email: `notanemail`
2. Submit

**Expected Result:**
- ❌ Error: "Geçersiz email formatı"
- ❌ HTML5 validation (before JS validation)

**Additional Test Cases:**
```
notanemail             → Blocked (no @)
test@                  → Blocked (no domain)
@example.com           → Blocked (no local part)
test..test@example.com → Blocked (double dot)
test@.com              → Blocked (domain starts with .)
test@example           → Blocked (no TLD)
test @example.com      → Blocked (space)
```

---

### Test 13: Email MX Record Validation ❌

**Purpose:** Verify email domain has mail server

**Steps:**
1. Email: `test@nonexistentdomain12345.com`
2. Submit

**Expected Result:**
- ❌ Error: "Email domain does not exist or has no mail server"
- ❌ Server-side check (client passes, server rejects)

**Note:** This requires server-side DNS lookup, so only works after client validation.

---

### Test 14: Blocked Name List ❌

**Purpose:** Verify permanently blocked names are rejected

**Setup:**
```bash
php manage-blocked-names.php block "TestSpammer" "Test block"
```

**Steps:**
1. Name: `TestSpammer`
2. Submit

**Expected Result:**
- ❌ Error: "This name cannot be used"
- ❌ Immediate rejection
- ❌ Cannot be bypassed

**Verification:**
```sql
SELECT * FROM blocked_names WHERE name = 'TestSpammer';
```

**Cleanup:**
```bash
php manage-blocked-names.php unblock "TestSpammer"
```

---

### Test 15: Server-Side Rate Limiting ❌

**Purpose:** Verify database-backed rate limiting (bypasses localStorage)

**Steps:**
1. Submit valid form
2. Open incognito/private window (clears localStorage)
3. Submit form again from same IP
4. Observe error

**Expected Result:**
- ❌ Error: "Please wait X minutes before submitting again"
- ❌ Database check, not localStorage
- ❌ Log entry with IP and timestamp

**Verification:**
```sql
SELECT ip_address, email, created_at 
FROM contact_submissions 
ORDER BY created_at DESC LIMIT 5;
```

---

## 🔍 Testing Tools

### Browser Console Tests

```javascript
// Test sentence counting
const message = "First sentence. Second sentence! Third question?";
const sentences = message.split(/[.!?]+/).filter(s => s.trim().length >= 2);
console.log('Sentence count:', sentences.length); // Should be 3

// Test vowel ratio
const name = "JYupWMLW";
const vowels = 'aeıioöuüAEIİOÖUÜ';
const vowelCount = name.split('').filter(c => vowels.includes(c)).length;
const ratio = vowelCount / name.length;
console.log('Vowel ratio:', ratio); // Should be < 0.2

// Test dangerous pattern
const email = "select(0)from@test.com";
const hasSqlPattern = /select\s*\(/i.test(email);
console.log('Has SQL pattern:', hasSqlPattern); // Should be true
```

### Database Verification

```sql
-- Check recent submissions
SELECT * FROM contact_submissions 
ORDER BY created_at DESC LIMIT 10;

-- Check blocked names
SELECT * FROM blocked_names;

-- Check spam statistics
SELECT 
    status,
    COUNT(*) as count
FROM contact_submissions
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY status;

-- Find duplicate submissions
SELECT name, COUNT(*) as count
FROM contact_submissions
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY name
HAVING count > 1;
```

### Log Verification

```bash
# View spam attempts
tail -f site/assets/logs/contact-spam.txt

# View successful submissions
tail -f site/assets/logs/contact-submissions.txt

# Search for specific pattern
grep "JYupWMLW" site/assets/logs/contact-spam.txt

# Count spam attempts today
grep "$(date +%Y-%m-%d)" site/assets/logs/contact-spam.txt | wc -l
```

---

## 📊 Test Results Template

Use this template to document your testing:

```
Date: _____________
Tester: _____________
Environment: [ ] Production [ ] Staging [ ] Local

Test Results:
[ ] Test 1: Valid Submission - PASS / FAIL
[ ] Test 2: Block Gibberish Name - PASS / FAIL
[ ] Test 3: Block Short Message - PASS / FAIL
[ ] Test 4: Block SQL Injection (Email) - PASS / FAIL
[ ] Test 5: Block SQL Injection (Message) - PASS / FAIL
[ ] Test 6: Block Fake Domains - PASS / FAIL
[ ] Test 7: Rate Limiting (Client) - PASS / FAIL
[ ] Test 8: Name Cooldown - PASS / FAIL
[ ] Test 9: Honeypot Detection - PASS / FAIL
[ ] Test 10: Time-Based Detection - PASS / FAIL
[ ] Test 11: Missing reCAPTCHA - PASS / FAIL
[ ] Test 12: Invalid Email Format - PASS / FAIL
[ ] Test 13: MX Record Validation - PASS / FAIL
[ ] Test 14: Blocked Name List - PASS / FAIL
[ ] Test 15: Server-Side Rate Limiting - PASS / FAIL

Issues Found:
_______________________________________________________
_______________________________________________________

Notes:
_______________________________________________________
_______________________________________________________

Sign-off:
Tester: _________________ Date: _____________
Approver: _______________ Date: _____________
```

---

## 🐛 Debugging Tips

### Form Not Submitting

1. **Check browser console:**
   ```javascript
   // Look for errors in console (F12)
   console.log('Form found:', document.querySelector('form[data-url*="contact"]'));
   console.log('Submit button:', document.querySelector('.contact-button'));
   ```

2. **Check AJAX URL:**
   ```javascript
   console.log(document.querySelector('form').dataset.url);
   // Should point to your AJAX handler
   ```

3. **Check reCAPTCHA:**
   ```javascript
   console.log(typeof grecaptcha !== 'undefined');
   console.log(grecaptcha.getResponse());
   // Should return non-empty string after completing
   ```

### False Positives (Valid Users Blocked)

1. **Check specific error message**
2. **Test with different name** (may be in blocked list)
3. **Adjust vowel ratio threshold** for non-Latin names
4. **Review sentence splitting logic** for punctuation-heavy text

### Spam Getting Through

1. **Check logs** to see why it passed
2. **Add specific patterns** to dangerous_patterns array
3. **Block specific names/domains** using management script
4. **Lower thresholds** (more strict)

---

## ✅ Pre-Deployment Checklist

Before deploying to production:

- [ ] All 15 test cases pass
- [ ] Database properly configured
- [ ] reCAPTCHA keys verified (both site and secret)
- [ ] Admin email notifications working
- [ ] Logs being written to correct location
- [ ] Management script accessible and working
- [ ] Backup of current form template saved
- [ ] Backup of database completed
- [ ] Documentation reviewed by team
- [ ] Rollback plan documented

---

**Testing Status:** Ready for comprehensive testing

**Recommendation:** Complete all 15 tests in staging before production deployment.

**Support:** Refer to ANTI-SPAM-README.md for troubleshooting guidance.