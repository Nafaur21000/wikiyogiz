<?php
session_start();
require_once '../config/link_bdd_php.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['creer_page'])) {
    $titre = $_POST['titre'];
    $slug = strtolower(str_replace(' ', '-', $titre)); // Crée une URL simple
    $contenu = $_POST['contenu'];

    $stmt = $pdo->prepare("INSERT INTO pages (titre, contenu, slug) VALUES (?, ?, ?)");
    $stmt->execute([$titre, $contenu, $slug]);
    $message = "La page '$titre' a été créée !";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gérer les Pages</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
    <div class="menu">
        <h2 style="font-family: 'Oswald', sans-serif;">Espace Privé</h2>
        <hr style="width: 100%; border-color: #cbe3fd;">
        <a href="dashboard.php" class="menu-item" style="font-size: 1rem;">< Retour Dashboard</a>
    </div>
    <main class="main-container">
        <h1 style="font-family: 'Oswald', sans-serif;">Créer une nouvelle page</h1>
        
        <?php if (isset($message)) echo "<p style='color:green; font-family: Nunito; font-weight: bold;'>$message</p>"; ?>
        
        <form method="POST" style="display:flex; flex-direction:column; gap: 15px; width: 600px; font-family: Nunito;">
            <input type="text" name="titre" placeholder="Titre de la page" required style="padding: 10px; border-radius: 5px; border: 1px solid #ccc; font-size: 1.2rem;">
            <textarea name="contenu" placeholder="Contenu de votre page (HTML autorisé pour le moment)..." required style="padding: 10px; border-radius: 5px; border: 1px solid #ccc; height: 200px; font-family: 'Nunito', sans-serif; resize: vertical;"></textarea>
            <button type="submit" name="creer_page" style="background:#654178; color:white; padding:12px; font-family: 'Oswald', sans-serif; font-size: 1.1rem; cursor: pointer; border: none; border-radius: 5px;">Enregistrer la page</button>
        </form>
    </main>
</body>
</html>