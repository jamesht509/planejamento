<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
include 'conexao.php';

$nome_usuario = explode('@', $_SESSION['usuario'])[0]; // Simplificado para manter consistência

$mensagem = '';
$mensagem_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'];
    $moeda = $_POST['moeda'];
    $valor = floatval($_POST['valor']);
    if ($valor > 0 && in_array($tipo, ['saldo', 'despesa']) && in_array($moeda, ['USD', 'BRL'])) {
        $stmt = $pdo->prepare("INSERT INTO financeiro (tipo, valor, moeda) VALUES (?, ?, ?)");
        if ($stmt->execute([$tipo, $valor, $moeda])) {
            $mensagem = 'Registro salvo com sucesso!';
            $mensagem_tipo = 'success';
        } else {
            $mensagem = 'Erro ao salvar.';
            $mensagem_tipo = 'danger';
        }
    } else {
        $mensagem = 'Preencha corretamente todos os campos.';
        $mensagem_tipo = 'warning';
    }
}

$dados = $pdo->query("SELECT * FROM financeiro ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Financeiro</title>
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
    .dark-mode .card, .dark-mode .form-control, .dark-mode .form-select, .dark-mode .table {
      background-color: #2c2c2c !important;
      border-color: #444 !important;
      color: #e0e0e0 !important;
    }
    .dark-mode .table-striped>tbody>tr:nth-of-type(odd) {
      background-color: rgba(255,255,255,0.05) !important;
    }
    .dark-mode .table-hover tbody tr:hover {
      background-color: rgba(255,255,255,0.1) !important;
    }
    .dark-mode .table thead {
      background-color: #333 !important;
      color: #fff !important;
    }
    .dark-mode .form-control::placeholder {
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
    .dark-mode .input-group-text {
      background-color: #333;
      border-color: #444;
      color: #e0e0e0;
    }

    .navbar-custom {
      background-color: #ffffff;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      padding: 15px 0;
    }
    .navbar-brand-custom {
      font-weight: 600;
      color: #00c875 !important;
      font-size: 1.2rem;
    }
    .theme-toggle {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: transparent;
      border: 1px solid rgba(0,0,0,0.1);
      color: inherit;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .theme-toggle:hover {
      background: rgba(0,0,0,0.05);
      transform: scale(1.05);
    }
    .dark-mode .theme-toggle {
      border-color: rgba(255,255,255,0.2);
    }
    .dark-mode .theme-toggle:hover {
      background: rgba(255,255,255,0.1);
    }
    .profile-link {
      color: inherit;
      text-decoration: none;
      font-weight: 500;
      display: flex;
      align-items: center;
      padding: 8px 12px;
      border-radius: 50px;
      background-color: rgba(0,0,0,0.05);
      transition: all 0.3s ease;
    }
    .dark-mode .profile-link {
      background-color: rgba(255,255,255,0.1);
    }
    .profile-link:hover {
      color: #00c875;
      background-color: rgba(0,0,0,0.08);
      transform: translateY(-2px);
    }
    .dark-mode .profile-link:hover {
      background-color: rgba(255,255,255,0.15);
    }

    .main-header {
      font-size: 1.8rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
      color: #333;
      position: relative;
      display: inline-block;
    }
    .main-header::after {
      content: '';
      position: absolute;
      bottom: -8px;
      left: 0;
      width: 60px;
      height: 4px;
      background-color: #00c875;
      border-radius: 2px;
    }
    .dark-mode .main-header {
        color: #e0e0e0;
    }

    .form-card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      padding: 2rem;
      transition: all 0.3s ease;
    }
    .form-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .form-label {
        font-weight: 500;
        margin-bottom: 0.5rem;
    }
    .form-control, .form-select {
        border-radius: 8px;
        padding: 0.8rem 1rem;
        border: 1px solid #ced4da;
        transition: all 0.3s ease;
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
        transition: all 0.3s ease;
    }
    .btn-primary-custom:hover {
        background-color: #00a060;
        border-color: #00a060;
        transform: translateY(-2px);
    }
    
    .input-group-text {
      border-radius: 8px 0 0 8px;
      background-color: rgba(0,0,0,0.03);
      border-color: #ced4da;
    }

    .table-container {
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      background-color: #fff;
      transition: all 0.3s ease;
    }
    .table-container:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .dark-mode .table-container {
      background-color: #2c2c2c;
    }
    
    .table {
      margin-bottom: 0;
    }
    
    .table thead th {
      font-weight: 600;
      padding: 15px 20px;
      border-bottom: 2px solid rgba(0,0,0,0.05);
    }
    
    .table tbody td {
      padding: 15px 20px;
      vertical-align: middle;
      border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    .dark-mode .table tbody td {
      border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    
    .table-striped>tbody>tr:nth-of-type(odd) {
      background-color: rgba(0,0,0,0.02);
    }
    
    .table-hover tbody tr:hover {
      background-color: rgba(0,0,0,0.05);
    }
    
    .badge {
      padding: 0.5em 0.8em;
      font-weight: 500;
      border-radius: 6px;
    }
    
    .badge-saldo {
      background-color: rgba(46, 204, 113, 0.1);
      color: #2ecc71;
    }
    
    .badge-despesa {
      background-color: rgba(231, 76, 60, 0.1);
      color: #e74c3c;
    }
    
    .dark-mode .badge-saldo {
      background-color: rgba(46, 204, 113, 0.2);
    }
    
    .dark-mode .badge-despesa {
      background-color: rgba(231, 76, 60, 0.2);
    }
    
    .valor-moeda {
      font-family: 'Inter', monospace;
      font-weight: 600;
    }
    
    .valor-usd {
      color: #3498db;
    }
    
    .valor-brl {
      color: #2ecc71;
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
      transition: all 0.3s ease;
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
    .fixed-nav a:hover {
      color: #00c875;
      transform: translateY(-2px);
    }
    
    .empty-state {
      padding: 40px 0;
      text-align: center;
      color: #888;
    }
    
    .empty-state i {
      font-size: 3rem;
      margin-bottom: 15px;
      opacity: 0.3;
    }
    
    .empty-state h5 {
      margin-bottom: 10px;
      font-weight: 600;
    }
    
    .empty-state p {
      max-width: 500px;
      margin: 0 auto;
    }
    
    @media (max-width: 768px) {
      .main-header {
        font-size: 1.5rem;
      }
      
      .form-card {
        padding: 1.5rem;
      }
      
      .table-container {
        border-radius: 0;
        box-shadow: none;
      }
      
      .table thead th, 
      .table tbody td {
        padding: 12px 15px;
      }
    }
  </style>
</head>
<body>

<nav class="navbar navbar-custom sticky-top">
  <div class="container-fluid px-3">
    <a class="navbar-brand navbar-brand-custom" href="index.php">
      <i class="fas fa-wallet me-2"></i>Financeiro
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
  <h1 class="main-header text-center mb-4">Registro Financeiro</h1>

  <?php if ($mensagem): ?>
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="alert alert-<?= htmlspecialchars($mensagem_tipo) ?> text-center" role="alert">
                <i class="fas <?= $mensagem_tipo == 'success' ? 'fa-check-circle' : ($mensagem_tipo == 'danger' ? 'fa-times-circle' : 'fa-exclamation-triangle') ?> me-2"></i>
                <?= htmlspecialchars($mensagem) ?>
            </div>
        </div>
    </div>
  <?php endif; ?>

  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
      <div class="card form-card mb-5">
        <form method="POST">
          <div class="mb-3">
            <label for="tipo" class="form-label">Tipo de Registro:</label>
            <select id="tipo" name="tipo" class="form-select" required>
              <option value="">Selecione o tipo</option>
              <option value="saldo">Saldo</option>
              <option value="despesa">Despesa</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="moeda" class="form-label">Moeda:</label>
            <select id="moeda" name="moeda" class="form-select" required>
              <option value="USD">Dólar (USD)</option>
              <option value="BRL">Real (BRL)</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="valor" class="form-label">Valor:</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
              <input type="number" step="0.01" min="0.01" name="valor" id="valor" class="form-control" placeholder="0.00" required>
            </div>
          </div>
          
          <div class="d-grid">
            <button type="submit" class="btn btn-primary-custom">
              <i class="fas fa-save me-2"></i>Salvar Registro
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <div class="row mt-5">
    <div class="col-12">
      <h2 class="text-center mb-4">
        <i class="fas fa-history me-2"></i>Registros Recentes
      </h2>
      
      <div class="table-container">
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th scope="col">Tipo</th>
                <th scope="col">Valor</th>
                <th scope="col">Moeda</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($dados) > 0): ?>
                <?php foreach ($dados as $d): ?>
                  <?php 
                    $badgeClass = 'badge-' . $d['tipo'];
                    $tipoIcon = $d['tipo'] == 'saldo' ? 'fa-arrow-up' : 'fa-arrow-down';
                    $valorClass = 'valor-' . strtolower($d['moeda']);
                  ?>
                  <tr>
                    <td>
                      <span class="badge <?= $badgeClass ?>">
                        <i class="fas <?= $tipoIcon ?> me-1"></i>
                        <?= ucfirst($d['tipo']) ?>
                      </span>
                    </td>
                    <td class="valor-moeda <?= $valorClass ?>">
                      <?= $d['moeda'] == 'USD' ? '$' : 'R$' ?> <?= number_format($d['valor'], 2, '.', ',') ?>
                    </td>
                    <td>
                      <?php if ($d['moeda'] == 'USD'): ?>
                        <i class="fas fa-dollar-sign me-1"></i> Dólar
                      <?php else: ?>
                        <i class="fas fa-money-bill-alt me-1"></i> Real
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="3">
                    <div class="empty-state">
                      <i class="fas fa-receipt"></i>
                      <h5>Nenhum registro financeiro</h5>
                      <p>Adicione seu primeiro registro financeiro usando o formulário acima.</p>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="fixed-nav">
  <a href="index.php">
    <i class="fas fa-home"></i>
    <span>Resumo</span>
  </a>
  <a href="ganhos.php">
    <i class="fas fa-hand-holding-usd"></i>
    <span>Ganhos</span>
  </a>
  <a href="nova_atividade.php">
    <i class="fas fa-plus-circle"></i>
    <span>Atividade</span>
  </a>
  <a href="listar_ganhos.php">
    <i class="fas fa-list-alt"></i>
    <span>Listar Ganhos</span>
  </a>
  <a href="financeiro.php" class="active">
    <i class="fas fa-wallet"></i>
    <span>Financeiro</span>
  </a>
  <a href="ynab_dashboard.php">
    <i class="fas fa-book-open"></i>
    <span>YNAB</span>
  </a>
</div>

<script>
  // Theme Toggle
  const themeToggleBtn = document.querySelector('.theme-toggle i');
  
  function applyTheme(isDark) {
    document.body.classList.toggle('dark-mode', isDark);
    themeToggleBtn.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
    localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
  }

  function toggleTheme() {
    const isDark = document.body.classList.contains('dark-mode');
    applyTheme(!isDark);
  }

  document.addEventListener('DOMContentLoaded', () => {
    // Verificar preferência de tema salva
    if (localStorage.getItem('darkMode') === 'enabled') {
      applyTheme(true);
    }
    
    // Atualizar link ativo na navegação fixa
    const currentPath = window.location.pathname.split('/').pop();
    document.querySelectorAll('.fixed-nav a').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
  });
</script>

</body>
</html>