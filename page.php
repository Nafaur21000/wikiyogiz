<?php
// ============================================================
//  AFFICHAGE D'UNE PAGE DU WIKI
//  Une seule page PHP affiche un nombre illimité d'articles.
//  Le titre et le contenu sont pris dans la langue en cours ;
//  si la version anglaise est vide, le français s'affiche.
// ============================================================
require_once 'config/auth.php';
require_once 'config/link_bdd_php.php';

exiger_connexion('');

$base = '';
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if ($slug === '' || $slug === 'accueil') {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ?");
$stmt->execute([$slug]);
$page = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$page) {
    header("HTTP/1.0 404 Not Found");
    $page_titre   = t('site.introuvable');
    $page_contenu = '<p>' . t('site.introuvable_txt') . '</p>';
    $id_page      = null;
} else {
    $page_titre   = champ_page($page, 'titre');
    $page_contenu = champ_page($page, 'contenu');
    $id_page      = $page['id'];
}

$peut_modifier = $id_page && peut_modifier_page($page);
?>
<!DOCTYPE html>
<html lang="<?php echo langue(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_titre); ?> - WikiYogiz</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>

<?php include 'includes/menu.php'; ?>

<main class="contenu-central page-mode" id="mainContent">
    <article class="page-article">
        <h1 class="page-title"><?php echo htmlspecialchars(wy_titre_menu($page_titre)); ?></h1>

        <?php if ($peut_modifier): ?>
            <p><a href="admin/editeur_page.php?id=<?php echo $id_page; ?>" class="btn btn-small"><?php echo te('site.modifier_page'); ?></a></p>
        <?php endif; ?>

        <div class="page-content">
            <?php
            // Pas de htmlspecialchars : le contenu vient de l'éditeur
            // et doit être interprété comme du HTML.
            echo $page_contenu;
            ?>
        </div>
    </article>
</main>

<script src="script/script.js"></script>
</body>
</html>