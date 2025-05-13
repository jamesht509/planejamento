<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
include 'conexao.php';

$email = $_SESSION['usuario'];

$stmt = $pdo->prepare("SELECT nome, meta_valor, meta_prazo FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();
$nome_usuario = $user['nome'] ?? explode('@', $email)[0]; // Use name or part of email
$meta_reais = $user['meta_valor'] ?? 1000000;
$prazo_meses = $user['meta_prazo'] ?? 36;

$data_inicio_meta = new DateTime('2025-06-11'); // Assuming this is a fixed start date from user profile or system setting
$data_final_meta = clone $data_inicio_meta;
$data_final_meta->modify("+" . $prazo_meses . " months");
$data_hoje = new DateTime();

$dias_totais_meta = $data_inicio_meta->diff($data_final_meta)->days;
$dias_passados_meta = $data_hoje < $data_inicio_meta ? 0 : $data_inicio_meta->diff($data_hoje)->days;
$dias_restantes_meta = $data_hoje > $data_final_meta ? 0 : $data_hoje->diff($data_final_meta)->days;
$meses_restantes_meta = $data_hoje > $data_final_meta ? 0 : floor($dias_restantes_meta / 30.4375); // Average days in month
$porcentagem_tempo_meta = $dias_totais_meta > 0 ? min(100, round(($dias_passados_meta / $dias_totais_meta) * 100, 1)) : 0;

$cotacao_dolar = 5.0; // Consider making this dynamic or configurable
$stmt_ganhos = $pdo->query("SELECT SUM(valor_usd) as total FROM ganhos");
$total_usd_ganho = $stmt_ganhos->fetch()['total'] ?? 0;
$total_reais_ganho = $total_usd_ganho * $cotacao_dolar;

$financeiro_registros = $pdo->query("SELECT * FROM financeiro")->fetchAll();
$saldo_total_financeiro_usd = 0;
$divida_total_financeiro_usd = 0;

foreach ($financeiro_registros as $f) {
    $valor_em_usd = $f['moeda'] === 'BRL' ? $f['valor'] / $cotacao_dolar : $f['valor'];
    if ($f['tipo'] === 'saldo') {
        $saldo_total_financeiro_usd += $valor_em_usd;
    }
    if ($f['tipo'] === 'despesa') {
        $divida_total_financeiro_usd += $valor_em_usd;
    }
}

$saldo_real_financeiro_usd = $saldo_total_financeiro_usd - $divida_total_financeiro_usd;
$valor_necessario_mensal_meta_reais = $prazo_meses > 0 ? $meta_reais / $prazo_meses : 0;
$valor_necessario_mensal_meta_usd = $prazo_meses > 0 ? $valor_necessario_mensal_meta_reais / $cotacao_dolar : 0;

// YNAB Style Summary (Simplified for dashboard)
$stmt_ynab_receitas = $pdo->prepare("SELECT SUM(valor) as total FROM transacoes WHERE tipo = 'receita'");
$stmt_ynab_receitas->execute();
$total_ynab_receitas = $stmt_ynab_receitas->fetch()['total'] ?? 0;

$stmt_ynab_despesas = $pdo->prepare("SELECT SUM(valor) as total FROM transacoes WHERE tipo = 'despesa'");
$stmt_ynab_despesas->execute();
$total_ynab_despesas = $stmt_ynab_despesas->fetch()['total'] ?? 0;
$saldo_ynab = $total_ynab_receitas - $total_ynab_despesas;

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Painel Financeiro 360</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <style>
    body {
      font-family: 'Inter', sans-serif; /* Using a more modern font, ensure it's available or use a common sans-serif */
      background-color: #f4f7f6; /* Lighter gray for a softer look */
      color: #333;
      padding-bottom: 100px; /* Space for fixed nav */
    }
    .dark-mode {
      background-color: #1a1a1a !important;
      color: #e0e0e0 !important;
    }
    .dark-mode .card {
      background-color: #2c2c2c !important;
      border-color: #444 !important;
      color: #e0e0e0 !important;
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
      color: #00c875 !important; /* Accent color for active link in dark mode */
    }
    .dark-mode .text-muted-custom {
        color: #aaa !important;
    }

    .navbar-custom {
      background-color: #ffffff;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .navbar-brand-custom {
      font-weight: 600;
      color: #00c875 !important; /* Accent color */
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

    .stat-card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
      height: 100%;
    }
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 16px rgba(0,0,0,0.12);
    }
    .stat-card .card-body {
      padding: 1.5rem;
    }
    .stat-card-title {
      font-size: 0.95rem;
      color: #555;
      margin-bottom: 0.5rem;
      font-weight: 500;
    }
    .dark-mode .stat-card-title {
        color: #bbb;
    }
    .stat-card-value {
      font-size: 1.75rem;
      font-weight: 700;
      margin-bottom: 0.25rem;
    }
    .stat-card-icon {
      font-size: 1.8rem;
      opacity: 0.7;
    }

    .progress-chart-container {
      position: relative;
      height: 180px; /* Adjusted height */
    }
    .progress-percentage {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 1.5rem;
      font-weight: 700;
    }

    .info-card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      padding: 1.5rem;
    }
    .info-card strong {
      color: #333;
    }
    .dark-mode .info-card strong {
        color: #e0e0e0;
    }
    .info-card p {
      margin-bottom: 0.6rem;
      font-size: 0.95rem;
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
      font-size: 0.75rem; /* Slightly smaller for a cleaner look */
      padding: 0.3rem 0;
    }
    .fixed-nav a i {
      font-size: 1.3rem; /* Larger icons */
      display: block;
      margin-bottom: 0.2rem;
    }
    .fixed-nav a.active {
      color: #00c875; /* Accent color for active link */
      font-weight: 600;
    }
    .text-muted-custom {
        color: #6c757d; /* Default Bootstrap muted color */
    }

  </style>
