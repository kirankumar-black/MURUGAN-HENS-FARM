<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AnimalController.php';
require_once __DIR__ . '/../auth/middleware.php';

enable_cors();

$method = $_SERVER['REQUEST_METHOD'];
$controller = new AnimalController();

switch ($method) {
    case 'GET':
        // Check if fetching seller listings or featured listings
        if (isset($_GET['seller']) && $_GET['seller'] === 'true') {
            $user = getAuthenticatedUser();
            $response = $controller->getSellerListings($user['id']);
        } elseif (isset($_GET['featured']) && $_GET['featured'] === 'true') {
            $response = $controller->getFeatured();
        } else {
            $response = $controller->list($_GET);
        }
        echo json_encode($response);
        break;

    case 'POST':
        // Requires authentication
        $user = getAuthenticatedUser();
        
        // Handle normal post listing OR update listing (multipart/form-data workaround for PUT in PHP)
        if (isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
            if (empty($_POST['id'])) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Listing ID is required for update."]);
                exit();
            }
            $id = $_POST['id'];
            $response = $controller->updateListing($user['id'], $id, $_POST, $_FILES);
        } else {
            $response = $controller->createListing($user['id'], $_POST, $_FILES);
        }
        echo json_encode($response);
        break;

    case 'DELETE':
        $user = getAuthenticatedUser();
        // Since DELETE payload is usually in URL
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Listing ID is required."]);
            exit();
        }
        $response = $controller->deleteListing($user['id'], (int)$_GET['id']);
        echo json_encode($response);
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed."]);
        break;
}
?>
