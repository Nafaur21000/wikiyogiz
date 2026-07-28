<?php
// Mettre ici les accès à la base de données fournis par OVH
$host = 'wikiyoz943.mysql.db';
$dbname = 'wikiyoz943';
$username = 'wikiyoz943';
$password = 'TanKiou97';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>