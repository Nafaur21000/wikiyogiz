<?php
session_start();
require_once '../config/link_bdd_php.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Accès refusé. Seul un Administrateur peut créer des comptes.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['creer_user'])) {
    $nom = $_POST['nouveau_nom'];
    $pass_crypte = password_hash($_POST['nouveau_pass'], PASSWORD_DEFAULT);
    $role = $_POST['role_user'];

    $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom_utilisateur, mot_de_passe, role) VALUES (?, ?, ?)");
    $stmt->execute([$nom, $pass_crypte, $role]);
    $message = "L'utilisateur $nom a été créé avec succès !";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gérer les comptes</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
    <div class="menu">
        <h2 style="font-family: 'Oswald', sans-serif;">Espace Privé</h2>
        <hr style="width: 100%; border-color: #cbe3fd;">
        <a href="dashboard.php" class="menu-item" style="font-size: 1rem;">< Retour Dashboard</a>
        <a href="../logout.php" class="menu-item" style="font-size: 1rem; color: #ff6b6b; margin-top: auto;">Déconnexion</a>
    </div>
    <main class="main-container">
        <h1 style="font-family: 'Oswald', sans-serif;">Créer un nouvel accès</h1>
        
        <?php if (isset($message)) echo "<p style='color:green; font-family: Nunito; font-weight: bold;'>$message</p>"; ?>
        
        <form method="POST" style="display:flex; flex-direction:column; gap: 15px; width: 350px; font-family: Nunito;">
            <input type="text" name="nouveau_nom" placeholder="Nom de l'utilisateur (Identifiant)" required style="padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
            <input type="password" name="nouveau_pass" placeholder="Mot de passe temporaire" required style="padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
            <select name="role_user" style="padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                <option value="editeur">Éditeur (Peut créer/modifier des pages)</option>
                <option value="admin">Administrateur (Contrôle total)</option>
            </select>
            <button type="submit" name="creer_user" style="background:#654178; color:white; padding:12px; font-family: 'Oswald', sans-serif; font-size: 1.1rem; cursor: pointer; border: none; border-radius: 5px;">Créer le compte</button>
        </form>
    </main>
</body>
</html>