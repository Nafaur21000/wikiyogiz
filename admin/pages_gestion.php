<?php
// ============================================================
//  LISTE ET GESTION DES PAGES
//    - arborescence menus / sous-menus
//    - flèches monter / descendre, y compris pour les sous-menus
//    - verrou "réservé à l'administrateur"
// ============================================================
require_once '../config/auth.php';
require_once '../config/link_bdd_php.php';
exiger_editeur();

$message = null;
$erreur  = null;

if (isset($_GET['ok']))    { $message = "Page enregistrée."; }
if (isset($_GET['refus'])) { $erreur  = "Cette page est réservée à l'administrateur."; }

/* ------------------------------------------------------------
   DÉPLACEMENT D'UNE PAGE DANS LE MENU
   On échange la valeur `ordre` avec la page voisine, à l'intérieur
   du même groupe (même parent). C'est ce qui permet de régler
   l'ordre des sous-menus autant que celui des menus principaux.
   ------------------------------------------------------------ */
function deplacer(PDO $pdo, $id, $sens) {
    $stmt = $pdo->prepare("SELECT id, parent_id, ordre FROM pages WHERE id = ?");
    $stmt->execute([$id]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$page) { return; }

    // Les voisins : même parent (NULL compris), classés comme dans le menu.
    if ($page['parent_id'] === null) {
        $q = $pdo->query("SELECT id, ordre FROM pages WHERE parent_id IS NULL AND slug <> 'accueil' ORDER BY ordre ASC, id ASC");
    } else {
        $q = $pdo->prepare("SELECT id, ordre FROM pages WHERE parent_id = ? ORDER BY ordre ASC, id ASC");
        $q->execute([$page['parent_id']]);
    }
    $groupe = $q->fetchAll(PDO::FETCH_ASSOC);

    // Position de la page dans son groupe
    $pos = null;
    foreach ($groupe as $i => $g) {
        if ((int)$g['id'] === (int)$id) { $pos = $i; break; }
    }
    if ($pos === null) { return; }

    $cible = ($sens === 'monter') ? $pos - 1 : $pos + 1;
    if ($cible < 0 || $cible >= count($groupe)) { return; }   // déjà en bout

    // Les valeurs d'ordre peuvent être identiques (toutes à 0 au
    // départ) : on réécrit tout le groupe proprement, en 1, 2, 3...
    $ids = array_column($groupe, 'id');
    $tmp = $ids[$pos];
    $ids[$pos]   = $ids[$cible];
    $ids[$cible] = $tmp;

    $maj = $pdo->prepare("UPDATE pages SET ordre = ? WHERE id = ?");
    foreach ($ids as $rang => $idPage) {
        $maj->execute([$rang + 1, $idPage]);
    }
}

if (isset($_GET['monter']))    { deplacer($pdo, (int)$_GET['monter'], 'monter');       $message = "Ordre mis à jour."; }
if (isset($_GET['descendre'])) { deplacer($pdo, (int)$_GET['descendre'], 'descendre'); $message = "Ordre mis à jour."; }

/* ---------- SUPPRESSION ---------- */
if (isset($_GET['supprimer'])) {
    $id = (int)$_GET['supprimer'];

    $v = $pdo->prepare("SELECT admin_only, slug FROM pages WHERE id = ?");
    $v->execute([$id]);
    $cible = $v->fetch(PDO::FETCH_ASSOC);

    if (!$cible) {
        $erreur = "Cette page n'existe plus.";
    } elseif ($cible['slug'] === 'accueil') {
        $erreur = "La page d'accueil ne peut pas être supprimée.";
    } elseif (!peut_modifier_page($cible)) {
        $erreur = "Cette page est réservée à l'administrateur.";
    } else {
        // On détache les enfants pour ne pas les rendre orphelins
        $pdo->prepare("UPDATE pages SET parent_id = NULL WHERE parent_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM pages WHERE id = ?")->execute([$id]);
        $message = "Page supprimée. Ses sous-pages sont devenues des menus principaux.";
    }
}

