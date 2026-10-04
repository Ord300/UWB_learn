-- ============================================================
-- UWB.Learn — Données de démonstration (mêmes que localStorage JS)
-- Import : mysql -u root -p uwb_learn < seed.sql
-- Comptes : admin@uwb.ac.cd/admin123 • prof@uwb.ac.cd/prof123 • etudiant@uwb.ac.cd/etu123
-- ============================================================
USE uwb_learn;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
-- TRUNCATE interdit sur table parente avec FK (erreur 1701) : on utilise DELETE
DELETE FROM soumissions;
DELETE FROM questions;
DELETE FROM evaluations;
DELETE FROM livres;
DELETE FROM cours;
DELETE FROM users;
DELETE FROM filieres;
ALTER TABLE soumissions AUTO_INCREMENT = 1;
ALTER TABLE questions AUTO_INCREMENT = 1;
ALTER TABLE evaluations AUTO_INCREMENT = 1;
ALTER TABLE livres AUTO_INCREMENT = 1;
ALTER TABLE cours AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;
ALTER TABLE filieres AUTO_INCREMENT = 1;
SET FOREIGN_KEY_CHECKS=1;

INSERT INTO filieres (id, nom, code, description) VALUES
(1,'Informatique','INFO','Programmation, bases de données, réseaux, IA.'),
(2,'Gestion','GEST','Comptabilité OHADA, finance, management.'),
(3,'Méthodologie','METH','TFE, mémoire, recherche scientifique.');

-- Mots de passe hashés : admin123 / prof123 / etu123
INSERT INTO users (id, nom, email, password_hash, role, filiere, niveau) VALUES
(1,'Admin UWB','admin@uwb.ac.cd','$2y$10$LNObgj1pYzMzo59NMURFPexQdLsVgQj4b8EJPFzcHz5wJn60MrPeS','admin','Administration','—'),
(2,'Prof. Mbuyi Kalonji','prof@uwb.ac.cd','$2y$10$yuM8kvxMw64rv2NKCVP4v.HnNTYZSIolAWV1CH23L.7S8Fq2ChzxO','professeur','Informatique','—'),
(3,'Prof. Aline Nsimba','aline@uwb.ac.cd','$2y$10$yuM8kvxMw64rv2NKCVP4v.HnNTYZSIolAWV1CH23L.7S8Fq2ChzxO','professeur','Gestion','—'),
(4,'Grace Lukusa','etudiant@uwb.ac.cd','$2y$10$ghNw64fKtlQGkOxTtt/UkOuEAywlLDlc4d4eM61FCCX3tILj3U8Bm','etudiant','Informatique','L3'),
(5,'Jean Ilunga','jean@uwb.ac.cd','$2y$10$ghNw64fKtlQGkOxTtt/UkOuEAywlLDlc4d4eM61FCCX3tILj3U8Bm','etudiant','Gestion','L2');

INSERT INTO cours (id, titre, enseignant_id, enseignant_nom, categorie, niveau, description, supports, seance_date, meet_link, salle, inscrits, image) VALUES
(1,'Programmation Web : HTML, CSS & JavaScript',2,'Prof. Mbuyi Kalonji','Informatique','L3','Création d''interfaces modernes, Bootstrap 5, DOM, fetch API et bonnes pratiques.','["Support PDF - HTML5.pdf","TP N°3 - Portfolio Bootstrap.zip"]','2026-10-07 10:00','https://meet.google.com/uwb-web-101','Visioconférence Google Meet',42,'forms/portfolio-8.webp'),
(2,'Bases de données & SQL',2,'Prof. Mbuyi Kalonji','Informatique','L2','Modèle relationnel, MCD/MLD, requêtes SQL, vues et transactions.','["MCD - Gestion UWB.pdf","TD SQL corrigé.pdf"]','2026-10-08 14:00','https://meet.google.com/uwb-sql-202','Visioconférence Google Meet',38,'forms/portfolio-2.webp'),
(3,'Comptabilité Générale OHADA',3,'Prof. Aline Nsimba','Gestion','L2','Plan comptable OHADA, journal, grand-livre, balance et états financiers.','["Plan OHADA résumé.pdf"]','2026-10-09 09:00','https://meet.google.com/uwb-ohada-303','Visioconférence Google Meet',55,'forms/portfolio-6.webp'),
(4,'Intelligence Artificielle : introduction au NLP',2,'Prof. Mbuyi Kalonji','Informatique','M1','Traitement du langage, embeddings, correction automatique et feedback pédagogique.','["Intro NLP - slides.pdf"]','2026-10-10 11:00','https://meet.google.com/uwb-ia-404','Visioconférence Google Meet',27,'forms/portfolio-7.webp');

