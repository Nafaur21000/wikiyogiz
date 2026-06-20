<?php
session_start();
require_once 'link_bdd_php.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE nom_utilisateur = ?");
    $stmt->execute([$user]);
    $utilisateur = $stmt->fetch();

    if ($utilisateur && password_verify($pass, $utilisateur['mot_de_passe'])) {
        $_SESSION['user_id'] = $utilisateur['id'];
        $_SESSION['role'] = $utilisateur['role'];
        $_SESSION['nom'] = $utilisateur['nom_utilisateur'];
        header('Location: admin/dashboard.php');
        exit;
    } else {
        $erreur = "Identifiants incorrects.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Wikiyogiz</title>
    <link rel="stylesheet" href="./style/style.css">
</head>
<body style="justify-content: center; align-items: center; height: 100vh; background-color: #cbe3fd;">
    <div style="background: white; padding: 40px; border-radius: 10px; color: #654178; box-shadow: 0 10px 20px rgba(0,0,0,0.15); width: 300px;">
        <h2 style="font-family: 'Oswald', sans-serif; text-align: center;">Connexion Admin</h2>
        
        <?php if (isset($erreur)) echo "<p style='color:red; font-family: Nunito; text-align: center;'>$erreur</p>"; ?>
        
        <form method="POST" style="display:flex; flex-direction:column; gap: 15px;">
            <input type="text" name="username" placeholder="Nom d'utilisateur" required style="padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-family: Nunito;">
            <input type="password" name="password" placeholder="Mot de passe" required style="padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-family: Nunito;">
            <button type="submit" style="background:#654178; color:white; padding:10px; border:none; border-radius: 5px; cursor: pointer; font-family: 'Oswald', sans-serif; font-size: 1.1rem;">SE CONNECTER</button>
        </form>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="index.php" style="color: #654178; text-decoration: none; font-family: Nunito;">Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>