<?php
require_once '../config/auth.php';
require_once '../config/link_bdd_php.php';
exiger_admin();

$message = null;
$erreur  = null;

/* ---------- CRÉATION D'UN UTILISATEUR ---------- */
if (isset($_POST['creer_user'])) {
    $nom  = trim($_POST['nouveau_nom']);
    $pass = $_POST['nouveau_pass'];
    $role = $_POST['role_user'];

    if ($nom === '' || $pass === '') {
        $erreur = "Le nom et le mot de passe sont obligatoires.";
    } elseif (!in_array($role, ['admin', 'editeur', 'lecteur'], true)) {
        $erreur = "Rôle invalide.";
    } else {
        $verif = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE nom_utilisateur = ?");
        $verif->execute([$nom]);
        if ((int)$verif->fetchColumn() > 0) {
            $erreur = "Cet identifiant est déjà pris.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom_utilisateur, mot_de_passe, role) VALUES (?, ?, ?)");
            $stmt->execute([$nom, password_hash($pass, PASSWORD_DEFAULT), $role]);
            $message = "L'utilisateur « $nom » a été créé avec le rôle « $role ».";
        }
    }
}

/* ---------- CHANGEMENT DE RÔLE ---------- */
if (isset($_POST['changer_role'])) {
    $id   = (int)$_POST['user_id'];
    $role = $_POST['role'];
    if (in_array($role, ['admin', 'editeur', 'lecteur'], true)) {
        if ($id === (int)$_SESSION['user_id'] && $role !== 'admin') {
            $erreur = "Tu ne peux pas retirer ton propre statut d'administrateur.";
        } else {
            $pdo->prepare("UPDATE utilisateurs SET role = ? WHERE id = ?")->execute([$role, $id]);
            $message = "Le rôle a été mis à jour.";
        }
    }
}

/* ---------- SUPPRESSION ---------- */
if (isset($_GET['supprimer'])) {
    $id = (int)$_GET['supprimer'];
    if ($id === (int)$_SESSION['user_id']) {
        $erreur = "Tu ne peux pas supprimer ton propre compte.";
    } else {
        $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?")->execute([$id]);
        $message = "L'utilisateur a été supprimé.";
    }
}

$utilisateurs = $pdo->query("SELECT id, nom_utilisateur, role, date_creation FROM utilisateurs ORDER BY nom_utilisateur ASC")
                    ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Utilisateurs et rôles - WikiYogiz</title>
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
                <tr><th>Identifiant</th><th>Rôle</th><th>Créé le</th><th style="width:300px;">Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($utilisateurs as $u): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($u['nom_utilisateur']); ?></strong></td>
                    <td><span class="badge <?php echo $u['role'] === 'admin' ? 'badge-admin' : ''; ?>"><?php echo htmlspecialchars($u['role']); ?></span></td>
                    <td><?php echo htmlspecialchars($u['date_creation']); ?></td>
                    <td>
                        <form method="POST" style="display:inline-flex; gap:6px; align-items:center;">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <select name="role" style="padding:6px; border-radius:4px; border:1px solid #ccc;">
                                    <option value="lecteur">Lecteur — lit les pages, pas de back-office</option>
                                    <option value="editeur" selected>Éditeur — crée, modifie et supprime les pages</option>
                                    <option value="admin">Administrateur — tout, plus la gestion des comptes</option>
                            </select>
                            <button type="submit" name="changer_role" value="1" class="btn btn-small">OK</button>
                        </form>
                        <a href="?supprimer=<?php echo $u['id']; ?>" class="btn btn-small btn-danger"
                           data-confirm="Supprimer le compte « <?php echo htmlspecialchars($u['nom_utilisateur'], ENT_QUOTES); ?> » ?">Suppr.</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="admin-card">
        <h2>Créer un nouvel accès</h2>
        <form method="POST" class="form-admin" style="max-width:420px;">
            <div>
                <label for="nouveau_nom">Identifiant</label>
                <input type="text" id="nouveau_nom" name="nouveau_nom" required>
            </div>
            <div>
                <label for="nouveau_pass">Mot de passe temporaire</label>
                <input type="password" id="nouveau_pass" name="nouveau_pass" required>
            </div>
            <div>
                <label for="role_user">Rôle</label>
                <select id="role_user" name="role_user">
                    <option value="lecteur">Lecteur — consultation uniquement</option>
                    <option value="editeur" selected>Éditeur — peut créer et modifier des pages</option>
                    <option value="admin">Administrateur — contrôle total</option>
                </select>
            </div>
            <div><button type="submit" name="creer_user" value="1" class="btn">Créer le compte</button></div>
        </form>
    </div>
</main>

<script src="../script/script.js"></script>
</body>
</html>