INSERT INTO livres (id, titre, auteur, categorie, annee, resume, mots, cover, telechargements) VALUES
(1,'Apprendre JavaScript Moderne','M. Lelo','Informatique',2023,'Bases JS, ES6+, DOM et projets web.','javascript,web,programmation,html,css','',312),
(2,'Systèmes de Gestion de Bases de Données','R. Elmasri','Informatique',2022,'Conception MCD/MLD et SQL avancé.','sql,mcd,base de données,merise','',428),
(3,'Comptabilité OHADA en pratique','A. Nsimba','Gestion',2024,'Écritures, états financiers, cas pratiques RDC.','ohada,comptabilité,gestion,finance','',265),
(4,'Introduction au Machine Learning','A. Ng - adapté UWB','Informatique',2024,'Régression, classification, NLP et évaluation.','ia,machine learning,nlp,correction automatique,python','',198),
(5,'Méthodologie de recherche (TFE & Mémoire)','Dir. Recherche UWB','Méthodologie',2023,'Problématique, revue de littérature, plan de rédaction.','tfe,mémoire,problématique,objectifs','',340),
(6,'Réseaux informatiques CCNA - Essentiel','Cisco Academy','Informatique',2022,'Adressage IP, routage, switching.','réseau,tcp,ip,cisco','',150);

INSERT INTO evaluations (id, cours_id, titre, prof_id, prof_nom, filiere, niveau, duree, statut) VALUES
(1,1,'Quiz HTML/CSS - Session 1',2,'Prof. Mbuyi Kalonji','Informatique','L3',20,'publiée'),
(2,2,'Devoir SQL - Requêtes SELECT',2,'Prof. Mbuyi Kalonji','Informatique','L2',45,'publiée');

INSERT INTO questions (evaluation_id, qkey, type, enonce, choix, bonne, mots_cles, points) VALUES
(1,'q1','qcm','Quelle balise crée un lien hypertexte ?','["<link>","<a>","<href>","<url>"]','<a>','',5),
(1,'q2','qcm','Quelle classe Bootstrap centre un texte ?','[".text-left",".text-center",".center",".align"]','.text-center','',5),
(1,'q3','ouverte','Expliquez en 3 lignes le rôle du JavaScript dans une page web.','','','interactivité,dynamique,dom,client,navigateur,événement',10),
(2,'q1','ouverte','Écrivez une requête SQL listant les étudiants inscrits en L3 triés par nom.','','','select,from,where,order by,étudiants',10),
(2,'q2','qcm','Quelle clause filtre les groupes ?','["WHERE","HAVING","GROUP","FILTER"]','HAVING','',5),
(2,'q3','ouverte','Citez 2 avantages d''une clé primaire.','','','unicité,identifiant,intégrité,index,référence',5);

INSERT INTO soumissions (evaluation_id, etudiant_id, etudiant_nom, email, reponses, note_ia, note_finale, feedback, details, statut, commentaire_prof, date_soumission) VALUES
(1,4,'Grace Lukusa','etudiant@uwb.ac.cd','{"q1":"<a>","q2":".text-center","q3":"JavaScript rend la page interactive côté client, manipule le DOM et réagit aux événements du navigateur."}',17,17,'Bonne maîtrise. Q1-Q2 justes. Q3 : mots-clés interactivité, client, DOM, événement détectés.','[]','validee','','2026-09-28');
