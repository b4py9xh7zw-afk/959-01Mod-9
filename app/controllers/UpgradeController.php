<?php
/**
 * Upgrade Controller
 * 套餐升级控制器，实现多产品套餐升级和回退功能
 */

require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../models/License.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Invoice.php';
require_once __DIR__ . '/../models/DeviceBinding.php';
require_once __DIR__ . '/../models/Plugin.php';
require_once __DIR__ . '/../models/UpgradeHistory.php';

class UpgradeController {
    private $authController;
    private $licenseModel;
    private $productModel;
    private $orderModel;
    private $invoiceModel;
    private $deviceBindingModel;
    private $pluginModel;
    private $upgradeHistoryModel;
    
    public function __construct() {
        $this->authController = new AuthController();
        $this->licenseModel = new License();
        $this->productModel = new Product();
        $this->orderModel = new Order();
        $this->invoiceModel = new Invoice();
        $this->deviceBindingModel = new DeviceBinding();
        $this->pluginModel = new Plugin();
        $this->upgradeHistoryModel = new UpgradeHistory();
    }
    
    public function calculateRemainingDays($license) {
        if (empty($license['expires_at'])) {
            return 365;
        }
        
        $expireTime = strtotime($license['expires_at']);
        $now = time();
        
        if ($expireTime <= $now) {
            return 0;
        }
        
        $remainingSeconds = $expireTime - $now;
        return (int)ceil($remainingSeconds / (60 * 60 * 24));
    }
    
