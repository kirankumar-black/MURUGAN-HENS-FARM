<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Wishlist.php';
require_once __DIR__ . '/../auth/middleware.php';

enable_cors();

$user = getAuthenticatedUser();
$method = $_SERVER['REQUEST_METHOD'];

$database = new Database();
$db = $database->getConnection();
$wishlistModel = new Wishlist($db);

switch ($method) {
    case 'GET':
        $items = $wishlistModel->getByUser($user['id']);
        echo json_encode(["status" => "success", "wishlist" => $items]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (empty($input['animal_id'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Animal ID is required."]);
            exit();
        }
        $response = $wishlistModel->toggle($user['id'], (int)$input['animal_id']);
        echo json_encode($response);
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed."]);
        break;
}
?>
