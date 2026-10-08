# UWB.Learn — Version PHP + MySQL (design inchangé)

Plateforme E-learning UWB : cours + Google Meet, bibliothèque intelligente, évaluations + IA, 3 rôles.
Conversion fidèle de la version statique (`localStorage`) vers **PHP 8 + MySQL + sessions**, **sans modifier le design** (`assets/css/style.css`, images, classes et structure HTML identiques).

## 1. Prérequis
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
> Un **système de notifications** (cloche 🔔) signale les nouveaux cours/évaluations/ressources,
> les copies validées et les diffusions de l'administration.

Comptes démo :
| Rôle | Email | Mot de passe |
|---|---|---|
| Admin | admin@uwb.ac.cd | admin123 |
| Prof | prof@uwb.ac.cd | prof123 |
| Étudiant | etudiant@uwb.ac.cd | etu123 |

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
- Uploads : couvertures (2 Mo max, `uploads/cov_*`), PDF (20 Mo max, `uploads/doc_*`).
