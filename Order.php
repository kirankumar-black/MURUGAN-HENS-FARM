<?php
class Order {
    private $conn;
    private $table_name = "orders";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($buyer_id, $total_amount, $payment_method, $shipping_address, $animal_ids) {
        try {
            $this->conn->beginTransaction();

            // 1. Create order
            $query = "INSERT INTO " . $this->table_name . " 
                      (buyer_id, total_amount, payment_method, shipping_address, status, payment_status) 
                      VALUES (:buyer_id, :total_amount, :payment_method, :shipping_address, 'pending', 'unpaid')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':buyer_id', $buyer_id);
            $stmt->bindParam(':total_amount', $total_amount);
            $stmt->bindParam(':payment_method', $payment_method);
            $stmt->bindParam(':shipping_address', $shipping_address);
            $stmt->execute();
            
            $order_id = $this->conn->lastInsertId();

            // 2. Add order items & update animal status to 'pending' (or sold)
            foreach ($animal_ids as $animal_id) {
                // Get animal price
                $priceQuery = "SELECT price FROM animals WHERE id = :animal_id";
                $priceStmt = $this->conn->prepare($priceQuery);
                $priceStmt->bindParam(':animal_id', $animal_id);
                $priceStmt->execute();
                $animal = $priceStmt->fetch();
                
                if (!$animal) {
                    throw new Exception("Animal not found: " . $animal_id);
                }
                
                $price = $animal['price'];

                // Insert into order_items
                $itemQuery = "INSERT INTO order_items (order_id, animal_id, price) VALUES (:order_id, :animal_id, :price)";
                $itemStmt = $this->conn->prepare($itemQuery);
                $itemStmt->bindParam(':order_id', $order_id);
                $itemStmt->bindParam(':animal_id', $animal_id);
                $itemStmt->bindParam(':price', $price);
                $itemStmt->execute();

                // Update animal status
                $updateQuery = "UPDATE animals SET status = 'pending' WHERE id = :animal_id";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(':animal_id', $animal_id);
                $updateStmt->execute();
            }

            $this->conn->commit();
            return ["status" => true, "order_id" => $order_id];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ["status" => false, "message" => $e->getMessage()];
        }
    }

    public function recordPayment($order_id, $payment_gateway, $transaction_id, $amount, $status) {
        try {
            $this->conn->beginTransaction();

            // Insert payment record
            $query = "INSERT INTO payments (order_id, payment_gateway, transaction_id, amount, status) 
                      VALUES (:order_id, :payment_gateway, :transaction_id, :amount, :status)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->bindParam(':payment_gateway', $payment_gateway);
            $stmt->bindParam(':transaction_id', $transaction_id);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':status', $status);
            $stmt->execute();

            if ($status === 'success' || $status === 'completed' || $status === 'paid') {
                // Update order payment status
                $updateOrderQuery = "UPDATE " . $this->table_name . " 
                                     SET payment_status = 'paid', status = 'processing' 
                                     WHERE id = :order_id";
                $updateOrderStmt = $this->conn->prepare($updateOrderQuery);
                $updateOrderStmt->bindParam(':order_id', $order_id);
                $updateOrderStmt->execute();

                // Finalize animal status to 'sold'
                $updateAnimalQuery = "UPDATE animals a 
                                      JOIN order_items oi ON a.id = oi.animal_id 
                                      SET a.status = 'sold' 
                                      WHERE oi.order_id = :order_id";
                $updateAnimalStmt = $this->conn->prepare($updateAnimalQuery);
                $updateAnimalStmt->bindParam(':order_id', $order_id);
                $updateAnimalStmt->execute();
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getByBuyer($buyer_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE buyer_id = :buyer_id ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':buyer_id', $buyer_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getDetails($id) {
        // Order main details
        $query = "SELECT o.*, u.name as buyer_name, u.email as buyer_email, u.phone as buyer_phone, p.transaction_id, p.payment_gateway, p.created_at as payment_date
                  FROM " . $this->table_name . " o
                  LEFT JOIN users u ON o.buyer_id = u.id
                  LEFT JOIN payments p ON o.id = p.order_id
                  WHERE o.id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $order = $stmt->fetch();

        if ($order) {
            // Get order items (animals details)
            $itemQuery = "SELECT oi.price as purchase_price, a.id as animal_id, a.name as animal_name, a.breed, a.main_image, c.name as category_name
                          FROM order_items oi
                          LEFT JOIN animals a ON oi.animal_id = a.id
                          LEFT JOIN categories c ON a.category_id = c.id
                          WHERE oi.order_id = :order_id";
            $itemStmt = $this->conn->prepare($itemQuery);
            $itemStmt->bindParam(':order_id', $id);
            $itemStmt->execute();
            $order['items'] = $itemStmt->fetchAll();
        }

        return $order;
    }

    public function getAllOrders() {
        $query = "SELECT o.*, u.name as buyer_name 
                  FROM " . $this->table_name . " o
                  LEFT JOIN users u ON o.buyer_id = u.id 
                  ORDER BY o.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateStatus($id, $status, $tracking_number = null) {
        $query = "UPDATE " . $this->table_name . " SET status = :status";
        if ($tracking_number !== null) {
            $query .= ", tracking_number = :tracking_number";
        }
        $query .= " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        if ($tracking_number !== null) {
            $stmt->bindParam(':tracking_number', $tracking_number);
        }
        return $stmt->execute();
    }

    public function cancelOrder($id) {
        try {
            $this->conn->beginTransaction();

            // Set order status to cancelled
            $query = "UPDATE " . $this->table_name . " SET status = 'cancelled' WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            // Release animals back to 'available'
            $releaseQuery = "UPDATE animals a 
                             JOIN order_items oi ON a.id = oi.animal_id 
                             SET a.status = 'available' 
                             WHERE oi.order_id = :order_id";
            $releaseStmt = $this->conn->prepare($releaseQuery);
            $releaseStmt->bindParam(':order_id', $id);
            $releaseStmt->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}
?>
