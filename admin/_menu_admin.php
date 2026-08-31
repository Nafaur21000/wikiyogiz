<?php
// ============================================================
//  MENU LATÉRAL DU BACK-OFFICE
//  Titre adapté au rôle, entrée "Utilisateurs" réservée à l'admin.
// ============================================================
?>
<aside class="menu">
    <a href="dashboard.php" class="menu-brand">
        <img src="../images/logo-contour.png" alt="WikiYogiz" class="menu-logo">
        <span class="site-title"><?php echo nom_espace(); ?></span>
    </a>

    <nav class="menu-nav">
        <a href="dashboard.php" class="menu-item">Tableau de bord</a>
        <a href="pages_gestion.php" class="menu-item">Gérer les pages</a>
        <?php if (est_admin()): ?>
            <a href="users_gestion.php" class="menu-item">Utilisateurs</a>
        <?php endif; ?>
        <a href="../index.php" class="menu-item">Voir le site</a>
    </nav>

    <div class="menu-utilisateur">
        <span class="pastille-initiales"><?php echo htmlspecialchars(initiales()); ?></span>
        <span><?php echo htmlspecialchars(nom_affiche()); ?></span>
    </div>

    <a href="../config/logout.php" class="menu-item" style="color:#ff9b9b;">Déconnexion</a>
</aside>