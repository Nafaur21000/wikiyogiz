<?php
require_once '../config/auth.php';
exiger_connexion();

$upload_dir = __DIR__ . '/../upload/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$autorisees = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'docx', 'xlsx', 'pptx'];

if (isset($_FILES['upload']['name'])) {
    $nom_original = basename($_FILES['upload']['name']);
    $extension    = strtolower(pathinfo($nom_original, PATHINFO_EXTENSION));

    if (!in_array($extension, $autorisees, true)) {
        echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Type de fichier non autorisé.']]);
        exit;
    }

    $nom_propre = preg_replace('/[^a-zA-Z0-9._-]/', '-', $nom_original);
    $nom_final  = time() . '-' . $nom_propre;

    if (move_uploaded_file($_FILES['upload']['tmp_name'], $upload_dir . $nom_final)) {
        echo json_encode([
            'uploaded' => 1,
            'fileName' => $nom_final,
            'url'      => '../upload/' . $nom_final
        ]);
    } else {
        echo json_encode(['uploaded' => 0, 'error' => ['message' => "Erreur lors de l'upload."]]);
    }
}