# Contact Form Anti-Spam Protection for ProcessWire

Comprehensive anti-spam solution for ProcessWire contact forms with advanced validation, SQL injection protection, and bot detection.

## 🎯 Features

### Client-Side Protection (JavaScript)
- ✅ **Real-time validation** with user-friendly error messages
- ✅ **Name validation** - Detects gibberish names like `JYupWMLW`
- ✅ **Email validation** - Blocks SQL injection patterns and fake domains
- ✅ **Message validation** - Requires minimum 3 sentences, 30 characters
- ✅ **Rate limiting** - 5-minute cooldown using localStorage
- ✅ **Name cooldown** - Same name cannot submit twice within 30 days
- ✅ **Honeypot fields** - Hidden fields to catch bots
- ✅ **Time-based detection** - Blocks submissions faster than 3 seconds
- ✅ **Pattern detection** - Blocks SQL injection attempts like `select(0)from(select(sleep(15)))`

### Server-Side Protection (PHP)
- ✅ **Comprehensive validation** - Re-validates all client checks server-side
- ✅ **SQL injection detection** - Pattern matching for common attack vectors
- ✅ **Database-backed cooldowns** - Enforces 30-day name uniqueness
- ✅ **IP-based rate limiting** - Prevents spam from same IP/email
- ✅ **reCAPTCHA verification** - Server-side Google reCAPTCHA validation
- ✅ **MX record validation** - Checks email domain has mail server
- ✅ **Blocked names list** - Permanent ban for known spammers
- ✅ **Detailed logging** - All spam attempts logged for analysis

## 📋 Requirements

- ProcessWire 3.x
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.2+
- Google reCAPTCHA account (v2)

## 🚀 Installation

### Step 1: Database Setup

Run the SQL schema in your ProcessWire database:

```bash
# Via MySQL CLI
mysql -u your_user -p your_database < database-schema.sql

# Or via phpMyAdmin
# Import database-schema.sql
```

This creates two tables:
- `contact_submissions` - Stores all form submissions
- `blocked_names` - Stores permanently blocked names

### Step 2: Update Your Contact Form Template

Replace your existing contact form template with the enhanced version:

```bash
# Backup your current template
cp site/templates/contact.php site/templates/contact.php.backup

# Copy the new enhanced form
cp contact-form-enhanced.php site/templates/contact.php
```

### Step 3: Setup AJAX Handler

Add the handler to your AJAX processing file (usually `templates/ajax.php` or similar):

```php
<?php namespace ProcessWire;

// Your existing AJAX routing...

if($input->get('action') === 'contact') {
    require_once('./ajax-contact-handler.php');
    exit;
}
```

Or if you don't have an AJAX handler yet, create one:

```bash
cp ajax-contact-handler.php site/templates/ajax-contact.php
```

Then update your form's `data-url` attribute to point to it.

### Step 4: Configure reCAPTCHA

1. Get your reCAPTCHA keys from: https://www.google.com/recaptcha/admin

2. Update the site key in `contact-form-enhanced.php`:
```html
<div class="g-recaptcha" data-sitekey="YOUR_SITE_KEY_HERE"></div>
```

3. Update the secret key in `ajax-contact-handler.php`:
```php
'recaptcha_secret' => 'YOUR_SECRET_KEY_HERE'
```

### Step 5: Install Management Script (Optional)

Copy the management script for blocking/unblocking names:

```bash
cp manage-blocked-names.php site/templates/admin/
```

## 📖 Usage

### Client-Side Features

The enhanced contact form automatically:

1. **Validates names** in real-time
   - Rejects gibberish like `JYupWMLW`
   - Requires realistic human names
   - Checks vowel ratio to detect random characters

2. **Validates emails**
   - Blocks SQL injection patterns: `select(`, `sleep(`, `union`, etc.
   - Rejects fake domains: `example.com`, `test.com`, etc.
   - Validates format and structure

3. **Validates messages**
   - Requires minimum 3 sentences
   - Blocks messages like just `20`
   - Prevents repetitive spam content

4. **Prevents duplicate submissions**
   - 5-minute rate limit per device
   - 30-day cooldown per name (stored in localStorage)

### Server-Side Validation

All client validations are re-checked server-side, plus:

- **Database-backed name cooldown** - Prevents bypassing localStorage
- **IP-based rate limiting** - Blocks rapid submissions from same IP
- **reCAPTCHA verification** - Confirms human interaction
- **MX record check** - Validates email domain has mail server
- **Blocked names check** - Permanent bans for known spammers