</head>
<body>

<nav class="navbar navbar-custom sticky-top">
  <div class="container-fluid px-3">
    <a class="navbar-brand navbar-brand-custom" href="index.php">
      <i class="fas fa-chart-pie me-2"></i>Painel 360
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

<div class="container-xl mt-4 px-3">
  <h1 class="main-header text-center mb-4">Visão Geral Financeira</h1>

  <div class="row g-4 mb-4">
    <div class="col-lg-4 col-md-6">
      <div class="card stat-card">
        <div class="card-body d-flex flex-column justify-content-between">
          <div>
            <div class="d-flex justify-content-between align-items-start">
              <h6 class="stat-card-title text-uppercase">Total de Ganhos (USD)</h6>
              <i class="fas fa-dollar-sign stat-card-icon text-success"></i>
            </div>
            <p class="stat-card-value text-success">$<?= number_format($total_usd_ganho, 2, ',', '.') ?></p>
          </div>
          <small class="text-muted-custom">Equivalente a R$ <?= number_format($total_reais_ganho, 2, ',', '.') ?></small>
        </div>
      </div>
    </div>

    <div class="col-lg-4 col-md-6">
      <div class="card stat-card">
        <div class="card-body d-flex flex-column justify-content-between">
          <div>
            <div class="d-flex justify-content-between align-items-start">
              <h6 class="stat-card-title text-uppercase">Saldo Real (Financeiro)</h6>
              <i class="fas fa-balance-scale stat-card-icon text-primary"></i>
            </div>
            <p class="stat-card-value text-<?= $saldo_real_financeiro_usd >= 0 ? 'primary' : 'danger' ?>">$<?= number_format($saldo_real_financeiro_usd, 2, ',', '.') ?></p>
          </div>
          <small class="text-muted-custom">Saldo: $<?= number_format($saldo_total_financeiro_usd, 2, ',', '.') ?> | Dívidas: $<?= number_format($divida_total_financeiro_usd, 2, ',', '.') ?></small>
        </div>
      </div>
    </div>

    <div class="col-lg-4 col-md-12">
      <div class="card stat-card">
        <div class="card-body text-center">
          <h6 class="stat-card-title text-uppercase">Progresso da Meta</h6>
          <div class="progress-chart-container mx-auto" style="max-width: 200px;">
            <canvas id="progressoMetaChart"></canvas>
            <div id="progressMetaText" class="progress-percentage text-info"><?= $porcentagem_tempo_meta ?>%</div>
          </div>
          <small class="text-muted-custom mt-2 d-block">Tempo decorrido</small>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-md-6">
      <div class="card info-card h-100">
        <h5 class="mb-3"><i class="fas fa-bullseye me-2 text-danger"></i>Detalhes da Meta</h5>
        <p><strong>Objetivo:</strong> <span class="text-danger fw-bold">R$ <?= number_format($meta_reais, 2, ',', '.') ?></span></p>
        <p><strong>Prazo Final:</strong> <?= $data_final_meta->format('d/m/Y') ?></p>
        <p><strong>Tempo Restante:</strong> <?= $dias_restantes_meta ?> dias (aprox. <?= $meses_restantes_meta ?> meses)</p>
        <p><strong>Necessário por Mês:</strong> R$ <?= number_format($valor_necessario_mensal_meta_reais, 2, ',', '.') ?> ($<?= number_format($valor_necessario_mensal_meta_usd, 2, ',', '.') ?>)</p>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card info-card h-100">
        <h5 class="mb-3"><i class="fas fa-book me-2 text-warning"></i>Resumo YNAB (Simplificado)</h5>
        <p><strong>Total de Receitas:</strong> <span class="text-success fw-bold">R$ <?= number_format($total_ynab_receitas, 2, ',', '.') ?></span></p>
        <p><strong>Total de Despesas:</strong> <span class="text-danger fw-bold">R$ <?= number_format($total_ynab_despesas, 2, ',', '.') ?></span></p>
        <hr class="my-2">
        <p><strong>Saldo YNAB:</strong> <span class="fw-bold text-<?= $saldo_ynab >= 0 ? 'success' : 'danger' ?>">R$ <?= number_format($saldo_ynab, 2, ',', '.') ?></span></p>
      </div>
    </div>
  </div>

