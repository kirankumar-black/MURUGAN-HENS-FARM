<?php
class Review {
    private $conn;
    private $table_name = "reviews";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($animal_id, $user_id, $rating, $comment) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (animal_id, user_id, rating, comment, status) 
                  VALUES (:animal_id, :user_id, :rating, :comment, 'approved')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':animal_id', $animal_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':comment', $comment);

        if ($stmt->execute()) {
            // Recalculate animal average rating
            $animalQuery = "UPDATE animals a 
                            SET rating = (SELECT AVG(rating) FROM reviews WHERE animal_id = :animal_id AND status = 'approved') 
                            WHERE id = :id";
            $animalStmt = $this->conn->prepare($animalQuery);
            $animalStmt->bindParam(':animal_id', $animal_id);
            $animalStmt->bindParam(':id', $animal_id);
            $animalStmt->execute();
            
            return true;
        }
        return false;
    }

    public function getByAnimal($animal_id) {
        $query = "SELECT r.*, u.name as reviewer_name 
                  FROM " . $this->table_name . " r
                  LEFT JOIN users u ON r.user_id = u.id
                  WHERE r.animal_id = :animal_id AND r.status = 'approved'
                  ORDER BY r.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':animal_id', $animal_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAllReviews() {
        $query = "SELECT r.*, u.name as reviewer_name, a.name as animal_name 
                  FROM " . $this->table_name . " r
                  LEFT JOIN users u ON r.user_id = u.id
                  LEFT JOIN animals a ON r.animal_id = a.id
                  ORDER BY r.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            // Update animal ratings in case it changes
            $reviewQuery = "SELECT animal_id FROM " . $this->table_name . " WHERE id = :id";
            $reviewStmt = $this->conn->prepare($reviewQuery);
            $reviewStmt->bindParam(':id', $id);
            $reviewStmt->execute();
            $review = $reviewStmt->fetch();
            if ($review) {
                $animalQuery = "UPDATE animals a 
                                SET rating = (SELECT IFNULL(AVG(rating), 0) FROM reviews WHERE animal_id = :animal_id AND status = 'approved') 
                                WHERE id = :id";
                $animalStmt = $this->conn->prepare($animalQuery);
                $animalStmt->bindParam(':animal_id', $review['animal_id']);
                $animalStmt->bindParam(':id', $review['animal_id']);
                $animalStmt->execute();
            }
            return true;
        }
        return false;
    }

    public function delete($id) {
        // Fetch animal_id before delete
        $reviewQuery = "SELECT animal_id FROM " . $this->table_name . " WHERE id = :id";
        $reviewStmt = $this->conn->prepare($reviewQuery);
        $reviewStmt->bindParam(':id', $id);
        $reviewStmt->execute();
        $review = $reviewStmt->fetch();

        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            if ($review) {
                $animalQuery = "UPDATE animals a 
                                SET rating = (SELECT IFNULL(AVG(rating), 0) FROM reviews WHERE animal_id = :animal_id AND status = 'approved') 
                                WHERE id = :id";
                $animalStmt = $this->conn->prepare($animalQuery);
                $animalStmt->bindParam(':animal_id', $review['animal_id']);
                $animalStmt->bindParam(':id', $review['animal_id']);
                $animalStmt->execute();
            }
            return true;
        }
        return false;
    }
}
?>
