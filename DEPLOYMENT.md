# Deployment Guide - Contact Form Anti-Spam

**Target Environment:** Production ProcessWire Site  
**Deployment Time:** 10-15 minutes  
**Downtime Required:** None (unless replacing existing contact form)

---

## 📦 Pre-Deployment Preparation

### 1. Gather Information

Before starting, collect:
- [ ] ProcessWire site root path (e.g., `/var/www/html/site`)
- [ ] Database credentials (host, user, password, database name)
- [ ] SSH/FTP access credentials
- [ ] Current contact form template location
- [ ] AJAX handler location (if exists)
- [ ] Google reCAPTCHA account access

### 2. Backup Current System

**Critical:** Always backup before making changes!

```bash
# Backup current contact template
cp site/templates/contact.php site/templates/contact.php.backup.$(date +%Y%m%d)

# Backup database
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# Or use ProcessWire backup module if available
```

### 3. Prepare Files

Download all files to your local machine:
```
contact-form-enhanced.php
ajax-contact-handler.php
database-schema.sql
manage-blocked-names.php
antispam-config.php
```

---

## 🚀 Deployment Steps

### Step 1: Database Setup (5 minutes)

#### Option A: Via phpMyAdmin (Recommended for beginners)
1. Login to phpMyAdmin
2. Select your ProcessWire database
3. Click "Import" tab
4. Choose file: `database-schema.sql`
5. Click "Go"
6. Verify tables created: `contact_submissions`, `blocked_names`

#### Option B: Via MySQL CLI (Advanced)
```bash
# Login to MySQL
mysql -u your_user -p

# Select database
USE your_database;

# Import schema
SOURCE /path/to/database-schema.sql;

# Verify tables
SHOW TABLES LIKE 'contact_%';
```

#### Verification
```sql
-- Should show 2 tables
SHOW TABLES LIKE 'contact_%';

-- Should show sample blocked names
SELECT * FROM blocked_names;

-- Should be empty (no submissions yet)
SELECT COUNT(*) FROM contact_submissions;
```

---

### Step 2: Configure reCAPTCHA (2 minutes)

1. **Get reCAPTCHA Keys**
   - Go to: https://www.google.com/recaptcha/admin
   - Click "+" to create new site
   - Settings:
     - Label: "Your Site Contact Form"
     - reCAPTCHA type: v2 "I'm not a robot" Checkbox
     - Domains: yourdomain.com
   - Submit
   - **Copy** Site Key and Secret Key

2. **Update Configuration Files**

   **File:** `contact-form-enhanced.php` (line ~35)
   ```html
   <!-- Find: -->
   <div class="g-recaptcha" data-sitekey="6LcZRT4rAAAAANpfPst5iQls8giUv8-1FdsllfgJ"></div>
   
   <!-- Replace with: -->
   <div class="g-recaptcha" data-sitekey="YOUR_SITE_KEY_HERE"></div>
   ```

   **File:** `ajax-contact-handler.php` (line ~41)
   ```php
   // Find:
   'recaptcha_secret' => 'YOUR_SECRET_KEY_HERE',
   
   // Replace with:
   'recaptcha_secret' => 'your_actual_secret_key',
   ```

   **File:** `antispam-config.php` (line ~88-89)
   ```php
   'recaptcha_site_key' => 'YOUR_SITE_KEY_HERE',
   'recaptcha_secret' => 'YOUR_SECRET_KEY_HERE',
   ```

---

### Step 3: Upload Files (3 minutes)

#### Via FTP/SFTP (FileZilla, WinSCP, etc.)

```
Upload to your ProcessWire installation:

contact-form-enhanced.php      → site/templates/contact.php
ajax-contact-handler.php       → site/templates/ajax-contact.php
manage-blocked-names.php       → site/templates/admin/manage-blocked-names.php
antispam-config.php           → site/templates/antispam-config.php
```

#### Via SSH/Command Line

```bash
# Upload files (adjust paths as needed)
scp contact-form-enhanced.php user@yourserver:/var/www/html/site/templates/contact.php
scp ajax-contact-handler.php user@yourserver:/var/www/html/site/templates/ajax-contact.php
scp manage-blocked-names.php user@yourserver:/var/www/html/site/templates/admin/
scp antispam-config.php user@yourserver:/var/www/html/site/templates/

# Set permissions
ssh user@yourserver
cd /var/www/html/site/templates
chmod 644 contact.php ajax-contact.php antispam-config.php
chmod 755 admin/manage-blocked-names.php
```

---

### Step 4: Integrate AJAX Handler (2 minutes)

**Option A: You already have ajax.php**

Edit your existing `site/templates/ajax.php` or similar:

```php
<?php namespace ProcessWire;

// Your existing AJAX routing...

// Add this at appropriate place:
if($input->get('action') === 'contact') {
    require_once(__DIR__ . '/ajax-contact.php');
    exit;
}
```

**Option B: No existing AJAX handler**

Your form in `contact-form-enhanced.php` should already point to:
```html
<form data-url="<?php echo $config->urls->templates; ?>ajax-contact.php">
```

No additional integration needed!

**Option C: Using a different AJAX URL**

Update the form's `data-url` attribute in `contact-form-enhanced.php`:
```html
<form data-url="<?php echo $ajax->url;?>?action=contact">
```

---

### Step 5: Configure Settings (2 minutes)

Edit `antispam-config.php`:

```php
// Update admin email (line ~126)
'admin_email' => 'your-email@yourdomain.com',  // CHANGE THIS

// Optional: Adjust validation thresholds
'min_sentences' => 3,           // Require 3 sentences
'name_cooldown_days' => 30,     // 30-day name cooldown
'rate_limit_minutes' => 5,      // 5 minutes between submissions

// Optional: Add your blocked domains
'blocked_domains' => [
    'example.com',
    'test.com',
    'your-blocked-domain.com',  // Add here
],
```

---

### Step 6: Test Everything (5 minutes)

#### Test 1: Valid Submission ✅
1. Go to your contact page
2. Fill with real data:
   - Name: Your Real Name
   - Email: your@email.com
   - Message: "This is a test. It has three sentences. Please confirm it works."
3. Complete reCAPTCHA
4. Submit
5. **Expected:** Success message, form resets

#### Test 2: Block Gibberish ❌
1. Name: `JYupWMLW`
2. Submit
3. **Expected:** Error about invalid name

#### Test 3: Block Short Message ❌
1. Message: `20`
2. Submit
3. **Expected:** Error about message length

#### Test 4: Check Database
```sql
SELECT * FROM contact_submissions ORDER BY created_at DESC LIMIT 5;
```
Should show your test submission(s).

#### Test 5: Check Logs
```bash
# Should show successful submission
tail site/assets/logs/contact-submissions.txt

# Should show blocked attempts
tail site/assets/logs/contact-spam.txt
```

---

## ✅ Post-Deployment Checklist

### Functional Tests
- [ ] Valid submission succeeds
- [ ] Receives admin email notification
- [ ] Database record created
- [ ] Form resets after submission
- [ ] Gibberish name blocked
- [ ] Short message blocked
- [ ] SQL injection blocked
- [ ] Fake email domain blocked

### Security Tests
- [ ] Honeypot triggers rejection
- [ ] Time-based detection works
- [ ] reCAPTCHA required
- [ ] Rate limiting enforced
- [ ] Name cooldown works

### System Tests
- [ ] No PHP errors in logs
- [ ] No JavaScript console errors
- [ ] Page loads correctly
- [ ] Form styling intact
- [ ] Mobile responsive

### Documentation
- [ ] Team informed of deployment
- [ ] Management commands documented
- [ ] Admin email configured
- [ ] Backup completed
- [ ] Rollback plan ready

---

## 🔄 Rollback Plan

If something goes wrong:

### Quick Rollback (< 1 minute)
```bash
# Restore old contact form
cp site/templates/contact.php.backup.YYYYMMDD site/templates/contact.php

# Clear cache
rm -rf site/assets/cache/FileCompiler/*
```

### Full Rollback (< 5 minutes)
```bash
# Restore contact form
cp site/templates/contact.php.backup.YYYYMMDD site/templates/contact.php

# Restore database (optional - only if database issues)
mysql -u username -p database_name < backup_YYYYMMDD.sql

# Remove uploaded files
rm site/templates/ajax-contact.php
rm site/templates/antispam-config.php
rm site/templates/admin/manage-blocked-names.php

# Clear cache
rm -rf site/assets/cache/*
```

---

## 🛠️ Troubleshooting

### Issue: Form Not Submitting

**Symptoms:** Button clicks, nothing happens

**Solutions:**
1. Open browser console (F12) - check for JavaScript errors
2. Verify `data-url` attribute points to correct AJAX handler
3. Check reCAPTCHA is loading: `console.log(typeof grecaptcha)`
4. Check network tab for failed requests

### Issue: "Database Error" Message

**Symptoms:** Error about database connection/query

**Solutions:**
1. Verify tables exist: `SHOW TABLES LIKE 'contact_%';`
2. Check database permissions
3. Review ProcessWire error logs
4. Check `site/assets/logs/contact-errors.txt`

### Issue: Valid Users Being Blocked

**Symptoms:** Legitimate submissions rejected

