<?php
class User {
    private $conn;
    private $table_name = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register($name, $email, $password, $phone, $address, $role) {
        // Check if email already exists
        if ($this->emailExists($email)) {
            return ["status" => false, "message" => "Email is already registered."];
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  (name, email, password, phone, address, role) 
                  VALUES (:name, :email, :password, :phone, :address, :role)";

        $stmt = $this->conn->prepare($query);

        // Hashing password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':role', $role);

        if ($stmt->execute()) {
            return ["status" => true, "id" => $this->conn->lastInsertId()];
        }
        return ["status" => false, "message" => "Failed to register user."];
    }

    public function login($email, $password) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            
            // Check status
            if ($row['status'] === 'banned') {
                return ["status" => false, "message" => "Your account has been banned by the administrator."];
            }

            // Verify password
            if (password_verify($password, $row['password'])) {
                unset($row['password']); // Don't return password hash
                return ["status" => true, "user" => $row];
            }
        }
        return ["status" => false, "message" => "Invalid email or password."];
    }

    public function getUserById($id) {
        $query = "SELECT id, name, email, phone, address, role, status, created_at 
                  FROM " . $this->table_name . " WHERE id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function updateProfile($id, $name, $phone, $address, $password = null) {
        if ($password) {
            $query = "UPDATE " . $this->table_name . " 
                      SET name = :name, phone = :phone, address = :address, password = :password 
                      WHERE id = :id";
            $hashed = password_hash($password, PASSWORD_BCRYPT);
        } else {
            $query = "UPDATE " . $this->table_name . " 
                      SET name = :name, phone = :phone, address = :address 
                      WHERE id = :id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':id', $id);
        if ($password) {
            $stmt->bindParam(':password', $hashed);
        }

        return $stmt->execute();
    }

    public function emailExists($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function getAllUsers() {
        $query = "SELECT id, name, email, phone, address, role, status, created_at 
                  FROM " . $this->table_name . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
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
}
?>
