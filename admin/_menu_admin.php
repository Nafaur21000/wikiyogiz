<?php
/** Menu latéral commun à toutes les pages du back-office */
?>
<aside class="menu">
    <a href="dashboard.php" class="menu-brand">
        <img src="../images/logo-contour.png" alt="WikiYogiz" class="menu-logo">
        <span class="site-title">ESPACE ADMIN</span>
    </a>

    <nav class="menu-nav">
        <a href="dashboard.php" class="menu-item">Tableau de bord</a>
        <a href="pages_gestion.php" class="menu-item">Gérer les pages</a>
        <a href="editeur_page.php" class="menu-item">Nouvelle page</a>
        <?php if (est_admin()): ?>
            <a href="users_gestion.php" class="menu-item">Utilisateurs</a>
        <?php endif; ?>
        <a href="../index.php" class="menu-item">Voir le site</a>
    </nav>

    <a href="/config/logout.php" class="menu-item" style="margin-top:auto; color:#ff9b9b;">Déconnexion</a>
</aside>