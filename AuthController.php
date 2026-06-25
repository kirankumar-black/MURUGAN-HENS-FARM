<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../auth/middleware.php';

class AuthController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    public function register($data) {
        $data = sanitizeInput($data);

        if (empty($data['name']) || empty($data['email']) || empty($data['password']) || empty($data['phone'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Please provide name, email, password, and phone number."];
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            return ["status" => "error", "message" => "Invalid email address."];
        }

        if (strlen($data['password']) < 6) {
            http_response_code(400);
            return ["status" => "error", "message" => "Password must be at least 6 characters."];
        }

        $address = $data['address'] ?? '';
        $role = $data['role'] ?? 'buyer';

        $result = $this->user->register($data['name'], $data['email'], $data['password'], $data['phone'], $address, $role);
        
        if ($result['status']) {
            http_response_code(201);
            return ["status" => "success", "message" => "Registration successful. Please login."];
        } else {
            http_response_code(400);
            return ["status" => "error", "message" => $result['message']];
        }
    }

    public function login($data) {
        $data = sanitizeInput($data);

        if (empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Please provide email and password."];
        }

        $result = $this->user->login($data['email'], $data['password']);

        if ($result['status']) {
            $user = $result['user'];
            
            // Generate JWT Token
            $tokenPayload = [
                "id" => $user['id'],
                "name" => $user['name'],
                "email" => $user['email'],
                "role" => $user['role']
            ];
            $jwt = generateJWT($tokenPayload);

            http_response_code(200);
            return [
                "status" => "success",
                "message" => "Login successful.",
                "token" => $jwt,
                "user" => [
                    "id" => $user['id'],
                    "name" => $user['name'],
                    "email" => $user['email'],
                    "phone" => $user['phone'],
                    "address" => $user['address'],
                    "role" => $user['role']
                ]
            ];
        } else {
            http_response_code(400);
            return ["status" => "error", "message" => $result['message']];
        }
    }

    public function getProfile($userId) {
        $profile = $this->user->getUserById($userId);
        if ($profile) {
            return ["status" => "success", "profile" => $profile];
        } else {
            http_response_code(404);
            return ["status" => "error", "message" => "User not found."];
        }
    }

    public function updateProfile($userId, $data) {
        $data = sanitizeInput($data);

        if (empty($data['name']) || empty($data['phone'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Name and phone number are required."];
        }

        $password = !empty($data['password']) ? $data['password'] : null;
        $address = $data['address'] ?? '';

        if ($password && strlen($password) < 6) {
            http_response_code(400);
            return ["status" => "error", "message" => "New password must be at least 6 characters."];
        }

        $success = $this->user->updateProfile($userId, $data['name'], $data['phone'], $address, $password);

        if ($success) {
            $updatedUser = $this->user->getUserById($userId);
            return [
                "status" => "success", 
                "message" => "Profile updated successfully.",
                "user" => $updatedUser
            ];
        } else {
            http_response_code(500);
            return ["status" => "error", "message" => "Failed to update profile."];
        }
    }
}
?>
