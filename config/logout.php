<?php
// ============================================================
//  DÉCONNEXION
//  On vide la session, puis on renvoie vers la page de connexion
//  (et non vers l'accueil : le wiki est privé).
// ============================================================
session_start();
$_SESSION = [];
session_unset();
session_destroy();

header('Location: login.php');
exit;