-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: shstorage
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brands`
--

LOCK TABLES `brands` WRITE;
/*!40000 ALTER TABLE `brands` DISABLE KEYS */;
INSERT INTO `brands` VALUES (1,'Nike',NULL,'2026-05-16 13:54:09'),(2,'Adidas',NULL,'2026-05-16 13:54:09'),(3,'Converse',NULL,'2026-05-16 13:54:09');
/*!40000 ALTER TABLE `brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) DEFAULT NULL,
  `shoe_id` int(11) NOT NULL,
  `colorway_id` int(11) DEFAULT NULL,
  `size` varchar(10) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_shoe_cw_size` (`shoe_id`,`colorway_id`,`size`),
  KEY `fk_inv_colorway` (`colorway_id`),
  KEY `fk_inv_branch` (`branch_id`),
  CONSTRAINT `fk_inv_branch` FOREIGN KEY (`branch_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_inv_colorway` FOREIGN KEY (`colorway_id`) REFERENCES `shoe_colorways` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inv_shoe` FOREIGN KEY (`shoe_id`) REFERENCES `shoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=143 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
INSERT INTO `inventory` VALUES (1,NULL,1,1,'6',5,'2026-05-16 14:36:31'),(2,NULL,1,1,'7',5,'2026-05-16 14:14:36'),(3,NULL,1,1,'8',5,'2026-05-16 14:14:36'),(4,NULL,1,1,'9',10,'2026-05-16 14:36:31'),(5,NULL,1,1,'10',5,'2026-05-16 14:14:36'),(6,NULL,1,2,'6',1,'2026-05-16 14:15:39'),(7,NULL,1,2,'7',5,'2026-05-16 14:14:36'),(8,NULL,1,2,'8',5,'2026-05-16 14:14:36'),(9,NULL,1,2,'9',-1,'2026-05-16 14:15:39'),(10,NULL,1,2,'10',5,'2026-05-16 14:14:36'),(11,NULL,2,3,'6',5,'2026-05-17 07:53:19'),(12,NULL,2,3,'7',5,'2026-05-17 07:53:19'),(13,NULL,2,3,'8',5,'2026-05-17 07:53:19'),(14,NULL,2,3,'9',5,'2026-05-17 07:53:19'),(15,NULL,2,3,'10',5,'2026-05-17 07:53:19'),(16,NULL,2,3,'11',5,'2026-05-17 07:53:19'),(17,NULL,2,3,'12',5,'2026-05-17 07:53:19'),(18,NULL,2,3,'13',5,'2026-05-17 07:53:19'),(19,NULL,2,4,'6',5,'2026-05-17 07:53:19'),(20,NULL,2,4,'7',5,'2026-05-17 07:53:19'),(21,NULL,2,4,'8',5,'2026-05-17 07:53:19'),(22,NULL,2,4,'9',5,'2026-05-17 07:53:19'),(23,NULL,2,4,'10',5,'2026-05-17 07:53:19'),(24,NULL,2,4,'11',5,'2026-05-17 07:53:19'),(25,NULL,2,4,'12',5,'2026-05-17 07:53:19'),(26,NULL,2,4,'13',5,'2026-05-17 07:53:19'),(27,NULL,3,5,'6',5,'2026-05-17 08:01:22'),(28,NULL,3,5,'7',5,'2026-05-17 08:01:22'),(29,NULL,3,5,'8',5,'2026-05-17 08:01:22'),(30,NULL,3,5,'9',5,'2026-05-17 08:01:22'),(31,NULL,4,7,'6',5,'2026-05-17 08:04:48'),(32,NULL,4,7,'7',5,'2026-05-17 08:04:48'),(33,NULL,4,7,'8',5,'2026-05-17 08:04:48'),(34,NULL,4,7,'9',5,'2026-05-17 08:04:48'),(35,NULL,4,7,'10',5,'2026-05-17 08:04:48'),(36,NULL,4,8,'6',5,'2026-05-17 08:04:48'),(37,NULL,4,8,'7',5,'2026-05-17 08:04:48'),(38,NULL,4,8,'8',5,'2026-05-17 08:04:48'),(39,NULL,4,8,'9',5,'2026-05-17 08:04:48'),(40,NULL,5,9,'6',5,'2026-05-17 12:39:57'),(41,NULL,5,9,'7',5,'2026-05-17 12:39:57'),(42,NULL,5,9,'8',5,'2026-05-17 12:39:57'),(43,NULL,5,9,'9',5,'2026-05-17 12:39:57'),(44,NULL,5,9,'10',5,'2026-05-17 12:39:57'),(45,NULL,5,10,'6',5,'2026-05-17 12:39:57'),(46,NULL,5,10,'7',5,'2026-05-17 12:39:57'),(47,NULL,5,10,'8',5,'2026-05-17 12:39:57'),(48,NULL,5,10,'9',5,'2026-05-17 12:39:57'),(49,NULL,5,10,'10',5,'2026-05-17 12:39:57'),(50,NULL,6,11,'6',5,'2026-05-17 12:43:09'),(51,NULL,6,11,'7',5,'2026-05-17 12:43:09'),(52,NULL,6,11,'8',5,'2026-05-17 12:43:09'),(53,NULL,6,11,'9',5,'2026-05-17 12:43:09'),(54,NULL,6,11,'10',5,'2026-05-17 12:43:09'),(55,NULL,6,12,'6',5,'2026-05-17 12:43:09'),(56,NULL,6,12,'7',5,'2026-05-17 12:43:09'),(57,NULL,6,12,'8',5,'2026-05-17 12:43:09'),(58,NULL,6,12,'9',5,'2026-05-17 12:43:09'),(59,NULL,6,12,'10',5,'2026-05-17 12:43:09'),(60,NULL,7,13,'6',5,'2026-05-17 12:45:11'),(61,NULL,7,13,'7',5,'2026-05-17 12:45:11'),(62,NULL,7,13,'8',5,'2026-05-17 12:45:12'),(63,NULL,7,13,'9',5,'2026-05-17 12:45:12'),(64,NULL,7,13,'10',5,'2026-05-17 12:45:12'),(65,NULL,7,14,'6',5,'2026-05-17 12:45:54'),(66,NULL,7,14,'7',5,'2026-05-17 12:45:54'),(67,NULL,7,14,'8',5,'2026-05-17 12:45:54'),(68,NULL,7,14,'9',5,'2026-05-17 12:45:54'),(69,NULL,7,14,'10',5,'2026-05-17 12:45:54'),(70,NULL,8,15,'6',5,'2026-05-17 12:51:23'),(71,NULL,8,15,'7',5,'2026-05-17 12:51:23'),(72,NULL,8,15,'8',5,'2026-05-17 12:51:23'),(73,NULL,8,15,'9',5,'2026-05-17 12:51:23'),(74,NULL,8,15,'10',5,'2026-05-17 12:51:23'),(75,NULL,8,16,'6',5,'2026-05-17 12:51:23'),(76,NULL,8,16,'7',5,'2026-05-17 12:51:23'),(77,NULL,8,16,'8',5,'2026-05-17 12:51:23'),(78,NULL,8,16,'9',5,'2026-05-17 12:51:23'),(79,NULL,8,16,'10',5,'2026-05-17 12:51:23'),(80,NULL,9,17,'6',5,'2026-05-17 12:54:40'),(81,NULL,9,17,'7',5,'2026-05-17 12:54:40'),(82,NULL,9,17,'8',5,'2026-05-17 12:54:40'),(83,NULL,9,17,'9',5,'2026-05-17 12:54:40'),(84,NULL,9,17,'10',5,'2026-05-17 12:54:40'),(85,NULL,9,18,'6',5,'2026-05-17 12:54:41'),(86,NULL,9,18,'7',5,'2026-05-17 12:54:41'),(87,NULL,9,18,'8',5,'2026-05-17 12:54:41'),(88,NULL,9,18,'9',5,'2026-05-17 12:54:41'),(89,NULL,9,18,'10',5,'2026-05-17 12:54:41'),(90,NULL,10,19,'6',5,'2026-05-17 12:57:19'),(91,NULL,10,19,'7',5,'2026-05-17 12:57:19'),(92,NULL,10,19,'8',5,'2026-05-17 12:57:19'),(93,NULL,10,19,'9',5,'2026-05-17 12:57:19'),(94,NULL,10,19,'10',5,'2026-05-17 12:57:19'),(95,NULL,10,20,'6',5,'2026-05-17 12:57:19'),(96,NULL,10,20,'7',5,'2026-05-17 12:57:19'),(97,NULL,10,20,'8',5,'2026-05-17 12:57:19'),(98,NULL,10,20,'9',5,'2026-05-17 12:57:19'),(99,NULL,10,20,'10',5,'2026-05-17 12:57:19'),(100,NULL,11,21,'6',5,'2026-05-17 13:01:58'),(101,NULL,11,21,'7',5,'2026-05-17 13:01:58'),(102,NULL,11,21,'8',5,'2026-05-17 13:01:58'),(103,NULL,11,21,'9',5,'2026-05-17 13:01:58'),(104,NULL,11,21,'10',5,'2026-05-17 13:01:58'),(105,NULL,11,22,'6',5,'2026-05-17 13:01:58'),(106,NULL,11,22,'7',5,'2026-05-17 13:01:58'),(107,NULL,11,22,'8',5,'2026-05-17 13:01:58'),(108,NULL,11,22,'9',5,'2026-05-17 13:01:58'),(109,NULL,11,22,'10',5,'2026-05-17 13:01:58'),(110,NULL,12,23,'6',5,'2026-05-17 13:04:52'),(111,NULL,12,23,'7',5,'2026-05-17 13:04:52'),(112,NULL,12,23,'8',5,'2026-05-17 13:04:52'),(113,NULL,12,23,'9',5,'2026-05-17 13:04:52'),(114,NULL,12,23,'10',5,'2026-05-17 13:04:52'),(115,NULL,12,24,'6',5,'2026-05-17 13:04:52'),(116,NULL,12,24,'7',5,'2026-05-17 13:04:52'),(117,NULL,12,24,'8',5,'2026-05-17 13:04:52'),(118,NULL,12,24,'9',5,'2026-05-17 13:04:52'),(119,NULL,12,24,'10',5,'2026-05-17 13:04:52'),(120,NULL,13,25,'6',5,'2026-05-17 13:07:13'),(121,NULL,13,25,'7',5,'2026-05-17 13:07:13'),(122,NULL,13,25,'8',5,'2026-05-17 13:07:13'),(123,NULL,13,25,'9',5,'2026-05-17 13:07:13'),(124,NULL,13,25,'10',5,'2026-05-17 13:07:13'),(125,NULL,14,26,'6',5,'2026-05-17 13:09:31'),(126,NULL,14,26,'7',5,'2026-05-17 13:09:31'),(127,NULL,14,26,'8',5,'2026-05-17 13:09:31'),(128,NULL,14,26,'9',5,'2026-05-17 13:09:31'),(129,NULL,14,26,'10',5,'2026-05-17 13:09:31'),(130,NULL,15,27,'6',5,'2026-05-17 13:12:40'),(131,NULL,15,27,'7',5,'2026-05-17 13:12:40'),(132,NULL,15,27,'8',5,'2026-05-17 13:12:40'),(133,NULL,15,27,'9',5,'2026-05-17 13:12:40'),(134,NULL,15,27,'10',5,'2026-05-17 13:12:40'),(135,NULL,16,28,'6',5,'2026-05-17 13:13:59'),(136,NULL,16,28,'7',5,'2026-05-17 13:13:59'),(137,NULL,16,28,'8',5,'2026-05-17 13:13:59'),(138,NULL,16,28,'9',5,'2026-05-17 13:13:59'),(139,NULL,16,28,'10',5,'2026-05-17 13:13:59'),(140,NULL,16,28,'11',5,'2026-05-17 13:13:59'),(141,NULL,16,28,'12',5,'2026-05-17 13:13:59'),(142,NULL,16,28,'13',5,'2026-05-17 13:13:59');
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `request_items`
--

DROP TABLE IF EXISTS `request_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `request_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `shoe_id` int(11) NOT NULL,
  `colorway_id` int(11) DEFAULT NULL,
  `colorway` varchar(100) DEFAULT NULL,
  `size` varchar(10) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_ri_request` (`request_id`),
  KEY `fk_ri_shoe` (`shoe_id`),
  CONSTRAINT `fk_ri_request` FOREIGN KEY (`request_id`) REFERENCES `stock_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ri_shoe` FOREIGN KEY (`shoe_id`) REFERENCES `shoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `request_items`
--

LOCK TABLES `request_items` WRITE;
/*!40000 ALTER TABLE `request_items` DISABLE KEYS */;
INSERT INTO `request_items` VALUES (1,1,1,1,'White','6',2),(2,1,1,2,'Black','9',3);
/*!40000 ALTER TABLE `request_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shoe_colorways`
--

DROP TABLE IF EXISTS `shoe_colorways`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shoe_colorways` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shoe_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_cw_shoe` (`shoe_id`),
  CONSTRAINT `fk_cw_shoe` FOREIGN KEY (`shoe_id`) REFERENCES `shoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shoe_colorways`
--

LOCK TABLES `shoe_colorways` WRITE;
/*!40000 ALTER TABLE `shoe_colorways` DISABLE KEYS */;
INSERT INTO `shoe_colorways` VALUES (1,1,'White','cw_6a080b4c4eba1.png',0,'2026-05-16 14:14:36'),(2,1,'Black','cw_6a080b4c54c05.png',1,'2026-05-16 14:14:36'),(3,2,'White','cw_6a09036fded3d.png',0,'2026-05-17 07:53:19'),(4,2,'Black','cw_6a09036fe8e9e.png',1,'2026-05-17 07:53:19'),(5,3,'White','cw_6a090552b7777.jpg',0,'2026-05-17 08:01:22'),(6,3,'Black','cw_6a090552bdbf5.jpg',1,'2026-05-17 08:01:22'),(7,4,'White','cw_6a0906202fd1e.jpg',0,'2026-05-17 08:04:48'),(8,4,'Black','cw_6a09062037df1.png',1,'2026-05-17 08:04:48'),(9,5,'White','cw_6a09469d8549a.jpg',0,'2026-05-17 12:39:57'),(10,5,'Black','cw_6a09469d8d847.jpg',1,'2026-05-17 12:39:57'),(11,6,'White','cw_6a09475d0df8c.jpg',0,'2026-05-17 12:43:09'),(12,6,'Black','cw_6a09475d16b8e.jpg',1,'2026-05-17 12:43:09'),(13,7,'White','cw_6a0947d7f1c71.png',0,'2026-05-17 12:45:11'),(14,7,'Black','cw_6a0947f4910d2.jpg',1,'2026-05-17 12:45:40'),(15,8,'White','cw_6a09494b1bc77.jpg',0,'2026-05-17 12:51:23'),(16,8,'Black','cw_6a09494b25d87.jpg',1,'2026-05-17 12:51:23'),(17,9,'White','cw_6a094a10eb88d.jpg',0,'2026-05-17 12:54:40'),(18,9,'Black','cw_6a094a10f3c92.png',1,'2026-05-17 12:54:41'),(19,10,'White','cw_6a094aafa8df7.webp',0,'2026-05-17 12:57:19'),(20,10,'Black','cw_6a094aafada60.png',1,'2026-05-17 12:57:19'),(21,11,'White','cw_6a094bc6cefd8.jpg',0,'2026-05-17 13:01:58'),(22,11,'Black','cw_6a094bc6d5fc1.jpg',1,'2026-05-17 13:01:58'),(23,12,'White','cw_6a094c743221f.jpg',0,'2026-05-17 13:04:52'),(24,12,'Black','cw_6a094c74390a9.png',1,'2026-05-17 13:04:52'),(25,13,'White','cw_6a094d0138749.jpg',0,'2026-05-17 13:07:13'),(26,14,'Black','cw_6a094d8ba0f3a.jpg',0,'2026-05-17 13:09:31'),(27,15,'Black','cw_6a094e48c664d.jpg',0,'2026-05-17 13:12:40'),(28,16,'White','cw_6a094e9731f38.jpg',0,'2026-05-17 13:13:59');
/*!40000 ALTER TABLE `shoe_colorways` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shoes`
--

