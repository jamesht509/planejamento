<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

include 'conexao.php';
require_once 'GoogleAuthenticator.php';

$usuario = $_SESSION['usuario'];
$auth = new PHPGangsta_GoogleAuthenticator();

// Verifica se já tem 2FA
$stmt = $pdo->prepare("SELECT segredo_2fa FROM usuarios WHERE usuario = ?");
$stmt->execute([$usuario]);
$user = $stmt->fetch();

$segredo = $user['segredo_2fa'];

if (!$segredo) {
    // Gerar e salvar segredo se não existir
    $segredo = $auth->createSecret();
    $stmt = $pdo->prepare("UPDATE usuarios SET segredo_2fa = ? WHERE usuario = ?");
    $stmt->execute([$segredo, $usuario]);
}

$qrCodeUrl = $auth->getQRCodeGoogleUrl("ProjetoX-$usuario", $segredo, "ProjetoX");

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'];
    if ($auth->verifyCode($segredo, $codigo, 2)) {
        $mensagem = '<div class="alert alert-success">✅ Código verificado com sucesso! 2FA Ativado.</div>';
    } else {
        $mensagem = '<div class="alert alert-danger">❌ Código inválido. Tente novamente.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ativar 2FA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h3 class="text-center mb-4">🔐 Ativar Autenticação 2FA</h3>

        <?= $mensagem ?>

        <div class="text-center mb-4">
            <p>Escaneie o QR Code abaixo com seu app autenticador:</p>
            <img src="<?= $qrCodeUrl ?>" alt="QR Code">
        </div>

        <form method="POST" class="mx-auto" style="max-width: 400px;">
            <label>Digite o código de 6 dígitos:</label>
            <input type="text" name="codigo" class="form-control mb-3" required>
            <button type="submit" class="btn btn-success w-100">Verificar Código</button>
        </form>
    </div>
</body>
</html>