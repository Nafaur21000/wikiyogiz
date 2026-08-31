<?php
// ============================================================
//  PANNEAU D'ADMINISTRATION (accueil du back-office)
//  Ouvert aux éditeurs et aux administrateurs.
// ============================================================
require_once '../config/auth.php';
require_once '../config/link_bdd_php.php';
exiger_editeur();

$nb_pages = (int)$pdo->query("SELECT COUNT(*) FROM pages WHERE slug <> 'accueil'")->fetchColumn();
$nb_menus = (int)$pdo->query("SELECT COUNT(*) FROM pages WHERE parent_id IS NULL AND slug <> 'accueil'")->fetchColumn();

$nb_users = est_admin()
    ? (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn()
    : null;

$dernieres = $pdo->query(
    "SELECT * FROM pages WHERE slug <> 'accueil'
     ORDER BY date_creation DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?php echo langue(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(nom_espace()); ?> - WikiYogiz</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body class="admin-body">

<?php include '_menu_admin.php'; ?>

<main class="admin-main">
    <h1><?php echo htmlspecialchars(nom_espace()); ?></h1>
    <p><?php echo te('tdb.bienvenue'); ?>
       <strong><?php echo htmlspecialchars(nom_affiche()); ?></strong>
       — <?php echo te('tdb.role'); ?> : <?php echo htmlspecialchars(role_affiche()); ?>.</p>

    <div class="admin-card">
        <h2><?php echo te('tdb.coup_oeil'); ?></h2>
        <p>
            <strong><?php echo $nb_pages; ?></strong> <?php echo te('tdb.pages'); ?>,
            <?php echo te('tdb.dont'); ?> <strong><?php echo $nb_menus; ?></strong> <?php echo te('tdb.menu_principal'); ?>
            <?php if ($nb_users !== null): ?>
                <strong><?php echo $nb_users; ?></strong> <?php echo te('tdb.utilisateurs'); ?>
            <?php endif; ?>
        </p>
        <p>
            <a href="pages_gestion.php" class="btn"><?php echo te('nav.gerer_pages'); ?></a>
            <a href="editeur_page.php" class="btn"><?php echo te('tdb.nouvelle_page'); ?></a>
            <?php if (est_admin()): ?>
                <a href="users_gestion.php" class="btn"><?php echo te('nav.utilisateurs'); ?></a>
            <?php endif; ?>
        </p>
    </div>

    <div class="admin-card">
        <h2><?php echo te('tdb.dernieres'); ?></h2>
        <?php if (empty($dernieres)): ?>
            <p><?php echo te('tdb.aucune_page'); ?></p>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr>
                    <th><?php echo te('tdb.titre_col'); ?></th>
                    <th><?php echo te('tdb.date_col'); ?></th>
                    <th></th>
                </tr></thead>
                <tbody>
                <?php foreach ($dernieres as $d): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(champ_page($d, 'titre')); ?></td>
                        <td><?php echo htmlspecialchars($d['date_creation']); ?></td>
                        <td>
                            <a href="editeur_page.php?id=<?php echo $d['id']; ?>" class="btn btn-small"><?php echo te('commun.modifier'); ?></a>
                            <a href="../page.php?slug=<?php echo urlencode($d['slug']); ?>" class="btn btn-small" target="_blank"><?php echo te('commun.voir'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>

<script src="../script/script.js"></script>
</body>
</html>