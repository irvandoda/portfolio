<?php
/**
 * Example wp-config.php snippet for SuperAdmin Secure
 * 
 * Add this line to your wp-config.php file (before "That's all, stop editing!"):
 * 
 * define('SASEC_SECRET_KEY', 'your-secret-key-here-minimum-32-characters-long');
 * 
 * Generate a secure key using:
 * 
 * openssl rand -hex 32
 * 
 * Or in PHP:
 * 
 * <?php
 * echo bin2hex(random_bytes(32));
 * ?>
 */

// Example (DO NOT USE THIS KEY - GENERATE YOUR OWN):
// define('SASEC_SECRET_KEY', 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6');

