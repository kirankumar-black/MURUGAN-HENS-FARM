<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Animal.php';
require_once __DIR__ . '/../auth/middleware.php';

class CartController {
    private $db;
    private $animal;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->animal = new Animal($this->db);
    }

    public function getCartItems($data) {
        $data = sanitizeInput($data);
        $ids = $data['ids'] ?? [];

        if (empty($ids) || !is_array($ids)) {
            return ["status" => "success", "items" => []];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "SELECT a.*, c.name as category_name 
                  FROM animals a
                  LEFT JOIN categories c ON a.category_id = c.id
                  WHERE a.id IN ($placeholders)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute(array_map('intval', $ids));
        $items = $stmt->fetchAll();

        return ["status" => "success", "items" => $items];
    }
}
?>
