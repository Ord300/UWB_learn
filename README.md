# UWB.Learn — Version PHP + MySQL (design inchangé)

Plateforme E-learning UWB : cours + Google Meet, bibliothèque intelligente, évaluations + IA, 3 rôles.
Conversion fidèle de la version statique (`localStorage`) vers **PHP 8 + MySQL + sessions**, **sans modifier le design** (`assets/css/style.css`, images, classes et structure HTML identiques).

## 1. Prérequi
- PHP 8.1+, MySQL 8 (XAMPP / WAMP / Laragon / Ubuntu), Apache
- Extensions PHP : `pdo_mysql`, `mbstring`, `intl` (recommandé pour l'IA)

## 2. Installation (2 min)
```bash
# a) Config DB si besoin
# éditez config/database.php (DB_HOST, DB_NAME, DB_USER, DB_PASS)

# b) Créez + remplissez la base
mysql -u root --default-character-set=utf8mb4 < uwb_learn.sql

# OU via navigateur :
# http://localhost/UWB_learn/install.php  → bouton "Installer / Réinitialiser"
```

## 3. Lancement
- Placez le dossier sous `htdocs/` (XAMPP) ou `www/` (WAMP), puis :
- `http://localhost/UWB_learn/index.php`

> **Inscription** : réservée aux **étudiants** (professeurs/admin créés par l'admin).
> Chaque étudiant accède aux **cours, bibliothèque et évaluations de sa faculté**
> (facultés : Médecine, ISTM, Économie, Communication, Droit, Théologie, Informatique, Gestion…).
> Un **bouton de notifications** (cloche 🔔 + compteur) est placé **juste à côté du bouton Déconnexion** ;
> il signale les nouveaux cours/évaluations/ressources, les copies validées et les diffusions de l'administration.

Aucun compte de démonstration n'est créé. Un **compte administrateur initial** est provisionné
(`admin@uwb.ac.cd` / `admin123`) pour le premier accès ; **changez son mot de passe** aussitôt connecté.
Les professeurs et étudiants sont ensuite créés depuis l'espace administrateur (inscription réservée aux étudiants).

## 4. Structure (nouveau = PHP, design = identique)
```
index.php / login.php / inscription.php / dashboard.php  # vitrine + auth
etudiant/dashboard.php | cours.php | bibliotheque.php | evaluations.php | resultats.php
prof/dashboard.php | mes-cours.php | creer-evaluation.php | validation-ia.php
admin/dashboard.php | facultes.php
actions/  # login, register, logout, cours, livre, evaluation, submit_eval, validate, admin
config/database.php  # PDO (modifiez ici)
includes/auth.php | ia.php (moteur IA porté du JS) | layout.php (header/footer/cards)
uwb_learn.sql | install.php
uploads/  # couvertures + PDF uploadés
assets/css/style.css  # INCHANGÉ
```
Les anciens `.html` sont conservés ; `.htaccess` redirige `.html → .php`.

## 5. Notes
- Mots de passe hashés (`password_hash`), requêtes préparées PDO.
- Moteur IA PHP = même algo que `assets/js/script.js` (QCM 100% + mots-clés + note /20).
- **IA réelle (facultatif)** : collez une clé Groq gratuite dans `config/ia_api.php` → correction par API
  (sinon moteur IA local inclus). Résultats étudiants affichés **uniquement après validation du professeur**.
- **Séance caméra (Meet)** : le professeur définit l'**heure de début et l'heure de fin** de la séance
  (`Mes cours & séances Meet`) ; hors de cette fenêtre, l'étudiant ne peut plus rejoindre la séance (bouton verrouillé).
- **Évaluation** : le professeur peut fixer une **fenêtre ouverture → clôture** en plus du délai.
  Avant l'ouverture : « Pas encore ouverte » (impossible de commencer) ; après la clôture : accès fermé.
  La session démarre au clic de l'étudiant, dure au plus le délai et n'excède **jamais** la clôture ;
  sortie de page = copie soumise automatiquement (triche, note proposée 0/20 à valider).
- Uploads : couvertures (2 Mo max, `uploads/cov_*`), PDF (20 Mo max, `uploads/doc_*`).
