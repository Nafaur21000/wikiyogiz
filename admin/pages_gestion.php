<?php
// ============================================================
//  LISTE ET GESTION DES PAGES
//    - arborescence menus / sous-menus
//    - flèches monter / descendre, sous-menus compris
//    - verrou "réservé à l'administrateur"
//  Les titres s'affichent dans la langue en cours.
// ============================================================
require_once '../config/auth.php';
require_once '../config/link_bdd_php.php';
exiger_editeur();

$message = null;
$erreur  = null;

if (isset($_GET['ok']))    { $message = t('pages.enregistree'); }
if (isset($_GET['refus'])) { $erreur  = t('pages.refus'); }

/* ------------------------------------------------------------
   DÉPLACEMENT D'UNE PAGE DANS LE MENU
   On échange la position avec la page voisine du même groupe,
   puis on renumérote tout le groupe proprement.
   ------------------------------------------------------------ */
function deplacer(PDO $pdo, $id, $sens) {
    $stmt = $pdo->prepare("SELECT id, parent_id FROM pages WHERE id = ?");
    $stmt->execute([$id]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$page) { return; }

    if ($page['parent_id'] === null) {
        $q = $pdo->query("SELECT id FROM pages WHERE parent_id IS NULL AND slug <> 'accueil' ORDER BY ordre ASC, id ASC");
    } else {
        $q = $pdo->prepare("SELECT id FROM pages WHERE parent_id = ? ORDER BY ordre ASC, id ASC");
        $q->execute([$page['parent_id']]);
    }
    $groupe = $q->fetchAll(PDO::FETCH_ASSOC);

    $pos = null;
    foreach ($groupe as $i => $g) {
        if ((int)$g['id'] === (int)$id) { $pos = $i; break; }
    }
    if ($pos === null) { return; }

    $cible = ($sens === 'monter') ? $pos - 1 : $pos + 1;
    if ($cible < 0 || $cible >= count($groupe)) { return; }   // déjà en bout

    $ids = array_column($groupe, 'id');
    $tmp = $ids[$pos];
    $ids[$pos]   = $ids[$cible];
    $ids[$cible] = $tmp;

    $maj = $pdo->prepare("UPDATE pages SET ordre = ? WHERE id = ?");
    foreach ($ids as $rang => $idPage) {
        $maj->execute([$rang + 1, $idPage]);
    }
}

/**
 * Le droit de déplacer se revérifie côté serveur : masquer une
 * flèche ne protège rien, on pourrait taper ?monter=12 à la main.
 */
function deplacement_autorise(PDO $pdo, $id) {
    $v = $pdo->prepare("SELECT admin_only, slug FROM pages WHERE id = ?");
    $v->execute([$id]);
    $cible = $v->fetch(PDO::FETCH_ASSOC);
    if (!$cible || $cible['slug'] === 'accueil') { return false; }
    return peut_modifier_page($cible);
}

foreach (['monter', 'descendre'] as $sens) {
    if (isset($_GET[$sens])) {
        $id = (int)$_GET[$sens];
        if (deplacement_autorise($pdo, $id)) {
            deplacer($pdo, $id, $sens);
            $message = t('pages.ordre_maj');
        } else {
            $erreur = t('pages.refus_deplacer');
        }
    }
}

