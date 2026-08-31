<?php
// ============================================================
//  SÉCURITÉ, RÔLES ET IDENTITÉ
//  Inclus en première ligne de chaque page d'administration.
//
//  TROIS RÔLES :
//    lecteur  -> lit les pages publiques, aucun accès au back-office
//    editeur  -> crée / modifie / supprime les pages ordinaires
//    admin    -> tout ça, plus les utilisateurs et les pages verrouillées
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ------------------------------------------------------------
   LECTURE DU RÔLE
   ------------------------------------------------------------ */

function role_actuel() {
    return $_SESSION['role'] ?? '';
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

/**
 * Nom lisible : "Naël Fleaura" si prénom et nom sont renseignés,
 * sinon on retombe sur l'identifiant de connexion.
 */
function nom_affiche() {
    $prenom = trim($_SESSION['prenom'] ?? '');
    $nom    = trim($_SESSION['nom_famille'] ?? '');
    $complet = trim($prenom . ' ' . $nom);
    return $complet !== '' ? $complet : ($_SESSION['identifiant'] ?? '');
}

/**
 * Initiales affichées dans le bouton rond du profil.
 * Première lettre du prénom + première lettre du nom.
 * Si l'utilisateur n'a ni prénom ni nom, on prend la première
 * lettre de son identifiant.
 */
function initiales() {
    $prenom = trim($_SESSION['prenom'] ?? '');
    $nom    = trim($_SESSION['nom_famille'] ?? '');

    $i = '';
    if ($prenom !== '') { $i .= mb_substr($prenom, 0, 1); }
    if ($nom    !== '') { $i .= mb_substr($nom, 0, 1); }

    if ($i === '') {
        $i = mb_substr($_SESSION['identifiant'] ?? 'A', 0, 1);
    }
    return mb_strtoupper($i);
}

/* ------------------------------------------------------------
   CONTRÔLES D'ACCÈS
   ------------------------------------------------------------ */

function exiger_connexion() {
    if (!est_connecte()) {
        header('Location: ../config/login.php');
        exit;
    }
}

/** Back-office : éditeur ou admin. Un lecteur repart sur le site. */
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
 * Une page dont admin_only vaut 1 (la page d'accueil, par exemple)
 * ne peut être modifiée ni supprimée que par un administrateur.
 * $page peut être la ligne complète ou juste la valeur admin_only.
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
    return est_admin() ? 'ESPACE ADMIN' : 'ESPACE ÉDITEUR';
}

/**
 * Destination juste après la connexion.
 *   admin              -> panneau d'administration
 *   éditeur / lecteur  -> page d'accueil du site
 */
function destination_apres_connexion($role) {
    return ($role === 'admin') ? '../admin/dashboard.php' : '../index.php';
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