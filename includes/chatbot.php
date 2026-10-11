<?php
// ============================================================
// UWB.Learn — Chatbot : moteur de réponses LOCAL (sans clé API)
// Repli automatique quand Groq n'est pas configuré/indisponible.
// Réutilise la normalisation de includes/ia.php.
// ============================================================
require_once __DIR__ . '/ia.php';

/**
 * Réponse locale du chatbot (FAQ plateforme). Toujours une chaîne non vide.
 * $u : utilisateur connecté (['role','nom','faculte','matricule', ...]).
 */
function uwb_chat_local(string $message, array $u = []): string {
    $n = ia_normaliser($message);
    $has = function (array $kw) use ($n): bool {
        foreach ($kw as $k) if (mb_strpos($n, ia_normaliser($k)) !== false) return true;
        return false;
    };
    $role = (string)($u['role'] ?? '');
    $prenom = explode(' ', trim((string)($u['nom'] ?? '')))[0] ?? '';
    $qui = $prenom !== '' ? $prenom : 'étudiant(e)';

    // 1) Salutations
    if ($has(['bonjour','bonsoir','salut','hello','hi','coucou','ca va'])) {
        return "Bonjour {$qui} 👋 Je suis l'assistant UWB.Learn. Je peux vous aider sur : les cours en direct et les replays, la bibliothèque, les évaluations et l'anti-triche, la connexion (matricule), les notifications et les contacts. Que voulez-vous savoir ?";
    }

    // 2) Remerciements
    if ($has(['merci','thanks','super','parfait','ok merci'])) {
        return "Avec plaisir 😊 N'hésitez pas si vous avez une autre question sur la plateforme.";
    }

    // 3) Contacts / support
    if ($has(['contact','contacter','support','assistance','email','telephone','numero','adresse','reclamation','nous joindre'])) {
        return "Contacts UWB — Adresse : 12 Avenue Du Kasai, Gombe, Kinshasa. Téléphone : +243 815 431 601. Email : infos@uwbcongo.org (rectorat : rectorat@uwbcongo.org). Site : www.uwbcongo.org.";
    }

    // 4) Connexion / mot de passe / matricule
    if ($has(['mot de passe','password','connexion','connecter','login','matricule','identifiant','compte','inscription','inscrire','creer un compte'])) {
        return "Pour vous connecter : utilisez votre Matricule (étudiants) OU votre email, avec le mot de passe fourni par l'administration. Les comptes ne s'auto-inscrivent pas : ils sont créés par l'administration. Mot de passe oublié ou compte manquant ? Écrivez à infos@uwbcongo.org.";
    }

    // 5) Cours en direct / Meet / replay
    if ($has(['cours','seance','live','direct','meet','google meet','rejoindre','replay','enregistrement','video','camera','revoir'])) {
        return "Les cours en direct se rejoignent depuis « Mes cours » (étudiant) ou « Mes cours » (professeur). Le bouton « Rejoindre » est actif uniquement pendant la fenêtre de la séance (début → fin). Après la fin, si le professeur a déposé un enregistrement, le bouton jaune « Revoir le cours » donne accès au replay. Aucun lien n'est partagé en dehors de la plateforme.";
    }

    // 6) Évaluations / notes / anti-triche
    if ($has(['evaluation','examen','quiz','test','note','resultat','copie','rendre','triche','plein ecran','tentative','minuteur','chrono','temporisateur'])) {
        return "Les évaluations se passent dans l'espace « Évaluations ». Vous répondez en ligne, puis votre copie est corrigée et validée par le professeur ; la note s'affiche dans « Résultats ». L'épreuve se déroule en conditions d'examen : restez concentré(e), restez sur la page et respectez les consignes affichées à l'écran. Pour toute question, contactez votre professeur ou infos@uwbcongo.org.";
    }

    // 7) Bibliothèque
    if ($has(['bibliotheque','livre','ouvrage','ressource','pdf','lecture','lire','document','memoire','tfc'])) {
        return "La « Bibliothèque » regroupe les ressources (PDF, mémoires, ouvrages). Vous pouvez rechercher par titre, auteur ou catégorie, filtrer par faculté et ouvrir un document en lecture. Les suggestions s'appuient sur vos recherches récentes.";
    }

    // 8) Notifications
    if ($has(['notification','notif','alerte','message','cloche','prevenir'])) {
        return "La cloche 🔔 en haut à droite affiche vos notifications : nouvelles évaluations, sessions, copies soumises, notes validées… Cliquez sur « Tout marquer comme lu » dans la page Notifications pour les archiver.";
    }

    // 9) Rôles / espaces
    if ($has(['professeur','prof','enseignant','admin','administrateur','etudiant','role','espace','tableau de bord','dashboard'])) {
        return "La plateforme a trois espaces : Étudiant (cours, bibliothèque, évaluations, résultats), Professeur (cours & séances, création d'évaluations, validation IA, anti-triche) et Administrateur (comptes, facultés, catalogue). Vous êtes connecté(e) en tant que « {$role} ».";
    }

    // 10) Faculté / niveau
    if ($has(['faculte','filiere','niveau','promotion','l1','l2','l3','m1','m2'])) {
        $fac = trim((string)($u['faculte'] ?? ''));
        return "Les contenus (cours et évaluations) sont filtrés selon votre faculté" . ($fac !== '' ? " ({$fac})" : '') . " et votre niveau. Si votre faculté semble incorrecte, contactez l'administration (infos@uwbcongo.org).";
    }

    // 11) Questions « étude » détectées → orienter
    if ($has(['explique','c est quoi','qu est ce que','definition','difference','comment calculer','resoudre','exercice','theoreme','formule'])) {
        return "Bonne question ! Pour une réponse détaillée et personnalisée sur une notion de cours, l'assistant IA avancé doit être activé (clé Groq à configurer par l'administration). En attendant, je peux vous guider sur la plateforme : cours en direct, replays, bibliothèque, évaluations. Reformulez ou précisez le sujet et je fais au mieux.";
    }

    // 12) Fallback
    return "Je n'ai pas la réponse exacte, mais je peux vous aider sur :\n• rejoindre un cours en direct ou voir un replay (Mes cours)\n• passer une évaluation et comprendre l'anti-triche (Évaluations)\n• chercher une ressource (Bibliothèque)\n• la connexion par matricule et les contacts\nPrécisez votre besoin, ou écrivez à infos@uwbcongo.org.";
}
