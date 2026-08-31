<?php
// ============================================================
//  MENU LATÉRAL PARTAGÉ + BOUTON PROFIL
//  Inclus par index.php et page.php.
//
//  Le menu est reconstruit à chaque chargement depuis la table
//  `pages`, dans la langue en cours : champ_page() renvoie le
//  titre anglais s'il existe, sinon le français.
//
//  Prérequis : $pdo existe déjà et config/auth.php est chargé.
//  $base = préfixe des liens ('' à la racine, '../' ailleurs).
// ============================================================
if (!isset($base)) { $base = ''; }

// La page d'accueil est stockée en base comme les autres, mais
// elle ne doit pas apparaître dans le menu : on l'exclut.
$stmt_parents = $pdo->query(
    "SELECT * FROM pages
     WHERE parent_id IS NULL AND slug <> 'accueil'
     ORDER BY ordre ASC, date_creation DESC"
);
$menus_principaux = $stmt_parents->fetchAll(PDO::FETCH_ASSOC);

$stmt_enfants = $pdo->query(
    "SELECT * FROM pages
     WHERE parent_id IS NOT NULL
     ORDER BY ordre ASC, titre ASC"
);
$sous_pages = $stmt_enfants->fetchAll(PDO::FETCH_ASSOC);

$sous_menus = [];
foreach ($sous_pages as $sp) {
    $sous_menus[$sp['parent_id']][] = $sp;
}

/**
 * Titre d'une entrée de menu, dans la langue en cours.
 * On enlève au passage le "S" de MISSIONS, sans toucher à la base.
 */
function wy_titre_menu($page) {
    $titre = is_array($page) ? champ_page($page, 'titre') : $page;
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
            $titre_menu    = wy_titre_menu($menu);
            $a_des_enfants = isset($sous_menus[$menu['id']]);
            ?>
            <div class="menu-group">
                <?php if ($a_des_enfants): ?>
                    <a href="#" class="menu-item toggle-submenu">
                        <span><?php echo htmlspecialchars($titre_menu); ?></span>
                        <span class="arrow">&#9660;</span>
                    </a>
                    <div class="submenu">
                        <a href="<?php echo $base; ?>page.php?slug=<?php echo urlencode($menu['slug']); ?>" class="submenu-item-oswald">
                            <?php echo te('nav.overview'); ?>
                        </a>
                        <?php foreach ($sous_menus[$menu['id']] as $sous_item): ?>
                            <a href="<?php echo $base; ?>page.php?slug=<?php echo urlencode($sous_item['slug']); ?>" class="submenu-item-nunito">
                                <?php echo htmlspecialchars(wy_titre_menu($sous_item)); ?>
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

<button class="menu-toggle" id="menuToggle" aria-label="<?php echo te('nav.ouvrir_menu'); ?>">&#9776;</button>

<!-- ================== BOUTON PROFIL ==================
     Rond avec les initiales. Il ouvre le menu identité,
     changement de langue, mot de passe et déconnexion.
     ==================================================== -->
<div class="profil-zone" id="profilZone">
    <?php if (est_connecte()): ?>
        <button type="button" class="profil" id="profilBouton" title="<?php echo htmlspecialchars(nom_affiche()); ?>">
            <?php echo htmlspecialchars(initiales()); ?>
        </button>

        <div class="profil-menu" id="profilMenu">
            <div class="profil-entete">
                <strong><?php echo htmlspecialchars(nom_affiche()); ?></strong>
                <span><?php echo htmlspecialchars(role_affiche()); ?></span>
            </div>

            <?php if (peut_gerer_pages()): ?>
                <a href="<?php echo $base; ?>admin/dashboard.php">
                    <?php echo est_admin() ? te('nav.panneau_admin') : te('nav.espace_editeur'); ?>
                </a>
            <?php endif; ?>

            <!-- BASCULE DE LANGUE
                 url_langue() reconstruit l'adresse de la page en cours
                 avec l'autre langue : on ne perd pas la page consultée. -->
            <a href="<?php echo htmlspecialchars(url_langue(autre_langue())); ?>" class="profil-langue">
                <span class="drapeau"><?php echo autre_langue() === 'en' ? 'EN' : 'FR'; ?></span>
                <?php echo htmlspecialchars(nom_langue(autre_langue())); ?>
            </a>

            <a href="<?php echo $base; ?>config/profil.php"><?php echo te('profil.mon_mdp'); ?></a>
            <a href="<?php echo $base; ?>config/logout.php" class="profil-deconnexion"><?php echo te('profil.deconnexion'); ?></a>
        </div>
    <?php else: ?>
        <a href="<?php echo $base; ?>config/login.php" class="profil" title="<?php echo te('conn.titre'); ?>">P</a>
    <?php endif; ?>
</div>