### Blocked Names Management

#### CLI Commands

```bash
# Block a name
php site/templates/admin/manage-blocked-names.php block "JYupWMLW" "Spam pattern detected"

# Unblock a name
php site/templates/admin/manage-blocked-names.php unblock "JYupWMLW"

# List all blocked names
php site/templates/admin/manage-blocked-names.php list

# View statistics (last 30 days)
php site/templates/admin/manage-blocked-names.php stats

# Find duplicate submissions (last 7 days)
php site/templates/admin/manage-blocked-names.php duplicates

# Purge old spam (older than 90 days)
php site/templates/admin/manage-blocked-names.php purge --days=90

# Show help
php site/templates/admin/manage-blocked-names.php help
```

#### Web API (Superuser only)

```bash
# Block a name
curl "https://yoursite.com/admin/manage-blocked-names.php?action=block&name=JYupWMLW&reason=spam"

# Unblock a name
curl "https://yoursite.com/admin/manage-blocked-names.php?action=unblock&name=JYupWMLW"

# List blocked names
curl "https://yoursite.com/admin/manage-blocked-names.php?action=list"

# Get statistics
curl "https://yoursite.com/admin/manage-blocked-names.php?action=stats&days=30"
```

## 🛡️ Security Features

### Protection Against Your Spam Examples

#### Example 1: SQL Injection in Email
**Spam:** `(select(0)from(select(sleep(15)))v)/*'+(select(0)from(select(sleep(15)))v)+"*/`

**Blocked by:**
- Client-side pattern matching (immediate rejection)
- Server-side dangerous pattern detection
- Email format validation (not a valid email)

#### Example 2: Short Message
**Spam:** `20`

**Blocked by:**
- Client-side message length validation (< 30 chars)
- Server-side sentence count validation (< 3 sentences)
- Client-side "numbers only" detection

#### Example 3: Gibberish Name
**Spam:** `JYupWMLW`

**Blocked by:**
- Client-side vowel ratio check (< 20% vowels)
- Server-side human name validation
- Blocked names database (after first detection)
- 30-day name cooldown (prevents reuse)

### Additional Protections

1. **Honeypot Fields**
   - Hidden `website` and `company` fields
   - Bots fill these, humans don't see them
   - Silent rejection if filled

2. **Time-Based Detection**
   - Form must be open for at least 3 seconds
   - Prevents automated rapid submissions

3. **Rate Limiting**
   - Client: 5-minute cooldown (localStorage)
   - Server: Database-backed IP + email throttling

4. **Name Cooldown**
   - Same exact name blocked for 30 days
   - Configurable duration
   - Case-insensitive matching

## ⚙️ Configuration

Edit `ajax-contact-handler.php` to customize:

```php
$antispamConfig = [
    'min_sentences' => 3,              // Minimum sentences in message
    'min_message_length' => 30,        // Minimum message characters
    'max_message_length' => 4000,      // Maximum message characters
    'min_name_length' => 2,            // Minimum name characters
    'max_name_length' => 80,           // Maximum name characters
    'name_cooldown_days' => 30,        // Days before same name can submit again
    'rate_limit_minutes' => 5,         // Minutes between submissions from same IP/email
    'recaptcha_secret' => 'YOUR_KEY',  // Google reCAPTCHA secret key
    
    // Add more blocked domains
    'blocked_domains' => [
        'example.com',
        'test.com',
        'your-blocked-domain.com'
    ]
];
```

## 📊 Monitoring & Logs

### ProcessWire Logs

All spam attempts are logged in ProcessWire's logging system:

```bash
# View spam log
tail -f site/assets/logs/contact-spam.txt

# View admin actions
tail -f site/assets/logs/contact-admin.txt

# View errors
tail -f site/assets/logs/contact-errors.txt

# View successful submissions
tail -f site/assets/logs/contact-submissions.txt
```

### Database Views

Query pre-built views for monitoring:

```sql
-- Recent submissions (last 100)
SELECT * FROM recent_contact_submissions;

-- Spam statistics (last 30 days)
SELECT * FROM contact_spam_stats;

-- Find duplicate names
SELECT name, COUNT(*) as count 
FROM contact_submissions 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY name 
HAVING count > 1;
```

## 🧪 Testing

### Test Valid Submission

