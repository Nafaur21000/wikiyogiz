<?php
// ============================================================
//  SÉCURITÉ, RÔLES ET IDENTITÉ
//
//  TROIS RÔLES :
//    lecteur  -> lit les pages, aucun accès au back-office
//    editeur  -> crée / modifie / supprime les pages ordinaires
//    admin    -> tout ça, plus les utilisateurs et les pages verrouillées
//
//  Ce fichier charge aussi la couche de langue : toute page qui
//  inclut auth.php dispose donc de t() et de langue().
// ============================================================

require_once __DIR__ . '/lang.php';

/* ------------------------------------------------------------
   LECTURE DU RÔLE
   ------------------------------------------------------------ */

function role_actuel() {
    return $_SESSION['role'] ?? '';
}

/** Nom du rôle traduit, pour l'affichage. */
function role_affiche() {
    $r = role_actuel();
    if ($r === '') { return ''; }
    return t('role.' . $r);
}

function est_admin() {
    return role_actuel() === 'admin';
}

/** Un éditeur ET un admin peuvent gérer les pages. */
function peut_gerer_pages() {
    return in_array(role_actuel(), ['admin', 'editeur'], true);
}

function est_connecte() {
    return isset($_SESSION['user_id']);
}

/* ------------------------------------------------------------
   IDENTITÉ DE L'UTILISATEUR
   ------------------------------------------------------------ */

function nom_affiche() {
    $prenom  = trim($_SESSION['prenom'] ?? '');
    $nom     = trim($_SESSION['nom_famille'] ?? '');
    $complet = trim($prenom . ' ' . $nom);
    return $complet !== '' ? $complet : ($_SESSION['identifiant'] ?? '');
}

/** Première lettre du prénom + première lettre du nom. */
function initiales() {
    $prenom = trim($_SESSION['prenom'] ?? '');
    $nom    = trim($_SESSION['nom_famille'] ?? '');

    $i = '';
    if ($prenom !== '') { $i .= mb_substr($prenom, 0, 1); }
    if ($nom    !== '') { $i .= mb_substr($nom, 0, 1); }
    if ($i === '')      { $i  = mb_substr($_SESSION['identifiant'] ?? 'A', 0, 1); }

    return mb_strtoupper($i);
}

/* ------------------------------------------------------------
   CONTRÔLES D'ACCÈS
   ------------------------------------------------------------ */

/**
 * Il faut être connecté.
 * $prefixe : '' depuis la racine du site, '../' depuis un sous-dossier.
 * Le wiki est entièrement privé : même la lecture demande un compte.
 */
function exiger_connexion($prefixe = '../') {
    if (!est_connecte()) {
        header('Location: ' . $prefixe . 'config/login.php');
        exit;
    }
}

/** Back-office : éditeur ou admin. Un lecteur repart sur l'accueil. */
function exiger_editeur() {
    exiger_connexion();
    if (!peut_gerer_pages()) {
        header('Location: ../index.php');
        exit;
    }
}

/** Réservé aux administrateurs. */
function exiger_admin() {
    exiger_connexion();
    if (!est_admin()) {
        header('Location: ../index.php');
        exit;
    }
}

/**
 * PAGES VERROUILLÉES.
 * admin_only = 1 -> modification, suppression ET déplacement
 * réservés à l'administrateur.
 */
function peut_modifier_page($page) {
    $verrouillee = is_array($page) ? (int)($page['admin_only'] ?? 0) : (int)$page;
    if ($verrouillee === 1) {
        return est_admin();
    }
    return peut_gerer_pages();
}

/* ------------------------------------------------------------
   AFFICHAGE
   ------------------------------------------------------------ */

function nom_espace() {
    return est_admin() ? t('nav.espace_admin') : t('nav.espace_editeur');
}

/**
 * Destination après la connexion.
 * Tous les rôles arrivent sur la page d'accueil du site ;
 * le back-office se rejoint ensuite par le bouton profil.
 */
function destination_apres_connexion($role = null) {
    return '../index.php';
}

/* ------------------------------------------------------------
   FABRICATION DES URL DE PAGES
   ------------------------------------------------------------ */

function creer_slug($titre) {
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $titre);
    $slug = strtolower($slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug !== '' ? $slug : 'page';
}

function slug_unique(PDO $pdo, $slug, $id_exclu = null) {
    $base = $slug;
    $i = 1;
    while (true) {
        if ($id_exclu) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pages WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $id_exclu]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pages WHERE slug = ?");
            $stmt->execute([$slug]);
        }
        if ((int)$stmt->fetchColumn() === 0) {
            return $slug;
        }
        $i++;
        $slug = $base . '-' . $i;
    }
}