<?php
// ============================================================
//  RÉCEPTION DES FICHIERS ET DES IMAGES
//
//  Deux clients l'appellent :
//    1. CKEditor  -> champ nommé "upload"
//    2. Le bloc "Envoyer un fichier" de editeur_page.php -> "fichier"
//
//  Réponse : toujours du JSON, jamais de HTML. Si la page renvoyait
//  du HTML (une erreur PHP, une redirection), CKEditor afficherait
//  un message incompréhensible : c'était la cause principale des
//  uploads qui « ne marchaient pas ».
// ============================================================

// On coupe l'affichage des erreurs à l'écran : une notice PHP
// suffirait à casser le JSON et donc l'upload.
ini_set('display_errors', '0');
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once '../config/auth.php';

/** Réponse uniforme, puis arrêt immédiat du script. */
function repondre($ok, $url = '', $message = '', $nom = '') {
    echo json_encode([
        'uploaded' => $ok ? 1 : 0,
        'fileName' => $nom,
        'url'      => $url,
        'error'    => $ok ? null : ['message' => $message],
    ]);
    exit;
}

// --- SÉCURITÉ ---
// On ne redirige pas (CKEditor ne saurait pas quoi en faire),
// on renvoie une erreur JSON lisible.
if (!est_connecte()) {
    repondre(false, '', "Session expirée. Reconnecte-toi puis réessaie.");
}
if (!peut_gerer_pages()) {
    repondre(false, '', "Ton rôle ne permet pas d'envoyer des fichiers.");
}

// --- DOSSIER DE DESTINATION ---
// __DIR__ = le dossier admin/. On remonte d'un cran vers /upload/.
$upload_dir = __DIR__ . '/../upload/';

if (!is_dir($upload_dir) && !@mkdir($upload_dir, 0755, true)) {
    repondre(false, '', "Le dossier /upload n'existe pas et n'a pas pu être créé. Crée-le à la racine du site en FTP, avec les droits d'écriture (755).");
}
if (!is_writable($upload_dir)) {
    repondre(false, '', "Le dossier /upload existe mais n'est pas accessible en écriture. Passe ses droits à 755 en FTP.");
}

// --- RÉCUPÉRATION DU FICHIER ---
// CKEditor envoie "upload", notre bloc maison envoie "fichier".
$champ = null;
foreach (['upload', 'fichier'] as $c) {
    if (isset($_FILES[$c])) { $champ = $c; break; }
}
if ($champ === null) {
    repondre(false, '', "Aucun fichier reçu.");
}

$f = $_FILES[$champ];

// --- ERREURS RENVOYÉES PAR PHP LUI-MÊME ---
// Sans ce bloc, un fichier trop lourd échouait en silence.
if ($f['error'] !== UPLOAD_ERR_OK) {
    $messages = [
        UPLOAD_ERR_INI_SIZE   => "Fichier trop lourd (limite du serveur : " . ini_get('upload_max_filesize') . ").",
        UPLOAD_ERR_FORM_SIZE  => "Fichier trop lourd.",
        UPLOAD_ERR_PARTIAL    => "Envoi interrompu, réessaie.",
        UPLOAD_ERR_NO_FILE    => "Aucun fichier sélectionné.",
        UPLOAD_ERR_NO_TMP_DIR => "Dossier temporaire manquant sur le serveur.",
        UPLOAD_ERR_CANT_WRITE => "Écriture impossible sur le serveur.",
        UPLOAD_ERR_EXTENSION  => "Envoi bloqué par une extension PHP.",
    ];
    repondre(false, '', $messages[$f['error']] ?? "Erreur d'envoi (code {$f['error']}).");
}

// --- LISTE BLANCHE D'EXTENSIONS ---
// On autorise explicitement. Sans ce filtre, quelqu'un pourrait
// déposer un .php et le faire exécuter par le serveur.
$autorisees = [
    'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif',
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
    'txt', 'csv', 'zip',
];

$nom_original = basename($f['name']);
$extension    = strtolower(pathinfo($nom_original, PATHINFO_EXTENSION));

if (!in_array($extension, $autorisees, true)) {
    repondre(false, '', "Type de fichier non autorisé (.$extension). Formats acceptés : " . implode(', ', $autorisees) . ".");
}

// --- NOM DE FICHIER PROPRE ET UNIQUE ---
// On retire accents et caractères spéciaux, puis on préfixe par
// l'heure pour que deux fichiers du même nom ne s'écrasent pas.
$sans_ext   = pathinfo($nom_original, PATHINFO_FILENAME);
$sans_ext   = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $sans_ext);
$sans_ext   = preg_replace('/[^a-zA-Z0-9._-]/', '-', $sans_ext);
$sans_ext   = trim($sans_ext, '-');
if ($sans_ext === '') { $sans_ext = 'fichier'; }

$nom_final = date('Ymd-His') . '-' . $sans_ext . '.' . $extension;

if (!move_uploaded_file($f['tmp_name'], $upload_dir . $nom_final)) {
    repondre(false, '', "Le fichier n'a pas pu être enregistré sur le serveur.");
}

// --- URL RENVOYÉE : ABSOLUE, ET C'EST ESSENTIEL ---
// L'ancienne version renvoyait "../upload/photo.png". Depuis
// /admin/ cela pointait bien vers /upload/, mais une fois la page
// affichée à la racine du site (page.php), "../upload/" désignait
// un dossier situé AU-DESSUS du site : image cassée.
// Une URL commençant par "/" part toujours de la racine.
repondre(true, '/upload/' . $nom_final, '', $nom_final);