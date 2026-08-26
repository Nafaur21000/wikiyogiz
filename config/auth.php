<?php
// ============================================================
//  SÉCURITÉ ET RÔLES
//  Inclus en première ligne de chaque page d'administration.
//
//  TROIS RÔLES :
//    lecteur  -> lit les pages publiques, aucun accès au back-office
//    editeur  -> crée / modifie / supprime les pages
//    admin    -> tout ça, plus la gestion des utilisateurs
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ------------------------------------------------------------
   LECTURE DU RÔLE
   ------------------------------------------------------------ */

/** Renvoie le rôle de l'utilisateur connecté, ou '' si visiteur. */
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
   CONTRÔLES D'ACCÈS
   ------------------------------------------------------------ */

/** Porte d'entrée : il faut être connecté. */
function exiger_connexion() {
    if (!est_connecte()) {
        header('Location: ../config/login.php');
        exit;
    }
}

/**
 * Accès au back-office : éditeur ou admin.
 * Un lecteur connecté est renvoyé vers le site public, sans message
 * d'erreur : il n'a simplement rien à faire ici.
 */
function exiger_editeur() {
    exiger_connexion();
    if (!peut_gerer_pages()) {
        header('Location: ../index.php');
        exit;
    }
}

/** Réservé aux administrateurs (gestion des utilisateurs). */
function exiger_admin() {
    exiger_connexion();
    if (!est_admin()) {
        header('Location: ../index.php');
        exit;
    }
}

/* ------------------------------------------------------------
   AFFICHAGE
   ------------------------------------------------------------ */

/**
 * Titre du menu latéral du back-office.
 * L'éditeur voit "ESPACE ÉDITEUR", l'administrateur "ESPACE ADMIN".
 */
function nom_espace() {
    return est_admin() ? 'ESPACE ADMIN' : 'ESPACE ÉDITEUR';
}

/**
 * Où envoyer l'utilisateur juste après sa connexion.
 *   admin              -> le panneau d'administration
 *   éditeur / lecteur  -> la page d'accueil du site
 */
function destination_apres_connexion($role) {
    return ($role === 'admin') ? '../admin/dashboard.php' : '../index.php';
}

/* ------------------------------------------------------------
   FABRICATION DES URL DE PAGES
   ------------------------------------------------------------ */

/** "Missions & Purpose" -> "missions-purpose" */
function creer_slug($titre) {
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $titre);
    $slug = strtolower($slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug !== '' ? $slug : 'page';
}

/** Ajoute -2, -3... tant que le slug est déjà pris (colonne UNIQUE). */
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