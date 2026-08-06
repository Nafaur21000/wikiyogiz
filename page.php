<?php
session_start();
require_once 'config/link_bdd_php.php';

$base = '';
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if ($slug === '') {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ?");
$stmt->execute([$slug]);
$page = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$page) {
    header("HTTP/1.0 404 Not Found");
    $page_titre   = "Page introuvable";
    $page_contenu = "<p>Désolé, la page que vous recherchez n'existe pas ou a été déplacée.</p>";
} else {
    $page_titre   = $page['titre'];
    $page_contenu = $page['contenu'];
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

<?php include 'includes/menu.php'; ?>

<main class="contenu-central page-mode" id="mainContent">
    <article class="page-article">
        <h1 class="page-title"><?php echo htmlspecialchars(wy_titre_menu($page_titre)); ?></h1>
        <div class="page-content">
            <?php echo $page_contenu; ?>
        </div>
    </article>
</main>

<script src="script/script.js"></script>
</body>
</html>