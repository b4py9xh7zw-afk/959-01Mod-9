<?php
/**
 * Database initialization script
 * This script can be run manually to initialize the database
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    $sqlFiles = [
        __DIR__ . '/init.sql',
        __DIR__ . '/upgrade_tables.sql'
    ];
    
    foreach ($sqlFiles as $sqlFile) {
        if (!file_exists($sqlFile)) {
            error_log("Warning: SQL file not found: {$sqlFile}");
            continue;
        }
        
        $sql = file_get_contents($sqlFile);
        
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                try {
                    $connection->exec($statement);
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate') === false && 
                        strpos($e->getMessage(), 'already exists') === false &&
                        strpos($e->getMessage(), '1060') === false &&
                        strpos($e->getMessage(), '1061') === false &&
                        strpos($e->getMessage(), '1091') === false) {
                        error_log("SQL execution warning: " . $e->getMessage());
                    }
                }
            }
        }
    }
    
    echo "Database initialized successfully!\n";
} catch (Exception $e) {
    error_log("Database initialization failed: " . $e->getMessage());
    echo "Database initialization failed: " . $e->getMessage() . "\n";
    exit(1);
}