</div>

<div class="fixed-nav">
  <a href="index.php" class="active"><i class="fas fa-home"></i><span>Resumo</span></a>
  <a href="ganhos.php"><i class="fas fa-hand-holding-usd"></i><span>Ganhos</span></a>
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
    // Update chart colors if they exist
    if (typeof progressoMetaChart !== 'undefined' && progressoMetaChart) {
        progressoMetaChart.options.plugins.legend.labels.color = isDark ? '#e0e0e0' : '#333';
        progressoMetaChart.data.datasets[0].backgroundColor = [isDark ? '#00a060' : '#00c875', isDark ? '#444' : '#dee2e6'];
        progressoMetaChart.update();
    }
  }

  function toggleTheme() {
    const isDark = document.body.classList.contains('dark-mode');
    applyTheme(!isDark);
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('darkMode') === 'enabled') {
      applyTheme(true);
    }

    // Progress Chart
    const ctxProgressoMeta = document.getElementById('progressoMetaChart')?.getContext('2d');
    if (ctxProgressoMeta) {
      const isDark = document.body.classList.contains('dark-mode');
      window.progressoMetaChart = new Chart(ctxProgressoMeta, {
        type: 'doughnut',
        data: {
          labels: ['Concluído', 'Faltando'],
          datasets: [{
            data: [<?= $porcentagem_tempo_meta ?>, <?= 100 - $porcentagem_tempo_meta ?>],
            backgroundColor: [isDark ? '#00a060' : '#00c875', isDark ? '#444' : '#dee2e6'],
            borderColor: isDark ? '#2c2c2c' : '#f4f7f6', // Match card/body background
            borderWidth: 3,
            hoverOffset: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '75%',
          plugins: {
            legend: { display: false },
            tooltip: { enabled: true }
          },
          animation: {
            animateScale: true,
            animateRotate: true
          }
        }
      });
    }
    // Update active link in fixed-nav
    const currentPath = window.location.pathname.split('/').pop();
    document.querySelectorAll('.fixed-nav a').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
    if (currentPath === 'index.php' || currentPath === '') {
        document.querySelector('.fixed-nav a[href="index.php"]').classList.add('active');
    }

  });
</script>

</body>
</html>

