<?php
class Category {
    private $conn;
    private $table_name = "categories";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT c.*, COUNT(a.id) as animal_count 
                  FROM " . $this->table_name . " c
                  LEFT JOIN animals a ON c.id = a.category_id AND a.status = 'available'
                  GROUP BY c.id
                  ORDER BY c.name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create($name, $slug, $image) {
        $query = "INSERT INTO " . $this->table_name . " (name, slug, image) VALUES (:name, :slug, :image)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':slug', $slug);
        $stmt->bindParam(':image', $image);
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>
