<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Back-Office - Wikiyogiz</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
    <div class="menu">
        <h2 style="font-family: 'Oswald', sans-serif;">Espace Privé</h2>
        <p style="font-family: Nunito;">Bienvenue, <?php echo htmlspecialchars($_SESSION['nom']); ?></p>
        
        <hr style="width: 100%; border-color: #cbe3fd;">
        
        <a href="pages_gestion.php" class="menu-item" style="font-size: 1rem;">Gérer les pages</a>
        
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="users_gestion.php" class="menu-item" style="font-size: 1rem;">Créer un utilisateur</a>
        <?php endif; ?>
        
        <a href="../logout.php" class="menu-item" style="font-size: 1rem; color: #ff6b6b; margin-top: auto;">Déconnexion</a>
    </div>

    <main class="main-container">
        <h1 style="font-family: 'Oswald', sans-serif;">Tableau de bord</h1>
        <p style="font-family: Nunito; font-size: 1.2rem;">Bienvenue dans votre espace d'administration.</p>
        <p style="font-family: Nunito;">Utilisez le menu à gauche pour créer des pages ou de nouveaux accès.</p>
        <a href="../index.php" style="color: #654178; font-family: Nunito; text-decoration: underline; margin-top: 20px; display: inline-block;">Retourner voir le site public</a>
    </main>
</body>
</html>