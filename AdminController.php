<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Review.php';
require_once __DIR__ . '/../models/Animal.php';
require_once __DIR__ . '/../auth/middleware.php';

class AdminController {
    private $db;
    private $admin;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->admin = new Admin($this->db);
    }

    public function login($data) {
        $data = sanitizeInput($data);

        if (empty($data['username']) || empty($data['password'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Please provide username and password."];
        }

        $result = $this->admin->login($data['username'], $data['password']);

        if ($result['status']) {
            $adminUser = $result['admin'];
            
            // Generate JWT Token
            $tokenPayload = [
                "id" => $adminUser['id'],
                "username" => $adminUser['username'],
                "email" => $adminUser['email'],
                "role" => "admin"
            ];
            $jwt = generateJWT($tokenPayload);

            return [
                "status" => "success",
                "message" => "Admin login successful.",
                "token" => $jwt,
                "admin" => $adminUser
            ];
        } else {
            http_response_code(400);
            return ["status" => "error", "message" => $result['message']];
        }
    }

    public function getStats() {
        $stats = $this->admin->getDashboardStats();
        return ["status" => "success", "stats" => $stats];
    }

    public function getCharts() {
        $charts = $this->admin->getSalesChartsData();
        return ["status" => "success", "charts" => $charts];
    }

    // User management
    public function getUsers() {
        $userModel = new User($this->db);
        $users = $userModel->getAllUsers();
        return ["status" => "success", "users" => $users];
    }

    public function updateUserStatus($data) {
        $data = sanitizeInput($data);
        if (empty($data['user_id']) || empty($data['status'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "User ID and status are required."];
        }

        $userModel = new User($this->db);
        $success = $userModel->updateStatus((int)$data['user_id'], $data['status']);
        
        if ($success) {
            return ["status" => "success", "message" => "User status updated successfully."];
        } else {
            http_response_code(500);
            return ["status" => "error", "message" => "Failed to update user status."];
        }
    }

    // Order management
    public function getOrders() {
        $orderModel = new Order($this->db);
        $orders = $orderModel->getAllOrders();
        return ["status" => "success", "orders" => $orders];
    }

    public function updateOrderStatus($data) {
        $data = sanitizeInput($data);
        if (empty($data['order_id']) || empty($data['status'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Order ID and status are required."];
        }

        $orderModel = new Order($this->db);
        $tracking = $data['tracking_number'] ?? null;
        $success = $orderModel->updateStatus((int)$data['order_id'], $data['status'], $tracking);

        if ($success) {
            return ["status" => "success", "message" => "Order status updated successfully."];
        } else {
            http_response_code(500);
            return ["status" => "error", "message" => "Failed to update order status."];
        }
    }

    // Review management
    public function getReviews() {
        $reviewModel = new Review($this->db);
        $reviews = $reviewModel->getAllReviews();
        return ["status" => "success", "reviews" => $reviews];
    }

    public function updateReviewStatus($data) {
        $data = sanitizeInput($data);
        if (empty($data['review_id']) || empty($data['status'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Review ID and status are required."];
        }

        $reviewModel = new Review($this->db);
        $success = $reviewModel->updateStatus((int)$data['review_id'], $data['status']);

        if ($success) {
            return ["status" => "success", "message" => "Review status updated successfully."];
        } else {
            http_response_code(500);
            return ["status" => "error", "message" => "Failed to update review status."];
        }
    }

    public function deleteReview($reviewId) {
        $reviewModel = new Review($this->db);
        $success = $reviewModel->delete((int)$reviewId);
        if ($success) {
            return ["status" => "success", "message" => "Review deleted successfully."];
        } else {
            http_response_code(500);
            return ["status" => "error", "message" => "Failed to delete review."];
        }
    }

    // Animal management
    public function getAnimals() {
        $animalModel = new Animal($this->db);
        $animals = $animalModel->getAll();
        return ["status" => "success", "animals" => $animals];
    }

    public function deleteAnimal($animalId) {
        $animalModel = new Animal($this->db);
        $success = $animalModel->delete((int)$animalId);
        if ($success) {
            return ["status" => "success", "message" => "Animal listing deleted successfully."];
        } else {
            http_response_code(500);
            return ["status" => "error", "message" => "Failed to delete animal listing."];
        }
    }
}
?>
