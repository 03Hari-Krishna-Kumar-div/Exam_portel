<?php
/**
 * Mail Configuration for OTP Email Sending.
 *
 * ===== SETUP INSTRUCTIONS =====
 *
 * Option A: PHP built-in mail() (XAMPP / local)
 *   1. Edit C:\xampp\php\php.ini, set:
 *        SMTP = smtp.gmail.com
 *        smtp_port = 587
 *        sendmail_from = your@gmail.com
 *        sendmail_path = "C:\xampp\sendmail\sendmail.exe -t"
 *
 *   2. Edit C:\xampp\sendmail\sendmail.ini:
 *        smtp_server=smtp.gmail.com
 *        smtp_port=587
 *        auth_username=your@gmail.com
 *        auth_password=your-app-password
 *        force_sender=your@gmail.com
 *
 *   Then set MAIL_DRIVER = 'mail' below.
 *
 * Option B: Direct SMTP (no external dependency)
 *   Set MAIL_DRIVER = 'smtp' and fill SMTP_* settings below.
 *   Uses PHP fsockopen — works on any host without sendmail.
 *
 * ===== GMAIL APP PASSWORD =====
 * For Gmail, you MUST use an App Password (not your regular password):
 *   1. Enable 2-Factor Authentication at https://myaccount.google.com/security
 *   2. Go to https://myaccount.google.com/apppasswords
 *   3. Generate an App Password for "Mail"
 *   4. Use that 16-character password below
 */

// ─── MAIL DRIVER ──────────────────────────────────────────
// Options: 'mail' (PHP mail() + sendmail) or 'smtp' (direct SMTP)
define('MAIL_DRIVER', 'smtp');

// ─── SMTP SETTINGS (used when MAIL_DRIVER = 'smtp') ──────
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);                // 587 for TLS, 465 for SSL
define('SMTP_USERNAME', 'test.dev.hari0003@gmail.com');  // ← FILL THIS
define('SMTP_PASSWORD', 'ukzb epeu crqm ezzq');      // ← FILL THIS — MUST be a 16-char Gmail App Password!
define('SMTP_FROM',     'test.dev.hari0003@gmail.com');   // ← FILL THIS
define('SMTP_FROM_NAME', 'Test Platform');

// ─── PHP MAIL() SETTINGS (used when MAIL_DRIVER = 'mail') ─
define('MAIL_FROM',     'test.dev.hari0003@gmail.com');  // ← FILL THIS
define('MAIL_FROM_NAME','Test Platform');

// ─── DEVELOPMENT SETTINGS ─────────────────────────────────
// When true, OTP is logged to file instead of sent (for testing)
// Set to FALSE once SMTP credentials are working
define('MAIL_DEV_MODE', false);
define('MAIL_DEV_LOG',  __DIR__ . '/../storage/logs/otp.log');
