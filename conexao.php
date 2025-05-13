<?php
$host = 'localhost';
$db = 'u453400583_planejamento';
$user = 'u453400583_planejamento';
$pass = 'Zoe1992509';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
?>