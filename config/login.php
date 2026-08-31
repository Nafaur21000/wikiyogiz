<?php
// ============================================================
//  CONNEXION
//  nom_utilisateur = IDENTIFIANT de connexion.
//  prenom et nom servent à l'affichage et aux initiales.
//
//  Destination après connexion :
//    admin             -> panneau d'administration
//    éditeur / lecteur -> page d'accueil du site
// ============================================================
require_once 'auth.php';
require_once 'link_bdd_php.php';

if (est_connecte()) {
    header('Location: ' . destination_apres_connexion(role_actuel()));
    exit;
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = trim($_POST['username']);
    $pass        = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE nom_utilisateur = ?");
    $stmt->execute([$identifiant]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($u && password_verify($pass, $u['mot_de_passe'])) {
        session_regenerate_id(true);

        $_SESSION['user_id']     = $u['id'];
        $_SESSION['role']        = $u['role'];
        $_SESSION['identifiant'] = $u['nom_utilisateur'];
        // Les colonnes prenom/nom peuvent ne pas exister sur une
        // base pas encore migrée : ?? '' évite toute erreur.
        $_SESSION['prenom']      = $u['prenom'] ?? '';
        $_SESSION['nom_famille'] = $u['nom'] ?? '';

        header('Location: ' . destination_apres_connexion($u['role']));
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
<body class="page-connexion">

    <div class="carte-connexion">
        <!-- Logo réduit : 90px au lieu de 180px -->
        <img src="../images/logo-contour.png" alt="WikiYogiz" class="logo-connexion">
        <h2>CONNEXION</h2>

        <?php if ($erreur): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($erreur); ?></div>
        <?php endif; ?>

        <form method="POST" class="form-admin">
            <div>
                <label for="username">Identifiant</label>
                <input type="text" id="username" name="username" required autofocus>
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