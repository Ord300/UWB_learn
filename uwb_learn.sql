-- ============================================================
-- UWB.Learn - Base de donnees MySQL (design inchange, 100% PHP)
-- Facultes (ex-filieres) + notifications
-- Import : mysql -u root --default-character-set=utf8mb4 < uwb_learn.sql
-- ============================================================
CREATE DATABASE IF NOT EXISTS uwb_learn
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE uwb_learn;
-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: uwb_learn
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
-- Table structure for table `cours`
--

DROP TABLE IF EXISTS `cours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `enseignant_id` int(11) DEFAULT NULL,
  `enseignant_nom` varchar(150) NOT NULL DEFAULT 'UWB',
  `categorie` varchar(50) NOT NULL DEFAULT 'Informatique',
  `niveau` varchar(10) NOT NULL DEFAULT 'L3',
  `description` text DEFAULT NULL,
  `supports` text DEFAULT NULL COMMENT 'JSON array de strings',
  `seance_date` varchar(32) DEFAULT 'À programmer',
  `meet_link` varchar(255) DEFAULT '',
  `salle` varchar(100) DEFAULT 'Google Meet',
  `inscrits` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT 'forms/portfolio-8.webp',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `faculte` varchar(150) NOT NULL DEFAULT 'Informatique',
  PRIMARY KEY (`id`),
  KEY `enseignant_id` (`enseignant_id`),
  CONSTRAINT `cours_ibfk_1` FOREIGN KEY (`enseignant_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cours`
--

LOCK TABLES `cours` WRITE;
/*!40000 ALTER TABLE `cours` DISABLE KEYS */;
INSERT INTO `cours` VALUES (3,'Comptabilité Générale OHADA',3,'Prof. Aline Nsimba','Gestion','L2','Plan comptable OHADA, journal, grand-livre, balance et états financiers.','[\"Plan OHADA résumé.pdf\"]','2026-10-09 09:00','https://meet.google.com/uwb-ohada-303','Visioconférence Google Meet',55,'forms/portfolio-6.webp','2026-10-04 13:31:40','Informatique'),(5,'Cours d\'Echec',2,'Prof. Mbuyi Kalonji','Informatique','L3','C’est avec une immense joie que nous vous invitons à célébrer notre union et partager ce moment unique rempli d’amour, de bonheur et d’émotions.','[\"Cours d\'echec de marie\"]','2026-10-04 17:50','https://meet.google.com/nym-ankg-swn','Google Meet',0,'forms/portfolio-1.webp','2026-10-04 15:31:30','Informatique');
/*!40000 ALTER TABLE `cours` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluations`
--

