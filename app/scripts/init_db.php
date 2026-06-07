<?php
/**
 * Database initialization script
 * This script can be run manually to initialize the database
 * 支持包含存储过程的复杂SQL文件
 */

require_once __DIR__ . '/../config/database.php';

/**
 * 智能解析SQL文件，正确处理DELIMITER和存储过程
 */
function parseSqlFile($sqlContent) {
    $statements = [];
    $currentStatement = '';
    $delimiter = ';';
    $lines = explode("\n", $sqlContent);
    
    foreach ($lines as $lineNum => $line) {
        $trimmedLine = trim($line);
        
        if (empty($trimmedLine) || strpos($trimmedLine, '--') === 0) {
            continue;
        }
        
        if (preg_match('/^DELIMITER\s+(.+)$/i', $trimmedLine, $matches)) {
            if (!empty($currentStatement)) {
                $statements[] = trim($currentStatement);
                $currentStatement = '';
            }
            $delimiter = trim($matches[1]);
            continue;
        }
        
        $currentStatement .= $line . "\n";
        
        $foundDelimiter = false;
        $inString = false;
        $stringChar = '';
        $escapeNext = false;
        $len = strlen($line);
        
        for ($i = 0; $i < $len; $i++) {
            $char = $line[$i];
            
            if ($escapeNext) {
                $escapeNext = false;
                continue;
            }
            
            if ($char === '\\') {
                $escapeNext = true;
                continue;
            }
            
            if ($inString) {
                if ($char === $stringChar) {
                    $inString = false;
                }
                continue;
            }
            
            if ($char === "'" || $char === '"' || $char === '`') {
                $inString = true;
                $stringChar = $char;
                continue;
            }
            
            if (!$inString) {
                $delimiterLen = strlen($delimiter);
                if (substr($line, $i, $delimiterLen) === $delimiter) {
                    $foundDelimiter = true;
                    break;
                }
            }
        }
        
        if ($foundDelimiter) {
            $stmt = trim($currentStatement);
            $stmt = rtrim($stmt, $delimiter);
            $stmt = trim($stmt);
            if (!empty($stmt)) {
                $statements[] = $stmt;
            }
            $currentStatement = '';
        }
    }
    
    if (!empty(trim($currentStatement))) {
        $stmt = trim($currentStatement);
        $stmt = rtrim($stmt, $delimiter);
        $stmt = trim($stmt);
        if (!empty($stmt)) {
            $statements[] = $stmt;
        }
    }
    
    return $statements;
}

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sqlFiles = [
        __DIR__ . '/init.sql',
        __DIR__ . '/upgrade_tables.sql'
    ];
    
    $totalStatements = 0;
    $successCount = 0;
    $skipCount = 0;
    $errorCount = 0;
    
    foreach ($sqlFiles as $sqlFile) {
        if (!file_exists($sqlFile)) {
            echo "Warning: SQL file not found: {$sqlFile}\n";
            continue;
        }
        
        echo "\nProcessing: " . basename($sqlFile) . "\n";
        echo "========================================\n";
        
        $sql = file_get_contents($sqlFile);
        $statements = parseSqlFile($sql);
        
        foreach ($statements as $index => $statement) {
            $totalStatements++;
            
            try {
                $connection->exec($statement);
                $successCount++;
                echo "  ✓ Statement " . ($index + 1) . " executed successfully\n";
            } catch (PDOException $e) {
                $errorMsg = $e->getMessage();
                
                $skipErrors = [
                    'Duplicate',
                    'already exists',
                    '1060', // Duplicate column name
                    '1061', // Duplicate key name
                    '1091', // Can't DROP ... check that column/key exists
                    '1304', // Procedure already exists
                    '1305', // Procedure does not exist
                ];
                
                $canSkip = false;
                foreach ($skipErrors as $skipError) {
                    if (strpos($errorMsg, $skipError) !== false) {
                        $canSkip = true;
                        break;
                    }
                }
                
                if ($canSkip) {
                    $skipCount++;
                    echo "  ℹ Statement " . ($index + 1) . " skipped (already exists)\n";
                } else {
                    $errorCount++;
                    echo "  ✗ Statement " . ($index + 1) . " error: " . $errorMsg . "\n";
                    echo "    SQL: " . substr($statement, 0, 100) . "...\n";
                }
            }
        }
    }
    
    echo "\n========================================\n";
    echo "Summary:\n";
    echo "  Total statements: {$totalStatements}\n";
    echo "  Success: {$successCount}\n";
    echo "  Skipped (already exists): {$skipCount}\n";
    echo "  Errors: {$errorCount}\n";
    
    // 验证表是否创建成功
    echo "\n========================================\n";
    echo "Verifying tables...\n";
    $expectedTables = [
        'users',
        'licenses',
        'products',
        'product_relations',
        'plugins',
        'product_plugins',
        'orders',
        'order_items',
        'invoices',
        'device_bindings',
        'license_plugins',
        'upgrade_history'
    ];
    
    $existingTables = $connection->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($expectedTables as $table) {
        if (in_array($table, $existingTables)) {
            echo "  ✓ Table '{$table}' exists\n";
        } else {
            echo "  ✗ Table '{$table}' MISSING!\n";
            $errorCount++;
        }
    }
    
    // 验证基础数据
    echo "\n========================================\n";
    echo "Verifying base data...\n";
    
    $productCount = $connection->query("SELECT COUNT(*) FROM products")->fetchColumn();
    echo "  Products: {$productCount} records\n";
    
    $pluginCount = $connection->query("SELECT COUNT(*) FROM plugins")->fetchColumn();
    echo "  Plugins: {$pluginCount} records\n";
    
    $suiteCount = $connection->query("SELECT COUNT(*) FROM products WHERE type = 'suite'")->fetchColumn();
    echo "  Suites: {$suiteCount} records\n";
    
    if ($errorCount > 0) {
        echo "\n⚠ Warning: {$errorCount} errors occurred. Please check the output above.\n";
        exit(1);
    } else {
        echo "\n✅ Database initialized successfully!\n";
    }
    
} catch (Exception $e) {
    error_log("Database initialization failed: " . $e->getMessage());
    echo "\n❌ Database initialization failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
