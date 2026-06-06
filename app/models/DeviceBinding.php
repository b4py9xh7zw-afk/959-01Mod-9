<?php
/**
 * DeviceBinding Model
 * 设备绑定模型
 */

require_once __DIR__ . '/../config/database.php';

class DeviceBinding {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $sql = "INSERT INTO device_bindings (license_id, device_id, device_name, device_info, status)
                VALUES (:license_id, :device_id, :device_name, :device_info, :status)";
        
        $params = [
            ':license_id' => $data['license_id'],
            ':device_id' => $data['device_id'],
            ':device_name' => $data['device_name'] ?? null,
            ':device_info' => $data['device_info'] ?? null,
            ':status' => $data['status'] ?? 'active'
        ];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    public function findByLicenseId($licenseId, $status = null) {
        $sql = "SELECT * FROM device_bindings WHERE license_id = :license_id";
        $params = [':license_id' => $licenseId];
        
        if ($status) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }
        $sql .= " ORDER BY bound_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function findActiveByLicenseId($licenseId) {
        return $this->findByLicenseId($licenseId, 'active');
    }
    
    public function transferBindings($oldLicenseId, $newLicenseId) {
        $sql = "UPDATE device_bindings 
                SET license_id = :new_license_id 
                WHERE license_id = :old_license_id AND status = 'active'";
        
        $params = [
            ':new_license_id' => $newLicenseId,
            ':old_license_id' => $oldLicenseId
        ];
        
        $this->db->execute($sql, $params);
        return true;
    }
    
    public function copyBindings($oldLicenseId, $newLicenseId) {
        $bindings = $this->findActiveByLicenseId($oldLicenseId);
        
        foreach ($bindings as $binding) {
            $this->create([
                'license_id' => $newLicenseId,
                'device_id' => $binding['device_id'],
                'device_name' => $binding['device_name'],
                'device_info' => $binding['device_info']
            ]);
        }
        
        return true;
    }
    
    public function unbind($id) {
        $sql = "UPDATE device_bindings SET status = 'inactive', unbound_at = NOW() WHERE id = :id";
        $this->db->execute($sql, [':id' => $id]);
        return true;
    }
    
    public function unbindAll($licenseId) {
        $sql = "UPDATE device_bindings SET status = 'inactive', unbound_at = NOW() WHERE license_id = :license_id AND status = 'active'";
        $this->db->execute($sql, [':license_id' => $licenseId]);
        return true;
    }
    
    public function countByLicenseId($licenseId) {
        $sql = "SELECT COUNT(*) as count FROM device_bindings WHERE license_id = :license_id AND status = 'active'";
        $result = $this->db->fetchOne($sql, [':license_id' => $licenseId]);
        return $result['count'] ?? 0;
    }
}
