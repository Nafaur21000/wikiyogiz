<?php
session_start();
require_once './config/link_bdd_php.php';

// On récupère le slug dans l'URL (ex: page.php?slug=ma-page)
if (isset($_GET['slug'])) {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ?");
    $stmt->execute([$_GET['slug']]);
    $page = $stmt->fetch();
    
    if (!$page) {
        die("Cette page n'existe pas.");
    }
} else {
    die("Aucune page demandée.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page['titre']); ?> - Wikiyogiz</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Oswald:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./style/style.css">
</head>
<body>
    <div class="menu-toggle" id="menuToggle">☰ MENU</div>
    <nav class="menu" id="sideMenu">
        <div class="site-title">WIKIYOGIZ</div>
        <a href="index.php" class="menu-item">< Retour Accueil</a>
        
        <!-- Ajout du lien vers l'éditeur directement dans le menu comme demandé -->
        <div style="margin-top: 40px; border-top: 1px solid white; padding-top: 20px;">
            <a href="./admin/editeur_page.php" class="menu-item" style="font-size: 1rem; color: #ffeb3b;">+ Créer une page (Test)</a>
        </div>
    </nav>

    <main class="contenu-central" id="mainContent" style="padding: 50px; justify-content: flex-start; align-items: flex-start;">
        <h1 style="font-family: 'Oswald', sans-serif;"><?php echo htmlspecialchars($page['titre']); ?></h1>
        
        <!-- Affichage du contenu généré par CKEditor -->
        <div style="font-family: 'Nunito', sans-serif; width: 100%;">
            <?php echo $page['contenu']; ?>
        </div>
    </main>
    <script src="./script/script.js"></script>
</body>
</html>