<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}
include 'conexao.php';

$email = $_SESSION["usuario"];
$stmt_user = $pdo->prepare("SELECT nome FROM usuarios WHERE email = ?");
$stmt_user->execute([$email]);
$user = $stmt_user->fetch();
$nome_usuario = $user['nome'] ?? explode('@', $email)[0];

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $atividade = $_POST['atividade'];
    $valor = floatval($_POST['valor']);
    $data = $_POST['data'];

    if (empty($atividade)) {
        $mensagem = '<div class="alert alert-warning text-center"><i class="fas fa-exclamation-triangle me-2"></i>Por favor, selecione uma atividade.</div>';
    } elseif ($valor <= 0) {
        $mensagem = '<div class="alert alert-danger text-center"><i class="fas fa-times-circle me-2"></i>O valor do ganho deve ser maior que zero.</div>';
    } elseif (empty($data)) {
        $mensagem = '<div class="alert alert-warning text-center"><i class="fas fa-exclamation-triangle me-2"></i>Por favor, selecione a data do ganho.</div>';
    } else {
        $stmt = $pdo->prepare("INSERT INTO ganhos (atividade, valor_usd, data, usuario_email) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$atividade, $valor, $data, $email])) {
            $mensagem = '<div class="alert alert-success text-center"><i class="fas fa-check-circle me-2"></i>Ganho registrado com sucesso!</div>';
        } else {
            $mensagem = '<div class="alert alert-danger text-center"><i class="fas fa-times-circle me-2"></i>Erro ao salvar o registro. Tente novamente.</div>';
        }
    }
}

