-- =============================================================
-- SweetNest Homemade Order and Inventory Management System
-- Database: sweetnest_oims  (MySQL 8 / MariaDB, utf8mb4)
--
-- How to use:
--   * phpMyAdmin: Import this file. It creates the sweetnest_oims
--     database with every table, including "sessions".
--   * Or run the Laravel way (recommended, gives fresh sample dates):
--       php artisan migrate:fresh --seed
--
-- Sample logins (password: password)
--   Owner: owner@sweetnest.com
--   Staff: staff@sweetnest.com
--
-- Sample orders are dated around October 11, 2026. Order totals,
-- payment status, stock on hand and count differences are not stored;
-- the system computes them from the item, payment and stock lines.
-- =============================================================

-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: sweetnest_oims
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `sweetnest_oims`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `sweetnest_oims` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `sweetnest_oims`;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `contact_number` varchar(15) NOT NULL,
  `street` varchar(150) DEFAULT NULL,
  `address_line2` varchar(150) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `postal_code` char(4) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `facebook_name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_contact_number_unique` (`contact_number`),
  KEY `customers_is_active_last_name_index` (`is_active`,`last_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES
(1,'Maria','Clara','Santos','09171234567','Blk 7 Lot 3, Narra St.','Phase 1','Santo Niño','Davao City','Davao del Sur','8022',7.0846000,125.5093000,'Maria Santos',1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(2,'Juan',NULL,'Dela Cruz','09209876543','Km. 7 McArthur Highway',NULL,'Bangkal','Davao City','Davao del Sur','8000',7.0476000,125.5667000,'Juan DC',1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(3,'Katrina',NULL,'Alcantara','09985558899','Purok 3, Bayabas Rd.','Crossing Bayabas','Bayabas','Davao City','Davao del Sur','8025',7.0302000,125.5233000,'Kat Alcantara',1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(4,'Paolo',NULL,'Villanueva','09175550123','Blk 2 Lot 15, Dahlia St.',NULL,'Matina Aplaya','Davao City','Davao del Sur','8000',7.0468000,125.5874000,NULL,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(5,'Grace','Lim','Uy','09189990001','Door 3, J.P. Laurel Ave.','Near SM Lanang Premier','Pampanga','Davao City','Davao del Sur','8000',7.1005000,125.6300000,'Grace Uy',1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(6,'Ramon',NULL,'Cabrera','09223334444','Purok 7',NULL,'Buhangin','Davao City','Davao del Sur','8000',NULL,NULL,NULL,0,'2026-10-11 13:50:14','2026-10-11 13:50:14');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ingredients`
--

DROP TABLE IF EXISTS `ingredients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ingredients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `reorder_level` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ingredients_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingredients`
--

LOCK TABLES `ingredients` WRITE;
/*!40000 ALTER TABLE `ingredients` DISABLE KEYS */;
INSERT INTO `ingredients` VALUES
(1,'All-Purpose Flour','kg',15.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(2,'Refined White Sugar','kg',10.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(3,'Unsalted Butter','kg',5.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(4,'Eggs','pcs',30.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(5,'Fresh Milk','L',6.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(6,'Baking Powder','kg',1.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(7,'Cocoa Powder','kg',2.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(8,'Vanilla Extract','mL',250.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(9,'Cream Cheese','kg',2.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(10,'Fondant','kg',3.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(11,'Sintra Board (A4)','sheet',25.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(12,'Cake Boxes (8-inch)','pcs',10.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14');
/*!40000 ALTER TABLE `ingredients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_orders`
--

DROP TABLE IF EXISTS `job_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `production_date` date NOT NULL,
  `status` enum('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_orders_order_id_unique` (`order_id`),
  KEY `job_orders_assigned_to_foreign` (`assigned_to`),
  CONSTRAINT `job_orders_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `job_orders_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_orders`
--

LOCK TABLES `job_orders` WRITE;
/*!40000 ALTER TABLE `job_orders` DISABLE KEYS */;
INSERT INTO `job_orders` VALUES
(1,1,2,'2026-10-10','in_progress','2026-10-11 08:30:00',NULL,NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(2,2,2,'2026-10-10','completed','2026-10-10 09:00:00','2026-10-10 13:40:00',NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(3,5,2,'2026-10-07','completed','2026-10-07 08:00:00','2026-10-07 12:00:00',NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(4,7,2,'2026-10-29','pending',NULL,NULL,NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(5,8,2,'2026-10-11','completed','2026-10-11 07:00:00','2026-10-11 10:30:00',NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14');
/*!40000 ALTER TABLE `job_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2026_09_27_000001_create_customers_table',1),
(5,'2026_09_27_000002_create_products_table',1),
(6,'2026_09_27_000003_create_orders_table',1),
(7,'2026_09_27_000004_create_order_items_table',1),
(8,'2026_09_27_000005_create_payments_table',1),
(9,'2026_09_27_000006_create_job_orders_table',1),
(10,'2026_09_27_000007_create_ingredients_table',1),
(11,'2026_09_27_000008_create_stock_ins_table',1),
(12,'2026_09_27_000009_create_stock_outs_table',1),
(13,'2026_09_27_000010_create_physical_counts_table',1),
(14,'2026_09_27_000011_create_stock_adjustments_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `custom_name` varchar(120) DEFAULT NULL,
  `custom_category` varchar(20) DEFAULT NULL,
  `cake_type` varchar(20) DEFAULT NULL,
  `flavor` varchar(100) DEFAULT NULL,
  `size` varchar(100) DEFAULT NULL,
  `tiers` tinyint(3) unsigned DEFAULT NULL,
  `design_theme` varchar(255) DEFAULT NULL,
  `inscription` varchar(255) DEFAULT NULL,
  `reference_image` varchar(255) DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES
(1,1,2,NULL,NULL,'moist','Chocolate','8x8',2,'Pastel pink floral garden, two tiers','Sofia turns 7!',NULL,1,2500.00,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(2,2,6,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,600.00,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(3,3,10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,25,50.00,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(4,4,1,NULL,NULL,'chiffon','Ube','8x8',1,'Superhero theme, red and blue','Happy 5th Birthday Migo',NULL,1,1300.00,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(5,4,11,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,180.00,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(6,4,NULL,'Number 5 cake topper (fondant)','souvenir',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,250.00,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(7,5,7,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,450.00,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(8,6,5,NULL,NULL,'sponge','Vanilla','3x2 (bento cake)',1,'Minimalist','Happy Anniversary',NULL,2,350.00,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(9,7,3,NULL,NULL,'chiffon','Mocha','8x8',3,'White and gold, fresh flowers, three tiers','Juan and Bea',NULL,1,6500.00,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(10,8,9,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,250.00,'2026-10-11 13:50:14','2026-10-11 13:50:14');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_number` varchar(20) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `order_date` date NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `order_type` enum('pickup','delivery') NOT NULL DEFAULT 'pickup',
  `order_kind` varchar(10) NOT NULL DEFAULT 'regular',
  `rush_fee_percent` decimal(5,2) DEFAULT NULL,
  `delivery_fee` decimal(10,2) DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL,
  `delivery_street` varchar(150) DEFAULT NULL,
  `delivery_address_line2` varchar(150) DEFAULT NULL,
  `delivery_barangay` varchar(100) DEFAULT NULL,
  `delivery_city` varchar(100) DEFAULT NULL,
  `delivery_province` varchar(100) DEFAULT NULL,
  `delivery_postal_code` char(4) DEFAULT NULL,
  `delivery_latitude` decimal(10,7) DEFAULT NULL,
  `delivery_longitude` decimal(10,7) DEFAULT NULL,
  `status` enum('pending','confirmed','in_production','ready_for_release','out_for_delivery','completed','cancelled') NOT NULL DEFAULT 'pending',
  `cancel_reason` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  KEY `orders_customer_id_foreign` (`customer_id`),
  KEY `orders_created_by_foreign` (`created_by`),
  KEY `orders_status_scheduled_at_index` (`status`,`scheduled_at`),
  CONSTRAINT `orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES
(1,'SN-2026-0001',1,2,'2026-10-07','2026-10-11 16:00:00','delivery','regular',NULL,80.00,NULL,NULL,'Blk 7 Lot 3, Narra St.','Phase 1','Santo Niño','Davao City','Davao del Sur','8022',7.0846000,125.5093000,'in_production',NULL,'Please include 7 candles and a cake knife.','2026-10-11 13:50:14','2026-10-11 13:50:14'),
(2,'SN-2026-0002',2,2,'2026-10-08','2026-10-12 10:30:00','pickup','regular',NULL,NULL,NULL,NULL,'Lot 14 Block 5, Sampaguita St.','Tugbok District','Santo Niño','Davao City','Davao del Sur','8000',7.0846000,125.5093000,'ready_for_release',NULL,NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(3,'SN-2026-0003',3,2,'2026-10-09','2026-10-16 09:00:00','delivery','regular',NULL,70.00,NULL,NULL,'Purok 3, Bayabas Rd.','Crossing Bayabas','Bayabas','Davao City','Davao del Sur','8025',7.0302000,125.5233000,'confirmed',NULL,'Individual plastic packaging for each keepsake.','2026-10-11 13:50:14','2026-10-11 13:50:14'),
(4,'SN-2026-0004',4,2,'2026-10-11','2026-10-12 15:00:00','pickup','rush',40.00,NULL,NULL,NULL,'SM City Davao, Quimpo Blvd.','Main entrance','Matina','Davao City','Davao del Sur','8000',7.0512000,125.5947000,'pending',NULL,NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(5,'SN-2026-0005',5,2,'2026-10-03','2026-10-08 11:00:00','pickup','regular',NULL,NULL,NULL,NULL,'Lot 14 Block 5, Sampaguita St.','Tugbok District','Santo Niño','Davao City','Davao del Sur','8000',7.0846000,125.5093000,'completed',NULL,NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(6,'SN-2026-0006',1,2,'2026-10-05','2026-10-10 13:00:00','pickup','regular',NULL,NULL,350.00,'2026-10-06 10:00:00','Lot 14 Block 5, Sampaguita St.','Tugbok District','Santo Niño','Davao City','Davao del Sur','8000',7.0846000,125.5093000,'cancelled','Customer moved the celebration to next month.',NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(7,'SN-2026-0007',2,2,'2026-10-10','2026-10-31 14:00:00','delivery','regular',NULL,150.00,NULL,NULL,'J.P. Laurel Ave.','Function hall, 3rd floor','Bajada','Davao City','Davao del Sur','8000',7.0905000,125.6130000,'confirmed',NULL,'Wedding reception venue. Deliver by 12 noon.','2026-10-11 13:50:14','2026-10-11 13:50:14'),
(8,'SN-2026-0008',5,2,'2026-10-09','2026-10-11 18:00:00','delivery','regular',NULL,60.00,NULL,NULL,'Door 3, J.P. Laurel Ave.','Near SM Lanang Premier','Pampanga','Davao City','Davao del Sur','8000',7.1005000,125.6300000,'out_for_delivery',NULL,NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `received_by` bigint(20) unsigned DEFAULT NULL,
  `payment_type` enum('down_payment','balance_payment','full_payment') NOT NULL,
  `payment_method` enum('cash','gcash') NOT NULL DEFAULT 'cash',
  `amount` decimal(10,2) NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `paid_at` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_order_id_foreign` (`order_id`),
  KEY `payments_received_by_foreign` (`received_by`),
  KEY `payments_paid_at_index` (`paid_at`),
  CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `payments_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES
(1,1,2,'down_payment','gcash',1290.00,'GC-98234110',NULL,'2026-10-07 11:20:00',NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(2,2,2,'full_payment','cash',600.00,NULL,NULL,'2026-10-08 14:05:00',NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(3,3,2,'down_payment','gcash',660.00,'GC-77182901',NULL,'2026-10-09 16:45:00',NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(4,5,2,'down_payment','cash',450.00,NULL,NULL,'2026-10-03 10:00:00',NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(5,5,2,'balance_payment','cash',450.00,NULL,NULL,'2026-10-08 11:10:00',NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(6,6,2,'full_payment','cash',700.00,NULL,NULL,'2026-10-05 15:30:00',NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(7,7,2,'down_payment','gcash',3325.00,'1009231177',NULL,'2026-10-10 17:30:00',NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(8,8,2,'full_payment','gcash',810.00,'GC-11029384',NULL,'2026-10-09 09:15:00',NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `physical_count_items`
--

DROP TABLE IF EXISTS `physical_count_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `physical_count_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `physical_count_id` bigint(20) unsigned NOT NULL,
  `ingredient_id` bigint(20) unsigned NOT NULL,
  `system_qty` decimal(10,2) NOT NULL,
  `counted_qty` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `physical_count_items_physical_count_id_ingredient_id_unique` (`physical_count_id`,`ingredient_id`),
  KEY `physical_count_items_ingredient_id_foreign` (`ingredient_id`),
  CONSTRAINT `physical_count_items_ingredient_id_foreign` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`),
  CONSTRAINT `physical_count_items_physical_count_id_foreign` FOREIGN KEY (`physical_count_id`) REFERENCES `physical_counts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `physical_count_items`
--

LOCK TABLES `physical_count_items` WRITE;
/*!40000 ALTER TABLE `physical_count_items` DISABLE KEYS */;
INSERT INTO `physical_count_items` VALUES
(1,1,1,48.00,48.00,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(2,1,6,3.00,3.00,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(3,1,12,11.00,11.00,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(4,1,7,7.30,7.30,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(5,1,9,4.50,4.50,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(6,1,4,222.00,220.00,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(7,1,10,4.00,4.00,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(8,1,5,14.00,14.00,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(9,1,2,28.30,28.30,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(10,1,11,100.00,100.00,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(11,1,3,16.25,16.25,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(12,1,8,1000.00,1000.00,'2026-10-11 13:50:14','2026-10-11 13:50:14');
/*!40000 ALTER TABLE `physical_count_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `physical_counts`
--

DROP TABLE IF EXISTS `physical_counts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `physical_counts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `count_date` date NOT NULL,
  `shift` enum('morning','night') NOT NULL,
  `counted_by` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `physical_counts_count_date_shift_unique` (`count_date`,`shift`),
  KEY `physical_counts_counted_by_foreign` (`counted_by`),
  CONSTRAINT `physical_counts_counted_by_foreign` FOREIGN KEY (`counted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `physical_counts`
--

LOCK TABLES `physical_counts` WRITE;
/*!40000 ALTER TABLE `physical_counts` DISABLE KEYS */;
INSERT INTO `physical_counts` VALUES
(1,'2026-10-10','night',2,'Two eggs were cracked in the tray.','2026-10-11 13:50:14','2026-10-11 13:50:14');
/*!40000 ALTER TABLE `physical_counts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES
(1,'cake','Custom Birthday Cake (8-inch)',1200.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(2,'cake','2-Tier Celebration Cake',2500.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(3,'cake','3-Tier Wedding Cake',6500.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(4,'cake','Chocolate Moist Cake',850.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(5,'cake','Bento Cake',350.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(6,'pastry','Red Velvet Cupcakes (box of 12)',600.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(7,'pastry','Fudge Brownies (box of 12)',450.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(8,'pastry','Ensaymada (box of 6)',300.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(9,'pastry','Crinkles (box of 20)',250.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(10,'souvenir','Sintra Board Standee',50.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(11,'souvenir','Personalized Mug',180.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(12,'bread','Pandesal (bag of 10)',50.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(13,'bread','Spanish Bread (box of 6)',90.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(14,'bread','Cheese Bread (box of 6)',95.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(15,'bread','Pan de Coco (box of 6)',85.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(16,'souvenir','Refrigerator Magnet',35.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(17,'souvenir','Shot Glass',60.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(18,'souvenir','Keychain',30.00,1,'2026-10-11 13:50:14','2026-10-11 13:50:14');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_adjustment_items`
--

DROP TABLE IF EXISTS `stock_adjustment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_adjustment_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stock_adjustment_id` bigint(20) unsigned NOT NULL,
  `ingredient_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_adjustment_items_stock_adjustment_id_ingredient_id_unique` (`stock_adjustment_id`,`ingredient_id`),
  KEY `stock_adjustment_items_ingredient_id_foreign` (`ingredient_id`),
  CONSTRAINT `stock_adjustment_items_ingredient_id_foreign` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`),
  CONSTRAINT `stock_adjustment_items_stock_adjustment_id_foreign` FOREIGN KEY (`stock_adjustment_id`) REFERENCES `stock_adjustments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_adjustment_items`
--

LOCK TABLES `stock_adjustment_items` WRITE;
/*!40000 ALTER TABLE `stock_adjustment_items` DISABLE KEYS */;
INSERT INTO `stock_adjustment_items` VALUES
(1,1,5,-1.00,'2026-10-11 13:50:14','2026-10-11 13:50:14');
/*!40000 ALTER TABLE `stock_adjustment_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_adjustments`
--

DROP TABLE IF EXISTS `stock_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `physical_count_id` bigint(20) unsigned DEFAULT NULL,
  `reason` enum('spoilage','expired','damaged','count_correction','entry_correction','other') NOT NULL,
  `adjusted_date` date NOT NULL,
  `adjusted_by` bigint(20) unsigned DEFAULT NULL,
  `remarks` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_adjustments_physical_count_id_unique` (`physical_count_id`),
  KEY `stock_adjustments_adjusted_by_foreign` (`adjusted_by`),
  CONSTRAINT `stock_adjustments_adjusted_by_foreign` FOREIGN KEY (`adjusted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_adjustments_physical_count_id_foreign` FOREIGN KEY (`physical_count_id`) REFERENCES `physical_counts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_adjustments`
--

LOCK TABLES `stock_adjustments` WRITE;
/*!40000 ALTER TABLE `stock_adjustments` DISABLE KEYS */;
INSERT INTO `stock_adjustments` VALUES
(1,NULL,'spoilage','2026-10-09',1,'One liter of milk was left out overnight and spoiled.','2026-10-11 13:50:14','2026-10-11 13:50:14');
/*!40000 ALTER TABLE `stock_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_in_items`
--

DROP TABLE IF EXISTS `stock_in_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_in_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stock_in_id` bigint(20) unsigned NOT NULL,
  `ingredient_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_in_items_stock_in_id_ingredient_id_unique` (`stock_in_id`,`ingredient_id`),
  KEY `stock_in_items_ingredient_id_foreign` (`ingredient_id`),
  CONSTRAINT `stock_in_items_ingredient_id_foreign` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`),
  CONSTRAINT `stock_in_items_stock_in_id_foreign` FOREIGN KEY (`stock_in_id`) REFERENCES `stock_ins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_in_items`
--

LOCK TABLES `stock_in_items` WRITE;
/*!40000 ALTER TABLE `stock_in_items` DISABLE KEYS */;
INSERT INTO `stock_in_items` VALUES
(1,1,1,50.00,65.00,'2027-02-11','2026-10-11 13:50:14','2026-10-11 13:50:14'),
(2,1,2,30.00,78.00,'2027-02-11','2026-10-11 13:50:14','2026-10-11 13:50:14'),
(3,1,3,12.00,280.00,'2027-02-11','2026-10-11 13:50:14','2026-10-11 13:50:14'),
(4,1,4,180.00,8.50,'2026-10-29','2026-10-11 13:50:14','2026-10-11 13:50:14'),
(5,1,5,15.00,95.00,'2027-02-11','2026-10-11 13:50:14','2026-10-11 13:50:14'),
(6,1,6,3.00,120.00,'2027-02-11','2026-10-11 13:50:14','2026-10-11 13:50:14'),
(7,1,7,8.00,340.00,'2027-02-11','2026-10-11 13:50:14','2026-10-11 13:50:14'),
(8,1,8,1000.00,0.65,'2027-02-11','2026-10-11 13:50:14','2026-10-11 13:50:14'),
(9,1,9,5.00,320.00,'2027-02-11','2026-10-11 13:50:14','2026-10-11 13:50:14'),
(10,1,10,4.00,210.00,'2027-02-11','2026-10-11 13:50:14','2026-10-11 13:50:14'),
(11,1,11,100.00,45.00,NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(12,1,12,12.00,35.00,NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(13,2,4,60.00,8.50,'2026-11-01','2026-10-11 13:50:14','2026-10-11 13:50:14'),
(14,2,3,5.00,285.00,'2027-01-11','2026-10-11 13:50:14','2026-10-11 13:50:14');
/*!40000 ALTER TABLE `stock_in_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_ins`
--

DROP TABLE IF EXISTS `stock_ins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_ins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `receipt_no` varchar(50) DEFAULT NULL,
  `supplier_name` varchar(100) DEFAULT NULL,
  `received_date` date NOT NULL,
  `received_by` bigint(20) unsigned DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_ins_received_by_foreign` (`received_by`),
  CONSTRAINT `stock_ins_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_ins`
--

LOCK TABLES `stock_ins` WRITE;
/*!40000 ALTER TABLE `stock_ins` DISABLE KEYS */;
INSERT INTO `stock_ins` VALUES
(1,'OR-10231','Davao Baking Supplies','2026-10-01',1,'Opening stock when the system started.','2026-10-11 13:50:14','2026-10-11 13:50:14'),
(2,'OR-10388','Bankerohan Market','2026-10-08',1,NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14');
/*!40000 ALTER TABLE `stock_ins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_out_items`
--

DROP TABLE IF EXISTS `stock_out_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_out_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stock_out_id` bigint(20) unsigned NOT NULL,
  `ingredient_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_out_items_stock_out_id_ingredient_id_unique` (`stock_out_id`,`ingredient_id`),
  KEY `stock_out_items_ingredient_id_foreign` (`ingredient_id`),
  CONSTRAINT `stock_out_items_ingredient_id_foreign` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`),
  CONSTRAINT `stock_out_items_stock_out_id_foreign` FOREIGN KEY (`stock_out_id`) REFERENCES `stock_outs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_out_items`
--

LOCK TABLES `stock_out_items` WRITE;
/*!40000 ALTER TABLE `stock_out_items` DISABLE KEYS */;
INSERT INTO `stock_out_items` VALUES
(1,1,1,0.50,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(2,1,2,0.40,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(3,1,3,0.25,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(4,1,4,4.00,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(5,1,9,0.50,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(6,1,12,1.00,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(7,2,1,0.60,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(8,2,2,0.70,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(9,2,3,0.50,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(10,2,4,8.00,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(11,2,7,0.40,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(12,3,1,0.90,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(13,3,2,0.60,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(14,3,7,0.30,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(15,3,4,6.00,'2026-10-11 13:50:14','2026-10-11 13:50:14');
/*!40000 ALTER TABLE `stock_out_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_outs`
--

DROP TABLE IF EXISTS `stock_outs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_outs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_order_id` bigint(20) unsigned NOT NULL,
  `out_date` date NOT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_outs_job_order_id_foreign` (`job_order_id`),
  KEY `stock_outs_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `stock_outs_job_order_id_foreign` FOREIGN KEY (`job_order_id`) REFERENCES `job_orders` (`id`),
  CONSTRAINT `stock_outs_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_outs`
--

LOCK TABLES `stock_outs` WRITE;
/*!40000 ALTER TABLE `stock_outs` DISABLE KEYS */;
INSERT INTO `stock_outs` VALUES
(1,2,'2026-10-10',2,NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(2,3,'2026-10-07',2,NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(3,5,'2026-10-11',2,NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14');
/*!40000 ALTER TABLE `stock_outs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('owner','staff') NOT NULL DEFAULT 'staff',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Maria',NULL,'Dizon','owner@sweetnest.com',NULL,'$2y$12$EVCyDKmcEOCzqmHtjJmuLeNbbfzppnXka2g8TRCw38wP.syMCRf4q','owner',1,NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14'),
(2,'Anna','Marie','Reyes','staff@sweetnest.com',NULL,'$2y$12$jTDN1NUv/LURgbnH7T/Ad.EhJ5q3dNXtORn7NzEM/0JHRDrHi4Dzq','staff',1,NULL,'2026-10-11 13:50:14','2026-10-11 13:50:14');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
