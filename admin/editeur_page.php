<?php
// ============================================================
//  ÉDITEUR DE PAGE (création ET modification)
//    - URL sans ?id=    -> formulaire vide       -> INSERT
//    - URL avec ?id=12  -> formulaire pré-rempli -> UPDATE
//  Accessible aux éditeurs et aux administrateurs.
// ============================================================
require_once '../config/auth.php';
require_once '../config/link_bdd_php.php';
exiger_editeur();

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            <label for="editeur">Contenu</label>
            <textarea name="contenu" id="editeur"><?php echo htmlspecialchars($page['contenu']); ?></textarea>
        </div>

        <div>
            <button type="submit" class="btn">Sauvegarder la page</button>
            <a href="pages_gestion.php" class="btn btn-small" style="background:#888;">Annuler</a>
        </div>
    </form>

    <!-- ============================================================
         BLOC D'ENVOI DIRECT
         Indépendant de CKEditor : si son navigateur de fichiers fait
         des siennes, ce bloc fonctionne toujours. Il envoie le fichier
         puis l'insère dans l'éditeur, ou donne le lien à copier.
         Il est HORS du formulaire principal : un formulaire ne peut
         pas en contenir un autre en HTML.
         ============================================================ -->
    <div class="admin-card" style="margin-top:30px;">
        <h2>Envoyer une image ou un fichier</h2>
        <p style="font-family:'Nunito',sans-serif; margin-top:0;">
            Choisis un fichier sur ton ordinateur, puis insère-le à l'endroit du curseur dans le contenu.
        </p>

        <input type="file" id="fichierLocal" style="font-family:'Nunito',sans-serif;">
        <button type="button" id="btnEnvoyer" class="btn btn-small" style="margin-left:10px;">Envoyer</button>

        <div id="resultatUpload" style="margin-top:15px; font-family:'Nunito',sans-serif;"></div>
    </div>
</main>

<script>
/* ------------------------------------------------------------
   CONFIGURATION DE CKEDITOR
   ------------------------------------------------------------ */

// On force les URL telles qu'on les renvoie ("/upload/photo.png") :
// sans ça CKEditor les réécrirait en chemins relatifs, et les images
// se casseraient une fois la page affichée à la racine du site.
CKEDITOR.on('instanceCreated', function () {
    CKEDITOR.config.baseHref = '/';
});

/* SUPPRESSION DE L'AJOUT PAR URL
   Les boîtes "Image" et "Lien" de CKEditor proposent par défaut de
   coller une adresse. On ouvre directement l'onglet "Envoyer" et on
   masque le champ URL pour ne laisser que l'envoi depuis le PC. */
CKEDITOR.on('dialogDefinition', function (ev) {
    var nom = ev.data.name;
    var def = ev.data.definition;

    function masquer(ongletId, champs) {
        var onglet = def.getContents(ongletId);
        if (!onglet) return;
        champs.forEach(function (id) {
            var el = onglet.get(id);
            if (el) { el.style = 'display:none !important;'; }
        });
    }

    if (nom === 'image') {
        masquer('info', ['txtUrl', 'browse', 'btnUpload']);
        def.onShow = function () { this.selectPage('Upload'); };
    }

    if (nom === 'link') {
        masquer('info', ['linkType', 'urlOptions', 'browse']);
        def.onShow = function () { this.selectPage('upload'); };
    }
});

CKEDITOR.replace('editeur', {
    height: 420,

    // Branche les onglets "Envoyer" sur notre script admin/upload.php
    filebrowserUploadUrl:      'upload.php',
    filebrowserImageUploadUrl: 'upload.php',
    filebrowserUploadMethod:   'form',

    // Aucune adresse de navigateur de fichiers : le bouton
    // "Parcourir le serveur" disparaît de lui-même.
    filebrowserBrowseUrl:      '',
    filebrowserImageBrowseUrl: ''
});

/* ------------------------------------------------------------
   BLOC D'ENVOI DIRECT
   fetch() envoie le fichier en arrière-plan et lit la réponse JSON
   renvoyée par upload.php.
   ------------------------------------------------------------ */
document.getElementById('btnEnvoyer').addEventListener('click', function () {
    var input  = document.getElementById('fichierLocal');
    var sortie = document.getElementById('resultatUpload');

    if (!input.files.length) {
        sortie.innerHTML = '<span style="color:#a02a1c;">Choisis d\'abord un fichier.</span>';
        return;
    }

    var donnees = new FormData();
    donnees.append('fichier', input.files[0]);

    sortie.textContent = 'Envoi en cours...';

    fetch('upload.php', { method: 'POST', body: donnees })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.uploaded) {
                sortie.innerHTML = '<span style="color:#a02a1c;">' + (data.error ? data.error.message : 'Échec de l\'envoi.') + '</span>';
                return;
            }

            var estImage = /\.(jpe?g|png|gif|webp|svg|avif)$/i.test(data.url);

            sortie.innerHTML =
                '<span style="color:#1e6b2c;">Fichier envoyé.</span> ' +
                '<button type="button" class="btn btn-small" id="btnInserer">Insérer dans le contenu</button> ' +
                '<code style="margin-left:10px;">' + data.url + '</code>';

            document.getElementById('btnInserer').addEventListener('click', function () {
                var html = estImage
                    ? '<img src="' + data.url + '" alt="">'
                    : '<a href="' + data.url + '" target="_blank">' + data.fileName + '</a>';
                CKEDITOR.instances.editeur.insertHtml(html);
                sortie.innerHTML = '<span style="color:#1e6b2c;">Inséré dans le contenu.</span>';
            });
        })
        .catch(function () {
            sortie.innerHTML = '<span style="color:#a02a1c;">Le serveur n\'a pas répondu correctement.</span>';
        });
});
</script>
<script src="../script/script.js"></script>
</body>
</html>