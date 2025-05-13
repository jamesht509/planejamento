<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
include 'conexao.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM ganhos WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: listar_ganhos.php");
exit;
?>