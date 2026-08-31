<?php
// ============================================================
//  ÉDITEUR DE PAGE — VERSION MAISON
//
//  CKEditor a été retiré. À la place, un éditeur intégré au site :
//    - une grande zone d'écriture (600 px, redimensionnable)
//    - une barre d'outils simple
//    - l'envoi d'images et de fichiers UNIQUEMENT depuis le PC
//    - la gestion de sections (ajouter / supprimer)
//
//  Principe technique : la zone d'écriture est un <div> avec
//  l'attribut contenteditable. Le navigateur y autorise la mise
//  en forme, et au moment d'enregistrer on recopie son contenu
//  HTML dans un <textarea> caché, qui part avec le formulaire.
//
//    - URL sans ?id=    -> formulaire vide       -> INSERT
//    - URL avec ?id=12  -> formulaire pré-rempli -> UPDATE
// ============================================================
require_once '../config/auth.php';
require_once '../config/link_bdd_php.php';
exiger_editeur();

$erreur = null;

$page = [
    'id' => '', 'titre' => '', 'contenu' => '',
    'parent_id' => null, 'ordre' => 0, 'slug' => '', 'admin_only' => 0,
];

/* ---------- CHARGEMENT D'UNE PAGE EXISTANTE ---------- */
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $trouvee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trouvee) {
        $erreur = "Cette page n'existe pas.";
    } elseif (!peut_modifier_page($trouvee)) {
        // Page verrouillée et utilisateur non administrateur
        header('Location: pages_gestion.php?refus=1');
        exit;
    } else {
        $page = $trouvee;
    }
}

