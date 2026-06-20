<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>wikiyogiz - Accueil</title>

    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Oswald:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./style/style.css">
</head>

<body>
    <div class="menu-toggle" id="menuToggle">☰ MENU</div>
    <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.3); padding-top: 20px;">
        <a href="./admin/editeur_page.php" class="menu-item" style="font-size: 1rem; color: red; text-transform: none;">
            + Créer / Éditer une page
        </a>
    </div>
    <nav class="menu" id="sideMenu">

        <a href="index.php" class="menu-item active">
            <img src="./images/logo_et_slogan.png" alt="Logo" class="menu-logo">
        </a>

        <div class="menu-group" id="dnaMenuGroup">
            <a href="#" class="menu-item toggle-submenu">
                SUPERYOGIZ DNA <span class="arrow">▼</span>
            </a>
            <div class="submenu">
                <a href="./pages/adn.php">OVERVIEW</a>
                <a href="./pages/adn.php#missions">MISSIONS & PURPOSE</a>
                <a href="./pages/adn.php#Tagline">TAGLINE & MOTTO</a>
                <a href="./pages/adn.php#values">VALUES</a>
                <a href="./pages/adn.php#Graphic_Charter">GRAPHIC CHARTER</a>
            </div>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="./admin/dashboard.php" class="menu-item" style="margin-top: 20px; font-size: 1rem; color: #cbe3fd;">Accès Back-Office</a>
        <?php endif; ?>
    </nav>

    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="./admin/dashboard.php" class="profil">
            <?php echo strtoupper(substr($_SESSION['nom'], 0, 1)); ?>
        </a>
    <?php else: ?>
        <a href="./config/login.php" class="profil">P</a>
    <?php endif; ?>


    <main class="contenu-central" id="mainContent">
        <h1 class="site-title" style="font-family: 'Oswald', sans-serif;  color: #654178;">WIKIYOGIZ</h1>
        <h2>PEOPLE FIRST. PERFORMANCE FOLLOWS.</h2>
        <img src="./images/Tree-pose-Cape.png" alt="Photo d'Hobbi" class="image">
    </main>

    <script src="./script/script.js"></script>
</body>

</html>