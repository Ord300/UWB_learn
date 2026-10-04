-- ============================================================
-- UWB.Learn — Base de données MySQL (design inchangé, 100% PHP)
-- Import : mysql -u root -p < database.sql
-- ============================================================
CREATE DATABASE IF NOT EXISTS uwb_learn
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE uwb_learn;


CREATE TABLE `cours` (
  `id` int NOT NULL,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enseignant_id` int DEFAULT NULL,
  `enseignant_nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UWB',
  `categorie` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Informatique',
  `niveau` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'L3',
  `description` text COLLATE utf8mb4_unicode_ci,
  `supports` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array de strings',
  `seance_date` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT 'À programmer',
  `meet_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `salle` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Google Meet',
  `inscrits` int DEFAULT '0',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'forms/portfolio-8.webp',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `filiere` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Informatique'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `cours`
--

INSERT INTO `cours` (`id`, `titre`, `enseignant_id`, `enseignant_nom`, `categorie`, `niveau`, `description`, `supports`, `seance_date`, `meet_link`, `salle`, `inscrits`, `image`, `created_at`, `filiere`) VALUES
(3, 'Comptabilité Générale OHADA', 3, 'Prof. Aline Nsimba', 'Gestion', 'L2', 'Plan comptable OHADA, journal, grand-livre, balance et états financiers.', '[\"Plan OHADA résumé.pdf\"]', '2026-10-09 09:00', 'https://meet.google.com/uwb-ohada-303', 'Visioconférence Google Meet', 55, 'forms/portfolio-6.webp', '2026-10-04 14:31:40', 'Informatique'),
(5, 'Cours d\'Echec', 2, 'Prof. Mbuyi Kalonji', 'Informatique', 'L3', 'C’est avec une immense joie que nous vous invitons à célébrer notre union et partager ce moment unique rempli d’amour, de bonheur et d’émotions.', '[\"Cours d\'echec de marie\"]', '2026-10-04 17:50', 'https://meet.google.com/nym-ankg-swn', 'Google Meet', 0, 'forms/portfolio-1.webp', '2026-10-04 16:31:30', 'Informatique');

-- --------------------------------------------------------

--
-- Structure de la table `evaluations`
--

CREATE TABLE `evaluations` (
  `id` int NOT NULL,
  `cours_id` int DEFAULT NULL,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prof_id` int DEFAULT NULL,
  `prof_nom` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `duree` int DEFAULT '30' COMMENT 'minutes',
  `statut` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'publiée',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `filiere` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Informatique',
  `niveau` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'L3'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `evaluations`
--

INSERT INTO `evaluations` (`id`, `cours_id`, `titre`, `prof_id`, `prof_nom`, `duree`, `statut`, `created_at`, `filiere`, `niveau`) VALUES
(3, 5, 'Quiz echec', 2, 'Prof. Mbuyi Kalonji', 30, 'publiée', '2026-10-04 17:18:16', 'Informatique', 'L3');

-- --------------------------------------------------------

--
-- Structure de la table `filieres`
--

CREATE TABLE `filieres` (
  `id` int NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `filieres`
--

INSERT INTO `filieres` (`id`, `nom`, `code`, `description`, `created_at`) VALUES
(1, 'Informatique', 'INFO', 'Programmation, bases de données, réseaux, IA.', '2026-10-04 14:31:40'),
(3, 'Méthodologie', 'METH', 'TFE, mémoire, recherche scientifique.', '2026-10-04 14:31:40'),
(5, 'L1 Informatique', 'INFO 1', 'Informatique et intelligence artificiel', '2026-10-04 15:59:25');

-- --------------------------------------------------------

--
-- Structure de la table `livres`
--

CREATE TABLE `livres` (
  `id` int NOT NULL,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auteur` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `categorie` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Informatique',
  `annee` int DEFAULT '2024',
  `resume` text COLLATE utf8mb4_unicode_ci,
  `mots` text COLLATE utf8mb4_unicode_ci COMMENT 'mots-cles separes par virgule',
  `cover` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `fichier` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `telechargements` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `livres`
--

INSERT INTO `livres` (`id`, `titre`, `auteur`, `categorie`, `annee`, `resume`, `mots`, `cover`, `fichier`, `telechargements`, `created_at`) VALUES
(1, 'Apprendre JavaScript Moderne', 'M. Lelo', 'Informatique', 2023, 'Bases JS, ES6+, DOM et projets web.', 'javascript,web,programmation,html,css', '', '', 312, '2026-10-04 14:31:40'),
(4, 'Introduction au Machine Learning', 'A. Ng - adapté UWB', 'Informatique', 2024, 'Régression, classification, NLP et évaluation.', 'ia,machine learning,nlp,correction automatique,python', 'https://miro.medium.com/v2/resize:fit:1400/1*ikEB53J-pPJCXy1Ub1XUsQ.jpeg', '', 198, '2026-10-04 14:31:40'),
(5, 'Méthodologie de recherche (TFE & Mémoire)', 'Dir. Recherche UWB', 'Méthodologie', 2023, 'Problématique, revue de littérature, plan de rédaction.', 'tfe,mémoire,problématique,objectifs', 'uploads/cov_1791130513_fea235a2.jpg', '', 341, '2026-10-04 14:31:40'),
(7, 'Cours d\'Echec de Marie', 'Marie', 'Gestion', 2026, 'Apprendre à joué au échec avec monsieur Marie de ordi likindasaka', '', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTtqZhb3T2MWWgFm_CoSCy1Z5srw5AtREs39qy6hY-OUh5lSnzLtcHvoazq&s=10', 'uploads/doc_1791130893_c3a23d26.pdf', 3, '2026-10-04 16:05:13');

-- --------------------------------------------------------

--
-- Structure de la table `questions`
--

CREATE TABLE `questions` (
  `id` int NOT NULL,
  `evaluation_id` int NOT NULL,
  `qkey` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'q1',
  `type` enum('qcm','ouverte') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'qcm',
  `enonce` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `choix` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array pour QCM',
  `bonne` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mots_cles` text COLLATE utf8mb4_unicode_ci COMMENT 'mots-cles separes par virgule pour correction IA',
  `points` int NOT NULL DEFAULT '5'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `questions`
--

INSERT INTO `questions` (`id`, `evaluation_id`, `qkey`, `type`, `enonce`, `choix`, `bonne`, `mots_cles`, `points`) VALUES
(7, 3, 'q1', 'qcm', '1 + 1', '[\"2\",\"3\",\"5\",\"Aucune bonne réponse\"]', '2', '', 5);

-- --------------------------------------------------------

--
-- Structure de la table `soumissions`
--

CREATE TABLE `soumissions` (
  `id` int NOT NULL,
  `evaluation_id` int NOT NULL,
  `etudiant_id` int DEFAULT NULL,
  `etudiant_nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reponses` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON {qkey: reponse}',
  `note_ia` int DEFAULT '0',
  `note_finale` int DEFAULT NULL,
  `feedback` text COLLATE utf8mb4_unicode_ci,
  `details` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON details IA',
  `statut` enum('proposee-IA','validee') COLLATE utf8mb4_unicode_ci DEFAULT 'proposee-IA',
  `commentaire_prof` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `date_soumission` date DEFAULT (curdate()),
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','professeur','etudiant') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'etudiant',
  `filiere` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '—',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `niveau` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'L3'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `nom`, `email`, `password_hash`, `role`, `filiere`, `created_at`, `niveau`) VALUES
(1, 'Admin UWB', 'admin@uwb.ac.cd', '$2y$10$LNObgj1pYzMzo59NMURFPexQdLsVgQj4b8EJPFzcHz5wJn60MrPeS', 'admin', 'Administration', '2026-10-04 14:31:40', 'L3'),
(2, 'Prof. Mbuyi Kalonji', 'prof@uwb.ac.cd', '$2y$10$yuM8kvxMw64rv2NKCVP4v.HnNTYZSIolAWV1CH23L.7S8Fq2ChzxO', 'professeur', 'Informatique', '2026-10-04 14:31:40', 'L3'),
(3, 'Prof. Aline Nsimba', 'aline@uwb.ac.cd', '$2y$10$yuM8kvxMw64rv2NKCVP4v.HnNTYZSIolAWV1CH23L.7S8Fq2ChzxO', 'professeur', 'Gestion', '2026-10-04 14:31:40', 'L3'),
(4, 'Grace Lukusa', 'etudiant@uwb.ac.cd', '$2y$10$ghNw64fKtlQGkOxTtt/UkOuEAywlLDlc4d4eM61FCCX3tILj3U8Bm', 'etudiant', 'Informatique', '2026-10-04 14:31:40', 'L3'),
(5, 'Jean Ilunga', 'jean@uwb.ac.cd', '$2y$10$ghNw64fKtlQGkOxTtt/UkOuEAywlLDlc4d4eM61FCCX3tILj3U8Bm', 'etudiant', 'Gestion', '2026-10-04 14:31:40', 'L2'),
(6, 'Ordi', 'ordidimbi@gmail.com', '$2y$10$n.gBnHZIC34odLkmzO6MveD8vCFPdk/Bgj8RwAcTsgdxaEltdX8Ha', 'etudiant', 'Informatique', '2026-10-04 16:52:03', 'L3');

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
-- Index pour la table `filieres`
--
ALTER TABLE `filieres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `livres`
--
ALTER TABLE `livres`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `livres` ADD FULLTEXT KEY `ft_livres` (`titre`,`auteur`,`resume`,`mots`);

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
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `cours`
--
ALTER TABLE `cours`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `evaluations`
--
ALTER TABLE `evaluations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `filieres`
--
ALTER TABLE `filieres`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `livres`
--
ALTER TABLE `livres`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `soumissions`
--
ALTER TABLE `soumissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
