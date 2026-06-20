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
    <header>
        <!-- Remplacement avec le logo blanc -->
        <div class="logo-container">
            <img src="images/logo_et_slogan.png" alt="Logo Blanc WikiYogiz" class="logo-blanc">
        </div>
        
        <nav class="nav-bar">
            <!-- Premier menu (Accueil) - Style en police OSWALD -->
            <div class="menu-group font-oswald">
                <a href="index.php" class="menu-item">ACCUEIL</a>
            </div>

            <!-- Génération DYNAMIQUE des menus -->
            <?php foreach ($menus_principaux as $menu): 
                // Correction orthographique pour le menu SUPERYOGIZ DNA
                $titre_menu = htmlspecialchars($menu['titre']);
                if (strtoupper($titre_menu) === 'SUPERYOGIZ DNA') {
                    $titre_menu = 'SUPERYOGIZ DNA';
                }
                ?>
                <div class="menu-group font-oswald">
                    <?php if (isset($sous_menus[$menu['id']])): ?>
                        <!-- Si le menu a des sous-pages, on crée un menu déroulant -->
                        <a href="#" class="menu-item toggle-submenu">
                            <?php echo strtoupper($titre_menu); ?> <span class="arrow">▼</span>
                        </a>
                        <!-- Les tout derniers sous-menus passent en NUNITO via la classe font-nunito -->
                        <div class="submenu font-nunito">
                            <!-- Premier élément du sous-menu en OSWALD (Overview & Graphic Charter) -->
                            <a href="page.php?slug=<?php echo $menu['slug']; ?>" class="overview-item font-oswald">
                                OVERVIEW
                            </a>
                            
                            <?php foreach ($sous_menus[$menu['id']] as $sous_item): 
                                $titre_sous_item = htmlspecialchars($sous_item['titre']);
                                
                                // Suppression du "S" de MISSION si présent
                                if (strtoupper($titre_sous_item) === 'MISSIONS & PURPOSE') {
                                    $titre_sous_item = 'MISSION & PURPOSE';
                                }
                                ?>
                                <a href="page.php?slug=<?php echo $sous_item['slug']; ?>">
                                    <?php echo strtoupper($titre_sous_item); ?>
                                </a>
                            <?php endforeach; ?>

                            <!-- Ajout manuel de l'élément Graphic Charter en OSWALD si c'est le menu DNA -->
                            <?php if (strtoupper($menu['titre']) === 'SUPERYOGIZ DNA'): ?>
                                <a href="page.php?slug=graphic-charter" class="graphic-charter-item font-oswald">
                                    GRAPHIC CHARTER
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- Si le menu n'a pas de sous-page -->
                        <a href="page.php?slug=<?php echo $menu['slug']; ?>" class="menu-item">
                            <?php echo strtoupper($titre_menu); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- Espace Admin / Connexion -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="menu-group font-oswald">
                    <a href="admin/dashboard.php" class="menu-item admin-link" style="color: #ffcc00;">ADMIN</a>
                </div>
            <?php else: ?>
                <div class="menu-group font-oswald">
                    <a href="config/login.php" class="menu-item">CONNEXION</a>
                </div>
            <?php endif; ?>
        </nav>
    </header>

    <main class="content-wrapper">
        <section class="hero-section">
            <!-- Ajout des guillemets obligatoires autour du slogan -->
            <h1>Bienvenue sur WikiYogiz</h1>
            <p class="tagline">“People First, Performance Follow”</p>
        </section>

        <!-- Exemple d'un carré ou d'une section HOBBI (sans ombre) -->
        <section class="hobbi-square">
            <h2 class="font-oswald">SECTION HOBBI</h2>
            <p class="font-nunito">Ce bloc ne possède désormais plus aucune ombre portée sur son contour.</p>
        </section>
    </main>

    <script src="script/script.js"></script>
</body>
</html>