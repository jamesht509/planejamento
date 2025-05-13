<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
include 'conexao.php';

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM transacoes WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: ynab_listar_transacoes.php");
exit;
?>