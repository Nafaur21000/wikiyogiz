<?php
// ============================================================
//  UTILISATEURS ET RÔLES  —  réservé à l'administrateur
//
//  Vocabulaire :
//    identifiant (colonne nom_utilisateur) = sert à se connecter
//    prénom + nom                          = servent à l'affichage
//                                            et aux initiales du profil
// ============================================================
require_once '../config/auth.php';
require_once '../config/link_bdd_php.php';
exiger_admin();

$message = null;
$erreur  = null;

$roles_valides = ['admin', 'editeur', 'lecteur'];

/* ---------- CRÉATION ---------- */
if (isset($_POST['creer_user'])) {
    $prenom      = trim($_POST['prenom']);
    $nom         = trim($_POST['nom']);
    $identifiant = trim($_POST['identifiant']);
    $pass        = $_POST['mot_de_passe'];
    $role        = $_POST['role_user'];

    if ($identifiant === '' || $pass === '') {
        $erreur = t('users.err_oblig');
    } elseif (!in_array($role, $roles_valides, true)) {
        $erreur = t('users.err_role');
    } else {
        $verif = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE nom_utilisateur = ?");
        $verif->execute([$identifiant]);

        if ((int)$verif->fetchColumn() > 0) {
            $erreur = t('users.err_pris');
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO utilisateurs (nom_utilisateur, mot_de_passe, role, prenom, nom)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$identifiant, password_hash($pass, PASSWORD_DEFAULT), $role, $prenom, $nom]);
            $message = t('users.cree');
        }
    }
}

/* ---------- MODIFICATION (identité + rôle) ---------- */
if (isset($_POST['modifier_user'])) {
    $id     = (int)$_POST['user_id'];
    $prenom = trim($_POST['prenom_mod']);
    $nom    = trim($_POST['nom_mod']);
    $role   = $_POST['role_mod'];

    if (!in_array($role, $roles_valides, true)) {
        $erreur = t('users.err_role');
    } elseif ($id === (int)$_SESSION['user_id'] && $role !== 'admin') {
        // Garde-fou : le dernier admin ne peut pas se rétrograder
        $erreur = t('users.err_soi_admin');
    } else {
        $pdo->prepare("UPDATE utilisateurs SET prenom = ?, nom = ?, role = ? WHERE id = ?")
            ->execute([$prenom, $nom, $role, $id]);

        // Si l'admin modifie son propre profil, la session doit suivre
        if ($id === (int)$_SESSION['user_id']) {
            $_SESSION['prenom']      = $prenom;
            $_SESSION['nom_famille'] = $nom;
            $_SESSION['role']        = $role;
        }
        $message = t('users.maj');
    }
}

/* ---------- SUPPRESSION ---------- */
if (isset($_GET['supprimer'])) {
    $id = (int)$_GET['supprimer'];
    if ($id === (int)$_SESSION['user_id']) {
        $erreur = t('users.err_soi_suppr');
    } else {
        $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?")->execute([$id]);
        $message = t('users.supprime');
    }
}

$utilisateurs = $pdo->query(
    "SELECT id, nom_utilisateur, prenom, nom, role, date_creation
     FROM utilisateurs ORDER BY role ASC, nom_utilisateur ASC"
)->fetchAll(PDO::FETCH_ASSOC);

/** Initiales affichées dans la liste. */
function ini_user($u) {
    $i = '';
    if (trim($u['prenom']) !== '') { $i .= mb_substr(trim($u['prenom']), 0, 1); }
    if (trim($u['nom'])    !== '') { $i .= mb_substr(trim($u['nom']), 0, 1); }
    if ($i === '') { $i = mb_substr($u['nom_utilisateur'], 0, 1); }
    return mb_strtoupper($i);
}
?>
<!DOCTYPE html>
<html lang="<?php echo langue(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo te('users.titre'); ?> - WikiYogiz</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body class="admin-body">

<?php include '_menu_admin.php'; ?>

