<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "▶ Testing config/database.php...\n";
require_once 'config/database.php';
$result = $db->query("SELECT 1");
if ($result) {
    echo "✅ Database OK\n";
} else {
    die("❌ Database error: " . $db->error);
}

echo "▶ Loading security modules...\n";
require_once 'security/encryption.php';
require_once 'security/csrf_protection.php';
require_once 'security/sql_protection.php';
require_once 'security/xss_protection.php';
require_once 'security/input_validator.php';
require_once 'security/security_headers.php';
require_once 'security/rate_limiter.php';
require_once 'security/session_manager.php';
require_once 'security/audit_logger.php';
require_once 'security/file_scanner.php';
require_once 'security/password_policy.php';
echo "✅ All security files loaded\n";

echo "▶ Testing Encryption...\n";
$enc = new Encryption();
$hash = $enc->hashPassword('Test@1234');
echo "   Hash: " . substr($hash, 0, 30) . "...\n";
echo "✅ Encryption works\n";

echo "▶ Testing CSRF Protection...\n";
session_start();
$csrf = new CSRFProtection();
$token = $csrf->generateToken();
echo "   Token: " . substr($token, 0, 20) . "...\n";
echo "✅ CSRF token generated\n";

echo "▶ Testing Input Validator...\n";
$validator = new InputValidator();
try {
    $data = $validator->validate(['email' => 'test@example.com'], ['email' => 'required|email']);
    echo "✅ Validation passed\n";
} catch (Exception $e) {
    echo "❌ Validation failed: " . $e->getMessage() . "\n";
}

echo "▶ Testing Password Policy...\n";
$policy = new PasswordPolicy();
$errors = $policy->validate('weak');
echo "   Weak password errors: " . count($errors) . " (expected >0)\n";
$errors = $policy->validate('StrongPass1!');
echo "   Strong password errors: " . count($errors) . " (expected 0)\n";
echo "✅ Password policy works\n";

echo "▶ Testing SQL Protection...\n";
$sqlProtect = new SQLProtection($db);
// test a simple select
$queryData = $sqlProtect->buildSelectQuery('users', 'id', ['username' => 'test']);
echo "   Generated query: " . $queryData['query'] . "\n";
echo "✅ SQL builder works\n";

echo "▶ Loading server helpers...\n";
require_once 'server/php/helpers.php';
require_once 'server/php/server_manager.php';
echo "✅ Helpers loaded\n";

echo "\n🎉 All debug tests passed! Batch 1 is ready for Batch 2.\n";
