<?php
/**
 * UpgradeHistory Model
 * 升级历史模型，记录所有升级和回退操作
 */

require_once __DIR__ . '/../config/database.php';

class UpgradeHistory {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $sql = "INSERT INTO upgrade_history (
            user_id, old_license_id, new_license_id, old_product_id, new_product_id,
            order_id, invoice_id, type, old_status, remaining_days, price_difference,
            old_license_disposal, plugins_added, plugins_removed, reason, performed_by
        ) VALUES (
            :user_id, :old_license_id, :new_license_id, :old_product_id, :new_product_id,
            :order_id, :invoice_id, :type, :old_status, :remaining_days, :price_difference,
            :old_license_disposal, :plugins_added, :plugins_removed, :reason, :performed_by
        )";
        
        $params = [
            ':user_id' => $data['user_id'],
            ':old_license_id' => $data['old_license_id'],
            ':new_license_id' => $data['new_license_id'] ?? null,
            ':old_product_id' => $data['old_product_id'],
            ':new_product_id' => $data['new_product_id'],
            ':order_id' => $data['order_id'] ?? null,
            ':invoice_id' => $data['invoice_id'] ?? null,
            ':type' => $data['type'],
            ':old_status' => $data['old_status'] ?? 'upgraded',
            ':remaining_days' => $data['remaining_days'] ?? 0,
            ':price_difference' => $data['price_difference'] ?? 0.00,
            ':old_license_disposal' => $data['old_license_disposal'] ?? 'retain',
            ':plugins_added' => isset($data['plugins_added']) ? json_encode($data['plugins_added']) : null,
            ':plugins_removed' => isset($data['plugins_removed']) ? json_encode($data['plugins_removed']) : null,
            ':reason' => $data['reason'] ?? null,
            ':performed_by' => $data['performed_by'] ?? null
        ];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    public function findById($id) {
        $sql = "SELECT uh.*, 
                old_lic.license_key as old_license_key,
                new_lic.license_key as new_license_key,
                old_prod.name as old_product_name,
                new_prod.name as new_product_name,
                u.username as user_name,
                o.order_no,
                i.invoice_no
                FROM upgrade_history uh
                LEFT JOIN licenses old_lic ON uh.old_license_id = old_lic.id
                LEFT JOIN licenses new_lic ON uh.new_license_id = new_lic.id
                LEFT JOIN products old_prod ON uh.old_product_id = old_prod.id
                LEFT JOIN products new_prod ON uh.new_product_id = new_prod.id
                LEFT JOIN users u ON uh.user_id = u.id
                LEFT JOIN orders o ON uh.order_id = o.id
                LEFT JOIN invoices i ON uh.invoice_id = i.id
                WHERE uh.id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    public function findByLicenseId($licenseId) {
        $sql = "SELECT uh.*,
                old_lic.license_key as old_license_key,
                new_lic.license_key as new_license_key,
                old_prod.name as old_product_name,
                new_prod.name as new_product_name,
                o.order_no
                FROM upgrade_history uh
                LEFT JOIN licenses old_lic ON uh.old_license_id = old_lic.id
                LEFT JOIN licenses new_lic ON uh.new_license_id = new_lic.id
                LEFT JOIN products old_prod ON uh.old_product_id = old_prod.id
                LEFT JOIN products new_prod ON uh.new_product_id = new_prod.id
                LEFT JOIN orders o ON uh.order_id = o.id
                WHERE uh.old_license_id = :license_id OR uh.new_license_id = :license_id2
                ORDER BY uh.created_at DESC";
        return $this->db->fetchAll($sql, [':license_id' => $licenseId, ':license_id2' => $licenseId]);
    }
    
    public function findByUserId($userId) {
        $sql = "SELECT uh.*,
                old_lic.license_key as old_license_key,
                new_lic.license_key as new_license_key,
                old_prod.name as old_product_name,
                new_prod.name as new_product_name,
                o.order_no
                FROM upgrade_history uh
                LEFT JOIN licenses old_lic ON uh.old_license_id = old_lic.id
                LEFT JOIN licenses new_lic ON uh.new_license_id = new_lic.id
                LEFT JOIN products old_prod ON uh.old_product_id = old_prod.id
                LEFT JOIN products new_prod ON uh.new_product_id = new_prod.id
                LEFT JOIN orders o ON uh.order_id = o.id
                WHERE uh.user_id = :user_id
                ORDER BY uh.created_at DESC";
        return $this->db->fetchAll($sql, [':user_id' => $userId]);
    }
    
    public function findAll($type = null) {
        $sql = "SELECT uh.*,
                old_lic.license_key as old_license_key,
                new_lic.license_key as new_license_key,
                old_prod.name as old_product_name,
                new_prod.name as new_product_name,
                u.username as user_name,
                o.order_no
                FROM upgrade_history uh
                LEFT JOIN licenses old_lic ON uh.old_license_id = old_lic.id
                LEFT JOIN licenses new_lic ON uh.new_license_id = new_lic.id
                LEFT JOIN products old_prod ON uh.old_product_id = old_prod.id
                LEFT JOIN products new_prod ON uh.new_product_id = new_prod.id
                LEFT JOIN users u ON uh.user_id = u.id
                LEFT JOIN orders o ON uh.order_id = o.id";
        
        if ($type) {
            $sql .= " WHERE uh.type = :type";
        }
        $sql .= " ORDER BY uh.created_at DESC";
        
        $params = $type ? [':type' => $type] : [];
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getUpgradeChain($licenseId) {
        $chain = [];
        $currentId = $licenseId;
        
        while ($currentId) {
            $sql = "SELECT * FROM upgrade_history 
                    WHERE new_license_id = :license_id OR old_license_id = :license_id2
                    ORDER BY created_at ASC
                    LIMIT 1";
            $record = $this->db->fetchOne($sql, [':license_id' => $currentId, ':license_id2' => $currentId]);
            
            if (!$record) break;
            
            $chain[] = $record;
            $currentId = $record['old_license_id'] == $currentId ? $record['new_license_id'] : $record['old_license_id'];
        }
        
        return $chain;
    }
}