```javascript
// In browser console
document.getElementById('name').value = 'John Smith';
document.getElementById('email').value = 'john@gmail.com';
document.getElementById('phone').value = '+1-555-1234';
document.getElementById('message').value = 'This is a test message. It has three sentences. Please ignore it.';
// Complete reCAPTCHA manually
document.querySelector('.contact-button').click();
```

### Test Spam Detection

```javascript
// Test 1: Gibberish name
document.getElementById('name').value = 'JYupWMLW';
// Expected: "Please use your real name (invalid name format)"

// Test 2: SQL injection in email
document.getElementById('email').value = "select(0)from(select(sleep(15)))@test.com";
// Expected: "Email contains invalid characters"

// Test 3: Short message
document.getElementById('message').value = '20';
// Expected: "Message is too short. Minimum 30 characters and 3 sentences required"

// Test 4: Less than 3 sentences
document.getElementById('message').value = 'This is only one sentence that is very long to pass length check';
// Expected: "Please write at least 3 sentences. Current: 1"
```

## 🔧 Troubleshooting

### Form not submitting

1. **Check browser console** for JavaScript errors
2. **Verify reCAPTCHA** is loaded and configured correctly
3. **Check AJAX URL** in form's `data-url` attribute
4. **Verify database tables** exist and are accessible

### False positives (valid users blocked)

1. **Check vowel ratio** - Adjust threshold in code if needed for non-Latin names
2. **Review blocked domains** - Remove legitimate domains from blocklist
3. **Adjust sentence detection** - Lower `min_sentences` if needed
4. **Check logs** - Review why specific submissions were blocked

### Spam still getting through

1. **Lower reCAPTCHA score threshold** (if using v3)
2. **Add more patterns** to `dangerous_patterns` array
3. **Review spam logs** to identify new patterns
4. **Block specific names** using management script
5. **Reduce rate limit window** to be more restrictive

## 📝 Example Spam Log Entry

```
2025-09-30 12:34:56: Blocked invalid_name: Please use your real name (invalid name format) | 
IP: 192.168.1.100 | 
Data: {"name":"JYupWMLW","email":"test@example.com","reason":"Please use your real name"}
```

## 🎨 Customization

### Translate Messages

All user-facing messages use ProcessWire's `__()` function for translation. Edit your language file to customize:

```php
// site/templates/translations/default.php
$translations = [
    'Name - Surname' => 'Ad - Soyad',
    'Phone' => 'Telefon',
    'Email' => 'E-posta',
    'Message' => 'Mesaj',
    'Submit' => 'Gönder',
    // ... more translations
];
```

### Customize Validation Rules

Edit the `AntiSpam` object in `contact-form-enhanced.php`:

```javascript
const ANTISPAM_CONFIG = {
    minSentences: 3,           // Change to 2 or 4
    minMessageLength: 30,      // Adjust as needed
    nameCooldownDays: 30,      // Change cooldown period
    // ... more config
};
```

### Style Validation Feedback

Add custom CSS for validation states:

```css
/* Valid input */
input:valid, textarea:valid {
    border-color: #10b981;
    background-image: url('checkmark.svg');
}

/* Invalid input */
input:invalid, textarea:invalid {
    border-color: #ef4444;
    background-image: url('warning.svg');
}
```

## 📚 Database Schema

### contact_submissions

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| name | VARCHAR(80) | Submitter name |
| email | VARCHAR(255) | Submitter email |
| phone | VARCHAR(20) | Optional phone |
| message | TEXT | Message content |
| ip_address | VARCHAR(45) | Client IP (IPv6 support) |
| user_agent | VARCHAR(500) | Browser user agent |
| page_url | VARCHAR(500) | Page URL where submitted |
| flags | JSON | Validation flags |
| status | ENUM | pending/read/replied/spam/deleted |
| created_at | DATETIME | Submission timestamp |
| updated_at | DATETIME | Last update timestamp |

### blocked_names

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| name | VARCHAR(80) | Blocked name (unique) |
| reason | VARCHAR(255) | Block reason |
| added_by | VARCHAR(50) | Who added the block |
| created_at | DATETIME | Block timestamp |

## 🤝 Contributing

Found a new spam pattern? Submit it as an issue or pull request!

## 📄 License

MIT License - Feel free to use and modify for your projects.

## ✨ Credits

Built for ProcessWire with security best practices from OWASP and industry standards.

---

**Need help?** Check ProcessWire forums or open an issue on GitHub.

**Found this useful?** Star the repository and share with the ProcessWire community!