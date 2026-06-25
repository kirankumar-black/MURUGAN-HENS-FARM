<?php
class Animal {
    private $conn;
    private $table_name = "animals";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($category_id, $seller_id, $name, $breed, $age_months, $weight_kg, $price, $location, $description, $main_image, $images = '[]') {
        $query = "INSERT INTO " . $this->table_name . " 
                  (category_id, seller_id, name, breed, age_months, weight_kg, price, location, description, main_image, images) 
                  VALUES (:category_id, :seller_id, :name, :breed, :age_months, :weight_kg, :price, :location, :description, :main_image, :images)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':seller_id', $seller_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':breed', $breed);
        $stmt->bindParam(':age_months', $age_months);
        $stmt->bindParam(':weight_kg', $weight_kg);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':main_image', $main_image);
        $stmt->bindParam(':images', $images);

        if ($stmt->execute()) {
            return ["status" => true, "id" => $this->conn->lastInsertId()];
        }
        return ["status" => false, "message" => "Failed to list animal."];
    }

    public function getDetails($id) {
        $query = "SELECT a.*, c.name as category_name, u.name as seller_name, u.phone as seller_phone, u.email as seller_email 
                  FROM " . $this->table_name . " a
                  LEFT JOIN categories c ON a.category_id = c.id
                  LEFT JOIN users u ON a.seller_id = u.id
                  WHERE a.id = :id LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function update($id, $category_id, $name, $breed, $age_months, $weight_kg, $price, $location, $description, $main_image = null, $images = null) {
        $query = "UPDATE " . $this->table_name . " 
                  SET category_id = :category_id, name = :name, breed = :breed, 
                      age_months = :age_months, weight_kg = :weight_kg, price = :price, 
                      location = :location, description = :description";
        
        if ($main_image) {
            $query .= ", main_image = :main_image";
        }
        if ($images !== null) {
            $query .= ", images = :images";
        }
        
        $query .= " WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':breed', $breed);
        $stmt->bindParam(':age_months', $age_months);
        $stmt->bindParam(':weight_kg', $weight_kg);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':id', $id);

        if ($main_image) {
            $stmt->bindParam(':main_image', $main_image);
        }
        if ($images !== null) {
            $stmt->bindParam(':images', $images);
        }

        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getAll($filters = []) {
        $query = "SELECT a.*, c.name as category_name, c.slug as category_slug 
                  FROM " . $this->table_name . " a
                  LEFT JOIN categories c ON a.category_id = c.id
                  WHERE a.status = 'available'";

        $params = [];

        if (!empty($filters['category'])) {
            $query .= " AND (c.slug = :category OR c.id = :category_id)";
            $params[':category'] = $filters['category'];
            $params[':category_id'] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $query .= " AND (a.name LIKE :search OR a.breed LIKE :search OR a.description LIKE :search OR a.location LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (isset($filters['price_min'])) {
            $query .= " AND a.price >= :price_min";
            $params[':price_min'] = $filters['price_min'];
        }

        if (isset($filters['price_max'])) {
            $query .= " AND a.price <= :price_max";
            $params[':price_max'] = $filters['price_max'];
        }

        if (!empty($filters['location'])) {
            $query .= " AND a.location LIKE :location";
            $params[':location'] = '%' . $filters['location'] . '%';
        }

        if (!empty($filters['breed'])) {
            $query .= " AND a.breed LIKE :breed";
            $params[':breed'] = '%' . $filters['breed'] . '%';
        }

        if (isset($filters['age_max'])) {
            $query .= " AND a.age_months <= :age_max";
            $params[':age_max'] = $filters['age_max'];
        }

        $query .= " ORDER BY a.id DESC";

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getFeatured() {
        $query = "SELECT a.*, c.name as category_name 
                  FROM " . $this->table_name . " a
                  LEFT JOIN categories c ON a.category_id = c.id
                  WHERE a.status = 'available' 
                  ORDER BY a.rating DESC, a.id DESC LIMIT 6";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getBySeller($seller_id) {
        $query = "SELECT a.*, c.name as category_name 
                  FROM " . $this->table_name . " a
                  LEFT JOIN categories c ON a.category_id = c.id
                  WHERE a.seller_id = :seller_id 
                  ORDER BY a.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':seller_id', $seller_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function updateRating($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET rating = (SELECT IFNULL(AVG(rating), 0) FROM reviews WHERE animal_id = :animal_id AND status = 'approved') 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':animal_id', $id);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getBreedSuggestions($category_id) {
        $query = "SELECT DISTINCT breed FROM " . $this->table_name . " WHERE category_id = :category_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>