/* ---------- ARBORESCENCE ---------- */
$accueil = $pdo->query("SELECT * FROM pages WHERE slug = 'accueil' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$parents = $pdo->query("SELECT * FROM pages WHERE parent_id IS NULL AND slug <> 'accueil' ORDER BY ordre ASC, titre ASC")
               ->fetchAll(PDO::FETCH_ASSOC);

$enfants_bruts = $pdo->query("SELECT * FROM pages WHERE parent_id IS NOT NULL ORDER BY ordre ASC, titre ASC")
                     ->fetchAll(PDO::FETCH_ASSOC);

$enfants = [];
foreach ($enfants_bruts as $e) {
    $enfants[$e['parent_id']][] = $e;
}

/** Affiche les boutons d'action d'une ligne du tableau. */
function actions_page($p, $premier, $dernier) {
    $modifiable = peut_modifier_page($p);
    $accueil    = ($p['slug'] === 'accueil');
    ?>
    <a href="../page.php?slug=<?php echo urlencode($p['slug']); ?>" class="btn btn-small" target="_blank">Voir</a>

    <?php if ($modifiable): ?>
        <a href="editeur_page.php?id=<?php echo $p['id']; ?>" class="btn btn-small">Modifier</a>
        <?php if (!$accueil): ?>
            <a href="?supprimer=<?php echo $p['id']; ?>" class="btn btn-small btn-danger"
               data-confirm="Supprimer définitivement « <?php echo htmlspecialchars($p['titre'], ENT_QUOTES); ?> » ?">Suppr.</a>
        <?php endif; ?>
    <?php else: ?>
        <span class="badge" title="Réservée à l'administrateur">verrouillée</span>
    <?php endif; ?>

    <?php if (!$accueil): ?>
        <a href="?monter=<?php echo $p['id']; ?>" class="btn btn-small btn-fleche <?php echo $premier ? 'inactif' : ''; ?>" title="Monter">&uarr;</a>
        <a href="?descendre=<?php echo $p['id']; ?>" class="btn btn-small btn-fleche <?php echo $dernier ? 'inactif' : ''; ?>" title="Descendre">&darr;</a>
    <?php endif;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les pages - WikiYogiz</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body class="admin-body">

<?php include '_menu_admin.php'; ?>

<main class="admin-main">
    <h1>Gérer les pages</h1>
    <p>Chaque page est une entrée de menu. Une page rattachée à un parent devient un sous-menu.
       Les flèches règlent l'ordre d'affichage, menus comme sous-menus.</p>

    <?php if ($message): ?><div class="alert alert-ok"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($erreur):  ?><div class="alert alert-error"><?php echo htmlspecialchars($erreur); ?></div><?php endif; ?>

    <p><a href="editeur_page.php" class="btn">+ Créer une nouvelle page</a></p>

    <table class="admin-table">
        <thead>
            <tr>
                <th style="width:40%;">Titre</th>
                <th>URL</th>
                <th style="width:70px;">Ordre</th>
                <th style="width:290px;">Actions</th>
            </tr>
        </thead>
        <tbody>

        <?php if ($accueil): ?>
            <tr class="ligne-accueil">
                <td><strong>ACCUEIL</strong> <span class="badge badge-admin">réservée admin</span></td>
                <td><code>/</code></td>
                <td>—</td>
                <td><?php actions_page($accueil, true, true); ?></td>
            </tr>
        <?php endif; ?>

        <?php if (empty($parents)): ?>
            <tr><td colspan="4">Aucune autre page pour le moment.</td></tr>
        <?php endif; ?>

        <?php foreach ($parents as $i => $p): ?>
            <tr>
                <td>
                    <strong><?php echo htmlspecialchars(strtoupper($p['titre'])); ?></strong>
                    <?php if ((int)$p['admin_only'] === 1): ?>
                        <span class="badge badge-admin">réservée admin</span>
                    <?php endif; ?>
                </td>
                <td><code><?php echo htmlspecialchars($p['slug']); ?></code></td>
                <td><?php echo (int)$p['ordre']; ?></td>
                <td><?php actions_page($p, $i === 0, $i === count($parents) - 1); ?></td>
            </tr>

            <?php if (isset($enfants[$p['id']])): ?>
                <?php $liste = $enfants[$p['id']]; ?>
                <?php foreach ($liste as $j => $c): ?>
                    <tr>
                        <td style="padding-left:40px;">
                            &#8627; <?php echo htmlspecialchars($c['titre']); ?>
                            <?php if ((int)$c['admin_only'] === 1): ?>
                                <span class="badge badge-admin">réservée admin</span>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo htmlspecialchars($c['slug']); ?></code></td>
                        <td><?php echo (int)$c['ordre']; ?></td>
                        <td><?php actions_page($c, $j === 0, $j === count($liste) - 1); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>

        </tbody>
    </table>
</main>

<script src="../script/script.js"></script>
</body>
</html>