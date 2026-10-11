-- ============================================================
-- UWB.Learn - Base de donnees MySQL (design inchange, 100% PHP)
-- Facultes (ex-filieres) + notifications + sessions d'evaluation
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
  `seance_fin` varchar(8) NOT NULL DEFAULT '',
  `meet_link` varchar(255) DEFAULT '',
  `enregistrement` varchar(512) DEFAULT '' COMMENT 'Replay vidéo de la séance (YouTube/Drive/MP4)',
  `seance_cloturee` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Séance close par le professeur',
  `salle` varchar(100) DEFAULT 'Google Meet',
  `inscrits` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT 'forms/portfolio-8.webp',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `faculte` varchar(150) NOT NULL DEFAULT 'Informatique',
  PRIMARY KEY (`id`),
  KEY `enseignant_id` (`enseignant_id`),
  CONSTRAINT `cours_ibfk_1` FOREIGN KEY (`enseignant_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cours`
--

LOCK TABLES `cours` WRITE;
/*!40000 ALTER TABLE `cours` DISABLE KEYS */;
/*!40000 ALTER TABLE `cours` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `eval_sessions`
--

DROP TABLE IF EXISTS `eval_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eval_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `evaluation_id` int(11) NOT NULL,
  `etudiant_id` int(11) NOT NULL,
  `tentative` int(11) NOT NULL DEFAULT 1,
  `incidents` int(11) NOT NULL DEFAULT 0,
  `started_at` datetime NOT NULL,
  `deadline_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `etat` enum('en_cours','soumise','triche','expiree') NOT NULL DEFAULT 'en_cours',
  `reponses` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_eval_etud_tent` (`evaluation_id`,`etudiant_id`,`tentative`),
  KEY `idx_etud` (`etudiant_id`),
  CONSTRAINT `fk_sess_etud` FOREIGN KEY (`etudiant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sess_eval` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `eval_events` (journal anti-triche / audit)
--

DROP TABLE IF EXISTS `eval_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eval_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `evaluation_id` int(11) NOT NULL,
  `etudiant_id` int(11) NOT NULL,
  `type` varchar(40) NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ev` (`evaluation_id`,`etudiant_id`),
  KEY `idx_ev_etud` (`etudiant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `eval_sessions`
--

LOCK TABLES `eval_sessions` WRITE;
/*!40000 ALTER TABLE `eval_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `eval_sessions` ENABLE KEYS */;
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
  `ouverture_at` datetime DEFAULT NULL,
  `cloture_at` datetime DEFAULT NULL,
  `statut` varchar(20) DEFAULT 'publiée',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `faculte` varchar(150) NOT NULL DEFAULT 'Informatique',
  `niveau` varchar(10) NOT NULL DEFAULT 'L3',
  `tentatives` int(11) NOT NULL DEFAULT 1,
  `melanger` tinyint(1) NOT NULL DEFAULT 1,
  `plein_ecran` tinyint(1) NOT NULL DEFAULT 1,
  `bloquer_copie` tinyint(1) NOT NULL DEFAULT 1,
  `minuteur_question` int(11) NOT NULL DEFAULT 0,
  `incidents_max` int(11) NOT NULL DEFAULT 3,
  PRIMARY KEY (`id`),
  KEY `cours_id` (`cours_id`),
  KEY `prof_id` (`prof_id`),
  CONSTRAINT `evaluations_ibfk_1` FOREIGN KEY (`cours_id`) REFERENCES `cours` (`id`) ON DELETE SET NULL,
  CONSTRAINT `evaluations_ibfk_2` FOREIGN KEY (`prof_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluations`
--

LOCK TABLES `evaluations` WRITE;
/*!40000 ALTER TABLE `evaluations` DISABLE KEYS */;
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
INSERT INTO `facultes` VALUES (1,'Sciences Informatiques & Intelligence Artificielle','INFO','Programmation, bases de données, réseaux, intelligence artificielle.','2026-10-04 13:31:40'),(2,'Science de l\'Information et de la Communication','COM','Journalisme, communication des organisations et sociale.','2026-10-04 13:31:40'),(3,'Sciences Economiques et de Gestion','ECO','Économie, gestion financière, marketing.','2026-10-04 13:31:40'),(4,'Médecine','MED','Faculté de Médecine (bio-médicale, dentisterie).','2026-10-04 13:31:40'),(5,'Droit','DRO','Droit public, privé et judiciaire, économique et social.','2026-10-04 13:31:40'),(6,'Institut Supérieur des Techniques Médicales','ISTM','Sciences et technologies de la santé.','2026-10-04 13:31:40'),(7,'Théologie','THE','Théologie pastorale et formation des officiers de l\'Armée du Salut.','2026-10-04 13:31:40');
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
INSERT INTO `livres` VALUES (1,'Apprendre JavaScript Moderne','M. Lelo','Informatique',2023,'Bases JS, ES6+, DOM et projets web.','javascript,web,programmation,html,css','','',312,'2026-10-04 13:31:40','Informatique'),(4,'Introduction au Machine Learning','A. Ng - adapté UWB','Informatique',2024,'Régression, classification, NLP et évaluation.','ia,machine learning,nlp,correction automatique,python','https://miro.medium.com/v2/resize:fit:1400/1*ikEB53J-pPJCXy1Ub1XUsQ.jpeg','',198,'2026-10-04 13:31:40','Informatique'),(5,'Méthodologie de recherche (TFE & Mémoire)','Dir. Recherche UWB','Méthodologie',2023,'Problématique, revue de littérature, plan de rédaction.','tfe,mémoire,problématique,objectifs','uploads/cov_1791130513_fea235a2.jpg','',341,'2026-10-04 13:31:40','—');
INSERT INTO `livres` (titre,auteur,categorie,annee,resume,mots,cover,fichier,faculte,telechargements) VALUES
('Introduction à l''algorithmique','Thomas H. Cormen, Charles E. Leiserson, Ronald L. Rivest, Clifford Stein','Informatique',2013,'Bases de l''algorithmique, structures de données, tri, graphes et complexité.','algorithmes,complexité,structures de données,tri,graphes','','','Informatique',0),
('Systèmes de gestion de bases de données','Ramez Elmasri, Shamkant B. Navathe','Informatique',2011,'Modèle relationnel, normalisation, SQL, transactions et conception des bases.','sql,bases de données,merise,normalisation,transactions','','','Informatique',0),
('Réseaux','Andrew S. Tanenbaum, David J. Wetherall','Informatique',2011,'Couches réseau, TCP/IP, routage, commutation et sécurité des communications.','réseau,tcp/ip,routage,protocoles,sécurité','','','Informatique',0),
('Génie logiciel','Ian Sommerville','Informatique',2016,'Cycle de vie, UML, méthodes agiles, tests et qualité logicielle.','génie logiciel,uml,agile,tests','','','Informatique',0),
('Apprendre à programmer avec Python','Gérard Swinnen','Informatique',2012,'Initiation à la programmation en Python : variables, boucles, fonctions et fichiers.','python,programmation,débutant,algorithmique','','','L1 Informatique',0),
('Algorithmique : techniques fondamentales de programmation','Sébastien Rohaut','Informatique',2019,'Notions de base de l''algorithmique illustrées par de nombreux exemples.','algorithmique,programmation,logique','','','L1 Informatique',0),
('Recherche opérationnelle et optimisation','Frederick S. Hillier, Gerald J. Lieberman','ISTM',2010,'Programmation linéaire, files d''attente, simulation et aide à la décision.','recherche opérationnelle,optimisation,simulation','','','ISTM',0),
('Statistique et probabilités pour l''ingénieur','Ronald E. Walpole','ISTM',2016,'Probabilités, distributions, estimation et tests d''hypothèses.','statistiques,probabilités,ingénieur','','','ISTM',0),
('Atlas d''anatomie humaine','Frank H. Netter','Médecine',2019,'Atlas illustré du corps humain : planches anatomiques de référence.','anatomie,corps humain,planches','','','Médecine',0),
('Physiologie médicale','Arthur C. Guyton, John E. Hall','Médecine',2016,'Fonctions des systèmes cardiovasculaire, respiratoire, rénal et nerveux.','physiologie,médecine,systèmes','','','Médecine',0),
('Pharmacologie fondamentale et clinique','Bertram G. Katzung','Médecine',2017,'Mécanismes d''action des médicaments et applications cliniques.','pharmacologie,médicaments,clinique','','','Médecine',0),
('Guide de l''examen clinique','Barbara Bates','Médecine',2014,'Sémiologie et technique de l''examen clinique du patient.','sémiologie,examen clinique,diagnostic','','','Médecine',0),
('Principes de l''économie','N. Gregory Mankiw, Mark P. Taylor','Économie',2019,'Micro et macroéconomie : marchés, croissance, inflation et politiques.','économie,microéconomie,macroéconomie','','','Économie',0),
('Introduction à la microéconomie','Hal R. Varian','Économie',2015,'Consommateur, producteur, marchés et équilibre.','microéconomie,marché,équilibre','','','Économie',0),
('Macroéconomie','Olivier Blanchard, Daniel Cohen','Économie',2020,'PIB, chômage, monnaie et politiques budgétaire et monétaire.','macroéconomie,chômage,monnaie','','','Économie',0),
('Histoire des théories de la communication','Armand Mattelart, Michèle Mattelart','Communication',2004,'Panorama des théories de l''information et de la communication.','communication,théories,médias','','','Communication',0),
('La déontologie des médias','Claude-Jean Bertrand','Communication',2000,'Règles et éthique du travail journalistique et des médias.','journalisme,éthique,médias','','','Communication',0),
('Introduction au droit','Jean-Luc Aubert, Éric Savaux','Droit',2018,'Notions fondamentales : sources du droit, personnes, biens et preuves.','droit,introduction,sources','','','Droit',0),
('Droit constitutionnel','Louis Favoreu et al.','Droit',2020,'Institutions, Constitution et contrôle de constitutionnalité.','droit constitutionnel,institutions,constitution','','','Droit',0),
('Droit des obligations','François Terré, Philippe Simler, Yves Lequette','Droit',2018,'Contrats, responsabilité civile et régime des obligations.','obligations,contrats,responsabilité','','','Droit',0),
('Introduction au Nouveau Testament','Raymond E. Brown','Théologie',2001,'Origines, textes et théologie du Nouveau Testament.','nouveau testament,exégèse,bible','','','Théologie',0),
('Théologie systématique','Wayne Grudem','Théologie',2013,'Doctrines chrétiennes : Dieu, Christ, salut et Église.','théologie,doctrine,christianisme','','','Théologie',0),
('Histoire de l''Église','Jean Comby','Théologie',2007,'Des origines à nos jours : grandes étapes du christianisme.','histoire,église,christianisme','','','Théologie',0),
('Système comptable OHADA (SYSCOHADA révisé)','Collectif OHADA','Gestion',2018,'Plan comptable, comptes et travaux de fin d''exercice selon le référentiel OHADA.','ohada,comptabilité,plan comptable','','','Gestion',0),
('Principes de management','Peter F. Drucker','Gestion',2006,'Fonctions du manager, organisation et prise de décision.','management,organisation,décision','','','Gestion',0),
('Marketing Management','Philip Kotler, Kevin Keller','Gestion',2016,'Stratégie marketing, segmentation, produit et distribution.','marketing,stratégie,commerce','','','Gestion',0),
('Contrôle de gestion','Robert N. Anthony, Vijay Govindarajan','Gestion',2010,'Budgets, coûts, indicateurs et pilotage de la performance.','contrôle de gestion,budget,coûts','','','Gestion',0),
('L''art de la thèse','Michel Beaud','Méthodologie',2006,'Comment préparer et rédiger une thèse, un mémoire ou un rapport.','thèse,mémoire,méthodologie,rédaction','','','Méthodologie',0),
('Manuel de recherche en sciences sociales','Raymond Quivy, Luc Van Campenhoudt','Méthodologie',2011,'Construction de l''objet, hypothèses, terrain et analyse des données.','recherche,sciences sociales,méthode','','','Méthodologie',0);
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
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `questions`
--

LOCK TABLES `questions` WRITE;
/*!40000 ALTER TABLE `questions` DISABLE KEYS */;
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
  `statut` enum('proposee-IA','validee','triche') NOT NULL DEFAULT 'proposee-IA',
  `commentaire_prof` varchar(500) DEFAULT '',
  `date_soumission` date DEFAULT curdate(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `etudiant_id` (`etudiant_id`),
  KEY `idx_eval` (`evaluation_id`),
  KEY `idx_email` (`email`),
  CONSTRAINT `soumissions_ibfk_1` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `soumissions_ibfk_2` FOREIGN KEY (`etudiant_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `matricule` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `matricule` (`matricule`),
  KEY `idx_role` (`role`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`,`nom`,`email`,`password_hash`,`role`,`faculte`,`created_at`,`niveau`,`matricule`) VALUES (1,'Admin UWB','admin@uwb.ac.cd','$2y$10$LNObgj1pYzMzo59NMURFPexQdLsVgQj4b8EJPFzcHz5wJn60MrPeS','admin','Administration','2026-10-04 13:31:40','L3',NULL);
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

-- Dump completed on 2026-10-08 17:44:17
