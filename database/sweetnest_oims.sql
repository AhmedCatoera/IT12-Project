-- =============================================================
-- SweetNest Bakeshop - Order & Inventory Management System
-- MySQL Database Dump — phpMyAdmin Import Ready
-- Generated: 2026-09-22 | IT12/L Academic Project
-- =============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+08:00";

-- Create and select the database
CREATE DATABASE IF NOT EXISTS `sweetnest_oims`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `sweetnest_oims`;

-- =============================================================
-- TABLE STRUCTURE
-- =============================================================

-- -------------------------------------------------------
-- users
-- -------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name`       VARCHAR(100)    NOT NULL,
  `middle_name`      VARCHAR(100)    DEFAULT NULL,
  `last_name`        VARCHAR(100)    NOT NULL,
  `email`            VARCHAR(255)    NOT NULL,
  `email_verified_at`TIMESTAMP       DEFAULT NULL,
  `password`         VARCHAR(255)    NOT NULL,
  `role`             ENUM('owner','staff','baker','delivery') NOT NULL DEFAULT 'staff',
  `is_active`        TINYINT(1)      NOT NULL DEFAULT 1,
  `remember_token`   VARCHAR(100)    DEFAULT NULL,
  `created_at`       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- customers
-- -------------------------------------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name`      VARCHAR(100)    NOT NULL,
  `middle_name`     VARCHAR(100)    DEFAULT NULL,
  `last_name`       VARCHAR(100)    NOT NULL,
  `contact_number`  VARCHAR(20)     NOT NULL,
  `facebook_name`   VARCHAR(150)    DEFAULT NULL,
  `address`         TEXT            DEFAULT NULL,
  `created_at`      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_contact_number_unique` (`contact_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- ingredients
-- -------------------------------------------------------
DROP TABLE IF EXISTS `ingredients`;
CREATE TABLE `ingredients` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(255)    NOT NULL,
  `unit`          VARCHAR(50)     NOT NULL,
  `current_stock` DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `reorder_level` DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ingredients_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- orders
-- -------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id`          BIGINT UNSIGNED NOT NULL,
  `created_by`           BIGINT UNSIGNED DEFAULT NULL,
  `order_number`         VARCHAR(50)     NOT NULL,
  `order_date`           DATE            NOT NULL,
  `scheduled_date`       DATETIME        NOT NULL,
  `order_type`           ENUM('pickup','delivery') NOT NULL DEFAULT 'pickup',
  `delivery_address`     TEXT            DEFAULT NULL,
  `status`               ENUM('pending','confirmed','in_production','ready_for_release','out_for_delivery','completed','cancelled') NOT NULL DEFAULT 'pending',
  `total_amount`         DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `down_payment_required`DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `total_paid`           DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `payment_status`       ENUM('unpaid','partially_paid','fully_paid') NOT NULL DEFAULT 'unpaid',
  `notes`                TEXT            DEFAULT NULL,
  `created_at`           TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  KEY `orders_customer_id_foreign` (`customer_id`),
  KEY `orders_created_by_foreign` (`created_by`),
  CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `orders_created_by_foreign`  FOREIGN KEY (`created_by`)  REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- order_items
-- -------------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`        BIGINT UNSIGNED NOT NULL,
  `item_name`       VARCHAR(255)    NOT NULL,
  `item_type`       ENUM('cake','pastry','souvenir','other') NOT NULL DEFAULT 'cake',
  `flavor`          VARCHAR(150)    DEFAULT NULL,
  `size`            VARCHAR(150)    DEFAULT NULL,
  `design_theme`    VARCHAR(255)    DEFAULT NULL,
  `custom_names`    VARCHAR(500)    DEFAULT NULL,
  `reference_image` VARCHAR(500)    DEFAULT NULL,
  `quantity`        INT UNSIGNED    NOT NULL DEFAULT 1,
  `unit_price`      DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `subtotal`        DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `created_at`      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- payments