<main class="admin-main">
    <h1><?php echo te('users.titre'); ?></h1>

    <?php if ($message): ?><div class="alert alert-ok"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($erreur):  ?><div class="alert alert-error"><?php echo htmlspecialchars($erreur); ?></div><?php endif; ?>


    <div class="admin-card">
        <h2><?php echo te('users.existants'); ?></h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:60px;"></th>
                    <th style="width:180px;"><?php echo te('users.col_identifiant'); ?></th>
                    <th><?php echo te('users.col_identite'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($utilisateurs as $u): ?>
                <?php $moi = ((int)$u['id'] === (int)($_SESSION['user_id'] ?? 0)); ?>
                <tr>
                    <td><span class="pastille-initiales"><?php echo htmlspecialchars(ini_user($u)); ?></span></td>

                    <td>
                        <code><?php echo htmlspecialchars($u['nom_utilisateur']); ?></code>
                        <?php if ($moi): ?><br><span class="badge"><?php echo te('users.cest_toi'); ?></span><?php endif; ?>
                    </td>

                    <!-- Un formulaire ne peut pas être réparti sur plusieurs
                         cellules : tout ce qui concerne un compte tient donc
                         dans une seule colonne. -->
                    <td>
                        <form method="POST" class="form-ligne">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <input type="text" name="prenom_mod" placeholder="<?php echo te('users.prenom'); ?>" value="<?php echo htmlspecialchars($u['prenom']); ?>" style="width:120px;">
                            <input type="text" name="nom_mod" placeholder="<?php echo te('users.nom'); ?>" value="<?php echo htmlspecialchars($u['nom']); ?>" style="width:120px;">
                            <select name="role_mod">
                                <option value="lecteur" <?php echo $u['role'] === 'lecteur' ? 'selected' : ''; ?>><?php echo te('role.lecteur'); ?></option>
                                <option value="editeur" <?php echo $u['role'] === 'editeur' ? 'selected' : ''; ?>><?php echo te('role.editeur'); ?></option>
                                <option value="admin"   <?php echo $u['role'] === 'admin'   ? 'selected' : ''; ?>><?php echo te('role.admin'); ?></option>
                            </select>
                            <button type="submit" name="modifier_user" value="1" class="btn btn-small"><?php echo te('commun.enregistrer'); ?></button>
                        </form>

                        <?php if (!$moi): ?>
                            <div style="margin-top:8px;">
                                <a href="?supprimer=<?php echo $u['id']; ?>" class="btn btn-small btn-danger"
                                   data-confirm="<?php echo te('users.confirm_suppr'); ?>"><?php echo te('users.suppr_compte'); ?></a>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="admin-card">
        <h2><?php echo te('users.creer'); ?></h2>
        <form method="POST" class="form-admin" style="max-width:460px;">
            <div class="ligne-champs">
                <div><label for="prenom"><?php echo te('users.prenom'); ?></label><input type="text" id="prenom" name="prenom"></div>
                <div><label for="nom"><?php echo te('users.nom'); ?></label><input type="text" id="nom" name="nom"></div>
            </div>
            <div>
                <label for="identifiant"><?php echo te('users.identifiant_lbl'); ?></label>
                <input type="text" id="identifiant" name="identifiant" required>
                <small><?php echo te('users.identifiant_aide'); ?></small>
            </div>
            <div>
                <label for="mot_de_passe"><?php echo te('users.mdp_temporaire'); ?></label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required minlength="8">
                <small><?php echo te('users.mdp_aide'); ?></small>
            </div>
            <div>
                <label for="role_user"><?php echo te('users.role'); ?></label>
                <select id="role_user" name="role_user">
                    <option value="lecteur"><?php echo te('users.role_lecteur'); ?></option>
                    <option value="editeur" selected><?php echo te('users.role_editeur'); ?></option>
                    <option value="admin"><?php echo te('users.role_admin'); ?></option>
                </select>
            </div>
            <div><button type="submit" name="creer_user" value="1" class="btn"><?php echo te('users.bouton_creer'); ?></button></div>
        </form>
    </div>
</main>

<script src="../script/script.js"></script>
</body>
</html>