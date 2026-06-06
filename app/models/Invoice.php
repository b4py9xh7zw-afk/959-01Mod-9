<?php
/**
 * Invoice Model
 * 发票模型
 */

require_once __DIR__ . '/../config/database.php';

class Invoice {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $sql = "INSERT INTO invoices (invoice_no, order_id, user_id, amount, status)
                VALUES (:invoice_no, :order_id, :user_id, :amount, :status)";
        
        $params = [
            ':invoice_no' => $this->generateInvoiceNo(),
            ':order_id' => $data['order_id'],
            ':user_id' => $data['user_id'],
            ':amount' => $data['amount'] ?? 0.00,
            ':status' => $data['status'] ?? 'issued'
        ];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    public function findById($id) {
        $sql = "SELECT i.*, o.order_no, u.username, u.email 
                FROM invoices i
                LEFT JOIN orders o ON i.order_id = o.id
                LEFT JOIN users u ON i.user_id = u.id
                WHERE i.id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    public function findByOrderId($orderId) {
        $sql = "SELECT i.*, o.order_no 
                FROM invoices i
                LEFT JOIN orders o ON i.order_id = o.id
                WHERE i.order_id = :order_id";
        return $this->db->fetchOne($sql, [':order_id' => $orderId]);
    }
    
    public function findByUserId($userId) {
        $sql = "SELECT i.*, o.order_no 
                FROM invoices i
                LEFT JOIN orders o ON i.order_id = o.id
                WHERE i.user_id = :user_id
                ORDER BY i.created_at DESC";
        return $this->db->fetchAll($sql, [':user_id' => $userId]);
    }
    
    public function findByLicenseId($licenseId) {
        $sql = "SELECT i.*, o.order_no
                FROM invoices i
                INNER JOIN orders o ON i.order_id = o.id
                INNER JOIN order_items oi ON o.id = oi.order_id
                WHERE oi.license_id = :license_id
                ORDER BY i.created_at DESC
                LIMIT 1";
        return $this->db->fetchOne($sql, [':license_id' => $licenseId]);
    }
    
    public function updateStatus($id, $status) {
        $sql = "UPDATE invoices SET status = :status";
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
    
    private function generateInvoiceNo() {
        return 'INV' . date('YmdHis') . strtoupper(substr(md5(uniqid()), 0, 6));
    }
}
