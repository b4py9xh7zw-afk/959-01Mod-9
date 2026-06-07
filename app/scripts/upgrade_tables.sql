-- 套餐升级功能所需的数据库表
-- 注意：所有语法兼容 MySQL 8.0

-- 产品表：存储所有产品和套件信息
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    type ENUM('single', 'suite') NOT NULL DEFAULT 'single',
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    duration_days INT NOT NULL DEFAULT 365,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 产品关系表：套件包含的单产品
CREATE TABLE IF NOT EXISTS product_relations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    suite_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (suite_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_suite_id (suite_id),
    INDEX idx_product_id (product_id),
    UNIQUE KEY uk_suite_product (suite_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插件表
CREATE TABLE IF NOT EXISTS plugins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 产品插件关联表
CREATE TABLE IF NOT EXISTS product_plugins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    plugin_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_plugin_id (plugin_id),
    UNIQUE KEY uk_product_plugin (product_id, plugin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 订单表
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_no VARCHAR(50) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    type ENUM('new', 'upgrade', 'renewal', 'downgrade') NOT NULL DEFAULT 'new',
    amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    status ENUM('pending', 'paid', 'cancelled', 'refunded') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_order_no (order_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 订单项目表
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NULL,
    plugin_id INT NULL,
    license_id INT NULL,
    unit_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    quantity INT NOT NULL DEFAULT 1,
    subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE SET NULL,
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE SET NULL,
    INDEX idx_order_id (order_id),
    INDEX idx_product_id (product_id),
    INDEX idx_license_id (license_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 发票表
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) NOT NULL UNIQUE,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    status ENUM('issued', 'paid', 'cancelled', 'refunded') DEFAULT 'issued',
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_user_id (user_id),
    INDEX idx_invoice_no (invoice_no),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 设备绑定表
CREATE TABLE IF NOT EXISTS device_bindings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_id INT NOT NULL,
    device_id VARCHAR(255) NOT NULL,
    device_name VARCHAR(255) NULL,
    device_info TEXT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    bound_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unbound_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE,
    INDEX idx_license_id (license_id),
    INDEX idx_device_id (device_id),
    INDEX idx_status (status),
    UNIQUE KEY uk_license_device (license_id, device_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 许可证插件关联表
CREATE TABLE IF NOT EXISTS license_plugins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_id INT NOT NULL,
    plugin_id INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE,
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    INDEX idx_license_id (license_id),
    INDEX idx_plugin_id (plugin_id),
    UNIQUE KEY uk_license_plugin (license_id, plugin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 升级历史表：记录所有升级和回退操作，保留历史记录
CREATE TABLE IF NOT EXISTS upgrade_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    old_license_id INT NOT NULL,
    new_license_id INT NULL,
    old_product_id INT NOT NULL,
    new_product_id INT NOT NULL,
    order_id INT NULL,
    invoice_id INT NULL,
    type ENUM('upgrade', 'downgrade') NOT NULL,
    old_status ENUM('active', 'inactive', 'expired', 'upgraded', 'downgraded') DEFAULT 'upgraded',
    remaining_days INT NOT NULL DEFAULT 0,
    price_difference DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    old_license_disposal ENUM('retain', 'cancel', 'convert') DEFAULT 'retain',
    plugins_added TEXT NULL,
    plugins_removed TEXT NULL,
    reason TEXT NULL,
    performed_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (old_license_id) REFERENCES licenses(id) ON DELETE CASCADE,
    FOREIGN KEY (new_license_id) REFERENCES licenses(id) ON DELETE SET NULL,
    FOREIGN KEY (old_product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (new_product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_old_license_id (old_license_id),
    INDEX idx_new_license_id (new_license_id),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 使用存储过程安全地添加列（MySQL 不支持 ADD COLUMN IF NOT EXISTS）
DROP PROCEDURE IF EXISTS add_column_if_not_exists;
DELIMITER //
CREATE PROCEDURE add_column_if_not_exists(
    IN table_name VARCHAR(64),
    IN col_name VARCHAR(64),
    IN col_definition TEXT
)
BEGIN
    DECLARE column_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO column_exists
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = table_name
    AND COLUMN_NAME = col_name;
    
    IF column_exists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE ', table_name, ' ADD COLUMN ', col_name, ' ', col_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

-- 使用存储过程安全地添加索引
DROP PROCEDURE IF EXISTS add_index_if_not_exists;
DELIMITER //
CREATE PROCEDURE add_index_if_not_exists(
    IN table_name VARCHAR(64),
    IN index_name VARCHAR(64),
    IN index_definition TEXT
)
BEGIN
    DECLARE index_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO index_exists
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = table_name
    AND INDEX_NAME = index_name;
    
    IF index_exists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE ', table_name, ' ADD INDEX ', index_name, ' ', index_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

-- 使用存储过程安全地添加外键
DROP PROCEDURE IF EXISTS add_foreign_key_if_not_exists;
DELIMITER //
CREATE PROCEDURE add_foreign_key_if_not_exists(
    IN table_name VARCHAR(64),
    IN fk_name VARCHAR(64),
    IN fk_definition TEXT
)
BEGIN
    DECLARE fk_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO fk_exists
    FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = table_name
    AND CONSTRAINT_NAME = fk_name;
    
    IF fk_exists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE ', table_name, ' ADD CONSTRAINT ', fk_name, ' FOREIGN KEY ', fk_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

-- 扩展 licenses 表，添加产品关联和升级相关字段
-- 注意：不使用AFTER子句，避免依赖不存在的列
CALL add_column_if_not_exists('licenses', 'product_id', 'INT NULL');
CALL add_column_if_not_exists('licenses', 'order_id', 'INT NULL');
CALL add_column_if_not_exists('licenses', 'invoice_id', 'INT NULL');
CALL add_column_if_not_exists('licenses', 'original_license_id', 'INT NULL');
CALL add_column_if_not_exists('licenses', 'source_license_id', 'INT NULL');
CALL add_column_if_not_exists('licenses', 'license_type', "ENUM('single', 'suite') DEFAULT 'single'");
CALL add_column_if_not_exists('licenses', 'upgrade_count', 'INT NOT NULL DEFAULT 0');
CALL add_column_if_not_exists('licenses', 'last_upgraded_at', 'TIMESTAMP NULL');

-- 添加索引
CALL add_index_if_not_exists('licenses', 'idx_product_id', '(product_id)');
CALL add_index_if_not_exists('licenses', 'idx_order_id', '(order_id)');
CALL add_index_if_not_exists('licenses', 'idx_invoice_id', '(invoice_id)');
CALL add_index_if_not_exists('licenses', 'idx_original_license_id', '(original_license_id)');
CALL add_index_if_not_exists('licenses', 'idx_license_type', '(license_type)');

-- 添加外键
CALL add_foreign_key_if_not_exists('licenses', 'fk_licenses_product_id', '(product_id) REFERENCES products(id) ON DELETE SET NULL');
CALL add_foreign_key_if_not_exists('licenses', 'fk_licenses_order_id', '(order_id) REFERENCES orders(id) ON DELETE SET NULL');
CALL add_foreign_key_if_not_exists('licenses', 'fk_licenses_invoice_id', '(invoice_id) REFERENCES invoices(id) ON DELETE SET NULL');
CALL add_foreign_key_if_not_exists('licenses', 'fk_licenses_original_id', '(original_license_id) REFERENCES licenses(id) ON DELETE SET NULL');
CALL add_foreign_key_if_not_exists('licenses', 'fk_licenses_source_id', '(source_license_id) REFERENCES licenses(id) ON DELETE SET NULL');

-- 更新 licenses 表的 status 枚举，添加升级和降级状态
-- 先检查当前枚举值，再决定是否需要修改
DROP PROCEDURE IF EXISTS update_license_status_enum;
DELIMITER //
CREATE PROCEDURE update_license_status_enum()
BEGIN
    DECLARE enum_type TEXT;
    
    SELECT COLUMN_TYPE INTO enum_type
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'licenses'
    AND COLUMN_NAME = 'status';
    
    IF enum_type NOT LIKE '%upgraded%' OR enum_type NOT LIKE '%downgraded%' THEN
        ALTER TABLE licenses 
        MODIFY COLUMN status ENUM('active', 'inactive', 'expired', 'upgraded', 'downgraded') DEFAULT 'active';
    END IF;
END //
DELIMITER ;
CALL update_license_status_enum();

-- 清理临时存储过程
DROP PROCEDURE IF EXISTS add_column_if_not_exists;
DROP PROCEDURE IF EXISTS add_index_if_not_exists;
DROP PROCEDURE IF EXISTS add_foreign_key_if_not_exists;
DROP PROCEDURE IF EXISTS update_license_status_enum;

-- 插入示例产品数据
INSERT IGNORE INTO products (name, description, type, price, duration_days, status) VALUES
('Product A', '单产品A - 基础功能', 'single', 299.00, 365, 'active'),
('Product B', '单产品B - 高级功能', 'single', 499.00, 365, 'active'),
('Product C', '单产品C - 专业功能', 'single', 799.00, 365, 'active'),
('Starter Suite', '入门套件 - 包含产品A', 'suite', 399.00, 365, 'active'),
('Professional Suite', '专业套件 - 包含产品A和B', 'suite', 699.00, 365, 'active'),
('Enterprise Suite', '企业套件 - 包含产品A、B、C', 'suite', 1299.00, 365, 'active');

-- 插入套件产品关系
INSERT IGNORE INTO product_relations (suite_id, product_id) VALUES
(4, 1),
(5, 1),
(5, 2),
(6, 1),
(6, 2),
(6, 3);

-- 插入示例插件数据
INSERT IGNORE INTO plugins (name, description, price, status) VALUES
('Plugin X', '高级报表插件', 99.00, 'active'),
('Plugin Y', 'API访问插件', 199.00, 'active'),
('Plugin Z', '团队协作插件', 299.00, 'active');

-- 产品插件关联
INSERT IGNORE INTO product_plugins (product_id, plugin_id) VALUES
(1, 1),
(2, 1),
(2, 2),
(3, 1),
(3, 2),
(3, 3),
(4, 1),
(5, 1),
(5, 2),
(6, 1),
(6, 2),
(6, 3);
