<?php
// ============================================================
//  PAGE D'ACCUEIL
//
//  Le wiki est privé : sans compte, on arrive sur la connexion.
//  Le bandeau (logo, nom, tagline, Hobbi) est fixe.
//  Le texte vient de la base, dans la langue en cours.
// ============================================================
require_once 'config/auth.php';
require_once 'config/link_bdd_php.php';

exiger_connexion('');   // '' : on est à la racine

$base = '';

// --- CONTENU PAR DÉFAUT ---
// Sert uniquement à remplir la base la toute première fois.
$contenu_par_defaut = <<<HTML
<section class="wiki-section">
    <h2>L'OBJECTIF DE CE WIKI</h2>
    <p>
        WikiYogiz est la mémoire partagée de Superyogiz. Il rassemble en un seul endroit
        notre ADN, nos missions, nos valeurs et nos repères de marque, pour que personne
        n'ait à chercher deux fois la même information.
    </p>
</section>
HTML;

$contenu_defaut_en = <<<HTML
<section class="wiki-section">
    <h2>WHAT THIS WIKI IS FOR</h2>
    <p>
        WikiYogiz is the shared memory of Superyogiz. It gathers in one place our DNA,
        our missions, our values and our brand guidelines, so that nobody has to look
        for the same information twice.
    </p>
</section>
HTML;

// --- LECTURE EN BASE ---
$stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? LIMIT 1");
$stmt->execute(['accueil']);
$accueil = $stmt->fetch(PDO::FETCH_ASSOC);

if ($accueil) {
    $id_accueil = $accueil['id'];
    // champ_page choisit l'anglais si disponible, sinon le français
    $contenu_accueil = champ_page($accueil, 'contenu');
} else {
    // Première visite : on crée la page dans les deux langues.
    try {
        $ins = $pdo->prepare(
            "INSERT INTO pages (titre, titre_en, slug, contenu, contenu_en, parent_id, ordre, admin_only)
             VALUES ('Accueil', 'Home', 'accueil', ?, ?, NULL, 0, 1)"
        );
        $ins->execute([$contenu_par_defaut, $contenu_defaut_en]);
        $id_accueil = $pdo->lastInsertId();
    } catch (PDOException $e) {
        $id_accueil = null;
    }
    $contenu_accueil = (langue() === 'en') ? $contenu_defaut_en : $contenu_par_defaut;
}

$peut_modifier_accueil = est_admin() && $id_accueil;
?>
<!DOCTYPE html>
<html lang="<?php echo langue(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WikiYogiz</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>

<?php include 'includes/menu.php'; ?>

<main class="contenu-central" id="mainContent">

    <img src="images/logo-contour.png" alt="WikiYogiz" class="accueil-logo">

    <h1 class="nom-site">WIKIYOGIZ</h1>

    <!-- La tagline est un élément de marque : elle reste en anglais
         dans les deux langues, comme le nom du site. -->
    <p class="tagline">&ldquo;People First. Performance Follows.&rdquo;</p>

    <img src="images/Tree-pose-Cape.png" alt="Hobbi" class="image-hobbi">

    <?php if ($peut_modifier_accueil): ?>
        <a href="admin/editeur_page.php?id=<?php echo $id_accueil; ?>" class="btn btn-small">
            <?php echo te('site.modifier_page'); ?>
        </a>
    <?php endif; ?>

    <div class="accueil-contenu">
        <?php echo $contenu_accueil; ?>
    </div>

</main>

<script src="script/script.js"></script>
</body>
</html>