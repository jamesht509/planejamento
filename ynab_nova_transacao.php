<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
include 'conexao.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Nova Transação | YNAB</title>
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
      position: relative;
    }
    
    .dark-mode .form-card {
      background-color: var(--dark-card);
    }
    
    .form-card::before {
      content: '';
      position: absolute;
      top: -50px;
      right: -50px;
      width: 200px;
      height: 200px;
      background: radial-gradient(circle, rgba(52, 152, 219, 0.05) 0%, rgba(52, 152, 219, 0) 70%);
      border-radius: 50%;
      z-index: 0;
    }
    
    .dark-mode .form-card::before {
      background: radial-gradient(circle, rgba(255, 255, 255, 0.03) 0%, rgba(255, 255, 255, 0) 70%);
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
      padding: 24px;
      position: relative;
      z-index: 1;
    }
    
    .form-label {
      font-weight: 500;
      margin-bottom: 0.5rem;
      font-size: 0.9rem;
    }
    
    .form-control, .form-select {
      padding: 0.6rem 1rem;
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
      padding: 0.8rem 1.5rem;
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
    
    .btn-outline-secondary {
      color: #6c757d;
      border-color: #6c757d;
    }
    
    .btn-outline-secondary:hover {
      color: #fff;
      background-color: #6c757d;
    }
    
    .input-group-text {
      border-radius: 8px 0 0 8px;
      background-color: rgba(0,0,0,0.03);
      border-color: #dee2e6;
    }
    
    .dark-mode .input-group-text {
      background-color: rgba(255,255,255,0.1);
      border-color: #444;
      color: #fff;
    }
    
    .form-floating label {
      padding-left: 1rem;
    }
    
    .form-floating .form-control {
      height: calc(3.5rem + 2px);
      padding: 1rem 0.75rem;
    }
    
    .form-floating textarea.form-control {
      height: auto;
      min-height: 100px;
    }
    
    .tipo-button {
      display: none;
    }
    
    .tipo-label {
      display: block;
      border-radius: 8px;
      border: 1px solid #dee2e6;
      padding: 12px;
      text-align: center;
      cursor: pointer;
      transition: var(--transition);
      position: relative;
      overflow: hidden;
    }
    
    .dark-mode .tipo-label {
      border-color: #444;
    }
    
    .tipo-label i {
      font-size: 1.5rem;
      margin-bottom: 8px;
      display: block;
    }
    
    .tipo-button:checked + .tipo-label {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 1px var(--primary-color);
    }
    
    .tipo-receita {
      color: var(--secondary-color);
    }
    
    .tipo-despesa {
      color: var(--danger-color);
    }
    
    .tipo-transferencia {
      color: var(--primary-color);
    }
    
    .tipo-button:checked + .tipo-label::before {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      border-width: 0 24px 24px 0;
      border-style: solid;
      border-color: var(--primary-color) transparent;
    }
    
    .tipo-button:checked + .tipo-label::after {
      content: '✓';
      position: absolute;
      top: 2px;
      right: 5px;
      font-size: 9px;
      color: white;
    }
    
    @media (max-width: 768px) {
      .page-header {
        font-size: 1.5rem;
      }
      
      .form-card-body {
        padding: 16px;
      }
      
      .btn {
        padding: 0.6rem 1.2rem;
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
      <i class="fa-solid fa-plus me-2"></i>Nova Transação
    </h4>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="form-card">
        <div class="form-card-header">
          <i class="fa-solid fa-pen-to-square me-2"></i>Preencha os dados da transação
        </div>
        <div class="form-card-body">
          <form action="ynab_salvar_transacao.php" method="POST" class="row g-3">
            <!-- Tipo de Transação -->
            <div class="col-12 mb-2">
              <label class="form-label">Tipo de Transação</label>
              <div class="row g-2">
                <div class="col-md-4">
                  <input type="radio" name="tipo" value="receita" id="tipo-receita" class="tipo-button" checked>
                  <label for="tipo-receita" class="tipo-label tipo-receita">
                    <i class="fa-solid fa-arrow-up"></i>
                    <span>Receita</span>
                  </label>
                </div>
                <div class="col-md-4">
                  <input type="radio" name="tipo" value="despesa" id="tipo-despesa" class="tipo-button">
                  <label for="tipo-despesa" class="tipo-label tipo-despesa">
                    <i class="fa-solid fa-arrow-down"></i>
                    <span>Despesa</span>
                  </label>
                </div>
                <div class="col-md-4">
                  <input type="radio" name="tipo" value="transferencia" id="tipo-transferencia" class="tipo-button">
                  <label for="tipo-transferencia" class="tipo-label tipo-transferencia">
                    <i class="fa-solid fa-exchange-alt"></i>
                    <span>Transferência</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Valor e Data -->
            <div class="col-md-6">
              <label class="form-label">Valor</label>
              <div class="input-group">
                <span class="input-group-text">R$</span>
                <input type="number" step="0.01" name="valor" class="form-control" required placeholder="0,00">
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Data</label>
              <input type="date" name="data" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>

            <!-- Categoria e Conta -->
            <div class="col-md-6">
              <label class="form-label">Categoria</label>
              <select name="categoria_id" class="form-select" required>
                <option value="">Selecione uma categoria</option>
                <?php
                $res = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC");
                foreach ($res as $row) {
                  echo "<option value='{$row['id']}'>{$row['nome']}</option>";
                }
                ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Conta</label>
              <select name="conta_id" class="form-select" required>
                <option value="">Selecione uma conta</option>
                <?php
                $res = $pdo->query("SELECT * FROM contas ORDER BY nome ASC");
                foreach ($res as $row) {
                  echo "<option value='{$row['id']}'>{$row['nome']}</option>";
                }
                ?>
              </select>
            </div>

            <!-- Descrição -->
            <div class="col-12">
              <label class="form-label">Descrição (opcional)</label>
              <textarea name="descricao" rows="3" class="form-control" placeholder="Adicione detalhes sobre esta transação"></textarea>
            </div>

            <!-- Botões de Ação -->
            <div class="col-12 mt-4">
              <div class="d-grid gap-2 d-md-flex">
                <button type="submit" class="btn btn-primary flex-grow-1">
                  <i class="fa-solid fa-save me-2"></i>Salvar Transação
                </button>
                <a href="ynab_listar_transacoes.php" class="btn btn-outline-secondary">
                  <i class="fa-solid fa-times me-2"></i>Cancelar
                </a>
              </div>
            </div>
          </form>
        </div>
      </div>
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
  <a href="ynab_nova_transacao.php" class="active">
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
  <a href="ynab_categorias.php">
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
  
  // Definir data atual como padrão se o campo estiver vazio
  const dataField = document.querySelector('input[name="data"]');
  if (!dataField.value) {
    dataField.value = new Date().toISOString().substring(0, 10);
  }
});
</script>

</body>
</html>