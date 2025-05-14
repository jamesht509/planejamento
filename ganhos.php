<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
include 'conexao.php';

$email = $_SESSION['usuario'];

// Recuperar o nome do usuário
$stmt_user = $pdo->prepare("SELECT nome FROM usuarios WHERE email = ?");
$stmt_user->execute([$email]);
$user = $stmt_user->fetch();
$nome_usuario = $user["nome"] ?? explode('@', $email)[0];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $atividade = trim($_POST["atividade"]);
    $valor_usd = floatval(str_replace([',', 'R$', '$'], ['.', '', ''], $_POST["valor_usd"]));
    $data = $_POST["data"];

    if (!empty($atividade) && $valor_usd > 0 && !empty($data)) {
        $stmt = $pdo->prepare("INSERT INTO ganhos (atividade, valor_usd, data, email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$atividade, $valor_usd, $data, $email]);
        header("Location: listar_ganhos.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Registrar Ganho</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #00c875;
      --bg-color: #f4f7f6;
      --card-color: #fff;
      --text-color: #333;
      --muted-color: #666;
    }
    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-color);
      color: var(--text-color);
      padding-bottom: 100px;
    }
    .dark-mode {
      --bg-color: #1a1a1a;
      --card-color: #2c2c2c;
      --text-color: #e0e0e0;
      --muted-color: #aaa;
    }
    .navbar-custom {
      background-color: var(--card-color);
      box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }
    .navbar-brand-custom {
      font-weight: 700;
      color: var(--primary-color) !important;
    }
    .theme-toggle {
      background: transparent;
      border: none;
      font-size: 1.2rem;
      color: var(--text-color);
    }
    .form-card {
      background-color: var(--card-color);
      border-radius: 16px;
      padding: 2rem;
      box-shadow: 0 4px 20px rgba(0,0,0,0.05);
      max-width: 600px;
      margin: 2rem auto;
    }
    label {
      font-weight: 600;
      margin-bottom: 0.3rem;
    }
    .btn-primary {
      background-color: var(--primary-color);
      border: none;
    }
    .btn-primary:hover {
      background-color: #00b46c;
    }
    .fixed-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      background-color: var(--card-color);
      border-top: 1px solid #ccc;
      display: flex;
      justify-content: space-around;
      padding: 0.7rem 0;
      box-shadow: 0 -2px 8px rgba(0,0,0,0.1);
    }
    .fixed-nav a {
      text-align: center;
      color: var(--muted-color);
      text-decoration: none;
      font-size: 0.8rem;
    }
    .fixed-nav a i {
      font-size: 1.3rem;
      display: block;
    }
    .fixed-nav a.active {
      color: var(--primary-color);
      font-weight: 600;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-custom sticky-top">
  <div class="container-fluid px-3 py-2 d-flex justify-content-between align-items-center">
    <a class="navbar-brand navbar-brand-custom" href="index.php"><i class="fas fa-chart-line me-2"></i>Painel 360</a>
    <div class="d-flex align-items-center">
      <a href="perfil.php" class="me-3 text-decoration-none text-dark">
        <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($nome_usuario); ?>
      </a>
      <button class="theme-toggle" onclick="toggleTheme()"><i class="fas fa-moon"></i></button>
    </div>
  </div>
</nav>

<div class="container">
  <div class="form-card">
    <h4 class="mb-4 text-center"><i class="fas fa-hand-holding-usd me-2"></i>Registrar Ganho</h4>
    <form method="POST" action="">
      <div class="mb-3">
        <label for="atividade">Atividade</label>
        <input type="text" class="form-control" id="atividade" name="atividade" required>
      </div>
      <div class="mb-3">
        <label for="valor_usd">Valor (USD)</label>
        <input type="number" step="0.01" class="form-control" id="valor_usd" name="valor_usd" inputmode="decimal" pattern="[\d,\.]+" required>
      </div>
      <div class="mb-3">
        <label for="data">Data</label>
        <input type="date" class="form-control" id="data" name="data" required>
      </div>
      <div class="d-grid">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Salvar Ganho</button>
      </div>
    </form>
  </div>
</div>

<div class="fixed-nav">
  <a href="index.php"><i class="fas fa-home"></i><span>Resumo</span></a>
  <a href="ganhos.php" class="active"><i class="fas fa-hand-holding-usd"></i><span>Ganhos</span></a>
  <a href="nova_atividade.php"><i class="fas fa-plus-circle"></i><span>Atividade</span></a>
  <a href="listar_ganhos.php"><i class="fas fa-list-alt"></i><span>Listar</span></a>
  <a href="financeiro.php"><i class="fas fa-wallet"></i><span>Financeiro</span></a>
  <a href="ynab_dashboard.php"><i class="fas fa-book-open"></i><span>YNAB</span></a>
</div>

<script>
function toggleTheme() {
  const isDark = document.body.classList.toggle('dark-mode');
  const icon = document.querySelector('.theme-toggle i');
  icon.classList.toggle('fa-moon', !isDark);
  icon.classList.toggle('fa-sun', isDark);
  localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
}
document.addEventListener('DOMContentLoaded', () => {
  if (localStorage.getItem('darkMode') === 'enabled') {
    document.body.classList.add('dark-mode');
    document.querySelector('.theme-toggle i').classList.replace('fa-moon', 'fa-sun');
  }
});
</script>

</body>
</html>