<?php namespace ProcessWire;?>
<div id="content">
    <div class="pt100"></div>
    <?php include "partials/breadcrumb.php";?>
    <div class="container-1350">
        <h1 class="mt50 mb50 layer-title"><?php echo $page->title;?></h1>
    </div>
    <section class="detail container-1350">
        <div class="row">
            <div class="col-lg-6 order-2 order-md-1">
                <img <?php echo renderImage($page->image);?> class="img-fluid border-radius-v2">
            </div>
            <div class="col-lg-6 order-1 order-md-2">
                <div class="contacting bordered-boxes">
                    <form data-url="<?php echo $ajax->url;?>?action=contact">
                        <input type="hidden" name="countrycode" id="countrycode">
                        <input type="hidden" name="page" value="<?php echo $page->url;?>">
                        <!-- Honeypot fields - bots will fill these -->
                        <input type="text" name="website" value="" style="display:none;" tabindex="-1" autocomplete="off">
                        <input type="text" name="company" value="" style="position:absolute;left:-9999px;" tabindex="-1" autocomplete="off">
                        <input type="hidden" name="form_timestamp" id="contact_form_timestamp" value="">
                        <input type="hidden" name="form_token" id="contact_form_token" value="">
                        
                        <div class="group">
                            <label for="name"><?php echo __('Name - Surname');?> <span class="required">*</span></label>
                            <input type="text" id="name" name="name" required minlength="2" maxlength="80" pattern="^[\p{L}\s'.\-]+$">
                            <small class="form-help"><?php echo __('Please enter your real full name');?></small>
                        </div>
                        <div class="group">
                            <label for="phone"><?php echo __('Phone');?></label>
                            <input type="tel" id="phone" name="phone" maxlength="20">
                        </div>
                        <div class="group">
                            <label for="email"><?php echo __('Email');?> <span class="required">*</span></label>
                            <input type="email" id="email" name="email" required placeholder="">
                            <small class="form-help"><?php echo __('A valid email address is required');?></small>
                        </div>
                        <div class="group">
                            <label for="message"><?php echo __('Message');?> <span class="required">*</span></label>
                            <textarea id="message" placeholder="<?php echo __('Please write at least 3 sentences describing your request...');?>" name="message" required minlength="30" maxlength="4000" rows="6"></textarea>
                            <small class="form-help"><?php echo __('Minimum 3 sentences, 30 characters');?></small>
                        </div>
                        <div class="group">
                            <div class="g-recaptcha" data-sitekey="6LcZRT4rAAAAANpfPst5iQls8giUv8-1FdsllfgJ"></div>
                        </div>
                        <button type="button" class="btn button center blur contact-button">
                            <?php echo __('Submit');?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
