<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../auth/middleware.php';

class OrderController {
    private $db;
    private $order;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->order = new Order($this->db);
    }

    public function checkout($buyerId, $data) {
        $data = sanitizeInput($data);

        if (empty($data['shipping_address']) || empty($data['payment_method']) || empty($data['animal_ids']) || !is_array($data['animal_ids'])) {
            http_response_code(400);
            return ["status" => "error", "message" => "Invalid checkout details."];
        }

        // Calculate total amount from animal prices
        $animalIds = $data['animal_ids'];
        $placeholders = implode(',', array_fill(0, count($animalIds), '?'));
        
        $priceQuery = "SELECT SUM(price) as total FROM animals WHERE id IN ($placeholders) AND status = 'available'";
        $priceStmt = $this->db->prepare($priceQuery);
        $priceStmt->execute(array_map('intval', $animalIds));
        $totalAmount = (float)($priceStmt->fetch()['total'] ?? 0);

        if ($totalAmount <= 0) {
            http_response_code(400);
            return ["status" => "error", "message" => "No available animals selected."];
        }

        // 1. Create order
        $orderResult = $this->order->create($buyerId, $totalAmount, $data['payment_method'], $data['shipping_address'], $animalIds);

        if (!$orderResult['status']) {
            http_response_code(500);
            return ["status" => "error", "message" => $orderResult['message']];
        }

        $orderId = $orderResult['order_id'];

        // 2. Mock payment gateway execution
        $paymentSuccess = true; // Assume success for sandbox
        $transactionId = 'TXN_' . strtoupper(bin2hex(random_bytes(8)));
        $gateway = $data['payment_method'];

        if ($paymentSuccess) {
            // Record payment & finalize orders/animals
            $payResult = $this->order->recordPayment($orderId, $gateway, $transactionId, $totalAmount, 'success');
            
            if ($payResult) {
                // Add notification
                $this->addNotification(
                    $buyerId,
                    "Order Placed Successfully",
                    "Your order #{$orderId} for ₹" . number_format($totalAmount, 2) . " has been paid and is being processed."
                );

                // Notify sellers
                $this->notifySellersForOrder($orderId);

                return [
                    "status" => "success",
                    "message" => "Order completed successfully.",
                    "order_id" => $orderId,
                    "transaction_id" => $transactionId
                ];
            }
        }

        http_response_code(500);
        return ["status" => "error", "message" => "Failed to process payment."];
    }

    public function listBuyerOrders($buyerId) {
        $orders = $this->order->getByBuyer($buyerId);
        return ["status" => "success", "orders" => $orders];
    }

    public function getOrderDetails($buyerId, $orderId, $isAdmin = false) {
        $orderId = (int)$orderId;
        $details = $this->order->getDetails($orderId);

        if (!$details) {
            http_response_code(404);
            return ["status" => "error", "message" => "Order not found."];
        }

        // Authorize
        if (!$isAdmin && $details['buyer_id'] != $buyerId) {
            http_response_code(403);
            return ["status" => "error", "message" => "You are not authorized to view this order."];
        }

        return ["status" => "success", "order" => $details];
    }

    public function cancel($buyerId, $orderId) {
        $orderId = (int)$orderId;
        $details = $this->order->getDetails($orderId);

        if (!$details || $details['buyer_id'] != $buyerId) {
            http_response_code(404);
            return ["status" => "error", "message" => "Order not found."];
        }

        if ($details['status'] !== 'pending' && $details['status'] !== 'processing') {
            http_response_code(400);
            return ["status" => "error", "message" => "Only pending or processing orders can be cancelled."];
        }

        $success = $this->order->cancelOrder($orderId);
        if ($success) {
            $this->addNotification(
                $buyerId,
                "Order Cancelled",
                "Your order #{$orderId} has been successfully cancelled. Refund will be processed."
            );
            return ["status" => "success", "message" => "Order cancelled successfully."];
        } else {
            http_response_code(500);
            return ["status" => "error", "message" => "Failed to cancel order."];
        }
    }

    // Helper: Add notifications
    private function addNotification($userId, $title, $message) {
        $query = "INSERT INTO notifications (user_id, title, message) VALUES (:user_id, :title, :message)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->execute();
    }

    // Helper: Notify sellers that their animals were sold
    private function notifySellersForOrder($orderId) {
        $query = "SELECT a.seller_id, a.name as animal_name, oi.price 
                  FROM order_items oi
                  JOIN animals a ON oi.animal_id = a.id
                  WHERE oi.order_id = :order_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();
        $items = $stmt->fetchAll();

        foreach ($items as $item) {
            $this->addNotification(
                $item['seller_id'],
                "Animal Sold!",
                "Your listing '{$item['animal_name']}' has been purchased for ₹" . number_format($item['price'], 2) . ". Check your dashboard."
            );
        }
    }
}
?>
