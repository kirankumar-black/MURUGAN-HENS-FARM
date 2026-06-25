<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../auth/middleware.php';

enable_cors();

$user = getAuthenticatedUser();
$method = $_SERVER['REQUEST_METHOD'];
$controller = new AuthController();

switch ($method) {
    case 'GET':
        $response = $controller->getProfile($user['id']);
        if ($response['status'] === 'success') {
            // Get user notifications
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY id DESC LIMIT 15";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user['id']);
            $stmt->execute();
            $notifications = $stmt->fetchAll();
            
            $response['notifications'] = $notifications;
        }
        echo json_encode($response);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        // Check if just marking notifications as read
        if (isset($input['action']) && $input['action'] === 'read_notifications') {
            $database = new Database();
            $db = $database->getConnection();
            $query = "UPDATE notifications SET is_read = TRUE WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user['id']);
            $stmt->execute();
            echo json_encode(["status" => "success", "message" => "Notifications marked as read."]);
            exit();
        }

        $response = $controller->updateProfile($user['id'], $input);
        echo json_encode($response);
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed."]);
        break;
}
?>