/* ---------- SUPPRESSION ---------- */
if (isset($_GET['supprimer'])) {
    $id = (int)$_GET['supprimer'];

    $v = $pdo->prepare("SELECT admin_only, slug FROM pages WHERE id = ?");
    $v->execute([$id]);
    $cible = $v->fetch(PDO::FETCH_ASSOC);

    if (!$cible) {
        $erreur = t('pages.nexiste_plus');
    } elseif ($cible['slug'] === 'accueil') {
        $erreur = t('pages.accueil_indel');
    } elseif (!peut_modifier_page($cible)) {
        $erreur = t('pages.refus');
    } else {
        // On détache les enfants pour ne pas les rendre orphelins
        $pdo->prepare("UPDATE pages SET parent_id = NULL WHERE parent_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM pages WHERE id = ?")->execute([$id]);
        $message = t('pages.supprimee');
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

/**
 * Boutons d'action d'une ligne.
 * Les flèches suivent la même règle que la modification :
 * une page verrouillée ne bouge que pour un administrateur.
 */
function actions_page($p, $premier, $dernier) {
    $modifiable = peut_modifier_page($p);
    $est_accueil = ($p['slug'] === 'accueil');
    ?>
    <a href="../page.php?slug=<?php echo urlencode($p['slug']); ?>" class="btn btn-small" target="_blank"><?php echo te('commun.voir'); ?></a>

    <?php if ($modifiable): ?>
        <a href="editeur_page.php?id=<?php echo $p['id']; ?>" class="btn btn-small"><?php echo te('commun.modifier'); ?></a>
        <?php if (!$est_accueil): ?>
            <a href="?supprimer=<?php echo $p['id']; ?>" class="btn btn-small btn-danger"
               data-confirm="<?php echo te('pages.confirm_suppr'); ?>"><?php echo te('commun.supprimer'); ?></a>
        <?php endif; ?>
    <?php else: ?>
        <span class="badge" title="<?php echo te('pages.badge_admin'); ?>"><?php echo te('pages.verrouillee'); ?></span>
    <?php endif; ?>

    <?php if (!$est_accueil && $modifiable): ?>
        <a href="?monter=<?php echo $p['id']; ?>" class="btn btn-small btn-fleche <?php echo $premier ? 'inactif' : ''; ?>" title="<?php echo te('commun.monter'); ?>">&uarr;</a>
        <a href="?descendre=<?php echo $p['id']; ?>" class="btn btn-small btn-fleche <?php echo $dernier ? 'inactif' : ''; ?>" title="<?php echo te('commun.descendre'); ?>">&darr;</a>
    <?php endif;
}
?>
<!DOCTYPE html>
<html lang="<?php echo langue(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo te('pages.titre'); ?> - WikiYogiz</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body class="admin-body">

<?php include '_menu_admin.php'; ?>

<main class="admin-main">
    <h1><?php echo te('pages.titre'); ?></h1>

    <?php if ($message): ?><div class="alert alert-ok"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($erreur):  ?><div class="alert alert-error"><?php echo htmlspecialchars($erreur); ?></div><?php endif; ?>

    <p><a href="editeur_page.php" class="btn"><?php echo te('pages.creer'); ?></a></p>

    <table class="admin-table">
        <thead>
            <tr>
                <th style="width:40%;"><?php echo te('pages.col_titre'); ?></th>
                <th><?php echo te('pages.col_url'); ?></th>
                <th style="width:290px;"><?php echo te('pages.col_actions'); ?></th>
            </tr>
        </thead>
        <tbody>

        <?php if ($accueil): ?>
            <tr class="ligne-accueil">
                <td><strong><?php echo te('nav.accueil'); ?></strong> <span class="badge badge-admin"><?php echo te('pages.reservee'); ?></span></td>
                <td><code>/</code></td>
                <td><?php actions_page($accueil, true, true); ?></td>
            </tr>
        <?php endif; ?>

        <?php if (empty($parents)): ?>
            <tr><td colspan="3"><?php echo te('pages.aucune'); ?></td></tr>
        <?php endif; ?>

        <?php foreach ($parents as $i => $p): ?>
            <tr>
                <td>
                    <strong><?php echo htmlspecialchars(strtoupper(champ_page($p, 'titre'))); ?></strong>
                    <?php if ((int)$p['admin_only'] === 1): ?>
                        <span class="badge badge-admin"><?php echo te('pages.reservee'); ?></span>
                    <?php endif; ?>
                </td>
                <td><code><?php echo htmlspecialchars($p['slug']); ?></code></td>
                <td><?php actions_page($p, $i === 0, $i === count($parents) - 1); ?></td>
            </tr>

            <?php if (isset($enfants[$p['id']])): ?>
                <?php $liste = $enfants[$p['id']]; ?>
                <?php foreach ($liste as $j => $c): ?>
                    <tr>
                        <td style="padding-left:40px;">
                            &#8627; <?php echo htmlspecialchars(champ_page($c, 'titre')); ?>
                            <?php if ((int)$c['admin_only'] === 1): ?>
                                <span class="badge badge-admin"><?php echo te('pages.reservee'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo htmlspecialchars($c['slug']); ?></code></td>
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