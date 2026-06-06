<?php
/**
 * Product Model
 * 产品模型，支持单产品和套件
 */

require_once __DIR__ . '/../config/database.php';

class Product {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $sql = "INSERT INTO products (name, description, type, price, duration_days, status) 
                VALUES (:name, :description, :type, :price, :duration_days, :status)";
        
        $params = [
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':type' => $data['type'] ?? 'single',
            ':price' => $data['price'] ?? 0.00,
            ':duration_days' => $data['duration_days'] ?? 365,
            ':status' => $data['status'] ?? 'active'
        ];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM products WHERE id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    public function findAll($type = null, $status = 'active') {
        $sql = "SELECT * FROM products WHERE 1=1";
        $params = [];
        
        if ($type) {
            $sql .= " AND type = :type";
            $params[':type'] = $type;
        }
        if ($status) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }
        $sql .= " ORDER BY price ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function findSuites() {
        return $this->findAll('suite');
    }
    
    public function findSingles() {
        return $this->findAll('single');
    }
    
    public function getSuiteProducts($suiteId) {
        $sql = "SELECT p.* 
                FROM products p
                INNER JOIN product_relations pr ON p.id = pr.product_id
                WHERE pr.suite_id = :suite_id AND p.status = 'active'";
        return $this->db->fetchAll($sql, [':suite_id' => $suiteId]);
    }
    
    public function getAvailableSuitesForUpgrade($productId) {
        $sql = "SELECT DISTINCT s.*
                FROM products s
                INNER JOIN product_relations pr ON s.id = pr.suite_id
                WHERE pr.product_id = :product_id 
                AND s.type = 'suite' 
                AND s.status = 'active'
                AND s.id != :product_id2
                ORDER BY s.price ASC";
        return $this->db->fetchAll($sql, [':product_id' => $productId, ':product_id2' => $productId]);
    }
    
    public function getProductPlugins($productId) {
        $sql = "SELECT p.* 
                FROM plugins p
                INNER JOIN product_plugins pp ON p.id = pp.plugin_id
                WHERE pp.product_id = :product_id AND p.status = 'active'";
        return $this->db->fetchAll($sql, [':product_id' => $productId]);
    }
    
    public function calculatePriceDifference($oldProductId, $newProductId, $remainingDays) {
        $oldProduct = $this->findById($oldProductId);
        $newProduct = $this->findById($newProductId);
        
        if (!$oldProduct || !$newProduct) {
            return null;
        }
        
        $oldDailyRate = $oldProduct['price'] / $oldProduct['duration_days'];
        $remainingValue = $oldDailyRate * $remainingDays;
        
        $newPrice = $newProduct['price'];
        $difference = $newPrice - $remainingValue;
        
        return [
            'old_product' => $oldProduct,
            'new_product' => $newProduct,
            'old_daily_rate' => round($oldDailyRate, 4),
            'remaining_value' => round($remainingValue, 2),
            'new_price' => $newProduct['price'],
            'price_difference' => round(max(0, $difference), 2),
            'remaining_days' => $remainingDays
        ];
    }
    
    public function getPluginDifference($oldProductId, $newProductId) {
        $oldPlugins = $this->getProductPlugins($oldProductId);
        $newPlugins = $this->getProductPlugins($newProductId);
        
        $oldPluginIds = array_column($oldPlugins, 'id');
        $newPluginIds = array_column($newPlugins, 'id');
        
        $addedPlugins = array_udiff($newPlugins, $oldPlugins, function($a, $b) {
            return $a['id'] - $b['id'];
        });
        
        $removedPlugins = array_udiff($oldPlugins, $newPlugins, function($a, $b) {
            return $a['id'] - $b['id'];
        });
        
        return [
            'old_plugins' => $oldPlugins,
            'new_plugins' => $newPlugins,
            'added_plugins' => $addedPlugins,
            'removed_plugins' => $removedPlugins,
            'added_plugin_ids' => array_values(array_diff($newPluginIds, $oldPluginIds)),
            'removed_plugin_ids' => array_values(array_diff($oldPluginIds, $newPluginIds))
        ];
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
        if (isset($data['type'])) {
            $fields[] = "type = :type";
            $params[':type'] = $data['type'];
        }
        if (isset($data['price'])) {
            $fields[] = "price = :price";
            $params[':price'] = $data['price'];
        }
        if (isset($data['duration_days'])) {
            $fields[] = "duration_days = :duration_days";
            $params[':duration_days'] = $data['duration_days'];
        }
        if (isset($data['status'])) {
            $fields[] = "status = :status";
            $params[':status'] = $data['status'];
        }
        
        if (empty($fields)) return false;
        
        $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = :id";
        $this->db->execute($sql, $params);
        return true;
    }
}
