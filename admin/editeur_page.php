<?php 
session_start(); 
require_once '../config/link_bdd_php.php'; 

// LIGNE DÉSACTIVÉE POUR TES TESTS :
// if (!isset($_SESSION['user_id'])) die("Accès refusé."); 

$page = ['id' => '', 'titre' => '', 'contenu' => '']; 
// ... garde le reste de ton code tel quel ...

// Si on modifie une page existante, on la charge depuis la base
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $page = $stmt->fetch();
}

// Si on clique sur Sauvegarder
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'];
    $contenu = $_POST['contenu'];
    // Crée une URL propre (ex: "Ma Super Page" devient "ma-super-page")
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $titre)));

    if (!empty($_POST['page_id'])) {
        $stmt = $pdo->prepare("UPDATE pages SET titre=?, contenu=?, slug=? WHERE id=?");
        $stmt->execute([$titre, $contenu, $slug, $_POST['page_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO pages (titre, contenu, slug) VALUES (?, ?, ?)");
        $stmt->execute([$titre, $contenu, $slug]);
    }
    header("Location: pages_gestion.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Éditeur de Page</title>
    <link rel="stylesheet" href="../style/style.css">
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
</head>
<body style="background-color: white;">
    <div class="menu">
        <h2 style="font-family: 'Oswald', sans-serif;">Espace Admin</h2>
        <a href="pages_gestion.php" class="menu-item" style="font-size: 1rem;">< Retour</a>
    </div>

    <main class="main-container" style="margin-left: 250px; padding: 40px; width: calc(100% - 250px);">
        <h1 style="font-family: 'Oswald', sans-serif; color: #654178;">
            <?php echo $page['id'] ? "Modifier la page" : "Créer une nouvelle page"; ?>
        </h1>
        
        <form method="POST" style="display:flex; flex-direction:column; gap: 20px; width: 100%; max-width: 900px;">
            <input type="hidden" name="page_id" value="<?php echo $page['id']; ?>">
            
            <input type="text" name="titre" placeholder="Titre de la page" value="<?php echo htmlspecialchars($page['titre']); ?>" required style="padding: 15px; font-size: 1.5rem; font-family: 'Oswald', sans-serif; border: 1px solid #ccc; border-radius: 5px;">
            
            <textarea name="contenu" id="editeur" required><?php echo htmlspecialchars($page['contenu']); ?></textarea>
            
            <button type="submit" style="background:#654178; color:white; padding:15px; font-family: 'Oswald', sans-serif; font-size: 1.2rem; cursor: pointer; border: none; border-radius: 5px;">💾 SAUVEGARDER LA PAGE</button>
        </form>
    </main>

    <script>
        // On active l'éditeur avancé avec l'option pour uploader des images
        CKEDITOR.replace('editeur', {
            filebrowserUploadUrl: 'upload.php',
            filebrowserUploadMethod: 'form',
            height: 400
        });
    </script>
</body>
</html>