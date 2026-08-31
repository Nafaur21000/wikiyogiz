<?php
// ============================================================
//  PAGE D'ACCUEIL
//
//  Le bandeau (logo, nom, tagline, Hobbi) est fixe.
//  Le texte, lui, devient modifiable depuis le back-office.
//
//  AUTO-INITIALISATION : au tout premier chargement, si la page
//  'accueil' n'existe pas encore en base, ce fichier l'y crée
//  avec le contenu par défaut écrit juste en dessous. Ensuite,
//  c'est toujours la version de la base qui s'affiche.
//  Aucune requête SQL à taper à la main.
// ============================================================
session_start();
require_once 'config/link_bdd_php.php';

$base = '';

// --- CONTENU PAR DÉFAUT ---
// Sert uniquement à remplir la base la première fois.
// Après, modifie le texte depuis l'admin, pas ici.
$contenu_par_defaut = <<<HTML
<section class="wiki-section">
    <h2>L'OBJECTIF DE CE WIKI</h2>
    <p>
        WikiYogiz est la mémoire partagée de Superyogiz. Il rassemble en un seul endroit
        notre ADN, nos missions, nos valeurs et nos repères de marque, pour que personne
        n'ait à chercher deux fois la même information.
    </p>
    <p>
        L'idée est simple : rendre l'essentiel accessible à tout le monde, tout de suite.
        Un nouvel arrivant y trouve de quoi comprendre où il met les pieds, et une équipe
        déjà en place y retrouve la même version des faits, le même vocabulaire, les mêmes
        règles du jeu. Chez nous, la clarté est une forme de respect.
    </p>
</section>

<section class="wiki-section">
    <h2>SECTION HOBBI</h2>
    <p>
        Hobbi est notre compagnon de route : il incarne l'esprit Superyogiz au quotidien
        et guide la lecture de ce wiki.
    </p>
</section>
HTML;

// --- LECTURE EN BASE ---
$stmt = $pdo->prepare("SELECT id, contenu FROM pages WHERE slug = ? LIMIT 1");
$stmt->execute(['accueil']);
$ligne = $stmt->fetch(PDO::FETCH_ASSOC);

if ($ligne) {
    // La page existe : on affiche ce qui a été enregistré depuis l'admin.
    $id_accueil      = $ligne['id'];
    $contenu_accueil = $ligne['contenu'];
} else {
    // Première visite : on crée la page à partir du contenu ci-dessus.
    // admin_only = 1 -> seul un administrateur pourra la modifier.
    try {
        $ins = $pdo->prepare(
            "INSERT INTO pages (titre, slug, contenu, parent_id, ordre, admin_only)
             VALUES ('Accueil', 'accueil', ?, NULL, 0, 1)"
        );
        $ins->execute([$contenu_par_defaut]);
        $id_accueil = $pdo->lastInsertId();
    } catch (PDOException $e) {
        // Si la colonne admin_only n'existe pas encore, on n'empêche
        // pas le site de s'afficher : on montre juste le texte fixe.
        $id_accueil = null;
    }
    $contenu_accueil = $contenu_par_defaut;
}

// Un administrateur voit un bouton pour modifier directement.
$peut_modifier_accueil = (($_SESSION['role'] ?? '') === 'admin') && $id_accueil;
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

<?php include 'includes/menu.php'; ?>

<main class="contenu-central" id="mainContent">

    <img src="images/logo-contour.png" alt="WikiYogiz" class="accueil-logo">

    <h1 class="nom-site">WIKIYOGIZ</h1>

    <p class="tagline">&ldquo;People First. Performance Follows.&rdquo;</p>

    <img src="images/Tree-pose-Cape.png" alt="Hobbi" class="image-hobbi">

    <!-- Raccourci visible uniquement par un administrateur connecté -->
    <?php if ($peut_modifier_accueil): ?>
        <a href="admin/editeur_page.php?id=<?php echo $id_accueil; ?>" class="btn btn-small">
            Modifier cette page
        </a>
    <?php endif; ?>

    <!-- Contenu piloté depuis le back-office -->
    <div class="accueil-contenu">
        <?php echo $contenu_accueil; ?>
    </div>

</main>

<script src="script/script.js"></script>
</body>
</html>