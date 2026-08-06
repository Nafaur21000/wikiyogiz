<?php
/**
 * MENU LATÉRAL PARTAGÉ
 * Inclus par index.php et page.php. Attend que $pdo existe déjà.
 */
if (!isset($base)) { $base = ''; }

$stmt_parents = $pdo->query("SELECT * FROM pages WHERE parent_id IS NULL ORDER BY ordre ASC, date_creation DESC");
$menus_principaux = $stmt_parents->fetchAll(PDO::FETCH_ASSOC);

$stmt_enfants = $pdo->query("SELECT * FROM pages WHERE parent_id IS NOT NULL ORDER BY ordre ASC, titre ASC");
$sous_pages = $stmt_enfants->fetchAll(PDO::FETCH_ASSOC);

$sous_menus = [];
foreach ($sous_pages as $sp) {
    $sous_menus[$sp['parent_id']][] = $sp;
}

/** Enlève le "S" de MISSIONS dans l'affichage du menu */
function wy_titre_menu($titre) {
    $t = strtoupper(trim($titre));
    if ($t === 'MISSIONS & PURPOSE') { return 'MISSION & PURPOSE'; }
    if ($t === 'MISSIONS')           { return 'MISSION'; }
    return $t;
}
?>
<!-- ================== MENU LATÉRAL ================== -->
<aside class="menu" id="sideMenu">

    <a href="<?php echo $base; ?>index.php" class="menu-brand">
        <img src="<?php echo $base; ?>images/logo-contour.png" alt="WikiYogiz" class="menu-logo">
        <span class="site-title">WIKIYOGIZ</span>
    </a>

    <nav class="menu-nav">
        <?php foreach ($menus_principaux as $menu):
            $titre_menu    = wy_titre_menu($menu['titre']);
            $a_des_enfants = isset($sous_menus[$menu['id']]);
            ?>
            <div class="menu-group">
                <?php if ($a_des_enfants): ?>
                    <a href="#" class="menu-item toggle-submenu">
                        <span><?php echo htmlspecialchars($titre_menu); ?></span>
                        <span class="arrow">&#9660;</span>
                    </a>
                    <div class="submenu">
                        <a href="<?php echo $base; ?>page.php?slug=<?php echo urlencode($menu['slug']); ?>" class="submenu-item-oswald">OVERVIEW</a>
                        <?php foreach ($sous_menus[$menu['id']] as $sous_item): ?>
                            <a href="<?php echo $base; ?>page.php?slug=<?php echo urlencode($sous_item['slug']); ?>" class="submenu-item-nunito">
                                <?php echo htmlspecialchars(wy_titre_menu($sous_item['titre'])); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $base; ?>page.php?slug=<?php echo urlencode($menu['slug']); ?>" class="menu-item">
                        <?php echo htmlspecialchars($titre_menu); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </nav>
</aside>

<button class="menu-toggle" id="menuToggle" aria-label="Ouvrir ou fermer le menu">&#9776;</button>

<?php if (isset($_SESSION['user_id'])): ?>
    <a href="<?php echo $base; ?>admin/dashboard.php" class="profil" title="Espace admin">
        <?php echo strtoupper(substr($_SESSION['nom'] ?? 'A', 0, 1)); ?>
    </a>
<?php else: ?>
    <a href="<?php echo $base; ?>config/login.php" class="profil" title="Connexion">P</a>
<?php endif; ?>