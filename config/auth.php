<?php
/**
 * SÉCURITÉ PARTAGÉE DU BACK-OFFICE
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function exiger_connexion() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../config/login.php');
        exit;
    }
}

function exiger_admin() {
    exiger_connexion();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        die("Accès refusé : cette section est réservée aux administrateurs.");
    }
}

function est_admin() {
    return (($_SESSION['role'] ?? '') === 'admin');
}

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