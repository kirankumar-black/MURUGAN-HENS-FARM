<?php
class Wishlist {
    private $conn;
    private $table_name = "wishlist";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function toggle($user_id, $animal_id) {
        if ($this->isInWishlist($user_id, $animal_id)) {
            $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id AND animal_id = :animal_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':animal_id', $animal_id);
            if ($stmt->execute()) {
                return ["status" => "removed"];
            }
        } else {
            $query = "INSERT INTO " . $this->table_name . " (user_id, animal_id) VALUES (:user_id, :animal_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':animal_id', $animal_id);
            if ($stmt->execute()) {
                return ["status" => "added"];
            }
        }
        return ["status" => "error", "message" => "Operation failed"];
    }

    public function getByUser($user_id) {
        $query = "SELECT w.*, a.name, a.breed, a.price, a.location, a.main_image, a.status 
                  FROM " . $this->table_name . " w
                  LEFT JOIN animals a ON w.animal_id = a.id
                  WHERE w.user_id = :user_id 
                  ORDER BY w.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function isInWishlist($user_id, $animal_id) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE user_id = :user_id AND animal_id = :animal_id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':animal_id', $animal_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>