DROP TABLE IF EXISTS `evaluations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cours_id` int(11) DEFAULT NULL,
  `titre` varchar(255) NOT NULL,
  `prof_id` int(11) DEFAULT NULL,
  `prof_nom` varchar(150) DEFAULT '',
  `duree` int(11) DEFAULT 30 COMMENT 'minutes',
  `statut` varchar(20) DEFAULT 'publiée',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `faculte` varchar(150) NOT NULL DEFAULT 'Informatique',
  `niveau` varchar(10) NOT NULL DEFAULT 'L3',
  PRIMARY KEY (`id`),
  KEY `cours_id` (`cours_id`),
  KEY `prof_id` (`prof_id`),
  CONSTRAINT `evaluations_ibfk_1` FOREIGN KEY (`cours_id`) REFERENCES `cours` (`id`) ON DELETE SET NULL,
  CONSTRAINT `evaluations_ibfk_2` FOREIGN KEY (`prof_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluations`
--

LOCK TABLES `evaluations` WRITE;
/*!40000 ALTER TABLE `evaluations` DISABLE KEYS */;
INSERT INTO `evaluations` VALUES (3,5,'Quiz echec',2,'Prof. Mbuyi Kalonji',30,'publiée','2026-10-04 16:18:16','Informatique','L3');
/*!40000 ALTER TABLE `evaluations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facultes`
--

DROP TABLE IF EXISTS `facultes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facultes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT '',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facultes`
--

LOCK TABLES `facultes` WRITE;
/*!40000 ALTER TABLE `facultes` DISABLE KEYS */;
INSERT INTO `facultes` VALUES (1,'Informatique','INFO','Programmation, bases de données, réseaux, IA.','2026-10-04 13:31:40'),(3,'Méthodologie','METH','TFE, mémoire, recherche scientifique.','2026-10-04 13:31:40'),(5,'L1 Informatique','INFO 1','Informatique et intelligence artificiel','2026-10-04 14:59:25'),(6,'Médecine','MED','Faculté de Médecine.','2026-10-08 12:25:24'),(7,'ISTM','ISTM','Informatique, Sciences et Technologies de la Management.','2026-10-08 12:25:24'),(8,'Économie','ECO','Faculté d\'Économie.','2026-10-08 12:25:24'),(9,'Communication','COM','Faculté de Communication et Information.','2026-10-08 12:25:24'),(10,'Droit','DRO','Faculté de Droit.','2026-10-08 12:25:24'),(11,'Théologie','THE','Faculté de Théologie.','2026-10-08 12:25:24'),(12,'Gestion','GES','Faculté de Gestion et Comptabilité.','2026-10-08 12:25:24');
/*!40000 ALTER TABLE `facultes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `livres`
--

DROP TABLE IF EXISTS `livres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `livres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `auteur` varchar(150) NOT NULL DEFAULT '',
  `categorie` varchar(50) DEFAULT 'Informatique',
  `annee` int(11) DEFAULT 2024,
  `resume` text DEFAULT NULL,
  `mots` text DEFAULT NULL COMMENT 'mots-cles separes par virgule',
  `cover` varchar(500) DEFAULT '',
  `fichier` varchar(500) DEFAULT '',
  `telechargements` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `faculte` varchar(150) NOT NULL DEFAULT '—',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `ft_livres` (`titre`,`auteur`,`resume`,`mots`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `livres`
--

LOCK TABLES `livres` WRITE;
/*!40000 ALTER TABLE `livres` DISABLE KEYS */;
INSERT INTO `livres` VALUES (1,'Apprendre JavaScript Moderne','M. Lelo','Informatique',2023,'Bases JS, ES6+, DOM et projets web.','javascript,web,programmation,html,css','','',312,'2026-10-04 13:31:40','Informatique'),(4,'Introduction au Machine Learning','A. Ng - adapté UWB','Informatique',2024,'Régression, classification, NLP et évaluation.','ia,machine learning,nlp,correction automatique,python','https://miro.medium.com/v2/resize:fit:1400/1*ikEB53J-pPJCXy1Ub1XUsQ.jpeg','',198,'2026-10-04 13:31:40','Informatique'),(5,'Méthodologie de recherche (TFE & Mémoire)','Dir. Recherche UWB','Méthodologie',2023,'Problématique, revue de littérature, plan de rédaction.','tfe,mémoire,problématique,objectifs','uploads/cov_1791130513_fea235a2.jpg','',341,'2026-10-04 13:31:40','—'),(7,'Cours d\'Echec de Marie','Marie','Gestion',2026,'Apprendre à joué au échec avec monsieur Marie de ordi likindasaka','','https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTtqZhb3T2MWWgFm_CoSCy1Z5srw5AtREs39qy6hY-OUh5lSnzLtcHvoazq&s=10','uploads/doc_1791130893_c3a23d26.pdf',3,'2026-10-04 15:05:13','Gestion');
/*!40000 ALTER TABLE `livres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `message` text DEFAULT NULL,
  `lien` varchar(255) NOT NULL DEFAULT '',
  `lu` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_lu` (`user_id`,`lu`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `evaluation_id` int(11) NOT NULL,
  `qkey` varchar(10) NOT NULL DEFAULT 'q1',
  `type` enum('qcm','ouverte') NOT NULL DEFAULT 'qcm',
  `enonce` text NOT NULL,
  `choix` text DEFAULT NULL COMMENT 'JSON array pour QCM',
  `bonne` varchar(500) NOT NULL DEFAULT '',
  `mots_cles` text DEFAULT NULL COMMENT 'mots-cles separes par virgule pour correction IA',
  `points` int(11) NOT NULL DEFAULT 5,
  PRIMARY KEY (`id`),
  KEY `idx_questions_evaluation` (`evaluation_id`),
  CONSTRAINT `fk_questions_evaluation` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `questions`
--

LOCK TABLES `questions` WRITE;
/*!40000 ALTER TABLE `questions` DISABLE KEYS */;
INSERT INTO `questions` VALUES (7,3,'q1','qcm','1 + 1','[\"2\",\"3\",\"5\",\"Aucune bonne réponse\"]','2','',5);
/*!40000 ALTER TABLE `questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `soumissions`
--

DROP TABLE IF EXISTS `soumissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `soumissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `evaluation_id` int(11) NOT NULL,
  `etudiant_id` int(11) DEFAULT NULL,
  `etudiant_nom` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `reponses` text DEFAULT NULL COMMENT 'JSON {qkey: reponse}',
  `note_ia` int(11) DEFAULT 0,
  `note_finale` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `details` text DEFAULT NULL COMMENT 'JSON details IA',
  `statut` enum('proposee-IA','validee') DEFAULT 'proposee-IA',
  `commentaire_prof` varchar(500) DEFAULT '',
  `date_soumission` date DEFAULT curdate(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `etudiant_id` (`etudiant_id`),
  KEY `idx_eval` (`evaluation_id`),
  KEY `idx_email` (`email`),
  CONSTRAINT `soumissions_ibfk_1` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `soumissions_ibfk_2` FOREIGN KEY (`etudiant_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `soumissions`
--

LOCK TABLES `soumissions` WRITE;
/*!40000 ALTER TABLE `soumissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `soumissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','professeur','etudiant') NOT NULL DEFAULT 'etudiant',
  `faculte` varchar(150) DEFAULT '—',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `niveau` varchar(10) NOT NULL DEFAULT 'L3',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin UWB','admin@uwb.ac.cd','$2y$10$LNObgj1pYzMzo59NMURFPexQdLsVgQj4b8EJPFzcHz5wJn60MrPeS','admin','Administration','2026-10-04 13:31:40','L3'),(2,'Prof. Mbuyi Kalonji','prof@uwb.ac.cd','$2y$10$yuM8kvxMw64rv2NKCVP4v.HnNTYZSIolAWV1CH23L.7S8Fq2ChzxO','professeur','Informatique','2026-10-04 13:31:40','L3'),(3,'Prof. Aline Nsimba','aline@uwb.ac.cd','$2y$10$yuM8kvxMw64rv2NKCVP4v.HnNTYZSIolAWV1CH23L.7S8Fq2ChzxO','professeur','Gestion','2026-10-04 13:31:40','L3'),(4,'Grace Lukusa','etudiant@uwb.ac.cd','$2y$10$ghNw64fKtlQGkOxTtt/UkOuEAywlLDlc4d4eM61FCCX3tILj3U8Bm','etudiant','Informatique','2026-10-04 13:31:40','L3'),(5,'Jean Ilunga','jean@uwb.ac.cd','$2y$10$ghNw64fKtlQGkOxTtt/UkOuEAywlLDlc4d4eM61FCCX3tILj3U8Bm','etudiant','Gestion','2026-10-04 13:31:40','L2'),(6,'Ordi','ordidimbi@gmail.com','$2y$10$n.gBnHZIC34odLkmzO6MveD8vCFPdk/Bgj8RwAcTsgdxaEltdX8Ha','etudiant','Informatique','2026-10-04 15:52:03','L3');
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
