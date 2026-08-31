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
        $erreur = "L'identifiant et le mot de passe sont obligatoires.";
    } elseif (!in_array($role, $roles_valides, true)) {
        $erreur = "Rôle invalide.";
    } else {
        $verif = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE nom_utilisateur = ?");
        $verif->execute([$identifiant]);

        if ((int)$verif->fetchColumn() > 0) {
            $erreur = "Cet identifiant est déjà pris.";
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO utilisateurs (nom_utilisateur, mot_de_passe, role, prenom, nom)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$identifiant, password_hash($pass, PASSWORD_DEFAULT), $role, $prenom, $nom]);
            $message = "Compte « $identifiant » créé.";
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
        $erreur = "Rôle invalide.";
    } elseif ($id === (int)$_SESSION['user_id'] && $role !== 'admin') {
        // Garde-fou : le dernier admin ne peut pas se rétrograder
        $erreur = "Tu ne peux pas retirer ton propre statut d'administrateur.";
    } else {
        $pdo->prepare("UPDATE utilisateurs SET prenom = ?, nom = ?, role = ? WHERE id = ?")
            ->execute([$prenom, $nom, $role, $id]);

        // Si l'admin modifie son propre profil, la session doit suivre
        if ($id === (int)$_SESSION['user_id']) {
            $_SESSION['prenom']      = $prenom;
            $_SESSION['nom_famille'] = $nom;
            $_SESSION['role']        = $role;
        }
        $message = "Compte mis à jour.";
    }
}

/* ---------- RÉINITIALISATION DU MOT DE PASSE ---------- */
if (isset($_POST['reset_pass'])) {
    $id   = (int)$_POST['user_id'];
    $pass = $_POST['nouveau_pass'];
    if ($pass === '') {
        $erreur = "Le nouveau mot de passe ne peut pas être vide.";
    } else {
        $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?")
            ->execute([password_hash($pass, PASSWORD_DEFAULT), $id]);
        $message = "Mot de passe réinitialisé.";
    }
}

/* ---------- SUPPRESSION ---------- */
if (isset($_GET['supprimer'])) {
    $id = (int)$_GET['supprimer'];
    if ($id === (int)$_SESSION['user_id']) {
        $erreur = "Tu ne peux pas supprimer ton propre compte.";
    } else {
        $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?")->execute([$id]);
        $message = "Compte supprimé.";
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
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilisateurs - WikiYogiz</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body class="admin-body">

<?php include '_menu_admin.php'; ?>

<main class="admin-main">
    <h1>Utilisateurs et rôles</h1>

    <?php if ($message): ?><div class="alert alert-ok"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($erreur):  ?><div class="alert alert-error"><?php echo htmlspecialchars($erreur); ?></div><?php endif; ?>

    <div class="admin-card">
        <h2>Comptes existants</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:60px;"></th>
                    <th style="width:180px;">Identifiant</th>
                    <th>Identité, rôle et actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($utilisateurs as $u): ?>
                <?php $moi = ((int)$u['id'] === (int)($_SESSION['user_id'] ?? 0)); ?>
                <tr>
                    <td><span class="pastille-initiales"><?php echo htmlspecialchars(ini_user($u)); ?></span></td>

                    <td>
                        <code><?php echo htmlspecialchars($u['nom_utilisateur']); ?></code>
                        <?php if ($moi): ?><br><span class="badge">c'est toi</span><?php endif; ?>
                    </td>

                    <!-- Un formulaire ne peut pas être réparti sur plusieurs
                         cellules : tout ce qui concerne un compte tient donc
                         dans une seule colonne. -->
                    <td>
                        <form method="POST" class="form-ligne">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <input type="text" name="prenom_mod" placeholder="Prénom" value="<?php echo htmlspecialchars($u['prenom']); ?>" style="width:120px;">
                            <input type="text" name="nom_mod" placeholder="Nom" value="<?php echo htmlspecialchars($u['nom']); ?>" style="width:120px;">
                            <select name="role_mod">
                                <option value="lecteur" <?php echo $u['role'] === 'lecteur' ? 'selected' : ''; ?>>Lecteur</option>
                                <option value="editeur" <?php echo $u['role'] === 'editeur' ? 'selected' : ''; ?>>Éditeur</option>
                                <option value="admin"   <?php echo $u['role'] === 'admin'   ? 'selected' : ''; ?>>Administrateur</option>
                            </select>
                            <button type="submit" name="modifier_user" value="1" class="btn btn-small">Enregistrer</button>
                        </form>

                        <form method="POST" class="form-ligne" style="margin-top:8px;">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <input type="password" name="nouveau_pass" placeholder="Nouveau mot de passe" style="width:190px;">
                            <button type="submit" name="reset_pass" value="1" class="btn btn-small" style="background:#888;">Réinitialiser</button>

                            <?php if (!$moi): ?>
                                <a href="?supprimer=<?php echo $u['id']; ?>" class="btn btn-small btn-danger"
                                   data-confirm="Supprimer le compte « <?php echo htmlspecialchars($u['nom_utilisateur'], ENT_QUOTES); ?> » ?">Supprimer le compte</a>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="admin-card">
        <h2>Créer un compte</h2>
        <form method="POST" class="form-admin" style="max-width:460px;">
            <div class="ligne-champs">
                <div><label for="prenom">Prénom</label><input type="text" id="prenom" name="prenom"></div>
                <div><label for="nom">Nom</label><input type="text" id="nom" name="nom"></div>
            </div>
            <div>
                <label for="identifiant">Identifiant de connexion</label>
                <input type="text" id="identifiant" name="identifiant" required>
                <small>C'est ce que la personne saisira pour se connecter.</small>
            </div>
            <div>
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            <div>
                <label for="role_user">Rôle</label>
                <select id="role_user" name="role_user">
                    <option value="lecteur">Lecteur — lit les pages, pas de back-office</option>
                    <option value="editeur" selected>Éditeur — crée, modifie et supprime les pages</option>
                    <option value="admin">Administrateur — tout, plus les comptes et les pages verrouillées</option>
                </select>
            </div>
            <div><button type="submit" name="creer_user" value="1" class="btn">Créer le compte</button></div>
        </form>
    </div>
</main>

<script src="../script/script.js"></script>
</body>
</html>