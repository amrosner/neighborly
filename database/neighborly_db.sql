-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: neighborly
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `user_id` int NOT NULL,
  `role` varchar(50) NOT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (9,'super_admin');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_attendees`
--

DROP TABLE IF EXISTS `event_attendees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_attendees` (
  `event_id` int NOT NULL,
  `volunteer_id` int NOT NULL,
  `signup_timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('registered','attended','cancelled') DEFAULT 'registered',
  PRIMARY KEY (`event_id`,`volunteer_id`),
  KEY `idx_event_attendees_volunteer` (`volunteer_id`),
  CONSTRAINT `event_attendees_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  CONSTRAINT `event_attendees_ibfk_2` FOREIGN KEY (`volunteer_id`) REFERENCES `volunteers` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_attendees`
--

LOCK TABLES `event_attendees` WRITE;
/*!40000 ALTER TABLE `event_attendees` DISABLE KEYS */;
INSERT INTO `event_attendees` VALUES (1,1,'2025-12-08 23:06:15','registered'),(1,3,'2025-11-18 15:33:39','registered'),(1,5,'2025-11-18 15:33:39','registered'),(2,1,'2025-12-09 04:07:20','registered'),(2,2,'2025-11-18 15:33:39','registered'),(2,4,'2025-11-18 15:33:39','registered'),(2,5,'2025-11-18 15:33:39','registered'),(3,1,'2025-12-11 13:31:57','registered'),(3,3,'2025-11-18 15:33:39','registered'),(4,1,'2025-12-08 23:06:36','cancelled'),(4,2,'2025-11-18 15:33:39','registered'),(4,4,'2025-11-18 15:33:39','registered'),(5,1,'2025-12-10 22:33:23','registered');
/*!40000 ALTER TABLE `event_attendees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_skills`
--

DROP TABLE IF EXISTS `event_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_skills` (
  `event_id` int NOT NULL,
  `skill_id` int NOT NULL,
  PRIMARY KEY (`event_id`,`skill_id`),
  KEY `skill_id` (`skill_id`),
  KEY `idx_event_skills_event` (`event_id`),
  CONSTRAINT `event_skills_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  CONSTRAINT `event_skills_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_skills`
--

LOCK TABLES `event_skills` WRITE;
/*!40000 ALTER TABLE `event_skills` DISABLE KEYS */;
INSERT INTO `event_skills` VALUES (3,1),(5,1),(6,2),(4,3),(2,4),(4,4),(2,5),(5,5),(1,6),(3,8),(3,10),(6,15);
/*!40000 ALTER TABLE `event_skills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `event_id` int NOT NULL AUTO_INCREMENT,
  `organizer_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image_url` varchar(1000) DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `slots_available` int NOT NULL,
  `is_approved` tinyint(1) DEFAULT '0',
  `status` enum('active','cancelled','completed') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`),
  KEY `idx_events_dates` (`start_date`,`end_date`),
  KEY `idx_events_location` (`location`),
  KEY `idx_events_organizer` (`organizer_id`),
  KEY `idx_events_approved` (`is_approved`),
  CONSTRAINT `events_ibfk_1` FOREIGN KEY (`organizer_id`) REFERENCES `organizers` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
INSERT INTO `events` VALUES (1,6,'Limpieza de Playa Ocean Park','Únete a nosotros para limpiar la playa de Ocean Park y mantener nuestro litoral limpio.','https://media.istockphoto.com/id/2075953855/photo/seascape-with-palm-trees-curved-volcanic-stone-walls-green-garden-coastal-buildings-and.jpg?s=612x612&w=0&k=20&c=6zuRJVHuFGvePqww8A59umwPUS15lAE5QeP1nHUd4Jc=','San Juan','2025-11-15 08:00:00','2025-11-15 12:00:00',20,1,'active','2025-11-18 15:33:39','2025-12-07 16:27:07'),(2,7,'Reforestación Parque Central','Ayúdanos a plantar árboles nativos en el Parque Central de Bayamón.',NULL,'Bayamón','2025-11-20 09:00:00','2025-11-20 13:00:00',15,1,'active','2025-11-18 15:33:39','2025-11-18 15:33:39'),(3,8,'Compañía para Ancianos Hogar Dulce Hogar','Pasa tiempo con residentes del hogar de ancianos, juega dominó y comparte historias.',NULL,'Ponce','2025-11-25 14:00:00','2025-11-25 17:00:00',10,1,'active','2025-11-18 15:33:39','2025-11-18 15:33:39'),(4,6,'Reparación de Viviendas Caño Martín Peña','Ayuda con reparaciones menores en viviendas de la comunidad del Caño Martín Peña.',NULL,'San Juan','2025-12-05 08:30:00','2025-12-05 16:00:00',8,1,'active','2025-11-18 15:33:39','2025-11-18 15:33:39'),(5,7,'Taller de Compostaje Comunitario','Aprende y ayuda a crear un sistema de compostaje para el jardín comunitario.','https://lovefoodhatewaste.co.nz/wp-content/uploads/2018/05/Compost.jpg','Caguas','2025-12-10 10:00:00','2025-12-10 12:00:00',12,1,'active','2025-11-18 15:33:39','2025-12-09 19:57:23'),(6,8,'Distribución de Alimentos Comunidad San José','Ayuda a organizar y distribuir alimentos a familias necesitadas.',NULL,'Carolina','2025-12-12 09:00:00','2025-12-12 14:00:00',15,0,'active','2025-11-18 15:33:39','2025-11-18 15:33:39'),(14,7,'EventTest123','This is a test event for the form.','https://cdn.prod.website-files.com/619e15d781b21202de206fb5/6304ea816823cf0a4b06f777_what-is-testing.jpg','Orocovis','2029-01-03 12:00:00','2029-01-03 15:20:00',20,1,'cancelled','2025-12-08 22:50:37','2025-12-08 23:11:38'),(16,7,'t5hihng','thing',NULL,'Añasco','2029-01-03 12:00:00','2029-01-03 13:30:00',10,1,'cancelled','2025-12-08 23:02:48','2025-12-08 23:11:31');
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organizers`
--

DROP TABLE IF EXISTS `organizers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `organizers` (
  `user_id` int NOT NULL,
  `org_name` varchar(128) NOT NULL,
  `org_description` text,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `organizers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organizers`
--

LOCK TABLES `organizers` WRITE;
/*!40000 ALTER TABLE `organizers` DISABLE KEYS */;
INSERT INTO `organizers` VALUES (6,'Comunidad Puerto Rico','Organización sin fines de lucro dedicada a mejorar comunidades en toda la isla.'),(7,'Ayuda Verde PR','Grupo ambientalista enfocado en reforestación y limpieza de espacios naturales.'),(8,'Sonrisas para Puerto Rico','Organización que provee servicios y compañía a personas de edad avanzada.'),(23,'arielOrgTest',NULL),(24,'PhoneFormattingTest',NULL),(28,'soyyo',NULL);
/*!40000 ALTER TABLE `organizers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `questions` (
  `question_id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `volunteer_id` int NOT NULL,
  `question_text` text NOT NULL,
  `asked_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `answer_text` text,
  `answered_by_organizer_id` int DEFAULT NULL,
  PRIMARY KEY (`question_id`),
  KEY `volunteer_id` (`volunteer_id`),
  KEY `answered_by_organizer_id` (`answered_by_organizer_id`),
  KEY `idx_questions_event` (`event_id`),
  CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  CONSTRAINT `questions_ibfk_2` FOREIGN KEY (`volunteer_id`) REFERENCES `volunteers` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `questions_ibfk_3` FOREIGN KEY (`answered_by_organizer_id`) REFERENCES `organizers` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `questions`
--

LOCK TABLES `questions` WRITE;
/*!40000 ALTER TABLE `questions` DISABLE KEYS */;
INSERT INTO `questions` VALUES (2,1,4,'¿Hay estacionamiento disponible cerca?','2025-11-18 15:33:39','Sí, hay estacionamiento gratuito a 5 minutos caminando de la playa.',6),(3,2,1,'¿Debo traer mis propias herramientas de jardinería?','2025-11-18 15:33:39','Traeremos todas las herramientas, pero si tienes guantes de jardinería favoritos, puedes traerlos.',7),(4,3,5,'¿Hay algún protocolo de vestimenta específico?','2025-11-18 15:33:39','Ropa casual y cómoda está bien. Evita ropa con mensajes ofensivos.',8),(5,4,3,'¿Proveen equipo de seguridad para las reparaciones?','2025-11-18 15:33:39',NULL,NULL),(6,3,1,'Testing, testing, 1-2-3!','2025-12-09 20:23:46','Confirming test ˶ᵔ ᵕ ᵔ˶',8);
/*!40000 ALTER TABLE `questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `skills`
--

DROP TABLE IF EXISTS `skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `skills` (
  `skill_id` int NOT NULL AUTO_INCREMENT,
  `skill_name` varchar(50) NOT NULL,
  PRIMARY KEY (`skill_id`),
  UNIQUE KEY `skill_name` (`skill_name`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `skills`
--

LOCK TABLES `skills` WRITE;
/*!40000 ALTER TABLE `skills` DISABLE KEYS */;
INSERT INTO `skills` VALUES (15,'Administration'),(9,'Animal Care'),(11,'Art'),(7,'Childcare'),(6,'Cleaning'),(4,'Construction'),(2,'Cooking'),(8,'Elderly Care'),(3,'First Aid'),(16,'Fundraising'),(5,'Gardening'),(14,'Language'),(10,'Music'),(12,'Sports'),(1,'Teaching'),(13,'Technology');
/*!40000 ALTER TABLE `skills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_skills`
--

DROP TABLE IF EXISTS `user_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_skills` (
  `user_id` int NOT NULL,
  `skill_id` int NOT NULL,
  PRIMARY KEY (`user_id`,`skill_id`),
  KEY `skill_id` (`skill_id`),
  KEY `idx_user_skills_user` (`user_id`),
  CONSTRAINT `user_skills_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `user_skills_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_skills`
--

LOCK TABLES `user_skills` WRITE;
/*!40000 ALTER TABLE `user_skills` DISABLE KEYS */;
INSERT INTO `user_skills` VALUES (3,1),(4,1),(4,2),(27,2),(2,4),(1,5),(5,5),(5,6),(27,6),(3,7),(1,8),(5,9),(27,9),(3,10),(5,11),(2,13),(27,13),(3,14),(2,15),(4,15);
/*!40000 ALTER TABLE `user_skills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(25) NOT NULL,
  `password_hash` varchar(64) NOT NULL,
  `email` varchar(254) DEFAULT NULL,
  `role` enum('volunteer','organizer','admin') NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'maria_garcia','ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f','maria.garcia@email.com','volunteer','939-123-5459','San Juan','2025-11-18 15:33:39','2025-12-08 14:34:37'),(2,'carlos_rod','ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f','carlos.rodriguez@email.com','volunteer','787-234-5678','Bayamón','2025-11-18 15:33:39','2025-11-18 15:33:39'),(3,'ana_martinez','ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f','ana.martinez@email.com','volunteer','787-345-6789','Ponce','2025-11-18 15:33:39','2025-11-18 15:33:39'),(4,'jose_santos','ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f','jose.santos@email.com','volunteer','787-456-7890','Carolina','2025-11-18 15:33:39','2025-11-18 15:33:39'),(5,'laura_diaz','ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f','laura.diaz@email.com','volunteer','787-567-8901','Caguas','2025-11-18 15:33:39','2025-11-18 15:33:39'),(6,'comunidad_pr','f8085a3c209975769acecd0b3d05fd20181d758f3e259c763855670ce8842278','info@comunidadpr.org','organizer','787-678-9012','San Juan','2025-11-18 15:33:39','2025-11-18 15:33:39'),(7,'ayuda_verde','f8085a3c209975769acecd0b3d05fd20181d758f3e259c763855670ce8842278','contact@ayudaverde.org','organizer','787-789-0123','Ponce','2025-11-18 15:33:39','2025-11-18 15:33:39'),(8,'sonrisas_pr','f8085a3c209975769acecd0b3d05fd20181d758f3e259c763855670ce8842278','volunteer@sonrisaspr.org','organizer','787-890-1234','Bayamón','2025-11-18 15:33:39','2025-11-18 15:33:39'),(9,'admin_neighborly','e4abae53cc1cebe5fe89ea93882c699a5e71ab0bbf42a83b7d833975b61c4a41','admin@neighborly.pr','admin','787-901-2345','San Juan','2025-11-18 15:33:39','2025-11-18 15:33:39'),(22,'arielVolRegTest','ebdade6916636afed81f8cfc8e3485a7fe9a7137089980a638903bdf72825d66',NULL,'volunteer',NULL,NULL,'2025-12-03 16:46:04','2025-12-03 16:46:04'),(23,'arielOrgRegTest','ebdade6916636afed81f8cfc8e3485a7fe9a7137089980a638903bdf72825d66','arielOrgTest@gmail.com','organizer','123-456-7890',NULL,'2025-12-03 16:46:40','2025-12-03 16:46:40'),(24,'PhoneFormattingTest','ebdade6916636afed81f8cfc8e3485a7fe9a7137089980a638903bdf72825d66','PhoneFormattingTest@gmail.com','organizer','787-233-4444',NULL,'2025-12-03 16:50:20','2025-12-03 16:50:20'),(27,'volTest','71be7610f7fe365bde2b7fd0dc9fa47416a48e0b0faf2f0fc4cb31e090c9ad52','volTest@gmail.com','volunteer','123-249-1201','Orocovis','2025-12-08 13:45:46','2025-12-08 13:46:45'),(28,'organizernew','85777f270ad7cf2a790981bbae3c4e484a1dc55e24a77390d692fbf1cffa12fa','soyyo@gmail.com','organizer','939-423-8112',NULL,'2025-12-11 23:46:41','2025-12-11 23:46:41'),(29,'voltest1','854227c171746d2fc48eff095243a8c55dcbc26e9bc086fcc01edb915fa865b8',NULL,'volunteer',NULL,NULL,'2025-12-11 23:47:08','2025-12-11 23:47:08');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `volunteers`
--

DROP TABLE IF EXISTS `volunteers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `volunteers` (
  `user_id` int NOT NULL,
  `first_name` varchar(64) DEFAULT NULL,
  `last_name` varchar(64) DEFAULT NULL,
  `bio` text,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `volunteers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `volunteers`
--

LOCK TABLES `volunteers` WRITE;
/*!40000 ALTER TABLE `volunteers` DISABLE KEYS */;
INSERT INTO `volunteers` VALUES (1,'María','García','Enfermera con experiencia en cuidado de ancianos y primeros auxilios.'),(2,'Carlos','Rodríguez','Ingeniero civil que disfruta trabajos de construcción y reparación.'),(3,'Ana','Martínez','Maestra retirada con pasión por enseñar a niños y adultos.'),(4,'José','Santos','Chef que quiere compartir sus conocimientos de cocina con la comunidad.'),(5,'Laura','Díaz','Estudiante universitaria interesada en protección ambiental y jardinería.'),(22,NULL,NULL,NULL),(27,'Johnny','Test',NULL),(29,NULL,NULL,NULL);
/*!40000 ALTER TABLE `volunteers` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-11 19:48:17
