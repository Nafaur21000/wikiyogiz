<?php
// ============================================================
//  RÉCEPTION DES FICHIERS ET DES IMAGES
//  Réponse toujours en JSON, jamais en HTML : c'est ce qui
//  permet à l'éditeur d'afficher un message compréhensible.
//  Les messages sont traduits dans la langue de l'utilisateur.
// ============================================================

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
if (!est_connecte())      { repondre(false, '', t('up.session')); }
if (!peut_gerer_pages())  { repondre(false, '', t('up.role')); }

// --- DOSSIER DE DESTINATION ---
$upload_dir = __DIR__ . '/../upload/';

if (!is_dir($upload_dir) && !@mkdir($upload_dir, 0755, true)) {
    repondre(false, '', t('up.dossier_absent'));
}
if (!is_writable($upload_dir)) {
    repondre(false, '', t('up.dossier_lecture'));
}

// --- RÉCUPÉRATION DU FICHIER ---
$champ = null;
foreach (['upload', 'fichier'] as $c) {
    if (isset($_FILES[$c])) { $champ = $c; break; }
}
if ($champ === null) { repondre(false, '', t('up.aucun')); }

$f = $_FILES[$champ];

// --- ERREURS RENVOYÉES PAR PHP LUI-MÊME ---
if ($f['error'] !== UPLOAD_ERR_OK) {
    $messages = [
        UPLOAD_ERR_INI_SIZE   => t('up.trop_lourd') . ' (' . ini_get('upload_max_filesize') . ')',
        UPLOAD_ERR_FORM_SIZE  => t('up.trop_lourd'),
        UPLOAD_ERR_PARTIAL    => t('up.interrompu'),
        UPLOAD_ERR_NO_FILE    => t('up.aucun'),
        UPLOAD_ERR_NO_TMP_DIR => t('up.echec'),
        UPLOAD_ERR_CANT_WRITE => t('up.echec'),
        UPLOAD_ERR_EXTENSION  => t('up.echec'),
    ];
    repondre(false, '', $messages[$f['error']] ?? t('up.echec'));
}

// --- LISTE BLANCHE D'EXTENSIONS ---
$autorisees = [
    'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif',
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
    'txt', 'csv', 'zip',
];

$nom_original = basename($f['name']);
$extension    = strtolower(pathinfo($nom_original, PATHINFO_EXTENSION));

if (!in_array($extension, $autorisees, true)) {
    repondre(false, '', t('up.non_autorise') . ' (.' . $extension . ')');
}

// --- NOM DE FICHIER PROPRE ET UNIQUE ---
$sans_ext = pathinfo($nom_original, PATHINFO_FILENAME);
$sans_ext = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $sans_ext);
$sans_ext = preg_replace('/[^a-zA-Z0-9._-]/', '-', $sans_ext);
$sans_ext = trim($sans_ext, '-');
if ($sans_ext === '') { $sans_ext = 'fichier'; }

$nom_final = date('Ymd-His') . '-' . $sans_ext . '.' . $extension;

if (!move_uploaded_file($f['tmp_name'], $upload_dir . $nom_final)) {
    repondre(false, '', t('up.echec'));
}

// URL absolue : "/upload/x.png" fonctionne depuis n'importe quelle page.
repondre(true, '/upload/' . $nom_final, '', $nom_final);