DROP TABLE IF EXISTS `shoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `model_code` varchar(100) DEFAULT NULL,
  `gender` enum('male','female','unisex') NOT NULL DEFAULT 'unisex',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `is_trending` tinyint(1) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_shoe_brand` (`brand_id`),
  CONSTRAINT `fk_shoe_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shoes`
--

LOCK TABLES `shoes` WRITE;
/*!40000 ALTER TABLE `shoes` DISABLE KEYS */;
INSERT INTO `shoes` VALUES (1,3,'Chuck 70','CON162853C','male',5545.00,'',1,'cw_6a080b4c4eba1.png','2026-05-16 14:14:36'),(2,3,'Chuck Taylor All Star Navy','M9622','male',2500.00,'The Converse Chuck Taylor All Star in Navy is a timeless lifestyle sneaker featuring a durable navy blue cotton canvas upper, the iconic white rubber toe cap, and a vulcanized rubber sole with a contrast pinstripe. Recognized globally by its diamond tread pattern and classic ankle patch (on high-tops), it offers a versatile, casual look that molds to your foot over time.',0,'cw_6a09036fded3d.png','2026-05-17 07:53:19'),(3,3,'Day one classic','A19306C','male',1800.00,'The Converse Day One Classic (or Day One Original) in Black/White reimagines the timeless Chuck Taylor silhouette with a skate-inspired, lightweight build. It features the signature black cotton canvas upper, white rubber toe cap, and contrasting Star Chevron branding. Built for enhanced everyday comfort, it is upgraded with a padded heel collar, an OrthoLite® cushioned sockliner, and a lightweight EVA midsole paired with a durable rubber traction outsole.',0,'cw_6a090552b7777.jpg','2026-05-17 08:01:22'),(4,1,'Air Rift','848386-001','male',7500.00,'Originally debuted in 1996 as a hybrid running shoe inspired by Kenyan barefoot runners, the Nike Air Rift is a unique split-toe sneaker-sandal. It features a lightweight upper (available in breathable mesh or premium leather), adjustable midfoot and heel hook-and-loop straps for a customizable fit, and signature Nike Air cushioning in the heel for all-day comfort.',0,'cw_6a0906202fd1e.jpg','2026-05-17 08:04:48'),(5,1,'AIR JORDAN - Franchise','881472-011','male',5800.00,'A mid-top lifestyle basketball sneaker that blends design elements from classic Air Jordan legacy models (most notably the AJ8 texture and shape). It features a durable leather and textile upper, combined with a comfortable injected unit sole for street-ready cushioning and iconic off-court style.',0,'cw_6a09469d8549a.jpg','2026-05-17 12:39:57'),(6,3,'Hello Kitty Chuck 70','A17695C-100','male',19500.00,'A luxurious and sparkly twist on the classic heavy-duty canvas sneaker. This limited-edition Chuck 70 features a premium cream canvas upper highlighted by a dazzling Swarovski crystal Hello Kitty graphic on the side panel. It balances the playful Sanrio aesthetic with premium streetwear elegance, incorporating vibrant pink hits along the midsole piping, an inner pink lining, a custom bow integrated right onto the rubber toe cap, and a detachable crystal hangtag.',0,'cw_6a09475d0df8c.jpg','2026-05-17 12:43:09'),(7,3,'Omega Trainer','A13378C','male',3200.00,'A sleek, low-top sneaker heavily inspired by classic 1980s running shoes. It masterfully blends retro sport aesthetics with a lightweight design, featuring a premium suede upper with breathable mesh hits, a supportive EVA foam midsole, and an ultra-comfortable OrthoLite sockliner. The iconic Star Chevron branding wraps up a versatile look that easily fits into any casual dress code.',0,'cw_6a0947d7f1c71.png','2026-05-17 12:45:11'),(8,1,'Air Jordan 1','DZ5485-106','male',9000.00,'The ultimate sneaker icon that changed basketball and streetwear forever. Originally debuted in 1985 as Michael Jordan’s first signature game shoe, this throwback classic features premium leather overlays, a clean high-top collar with the historic Wings logo, and a supportive rubber cupsole complete with an encapsulated Air-Sole unit in the heel for comfortable, time-tested cushioning.',0,'cw_6a09494b1bc77.jpg','2026-05-17 12:51:23'),(9,1,'Cortez','DM4044-001','male',5000.00,'Originally designed by Nike co-founder Bill Bowerman for the 1972 Olympics, this is the historic track shoe that grounded the brand. It features a low-cut silhouette, a clean leather or textile upper, a plush foam midsole with the iconic contrast wedge insert for lightweight cushioning, and a herringbone-patterned rubber outsole that delivers vintage style and durable traction.',0,'cw_6a094a10eb88d.jpg','2026-05-17 12:54:40'),(10,1,'ACG AIR Deschutz','FJ1920-100','male',5000.00,'Originally debuting in 1999, this rugged outdoor trail runner has made a massive comeback in the street fashion scene. It features a durable layered upper combining breathable mesh with sturdy leather overlays, a signature neoprene inner sleeve for a glove-like fit, and an iconic web-wrapped midsole that houses an encapsulated Nike Air unit in the heel to deliver exceptional stability and cushioning on both rough trails and city pavements.',0,'cw_6a094aafa8df7.webp','2026-05-17 12:57:19'),(11,2,'Superstar','EG4958','male',5300.00,'Originally introduced in 1969 as a revolutionary basketball shoe and later popularized by hip-hop culture, the adidas Superstar is a legendary streetwear icon. It features a smooth, full-grain leather upper adorned with the signature serrated 3-Stripes and a durable rubber cupsole. Its most defining characteristic is the instantly recognizable rubber \"shell toe,\" which provides distinct style alongside added forefoot protection.',0,'cw_6a094bc6cefd8.jpg','2026-05-17 13:01:58'),(12,2,'Yeezy Boost 350 V2','CP9652','male',13500.00,'Designed by Kanye West, the Yeezy Boost 350 V2 is a landmark silhouette in sneaker culture known for its distinct futuristic, low-top shape. It features a flexible, sock-like Primeknit upper engineered to mold to the foot, often detailed with a signature monofilament side stripe. The upper sits entirely on a translucent TPU cage enclosing a full-length adidas Boost midsole, offering exceptional responsive cushioning and premium comfort.',0,'cw_6a094c743221f.jpg','2026-05-17 13:04:52'),(13,2,'Samba OG','B75807','male',5600.00,'Originally designed in 1949 as an indoor football (soccer) shoe to train on frozen pitches, the Samba OG is one of adidas\' most enduring and iconic low-profile silhouettes. It features a sleek premium leather upper contrasted by a velvety suede T-toe overlay and serrated leather 3-Stripes. The shoe is anchored by its signature, low-slung gum rubber outsole, providing a timeless, vintage terrace aesthetic and lightweight traction.',0,'cw_6a094d0138749.jpg','2026-05-17 13:07:13'),(14,2,'Adilette 22 slides','HP6522','male',3500.00,'Inspired by 3D topography and human expeditions to Mars, the Adilette 22 slides feature a futuristic, multi-layered design resembling a digital terrain map. They are constructed in a one-piece molded structure made entirely from bio-based EVA material containing 25% plant-based content derived from sugarcane. This lightweight composition delivers a soft, contoured footbed for premium slip-on comfort, complete with subtle branding along the lateral midsole.',0,'cw_6a094d8ba0f3a.jpg','2026-05-17 13:09:31'),(15,2,'NMD R1','G28729','male',7500.00,'Launched in 2015 as a pioneer of modern lifestyle sneakers, the NMD R1 blends elements of heritage 1980s racers with cutting-edge tech. It features a sock-like, stretchy textile upper that hugs the foot for a lightweight, breathable feel. The defining feature is the full-length Boost midsole outfitted with signature angled EVA stability plugs, delivering high-rebound cushioning and a distinct, futuristic aesthetic.',0,'cw_6a094e48c664d.jpg','2026-05-17 13:12:40'),(16,2,'Stan smith','FX5502','male',5300.00,'Originally created in 1965 for tennis star Robert Haillet and later renamed after American tennis icon Stan Smith, this silhouette is the epitome of clean, minimalist design. It features a sleek, low-top upper traditionally crafted from smooth leather (now updated with eco-friendly Primegreen recycled materials) accented by perforated 3-Stripes for ventilation. Finished with the signature portrait logo on the tongue and a pop of color on the heel tab, it stands as a versatile wardrobe staple.',0,'cw_6a094e9731f38.jpg','2026-05-17 13:13:59');
/*!40000 ALTER TABLE `shoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_requests`
--

DROP TABLE IF EXISTS `stock_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_req_branch` (`branch_id`),
  CONSTRAINT `fk_req_branch` FOREIGN KEY (`branch_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_requests`
--

LOCK TABLES `stock_requests` WRITE;
/*!40000 ALTER TABLE `stock_requests` DISABLE KEYS */;
INSERT INTO `stock_requests` VALUES (1,2,'approved','','2026-05-16 14:15:15','2026-05-16 14:15:39');
/*!40000 ALTER TABLE `stock_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(80) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','branch') NOT NULL DEFAULT 'branch',
  `branch_name` varchar(150) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin@shstorage.com','$2y$10$GxM1gc1zP/b71T.N.HYfIegV5uUKxBAmQlzDTMhQa7CZ48xJlQrQ.','admin',NULL,'2026-05-16 13:54:09'),(2,'sanpablo','sanpablo@gmail.com','$2y$10$JuUZUL8tRakGgGhaAZUHHuY4QYCgI5hRfTVmxViffqY3P7km/aPSa','branch','San Pablo, Laguna','2026-05-16 14:11:17'),(3,'santotomas','santotomas@gmail.com','$2y$10$vWrT86IpoLBFxTr2XZtkHOBI3fucAj/2RrgmLPeTmWJP2QgmM35py','branch','Santo Tomas, Batangas','2026-05-16 14:33:38');
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

-- Dump completed on 2026-05-18  7:50:48
