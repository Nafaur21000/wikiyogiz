<?php
require_once '../config/auth.php';
require_once '../config/link_bdd_php.php';
exiger_connexion();

$erreur = null;

$page = ['id' => '', 'titre' => '', 'contenu' => '', 'parent_id' => null, 'ordre' => 0, 'slug' => ''];

/* ---------- CHARGEMENT D'UNE PAGE EXISTANTE ---------- */
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $trouvee = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($trouvee) {
        $page = $trouvee;
    } else {
        $erreur = "Cette page n'existe pas.";
    }
}

/* ---------- ENREGISTREMENT ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre     = trim($_POST['titre']);
    $contenu   = $_POST['contenu'];
    $parent_id = ($_POST['parent_id'] === '') ? null : (int)$_POST['parent_id'];
    $ordre     = (int)$_POST['ordre'];
    $page_id   = $_POST['page_id'] !== '' ? (int)$_POST['page_id'] : null;

    if ($titre === '') {
        $erreur = "Le titre est obligatoire.";
    } elseif ($page_id !== null && $parent_id === $page_id) {
        $erreur = "Une page ne peut pas être son propre parent.";
    } else {
        $slug = slug_unique($pdo, creer_slug($titre), $page_id);

        if ($page_id !== null) {
            $stmt = $pdo->prepare("UPDATE pages SET titre=?, contenu=?, slug=?, parent_id=?, ordre=? WHERE id=?");
            $stmt->execute([$titre, $contenu, $slug, $parent_id, $ordre, $page_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO pages (titre, contenu, slug, parent_id, ordre) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$titre, $contenu, $slug, $parent_id, $ordre]);
        }

        header("Location: pages_gestion.php");
        exit;
    }

    $page = ['id' => $page_id, 'titre' => $titre, 'contenu' => $contenu,
             'parent_id' => $parent_id, 'ordre' => $ordre, 'slug' => ''];
}

/* ---------- LISTE DES PARENTS POSSIBLES ---------- */
$parents_possibles = $pdo->query("SELECT id, titre FROM pages WHERE parent_id IS NULL ORDER BY ordre ASC, titre ASC")
                         ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page['id'] ? 'Modifier une page' : 'Nouvelle page'; ?> - WikiYogiz</title>
    <link rel="stylesheet" href="../style/style.css">
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
</head>
<body class="admin-body">

<?php include '_menu_admin.php'; ?>

<main class="admin-main">
    <h1><?php echo $page['id'] ? 'Modifier la page' : 'Créer une nouvelle page'; ?></h1>

    <?php if ($erreur): ?><div class="alert alert-error"><?php echo htmlspecialchars($erreur); ?></div><?php endif; ?>

    <form method="POST" class="form-admin">
        <input type="hidden" name="page_id" value="<?php echo htmlspecialchars((string)$page['id']); ?>">

        <div>
            <label for="titre">Titre de la page</label>
            <input type="text" id="titre" name="titre" required
                   value="<?php echo htmlspecialchars($page['titre']); ?>">
        </div>

        <div>
            <label for="parent_id">Emplacement dans le menu</label>
            <select id="parent_id" name="parent_id">
                <option value="">— Menu principal (page de premier niveau) —</option>
                <?php foreach ($parents_possibles as $pp): ?>
                    <?php if ((string)$pp['id'] === (string)$page['id']) continue; ?>
                    <option value="<?php echo $pp['id']; ?>"
                        <?php echo ((string)$page['parent_id'] === (string)$pp['id']) ? 'selected' : ''; ?>>
                        Sous-menu de : <?php echo htmlspecialchars(strtoupper($pp['titre'])); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small style="font-family:'Nunito',sans-serif; color:#666;">
                Le menu et les sous-menus du site se mettent à jour automatiquement.
            </small>
        </div>

        <div>
            <label for="ordre">Ordre d'affichage</label>
            <input type="number" id="ordre" name="ordre" value="<?php echo (int)$page['ordre']; ?>" style="max-width:150px;">
            <small style="font-family:'Nunito',sans-serif; color:#666;">
                Plus le nombre est petit, plus l'entrée apparaît haut dans le menu.
            </small>
        </div>

        <div>
            <label for="editeur">Contenu (texte, images, fichiers)</label>
            <textarea name="contenu" id="editeur"><?php echo htmlspecialchars($page['contenu']); ?></textarea>
        </div>

        <div>
            <button type="submit" class="btn">Sauvegarder la page</button>
            <a href="pages_gestion.php" class="btn btn-small" style="background:#888;">Annuler</a>
        </div>
    </form>
</main>

<script>
    CKEDITOR.replace('editeur', {
        filebrowserUploadUrl: 'upload.php',
        filebrowserUploadMethod: 'form',
        height: 420
    });
</script>
<script src="../script/script.js"></script>
</body>
</html>