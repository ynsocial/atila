# Quick Start Guide - Contact Form Anti-Spam

## 🚀 5-Minute Setup

### What You'll Get

This anti-spam solution will block:
- ✅ SQL injection attempts like `select(0)from(select(sleep(15)))`
- ✅ Gibberish names like `JYupWMLW`
- ✅ Short spam messages like `20`
- ✅ Duplicate submissions from same name within 30 days
- ✅ Fake email domains
- ✅ Rapid-fire bot submissions

### Files Included

1. **contact-form-enhanced.php** - Your new contact form template (replaces existing)
2. **ajax-contact-handler.php** - Server-side validation handler
3. **database-schema.sql** - Database tables for tracking submissions
4. **manage-blocked-names.php** - Admin tool for blocking spammers
5. **ANTI-SPAM-README.md** - Complete documentation

---

## Step-by-Step Installation

### 1️⃣ Database Setup (2 minutes)

```bash
# Login to MySQL
mysql -u your_user -p your_database

# Or use phpMyAdmin and import the file
```

Then run:
```sql
source database-schema.sql;
```

This creates:
- `contact_submissions` table (stores all submissions)
- `blocked_names` table (stores banned names)

**Verify installation:**
```sql
SHOW TABLES LIKE 'contact_%';
SELECT * FROM blocked_names;
```

---

### 2️⃣ Get reCAPTCHA Keys (1 minute)

1. Go to: https://www.google.com/recaptcha/admin
2. Register a new site (reCAPTCHA v2 checkbox)
3. Copy your **Site Key** and **Secret Key**

---

### 3️⃣ Update Contact Form (1 minute)

**Option A: Replace entire template**
```bash
# Backup current template
cp site/templates/contact.php site/templates/contact.php.backup

# Use new template
cp contact-form-enhanced.php site/templates/contact.php
```

**Option B: Keep your design, just update form section**

Replace only the `<form>` section in your existing template with the one from `contact-form-enhanced.php`.

**Update reCAPTCHA Site Key:**

Find this line in the template:
```html
<div class="g-recaptcha" data-sitekey="6LcZRT4rAAAAANpfPst5iQls8giUv8-1FdsllfgJ"></div>
```

Replace with your Site Key:
```html
<div class="g-recaptcha" data-sitekey="YOUR_SITE_KEY_HERE"></div>
```

---

### 4️⃣ Setup AJAX Handler (1 minute)

**Find your AJAX handler** (usually `templates/ajax.php` or similar)

Add this code:
```php
<?php namespace ProcessWire;

// Your existing code...

if($input->get('action') === 'contact') {
    require_once(__DIR__ . '/ajax-contact-handler.php');
    exit;
}
```

**If you don't have an AJAX handler:**

1. Copy `ajax-contact-handler.php` to `site/templates/`
2. Update form's `data-url` to point to it:
```html
<form data-url="<?php echo $config->urls->templates; ?>ajax-contact-handler.php">
```

**Update reCAPTCHA Secret Key:**

Open `ajax-contact-handler.php` and find:
```php
'recaptcha_secret' => '6LcZRT4rAAAAAJ_YOUR_SECRET_KEY_HERE',
```

Replace with your Secret Key:
```php
'recaptcha_secret' => 'YOUR_SECRET_KEY_HERE',
```

---

### 5️⃣ Test Everything (30 seconds)

**Test 1: Valid Submission**
1. Open your contact form
2. Fill in:
   - Name: `John Smith`
   - Email: `john@gmail.com`
   - Message: `This is a test. It has three sentences. Please confirm it works.`
3. Complete reCAPTCHA
4. Submit

✅ Should succeed with: "Your message has been sent successfully!"

**Test 2: Block Gibberish Name**
1. Name: `JYupWMLW`
2. Submit

❌ Should block with: "Please use your real name (invalid name format)"

**Test 3: Block Short Message**
1. Message: `20`
2. Submit

❌ Should block with: "Message is too short. Minimum 30 characters and 3 sentences required"

**Test 4: Block SQL Injection**
1. Email: `select(0)from@test.com`
2. Submit

❌ Should block with: "Email contains invalid characters"

---

## 🎯 Common Issues & Fixes

### Issue: Form not submitting at all

**Solution:**
1. Open browser console (F12)
2. Check for JavaScript errors
3. Verify `data-url` in form points to correct AJAX handler
4. Ensure reCAPTCHA is loaded (check for `grecaptcha` object)

### Issue: "Database error" message

**Solution:**
1. Verify tables exist: `SHOW TABLES LIKE 'contact_%';`
2. Check database permissions
3. Review `site/assets/logs/contact-errors.txt`