/* ---------- ENREGISTREMENT ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre     = trim($_POST['titre']);
    $contenu   = $_POST['contenu'];
    $parent_id = ($_POST['parent_id'] === '') ? null : (int)$_POST['parent_id'];
    $ordre     = (int)$_POST['ordre'];
    $page_id   = $_POST['page_id'] !== '' ? (int)$_POST['page_id'] : null;

    // Seul un administrateur peut poser ou retirer le verrou.
    // Pour un éditeur, on conserve la valeur déjà en base.
    $admin_only = est_admin() ? (isset($_POST['admin_only']) ? 1 : 0) : (int)$page['admin_only'];

    // Re-vérification côté serveur : un éditeur ne doit pas pouvoir
    // modifier une page verrouillée, même en forçant le formulaire.
    if ($page_id !== null) {
        $verif = $pdo->prepare("SELECT admin_only, slug FROM pages WHERE id = ?");
        $verif->execute([$page_id]);
        $existante = $verif->fetch(PDO::FETCH_ASSOC);
        if ($existante && !peut_modifier_page($existante)) {
            header('Location: pages_gestion.php?refus=1');
            exit;
        }
    }

    if ($titre === '') {
        $erreur = "Le titre est obligatoire.";
    } elseif ($page_id !== null && $parent_id === $page_id) {
        $erreur = "Une page ne peut pas être son propre parent.";
    } else {
        // La page d'accueil garde son slug 'accueil' quoi qu'il arrive :
        // c'est lui qui permet à index.php de la retrouver.
        if ($page_id !== null && ($existante['slug'] ?? '') === 'accueil') {
            $slug = 'accueil';
        } else {
            $slug = slug_unique($pdo, creer_slug($titre), $page_id);
        }

        if ($page_id !== null) {
            $stmt = $pdo->prepare("UPDATE pages SET titre=?, contenu=?, slug=?, parent_id=?, ordre=?, admin_only=? WHERE id=?");
            $stmt->execute([$titre, $contenu, $slug, $parent_id, $ordre, $admin_only, $page_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO pages (titre, contenu, slug, parent_id, ordre, admin_only) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$titre, $contenu, $slug, $parent_id, $ordre, $admin_only]);
        }

        header("Location: pages_gestion.php?ok=1");
        exit;
    }

    // Erreur : on réinjecte la saisie pour ne rien perdre
    $page = ['id' => $page_id, 'titre' => $titre, 'contenu' => $contenu,
             'parent_id' => $parent_id, 'ordre' => $ordre, 'slug' => '',
             'admin_only' => $admin_only];
}

/* ---------- LISTE DES PARENTS POSSIBLES ---------- */
$parents_possibles = $pdo->query(
    "SELECT id, titre FROM pages
     WHERE parent_id IS NULL AND slug <> 'accueil'
     ORDER BY ordre ASC, titre ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$est_accueil = (($page['slug'] ?? '') === 'accueil');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page['id'] ? 'Modifier une page' : 'Nouvelle page'; ?> - WikiYogiz</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body class="admin-body">

<?php include '_menu_admin.php'; ?>

<main class="admin-main">
    <h1><?php echo $page['id'] ? 'Modifier la page' : 'Créer une nouvelle page'; ?></h1>

    <?php if ($erreur): ?><div class="alert alert-error"><?php echo htmlspecialchars($erreur); ?></div><?php endif; ?>

    <?php if ($est_accueil): ?>
        <div class="alert alert-ok">
            Tu modifies la <strong>page d'accueil</strong> du site. Son adresse et sa place
            dans le menu ne changent pas : seul le contenu est modifiable.
        </div>
    <?php endif; ?>

    <!-- onsubmit : recopie le contenu de la zone d'écriture dans le
         champ caché juste avant l'envoi du formulaire. -->
    <form method="POST" class="form-admin" id="formPage" onsubmit="return preparerEnvoi();">
        <input type="hidden" name="page_id" value="<?php echo htmlspecialchars((string)$page['id']); ?>">

        <div>
            <label for="titre">Titre de la page</label>
            <input type="text" id="titre" name="titre" required
                   value="<?php echo htmlspecialchars($page['titre']); ?>">
        </div>

        <?php if (!$est_accueil): ?>
        <div class="ligne-champs">
            <div style="flex:2;">
                <label for="parent_id">Emplacement dans le menu</label>
                <select id="parent_id" name="parent_id">
                    <option value="">— Menu principal (premier niveau) —</option>
                    <?php foreach ($parents_possibles as $pp): ?>
                        <?php if ((string)$pp['id'] === (string)$page['id']) continue; ?>
                        <option value="<?php echo $pp['id']; ?>"
                            <?php echo ((string)$page['parent_id'] === (string)$pp['id']) ? 'selected' : ''; ?>>
                            Sous-menu de : <?php echo htmlspecialchars(strtoupper($pp['titre'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex:1;">
                <label for="ordre">Ordre d'affichage</label>
                <input type="number" id="ordre" name="ordre" value="<?php echo (int)$page['ordre']; ?>">
                <small>Petit nombre = plus haut dans le menu.</small>
            </div>
        </div>
        <?php else: ?>
            <input type="hidden" name="parent_id" value="">
            <input type="hidden" name="ordre" value="0">
        <?php endif; ?>

        <?php if (est_admin()): ?>
        <div>
            <label style="text-transform:none; font-family:'Nunito',sans-serif;">
                <input type="checkbox" name="admin_only" value="1"
                       <?php echo ((int)$page['admin_only'] === 1) ? 'checked' : ''; ?>>
                Page réservée à l'administrateur — les éditeurs ne pourront ni la modifier ni la supprimer
            </label>
        </div>
        <?php endif; ?>

        <!-- ============================================================
             ZONE D'ÉCRITURE
             ============================================================ -->
        <div>
            <label>Contenu de la page</label>

            <div class="editeur">
                <div class="editeur-barre">
                    <div class="editeur-groupe">
                        <button type="button" data-cmd="bold"   title="Gras"><strong>G</strong></button>
                        <button type="button" data-cmd="italic" title="Italique"><em>I</em></button>
                        <button type="button" data-cmd="underline" title="Souligné"><u>S</u></button>
                    </div>

                    <div class="editeur-groupe">
                        <button type="button" data-bloc="h2" title="Grand titre">Titre</button>
                        <button type="button" data-bloc="h3" title="Sous-titre">Sous-titre</button>
                        <button type="button" data-bloc="p"  title="Paragraphe">Texte</button>
                    </div>

                    <div class="editeur-groupe">
                        <button type="button" data-cmd="insertUnorderedList" title="Liste à puces">&bull; Liste</button>
                        <button type="button" data-cmd="insertOrderedList"  title="Liste numérotée">1. Liste</button>
                    </div>

                    <div class="editeur-groupe">
                        <button type="button" id="btnLien" title="Lien vers une page web">Lien</button>
                        <button type="button" id="btnImage" title="Image depuis l'ordinateur">Image</button>
                        <button type="button" id="btnFichier" title="Fichier depuis l'ordinateur">Fichier</button>
                    </div>

                    <div class="editeur-groupe">
                        <button type="button" id="btnSectionAjout" title="Ajouter une section">+ Section</button>
                        <button type="button" id="btnSectionSuppr" title="Supprimer la section où se trouve le curseur">&minus; Section</button>
                    </div>

                    <div class="editeur-groupe">
                        <button type="button" data-cmd="removeFormat" title="Enlever la mise en forme">Nettoyer</button>
                        <button type="button" id="btnHtml" title="Voir et modifier le code HTML">&lt;/&gt;</button>
                    </div>
                </div>

                <!-- La zone d'écriture. contenteditable = le navigateur
                     laisse l'utilisateur écrire et mettre en forme dedans. -->
                <div class="editeur-zone" id="zoneEdition" contenteditable="true"><?php echo $page['contenu']; ?></div>

                <!-- Vue code, masquée par défaut -->
                <textarea class="editeur-html" id="zoneHtml" style="display:none;"></textarea>

                <div class="editeur-pied">
                    <span id="compteurMots">0 mot</span>
                    <span id="messageUpload"></span>
                </div>
            </div>

            <!-- Champ réellement envoyé au serveur -->
            <textarea name="contenu" id="contenuCache" style="display:none;"></textarea>

            <!-- Champs de fichier invisibles, déclenchés par les boutons -->
            <input type="file" id="inputImage" accept="image/*" style="display:none;">
            <input type="file" id="inputFichier" style="display:none;">
        </div>

        <div>
            <button type="submit" class="btn">Sauvegarder la page</button>
            <a href="pages_gestion.php" class="btn btn-small" style="background:#888;">Annuler</a>
        </div>
    </form>
</main>

<script src="../script/editeur.js"></script>
<script src="../script/script.js"></script>
</body>
</html>