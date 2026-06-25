<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/ChatbotController.php';

enable_cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed. Use POST."]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$controller = new ChatbotController();
$response = $controller->handleMessage($input);

echo json_encode($response);
?>
