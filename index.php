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

    <img src="images/logo-contour.png" alt="WikiYogiz" class="accueil-logo">

    <h1 class="nom-site">WIKIYOGIZ</h1>

    <p class="tagline">&ldquo;People First. Performance Follows.&rdquo;</p>

    <img src="images/Tree-pose-Cape.png" alt="Hobbi" class="image-hobbi">

    <section class="bloc-objectif">
        <h2>L'OBJECTIF DE CE WIKI</h2>
        <p>
            WikiYogiz est la mémoire partagée de Superyogiz. Il rassemble en un seul endroit
            notre ADN, nos missions, nos valeurs et nos repères de marque, pour que personne
            n'ait à chercher deux fois la même information.
        </p>
        <p>
            L'idée est simple : rendre l'essentiel accessible à tout le monde, tout de suite.
            Un nouvel arrivant y trouve de quoi comprendre où il met les pieds, et une équipe
            déjà en place y retrouve la même version des faits, le même vocabulaire, les mêmes
            règles du jeu. Chez nous, la clarté est une forme de respect.
        </p>
    </section>

    <section class="hobbi-square">
        <h2>SECTION HOBBI</h2>
        <p>
            Hobbi est notre compagnon de route : il incarne l'esprit Superyogiz au quotidien
            et guide la lecture de ce wiki.
        </p>
    </section>

</main>

<script src="script/script.js"></script>
</body>
</html>