<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['pre_autenticado'])) {
    header('Location: login.php');
    exit;
}

include 'conexao.php';
require_once 'GoogleAuthenticator.php';

$usuario = $_SESSION['pre_autenticado'];
$mensagem = '';

$stmt = $pdo->prepare("SELECT segredo_2fa FROM usuarios WHERE usuario = ?");
$stmt->execute([$usuario]);
$user = $stmt->fetch();

$auth = new PHPGangsta_GoogleAuthenticator();
$segredo = $user['segredo_2fa'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'];
    if ($auth->verifyCode($segredo, $codigo, 2)) {
        unset($_SESSION['pre_autenticado']);
        $_SESSION['usuario'] = $usuario;
        session_regenerate_id();
        header('Location: index.php');
        exit;
    } else {
        $mensagem = "❌ Código inválido. Tente novamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Verificação 2FA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h4 class="text-center mb-4">🔐 Verificação em Duas Etapas</h4>

        <?php if ($mensagem): ?>
            <div class="alert alert-danger text-center"><?= $mensagem ?></div>
        <?php endif; ?>

        <form method="POST" class="mx-auto" style="max-width: 400px;">
            <label for="codigo">Digite o código de 6 dígitos do seu app autenticador:</label>
            <input type="text" name="codigo" class="form-control my-3" required>
            <button type="submit" class="btn btn-success w-100">Confirmar</button>
        </form>
    </div>
</body>
</html>