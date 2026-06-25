<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Category.php';

enable_cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed. Use GET."]);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$categoryModel = new Category($db);
$categories = $categoryModel->getAll();

echo json_encode(["status" => "success", "categories" => $categories]);
?>