-- -------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`          BIGINT UNSIGNED NOT NULL,
  `received_by`       BIGINT UNSIGNED DEFAULT NULL,
  `payment_type`      ENUM('down_payment','balance_payment','full_payment') NOT NULL,
  `amount`            DECIMAL(10,2)   NOT NULL,
  `payment_method`    ENUM('cash','gcash','bank_transfer','other') NOT NULL DEFAULT 'cash',
  `payment_date`      DATETIME        NOT NULL,
  `reference_number`  VARCHAR(100)    DEFAULT NULL,
  `notes`             TEXT            DEFAULT NULL,
  `created_at`        TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `payments_order_id_foreign`    (`order_id`),
  KEY `payments_received_by_foreign` (`received_by`),
  CONSTRAINT `payments_order_id_foreign`    FOREIGN KEY (`order_id`)    REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users`  (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- productions
-- -------------------------------------------------------
DROP TABLE IF EXISTS `productions`;
CREATE TABLE `productions` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`          BIGINT UNSIGNED NOT NULL,
  `assigned_baker_id` BIGINT UNSIGNED DEFAULT NULL,
  `production_date`   DATE            NOT NULL,
  `status`            ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `started_at`        DATETIME        DEFAULT NULL,
  `completed_at`      DATETIME        DEFAULT NULL,
  `notes`             TEXT            DEFAULT NULL,
  `created_at`        TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `productions_order_id_foreign`          (`order_id`),
  KEY `productions_assigned_baker_id_foreign` (`assigned_baker_id`),
  CONSTRAINT `productions_order_id_foreign`          FOREIGN KEY (`order_id`)          REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `productions_assigned_baker_id_foreign` FOREIGN KEY (`assigned_baker_id`) REFERENCES `users`  (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- production_ingredients
-- -------------------------------------------------------
DROP TABLE IF EXISTS `production_ingredients`;
CREATE TABLE `production_ingredients` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `production_id`  BIGINT UNSIGNED NOT NULL,
  `ingredient_id`  BIGINT UNSIGNED NOT NULL,
  `quantity_used`  DECIMAL(10,2)   NOT NULL,
  `created_at`     TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prod_ing_unique` (`production_id`, `ingredient_id`),
  KEY `production_ingredients_production_id_foreign`  (`production_id`),
  KEY `production_ingredients_ingredient_id_foreign`  (`ingredient_id`),
  CONSTRAINT `production_ingredients_production_id_foreign`  FOREIGN KEY (`production_id`)  REFERENCES `productions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `production_ingredients_ingredient_id_foreign`  FOREIGN KEY (`ingredient_id`)  REFERENCES `ingredients` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- inventory_transactions
-- -------------------------------------------------------
DROP TABLE IF EXISTS `inventory_transactions`;
CREATE TABLE `inventory_transactions` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ingredient_id`    BIGINT UNSIGNED NOT NULL,
  `created_by`       BIGINT UNSIGNED DEFAULT NULL,
  `transaction_type` ENUM('stock_in','production_usage','audit_adjustment','spoilage') NOT NULL,
  `quantity`         DECIMAL(10,2)   NOT NULL,
  `unit_cost`        DECIMAL(10,2)   DEFAULT NULL,
  `total_cost`       DECIMAL(10,2)   DEFAULT NULL,
  `expiration_date`  DATE            DEFAULT NULL,
  `reference`        VARCHAR(100)    DEFAULT NULL,
  `remarks`          TEXT            DEFAULT NULL,
  `created_at`       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `inventory_transactions_ingredient_id_foreign` (`ingredient_id`),
  KEY `inventory_transactions_created_by_foreign`    (`created_by`),
  CONSTRAINT `inventory_transactions_ingredient_id_foreign` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `inventory_transactions_created_by_foreign`    FOREIGN KEY (`created_by`)    REFERENCES `users`        (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- inventory_audits
-- -------------------------------------------------------
DROP TABLE IF EXISTS `inventory_audits`;
CREATE TABLE `inventory_audits` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conducted_by` BIGINT UNSIGNED DEFAULT NULL,
  `audit_date`   DATE            NOT NULL,
  `audit_shift`  ENUM('morning','afternoon') NOT NULL DEFAULT 'morning',
  `notes`        TEXT            DEFAULT NULL,
  `created_at`   TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `inventory_audits_conducted_by_foreign` (`conducted_by`),
  CONSTRAINT `inventory_audits_conducted_by_foreign` FOREIGN KEY (`conducted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- inventory_audit_items
-- -------------------------------------------------------
DROP TABLE IF EXISTS `inventory_audit_items`;
CREATE TABLE `inventory_audit_items` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `audit_id`       BIGINT UNSIGNED NOT NULL,
  `ingredient_id`  BIGINT UNSIGNED NOT NULL,
  `system_stock`   DECIMAL(10,2)   NOT NULL,
  `physical_stock` DECIMAL(10,2)   NOT NULL,
  `variance`       DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `created_at`     TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `inventory_audit_items_audit_id_foreign`      (`audit_id`),
  KEY `inventory_audit_items_ingredient_id_foreign` (`ingredient_id`),
  CONSTRAINT `inventory_audit_items_audit_id_foreign`      FOREIGN KEY (`audit_id`)      REFERENCES `inventory_audits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_audit_items_ingredient_id_foreign` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients`      (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Laravel system tables (cache, jobs, migrations)
-- -------------------------------------------------------
DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key`        VARCHAR(255) NOT NULL,
  `value`      MEDIUMTEXT   NOT NULL,
  `expiration` INT          NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key`        VARCHAR(255) NOT NULL,
  `owner`      VARCHAR(255) NOT NULL,
  `expiration` INT          NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue`        VARCHAR(255)    NOT NULL,
  `payload`      LONGTEXT        NOT NULL,
  `attempts`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `reserved_at`  INT UNSIGNED    DEFAULT NULL,
  `available_at` INT UNSIGNED    NOT NULL,
  `created_at`   INT UNSIGNED    NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id`             VARCHAR(255) NOT NULL,
  `name`           VARCHAR(255) NOT NULL,
  `total_jobs`     INT          NOT NULL,
  `pending_jobs`   INT          NOT NULL,
  `failed_jobs`    INT          NOT NULL,
  `failed_job_ids` LONGTEXT     NOT NULL,
  `options`        MEDIUMTEXT   DEFAULT NULL,
  `cancelled_at`   INT          DEFAULT NULL,
  `created_at`     INT          NOT NULL,
  `finished_at`    INT          DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`       VARCHAR(255)    NOT NULL,
  `connection` TEXT            NOT NULL,
  `queue`      TEXT            NOT NULL,
  `payload`    LONGTEXT        NOT NULL,
  `exception`  LONGTEXT        NOT NULL,
  `failed_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id`        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `migration` VARCHAR(255)    NOT NULL,
  `batch`     INT             NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` VARCHAR(255)    NOT NULL,
  `tokenable_id`   BIGINT UNSIGNED NOT NULL,
  `name`           VARCHAR(255)    NOT NULL,
  `token`          VARCHAR(64)     NOT NULL,
  `abilities`      TEXT            DEFAULT NULL,
  `last_used_at`   TIMESTAMP       DEFAULT NULL,
  `expires_at`     TIMESTAMP       DEFAULT NULL,
  `created_at`     TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`, `tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- SEED DATA
-- =============================================================

-- -------------------------------------------------------
-- Users  (passwords are bcrypt of 'password')
-- -------------------------------------------------------
INSERT INTO `users`
  (`id`, `first_name`, `middle_name`, `last_name`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`)
VALUES
  (1, 'Maria',  NULL,    'SweetNest',  'owner@sweetnest.com',    '$2y$12$TOQROT724tD3eN/9UxvmT.aEWhIE4WKE2sWUlJAbvWTa4TLcepVZW', 'owner',  1, NOW(), NOW()),
  (2, 'Anna',   'Marie', 'Reyes',      'staff@sweetnest.com',    '$2y$12$TOQROT724tD3eN/9UxvmT.aEWhIE4WKE2sWUlJAbvWTa4TLcepVZW', 'staff',  1, NOW(), NOW());

-- -------------------------------------------------------
-- Customers
-- -------------------------------------------------------
INSERT INTO `customers`
  (`id`, `first_name`, `middle_name`, `last_name`, `contact_number`, `facebook_name`, `address`, `created_at`, `updated_at`)
VALUES
  (1, 'Maria',   'Clara', 'Santos',    '0917-123-4567', 'Maria Santos-Davao', 'Phase 1, Brgy. Sto. Nino, Tugbok, Davao City',         NOW(), NOW()),
  (2, 'Juan',    NULL,    'Dela Cruz', '0920-987-6543', 'Juan DC',            'Km. 7 McArthur Highway, Bangkal, Davao City',          NOW(), NOW()),
  (3, 'Katrina', NULL,    'Alcantara', '0998-555-8899', 'Kat Alcantara',      'Crossing Bayabas, Toril, Davao City',                  NOW(), NOW());

-- -------------------------------------------------------
-- Ingredients
-- -------------------------------------------------------
INSERT INTO `ingredients`
  (`id`, `name`, `unit`, `current_stock`, `reorder_level`, `is_active`, `created_at`, `updated_at`)
VALUES
  (1,  'All-Purpose Flour',               'kg',     50.00,   15.00,  1, NOW(), NOW()),
  (2,  'Refined White Sugar',             'kg',     30.00,   10.00,  1, NOW(), NOW()),
  (3,  'Unsalted Butter',                 'kg',     12.00,    5.00,  1, NOW(), NOW()),
  (4,  'Fresh Farm Eggs',                 'pcs',   120.00,   30.00,  1, NOW(), NOW()),
  (5,  'Fresh Whole Milk',                'liters', 15.00,    6.00,  1, NOW(), NOW()),
  (6,  'Baking Powder',                   'kg',      5.00,    2.00,  1, NOW(), NOW()),
  (7,  'Dark Cocoa Powder',               'kg',      8.00,    3.00,  1, NOW(), NOW()),
  (8,  'Pure Vanilla Extract',            'ml',   1000.00,  250.00,  1, NOW(), NOW()),
  (9,  'Sintra Board Blank Sheets (A4)',  'pcs',   100.00,   25.00,  1, NOW(), NOW()),
  (10, 'Cake Packaging Boxes (8-inch)',   'pcs',     4.00,   10.00,  1, NOW(), NOW());

-- -------------------------------------------------------
-- Inventory Transactions (initial stock-in)
-- -------------------------------------------------------
INSERT INTO `inventory_transactions`
  (`ingredient_id`, `created_by`, `transaction_type`, `quantity`, `unit_cost`, `total_cost`, `expiration_date`, `reference`, `remarks`, `created_at`, `updated_at`)
VALUES
  (1,  1, 'stock_in',  50.00,   65.00,  3250.00, DATE_ADD(CURDATE(), INTERVAL 6  MONTH),  'INITIAL-STOCK', 'Initial stock intake recorded upon system setup', NOW(), NOW()),
  (2,  1, 'stock_in',  30.00,   78.00,  2340.00, DATE_ADD(CURDATE(), INTERVAL 12 MONTH),  'INITIAL-STOCK', 'Initial stock intake recorded upon system setup', NOW(), NOW()),
  (3,  1, 'stock_in',  12.00,  280.00,  3360.00, DATE_ADD(CURDATE(), INTERVAL 2  MONTH),  'INITIAL-STOCK', 'Initial stock intake recorded upon system setup', NOW(), NOW()),
  (4,  1, 'stock_in', 120.00,    8.50,  1020.00, DATE_ADD(CURDATE(), INTERVAL 21 DAY),    'INITIAL-STOCK', 'Initial stock intake recorded upon system setup', NOW(), NOW()),
  (5,  1, 'stock_in',  15.00,   95.00,  1425.00, DATE_ADD(CURDATE(), INTERVAL 3  MONTH),  'INITIAL-STOCK', 'Initial stock intake recorded upon system setup', NOW(), NOW()),
  (6,  1, 'stock_in',   5.00,  120.00,   600.00, DATE_ADD(CURDATE(), INTERVAL 10 MONTH),  'INITIAL-STOCK', 'Initial stock intake recorded upon system setup', NOW(), NOW()),
  (7,  1, 'stock_in',   8.00,  340.00,  2720.00, DATE_ADD(CURDATE(), INTERVAL 8  MONTH),  'INITIAL-STOCK', 'Initial stock intake recorded upon system setup', NOW(), NOW()),
  (8,  1, 'stock_in',1000.00,    0.65,   650.00, DATE_ADD(CURDATE(), INTERVAL 18 MONTH),  'INITIAL-STOCK', 'Initial stock intake recorded upon system setup', NOW(), NOW()),
  (9,  1, 'stock_in', 100.00,   45.00,  4500.00, NULL,                                    'INITIAL-STOCK', 'Initial stock intake recorded upon system setup', NOW(), NOW()),
  (10, 1, 'stock_in',   4.00,   35.00,   140.00, NULL,                                    'INITIAL-STOCK', 'Initial stock intake recorded upon system setup', NOW(), NOW());

-- -------------------------------------------------------
-- Orders
-- -------------------------------------------------------
INSERT INTO `orders`
  (`id`, `customer_id`, `created_by`, `order_number`, `order_date`, `scheduled_date`,
   `order_type`, `delivery_address`, `status`, `total_amount`, `down_payment_required`,
   `total_paid`, `payment_status`, `notes`, `created_at`, `updated_at`)
VALUES
  (1, 1, 2, 'SN-2026-0001', CURDATE(), DATE_ADD(NOW(), INTERVAL 2 DAY),
   'delivery', 'Phase 1, Brgy. Sto. Nino, Tugbok, Davao City',
   'in_production', 1800.00, 900.00, 900.00, 'partially_paid',
   'Please include 7 candles and a cake knife. Fragile delivery.',
   NOW(), NOW()),

  (2, 2, 2, 'SN-2026-0002', CURDATE(), DATE_ADD(NOW(), INTERVAL 1 DAY),
   'pickup', NULL,
   'ready_for_release', 600.00, 300.00, 600.00, 'fully_paid',
   'Customer will pick up at the kitchen counter.',
   NOW(), NOW()),

  (3, 3, 2, 'SN-2026-0003', CURDATE(), DATE_ADD(NOW(), INTERVAL 5 DAY),
   'delivery', 'Crossing Bayabas, Toril, Davao City',
   'confirmed', 1250.00, 625.00, 625.00, 'partially_paid',
   'Individual plastic packaging for each keepsake.',
   NOW(), NOW());

-- -------------------------------------------------------
-- Order Items
-- -------------------------------------------------------
INSERT INTO `order_items`
  (`order_id`, `item_name`, `item_type`, `flavor`, `size`, `design_theme`, `custom_names`,
   `reference_image`, `quantity`, `unit_price`, `subtotal`, `created_at`, `updated_at`)
VALUES
  (1, '2-Tier Customized Birthday Cake',          'cake',    'Chocolate Moist with Salted Caramel',
   '8x4 inch base, 6x4 inch top', 'Pastel Pink Floral Garden', 'Sofia turns 7!',
   NULL, 1, 1800.00, 1800.00, NOW(), NOW()),

  (2, 'Red Velvet Cupcakes with Cream Cheese Frosting', 'pastry', 'Red Velvet',
   'Standard Box of 12', 'White frosting with red crumb dust', NULL,
   NULL, 12, 50.00, 600.00, NOW(), NOW()),

  (3, 'Personalized Sintra Board Standees',        'souvenir', NULL,
   '4x6 inch tabletop', 'Blue & Gold Royal Christening', 'Prince Liam Gabriel - Oct 2026',
   NULL, 25, 50.00, 1250.00, NOW(), NOW());

-- -------------------------------------------------------
-- Payments
-- -------------------------------------------------------
INSERT INTO `payments`
  (`order_id`, `received_by`, `payment_type`, `amount`, `payment_method`,
   `payment_date`, `reference_number`, `notes`, `created_at`, `updated_at`)
VALUES
  (1, 2, 'down_payment',   900.00, 'gcash', NOW(), 'GCASH-98234110',
   '50% down payment received via GCash to confirm booking.', NOW(), NOW()),

  (2, 2, 'full_payment',   600.00, 'cash',  NOW(), 'CASH-REC-001',
   'Full payment made in cash upon placing order.', NOW(), NOW()),

  (3, 2, 'down_payment',   625.00, 'gcash', NOW(), 'GCASH-77182901',
   '50% down payment received via GCash.', NOW(), NOW());

-- -------------------------------------------------------
-- Productions
-- -------------------------------------------------------
INSERT INTO `productions`
  (`id`, `order_id`, `assigned_baker_id`, `production_date`, `status`,
   `started_at`, `completed_at`, `notes`, `created_at`, `updated_at`)
VALUES
  (1, 1, 2, CURDATE(), 'in_progress',
   DATE_SUB(NOW(), INTERVAL 2 HOUR), NULL,
   'Sponge layers baked, chilling before fondant sculpting.',
   NOW(), NOW());

-- -------------------------------------------------------
-- Production Ingredients
-- -------------------------------------------------------
INSERT INTO `production_ingredients`
  (`production_id`, `ingredient_id`, `quantity_used`, `created_at`, `updated_at`)
VALUES
  (1, 1, 1.50, NOW(), NOW()),  -- All-Purpose Flour
  (1, 2, 0.80, NOW(), NOW()),  -- Refined White Sugar
  (1, 3, 0.40, NOW(), NOW()),  -- Unsalted Butter
  (1, 4, 6.00, NOW(), NOW()),  -- Fresh Farm Eggs
  (1, 7, 0.30, NOW(), NOW());  -- Dark Cocoa Powder

-- -------------------------------------------------------
-- Laravel migrations table (tracks which migrations ran)
-- -------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`) VALUES
  ('0001_01_01_000000_create_users_table', 1),
  ('0001_01_01_000001_create_cache_table', 1),
  ('0001_01_01_000002_create_jobs_table',  1),
  ('2026_09_21_000001_create_customers_table',              1),
  ('2026_09_21_000002_create_ingredients_table',            1),
  ('2026_09_21_000003_create_orders_table',                 1),
  ('2026_09_21_000004_create_order_items_table',            1),
  ('2026_09_21_000005_create_payments_table',               1),
  ('2026_09_21_000006_create_productions_table',            1),
  ('2026_09_21_000007_create_production_ingredients_table', 1),
  ('2026_09_21_000008_create_inventory_transactions_table', 1),
  ('2026_09_21_000009_create_inventory_audits_table',       1),
  ('2026_09_21_000010_create_inventory_audit_items_table',  1);

COMMIT;

-- =============================================================
-- IMPORT COMPLETE
-- Login Credentials:
--   Owner:  owner@sweetnest.com  / password
--   Staff:  staff@sweetnest.com  / password
-- =============================================================

