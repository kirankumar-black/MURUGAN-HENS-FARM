<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/OrderController.php';
require_once __DIR__ . '/../auth/middleware.php';

enable_cors();

$user = getAuthenticatedUser();
$method = $_SERVER['REQUEST_METHOD'];
$controller = new OrderController();

switch ($method) {
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $response = $controller->checkout($user['id'], $input);
        echo json_encode($response);
        break;

    case 'GET':
        if (isset($_GET['id'])) {
            $response = $controller->getOrderDetails($user['id'], (int)$_GET['id'], ($user['role'] === 'admin'));
        } else {
            $response = $controller->listBuyerOrders($user['id']);
        }
        echo json_encode($response);
        break;

    case 'DELETE':
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Order ID is required for cancellation."]);
            exit();
        }
        $response = $controller->cancel($user['id'], (int)$_GET['id']);
        echo json_encode($response);
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed."]);
        break;
}
?>
