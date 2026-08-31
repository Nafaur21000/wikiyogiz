<?php
// ============================================================
//  MENU LATÉRAL PARTAGÉ + BOUTON PROFIL
//  Inclus par index.php et page.php.
//  Le menu est reconstruit à chaque chargement depuis la table
//  `pages` : créer une page = créer une entrée de menu.
//
//  Prérequis : $pdo existe déjà.
//  $base = préfixe des liens ('' à la racine, '../' ailleurs).
// ============================================================
if (!isset($base)) { $base = ''; }

// La page d'accueil est stockée en base comme les autres (pour
// pouvoir être modifiée), mais elle ne doit pas apparaître dans
// le menu : on l'exclut par son slug.
$stmt_parents = $pdo->query(
    "SELECT * FROM pages
     WHERE parent_id IS NULL AND slug <> 'accueil'
     ORDER BY ordre ASC, date_creation DESC"
);
$menus_principaux = $stmt_parents->fetchAll(PDO::FETCH_ASSOC);

// Les sous-pages, triées elles aussi par leur champ `ordre` :
// c'est ce qui permet de régler la position des sous-menus.
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

/** Enlève le "S" de MISSIONS à l'affichage, sans toucher à la base. */
function wy_titre_menu($titre) {
    $t = strtoupper(trim($titre));
    if ($t === 'MISSIONS & PURPOSE') { return 'MISSION & PURPOSE'; }
    if ($t === 'MISSIONS')           { return 'MISSION'; }
    return $t;
}

// --- Informations du profil, calculées ici pour alléger le HTML ---
$connecte    = isset($_SESSION['user_id']);
$role_visite = $_SESSION['role'] ?? '';
$a_backoffice = in_array($role_visite, ['admin', 'editeur'], true);

// Initiales : première lettre du prénom + première lettre du nom.
$ini = '';
if (!empty($_SESSION['prenom']))      { $ini .= mb_substr($_SESSION['prenom'], 0, 1); }
if (!empty($_SESSION['nom_famille'])) { $ini .= mb_substr($_SESSION['nom_famille'], 0, 1); }
if ($ini === '')                      { $ini = mb_substr($_SESSION['identifiant'] ?? 'P', 0, 1); }
$ini = mb_strtoupper($ini);
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

<!-- ================== BOUTON PROFIL ==================
     Visiteur           : lien direct vers la connexion.
     Connecté           : rond avec les initiales, qui ouvre un
                          petit menu (panneau admin + déconnexion).
     ==================================================== -->
<div class="profil-zone" id="profilZone">
    <?php if ($connecte): ?>
        <button type="button" class="profil" id="profilBouton" title="<?php echo htmlspecialchars(trim(($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom_famille'] ?? ''))); ?>">
            <?php echo htmlspecialchars($ini); ?>
        </button>

        <div class="profil-menu" id="profilMenu">
            <div class="profil-entete">
                <strong><?php echo htmlspecialchars(trim(($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom_famille'] ?? '')) ?: ($_SESSION['identifiant'] ?? '')); ?></strong>
                <span><?php echo htmlspecialchars($role_visite); ?></span>
            </div>

            <?php if ($a_backoffice): ?>
                <a href="<?php echo $base; ?>admin/dashboard.php">
                    <?php echo $role_visite === 'admin' ? 'Panneau admin' : 'Espace éditeur'; ?>
                </a>
            <?php endif; ?>

            <a href="<?php echo $base; ?>config/logout.php" class="profil-deconnexion">Se déconnecter</a>
        </div>
    <?php else: ?>
        <a href="<?php echo $base; ?>config/login.php" class="profil" title="Connexion">P</a>
    <?php endif; ?>
</div>