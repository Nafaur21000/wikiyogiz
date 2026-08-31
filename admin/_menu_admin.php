<?php
// ============================================================
//  MENU LATÉRAL DU BACK-OFFICE
//  Identique dans les deux langues : tous les libellés passent
//  par t(). L'identité, la langue, le mot de passe et la
//  déconnexion sont dans le bouton rond en haut à droite.
// ============================================================
?>
<aside class="menu">
    <a href="../index.php" class="menu-brand">
        <img src="../images/logo-contour.png" alt="WikiYogiz" class="menu-logo">
        <span class="site-title"><?php echo htmlspecialchars(nom_espace()); ?></span>
    </a>

    <nav class="menu-nav">
        <a href="../index.php" class="menu-item"><?php echo te('nav.accueil'); ?></a>
        <a href="dashboard.php" class="menu-item"><?php echo te('nav.tableau_bord'); ?></a>
        <a href="pages_gestion.php" class="menu-item"><?php echo te('nav.gerer_pages'); ?></a>
        <?php if (est_admin()): ?>
            <a href="users_gestion.php" class="menu-item"><?php echo te('nav.utilisateurs'); ?></a>
        <?php endif; ?>
    </nav>
</aside>

<!-- Bouton profil : même composant que sur le site public -->
<div class="profil-zone" id="profilZone">
    <button type="button" class="profil" id="profilBouton" title="<?php echo htmlspecialchars(nom_affiche()); ?>">
        <?php echo htmlspecialchars(initiales()); ?>
    </button>

    <div class="profil-menu" id="profilMenu">
        <div class="profil-entete">
            <strong><?php echo htmlspecialchars(nom_affiche()); ?></strong>
            <span><?php echo htmlspecialchars(role_affiche()); ?></span>
        </div>

        <a href="../index.php"><?php echo te('nav.accueil_site'); ?></a>

        <!-- Bascule de langue : recharge la même page dans l'autre langue -->
        <a href="<?php echo htmlspecialchars(url_langue(autre_langue())); ?>" class="profil-langue">
            <span class="drapeau"><?php echo autre_langue() === 'en' ? 'EN' : 'FR'; ?></span>
            <?php echo htmlspecialchars(nom_langue(autre_langue())); ?>
        </a>

        <a href="../config/profil.php"><?php echo te('profil.mon_mdp'); ?></a>
        <a href="../config/logout.php" class="profil-deconnexion"><?php echo te('profil.deconnexion'); ?></a>
    </div>
</div>