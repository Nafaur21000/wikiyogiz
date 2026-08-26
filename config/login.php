<?php
// ============================================================
//  CONNEXION
//  La destination après connexion dépend du rôle :
//    admin             -> panneau d'administration
//    éditeur / lecteur -> page d'accueil du site
// ============================================================
require_once 'auth.php';
require_once 'link_bdd_php.php';

// Déjà connecté : on n'affiche pas le formulaire une seconde fois.
if (est_connecte()) {
    header('Location: ' . destination_apres_connexion(role_actuel()));
    exit;
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE nom_utilisateur = ?");
    $stmt->execute([$user]);
    $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($utilisateur && password_verify($pass, $utilisateur['mot_de_passe'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $utilisateur['id'];
        $_SESSION['role']    = $utilisateur['role'];
        $_SESSION['nom']     = $utilisateur['nom_utilisateur'];

        header('Location: ' . destination_apres_connexion($utilisateur['role']));
        exit;
    }
    $erreur = "Identifiants incorrects.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - WikiYogiz</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body style="display:flex; justify-content:center; align-items:center; min-height:100vh;">

    <div style="background:white; padding:40px; border-radius:15px; width:340px;">
        <img src="../images/logo-contour.png" alt="WikiYogiz" style="width:100%; max-width:180px; display:block; margin:0 auto 20px;">
        <h2 style="text-align:center; margin-top:0;">CONNEXION</h2>

        <?php if ($erreur): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($erreur); ?></div>
        <?php endif; ?>

        <form method="POST" class="form-admin">
            <div>
                <label for="username">Identifiant</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div>
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">SE CONNECTER</button>
        </form>

        <p style="text-align:center; margin-top:20px;">
            <a href="../index.php" style="color:#654178;">Retour à l'accueil</a>
        </p>
    </div>

</body>
</html>