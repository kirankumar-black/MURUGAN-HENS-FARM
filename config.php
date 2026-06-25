<?php
// Global Config File

// Error reporting settings
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default timezone
date_default_timezone_set('Asia/Kolkata');

// Define constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'animal_mart');

// JWT configuration
define('JWT_SECRET', 'animal_mart_super_secure_secret_key_123456!');
define('JWT_EXPIRY', 86400); // 1 day in seconds

// File Upload Settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// OpenAI API settings
define('OPENAI_API_KEY', ''); // Set your API key here for chatbot integration
define('OPENAI_MODEL', 'gpt-3.5-turbo');

// Enable Cors Helper
function enable_cors() {
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}
?>
