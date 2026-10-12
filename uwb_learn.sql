-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 11 oct. 2026 à 18:05
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET FOREIGN_KEY_CHECKS=0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `uwb_learn`
--
CREATE DATABASE IF NOT EXISTS `uwb_learn` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `uwb_learn`;

-- Suppression propre (ordre enfant -> parent) pour permettre la réimportation
DROP TABLE IF EXISTS `soumissions`, `questions`, `notifications`, `eval_sessions`, `eval_events`, `evaluations`, `cours`, `livres`, `facultes`, `users`;

-- --------------------------------------------------------

--
-- Structure de la table `cours`
--

CREATE TABLE IF NOT EXISTS `cours` (
  `id` int(11) NOT NULL,
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
  `salle` varchar(100) DEFAULT 'Google Meet',
  `inscrits` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT 'forms/portfolio-8.webp',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `faculte` varchar(150) NOT NULL DEFAULT 'Informatique',
  `enregistrement` varchar(512) NOT NULL DEFAULT '' COMMENT 'Replay video seance',
  `seance_cloturee` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Seance close par le professeur'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `cours`
--

INSERT INTO `cours` (`id`, `titre`, `enseignant_id`, `enseignant_nom`, `categorie`, `niveau`, `description`, `supports`, `seance_date`, `seance_fin`, `meet_link`, `salle`, `inscrits`, `image`, `created_at`, `faculte`, `enregistrement`, `seance_cloturee`) VALUES
(11, 'Introduction a la programmation Web', NULL, 'Prof. Mwamba', 'Informatique', 'L1', 'Premiers pas HTML, CSS et JavaScript', '[\"cours-web.pdf\"]', '2026-10-05 10:00', '11:00', 'https://meet.google.com/uwb-demo', 'Google Meet', 25, 'forms/portfolio-1.webp', '2026-10-09 21:51:42', 'Sciences Informatiques & Intelligence Artificielle', 'https://www.youtube.com/watch?v=k1lJ6Bjq8XQ', 0),
(12, 'Algorithmique', 13, 'CHRYS NGEMBO', 'Informatique', 'L3', '', '[]', '2026-10-10 10:35', '10:45', 'https://meet.google.com/mvi-psfr-rmq', 'Google Meet', 0, 'forms/portfolio-1.webp', '2026-10-10 09:32:10', 'Sciences Economiques et de Gestion', '', 0),
(13, 'MATH', 13, 'CHRYS NGEMBO', 'Informatique', 'L3', '', '[]', '2026-10-10 10:45', '10:50', 'https://meet.google.com/mvi-psfr-rmq', 'Google Meet', 0, 'forms/portfolio-1.webp', '2026-10-10 09:41:11', 'Sciences Economiques et de Gestion', '', 1);

-- --------------------------------------------------------

--
-- Structure de la table `evaluations`
--

CREATE TABLE IF NOT EXISTS `evaluations` (
  `id` int(11) NOT NULL,
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
  `incidents_max` int(11) NOT NULL DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `evaluations`
--

INSERT INTO `evaluations` (`id`, `cours_id`, `titre`, `prof_id`, `prof_nom`, `duree`, `ouverture_at`, `cloture_at`, `statut`, `created_at`, `faculte`, `niveau`, `tentatives`, `melanger`, `plein_ecran`, `bloquer_copie`, `minuteur_question`, `incidents_max`) VALUES
(11, NULL, 'ZZ TEST ANTI-TRICHE', 13, 'CHRYS NGEMBO', 30, NULL, NULL, 'publiée', '2026-10-10 18:29:45', 'Sciences Informatiques & Intelligence Artificielle', 'L3', 2, 1, 1, 1, 0, 2),
(17, 13, 'Quiz mathematique', 13, 'CHRYS NGEMBO', 30, '2026-10-11 10:15:00', '2026-10-11 10:20:00', 'publiée', '2026-10-11 09:12:32', 'Sciences Economiques et de Gestion', 'L3', 2, 1, 1, 1, 20, 3),
(18, 13, 'Quiz mathematique', 13, 'CHRYS NGEMBO', 30, '2026-10-11 10:15:00', '2026-10-11 10:20:00', 'publiée', '2026-10-11 09:12:34', 'Sciences Economiques et de Gestion', 'L3', 2, 1, 1, 1, 20, 3);

-- --------------------------------------------------------

--
-- Structure de la table `eval_events`
--

CREATE TABLE IF NOT EXISTS `eval_events` (
  `id` int(11) NOT NULL,
  `evaluation_id` int(11) NOT NULL,
  `etudiant_id` int(11) NOT NULL,
  `type` varchar(40) NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `eval_events`
--

INSERT INTO `eval_events` (`id`, `evaluation_id`, `etudiant_id`, `type`, `details`, `created_at`) VALUES
(3, 17, 17, 'perte_focus', 'La fenêtre d\'évaluation a perdu le focus', '2026-10-11 09:16:56'),
(4, 17, 17, 'perte_focus', 'La fenêtre d\'évaluation a perdu le focus', '2026-10-11 09:17:00'),
(5, 17, 17, 'perte_focus', 'La fenêtre d\'évaluation a perdu le focus', '2026-10-11 09:17:05'),
(6, 17, 17, 'perte_focus', 'La fenêtre d\'évaluation a perdu le focus', '2026-10-11 09:17:41'),
(7, 17, 17, 'perte_focus', 'La fenêtre d\'évaluation a perdu le focus', '2026-10-11 09:17:45'),
(8, 17, 17, 'perte_focus', 'La fenêtre d\'évaluation a perdu le focus', '2026-10-11 09:17:53'),
(9, 17, 18, 'perte_focus', 'La fenêtre d\'évaluation a perdu le focus', '2026-10-11 09:19:57'),
(10, 17, 18, 'perte_focus', 'La fenêtre d\'évaluation a perdu le focus', '2026-10-11 09:19:59');

-- --------------------------------------------------------

--
-- Structure de la table `eval_sessions`
--

CREATE TABLE IF NOT EXISTS `eval_sessions` (
  `id` int(11) NOT NULL,
  `evaluation_id` int(11) NOT NULL,
  `etudiant_id` int(11) NOT NULL,
  `started_at` datetime NOT NULL,
  `deadline_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `etat` enum('en_cours','soumise','triche','expiree') NOT NULL DEFAULT 'en_cours',
  `reponses` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `incidents` int(11) NOT NULL DEFAULT 0,
  `tentative` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `eval_sessions`
--

INSERT INTO `eval_sessions` (`id`, `evaluation_id`, `etudiant_id`, `started_at`, `deadline_at`, `ended_at`, `etat`, `reponses`, `created_at`, `incidents`, `tentative`) VALUES
(44, 17, 17, '2026-10-11 10:16:48', '2026-10-11 10:20:00', '2026-10-11 10:17:06', 'triche', '[]', '2026-10-11 09:16:48', 3, 1),
(45, 17, 17, '2026-10-11 10:17:22', '2026-10-11 10:20:00', '2026-10-11 10:17:53', 'triche', '[]', '2026-10-11 09:17:22', 3, 2),
(46, 17, 18, '2026-10-11 10:19:22', '2026-10-11 10:20:00', '2026-10-11 10:20:01', 'expiree', '{\"q2\":\"246\",\"q3\":\"Aucune bonne réponse\",\"q1\":\"2\"}', '2026-10-11 09:19:22', 2, 1);

-- --------------------------------------------------------

--
-- Structure de la table `facultes`
--

CREATE TABLE IF NOT EXISTS `facultes` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT '',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `facultes`
--

INSERT INTO `facultes` (`id`, `nom`, `code`, `description`, `created_at`) VALUES
(1, 'Sciences Informatiques & Intelligence Artificielle', 'INFO', 'Programmation, bases de données, réseaux, intelligence artificielle.', '2026-10-04 13:31:40'),
(6, 'Médecine', 'MED', 'Faculté de Médecine (bio-médicale, dentisterie).', '2026-10-08 12:25:24'),
(7, 'Institut Supérieur des Techniques Médicales', 'ISTM', 'Sciences et technologies de la santé.', '2026-10-08 12:25:24'),
(8, 'Sciences Economiques et de Gestion', 'ECO', 'Économie, gestion financière, marketing.', '2026-10-08 12:25:24'),
(9, 'Science de l\'Information et de la Communication', 'COM', 'Journalisme, communication des organisations et communication sociale.', '2026-10-08 12:25:24'),
(10, 'Droit', 'DRO', 'Droit public, droit privé et judiciaire, droit économique et social.', '2026-10-08 12:25:24'),
(11, 'Théologie', 'THE', 'Théologie pastorale et formation des officiers de l\'Armée du Salut.', '2026-10-08 12:25:24');

-- --------------------------------------------------------

--
-- Structure de la table `livres`
--

CREATE TABLE IF NOT EXISTS `livres` (
  `id` int(11) NOT NULL,
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
  `faculte` varchar(150) NOT NULL DEFAULT '—'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `livres`
--

INSERT INTO `livres` (`id`, `titre`, `auteur`, `categorie`, `annee`, `resume`, `mots`, `cover`, `fichier`, `telechargements`, `created_at`, `faculte`) VALUES
(1, 'Apprendre JavaScript Moderne', 'M. Lelo', 'Informatique', 2023, 'Bases JS, ES6+, DOM et projets web.', 'javascript,web,programmation,html,css', '', '', 312, '2026-10-04 13:31:40', 'Informatique'),
(4, 'Introduction au Machine Learning', 'A. Ng - adapté UWB', 'Informatique', 2024, 'Régression, classification, NLP et évaluation.', 'ia,machine learning,nlp,correction automatique,python', 'https://miro.medium.com/v2/resize:fit:1400/1*ikEB53J-pPJCXy1Ub1XUsQ.jpeg', '', 198, '2026-10-04 13:31:40', 'Informatique'),
(5, 'Méthodologie de recherche (TFE & Mémoire)', 'Dir. Recherche UWB', 'Méthodologie', 2023, 'Problématique, revue de littérature, plan de rédaction.', 'tfe,mémoire,problématique,objectifs', 'uploads/cov_1791130513_fea235a2.jpg', '', 341, '2026-10-04 13:31:40', '—'),
(37, 'Introduction à l\'algorithmique', 'Thomas H. Cormen, Charles E. Leiserson, Ronald L. Rivest, Clifford Stein', 'Informatique', 2013, 'Bases de l\'algorithmique, structures de données, tri, graphes et complexité.', 'algorithmes,complexité,structures de données,tri,graphes', '', '', 0, '2026-10-08 22:41:12', 'Informatique'),
(38, 'Systèmes de gestion de bases de données', 'Ramez Elmasri, Shamkant B. Navathe', 'Informatique', 2011, 'Modèle relationnel, normalisation, SQL, transactions et conception des bases.', 'sql,bases de données,merise,normalisation,transactions', '', '', 0, '2026-10-08 22:41:12', 'Informatique'),
(39, 'Réseaux', 'Andrew S. Tanenbaum, David J. Wetherall', 'Informatique', 2011, 'Couches réseau, TCP/IP, routage, commutation et sécurité des communications.', 'réseau,tcp/ip,routage,protocoles,sécurité', '', '', 0, '2026-10-08 22:41:12', 'Informatique'),
(40, 'Génie logiciel', 'Ian Sommerville', 'Informatique', 2016, 'Cycle de vie, UML, méthodes agiles, tests et qualité logicielle.', 'génie logiciel,uml,agile,tests', '', '', 0, '2026-10-08 22:41:12', 'Informatique'),
(41, 'Apprendre à programmer avec Python', 'Gérard Swinnen', 'Informatique', 2012, 'Initiation à la programmation en Python : variables, boucles, fonctions et fichiers.', 'python,programmation,débutant,algorithmique', '', '', 0, '2026-10-08 22:41:12', 'L1 Informatique'),
(42, 'Algorithmique : techniques fondamentales de programmation', 'Sébastien Rohaut', 'Informatique', 2019, 'Notions de base de l\'algorithmique illustrées par de nombreux exemples.', 'algorithmique,programmation,logique', '', '', 0, '2026-10-08 22:41:12', 'L1 Informatique'),
(43, 'Recherche opérationnelle et optimisation', 'Frederick S. Hillier, Gerald J. Lieberman', 'ISTM', 2010, 'Programmation linéaire, files d\'attente, simulation et aide à la décision.', 'recherche opérationnelle,optimisation,simulation', '', '', 0, '2026-10-08 22:41:12', 'ISTM'),
(44, 'Statistique et probabilités pour l\'ingénieur', 'Ronald E. Walpole', 'ISTM', 2016, 'Probabilités, distributions, estimation et tests d\'hypothèses.', 'statistiques,probabilités,ingénieur', '', '', 0, '2026-10-08 22:41:12', 'ISTM'),
(45, 'Atlas d\'anatomie humaine', 'Frank H. Netter', 'Médecine', 2019, 'Atlas illustré du corps humain : planches anatomiques de référence.', 'anatomie,corps humain,planches', '', '', 0, '2026-10-08 22:41:12', 'Médecine'),
(46, 'Physiologie médicale', 'Arthur C. Guyton, John E. Hall', 'Médecine', 2016, 'Fonctions des systèmes cardiovasculaire, respiratoire, rénal et nerveux.', 'physiologie,médecine,systèmes', '', '', 0, '2026-10-08 22:41:12', 'Médecine'),
(47, 'Pharmacologie fondamentale et clinique', 'Bertram G. Katzung', 'Médecine', 2017, 'Mécanismes d\'action des médicaments et applications cliniques.', 'pharmacologie,médicaments,clinique', '', '', 0, '2026-10-08 22:41:12', 'Médecine'),
(48, 'Guide de l\'examen clinique', 'Barbara Bates', 'Médecine', 2014, 'Sémiologie et technique de l\'examen clinique du patient.', 'sémiologie,examen clinique,diagnostic', '', '', 0, '2026-10-08 22:41:12', 'Médecine'),
(49, 'Principes de l\'économie', 'N. Gregory Mankiw, Mark P. Taylor', 'Économie', 2019, 'Micro et macroéconomie : marchés, croissance, inflation et politiques.', 'économie,microéconomie,macroéconomie', '', '', 0, '2026-10-08 22:41:12', 'Économie'),
(50, 'Introduction à la microéconomie', 'Hal R. Varian', 'Économie', 2015, 'Consommateur, producteur, marchés et équilibre.', 'microéconomie,marché,équilibre', '', '', 0, '2026-10-08 22:41:12', 'Économie'),
(51, 'Macroéconomie', 'Olivier Blanchard, Daniel Cohen', 'Économie', 2020, 'PIB, chômage, monnaie et politiques budgétaire et monétaire.', 'macroéconomie,chômage,monnaie', '', '', 0, '2026-10-08 22:41:12', 'Économie'),
(52, 'Histoire des théories de la communication', 'Armand Mattelart, Michèle Mattelart', 'Communication', 2004, 'Panorama des théories de l\'information et de la communication.', 'communication,théories,médias', '', '', 0, '2026-10-08 22:41:12', 'Communication'),
(53, 'La déontologie des médias', 'Claude-Jean Bertrand', 'Communication', 2000, 'Règles et éthique du travail journalistique et des médias.', 'journalisme,éthique,médias', '', '', 0, '2026-10-08 22:41:12', 'Communication'),
(54, 'Introduction au droit', 'Jean-Luc Aubert, Éric Savaux', 'Droit', 2018, 'Notions fondamentales : sources du droit, personnes, biens et preuves.', 'droit,introduction,sources', '', '', 0, '2026-10-08 22:41:12', 'Droit'),
(55, 'Droit constitutionnel', 'Louis Favoreu et al.', 'Droit', 2020, 'Institutions, Constitution et contrôle de constitutionnalité.', 'droit constitutionnel,institutions,constitution', '', '', 0, '2026-10-08 22:41:12', 'Droit'),
(56, 'Droit des obligations', 'François Terré, Philippe Simler, Yves Lequette', 'Droit', 2018, 'Contrats, responsabilité civile et régime des obligations.', 'obligations,contrats,responsabilité', '', '', 0, '2026-10-08 22:41:12', 'Droit'),
(57, 'Introduction au Nouveau Testament', 'Raymond E. Brown', 'Théologie', 2001, 'Origines, textes et théologie du Nouveau Testament.', 'nouveau testament,exégèse,bible', '', '', 0, '2026-10-08 22:41:12', 'Théologie'),
(58, 'Théologie systématique', 'Wayne Grudem', 'Théologie', 2013, 'Doctrines chrétiennes : Dieu, Christ, salut et Église.', 'théologie,doctrine,christianisme', '', '', 0, '2026-10-08 22:41:12', 'Théologie'),
(59, 'Histoire de l\'Église', 'Jean Comby', 'Théologie', 2007, 'Des origines à nos jours : grandes étapes du christianisme.', 'histoire,église,christianisme', '', '', 0, '2026-10-08 22:41:12', 'Théologie'),
(60, 'Système comptable OHADA (SYSCOHADA révisé)', 'Collectif OHADA', 'Gestion', 2018, 'Plan comptable, comptes et travaux de fin d\'exercice selon le référentiel OHADA.', 'ohada,comptabilité,plan comptable', '', '', 0, '2026-10-08 22:41:12', 'Gestion'),
(61, 'Principes de management', 'Peter F. Drucker', 'Gestion', 2006, 'Fonctions du manager, organisation et prise de décision.', 'management,organisation,décision', '', '', 0, '2026-10-08 22:41:12', 'Gestion'),
(62, 'Marketing Management', 'Philip Kotler, Kevin Keller', 'Gestion', 2016, 'Stratégie marketing, segmentation, produit et distribution.', 'marketing,stratégie,commerce', '', '', 0, '2026-10-08 22:41:12', 'Gestion'),
(63, 'Contrôle de gestion', 'Robert N. Anthony, Vijay Govindarajan', 'Gestion', 2010, 'Budgets, coûts, indicateurs et pilotage de la performance.', 'contrôle de gestion,budget,coûts', '', '', 0, '2026-10-08 22:41:12', 'Gestion'),
(64, 'L\'art de la thèse', 'Michel Beaud', 'Méthodologie', 2006, 'Comment préparer et rédiger une thèse, un mémoire ou un rapport.', 'thèse,mémoire,méthodologie,rédaction', '', '', 0, '2026-10-08 22:41:12', 'Méthodologie'),
(65, 'Manuel de recherche en sciences sociales', 'Raymond Quivy, Luc Van Campenhoudt', 'Méthodologie', 2011, 'Construction de l\'objet, hypothèses, terrain et analyse des données.', 'recherche,sciences sociales,méthode', '', '', 0, '2026-10-08 22:41:12', 'Méthodologie');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `message` text DEFAULT NULL,
  `lien` varchar(255) NOT NULL DEFAULT '',
  `lu` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `titre`, `message`, `lien`, `lu`, `created_at`) VALUES
(139, 6, 'Nouvelle évaluation publiée', 'Quiz PHP 2 — Prof. Mbuyi Kalonji • faculté : Informatique • niveau L3 • 30 min • accès : 08/10 20:58 → 08/10 21:00.', 'etudiant/evaluations.php', 0, '2026-10-08 19:57:28'),
(142, 13, 'Votre compte Professeur est créé', 'Identifiant : chrys@gmail.com — faculté : Économie. Contactez l\'administration pour votre mot de passe.', 'prof/dashboard.php', 1, '2026-10-08 22:24:27'),
(143, 1, 'Nouvel étudiant inscrit', 'NGEMBO WENANGUDI TRACY (Tracyngb@gmail.com) — faculté : Informatique, niveau L3.', 'admin/dashboard.php', 1, '2026-10-09 09:32:35'),
(144, 15, 'Votre compte Professeur est créé', 'Identifiant : Joel@gmail.com — faculté : Informatique. Contactez l\'administration pour votre mot de passe.', 'prof/dashboard.php', 1, '2026-10-09 09:55:28'),
(145, 17, 'Votre compte Étudiant est créé', 'Identifiant : Matricule UWB 0003 — faculté : Sciences Economiques et de Gestion. Contactez l\'administration pour votre mot de passe.', 'etudiant/dashboard.php', 1, '2026-10-10 09:34:14'),
(146, 18, 'Votre compte Étudiant est créé', 'Identifiant : Matricule UWB 0004 — faculté : Sciences Economiques et de Gestion. Contactez l\'administration pour votre mot de passe.', 'etudiant/dashboard.php', 0, '2026-10-10 09:34:51'),
(147, 17, 'Nouveau cours publié', 'MATH — CHRYS NGEMBO • faculté : Sciences Economiques et de Gestion • niveau L3.', 'etudiant/cours.php', 1, '2026-10-10 09:41:11'),
(148, 18, 'Nouveau cours publié', 'MATH — CHRYS NGEMBO • faculté : Sciences Economiques et de Gestion • niveau L3.', 'etudiant/cours.php', 0, '2026-10-10 09:41:11'),
(151, 17, 'Nouvelle évaluation publiée', 'Quiz mathematique — CHRYS NGEMBO • faculté : Sciences Economiques et de Gestion • niveau L3 • 30 min • accès : 11/10 10:15 → 11/10 10:20.', 'etudiant/evaluations.php', 0, '2026-10-11 09:12:32'),
(152, 18, 'Nouvelle évaluation publiée', 'Quiz mathematique — CHRYS NGEMBO • faculté : Sciences Economiques et de Gestion • niveau L3 • 30 min • accès : 11/10 10:15 → 11/10 10:20.', 'etudiant/evaluations.php', 0, '2026-10-11 09:12:33'),
(153, 17, 'Nouvelle évaluation publiée', 'Quiz mathematique — CHRYS NGEMBO • faculté : Sciences Economiques et de Gestion • niveau L3 • 30 min • accès : 11/10 10:15 → 11/10 10:20.', 'etudiant/evaluations.php', 0, '2026-10-11 09:12:34'),
(154, 18, 'Nouvelle évaluation publiée', 'Quiz mathematique — CHRYS NGEMBO • faculté : Sciences Economiques et de Gestion • niveau L3 • 30 min • accès : 11/10 10:15 → 11/10 10:20.', 'etudiant/evaluations.php', 0, '2026-10-11 09:12:34'),
(155, 13, '⚠ Tentative de triche signalée', 'GRACE — « Quiz mathematique » : sortie de page pendant la session. Copie soumise automatiquement, note proposée 0/20.', 'prof/validation-ia.php', 1, '2026-10-11 09:17:06'),
(156, 17, 'Session interrompue : copie soumise', 'Évaluation « Quiz mathematique » — votre copie a été soumise automatiquement (sortie de page détectée). En attente de la validation du professeur.', 'etudiant/resultats.php', 0, '2026-10-11 09:17:06'),
(157, 13, '⚠ Tentative de triche signalée', 'GRACE — « Quiz mathematique » : sortie de page pendant la session. Copie soumise automatiquement, note proposée 0/20.', 'prof/validation-ia.php', 1, '2026-10-11 09:17:53'),
(158, 17, 'Session interrompue : copie soumise', 'Évaluation « Quiz mathematique » — votre copie a été soumise automatiquement (sortie de page détectée). En attente de la validation du professeur.', 'etudiant/resultats.php', 0, '2026-10-11 09:17:53'),
(159, 13, 'Copie soumise (délai dépassé)', 'JUDITH — « Quiz mathematique » : délai écoulé, copie récupérée automatiquement.', 'prof/validation-ia.php', 1, '2026-10-11 09:20:01'),
(160, 18, 'Délai dépassé : copie soumise', 'Évaluation « Quiz mathematique » — votre copie a été soumise automatiquement à la fin du délai. En attente de la validation du professeur.', 'etudiant/resultats.php', 0, '2026-10-11 09:20:01'),
(161, 18, 'Copie validée : 13/20', 'Évaluation « Quiz mathematique » — commentaire : Validé après contrôle.', 'etudiant/resultats.php', 0, '2026-10-11 09:26:58'),
(162, 17, 'Copie validée : 0/20', 'Évaluation « Quiz mathematique » — commentaire : Validé après contrôle.', 'etudiant/resultats.php', 0, '2026-10-11 09:27:39'),
(163, 17, 'Copie validée : 0/20', 'Évaluation « Quiz mathematique » — commentaire : Validé après contrôle.', 'etudiant/resultats.php', 0, '2026-10-11 09:28:07');

-- --------------------------------------------------------

--
-- Structure de la table `questions`
--

CREATE TABLE IF NOT EXISTS `questions` (
  `id` int(11) NOT NULL,
  `evaluation_id` int(11) NOT NULL,
  `qkey` varchar(10) NOT NULL DEFAULT 'q1',
  `type` enum('qcm','ouverte') NOT NULL DEFAULT 'qcm',
  `enonce` text NOT NULL,
  `choix` text DEFAULT NULL COMMENT 'JSON array pour QCM',
  `bonne` varchar(500) NOT NULL DEFAULT '',
  `mots_cles` text DEFAULT NULL COMMENT 'mots-cles separes par virgule pour correction IA',
  `points` int(11) NOT NULL DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `questions`
--

INSERT INTO `questions` (`id`, `evaluation_id`, `qkey`, `type`, `enonce`, `choix`, `bonne`, `mots_cles`, `points`) VALUES
(15, 11, 'q1', 'qcm', '2+2 ?', '[\"4\",\"5\",\"3\"]', '4', '', 5),
(26, 17, 'q1', 'qcm', '1+1', '[\"3\",\"2\",\"3\",\"5\"]', '3', '', 5),
(27, 17, 'q2', 'qcm', '123+123', '[\"246\",\"214\",\"22\",\"139\"]', '246', '', 5),
(28, 17, 'q3', 'qcm', '123-234', '[\"Aucune bonne réponse\",\"-234\",\"23\",\"123\"]', 'Aucune bonne réponse', '', 5),
(29, 18, 'q1', 'qcm', '1+1', '[\"3\",\"2\",\"3\",\"5\"]', '3', '', 5),
(30, 18, 'q2', 'qcm', '123+123', '[\"246\",\"214\",\"22\",\"139\"]', '246', '', 5),
(31, 18, 'q3', 'qcm', '123-234', '[\"Aucune bonne réponse\",\"-234\",\"23\",\"123\"]', 'Aucune bonne réponse', '', 5);

-- --------------------------------------------------------

--
-- Structure de la table `soumissions`
--

CREATE TABLE IF NOT EXISTS `soumissions` (
  `id` int(11) NOT NULL,
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
  `date_soumission` date DEFAULT (curdate()),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `soumissions`
--

INSERT INTO `soumissions` (`id`, `evaluation_id`, `etudiant_id`, `etudiant_nom`, `email`, `reponses`, `note_ia`, `note_finale`, `feedback`, `details`, `statut`, `commentaire_prof`, `date_soumission`, `created_at`) VALUES
(40, 17, 17, 'GRACE', 'ngembo@gmail.com', '[]', 0, 0, '⚠️ SESSION INTERROMPUE — tentative de triche détectée (sortie de page pendant l\'épreuve).\nCopie soumise automatiquement. Note proposée : 0/20 (à confirmer par le professeur).\nÉlément déclencheur : Trop d\'incidents anti-triche (3/3) : La fenêtre d\'évaluation a perdu le focus\n\n--- Correction de la copie ---\nCorrection automatique IA — 0/15 → 0/20.\n• q1 (0/5) : Mauvaise réponse. Bonne réponse : 3\n• q2 (0/5) : Mauvaise réponse. Bonne réponse : 246\n• q3 (0/5) : Mauvaise réponse. Bonne réponse : Aucune bonne réponse\nAppréciation : Insuffisant, reprenez le chapitre et la bibliothèque.\n⚠️ Proposition IA à valider par l\'enseignant.', '{\"details\":[{\"qid\":\"q1\",\"type\":\"qcm\",\"reponse\":\"\",\"attendu\":\"3\",\"points\":\"0\\/5\",\"commentaire\":\"Mauvaise réponse. Bonne réponse : 3\",\"motsTrouves\":[],\"confiance\":\"100%\"},{\"qid\":\"q2\",\"type\":\"qcm\",\"reponse\":\"\",\"attendu\":\"246\",\"points\":\"0\\/5\",\"commentaire\":\"Mauvaise réponse. Bonne réponse : 246\",\"motsTrouves\":[],\"confiance\":\"100%\"},{\"qid\":\"q3\",\"type\":\"qcm\",\"reponse\":\"\",\"attendu\":\"Aucune bonne réponse\",\"points\":\"0\\/5\",\"commentaire\":\"Mauvaise réponse. Bonne réponse : Aucune bonne réponse\",\"motsTrouves\":[],\"confiance\":\"100%\"}],\"alertes\":[],\"moteur\":\"local\",\"type\":\"triche\"}', 'validee', 'Validé après contrôle.', '2026-10-11', '2026-10-11 09:17:06'),
(41, 17, 17, 'GRACE', 'ngembo@gmail.com', '[]', 0, 0, '⚠️ SESSION INTERROMPUE — tentative de triche détectée (sortie de page pendant l\'épreuve).\nCopie soumise automatiquement. Note proposée : 0/20 (à confirmer par le professeur).\nÉlément déclencheur : Trop d\'incidents anti-triche (3/3) : La fenêtre d\'évaluation a perdu le focus\n\n--- Correction de la copie ---\nCorrection automatique IA — 0/15 → 0/20.\n• q1 (0/5) : Mauvaise réponse. Bonne réponse : 3\n• q2 (0/5) : Mauvaise réponse. Bonne réponse : 246\n• q3 (0/5) : Mauvaise réponse. Bonne réponse : Aucune bonne réponse\nAppréciation : Insuffisant, reprenez le chapitre et la bibliothèque.\n⚠️ Proposition IA à valider par l\'enseignant.', '{\"details\":[{\"qid\":\"q1\",\"type\":\"qcm\",\"reponse\":\"\",\"attendu\":\"3\",\"points\":\"0\\/5\",\"commentaire\":\"Mauvaise réponse. Bonne réponse : 3\",\"motsTrouves\":[],\"confiance\":\"100%\"},{\"qid\":\"q2\",\"type\":\"qcm\",\"reponse\":\"\",\"attendu\":\"246\",\"points\":\"0\\/5\",\"commentaire\":\"Mauvaise réponse. Bonne réponse : 246\",\"motsTrouves\":[],\"confiance\":\"100%\"},{\"qid\":\"q3\",\"type\":\"qcm\",\"reponse\":\"\",\"attendu\":\"Aucune bonne réponse\",\"points\":\"0\\/5\",\"commentaire\":\"Mauvaise réponse. Bonne réponse : Aucune bonne réponse\",\"motsTrouves\":[],\"confiance\":\"100%\"}],\"alertes\":[],\"moteur\":\"local\",\"type\":\"triche\"}', 'validee', 'Validé après contrôle.', '2026-10-11', '2026-10-11 09:17:53'),
(42, 17, 18, 'JUDITH', 'judith@gmail.com', '{\"q2\":\"246\",\"q3\":\"Aucune bonne réponse\",\"q1\":\"2\"}', 13, 13, '⏰ Délai dépassé — copie soumise automatiquement.\nÉlément : Fin du délai autorisé\nCorrection automatique IA — 10/15 → 13/20.\n• q1 (0/5) : Mauvaise réponse. Bonne réponse : 3\n• q2 (5/5) : Bonne réponse.\n• q3 (5/5) : Bonne réponse.\nAppréciation : Bien, quelques notions à renforcer.\n⚠️ Proposition IA à valider par l\'enseignant.', '{\"details\":[{\"qid\":\"q1\",\"type\":\"qcm\",\"reponse\":\"2\",\"attendu\":\"3\",\"points\":\"0\\/5\",\"commentaire\":\"Mauvaise réponse. Bonne réponse : 3\",\"motsTrouves\":[],\"confiance\":\"100%\"},{\"qid\":\"q2\",\"type\":\"qcm\",\"reponse\":\"246\",\"attendu\":\"246\",\"points\":\"5\\/5\",\"commentaire\":\"Bonne réponse.\",\"motsTrouves\":[],\"confiance\":\"100%\"},{\"qid\":\"q3\",\"type\":\"qcm\",\"reponse\":\"Aucune bonne réponse\",\"attendu\":\"Aucune bonne réponse\",\"points\":\"5\\/5\",\"commentaire\":\"Bonne réponse.\",\"motsTrouves\":[],\"confiance\":\"100%\"}],\"alertes\":[],\"moteur\":\"local\",\"type\":\"expiree\"}', 'validee', 'Validé après contrôle.', '2026-10-11', '2026-10-11 09:20:01');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','professeur','etudiant') NOT NULL DEFAULT 'etudiant',
  `faculte` varchar(150) DEFAULT '—',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `niveau` varchar(10) NOT NULL DEFAULT 'L3',
  `matricule` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `nom`, `email`, `password_hash`, `role`, `faculte`, `created_at`, `niveau`, `matricule`) VALUES
(1, 'Admin UWB', 'admin@uwb.ac.cd', '$2y$10$LNObgj1pYzMzo59NMURFPexQdLsVgQj4b8EJPFzcHz5wJn60MrPeS', 'admin', 'Administration', '2026-10-04 13:31:40', 'L3', NULL),
(6, 'Ordi', 'ordidimbi@gmail.com', '$2y$10$n.gBnHZIC34odLkmzO6MveD8vCFPdk/Bgj8RwAcTsgdxaEltdX8Ha', 'etudiant', 'Sciences Informatiques & Intelligence Artificielle', '2026-10-04 15:52:03', 'L3', NULL),
(7, 'NARUTO UZUMAKI', 'Naruto@gmail.com', '$2y$10$9/a4HEHsZzzWPv07xDRfe.tck3zwjjfes3jRPlNEAYgh30Tj8mhZm', 'etudiant', 'Science de l\'Information et de la Communication', '2026-10-08 16:29:34', 'L1', NULL),
(13, 'CHRYS NGEMBO', 'chrys@gmail.com', '$2y$10$5K4sxBKjss./8N3owMzz4eCWdLnuIX.x1cIsizxd49SkhAAmeSBai', 'professeur', 'Sciences Economiques et de Gestion', '2026-10-08 22:24:27', 'L3', NULL),
(14, 'NGEMBO WENANGUDI TRACY', 'Tracyngb@gmail.com', '$2y$10$KEPaSJkDi8YlMm9vp6BLRuggY6zyFaL.Peyd2D66WfTlIrNIEu2v.', 'etudiant', 'Sciences Informatiques & Intelligence Artificielle', '2026-10-09 09:32:35', 'L3', NULL),
(15, 'JOEL NGEMBO', 'Joel@gmail.com', '$2y$10$PSVVTyCPs346m4MVwIGmZ.ChKPgZ6TJc.JFs311fDis8VQAindC82', 'professeur', 'Sciences Informatiques & Intelligence Artificielle', '2026-10-09 09:55:28', 'L3', NULL),
(16, 'Etudiant Demo UWB', 'etudiant.demo@uwb.ac.cd', '$2y$10$.u/w51gQo25YY1YAclwONuQEROLNvmSzsuVSi/EOqrpUXV6I7BfXe', 'etudiant', 'Sciences Informatiques & Intelligence Artificielle', '2026-10-09 21:38:33', 'L3', 'UWB-INFO-2024-0001'),
(17, 'GRACE', 'ngembo@gmail.com', '$2y$10$geHijeIMXD7c0.laAIrAeOAeM8wibwlfSdnunnqISEgdHztgDDaWm', 'etudiant', 'Sciences Economiques et de Gestion', '2026-10-10 09:34:14', 'L3', 'UWB 0003'),
(18, 'JUDITH', 'judith@gmail.com', '$2y$10$Zr2gsEcjBAPA2kjzuo7zS.8H9opH3Ps7YBnY/FJIFDC.YMKxpVLai', 'etudiant', 'Sciences Economiques et de Gestion', '2026-10-10 09:34:51', 'L3', 'UWB 0004');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `cours`
--
ALTER TABLE `cours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enseignant_id` (`enseignant_id`);

--
-- Index pour la table `evaluations`
--
ALTER TABLE `evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cours_id` (`cours_id`),
  ADD KEY `prof_id` (`prof_id`);

--
-- Index pour la table `eval_events`
--
ALTER TABLE `eval_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ev` (`evaluation_id`,`etudiant_id`),
  ADD KEY `idx_ev_etud` (`etudiant_id`);

--
-- Index pour la table `eval_sessions`
--
ALTER TABLE `eval_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_eval_etud_tent` (`evaluation_id`,`etudiant_id`,`tentative`),
  ADD KEY `idx_etud` (`etudiant_id`);

--
-- Index pour la table `facultes`
--
ALTER TABLE `facultes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `livres`
--
ALTER TABLE `livres`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `livres` ADD FULLTEXT KEY `ft_livres` (`titre`,`auteur`,`resume`,`mots`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_lu` (`user_id`,`lu`);

--
-- Index pour la table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_questions_evaluation` (`evaluation_id`);

--
-- Index pour la table `soumissions`
--
ALTER TABLE `soumissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `etudiant_id` (`etudiant_id`),
  ADD KEY `idx_eval` (`evaluation_id`),
  ADD KEY `idx_email` (`email`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `matricule` (`matricule`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `cours`
--
ALTER TABLE `cours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `evaluations`
--
ALTER TABLE `evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `eval_events`
--
ALTER TABLE `eval_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `eval_sessions`
--
ALTER TABLE `eval_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT pour la table `facultes`
--
ALTER TABLE `facultes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `livres`
--
ALTER TABLE `livres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=164;

--
-- AUTO_INCREMENT pour la table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT pour la table `soumissions`
--
ALTER TABLE `soumissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `cours`
--
ALTER TABLE `cours`
  ADD CONSTRAINT `cours_ibfk_1` FOREIGN KEY (`enseignant_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `evaluations`
--
ALTER TABLE `evaluations`
  ADD CONSTRAINT `evaluations_ibfk_1` FOREIGN KEY (`cours_id`) REFERENCES `cours` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `evaluations_ibfk_2` FOREIGN KEY (`prof_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `eval_sessions`
--
ALTER TABLE `eval_sessions`
  ADD CONSTRAINT `fk_sess_etud` FOREIGN KEY (`etudiant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sess_eval` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `fk_questions_evaluation` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `soumissions`
--
ALTER TABLE `soumissions`
  ADD CONSTRAINT `soumissions_ibfk_1` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `soumissions_ibfk_2` FOREIGN KEY (`etudiant_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
