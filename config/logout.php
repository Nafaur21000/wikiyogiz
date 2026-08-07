<?php
session_start();

// On vide toutes les variables de session
$_SESSION = array();

// On détruit la session
session_destroy();

// On redirige vers l'index à la racine du site
header('Location: /index.php');
exit();