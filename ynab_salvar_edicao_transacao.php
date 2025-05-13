<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $tipo = $_POST['tipo'];
    $valor = $_POST['valor'];
    $data = $_POST['data'];
    $categoria_id = $_POST['categoria_id'];
    $conta_id = $_POST['conta_id'];
    $descricao = $_POST['descricao'] ?? '';

    $stmt = $pdo->prepare("UPDATE transacoes SET tipo = ?, valor = ?, data = ?, categoria_id = ?, conta_id = ?, descricao = ? WHERE id = ?");
    $stmt->execute([$tipo, $valor, $data, $categoria_id, $conta_id, $descricao, $id]);

    header("Location: ynab_listar_transacoes.php");
    exit;
}
?>