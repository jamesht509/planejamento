<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
include 'conexao.php';

// Processar adição de categoria
if (isset($_POST['adicionar'])) {
  $stmt = $pdo->prepare("INSERT INTO categorias (nome, tipo) VALUES (?, ?)");
  $stmt->execute([$_POST['nome'], $_POST['tipo']]);
  header("Location: ynab_categorias.php");
  exit;
}

// Processar exclusão de categoria
if (isset($_GET['excluir'])) {
  $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
  $stmt->execute([$_GET['excluir']]);
  header("Location: ynab_categorias.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Categorias | YNAB</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #3498db;
      --secondary-color: #2ecc71;
      --warning-color: #f39c12;
      --danger-color: #e74c3c;
      --dark-bg: #121212;
      --dark-card: #1e1e1e;
      --light-bg: #f8f9fa;
      --light-text: #f1f1f1;
      --dark-text: #333;
      --border-radius: 12px;
      --box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      --transition: all 0.3s ease;
      --table-hover-bg: rgba(0,0,0,0.03);
      --table-header-bg: #f8f9fa;
    }
    
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background-color: var(--light-bg);
      color: var(--dark-text);
      padding-bottom: 80px;
      transition: var(--transition);
    }
    
    .dark-mode {
      background-color: var(--dark-bg);
      color: var(--light-text);
    }
    
    .dark-mode .card, 
    .dark-mode .navbar,
    .dark-mode .fixed-nav {
      background-color: var(--dark-card) !important;
      color: var(--light-text) !important;
      border-color: #333 !important;
    }
    
    .dark-mode .text-muted {
      color: #aaa !important;
    }
    
    .dark-mode .table {
      color: var(--light-text) !important;
      border-color: #333 !important;
    }
    
    .dark-mode .table thead {
      background-color: rgba(255,255,255,0.05) !important;
      color: #fff !important;
    }
    
    .dark-mode .table-hover tbody tr:hover {
      background-color: rgba(255,255,255,0.05);
    }
    
    .dark-mode .form-control,
    .dark-mode .form-select {
      background-color: #2c2c2c;
      border-color: #444;
      color: #fff;
    }
    
    .dark-mode .form-control:focus,
    .dark-mode .form-select:focus {
      background-color: #333;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
    }
    
    .navbar {
      box-shadow: var(--box-shadow);
      transition: var(--transition);
      padding: 15px 0;
    }
    
    .navbar-brand {
      font-weight: 700;
      font-size: 1.4rem;
      display: flex;
      align-items: center;
    }
    
    .navbar-brand i {
      font-size: 1.2rem;
      margin-right: 8px;
    }
    
    .theme-toggle {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: transparent;
      border: 1px solid rgba(255,255,255,0.2);
      color: inherit;
      cursor: pointer;
      transition: var(--transition);
    }
    
    .theme-toggle:hover {
      background: rgba(255,255,255,0.1);
      transform: scale(1.05);
    }
    
    .fixed-nav {
      position: fixed;
      bottom: 0;
      width: 100%;
      background: #fff;
      border-top: 1px solid #ddd;
      display: flex;
      justify-content: space-around;
      padding: 8px 0;
      z-index: 9999;
      box-shadow: 0 -4px 10px rgba(0,0,0,0.05);
      transition: var(--transition);
    }
    
    .fixed-nav a {
      flex: 1;
      text-align: center;
      text-decoration: none;
      color: #555;
      font-size: 14px;
      padding: 8px 0;
      transition: var(--transition);
      border-bottom: 2px solid transparent;
    }
    
    .fixed-nav a div {
      display: flex;
      flex-direction: column;
      align-items: center;
      font-weight: 500;
    }
    
    .fixed-nav a span {
      font-size: 12px;
      margin-top: 4px;
    }
    
    .fixed-nav a:hover {
      color: var(--primary-color);
      transform: translateY(-2px);
    }
    
    .fixed-nav a.active {
      color: var(--primary-color);
      border-bottom: 2px solid var(--primary-color);
    }
    
    .page-header {
      font-size: 1.8rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
      position: relative;
      display: inline-block;
    }
    
    .page-header::after {
      content: '';
      position: absolute;
      bottom: -8px;
      left: 0;
      width: 60px;
      height: 4px;
      background-color: var(--primary-color);
      border-radius: 2px;
    }
    
    .form-card {
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      border: none;
      transition: var(--transition);
      background-color: #fff;
      overflow: hidden;
      margin-bottom: 24px;
    }
    
    .dark-mode .form-card {
      background-color: var(--dark-card);
    }
    
    .form-card-header {
      background-color: rgba(0,0,0,0.02);
      padding: 16px 20px;
      border-bottom: 1px solid rgba(0,0,0,0.05);
      font-weight: 600;
      font-size: 1.1rem;
    }
    
    .dark-mode .form-card-header {
      background-color: rgba(255,255,255,0.05);
      border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    
    .form-card-body {
      padding: 20px;
      position: relative;
    }
    
    .form-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    
    .form-control, .form-select {
      padding: 0.7rem 1rem;
      border-radius: 8px;
      transition: var(--transition);
      border: 1px solid #dee2e6;
      font-size: 1rem;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
    }
    
    .btn {
      padding: 0.7rem 1.5rem;
      border-radius: 8px;
      font-weight: 500;
      transition: var(--transition);
      font-size: 1rem;
    }
    
    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .btn-primary {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
    }
    
    .btn-danger {
      background-color: var(--danger-color);
      border-color: var(--danger-color);
    }
    
    .table-container {
      border-radius: var(--border-radius);
      overflow: hidden;
      box-shadow: var(--box-shadow);
      background-color: #fff;
      transition: var(--transition);
    }
    
    .table-container:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    
    .dark-mode .table-container {
      background-color: var(--dark-card);
    }
    
    table {
      border-collapse: separate;
      border-spacing: 0;
      width: 100%;
      margin-bottom: 0 !important;
    }
    
    .table thead {
      background-color: var(--table-header-bg);
    }
    
    .table thead th {
      font-weight: 600;
      font-size: 0.9rem;
      padding: 15px 20px;
      border-bottom: 2px solid rgba(0,0,0,0.05);
    }
    
    .table tbody td {
      padding: 15px 20px;
      vertical-align: middle;
      border-bottom: 1px solid rgba(0,0,0,0.05);
      transition: var(--transition);
    }
    
    .dark-mode .table tbody td {
      border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    
    .table-hover tbody tr {
      transition: var(--transition);
    }
    
    .table-hover tbody tr:hover {
      background-color: var(--table-hover-bg);
    }
    
    .badge {
      padding: 0.5em 0.8em;
      font-weight: 500;
      border-radius: 6px;
    }
    
    .badge-receita {
      background-color: rgba(46, 204, 113, 0.1);
      color: var(--secondary-color);
    }
    
    .badge-despesa {
      background-color: rgba(231, 76, 60, 0.1);
      color: var(--danger-color);
    }
    
    .dark-mode .badge-receita {
      background-color: rgba(46, 204, 113, 0.2);
    }
    
    .dark-mode .badge-despesa {
      background-color: rgba(231, 76, 60, 0.2);
    }
    
    .btn-action {
      width: 36px;
      height: 36px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      transition: var(--transition);
      padding: 0;
    }
    
    .btn-action:hover {
      transform: translateY(-2px);
    }
    
    .empty-table {
      padding: 40px 0;
      text-align: center;
      color: #888;
    }
    
    .empty-table i {
      font-size: 3rem;
      margin-bottom: 15px;
      opacity: 0.3;
    }
    
    .empty-table h5 {
      margin-bottom: 10px;
      font-weight: 600;
    }
    
    .empty-table p {
      max-width: 500px;
      margin: 0 auto;
    }
    
    @media (max-width: 768px) {
      .page-header {
        font-size: 1.5rem;
      }
      
      .form-card-body {
        padding: 16px;
      }
      
      .table-container {
        border-radius: 0;
        box-shadow: none;
      }
      
      .table thead th {
        padding: 12px 15px;
      }
      
      .table tbody td {
        padding: 12px 15px;
      }
      
      .btn-action {
        width: 32px;
        height: 32px;
      }
    }
  </style>
</head>
<body>

<nav class="navbar navbar-dark bg-primary mb-4">
  <div class="container-fluid px-4 d-flex justify-content-between align-items-center">
    <a class="navbar-brand d-flex align-items-center" href="#">
      <i class="fa-solid fa-money-bill-wave me-2"></i>
      <span class="fw-bold">Finance Dashboard</span>
    </a>
    <div class="d-flex align-items-center">
      <a href="perfil.php" class="text-light text-decoration-none me-3">
        <i class="fa-solid fa-user-circle me-1"></i>
        <span class="d-none d-sm-inline"><?= $_SESSION['usuario']; ?></span>
      </a>
      <button class="theme-toggle" onclick="toggleTheme()">
        <i class="fa-solid fa-moon"></i>
      </button>
    </div>
  </div>
</nav>

<div class="container-xl px-4 pb-5">
  <div class="mb-4 text-center">
    <h4 class="page-header">
      <i class="fa-solid fa-tag me-2"></i>Categorias
    </h4>
  </div>

  <div class="row">
    <div class="col-lg-6 mx-auto">
      <div class="form-card mb-4">
        <div class="form-card-header">
          <i class="fa-solid fa-plus-circle me-2"></i>Nova Categoria
        </div>
        <div class="form-card-body">
          <form action="" method="POST" class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nome da Categoria</label>
              <input type="text" name="nome" class="form-control" placeholder="Ex: Alimentação, Transporte..." required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Tipo</label>
              <select name="tipo" class="form-select">
                <option value="despesa">Despesa</option>
                <option value="receita">Receita</option>
              </select>
            </div>
            <div class="col-12 mt-2">
              <button type="submit" name="adicionar" class="btn btn-primary">
                <i class="fa-solid fa-plus me-2"></i>Adicionar Categoria
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="table-container">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Tipo</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $stmt = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC");
          $categorias = $stmt->fetchAll();
          
          if (count($categorias) > 0) {
            foreach ($categorias as $cat) {
              $badgeClass = 'badge-' . $cat['tipo'];
              $tipoText = ($cat['tipo'] == 'receita') ? 'Receita' : 'Despesa';
              $iconClass = ($cat['tipo'] == 'receita') ? 'fa-arrow-up' : 'fa-arrow-down';
              
              echo "<tr>
                      <td>{$cat['nome']}</td>
                      <td><span class='badge {$badgeClass}'><i class='fa-solid {$iconClass} me-1'></i>{$tipoText}</span></td>
                      <td>
                        <a href='?excluir={$cat['id']}' onclick='return confirm(\"Tem certeza que deseja excluir esta categoria? Esta ação não poderá ser desfeita.\")' class='btn btn-action btn-danger' title='Excluir'>
                          <i class='fa-solid fa-trash'></i>
                        </a>
                      </td>
                    </tr>";
            }
          } else {
            echo "<tr>
                    <td colspan='3'>
                      <div class='empty-table'>
                        <i class='fa-solid fa-folder-open'></i>
                        <h5>Nenhuma categoria cadastrada</h5>
                        <p>Adicione categorias para organizar suas transações financeiras.</p>
                      </div>
                    </td>
                  </tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="fixed-nav">
  <a href="ynab_dashboard.php">
    <div>
      <i class="fa-solid fa-chart-line"></i>
      <span>Dashboard</span>
    </div>
  </a>
  <a href="ynab_nova_transacao.php">
    <div>
      <i class="fa-solid fa-plus"></i>
      <span>Nova</span>
    </div>
  </a>
  <a href="ynab_listar_transacoes.php">
    <div>
      <i class="fa-solid fa-list"></i>
      <span>Transações</span>
    </div>
  </a>
  <a href="ynab_categorias.php" class="active">
    <div>
      <i class="fa-solid fa-tag"></i>
      <span>Categorias</span>
    </div>
  </a>
  <a href="ynab_contas.php">
    <div>
      <i class="fa-solid fa-university"></i>
      <span>Contas</span>
    </div>
  </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle do tema escuro
function toggleTheme() {
  const body = document.body;
  body.classList.toggle("dark-mode");
  const isDark = body.classList.contains("dark-mode");
  localStorage.setItem("modo_escuro", isDark);
  
  const themeIcon = document.querySelector(".theme-toggle i");
  themeIcon.className = isDark ? "fa-solid fa-sun" : "fa-solid fa-moon";
}

// Verificar preferência de tema no carregamento da página
document.addEventListener("DOMContentLoaded", () => {
  const isDark = localStorage.getItem("modo_escuro") === "true";
  if (isDark) {
    document.body.classList.add("dark-mode");
    document.querySelector(".theme-toggle i").className = "fa-solid fa-sun";
  }
});
</script>

</body>
</html>