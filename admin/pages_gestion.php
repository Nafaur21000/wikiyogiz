<?php
require_once '../config/auth.php';
require_once '../config/link_bdd_php.php';
exiger_connexion();

$message = null;
$erreur  = null;

/* ---------- SUPPRESSION D'UNE PAGE ---------- */
if (isset($_GET['supprimer'])) {
    $id = (int)$_GET['supprimer'];
    $pdo->prepare("UPDATE pages SET parent_id = NULL WHERE parent_id = ?")->execute([$id]);
    $stmt = $pdo->prepare("DELETE FROM pages WHERE id = ?");
    $stmt->execute([$id]);
    $message = "La page a été supprimée. Ses éventuelles sous-pages sont devenues des menus principaux.";
}

/* ---------- CHANGEMENT RAPIDE DE L'ORDRE ---------- */
if (isset($_POST["maj_ordre"]) && !empty($_POST["ordre"])) {
    foreach ($_POST['ordre'] as $id => $valeur) {
        $stmt = $pdo->prepare("UPDATE pages SET ordre = ? WHERE id = ?");
        $stmt->execute([(int)$valeur, (int)$id]);
    }
    $message = "L'ordre des menus a été mis à jour.";
}

/* ---------- RÉCUPÉRATION DE L'ARBORESCENCE ---------- */
$parents = $pdo->query("SELECT * FROM pages WHERE parent_id IS NULL ORDER BY ordre ASC, titre ASC")
               ->fetchAll(PDO::FETCH_ASSOC);

$enfants_bruts = $pdo->query("SELECT * FROM pages WHERE parent_id IS NOT NULL ORDER BY ordre ASC, titre ASC")
                     ->fetchAll(PDO::FETCH_ASSOC);

$enfants = [];
foreach ($enfants_bruts as $e) {
    $enfants[$e['parent_id']][] = $e;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gérer les pages - WikiYogiz</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body class="admin-body">

<?php include '_menu_admin.php'; ?>

<main class="admin-main">
    <h1>Gérer les pages</h1>
    <p>Chaque page devient automatiquement une entrée de menu. Une page rattachée à un parent devient un sous-menu.</p>

    <?php if ($message): ?><div class="alert alert-ok"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($erreur):  ?><div class="alert alert-error"><?php echo htmlspecialchars($erreur); ?></div><?php endif; ?>

    <p><a href="editeur_page.php" class="btn">+ Créer une nouvelle page</a></p>

    <form method="POST">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:45%;">Titre</th>
                    <th>URL (slug)</th>
                    <th style="width:90px;">Ordre</th>
                    <th style="width:230px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($parents)): ?>
                <tr><td colspan="4">Aucune page pour le moment. Crée la première.</td></tr>
            <?php endif; ?>

            <?php foreach ($parents as $p): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars(strtoupper($p['titre'])); ?></strong></td>
                    <td><code><?php echo htmlspecialchars($p['slug']); ?></code></td>
                    <td><input type="number" name="ordre[<?php echo $p['id']; ?>]" value="<?php echo (int)$p['ordre']; ?>" style="width:65px; padding:5px;"></td>
                    <td>
                        <a href="../page.php?slug=<?php echo urlencode($p['slug']); ?>" class="btn btn-small" target="_blank">Voir</a>
                        <a href="editeur_page.php?id=<?php echo $p['id']; ?>" class="btn btn-small">Modifier</a>
                        <a href="?supprimer=<?php echo $p['id']; ?>" class="btn btn-small btn-danger"
                           data-confirm="Supprimer définitivement la page « <?php echo htmlspecialchars($p['titre'], ENT_QUOTES); ?> » ?">Supprimer</a>
                    </td>
                </tr>

                <?php if (isset($enfants[$p['id']])): ?>
                    <?php foreach ($enfants[$p['id']] as $c): ?>
                        <tr>
                            <td style="padding-left:40px;">&#8627; <?php echo htmlspecialchars($c['titre']); ?></td>
                            <td><code><?php echo htmlspecialchars($c['slug']); ?></code></td>
                            <td><input type="number" name="ordre[<?php echo $c['id']; ?>]" value="<?php echo (int)$c['ordre']; ?>" style="width:65px; padding:5px;"></td>
                            <td>
                                <a href="../page.php?slug=<?php echo urlencode($c['slug']); ?>" class="btn btn-small" target="_blank">Voir</a>
                                <a href="editeur_page.php?id=<?php echo $c['id']; ?>" class="btn btn-small">Modifier</a>
                                <a href="?supprimer=<?php echo $c['id']; ?>" class="btn btn-small btn-danger"
                                   data-confirm="Supprimer définitivement la page « <?php echo htmlspecialchars($c['titre'], ENT_QUOTES); ?> » ?">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>

        <p style="margin-top:20px;">
            <button type="submit" name="maj_ordre" value="1" class="btn">Enregistrer l'ordre des menus</button>
        </p>
    </form>
</main>

<script src="../script/script.js"></script>
</body>
</html>