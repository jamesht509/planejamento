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
$nome_usuario = $user["nome"] ?? explode('@', $email)[0];

// Adicionar filtro por usuário
$stmt_ganhos = $pdo->prepare("SELECT * FROM ganhos WHERE usuario_email = ? ORDER BY data DESC");
$stmt_ganhos->execute([$email]);
$ganhos = $stmt_ganhos->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Meus Ganhos Registrados</title>
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
    .dark-mode .card, .dark-mode .table {
      background-color: #2c2c2c !important;
      border-color: #444 !important;
      color: #e0e0e0 !important;
    }
    .dark-mode .table th, .dark-mode .table td {
        border-color: #444 !important;
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
    .dark-mode .btn-outline-primary-custom {
        color: #00c875;
        border-color: #00c875;
    }
    .dark-mode .btn-outline-primary-custom:hover {
        background-color: #00c875;
        color: #1a1a1a;
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

    .table-container {
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        overflow: hidden; /* Ensures border-radius is applied to table */
    }
    .table {
        margin-bottom: 0; /* Remove default bottom margin from Bootstrap table */
    }
    .table th {
        background-color: #e9ecef; /* Light grey for table header */
        font-weight: 600;
        color: #495057;
        border-bottom-width: 1px;
    }
    .dark-mode .table th {
        background-color: #343a40; /* Darker grey for table header in dark mode */
        color: #e0e0e0;
    }
    .table td {
        vertical-align: middle;
    }
    .table-hover tbody tr:hover {
        background-color: #f8f9fa; /* Lighter hover for light mode */
    }
    .dark-mode .table-hover tbody tr:hover {
        background-color: #383838; /* Darker hover for dark mode */
    }
    .btn-edit, .btn-delete {
        padding: 0.3rem 0.6rem;
        font-size: 0.8rem;
    }
    .btn-edit {
        color: #007bff;
    }
    .btn-edit:hover {
        color: #0056b3;
    }
    .btn-delete {
        color: #dc3545;
    }
    .btn-delete:hover {
        color: #c82333;
    }
    .dark-mode .btn-edit {
        color: #34a4eb;
    }
    .dark-mode .btn-edit:hover {
        color: #6bc3f0;
    }
    .dark-mode .btn-delete {
        color: #f16272;
    }
    .dark-mode .btn-delete:hover {
        color: #f58b97;
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
      <i class="fas fa-list-alt me-2"></i>Meus Ganhos
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
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
    <h1 class="main-header mb-0">Histórico de Ganhos</h1>
    <a href="ganhos.php" class="btn btn-outline-primary-custom">
        <i class="fas fa-plus me-2"></i>Registrar Novo Ganho
    </a>
  </div>

  <?php if (empty($ganhos)): ?>
    <div class="alert alert-info text-center" role="alert">
        <i class="fas fa-info-circle me-2"></i>Nenhum ganho registrado ainda. <a href="ganhos.php" class="alert-link">Clique aqui</a> para adicionar seu primeiro ganho!
    </div>
  <?php else: ?>
    <div class="table-container card">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th scope="col">Data</th>
              <th scope="col">Atividade</th>
              <th scope="col" class="text-end">Valor (USD)</th>
              <th scope="col" class="text-center">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ganhos as $g): ?>
            <tr>
              <td><?= date('d/m/Y', strtotime($g['data'])) ?></td>
              <td><?= htmlspecialchars($g['atividade']) ?></td>
              <td class="text-end fw-bold text-success">$<?= number_format($g['valor_usd'], 2, ',', '.') ?></td>
              <td class="text-center">
                <a href="editar_ganho.php?id=<?= $g['id'] ?>" class="btn btn-link btn-edit p-1" title="Editar">
                    <i class="fas fa-edit"></i>
                </a>
                <a href="excluir_ganho.php?id=<?= $g['id'] ?>" class="btn btn-link btn-delete p-1" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este ganho?');">
                    <i class="fas fa-trash-alt"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<div class="fixed-nav">
  <a href="index.php"><i class="fas fa-home"></i><span>Resumo</span></a>
  <a href="ganhos.php"><i class="fas fa-hand-holding-usd"></i><span>Ganhos</span></a>
  <a href="nova_atividade.php"><i class="fas fa-plus-circle"></i><span>Atividade</span></a>
  <a href="listar_ganhos.php" class="active"><i class="fas fa-list-alt"></i><span>Listar Ganhos</span></a>
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
    if (currentPath === 'listar_ganhos.php') {
        document.querySelector('.fixed-nav a[href="listar_ganhos.php"]').classList.add('active');
    }
  });
</script>

</body>
</html>

