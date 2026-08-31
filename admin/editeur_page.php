<?php
// ============================================================
//  ÉDITEUR DE PAGE — BILINGUE
//
//  Chaque page a deux versions : française et anglaise.
//  Deux onglets, deux titres, deux zones d'écriture.
//  Si l'anglais reste vide, le site affiche le français aux
//  lecteurs anglophones : on peut donc traduire plus tard.
//
//  Le slug (l'adresse de la page) est fabriqué à partir du
//  titre français et reste le même dans les deux langues :
//  changer de langue ne change pas l'URL.
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
    'titre_en' => '', 'contenu_en' => '',
    'parent_id' => null, 'ordre' => 0, 'slug' => '', 'admin_only' => 0,
];

/* ---------- CHARGEMENT D'UNE PAGE EXISTANTE ---------- */
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $trouvee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trouvee) {
        $erreur = t('edit.inexistante');
    } elseif (!peut_modifier_page($trouvee)) {
        header('Location: pages_gestion.php?refus=1');
        exit;
    } else {
        $page = array_merge($page, $trouvee);
    }
}

/* ---------- ENREGISTREMENT ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre      = trim($_POST['titre']);
    $titre_en   = trim($_POST['titre_en']);
    $contenu    = $_POST['contenu'];
    $contenu_en = $_POST['contenu_en'];
    $parent_id  = ($_POST['parent_id'] === '') ? null : (int)$_POST['parent_id'];
    $ordre      = (int)$_POST['ordre'];
    $page_id    = $_POST['page_id'] !== '' ? (int)$_POST['page_id'] : null;

    // Seul un administrateur peut poser ou retirer le verrou.
    $admin_only = est_admin() ? (isset($_POST['admin_only']) ? 1 : 0) : (int)$page['admin_only'];

    // Revérification côté serveur du droit de modifier.
    $existante = null;
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
        $erreur = t('edit.titre_oblig');
    } elseif ($page_id !== null && $parent_id === $page_id) {
        $erreur = t('edit.pas_propre_pere');
    } else {
        // L'accueil garde son slug quoi qu'il arrive : c'est lui
        // qui permet à index.php de retrouver la page.
        if ($existante && $existante['slug'] === 'accueil') {
            $slug = 'accueil';
        } else {
            $slug = slug_unique($pdo, creer_slug($titre), $page_id);
        }

        if ($page_id !== null) {
            $stmt = $pdo->prepare(
                "UPDATE pages SET titre=?, titre_en=?, contenu=?, contenu_en=?,
                        slug=?, parent_id=?, ordre=?, admin_only=?
                 WHERE id=?"
            );
            $stmt->execute([$titre, $titre_en, $contenu, $contenu_en,
                            $slug, $parent_id, $ordre, $admin_only, $page_id]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO pages (titre, titre_en, contenu, contenu_en, slug, parent_id, ordre, admin_only)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$titre, $titre_en, $contenu, $contenu_en,
                            $slug, $parent_id, $ordre, $admin_only]);
        }

        header("Location: pages_gestion.php?ok=1");
        exit;
    }

    // Erreur : on réinjecte la saisie pour ne rien perdre
    $page = ['id' => $page_id, 'titre' => $titre, 'titre_en' => $titre_en,
             'contenu' => $contenu, 'contenu_en' => $contenu_en,
             'parent_id' => $parent_id, 'ordre' => $ordre, 'slug' => '',
             'admin_only' => $admin_only];
}

/* ---------- LISTE DES PARENTS POSSIBLES ---------- */
$parents_possibles = $pdo->query(
    "SELECT * FROM pages
     WHERE parent_id IS NULL AND slug <> 'accueil'
     ORDER BY ordre ASC, titre ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$est_accueil = (($page['slug'] ?? '') === 'accueil');
?>
<!DOCTYPE html>
<html lang="<?php echo langue(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page['id'] ? te('edit.modifier') : te('edit.creer'); ?> - WikiYogiz</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body class="admin-body">

<?php include '_menu_admin.php'; ?>

<main class="admin-main">
    <h1><?php echo $page['id'] ? te('edit.modifier') : te('edit.creer'); ?></h1>

    <?php if ($erreur): ?><div class="alert alert-error"><?php echo htmlspecialchars($erreur); ?></div><?php endif; ?>

    <?php if ($est_accueil): ?>
        <div class="alert alert-ok"><?php echo te('edit.accueil_note'); ?></div>
    <?php endif; ?>

    <div class="alert alert-info"><?php echo te('edit.aide_bilingue'); ?></div>

    <form method="POST" class="form-admin" id="formPage" onsubmit="return preparerEnvoi();">
        <input type="hidden" name="page_id" value="<?php echo htmlspecialchars((string)$page['id']); ?>">

        <!-- ============================================================
             ONGLETS DE LANGUE
             Ils ne font que montrer ou cacher des blocs : les deux
             versions sont toujours dans le formulaire et partent
             ensemble à l'enregistrement.
             ============================================================ -->
        <div class="onglets-langue">
            <button type="button" class="onglet actif" data-onglet="fr">
                <span class="drapeau">FR</span> <?php echo te('edit.onglet_fr'); ?>
            </button>
            <button type="button" class="onglet" data-onglet="en">
                <span class="drapeau">EN</span> <?php echo te('edit.onglet_en'); ?>
            </button>
        </div>

        <!-- ---------- VERSION FRANÇAISE ---------- -->
        <div class="bloc-langue" data-bloc="fr">
            <div>
                <label for="titre"><?php echo te('edit.titre_page'); ?></label>
                <input type="text" id="titre" name="titre" required
                       value="<?php echo htmlspecialchars($page['titre']); ?>">
            </div>
        </div>

        <!-- ---------- VERSION ANGLAISE ---------- -->
        <div class="bloc-langue" data-bloc="en" style="display:none;">
            <div>
                <label for="titre_en"><?php echo te('edit.titre_en'); ?></label>
                <input type="text" id="titre_en" name="titre_en"
                       value="<?php echo htmlspecialchars($page['titre_en'] ?? ''); ?>">
            </div>
        </div>

        <!-- Les réglages de menu sont communs aux deux langues -->
        <?php if (!$est_accueil): ?>
        <div class="ligne-champs">
            <div style="flex:2;">
                <label for="parent_id"><?php echo te('edit.emplacement'); ?></label>
                <select id="parent_id" name="parent_id">
                    <option value=""><?php echo te('edit.menu_principal'); ?></option>
                    <?php foreach ($parents_possibles as $pp): ?>
                        <?php if ((string)$pp['id'] === (string)$page['id']) continue; ?>
                        <option value="<?php echo $pp['id']; ?>"
                            <?php echo ((string)$page['parent_id'] === (string)$pp['id']) ? 'selected' : ''; ?>>
                            <?php echo te('edit.sous_menu_de'); ?> <?php echo htmlspecialchars(strtoupper(champ_page($pp, 'titre'))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex:1;">
                <label for="ordre"><?php echo te('edit.ordre'); ?></label>
                <input type="number" id="ordre" name="ordre" value="<?php echo (int)$page['ordre']; ?>">
                <small><?php echo te('edit.ordre_aide'); ?></small>
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
                <?php echo te('edit.reserve_admin'); ?>
            </label>
        </div>
        <?php endif; ?>

        <!-- ============================================================
             ZONE D'ÉCRITURE
             Une seule barre d'outils, deux zones : celle qui est
             visible reçoit les commandes.
             ============================================================ -->
        <div>
            <label><?php echo te('edit.contenu'); ?></label>

            <div class="editeur">
                <div class="editeur-barre">
                    <div class="editeur-groupe">
                        <button type="button" data-cmd="bold"   title="<?php echo te('edit.gras'); ?>"><strong>G</strong></button>
                        <button type="button" data-cmd="italic" title="<?php echo te('edit.italique'); ?>"><em>I</em></button>
                        <button type="button" data-cmd="underline" title="<?php echo te('edit.souligne'); ?>"><u>S</u></button>
                    </div>

                    <div class="editeur-groupe">
                        <button type="button" data-bloc-html="h2" title="<?php echo te('edit.grand_titre'); ?>"><?php echo te('edit.grand_titre'); ?></button>
                        <button type="button" data-bloc-html="h3" title="<?php echo te('edit.sous_titre'); ?>"><?php echo te('edit.sous_titre'); ?></button>
                        <button type="button" data-bloc-html="p"  title="<?php echo te('edit.texte'); ?>"><?php echo te('edit.texte'); ?></button>
                    </div>

                    <div class="editeur-groupe">
                        <button type="button" data-cmd="insertUnorderedList" title="<?php echo te('edit.liste_puces'); ?>">&bull;</button>
                        <button type="button" data-cmd="insertOrderedList"  title="<?php echo te('edit.liste_num'); ?>">1.</button>
                    </div>

                    <div class="editeur-groupe">
                        <button type="button" id="btnLien" title="<?php echo te('edit.lien'); ?>"><?php echo te('edit.lien'); ?></button>
                        <button type="button" id="btnImage" title="<?php echo te('edit.image'); ?>"><?php echo te('edit.image'); ?></button>
                        <button type="button" id="btnFichier" title="<?php echo te('edit.fichier'); ?>"><?php echo te('edit.fichier'); ?></button>
                    </div>

                    <div class="editeur-groupe">
                        <button type="button" id="btnSectionAjout"><?php echo te('edit.section_plus'); ?></button>
                        <button type="button" id="btnSectionSuppr"><?php echo te('edit.section_moins'); ?></button>
                    </div>

                    <div class="editeur-groupe">
                        <button type="button" data-cmd="removeFormat" title="<?php echo te('edit.nettoyer'); ?>"><?php echo te('edit.nettoyer'); ?></button>
                        <button type="button" id="btnHtml" title="<?php echo te('edit.voir_html'); ?>">&lt;/&gt;</button>
                    </div>
                </div>

                <!-- Zone française -->
                <div class="editeur-zone bloc-langue" data-bloc="fr" id="zoneEdition"
                     contenteditable="true"><?php echo $page['contenu']; ?></div>

                <!-- Zone anglaise -->
                <div class="editeur-zone bloc-langue" data-bloc="en" id="zoneEditionEn"
                     contenteditable="true" style="display:none;"><?php echo $page['contenu_en'] ?? ''; ?></div>

                <!-- Vue code, une par langue -->
                <textarea class="editeur-html" id="zoneHtml" style="display:none;"></textarea>

                <div class="editeur-pied">
                    <span id="compteurMots">0</span>
                    <span id="messageUpload"></span>
                </div>
            </div>

            <!-- Champs réellement envoyés au serveur -->
            <textarea name="contenu"    id="contenuCache"   style="display:none;"></textarea>
            <textarea name="contenu_en" id="contenuCacheEn" style="display:none;"></textarea>

            <!-- Champs de fichier invisibles, déclenchés par les boutons -->
            <input type="file" id="inputImage" accept="image/*" style="display:none;">
            <input type="file" id="inputFichier" style="display:none;">
        </div>

        <div>
            <button type="submit" class="btn"><?php echo te('commun.sauvegarder'); ?></button>
            <a href="pages_gestion.php" class="btn btn-small" style="background:#888;"><?php echo te('commun.annuler'); ?></a>
        </div>
    </form>
</main>

<!-- Les textes affichés par le JavaScript viennent du dictionnaire PHP :
     l'éditeur parle donc lui aussi la langue de l'utilisateur. -->
<script>
window.WY_TEXTES = {
    lien_prompt:       <?php echo json_encode(t('js.lien_prompt')); ?>,
    section_ajoutee:   <?php echo json_encode(t('js.section_ajoutee')); ?>,
    section_supprimee: <?php echo json_encode(t('js.section_supprimee')); ?>,
    section_confirm:   <?php echo json_encode(t('js.section_confirm')); ?>,
    section_curseur:   <?php echo json_encode(t('js.section_curseur')); ?>,
    section_dehors:    <?php echo json_encode(t('js.section_dehors')); ?>,
    envoi:             <?php echo json_encode(t('js.envoi')); ?>,
    fichier_ajoute:    <?php echo json_encode(t('js.fichier_ajoute')); ?>,
    echec_envoi:       <?php echo json_encode(t('js.echec_envoi')); ?>,
    pas_de_reponse:    <?php echo json_encode(t('js.pas_de_reponse')); ?>,
    titre_section:     <?php echo json_encode(t('js.titre_section')); ?>,
    contenu_section:   <?php echo json_encode(t('js.contenu_section')); ?>,
    mot:               <?php echo json_encode(t('edit.mot')); ?>,
    mots:              <?php echo json_encode(t('edit.mots')); ?>
};
</script>
<script src="../script/editeur.js"></script>
<script src="../script/script.js"></script>
</body>
</html>