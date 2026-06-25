<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/AdminController.php';

enable_cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed. Use POST."]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if (isset($input['username'])) {
    // Admin login
    $controller = new AdminController();
    $response = $controller->login($input);
} else {
    // User login
    $controller = new AuthController();
    $response = $controller->login($input);
}

echo json_encode($response);
?>
