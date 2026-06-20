<?php
// Désactivation temporaire de la sécurité pour tes tests
// session_start();
// if (!isset($_SESSION['user_id'])) die("Accès refusé.");

// Dossier de destination des images
$upload_dir = '../upload/';

if (isset($_FILES['upload']['name'])) {
    $file_name = basename($_FILES['upload']['name']);
    $file_tmp = $_FILES['upload']['tmp_name'];
    
    // Déplace l'image du dossier temporaire vers ton dossier upload
    if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
        $url = '../upload/' . $file_name;
        // Réponse attendue par CKEditor pour afficher l'image
        echo json_encode([
            'uploaded' => 1,
            'fileName' => $file_name,
            'url' => $url
        ]);
    } else {
        echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Erreur lors de l\'upload']]);
    }
}
?>