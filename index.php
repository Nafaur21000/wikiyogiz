<?php
session_start();
require_once 'config/link_bdd_php.php';

$base = '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WikiYogiz - Accueil</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>

<?php include 'includes/menu.php'; ?>

<main class="contenu-central" id="mainContent">


    <h1 class="nom-site">WIKIYOGIZ</h1>

    <p class="tagline">&ldquo;People First. Performance Follows.&rdquo;</p>

    <img src="images/Tree-pose-Cape.png" alt="Hobbi" class="image-hobbi">

    <section class="bloc-objectif">
        <h2>At Superyogiz, we believe clarity is a form of respect.</h2>
        <p>
           WikiYogiz exists so no one wastes time searching for answers that already exist — so new collaborators move through onboarding with confidence, and every team member stays aligned with the same vision, the same language, the same system.
        </p>
        <p>
            This is not just documentation. It's how we scale without losing our soul.
        </p>
    </section>

   

</main>

<script src="script/script.js"></script>
</body>
</html>