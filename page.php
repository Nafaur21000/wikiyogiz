<?php
session_start();
require_once 'config/link_bdd_php.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: index.php');
    exit();
}

// Récupération de la page demandée via son SLUG / URL
$stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ?");
$stmt->execute([$slug]);
$page = $stmt->fetch(PDO::FETCH_ASSOC);

// Si la page n'existe pas en BDD
if (!$page) {
    header("HTTP/1.0 404 Not Found");
    $page_titre = "Page Introuvable";
    $page_contenu = "<p>Désolé, la page que vous recherchez n'existe pas ou a été déplacée.</p>";
} else {
    $page_titre = $page['titre'];
    $page_contenu = $page['contenu'];
    
    // Correction de l'affichage du titre spécifique à la mission
    if (strtoupper($page_titre) === 'MISSIONS & PURPOSE') {
        $page_titre = 'MISSION & PURPOSE';
    }
}

// Récupération globale du menu pour l'entête
$stmt_parents = $pdo->query("SELECT * FROM pages WHERE parent_id IS NULL ORDER BY ordre ASC");
$menus_principaux = $stmt_parents->fetchAll(PDO::FETCH_ASSOC);

$stmt_enfants = $pdo->query("SELECT * FROM pages WHERE parent_id IS NOT NULL ORDER BY ordre ASC");
$sous_pages = $stmt_enfants->fetchAll(PDO::FETCH_ASSOC);

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
    <title><?php echo htmlspecialchars($page_titre); ?> - WikiYogiz</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="images/logo_et_slogan.png" alt="Logo Blanc WikiYogiz" class="logo-blanc">
        </div>
        
        <nav class="nav-bar">
            <div class="menu-group font-oswald">
                <a href="index.php" class="menu-item">ACCUEIL</a>
            </div>

            <?php foreach ($menus_principaux as $menu): ?>
                <div class="menu-group font-oswald">
                    <?php if (isset($sous_menus[$menu['id']])): ?>
                        <a href="#" class="menu-item toggle-submenu">
                            <?php echo htmlspecialchars(strtoupper($menu['titre'] === 'SUPERYOGIZ DNA' ? 'SUPERYOGIZ DNA' : $menu['titre'])); ?> <span class="arrow">▼</span>
                        </a>
                        <div class="submenu font-nunito">
                            <a href="page.php?slug=<?php echo $menu['slug']; ?>" class="overview-item font-oswald">OVERVIEW</a>
                            <?php foreach ($sous_menus[$menu['id']] as $sous_item): 
                                $t_child = $sous_item['titre'];
                                if (strtoupper($t_child) === 'MISSIONS & PURPOSE') { $t_child = 'MISSION & PURPOSE'; }
                                ?>
                                <a href="page.php?slug=<?php echo $sous_item['slug']; ?>">
                                    <?php echo htmlspecialchars(strtoupper($t_child)); ?>
                                </a>
                            <?php endforeach; ?>
                            <?php if (strtoupper($menu['titre']) === 'SUPERYOGIZ DNA'): ?>
                                <a href="page.php?slug=graphic-charter" class="graphic-charter-item font-oswald">GRAPHIC CHARTER</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <a href="page.php?slug=<?php echo $menu['slug']; ?>" class="menu-item">
                            <?php echo htmlspecialchars(strtoupper($menu['titre'])); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </nav>
    </header>

    <main class="content-wrapper">
        <article class="page-article">
            <!-- Titre forcé en police OSWALD via le CSS global -->
            <h1 class="page-title"><?php echo htmlspecialchars(strtoupper($page_titre)); ?></h1>
            
            <!-- Contenu forcé en police NUNITO -->
            <div class="page-content font-nunito">
                <?php echo $page_contenu; ?>
            </div>
        </article>
    </main>

    <script src="script/script.js"></script>
</body>
</html>