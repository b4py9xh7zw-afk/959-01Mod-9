<?php
/**
 * Seed Licenses Script
 * This script creates sample licenses after users are created
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/License.php';
require_once __DIR__ . '/../models/Product.php';

try {
    $userModel = new User();
    $licenseModel = new License();
    $productModel = new Product();
    
    // Get admin user
    $admin = $userModel->findByEmail('admin@license-platform.com');
    if (!$admin) {
        echo "Admin user not found. Please run seed_users.php first.\n";
        exit(1);
    }
    
    // Get test user
    $testUser = $userModel->findByEmail('user@license-platform.com');
    if (!$testUser) {
        echo "Test user not found. Please run seed_users.php first.\n";
        exit(1);
    }
    
    $products = $productModel->findAll();
    $productMap = [];
    foreach ($products as $p) {
        $productMap[$p['name']] = $p;
    }
    
    // Check if licenses already exist
    $existingLicenses = $licenseModel->findAll(10, 0);
    if (count($existingLicenses) > 0) {
        echo "Licenses already exist. Skipping seed.\n";
        exit(0);
    }
    
    // Create sample licenses for admin - Product A (可升级)
    $productA = $productMap['Product A'] ?? null;
    $licenseModel->create([
        'user_id' => $admin['id'],
        'product_id' => $productA ? $productA['id'] : 1,
        'product_name' => $productA ? $productA['name'] : 'Product A',
        'license_type' => 'single',
        'status' => 'active',
        'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year'))
    ]);
    
    // Create sample license for admin - Product B
    $productB = $productMap['Product B'] ?? null;
    $licenseModel->create([
        'user_id' => $admin['id'],
        'product_id' => $productB ? $productB['id'] : 2,
        'product_name' => $productB ? $productB['name'] : 'Product B',
        'license_type' => 'single',
        'status' => 'active',
        'expires_at' => date('Y-m-d H:i:s', strtotime('+2 years'))
    ]);
    
    // Create sample licenses for test user - Product A (可升级)
    $licenseModel->create([
        'user_id' => $testUser['id'],
        'product_id' => $productA ? $productA['id'] : 1,
        'product_name' => $productA ? $productA['name'] : 'Product A',
        'license_type' => 'single',
        'status' => 'active',
        'expires_at' => date('Y-m-d H:i:s', strtotime('+6 months'))
    ]);
    
    // Create sample license for test user - Starter Suite
    $starterSuite = $productMap['Starter Suite'] ?? null;
    $licenseModel->create([
        'user_id' => $testUser['id'],
        'product_id' => $starterSuite ? $starterSuite['id'] : 4,
        'product_name' => $starterSuite ? $starterSuite['name'] : 'Starter Suite',
        'license_type' => 'suite',
        'status' => 'expired',
        'expires_at' => date('Y-m-d H:i:s', strtotime('-1 month'))
    ]);
    
    echo "Sample licenses created successfully!\n";
} catch (Exception $e) {
    error_log("License seeding failed: " . $e->getMessage());
    echo "License seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