**Solutions:**
1. Check specific error message
2. Review validation thresholds in `antispam-config.php`
3. For non-Latin names, adjust vowel ratio
4. Check if name is in blocked list: `SELECT * FROM blocked_names;`

### Issue: reCAPTCHA Not Loading

**Symptoms:** No reCAPTCHA checkbox visible

**Solutions:**
1. Verify reCAPTCHA script tag present: `<script src="https://www.google.com/recaptcha/api.js"`
2. Check site key is correct
3. Verify domain is whitelisted in reCAPTCHA admin
4. Check browser console for errors

### Issue: Email Notifications Not Sending

**Symptoms:** Submissions work but no email received

**Solutions:**
1. Check `admin_email` in `antispam-config.php`
2. Verify server can send email: test with ProcessWire's WireMail
3. Check spam folder
4. Review `site/assets/logs/contact-errors.txt`
5. Consider using SMTP module instead of PHP mail()

---

## 📊 Monitoring Post-Deployment

### First 24 Hours

**Check every 4 hours:**
```bash
# Count submissions
mysql -u user -p -e "SELECT COUNT(*) FROM your_db.contact_submissions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR);"

# Count spam blocked
grep "$(date +%Y-%m-%d)" site/assets/logs/contact-spam.txt | wc -l

# Check for errors
tail -50 site/assets/logs/contact-errors.txt
```

### First Week

**Daily checks:**
- Review spam log for new patterns
- Check for false positives
- Monitor submission volume
- Review blocked names list

### Ongoing

**Weekly:**
```bash
# Get statistics
php site/templates/admin/manage-blocked-names.php stats --days=7

# Find duplicates
php site/templates/admin/manage-blocked-names.php duplicates --days=7
```

**Monthly:**
```bash
# Purge old spam (older than 90 days)
php site/templates/admin/manage-blocked-names.php purge --days=90

# Review blocked domains, update as needed
```

---

## 🔐 Security Hardening (Optional)

### 1. Restrict Management Script Access

Edit `manage-blocked-names.php` (line ~485):
```php
// Change from:
if (!wire('user')->isLoggedin() || !wire('user')->isSuperuser()) {
    die('Access denied. Superuser login required.');
}

// To (more strict):
if (!wire('user')->isSuperuser() || $_SERVER['REMOTE_ADDR'] !== 'YOUR_IP') {
    die('Access denied.');
}
```

### 2. Move Config Outside Web Root

```bash
# Move config to parent directory
mv site/templates/antispam-config.php ../antispam-config.php

# Update require statements in ajax-contact.php
require_once(__DIR__ . '/../../antispam-config.php');
```

### 3. Enable HTTPS

```bash
# Update form to require HTTPS
# In contact-form-enhanced.php, add:
<?php if (!$config->https) wire('session')->redirect($page->httpUrl); ?>
```

### 4. Add IP Blocking Table (Advanced)

```sql
CREATE TABLE blocked_ips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL UNIQUE,
    reason VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Block an IP
INSERT INTO blocked_ips (ip_address, reason) VALUES ('192.168.1.100', 'Spam source');
```

---

## 📝 Deployment Checklist Summary

```
Pre-Deployment:
☐ Information gathered (DB credentials, paths, etc.)
☐ Current system backed up (files + database)
☐ Files prepared and configured locally
☐ reCAPTCHA keys obtained

Deployment:
☐ Database schema imported
☐ Tables verified (contact_submissions, blocked_names)
☐ reCAPTCHA keys configured (3 files)
☐ Admin email configured
☐ Files uploaded to server
☐ Permissions set correctly
☐ AJAX handler integrated

Testing:
☐ Valid submission works
☐ Spam patterns blocked
☐ Database records created
☐ Logs being written
☐ Email notifications sent

Post-Deployment:
☐ Team notified
☐ Documentation shared
☐ Monitoring enabled
☐ Rollback plan documented
```

---

## 🎉 Deployment Complete!

**Next Steps:**

1. **Monitor for 24-48 hours**
   - Check logs regularly
   - Watch for errors
   - Verify spam is blocked

2. **Fine-tune as needed**
   - Adjust validation thresholds
   - Add domain-specific blocks
   - Update blocked names

3. **Train your team**
   - Share documentation
   - Demonstrate management commands
   - Establish monitoring routine

4. **Celebrate!**
   - Your inbox should now be spam-free
   - Legitimate users can still contact you
   - Everything is logged for analysis

---

**Deployment Status:** ✅ Complete

**Support:** Refer to [INDEX.md](INDEX.md) for documentation navigation

**Need Help?** Review [ANTI-SPAM-README.md](ANTI-SPAM-README.md) § Troubleshooting

---

*Successfully deployed on: ________________*  
*Deployed by: ________________*  
*Production URL: ________________*