### Issue: Valid users being blocked

**Solution:**
1. Check specific error message shown to user
2. For names with few vowels (some Turkish names), adjust vowel ratio in code
3. Review blocked domains list - remove any legitimate ones
4. Temporarily reduce minimum sentences to 2 instead of 3

### Issue: Spam still getting through

**Solution:**
1. Block the specific name: `php manage-blocked-names.php block "SpamName" "Reason"`
2. Add email domain to blocked list in `ajax-contact-handler.php`
3. Review logs to identify patterns: `tail -f site/assets/logs/contact-spam.txt`

---

## 📊 Monitor Spam Activity

### View Recent Submissions
```sql
SELECT * FROM recent_contact_submissions ORDER BY created_at DESC LIMIT 20;
```

### View Spam Statistics
```sql
SELECT * FROM contact_spam_stats;
```

### Find Duplicate Submissions
```bash
php manage-blocked-names.php duplicates --days=7
```

### View Logs
```bash
# Spam attempts
tail -f site/assets/logs/contact-spam.txt

# Successful submissions
tail -f site/assets/logs/contact-submissions.txt

# Errors
tail -f site/assets/logs/contact-errors.txt
```

---

## 🛠️ Management Commands

### Block a Spammer
```bash
php manage-blocked-names.php block "JYupWMLW" "Spam burst detected"
```

### Unblock a Name
```bash
php manage-blocked-names.php unblock "JohnDoe"
```

### List All Blocked Names
```bash
php manage-blocked-names.php list
```

### View Statistics (Last 30 Days)
```bash
php manage-blocked-names.php stats
```

### Clean Up Old Spam (90+ Days)
```bash
php manage-blocked-names.php purge --days=90
```

---

## 🎨 Customization Quick Reference

### Change Minimum Sentences (Currently: 3)

**Client-side:** `contact-form-enhanced.php`
```javascript
const ANTISPAM_CONFIG = {
    minSentences: 3,  // Change to 2 or 4
```

**Server-side:** `ajax-contact-handler.php`
```php
$antispamConfig = [
    'min_sentences' => 3,  // Change to 2 or 4
```

### Change Name Cooldown (Currently: 30 Days)

**Client-side:** `contact-form-enhanced.php`
```javascript
nameCooldownDays: 30,  // Change to 7, 14, 60, etc.
```

**Server-side:** `ajax-contact-handler.php`
```php
'name_cooldown_days' => 30,  // Change to 7, 14, 60, etc.
```

### Add More Blocked Domains

**Client-side:** `contact-form-enhanced.php`
```javascript
blockedDomains: [
    'example.com',
    'test.com',
    'your-spam-domain.com'  // Add here
],
```

**Server-side:** `ajax-contact-handler.php`
```php
'blocked_domains' => [
    'example.com',
    'test.com',
    'your-spam-domain.com'  // Add here
],
```

---

## 📧 Email Notifications

To receive email when submissions are successful, the handler already includes basic email functionality. To customize:

**Edit `ajax-contact-handler.php`:**
```php
// Around line 485
$adminEmail = wire('config')->adminEmail ?? 'your-email@example.com';
$subject = 'New Contact Form Submission';
$body = "Name: $name\nEmail: $email\nPhone: $phone\n\nMessage:\n$message\n\nIP: $clientIp";

mail($adminEmail, $subject, $body, "From: $email\r\nReply-To: $email");
```

For better email handling, consider using ProcessWire's WireMail:
```php
$mail = wireMail();
$mail->to($adminEmail)
    ->from($email, $name)
    ->subject($subject)
    ->body($body)
    ->send();
```

---

## ✅ Post-Installation Checklist

- [ ] Database tables created (`contact_submissions`, `blocked_names`)
- [ ] reCAPTCHA keys configured (both site key and secret key)
- [ ] Contact form template updated
- [ ] AJAX handler integrated
- [ ] Tested valid submission (success)
- [ ] Tested spam detection (blocked)
- [ ] Reviewed logs location
- [ ] Admin email notifications working (optional)
- [ ] Management script accessible
- [ ] Documented for team/future reference

---

## 🆘 Need Help?

1. **Check logs first:** `site/assets/logs/contact-*.txt`
2. **Review README:** See `ANTI-SPAM-README.md` for detailed docs
3. **Test in browser console:** Use F12 to see JavaScript errors
4. **Check database:** Verify tables and data are being saved

---

## 🎉 Success!

Your contact form is now protected against:
- SQL injection attacks
- Gibberish spam names
- Short/invalid messages
- Duplicate submissions
- Rapid-fire bots
- Fake email domains

**Enjoy your spam-free inbox!** 🚀