<?php
/**
 * Plugin Model
 * 插件模型
 */

require_once __DIR__ . '/../config/database.php';

class Plugin {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $sql = "INSERT INTO plugins (name, description, price, status) 
                VALUES (:name, :description, :price, :status)";
        
        $params = [
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':price' => $data['price'] ?? 0.00,
            ':status' => $data['status'] ?? 'active'
        ];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM plugins WHERE id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    public function findAll($status = 'active') {
        $sql = "SELECT * FROM plugins";
        $params = [];
        
        if ($status) {
            $sql .= " WHERE status = :status";
            $params[':status'] = $status;
        }
        $sql .= " ORDER BY name ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function findByLicenseId($licenseId, $status = 'active') {
        $sql = "SELECT p.*, lp.status as license_plugin_status
                FROM plugins p
                INNER JOIN license_plugins lp ON p.id = lp.plugin_id
                WHERE lp.license_id = :license_id";
        $params = [':license_id' => $licenseId];
        
        if ($status) {
            $sql .= " AND lp.status = :status";
            $params[':status'] = $status;
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function assignToLicense($licenseId, $pluginId) {
        $sql = "INSERT IGNORE INTO license_plugins (license_id, plugin_id, status)
                VALUES (:license_id, :plugin_id, 'active')";
        
        $params = [
            ':license_id' => $licenseId,
            ':plugin_id' => $pluginId
        ];
        
        $this->db->execute($sql, $params);
        return true;
    }
    
    public function removeFromLicense($licenseId, $pluginId) {
        $sql = "UPDATE license_plugins SET status = 'inactive' 
                WHERE license_id = :license_id AND plugin_id = :plugin_id";
        
        $params = [
            ':license_id' => $licenseId,
            ':plugin_id' => $pluginId
        ];
        
        $this->db->execute($sql, $params);
        return true;
    }
    
    public function syncLicensePlugins($licenseId, $pluginIds) {
        $this->db->execute("UPDATE license_plugins SET status = 'inactive' WHERE license_id = :license_id", [':license_id' => $licenseId]);
        
        foreach ($pluginIds as $pluginId) {
            $this->assignToLicense($licenseId, $pluginId);
        }
        
        return true;
    }
    
    public function copyLicensePlugins($oldLicenseId, $newLicenseId) {
        $plugins = $this->findByLicenseId($oldLicenseId, 'active');
        
        foreach ($plugins as $plugin) {
            $this->assignToLicense($newLicenseId, $plugin['id']);
        }
        
        return true;
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($data['name'])) {
            $fields[] = "name = :name";
            $params[':name'] = $data['name'];
        }
        if (isset($data['description'])) {
            $fields[] = "description = :description";
            $params[':description'] = $data['description'];
        }
        if (isset($data['price'])) {
            $fields[] = "price = :price";
            $params[':price'] = $data['price'];
        }
        if (isset($data['status'])) {
            $fields[] = "status = :status";
            $params[':status'] = $data['status'];
        }
        
        if (empty($fields)) return false;
        
        $sql = "UPDATE plugins SET " . implode(', ', $fields) . " WHERE id = :id";
        $this->db->execute($sql, $params);
        return true;
    }
}