(function() {
    'use strict';
    
    // Anti-spam configuration
    const ANTISPAM_CONFIG = {
        minSentences: 3,
        minMessageLength: 30,
        maxMessageLength: 4000,
        minNameLength: 2,
        maxNameLength: 80,
        rateLimitMinutes: 5,
        nameCooldownDays: 30,
        
        // SQL injection and RCE patterns (case-insensitive)
        dangerousPatterns: [
            /select\s*\(/i,
            /sleep\s*\(/i,
            /union\s+select/i,
            /or\s+1\s*=\s*1/i,
            /information_schema/i,
            /load_file/i,
            /outfile/i,
            /;\s*--/i,
            /--\s/i,
            /\/\*/i,
            /\*\//i,
            /\$\{/i,
            /<\?php/i,
            /<script/i,
            /javascript:/i,
            /eval\s*\(/i,
            /exec\s*\(/i,
            /['"][\s]*\+[\s]*['"]/i,  // SQL concatenation attempts
            /['"]\s*\|\|\s*['"]/i,
        ],
        
        // Blocked fake/test domains
        blockedDomains: [
            'example.com', 'test.com', 'testing.com', 'fake.com', 'spam.com',
            'tempmail.com', 'guerrillamail.com', 'mailinator.com', '10minutemail.com',
            'trashmail.com', 'yopmail.com'
        ],
        
        // Turkish vowels for name validation
        vowels: 'aeıioöuüAEIİOÖUÜ'
    };
    
    // Utility functions
    const AntiSpam = {
        
        // Generate simple client token
        generateToken: function() {
            return Array.from({length: 32}, () => 
                Math.random().toString(36)[2] || '0'
            ).join('');
        },
        
        // Normalize string for validation
        normalize: function(str) {
            return str.trim().replace(/\s+/g, ' ').normalize('NFC');
        },
        
        // Count sentences in text
        countSentences: function(text) {
            const normalized = this.normalize(text);
            // Split by sentence terminators
            const segments = normalized.split(/[.!?]+/).filter(s => s.trim().length >= 2);
            return segments.length;
        },
        
        // Check if string contains dangerous patterns
        hasDangerousPattern: function(str) {
            return ANTISPAM_CONFIG.dangerousPatterns.some(pattern => pattern.test(str));
        },
        
        // Validate human-like name
        isValidHumanName: function(name) {
            const normalized = this.normalize(name);
            
            // Length check
            if (normalized.length < ANTISPAM_CONFIG.minNameLength || 
                normalized.length > ANTISPAM_CONFIG.maxNameLength) {
                return { valid: false, reason: 'İsim uzunluğu geçersiz (2-80 karakter olmalı)' };
            }
            
            // Only allow letters, spaces, apostrophes, periods, hyphens
            if (!/^[\p{L}\s'.\-]+$/u.test(normalized)) {
                return { valid: false, reason: 'İsimde geçersiz karakterler var (sadece harfler, boşluk, tire ve nokta kullanılabilir)' };
            }
            
            // Check for digits
            if (/\d/.test(normalized)) {
                return { valid: false, reason: 'İsimde rakam bulunamaz' };
            }
            
            // Check for suspicious patterns (all uppercase random letters)
            if (/^[A-Z]{6,}$/.test(normalized.replace(/\s/g, ''))) {
                return { valid: false, reason: 'Lütfen gerçek adınızı kullanın' };
            }
            
            // Check for mixed case gibberish (like JYupWMLW)
            const nameWithoutSpaces = normalized.replace(/[\s'\.\-]/g, '');
            if (nameWithoutSpaces.length >= 6) {
                // Count vowels
                const vowelCount = nameWithoutSpaces.split('').filter(c => 
                    ANTISPAM_CONFIG.vowels.includes(c)
                ).length;
                
                const vowelRatio = vowelCount / nameWithoutSpaces.length;
                
                // If less than 20% vowels, likely gibberish
                if (vowelRatio < 0.2) {
                    return { valid: false, reason: 'Lütfen gerçek adınızı kullanın (geçersiz isim formatı)' };
                }
                
                // Check for excessive consonant runs (4+ consonants in a row)
                if (/[^aeıioöuüAEIİOÖUÜ\s]{5,}/.test(nameWithoutSpaces)) {
                    return { valid: false, reason: 'Lütfen gerçek adınızı kullanın' };
                }
            }
            
            return { valid: true };
        },
        
        // Validate email format and domain
        isValidEmail: function(email) {
            const normalized = email.trim().toLowerCase();
            
            // Basic format check
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalized)) {
                return { valid: false, reason: 'Geçersiz email formatı' };
            }
            
            // Check for dangerous patterns
            if (this.hasDangerousPattern(normalized)) {
                return { valid: false, reason: 'Email adresinde geçersiz karakterler tespit edildi' };
            }
            
            // Extract domain
            const domain = normalized.split('@')[1];
            
            // Check blocked domains
            if (ANTISPAM_CONFIG.blockedDomains.includes(domain)) {
                return { valid: false, reason: 'Bu email domaini kabul edilmemektedir. Lütfen gerçek email adresinizi kullanın' };
            }
            
            // Check for common typos or fake patterns
            if (domain.includes('..') || domain.startsWith('.') || domain.endsWith('.')) {
                return { valid: false, reason: 'Geçersiz email domain' };
            }
            
            return { valid: true };
        },
        
        // Validate message content
        isValidMessage: function(message) {
            const normalized = this.normalize(message);
            
            // Length check
            if (normalized.length < ANTISPAM_CONFIG.minMessageLength) {
                return { 
                    valid: false, 
                    reason: `Mesajınız çok kısa. En az ${ANTISPAM_CONFIG.minMessageLength} karakter ve 3 cümle yazmalısınız` 
                };
            }
            
            if (normalized.length > ANTISPAM_CONFIG.maxMessageLength) {
                return { valid: false, reason: 'Mesaj çok uzun' };
            }
            
            // Check for dangerous patterns
            if (this.hasDangerousPattern(normalized)) {
                return { valid: false, reason: 'Mesajda geçersiz içerik tespit edildi' };
            }
            
            // Count sentences
            const sentenceCount = this.countSentences(normalized);
            if (sentenceCount < ANTISPAM_CONFIG.minSentences) {
                return { 
                    valid: false, 
                    reason: `Lütfen en az ${ANTISPAM_CONFIG.minSentences} cümle yazın. Şu an: ${sentenceCount} cümle` 
                };
            }
            
            // Check if message is just numbers or very repetitive
            if (/^\d+$/.test(normalized)) {
                return { valid: false, reason: 'Mesaj sadece rakamlardan oluşamaz' };
            }
            
            // Check for single repeated character/word
            if (/^(.)\1{20,}$/.test(normalized.replace(/\s/g, ''))) {
                return { valid: false, reason: 'Geçersiz mesaj içeriği' };
            }
            
            return { valid: true };
        },
        
        // Check rate limiting
        checkRateLimit: function() {
            const lastSubmission = localStorage.getItem('lastContactSubmission');
            const now = Date.now();
            const limitMs = ANTISPAM_CONFIG.rateLimitMinutes * 60 * 1000;
            
            if (lastSubmission && (now - parseInt(lastSubmission)) < limitMs) {
                const remainingTime = Math.ceil((limitMs - (now - parseInt(lastSubmission))) / (60 * 1000));
                return { 
                    allowed: false, 
                    reason: `Form gönderimi için ${remainingTime} dakika beklemeniz gerekiyor` 
                };
            }
            
            return { allowed: true };
        },
        
        // Check name cooldown (30 days)
        checkNameCooldown: function(name) {
            const normalized = this.normalize(name).toLowerCase();
            const submittedNames = JSON.parse(localStorage.getItem('contactSubmittedNames') || '{}');
            const cooldownMs = ANTISPAM_CONFIG.nameCooldownDays * 24 * 60 * 60 * 1000;
            const now = Date.now();
            
            if (submittedNames[normalized]) {
                const lastUsed = submittedNames[normalized];
                if (now - lastUsed < cooldownMs) {
                    const daysRemaining = Math.ceil((cooldownMs - (now - lastUsed)) / (24 * 60 * 60 * 1000));
                    return { 
                        allowed: false, 
                        reason: `Bu isimle yakın zamanda mesaj gönderilmiş. Lütfen gerçek adınızı kullanın. (${daysRemaining} gün beklemeniz gerekiyor)` 
                    };
                }
            }
            
            return { allowed: true, name: normalized };
        },
        
        // Save submitted name
        saveSubmittedName: function(name) {
            const normalized = this.normalize(name).toLowerCase();
            const submittedNames = JSON.parse(localStorage.getItem('contactSubmittedNames') || '{}');
            submittedNames[normalized] = Date.now();
            localStorage.setItem('contactSubmittedNames', JSON.stringify(submittedNames));
        },
        
        // Clean old entries from localStorage
        cleanOldEntries: function() {
            try {
                const submittedNames = JSON.parse(localStorage.getItem('contactSubmittedNames') || '{}');
                const cooldownMs = ANTISPAM_CONFIG.nameCooldownDays * 24 * 60 * 60 * 1000;
                const now = Date.now();
                let cleaned = false;
                
                Object.keys(submittedNames).forEach(name => {
                    if (now - submittedNames[name] > cooldownMs) {
                        delete submittedNames[name];
                        cleaned = true;
                    }
                });
                
                if (cleaned) {
                    localStorage.setItem('contactSubmittedNames', JSON.stringify(submittedNames));
                }
            } catch (e) {
                console.warn('Could not clean old entries:', e);
            }
        }
    };
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Set form timestamp
        const timestampField = document.getElementById('contact_form_timestamp');
        if (timestampField) {
            timestampField.value = Math.floor(Date.now() / 1000);
        }
        
        // Set form token
        const tokenField = document.getElementById('contact_form_token');
        if (tokenField) {
            tokenField.value = AntiSpam.generateToken();
        }
        
        // Clean old entries
        AntiSpam.cleanOldEntries();
        
        // Form submit control
        const form = document.querySelector('form[data-url*="action=contact"]');
        const submitBtn = document.querySelector('.contact-button');
        
        if (!form || !submitBtn) {
            console.error('Contact form or submit button not found');
            return;
        }
        
        // Add real-time validation feedback
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const messageInput = document.getElementById('message');
        
        // Name validation on blur
        if (nameInput) {
            nameInput.addEventListener('blur', function() {
                const validation = AntiSpam.isValidHumanName(this.value);
                if (!validation.valid && this.value.trim().length > 0) {
                    this.setCustomValidity(validation.reason);
                    this.reportValidity();
                } else {
                    this.setCustomValidity('');
                }
            });
            
            nameInput.addEventListener('input', function() {
                this.setCustomValidity('');
            });
        }
        
        // Email validation on blur
        if (emailInput) {
            emailInput.addEventListener('blur', function() {
                const validation = AntiSpam.isValidEmail(this.value);
                if (!validation.valid && this.value.trim().length > 0) {
                    this.setCustomValidity(validation.reason);
                    this.reportValidity();
                } else {
                    this.setCustomValidity('');
                }
            });
            
            emailInput.addEventListener('input', function() {
                this.setCustomValidity('');
            });
        }
        
        // Message validation on blur
        if (messageInput) {
            messageInput.addEventListener('blur', function() {
                const validation = AntiSpam.isValidMessage(this.value);
                if (!validation.valid && this.value.trim().length > 0) {
                    this.setCustomValidity(validation.reason);
                    this.reportValidity();
                } else {
                    this.setCustomValidity('');
                }
            });
            
            messageInput.addEventListener('input', function() {
                this.setCustomValidity('');
            });
        }
        
        // Main form submission handler
        submitBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            
            // Get form values
            const name = nameInput ? AntiSpam.normalize(nameInput.value) : '';
            const email = emailInput ? emailInput.value.trim().toLowerCase() : '';
            const message = messageInput ? AntiSpam.normalize(messageInput.value) : '';
            const website = document.querySelector('input[name="website"]')?.value || '';
            const company = document.querySelector('input[name="company"]')?.value || '';
            
            // HONEYPOT CHECK - If these hidden fields are filled, it's a bot
            if (website || company) {
                console.warn('Honeypot triggered - potential bot detected');
                // Silently fail for bots
                alert('Bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
                return;
            }
            
            // Check form timestamp (prevent too-fast submissions - under 3 seconds is suspicious)
            const formTimestamp = parseInt(timestampField?.value || '0');
            const now = Math.floor(Date.now() / 1000);
            if (now - formTimestamp < 3) {
                alert('Lütfen formu doldurmak için biraz daha zaman ayırın.');
                return;
            }
            
            // Validate NAME
            const nameValidation = AntiSpam.isValidHumanName(name);
            if (!nameValidation.valid) {
                alert(nameValidation.reason);
                nameInput?.focus();
                return;
            }
            
            // Check name cooldown
            const nameCooldown = AntiSpam.checkNameCooldown(name);
            if (!nameCooldown.allowed) {
                alert(nameCooldown.reason);
                nameInput?.focus();
                return;
            }
            
            // Validate EMAIL
            const emailValidation = AntiSpam.isValidEmail(email);
            if (!emailValidation.valid) {
                alert(emailValidation.reason);
                emailInput?.focus();
                return;
            }
            
            // Validate MESSAGE
            const messageValidation = AntiSpam.isValidMessage(message);
            if (!messageValidation.valid) {
                alert(messageValidation.reason);
                messageInput?.focus();
                return;
            }
            
            // Check rate limiting
            const rateLimit = AntiSpam.checkRateLimit();
            if (!rateLimit.allowed) {
                alert(rateLimit.reason);
                return;
            }
            
            // Check reCAPTCHA
            const recaptchaResponse = typeof grecaptcha !== 'undefined' ? grecaptcha.getResponse() : '';
            if (!recaptchaResponse) {
                alert('Lütfen reCAPTCHA doğrulamasını tamamlayın.');
                return;
            }
            
            // All validations passed - disable button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.textContent = 'Gönderiliyor...';
            
            try {
                // Prepare form data
                const formData = new FormData(form);
                
                // Add validation flags for server-side verification
                formData.append('client_validated', '1');
                formData.append('g-recaptcha-response', recaptchaResponse);
                
                // Send AJAX request
                const response = await fetch(form.dataset.url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Success - save submission timestamp and name
                    localStorage.setItem('lastContactSubmission', Date.now().toString());
                    AntiSpam.saveSubmittedName(name);
                    
                    alert('Mesajınız başarıyla gönderildi! En kısa sürede size dönüş yapacağız.');
                    
                    // Reset form
                    form.reset();
                    if (typeof grecaptcha !== 'undefined') {
                        grecaptcha.reset();
                    }
                    
                    // Reset timestamp
                    if (timestampField) {
                        timestampField.value = Math.floor(Date.now() / 1000);
                    }
                } else {
                    // Server-side error
                    alert('Bir hata oluştu: ' + (data.message || 'Bilinmeyen hata. Lütfen tekrar deneyin.'));
                }
            } catch (error) {
                console.error('Form submission error:', error);
                alert('Bir bağlantı hatası oluştu. Lütfen internet bağlantınızı kontrol edip tekrar deneyin.');
            } finally {
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.textContent = '<?php echo __('Submit');?>';
            }
        });
    });
})();
</script>

<style>
.form-help {
    display: block;
    margin-top: 4px;
    font-size: 0.875rem;
    color: #6b7280;
}
.required {
    color: #ef4444;
}
input:invalid, textarea:invalid {
    border-color: #ef4444;
}
input:valid, textarea:valid {
    border-color: #10b981;
}
</style>