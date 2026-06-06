<?php
/**
 * Order Model
 * 订单模型
 */

require_once __DIR__ . '/../config/database.php';

class Order {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $sql = "INSERT INTO orders (order_no, user_id, type, amount, status) 
                VALUES (:order_no, :user_id, :type, :amount, :status)";
        
        $params = [
            ':order_no' => $this->generateOrderNo(),
            ':user_id' => $data['user_id'],
            ':type' => $data['type'] ?? 'new',
            ':amount' => $data['amount'] ?? 0.00,
            ':status' => $data['status'] ?? 'pending'
        ];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    public function addItem($orderId, $item) {
        $sql = "INSERT INTO order_items (order_id, product_id, plugin_id, license_id, unit_price, quantity, subtotal)
                VALUES (:order_id, :product_id, :plugin_id, :license_id, :unit_price, :quantity, :subtotal)";
        
        $params = [
            ':order_id' => $orderId,
            ':product_id' => $item['product_id'] ?? null,
            ':plugin_id' => $item['plugin_id'] ?? null,
            ':license_id' => $item['license_id'] ?? null,
            ':unit_price' => $item['unit_price'] ?? 0.00,
            ':quantity' => $item['quantity'] ?? 1,
            ':subtotal' => $item['subtotal'] ?? 0.00
        ];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    public function findById($id) {
        $sql = "SELECT o.*, u.username, u.email 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    public function findByUserId($userId) {
        $sql = "SELECT o.*, u.username, u.email 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.user_id = :user_id 
                ORDER BY o.created_at DESC";
        return $this->db->fetchAll($sql, [':user_id' => $userId]);
    }
    
    public function getItems($orderId) {
        $sql = "SELECT oi.*, p.name as product_name, pl.name as plugin_name, l.license_key
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                LEFT JOIN plugins pl ON oi.plugin_id = pl.id
                LEFT JOIN licenses l ON oi.license_id = l.id
                WHERE oi.order_id = :order_id";
        return $this->db->fetchAll($sql, [':order_id' => $orderId]);
    }
    
    public function updateStatus($id, $status) {
        $sql = "UPDATE orders SET status = :status";
        $params = [':id' => $id, ':status' => $status];
        
        if ($status === 'paid') {
            $sql .= ", paid_at = NOW()";
        }
        
        $sql .= " WHERE id = :id";
        $this->db->execute($sql, $params);
        return true;
    }
    
    public function markAsPaid($id) {
        return $this->updateStatus($id, 'paid');
    }
    
    private function generateOrderNo() {
        return 'ORD' . date('YmdHis') . strtoupper(substr(md5(uniqid()), 0, 6));
    }
    
    public function findByLicenseId($licenseId) {
        $sql = "SELECT o.* 
                FROM orders o
                INNER JOIN order_items oi ON o.id = oi.order_id
                WHERE oi.license_id = :license_id
                ORDER BY o.created_at DESC
                LIMIT 1";
        return $this->db->fetchOne($sql, [':license_id' => $licenseId]);
    }
}
