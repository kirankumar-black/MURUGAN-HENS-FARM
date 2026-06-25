<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Animal.php';
require_once __DIR__ . '/../auth/middleware.php';

class AnimalController {
    private $db;
    private $animal;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->animal = new Animal($this->db);
    }

    public function list($filters) {
        $filters = sanitizeInput($filters);
        $list = $this->animal->getAll($filters);
        return ["status" => "success", "animals" => $list];
    }

    public function details($id) {
        $id = (int)$id;
        $details = $this->animal->getDetails($id);
        if ($details) {
            // Retrieve reviews
            require_once __DIR__ . '/../models/Review.php';
            $reviewModel = new Review($this->db);
            $reviews = $reviewModel->getByAnimal($id);
            $details['reviews'] = $reviews;
            
            // Decode images JSON
            $details['images'] = json_decode($details['images'] ?? '[]', true);
            
            return ["status" => "success", "animal" => $details];
        } else {
            http_response_code(404);
            return ["status" => "error", "message" => "Animal listing not found."];
        }
    }

    public function getFeatured() {
        $list = $this->animal->getFeatured();
        return ["status" => "success", "animals" => $list];
    }

    public function getSellerListings($sellerId) {
        $list = $this->animal->getBySeller($sellerId);
        return ["status" => "success", "animals" => $list];
    }

    public function createListing($sellerId, $data, $files) {
        $data = sanitizeInput($data);

        if (empty($data['name']) || empty($data['category_id']) || empty($data['breed']) || 
            empty($data['price']) || empty($data['location']) || empty($data['age_months']) || empty($data['weight_kg'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Please fill in all required fields."];
        }

        // Handle Image Upload
        $mainImage = 'default_animal.jpg';
        if (isset($files['main_image']) && $files['main_image']['error'] == UPLOAD_ERR_OK) {
            $uploaded = $this->uploadImage($files['main_image']);
            if ($uploaded['status']) {
                $mainImage = $uploaded['filename'];
            } else {
                http_response_code(400);
                return ["status" => "error", "message" => $uploaded['message']];
            }
        } else {
            http_response_code(400);
            return ["status" => "error", "message" => "Main image is required."];
        }

        // Handle additional images (optional)
        $additionalImages = [];
        if (isset($files['images'])) {
            // Can be multiple files
            $filesArr = $this->reArrayFiles($files['images']);
            foreach ($filesArr as $file) {
                if ($file['error'] == UPLOAD_ERR_OK) {
                    $uploaded = $this->uploadImage($file);
                    if ($uploaded['status']) {
                        $additionalImages[] = $uploaded['filename'];
                    }
                }
            }
        }
        $imagesJson = json_encode($additionalImages);

        $result = $this->animal->create(
            (int)$data['category_id'],
            $sellerId,
            $data['name'],
            $data['breed'],
            (int)$data['age_months'],
            (float)$data['weight_kg'],
            (float)$data['price'],
            $data['location'],
            $data['description'] ?? '',
            $mainImage,
            $imagesJson
        );

        if ($result['status']) {
            return ["status" => "success", "message" => "Animal listed successfully.", "id" => $result['id']];
        } else {
            http_response_code(500);
            return ["status" => "error", "message" => "Failed to create listing."];
        }
    }

    public function updateListing($sellerId, $id, $data, $files) {
        $id = (int)$id;
        $data = sanitizeInput($data);

        // Check ownership
        $existing = $this->animal->getDetails($id);
        if (!$existing || $existing['seller_id'] != $sellerId) {
            http_response_code(403);
            return ["status" => "error", "message" => "You are not authorized to update this listing."];
        }

        if (empty($data['name']) || empty($data['category_id']) || empty($data['breed']) || 
            empty($data['price']) || empty($data['location']) || empty($data['age_months']) || empty($data['weight_kg'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Please fill in all required fields."];
        }

        // Handle Main Image Upload (if updated)
        $mainImage = null;
        if (isset($files['main_image']) && $files['main_image']['error'] == UPLOAD_ERR_OK) {
            $uploaded = $this->uploadImage($files['main_image']);
            if ($uploaded['status']) {
                $mainImage = $uploaded['filename'];
            } else {
                http_response_code(400);
                return ["status" => "error", "message" => $uploaded['message']];
            }
        }

        // Handle Additional Images (if updated)
        $imagesJson = null;
        if (isset($files['images'])) {
            $additionalImages = [];
            $filesArr = $this->reArrayFiles($files['images']);
            foreach ($filesArr as $file) {
                if ($file['error'] == UPLOAD_ERR_OK) {
                    $uploaded = $this->uploadImage($file);
                    if ($uploaded['status']) {
                        $additionalImages[] = $uploaded['filename'];
                    }
                }
            }
            if (!empty($additionalImages)) {
                $imagesJson = json_encode($additionalImages);
            }
        }

        $success = $this->animal->update(
            $id,
            (int)$data['category_id'],
            $data['name'],
            $data['breed'],
            (int)$data['age_months'],
            (float)$data['weight_kg'],
            (float)$data['price'],
            $data['location'],
            $data['description'] ?? '',
            $mainImage,
            $imagesJson
        );

        if ($success) {
            return ["status" => "success", "message" => "Listing updated successfully."];
        } else {
            http_response_code(500);
            return ["status" => "error", "message" => "Failed to update listing."];
        }
    }

    public function deleteListing($sellerId, $id) {
        $id = (int)$id;
        $existing = $this->animal->getDetails($id);
        if (!$existing || $existing['seller_id'] != $sellerId) {
            http_response_code(403);
            return ["status" => "error", "message" => "You are not authorized to delete this listing."];
        }

        $success = $this->animal->delete($id);
        if ($success) {
            return ["status" => "success", "message" => "Listing deleted successfully."];
        } else {
            http_response_code(500);
            return ["status" => "error", "message" => "Failed to delete listing."];
        }
    }

    // Helper: Handle file upload securely
    private function uploadImage($file) {
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
            return ["status" => false, "message" => "Invalid file extension. Allowed: " . implode(', ', ALLOWED_EXTENSIONS)];
        }

        if ($file['size'] > MAX_FILE_SIZE) {
            return ["status" => false, "message" => "File is too large. Max size: 5MB."];
        }

        $newFileName = uniqid('animal_', true) . '.' . $fileExtension;
        $targetPath = UPLOAD_DIR . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ["status" => true, "filename" => $newFileName];
        }

        return ["status" => false, "message" => "Failed to move uploaded file."];
    }

    // Helper: Reorganize files array for multiple uploads
    private function reArrayFiles(&$file_post) {
        $file_ary = [];
        $file_count = count($file_post['name']);
        $file_keys = array_keys($file_post);

        for ($i=0; $i<$file_count; $i++) {
            foreach ($file_keys as $key) {
                $file_ary[$i][$key] = $file_post[$key][$i];
            }
        }

        return $file_ary;
    }
}
?>
