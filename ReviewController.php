<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Review.php';
require_once __DIR__ . '/../auth/middleware.php';

class ReviewController {
    private $db;
    private $review;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->review = new Review($this->db);
    }

    public function addReview($userId, $data) {
        $data = sanitizeInput($data);

        if (empty($data['animal_id']) || empty($data['rating']) || (int)$data['rating'] < 1 || (int)$data['rating'] > 5) {
            http_response_code(400);
            return ["status" => "error", "message" => "Valid animal ID and rating (1-5) are required."];
        }

        $animalId = (int)$data['animal_id'];
        $rating = (int)$data['rating'];
        $comment = $data['comment'] ?? '';

        // Check if user bought the animal (optional but professional - let's allow anyone who has purchased, or simple public reviews with a check. Let's make it simple public review capability for listed items).
        $success = $this->review->create($animalId, $userId, $rating, $comment);

        if ($success) {
            return ["status" => "success", "message" => "Review submitted successfully."];
        } else {
            http_response_code(500);
            return ["status" => "error", "message" => "Failed to submit review."];
        }
    }
}
?>
