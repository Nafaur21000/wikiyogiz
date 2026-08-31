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
    "SELECT id, titre, slug, date_creation FROM pages
     WHERE slug <> 'accueil' ORDER BY date_creation DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo nom_espace(); ?> - WikiYogiz</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body class="admin-body">

<?php include '_menu_admin.php'; ?>

<main class="admin-main">
    <h1><?php echo nom_espace(); ?></h1>
    <p>Bienvenue, <strong><?php echo htmlspecialchars(nom_affiche()); ?></strong>
       — rôle : <?php echo htmlspecialchars(role_actuel()); ?>.</p>

    <div class="admin-card">
        <h2>En un coup d'&oelig;il</h2>
        <p>
            <strong><?php echo $nb_pages; ?></strong> page(s),
            dont <strong><?php echo $nb_menus; ?></strong> en menu principal.
            <?php if ($nb_users !== null): ?>
                <strong><?php echo $nb_users; ?></strong> utilisateur(s).
            <?php endif; ?>
        </p>
        <p>
            <a href="pages_gestion.php" class="btn">Gérer les pages</a>
            <a href="editeur_page.php" class="btn">+ Nouvelle page</a>
            <?php if (est_admin()): ?>
                <a href="users_gestion.php" class="btn">Utilisateurs</a>
            <?php endif; ?>
        </p>
    </div>

    <div class="admin-card">
        <h2>Dernières pages créées</h2>
        <?php if (empty($dernieres)): ?>
            <p>Aucune page pour le moment.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>Titre</th><th>Date</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($dernieres as $d): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($d['titre']); ?></td>
                        <td><?php echo htmlspecialchars($d['date_creation']); ?></td>
                        <td>
                            <a href="editeur_page.php?id=<?php echo $d['id']; ?>" class="btn btn-small">Modifier</a>
                            <a href="../page.php?slug=<?php echo urlencode($d['slug']); ?>" class="btn btn-small" target="_blank">Voir</a>
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