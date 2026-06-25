<?php
class Admin {
    private $conn;
    private $table_name = "admins";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($username, $password) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = :username LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            if (password_verify($password, $row['password'])) {
                unset($row['password']);
                return ["status" => true, "admin" => $row];
            }
        }
        return ["status" => false, "message" => "Invalid username or password."];
    }

    public function getDashboardStats() {
        $stats = [];

        // 1. Total Users
        $q = $this->conn->query("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $q->fetch()['count'];

        // 2. Total Orders
        $q = $this->conn->query("SELECT COUNT(*) as count FROM orders");
        $stats['total_orders'] = $q->fetch()['count'];

        // 3. Total Sales
        $q = $this->conn->query("SELECT SUM(total_amount) as sales FROM orders WHERE payment_status = 'paid'");
        $stats['total_sales'] = (float)($q->fetch()['sales'] ?? 0);

        // 4. Total Animals
        $q = $this->conn->query("SELECT COUNT(*) as count FROM animals");
        $stats['total_animals'] = $q->fetch()['count'];

        // 5. Orders Status Breakdown
        $q = $this->conn->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
        $stats['order_statuses'] = $q->fetchAll();

        // 6. Active listings count
        $q = $this->conn->query("SELECT COUNT(*) as count FROM animals WHERE status = 'available'");
        $stats['active_listings'] = $q->fetch()['count'];

        return $stats;
    }

    public function getSalesChartsData() {
        // Sales over last 7 days
        $query = "SELECT DATE(created_at) as date, SUM(total_amount) as sales, COUNT(*) as count 
                  FROM orders 
                  WHERE payment_status = 'paid' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                  GROUP BY DATE(created_at) 
                  ORDER BY DATE(created_at) ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $sales = $stmt->fetchAll();

        // Sales distribution by category
        $queryCat = "SELECT c.name as category, COUNT(oi.id) as item_count, SUM(oi.price) as sales 
                     FROM order_items oi
                     JOIN animals a ON oi.animal_id = a.id
                     JOIN categories c ON a.category_id = c.id
                     JOIN orders o ON oi.order_id = o.id
                     WHERE o.payment_status = 'paid'
                     GROUP BY c.id";
        $stmtCat = $this->conn->prepare($queryCat);
        $stmtCat->execute();
        $categories = $stmtCat->fetchAll();

        return [
            "daily_sales" => $sales,
            "category_sales" => $categories
        ];
    }
}
?>