    public function preview() {
        $this->authController->requireAuth();
        
        $licenseId = $_GET['license_id'] ?? null;
        if (!$licenseId) {
            $_SESSION['error'] = '许可证ID是必填项';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $license = $this->licenseModel->findById($licenseId);
        if (!$license) {
            $_SESSION['error'] = '许可证不存在';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        if ($license['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
            $_SESSION['error'] = '访问被拒绝';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        if ($license['status'] !== 'active') {
            $_SESSION['error'] = '只有活跃状态的许可证才能升级';
            header('Location: /licenses/view?id=' . $licenseId);
            exit;
        }
        
        $remainingDays = $this->calculateRemainingDays($license);
        
        $productId = $license['product_id'] ?? null;
        if (!$productId) {
            $productId = $this->guessProductId($license['product_name']);
        }
        
        $availableSuites = $this->productModel->getAvailableSuitesForUpgrade($productId);
        
        $upgradePreviews = [];
        foreach ($availableSuites as $suite) {
            $priceInfo = $this->productModel->calculatePriceDifference($productId, $suite['id'], $remainingDays);
            $pluginInfo = $this->productModel->getPluginDifference($productId, $suite['id']);
            $suiteProducts = $this->productModel->getSuiteProducts($suite['id']);
            
            $upgradePreviews[] = [
                'suite' => $suite,
                'price_info' => $priceInfo,
                'plugin_info' => $pluginInfo,
                'suite_products' => $suiteProducts
            ];
        }
        
        $disposalOptions = [
            'retain' => '保留旧授权（可查看历史记录）',
            'cancel' => '立即取消旧授权',
            'convert' => '转换为优惠券'
        ];
        
        require_once __DIR__ . '/../views/licenses/upgrade_preview.php';
    }
    
    public function calculate() {
        $this->authController->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $licenseId = $_POST['license_id'] ?? null;
        $newProductId = $_POST['new_product_id'] ?? null;
        $disposalMethod = $_POST['disposal_method'] ?? 'retain';
        
        if (!$licenseId || !$newProductId) {
            echo json_encode(['error' => '缺少必要参数']);
            exit;
        }
        
        $license = $this->licenseModel->findById($licenseId);
        if (!$license || ($license['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin')) {
            echo json_encode(['error' => '许可证不存在或无权限']);
            exit;
        }
        
        $oldProductId = $license['product_id'] ?? $this->guessProductId($license['product_name']);
        $remainingDays = $this->calculateRemainingDays($license);
        
        $priceInfo = $this->productModel->calculatePriceDifference($oldProductId, $newProductId, $remainingDays);
        $pluginInfo = $this->productModel->getPluginDifference($oldProductId, $newProductId);
        $suiteProducts = $this->productModel->getSuiteProducts($newProductId);
        
        echo json_encode([
            'success' => true,
            'remaining_days' => $remainingDays,
            'price_info' => $priceInfo,
            'plugin_info' => $pluginInfo,
            'suite_products' => $suiteProducts,
            'disposal_method' => $disposalMethod
        ]);
        exit;
    }
    
    public function upgrade() {
        $this->authController->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $oldLicenseId = $_POST['license_id'] ?? null;
        $newProductId = $_POST['new_product_id'] ?? null;
        $disposalMethod = $_POST['disposal_method'] ?? 'retain';
        $reason = $_POST['reason'] ?? null;
        
        if (!$oldLicenseId || !$newProductId) {
            $_SESSION['error'] = '缺少必要参数';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $oldLicense = $this->licenseModel->findById($oldLicenseId);
        if (!$oldLicense) {
            $_SESSION['error'] = '许可证不存在';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        if ($oldLicense['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
            $_SESSION['error'] = '访问被拒绝';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        if ($oldLicense['status'] !== 'active') {
            $_SESSION['error'] = '只有活跃状态的许可证才能升级';
            header('Location: /licenses/view?id=' . $oldLicenseId);
            exit;
        }
        
        try {
            $oldProductId = $oldLicense['product_id'] ?? $this->guessProductId($oldLicense['product_name']);
            $newProduct = $this->productModel->findById($newProductId);
            $remainingDays = $this->calculateRemainingDays($oldLicense);
            
            $priceInfo = $this->productModel->calculatePriceDifference($oldProductId, $newProductId, $remainingDays);
            $pluginInfo = $this->productModel->getPluginDifference($oldProductId, $newProductId);
            
            $connection = $this->licenseModel->getDbConnection();
            $connection->beginTransaction();
            
            $orderId = $this->orderModel->create([
                'user_id' => $oldLicense['user_id'],
                'type' => 'upgrade',
                'amount' => $priceInfo['price_difference'],
                'status' => 'paid'
            ]);
            
            $this->orderModel->addItem($orderId, [
                'product_id' => $newProductId,
                'license_id' => $oldLicenseId,
                'unit_price' => $priceInfo['price_difference'],
                'quantity' => 1,
                'subtotal' => $priceInfo['price_difference']
            ]);
            
            $this->orderModel->markAsPaid($orderId);
            
            $invoiceId = $this->invoiceModel->create([
                'order_id' => $orderId,
                'user_id' => $oldLicense['user_id'],
                'amount' => $priceInfo['price_difference'],
                'status' => 'paid'
            ]);
            $this->invoiceModel->markAsPaid($invoiceId);
            
            $newLicenseId = $this->licenseModel->create([
                'user_id' => $oldLicense['user_id'],
                'product_id' => $newProductId,
                'order_id' => $orderId,
                'invoice_id' => $invoiceId,
                'product_name' => $newProduct['name'],
                'license_type' => $newProduct['type'],
                'status' => 'active',
                'expires_at' => date('Y-m-d H:i:s', time() + $newProduct['duration_days'] * 24 * 60 * 60),
                'original_license_id' => $this->getOriginalLicenseId($oldLicense),
                'source_license_id' => $oldLicenseId,
                'upgrade_count' => ($oldLicense['upgrade_count'] ?? 0) + 1
            ]);
            
            $newPluginIds = array_column($pluginInfo['new_plugins'], 'id');
            $this->pluginModel->syncLicensePlugins($newLicenseId, $newPluginIds);
            
            $this->deviceBindingModel->copyBindings($oldLicenseId, $newLicenseId);
            
            $oldLicenseStatus = 'upgraded';
            if ($disposalMethod === 'cancel') {
                $oldLicenseStatus = 'inactive';
            } elseif ($disposalMethod === 'retain') {
                $oldLicenseStatus = 'upgraded';
            }
            
            $this->licenseModel->update($oldLicenseId, [
                'status' => $oldLicenseStatus,
                'upgrade_count' => ($oldLicense['upgrade_count'] ?? 0) + 1,
                'last_upgraded_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->upgradeHistoryModel->create([
                'user_id' => $oldLicense['user_id'],
                'old_license_id' => $oldLicenseId,
                'new_license_id' => $newLicenseId,
                'old_product_id' => $oldProductId,
                'new_product_id' => $newProductId,
                'order_id' => $orderId,
                'invoice_id' => $invoiceId,
                'type' => 'upgrade',
                'old_status' => $oldLicenseStatus,
                'remaining_days' => $remainingDays,
                'price_difference' => $priceInfo['price_difference'],
                'old_license_disposal' => $disposalMethod,
                'plugins_added' => $pluginInfo['added_plugins'],
                'plugins_removed' => $pluginInfo['removed_plugins'],
                'reason' => $reason,
                'performed_by' => $_SESSION['user_id']
            ]);
            
            $connection->commit();
            
            $_SESSION['success'] = '套餐升级成功！新许可证已创建并激活。';
            header('Location: /licenses/view?id=' . $newLicenseId);
            exit;
            
        } catch (Exception $e) {
            if (isset($connection) && $connection->inTransaction()) {
                $connection->rollBack();
            }
            error_log("Upgrade error: " . $e->getMessage());
            $_SESSION['error'] = '升级失败：' . $e->getMessage();
            header('Location: /licenses/upgrade/preview?license_id=' . $oldLicenseId);
            exit;
        }
    }
    
    public function downgrade() {
        $this->authController->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $licenseId = $_POST['license_id'] ?? null;
        $reason = $_POST['reason'] ?? null;
        
        if (!$licenseId) {
            $_SESSION['error'] = '许可证ID是必填项';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $license = $this->licenseModel->findById($licenseId);
        if (!$license) {
            $_SESSION['error'] = '许可证不存在';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        if ($license['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
            $_SESSION['error'] = '访问被拒绝';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        if ($license['status'] !== 'active') {
            $_SESSION['error'] = '只有活跃状态的许可证才能回退';
            header('Location: /licenses/view?id=' . $licenseId);
            exit;
        }
        
        if (empty($license['source_license_id'])) {
            $_SESSION['error'] = '该许可证没有可回退的历史记录';
            header('Location: /licenses/view?id=' . $licenseId);
            exit;
        }
        
        try {
            $sourceLicense = $this->licenseModel->findById($license['source_license_id']);
            if (!$sourceLicense) {
                $_SESSION['error'] = '源许可证不存在，无法回退';
                header('Location: /licenses/view?id=' . $licenseId);
                exit;
            }
            
            $connection = $this->licenseModel->getDbConnection();
            $connection->beginTransaction();
            
            $oldProductId = $license['product_id'] ?? $this->guessProductId($license['product_name']);
            $newProductId = $sourceLicense['product_id'] ?? $this->guessProductId($sourceLicense['product_name']);
            
            $this->licenseModel->update($licenseId, [
                'status' => 'downgraded'
            ]);
            
            $this->licenseModel->update($sourceLicense['id'], [
                'status' => 'active',
                'expires_at' => $this->recalculateExpiresAt($sourceLicense)
            ]);
            
            $upgradeHistory = $this->upgradeHistoryModel->findByLicenseId($licenseId);
            $lastUpgrade = null;
            foreach ($upgradeHistory as $history) {
                if ($history['new_license_id'] == $licenseId && $history['type'] == 'upgrade') {
                    $lastUpgrade = $history;
                    break;
                }
            }
            
            $remainingDays = $this->calculateRemainingDays($license);
            $priceDiff = $lastUpgrade['price_difference'] ?? 0;
            
            $refundOrderId = $this->orderModel->create([
                'user_id' => $license['user_id'],
                'type' => 'downgrade',
                'amount' => -$priceDiff,
                'status' => 'refunded'
            ]);
            
            $this->orderModel->addItem($refundOrderId, [
                'product_id' => $oldProductId,
                'license_id' => $licenseId,
                'unit_price' => -$priceDiff,
                'quantity' => 1,
                'subtotal' => -$priceDiff
            ]);
            
            $refundInvoiceId = $this->invoiceModel->create([
                'order_id' => $refundOrderId,
                'user_id' => $license['user_id'],
                'amount' => -$priceDiff,
                'status' => 'refunded'
            ]);
            
            $this->deviceBindingModel->copyBindings($licenseId, $sourceLicense['id']);
            
            $this->upgradeHistoryModel->create([
                'user_id' => $license['user_id'],
                'old_license_id' => $licenseId,
                'new_license_id' => $sourceLicense['id'],
                'old_product_id' => $oldProductId,
                'new_product_id' => $newProductId,
                'order_id' => $refundOrderId,
                'invoice_id' => $refundInvoiceId,
                'type' => 'downgrade',
                'old_status' => 'downgraded',
                'remaining_days' => $remainingDays,
                'price_difference' => -$priceDiff,
                'old_license_disposal' => 'retain',
                'reason' => $reason,
                'performed_by' => $_SESSION['user_id']
            ]);
            
            $connection->commit();
            
            $_SESSION['success'] = '套餐回退成功！已恢复到之前的许可证，所有历史记录已保留。';
            header('Location: /licenses/view?id=' . $sourceLicense['id']);
            exit;
            
        } catch (Exception $e) {
            if (isset($connection) && $connection->inTransaction()) {
                $connection->rollBack();
            }
            error_log("Downgrade error: " . $e->getMessage());
            $_SESSION['error'] = '回退失败：' . $e->getMessage();
            header('Location: /licenses/view?id=' . $licenseId);
            exit;
        }
    }
    
    public function history() {
        $this->authController->requireAuth();
        
        $licenseId = $_GET['license_id'] ?? null;
        if (!$licenseId) {
            $_SESSION['error'] = '许可证ID是必填项';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $license = $this->licenseModel->findById($licenseId);
        if (!$license) {
            $_SESSION['error'] = '许可证不存在';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        if ($license['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
            $_SESSION['error'] = '访问被拒绝';
            header('Location: /dashboard/licenses');
            exit;
        }
        
        $history = $this->upgradeHistoryModel->findByLicenseId($licenseId);
        $upgradeChain = $this->upgradeHistoryModel->getUpgradeChain($licenseId);
        
        require_once __DIR__ . '/../views/licenses/upgrade_history.php';
    }
    
    private function guessProductId($productName) {
        $products = $this->productModel->findAll();
        foreach ($products as $product) {
            if (stripos($product['name'], $productName) !== false || stripos($productName, $product['name']) !== false) {
                return $product['id'];
            }
        }
        return 1;
    }
    
    private function getOriginalLicenseId($license) {
        if (!empty($license['original_license_id'])) {
            return $license['original_license_id'];
        }
        return $license['id'];
    }
    
    private function recalculateExpiresAt($license) {
        $remainingDays = $this->calculateRemainingDays($license);
        if ($remainingDays > 0) {
            return date('Y-m-d H:i:s', time() + $remainingDays * 24 * 60 * 60);
        }
        return date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60);
    }
}
