-- ============================================================
-- UWB.Learn — Base de données MySQL (design inchangé, 100% PHP)
-- Import : mysql -u root -p < database.sql
-- ============================================================
CREATE DATABASE IF NOT EXISTS uwb_learn
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE uwb_learn;

-- ---------- Filieres ----------
CREATE TABLE IF NOT EXISTS filieres (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  code VARCHAR(20) NOT NULL UNIQUE,
  description VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Utilisateurs ----------
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','professeur','etudiant') NOT NULL DEFAULT 'etudiant',
  filiere VARCHAR(100) DEFAULT '—',
  niveau VARCHAR(10) NOT NULL DEFAULT 'L3',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_role (role),
  INDEX idx_email (email)
) ENGINE=InnoDB;

-- ---------- Cours ----------
CREATE TABLE IF NOT EXISTS cours (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titre VARCHAR(255) NOT NULL,
  enseignant_id INT NULL,
  enseignant_nom VARCHAR(150) NOT NULL DEFAULT 'UWB',
  categorie VARCHAR(50) NOT NULL DEFAULT 'Informatique',
  filiere VARCHAR(100) NOT NULL DEFAULT 'Informatique',
  niveau VARCHAR(10) NOT NULL DEFAULT 'L3',
  description TEXT,
  supports TEXT COMMENT 'JSON array de strings',
  seance_date VARCHAR(32) DEFAULT 'À programmer',
  meet_link VARCHAR(255) DEFAULT '',
  salle VARCHAR(100) DEFAULT 'Google Meet',
  inscrits INT DEFAULT 0,
  image VARCHAR(255) DEFAULT 'forms/portfolio-8.webp',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (enseignant_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- Livres / Bibliotheque ----------
CREATE TABLE IF NOT EXISTS livres (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titre VARCHAR(255) NOT NULL,
  auteur VARCHAR(150) NOT NULL DEFAULT '',
  categorie VARCHAR(50) DEFAULT 'Informatique',
  annee INT DEFAULT 2024,
  resume TEXT,
  mots TEXT COMMENT 'mots-cles separes par virgule',
  cover VARCHAR(500) DEFAULT '',
  fichier VARCHAR(500) DEFAULT '',
  telechargements INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FULLTEXT INDEX ft_livres (titre, auteur, resume, mots)
) ENGINE=InnoDB;

-- ---------- Evaluations ----------
CREATE TABLE IF NOT EXISTS evaluations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cours_id INT NULL,
  titre VARCHAR(255) NOT NULL,
  prof_id INT NULL,
  prof_nom VARCHAR(150) DEFAULT '',
  filiere VARCHAR(100) NOT NULL DEFAULT 'Informatique',
  niveau VARCHAR(10) NOT NULL DEFAULT 'L3',
  duree INT DEFAULT 30 COMMENT 'minutes',
  statut VARCHAR(20) DEFAULT 'publiée',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cours_id) REFERENCES cours(id) ON DELETE SET NULL,
  FOREIGN KEY (prof_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  evaluation_id INT NOT NULL,
  qkey VARCHAR(10) NOT NULL DEFAULT 'q1',
  type ENUM('qcm','ouverte') NOT NULL DEFAULT 'qcm',
  enonce TEXT NOT NULL,
  choix TEXT NULL COMMENT 'JSON array pour QCM',
  bonne VARCHAR(500) NOT NULL DEFAULT '',
  mots_cles TEXT NULL COMMENT 'mots-cles separes par virgule pour correction IA',
  points INT NOT NULL DEFAULT 5,
  INDEX idx_questions_evaluation (evaluation_id),
  CONSTRAINT fk_questions_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Soumissions ----------
CREATE TABLE IF NOT EXISTS soumissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  evaluation_id INT NOT NULL,
  etudiant_id INT NULL,
  etudiant_nom VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  reponses TEXT COMMENT 'JSON {qkey: reponse}',
  note_ia INT DEFAULT 0,
  note_finale INT NULL,
  feedback TEXT,
  details TEXT COMMENT 'JSON details IA',
  statut ENUM('proposee-IA','validee') DEFAULT 'proposee-IA',
  commentaire_prof VARCHAR(500) DEFAULT '',
  date_soumission DATE DEFAULT (CURRENT_DATE),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE,
  FOREIGN KEY (etudiant_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_eval (evaluation_id),
  INDEX idx_email (email)
) ENGINE=InnoDB;
