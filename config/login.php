<?php
// ============================================================
//  CONNEXION — porte d'entrée du wiki
//  La page est traduite avant même l'identification : la langue
//  vient du cookie posé lors d'une visite précédente.
// ============================================================
require_once 'auth.php';
require_once 'link_bdd_php.php';

if (est_connecte()) {
    header('Location: ../index.php');
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
        $langue_choisie = langue();     // on garde la langue au travers
        session_regenerate_id(true);    // du changement d'identifiant de session
        $_SESSION['langue'] = $langue_choisie;

        $_SESSION['user_id']     = $u['id'];
        $_SESSION['role']        = $u['role'];
        $_SESSION['identifiant'] = $u['nom_utilisateur'];
        $_SESSION['prenom']      = $u['prenom'] ?? '';
        $_SESSION['nom_famille'] = $u['nom'] ?? '';

        header('Location: ../index.php');
        exit;
    }
    $erreur = t('conn.erreur');
}
?>
<!DOCTYPE html>
<html lang="<?php echo langue(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo te('conn.titre'); ?> - WikiYogiz</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body class="page-connexion">

    <div class="carte-connexion">
        <img src="../images/logo-contour.png" alt="WikiYogiz" class="logo-connexion">
        <h2><?php echo te('conn.titre'); ?></h2>

        <?php if ($erreur): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($erreur); ?></div>
        <?php endif; ?>

        <form method="POST" class="form-admin">
            <div>
                <label for="username"><?php echo te('conn.identifiant'); ?></label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div>
                <label for="password"><?php echo te('conn.mot_de_passe'); ?></label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn"><?php echo te('conn.bouton'); ?></button>
        </form>

        <!-- Bascule de langue, disponible avant même de se connecter -->
        <p class="choix-langue">
            <a href="<?php echo htmlspecialchars(url_langue(autre_langue())); ?>">
                <span class="drapeau"><?php echo autre_langue() === 'en' ? 'EN' : 'FR'; ?></span>
                <?php echo htmlspecialchars(nom_langue(autre_langue())); ?>
            </a>
        </p>
    </div>

</body>
</html>