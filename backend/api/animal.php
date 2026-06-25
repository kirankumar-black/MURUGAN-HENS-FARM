<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AnimalController.php';

enable_cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed. Use GET."]);
    exit();
}

if (empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Animal ID is required."]);
    exit();
}

$controller = new AnimalController();
$response = $controller->details((int)$_GET['id']);

echo json_encode($response);
?>