$atividades = $pdo->query("SELECT id, nome FROM atividades ORDER BY nome ASC")->fetchAll();
$dataAtual = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Registrar Novo Ganho</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f4f7f6;
      color: #333;
      padding-bottom: 100px; /* Space for fixed nav */
    }
    .dark-mode {
      background-color: #1a1a1a !important;
      color: #e0e0e0 !important;
    }
    .dark-mode .card, .dark-mode .form-control, .dark-mode .form-select {
      background-color: #2c2c2c !important;
      border-color: #444 !important;
      color: #e0e0e0 !important;
    }
    .dark-mode .form-control::placeholder, .dark-mode .form-select {
        color: #bbb;
    }
    .dark-mode .navbar-custom {
      background-color: #222 !important;
    }
    .dark-mode .fixed-nav {
      background-color: #2c2c2c !important;
      border-top: 1px solid #444 !important;
    }
    .dark-mode .fixed-nav a {
      color: #bbb !important;
    }
    .dark-mode .fixed-nav a.active {
      color: #00c875 !important;
    }
    .dark-mode .btn-primary-custom {
        background-color: #00c875;
        border-color: #00c875;
        color: #fff;
    }
    .dark-mode .btn-primary-custom:hover {
        background-color: #00a060;
        border-color: #00a060;
    }

    .navbar-custom {
      background-color: #ffffff;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .navbar-brand-custom {
      font-weight: 600;
      color: #00c875 !important;
    }
    .theme-toggle {
      font-size: 1.2rem;
      background: transparent;
      border: none;
      color: inherit;
      cursor: pointer;
    }
    .profile-link {
      color: inherit;
      text-decoration: none;
      font-weight: 500;
    }
    .profile-link:hover {
      color: #00c875;
    }

    .main-header {
      font-size: 1.8rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
      color: #333;
    }
    .dark-mode .main-header {
        color: #e0e0e0;
    }

    .form-card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      padding: 2rem;
    }
    .form-label {
        font-weight: 500;
        margin-bottom: 0.5rem;
    }
    .form-control, .form-select {
        border-radius: 8px;
        padding: 0.8rem 1rem;
        border: 1px solid #ced4da;
    }
    .form-control:focus, .form-select:focus {
        border-color: #00c875;
        box-shadow: 0 0 0 0.2rem rgba(0, 200, 117, 0.25);
    }
    .btn-primary-custom {
        background-color: #00c875;
        border-color: #00c875;
        padding: 0.8rem 1.5rem;
        font-weight: 600;
        border-radius: 8px;
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }
    .btn-primary-custom:hover {
        background-color: #00a060;
        border-color: #00a060;
    }

    .fixed-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      background-color: #ffffff;
      border-top: 1px solid #e0e0e0;
      display: flex;
      justify-content: space-around;
      padding: 0.5rem 0;
      box-shadow: 0 -2px 5px rgba(0,0,0,0.05);
      z-index: 1000;
    }
    .fixed-nav a {
      flex: 1;
      text-align: center;
      text-decoration: none;
      color: #666;
      font-size: 0.75rem;
      padding: 0.3rem 0;
    }
    .fixed-nav a i {
      font-size: 1.3rem;
      display: block;
      margin-bottom: 0.2rem;
    }
    .fixed-nav a.active {
      color: #00c875;
      font-weight: 600;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-custom sticky-top">
  <div class="container-fluid px-3">
    <a class="navbar-brand navbar-brand-custom" href="index.php">
      <i class="fas fa-hand-holding-usd me-2"></i>Registrar Ganho
    </a>
    <div class="d-flex align-items-center">
      <a href="perfil.php" class="profile-link me-3">
        <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($nome_usuario); ?>
      </a>
      <button class="theme-toggle" onclick="toggleTheme()" aria-label="Alternar tema">
        <i class="fas fa-moon"></i>
      </button>
    </div>
  </div>
</nav>

<div class="container mt-4 px-3">
  <h1 class="main-header text-center mb-4">Adicionar Novo Ganho</h1>

  <?php if ($mensagem): ?>
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <?= $mensagem ?>
        </div>
    </div>
  <?php endif; ?>

  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
      <div class="card form-card mb-5">
        <form method="POST">
          <div class="mb-3">
            <label for="atividade" class="form-label">Atividade:</label>
            <select name="atividade" id="atividade" class="form-select" required>
              <option value="">Selecione a atividade</option>
              <?php foreach ($atividades as $a): ?>
                <option value="<?= htmlspecialchars($a['nome']) ?>"><?= htmlspecialchars($a['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="valor" class="form-label">Valor em USD:</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                <input type="number" step="0.01" name="valor" id="valor" class="form-control" placeholder="Ex: 150.75" required>
            </div>
          </div>
          <div class="mb-4">
            <label for="data" class="form-label">Data do Ganho:</label>
            <input type="date" name="data" id="data" class="form-control" value="<?= $dataAtual ?>" max="<?= $dataAtual ?>" required>
          </div>
          <button type="submit" class="btn btn-primary-custom w-100"><i class="fas fa-save me-2"></i>Salvar Ganho</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="fixed-nav">
  <a href="index.php"><i class="fas fa-home"></i><span>Resumo</span></a>
  <a href="ganhos.php" class="active"><i class="fas fa-hand-holding-usd"></i><span>Ganhos</span></a>
  <a href="nova_atividade.php"><i class="fas fa-plus-circle"></i><span>Atividade</span></a>
  <a href="listar_ganhos.php"><i class="fas fa-list-alt"></i><span>Listar Ganhos</span></a>
  <a href="financeiro.php"><i class="fas fa-wallet"></i><span>Financeiro</span></a>
  <a href="ynab_dashboard.php"><i class="fas fa-book-open"></i><span>YNAB</span></a>
</div>

<script>
  // Theme Toggle
  const themeToggleBtn = document.querySelector('.theme-toggle i');
  function applyTheme(isDark) {
    document.body.classList.toggle('dark-mode', isDark);
    themeToggleBtn.classList.toggle('fa-sun', isDark);
    themeToggleBtn.classList.toggle('fa-moon', !isDark);
    localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
  }

  function toggleTheme() {
    const isDark = document.body.classList.contains('dark-mode');
    applyTheme(!isDark);
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('darkMode') === 'enabled') {
      applyTheme(true);
    }
    // Update active link in fixed-nav
    const currentPath = window.location.pathname.split('/').pop();
    document.querySelectorAll('.fixed-nav a').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
     // Ensure 'ganhos.php' is active if it's the current page
    if (currentPath === 'ganhos.php') {
        document.querySelector('.fixed-nav a[href="ganhos.php"]').classList.add('active');
    }
  });
</script>

</body>
</html>

