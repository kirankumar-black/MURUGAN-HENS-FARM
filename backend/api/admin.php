<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AdminController.php';
require_once __DIR__ . '/../auth/middleware.php';

enable_cors();

// Verify user is authenticated and has admin privileges
$user = getAuthenticatedUser();
if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Forbidden. Administrator privileges required."]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$controller = new AdminController();

switch ($method) {
    case 'GET':
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'stats':
                $response = $controller->getStats();
                break;
            case 'charts':
                $response = $controller->getCharts();
                break;
            case 'users':
                $response = $controller->getUsers();
                break;
            case 'orders':
                $response = $controller->getOrders();
                break;
            case 'reviews':
                $response = $controller->getReviews();
                break;
            case 'animals':
                $response = $controller->getAnimals();
                break;
            default:
                http_response_code(400);
                $response = ["status" => "error", "message" => "Invalid admin action."];
                break;
        }
        echo json_encode($response);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = $input['action'] ?? '';

        switch ($action) {
            case 'update_user_status':
                $response = $controller->updateUserStatus($input);
                break;
            case 'update_order_status':
                $response = $controller->updateOrderStatus($input);
                break;
            case 'update_review_status':
                $response = $controller->updateReviewStatus($input);
                break;
            case 'delete_review':
                if (empty($input['review_id'])) {
                    http_response_code(400);
                    $response = ["status" => "error", "message" => "Review ID is required."];
                } else {
                    $response = $controller->deleteReview($input['review_id']);
                }
                break;
            case 'delete_animal':
                if (empty($input['animal_id'])) {
                    http_response_code(400);
                    $response = ["status" => "error", "message" => "Animal ID is required."];
                } else {
                    $response = $controller->deleteAnimal($input['animal_id']);
                }
                break;
            default:
                http_response_code(400);
                $response = ["status" => "error", "message" => "Invalid admin post action."];
                break;
        }
        echo json_encode($response);
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed."]);
        break;
}
?>
