<?php
// ============================================================
//  MON MOT DE PASSE
//  Accessible à tous les rôles connectés, lecteurs compris.
//  L'administrateur donne un mot de passe temporaire à la
//  création du compte, puis n'y a plus accès : c'est ici que
//  chacun le change, en confirmant l'ancien.
// ============================================================
require_once 'auth.php';
require_once 'link_bdd_php.php';

// Ce fichier est DANS config/ : le formulaire de connexion est
// juste à côté, on n'utilise donc pas exiger_connexion().
if (!est_connecte()) {
    header('Location: login.php');
    exit;
}

$message = null;
$erreur  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actuel   = $_POST['actuel'];
    $nouveau  = $_POST['nouveau'];
    $confirme = $_POST['confirme'];

    // On relit le hash en base : il n'est jamais stocké en session.
    $stmt = $pdo->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($actuel, $hash)) {
        $erreur = t('profil.err_actuel');
    } elseif (mb_strlen($nouveau) < 8) {
        $erreur = t('profil.err_court');
    } elseif ($nouveau !== $confirme) {
        $erreur = t('profil.err_differe');
    } elseif ($nouveau === $actuel) {
        $erreur = t('profil.err_identique');
    } else {
        $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?")
            ->execute([password_hash($nouveau, PASSWORD_DEFAULT), $_SESSION['user_id']]);
        $message = t('profil.ok');
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo langue(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo te('profil.mon_mdp'); ?> - WikiYogiz</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body class="page-connexion">

    <div class="carte-connexion" style="width:380px;">
        <h2><?php echo te('profil.mon_mdp'); ?></h2>

        <p style="font-family:'Nunito',sans-serif; text-align:center; color:#777; font-size:0.9rem;">
            <?php echo te('profil.connecte_en'); ?>
            <strong><?php echo htmlspecialchars(nom_affiche()); ?></strong>
        </p>

        <?php if ($message): ?><div class="alert alert-ok"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($erreur):  ?><div class="alert alert-error"><?php echo htmlspecialchars($erreur); ?></div><?php endif; ?>

        <form method="POST" class="form-admin">
            <div>
                <label for="actuel"><?php echo te('profil.actuel'); ?></label>
                <input type="password" id="actuel" name="actuel" required>
            </div>
            <div>
                <label for="nouveau"><?php echo te('profil.nouveau'); ?></label>
                <input type="password" id="nouveau" name="nouveau" required minlength="8">
                <small><?php echo te('profil.min8'); ?></small>
            </div>
            <div>
                <label for="confirme"><?php echo te('profil.confirmer'); ?></label>
                <input type="password" id="confirme" name="confirme" required minlength="8">
            </div>
            <button type="submit" class="btn"><?php echo te('profil.bouton'); ?></button>
        </form>

        <p style="text-align:center; margin-top:20px;">
            <a href="../index.php" style="color:#654178;"><?php echo te('commun.retour_site'); ?></a>
        </p>
    </div>

</body>
</html>