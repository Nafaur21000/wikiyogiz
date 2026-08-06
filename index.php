<?php
session_start();
require_once 'config/link_bdd_php.php';

// 1. Récupération des pages parentes (les menus principaux)
$stmt_parents = $pdo->query("SELECT * FROM pages WHERE parent_id IS NULL ORDER BY ordre ASC, date_creation DESC");
$menus_principaux = $stmt_parents->fetchAll(PDO::FETCH_ASSOC);

// 2. Récupération de toutes les sous-pages (les sous-menus)
$stmt_enfants = $pdo->query("SELECT * FROM pages WHERE parent_id IS NOT NULL ORDER BY ordre ASC");
$sous_pages = $stmt_enfants->fetchAll(PDO::FETCH_ASSOC);

// Organisation des sous-pages par ID de parent
$sous_menus = [];
foreach ($sous_pages as $sp) {
    $sous_menus[$sp['parent_id']][] = $sp;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WikiYogiz - Accueil</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>

    <!-- MENU LATÉRAL -->
    <aside class="menu" id="sideMenu">
        <img src="images/logo_et_slogan.png" alt="Logo WikiYogiz" class="menu-logo">
        <div class="site-title">WikiYogiz</div>

        <a href="index.php" class="menu-item">ACCUEIL</a>

        <?php foreach ($menus_principaux as $menu):
            $titre_menu = htmlspecialchars($menu['titre']);
            if (strtoupper($titre_menu) === 'SUPERYOGIZ DNA') {
                $titre_menu = 'SUPERYOGIZ DNA';
            }
            ?>
            <div class="menu-group">
                <?php if (isset($sous_menus[$menu['id']])): ?>
                    <a href="#" class="menu-item toggle-submenu">
                        <?php echo strtoupper($titre_menu); ?> <span class="arrow">▼</span>
                    </a>
                    <div class="submenu">
                        <a href="page.php?slug=<?php echo $menu['slug']; ?>">
                            OVERVIEW
                        </a>
                        <?php foreach ($sous_menus[$menu['id']] as $sous_item):
                            $titre_sous_item = htmlspecialchars($sous_item['titre']);
                            if (strtoupper($titre_sous_item) === 'MISSIONS & PURPOSE') {
                                $titre_sous_item = 'MISSION & PURPOSE';
                            }
                            ?>
                            <a href="page.php?slug=<?php echo $sous_item['slug']; ?>">
                                <?php echo strtoupper($titre_sous_item); ?>
                            </a>
                        <?php endforeach; ?>
                        <?php if (strtoupper($menu['titre']) === 'SUPERYOGIZ DNA'): ?>
                            <a href="page.php?slug=graphic-charter">
                                GRAPHIC CHARTER
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <a href="page.php?slug=<?php echo $menu['slug']; ?>" class="menu-item">
                        <?php echo strtoupper($titre_menu); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </aside>

    <!-- BOUTON HAMBURGER (ouvrir/fermer le menu) -->
    <div class="menu-toggle" id="menuToggle">☰</div>

    <!-- BOUTON PROFIL -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="admin/dashboard.php" class="profil">ADM</a>
    <?php else: ?>
        <a href="config/login.php" class="profil">P</a>
    <?php endif; ?>

    <!-- ZONE DE CONTENU -->
    <main class="contenu-central main-container" id="mainContent">
        <h1 class="site-title" style="color:#654178;">Bienvenue sur WikiYogiz</h1>
        <p class="tagline">“People First, Performance Follows”</p>

        <img src="images/logo-contour.png" alt="Illustration" class="image">

        <section class="hobbi-section">
            <h2>SECTION HOBBI</h2>
            <p>Ce bloc ne possède désormais plus aucune ombre portée sur son contour.</p>
        </section>
    </main>

    <script src="script/script.js"></script>
</body>
</html>