<?php
require_once __DIR__ . '/../config/config.php';

// Base64 Url Encoding helpers
function base64UrlEncode($text) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
}

function base64UrlDecode($text) {
    $base64 = str_replace(['-', '_'], ['+', '/'], $text);
    // Pad base64 if needed
    $len = strlen($base64) % 4;
    if ($len) {
        $base64 .= str_repeat('=', 4 - $len);
    }
    return base64_decode($base64);
}

// Generate JWT token
function generateJWT($payload) {
    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
    $payload['iat'] = time();
    $payload['exp'] = time() + JWT_EXPIRY;
    
    $base64UrlHeader = base64UrlEncode($header);
    $base64UrlPayload = base64UrlEncode(json_encode($payload));
    
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = base64UrlEncode($signature);
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

// Verify JWT token
function verifyJWT($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    list($headerB64, $payloadB64, $signatureB64) = $parts;
    
    $signature = base64UrlDecode($signatureB64);
    $expectedSignature = hash_hmac('sha256', $headerB64 . "." . $payloadB64, JWT_SECRET, true);
    
    if (!hash_equals($signature, $expectedSignature)) {
        return false;
    }
    
    $payload = json_decode(base64UrlDecode($payloadB64), true);
    
    // Check expiry
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}

// Check authorization header and return token payload or error out
function getAuthenticatedUser() {
    $headers = getallheaders();
    $authHeader = null;
    
    foreach ($headers as $key => $val) {
        if (strcasecmp($key, 'Authorization') === 0) {
            $authHeader = $val;
            break;
        }
    }
    
    if (!$authHeader && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    }
    
    if (!$authHeader) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Access denied. Token missing."]);
        exit();
    }
    
    list($jwt) = sscanf($authHeader, 'Bearer %s');
    if (!$jwt) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Access denied. Invalid token format."]);
        exit();
    }
    
    $userData = verifyJWT($jwt);
    if (!$userData) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Access denied. Token invalid or expired."]);
        exit();
    }
    
    return $userData;
}

// Sanitize user inputs to protect against XSS
function sanitizeInput($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeInput($value);
        }
    } else {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

// Simple CSRF Protection (for standard form submits or session usage)
function generateCSRFToken() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
