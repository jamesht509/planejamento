<?php
session_start();
include 'conexao.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha'])) {
        if (!empty($user['segredo_2fa'])) {
            $_SESSION['pre_autenticado'] = $email;
            header('Location: verifica_2fa.php');
            exit;
        } else {
            $_SESSION['usuario'] = $email;
            session_regenerate_id();
            header('Location: index.php');
            exit;
        }
    } else {
        $mensagem = 'E-mail ou senha inválidos.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login Seguro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h3 class="text-center mb-4">🔐 Login no Sistema</h3>

        <?php if ($mensagem): ?>
            <div class="alert alert-danger text-center"><?= $mensagem ?></div>
        <?php endif; ?>

        <form method="POST" class="mx-auto" style="max-width: 400px;">
            <input type="email" name="email" placeholder="Seu e-mail" class="form-control mb-3" required>
            <input type="password" name="senha" placeholder="Sua senha" class="form-control mb-3" required>
            <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>
    </div>
</body>
</html>