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
$nome_usuario = $user['nome'] ?? explode('@', $email)[0];
$meta_reais = $user['meta_valor'] ?? 1000000;
$prazo_meses = $user['meta_prazo'] ?? 36;

$data_inicio_meta = new DateTime('2025-06-11');
$data_final_meta = clone $data_inicio_meta;
$data_final_meta->modify("+" . $prazo_meses . " months");
$data_hoje = new DateTime();

$dias_totais_meta = $data_inicio_meta->diff($data_final_meta)->days;
$dias_passados_meta = $data_hoje < $data_inicio_meta ? 0 : $data_inicio_meta->diff($data_hoje)->days;
$dias_restantes_meta = $data_hoje > $data_final_meta ? 0 : $data_hoje->diff($data_final_meta)->days;
$meses_restantes_meta = $data_hoje > $data_final_meta ? 0 : floor($dias_restantes_meta / 30.4375);
$porcentagem_tempo_meta = $dias_totais_meta > 0 ? min(100, round(($dias_passados_meta / $dias_totais_meta) * 100, 1)) : 0;

$cotacao_dolar = 5.0;
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
  <title>Dashboard Financeiro</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <style>
    :root {
      --primary-color: #00c875;
      --primary-hover: #00a060;
      --text-primary: #333;
      --text-secondary: #666;
      --bg-primary: #f4f7f6;
      --bg-card: #ffffff;
      --border-radius: 16px;
      --transition: all 0.3s ease;
    }

    .dark-mode {
      --primary-color: #00d884;
      --primary-hover: #00c875;
      --text-primary: #e0e0e0;
      --text-secondary: #bbb;
      --bg-primary: #1a1a1a;
      --bg-card: #2c2c2c;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background-color: var(--bg-primary);
      color: var(--text-primary);
      transition: var(--transition);
      padding-bottom: 100px;
    }

    .navbar-custom {
      background-color: var(--bg-card);
      box-shadow: 0 2px 12px rgba(0,0,0,0.08);
      padding: 1rem;
    }

    .navbar-brand-custom {
      font-weight: 700;
      font-size: 1.4rem;
      color: var(--primary-color) !important;
    }

    .profile-link {
      color: var(--text-primary);
      text-decoration: none;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      border-radius: var(--border-radius);
      transition: var(--transition);
    }

    .profile-link:hover {
      background-color: rgba(0,200,117,0.1);
      color: var(--primary-color);
    }

    .theme-toggle {
      background: transparent;
      border: none;
      color: var(--text-primary);
      font-size: 1.2rem;
      padding: 0.5rem;
      cursor: pointer;
      border-radius: 50%;
      transition: var(--transition);
    }

    .theme-toggle:hover {
      background-color: rgba(0,0,0,0.05);
    }

    .dark-mode .theme-toggle:hover {
      background-color: rgba(255,255,255,0.05);
    }

    .main-header {
      font-size: 2rem;
      font-weight: 700;
      text-align: center;
      margin: 2rem 0;
      color: var(--text-primary);
    }

    .stat-card {
      background-color: var(--bg-card);
      border: none;
      border-radius: var(--border-radius);
      box-shadow: 0 4px 20px rgba(0,0,0,0.05);
      transition: var(--transition);
      height: 100%;
      padding: 1.5rem;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .stat-card-title {
      font-size: 0.9rem;
      color: var(--text-secondary);
      margin-bottom: 1rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .stat-card-value {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
      color: var(--primary-color);
    }

    .stat-card-icon {
      font-size: 2rem;
      opacity: 0.8;
      color: var(--primary-color);
    }

    .progress-chart-container {
      position: relative;
      height: 200px;
      margin: 1rem auto;
    }

    .progress-percentage {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--primary-color);
    }

    .info-card {
      background-color: var(--bg-card);
      border: none;
      border-radius: var(--border-radius);
      padding: 1.5rem;
      height: 100%;
      box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    }

    .info-card h5 {
      font-size: 1.2rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
      color: var(--text-primary);
    }

    .info-card p {
      margin-bottom: 1rem;
      color: var(--text-secondary);
      font-size: 1rem;
    }

    .info-card strong {
      color: var(--text-primary);
      font-weight: 600;
    }

    .fixed-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      background-color: var(--bg-card);
      border-top: 1px solid rgba(0,0,0,0.1);
      display: flex;
      justify-content: space-around;
      padding: 0.8rem 0;
      box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
      z-index: 1000;
    }

    .fixed-nav a {
      flex: 1;
      text-align: center;
      text-decoration: none;
      color: var(--text-secondary);
      font-size: 0.8rem;
      padding: 0.5rem;
      transition: var(--transition);
    }

    .fixed-nav a i {
      font-size: 1.4rem;
      display: block;
      margin-bottom: 0.3rem;
    }

    .fixed-nav a.active {
      color: var(--primary-color);
      font-weight: 600;
    }

    .fixed-nav a:hover {
      color: var(--primary-color);
    }

    @media (max-width: 768px) {
      .stat-card {
        margin-bottom: 1rem;
      }
      
      .main-header {
        font-size: 1.6rem;
        margin: 1.5rem 0;
      }
      
      .stat-card-value {
        font-size: 1.6rem;
      }
    }

    .highlight-value {
      font-size: 1.2rem;
      font-weight: 700;
      color: var(--primary-color);
    }

    .text-success { color: #00c875 !important; }
    .text-danger { color: #ff4d4d !important; }
    .text-warning { color: #ffaa00 !important; }
    .text-info { color: #0099ff !important; }
  </style>
</head>
<body>

<nav class="navbar navbar-custom sticky-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand navbar-brand-custom" href="index.php">
      <i class="fas fa-chart-pie me-2"></i>Dashboard Geral
    </a>
    <div class="d-flex align-items-center">
      <a href="perfil.php" class="profile-link">
        <i class="fas fa-user-circle"></i>
        <?= htmlspecialchars($nome_usuario); ?>
      </a>
      <button class="theme-toggle ms-3" onclick="toggleTheme()" aria-label="Alternar tema">
        <i class="fas fa-moon"></i>
      </button>
    </div>
  </div>
</nav>

<div class="container-xl mt-4 px-4">
  <h1 class="main-header">Visão Geral Financeira</h1>

  <div class="row g-4 mb-4">
    <div class="col-lg-4 col-md-6">
      <div class="stat-card">
        <div class="d-flex justify-content-between align-items-start mb-4">
          <h6 class="stat-card-title m-0">Total de Ganhos</h6>
          <i class="fas fa-dollar-sign stat-card-icon"></i>
        </div>
        <p class="stat-card-value">$<?= number_format($total_usd_ganho, 2, ',', '.') ?></p>
        <p class="text-secondary mb-0">
          <i class="fas fa-exchange-alt me-1"></i>
          R$ <?= number_format($total_reais_ganho, 2, ',', '.') ?>
        </p>
      </div>
    </div>

    <div class="col-lg-4 col-md-6">
      <div class="stat-card">
        <div class="d-flex justify-content-between align-items-start mb-4">
          <h6 class="stat-card-title m-0">Saldo Real</h6>
          <i class="fas fa-balance-scale stat-card-icon"></i>
        </div>
        <p class="stat-card-value <?= $saldo_real_financeiro_usd >= 0 ? 'text-success' : 'text-danger' ?>">
          $<?= number_format($saldo_real_financeiro_usd, 2, ',', '.') ?>
        </p>
        <div class="d-flex justify-content-between text-secondary">
          <span><i class="fas fa-plus-circle me-1"></i>$<?= number_format($saldo_total_financeiro_usd, 2, ',', '.') ?></span>
          <span><i class="fas fa-minus-circle me-1"></i>$<?= number_format($divida_total_financeiro_usd, 2, ',', '.') ?></span>
        </div>
      </div>
    </div>

    <div class="col-lg-4 col-md-12">
      <div class="stat-card text-center">
        <h6 class="stat-card-title mb-4">Progresso da Meta</h6>
        <div class="progress-chart-container">
          <canvas id="progressoMetaChart"></canvas>
          <div class="progress-percentage"><?= $porcentagem_tempo_meta ?>%</div>
        </div>
        <p class="text-secondary mt-2">
          <i class="fas fa-clock me-1"></i>
          Tempo decorrido
        </p>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-md-6">
      <div class="info-card">
        <h5><i class="fas fa-bullseye me-2 text-primary"></i>Meta Financeira</h5>
        <p>
          <strong>Objetivo:</strong>
          <span class="highlight-value">R$ <?= number_format($meta_reais, 2, ',', '.') ?></span>
        </p>
        <p>
          <strong>Data Final:</strong>
          <span class="text-primary"><?= $data_final_meta->format('d/m/Y') ?></span>
        </p>
        <p>
          <strong>Tempo Restante:</strong>
          <?= $dias_restantes_meta ?> dias (<?= $meses_restantes_meta ?> meses)
        </p>
        <p class="mb-0">
          <strong>Necessário por Mês:</strong><br>
          <span class="highlight-value">R$ <?= number_format($valor_necessario_mensal_meta_reais, 2, ',', '.') ?></span>
          <small class="text-secondary">($<?= number_format($valor_necessario_mensal_meta_usd, 2, ',', '.') ?>)</small>
        </p>
      </div>
    </div>

    <div class="col-md-6">
      <div class="info-card">
        <h5><i class="fas fa-book me-2 text-warning"></i>Resumo YNAB</h5>
        <p>
          <strong>Receitas:</strong>
          <span class="text-success">R$ <?= number_format($total_ynab_receitas, 2, ',', '.') ?></span>
        </p>
        <p>
          <strong>Despesas:</strong>
          <span class="text-danger">R$ <?= number_format($total_ynab_despesas, 2, ',', '.') ?></span>
        </p>
        <hr class="my-3">
        <p class="mb-0">
          <strong>Saldo:</strong>
          <span class="highlight-value <?= $saldo_ynab >= 0 ? 'text-success' : 'text-danger' ?>">
            R$ <?= number_format($saldo_ynab, 2, ',', '.') ?>
          </span>
        </p>
      </div>
    </div>
  </div>
</div>

<div class="fixed-nav">
  <a href="index.php" class="active">
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
    <span>Listar</span>
  </a>
  <a href="financeiro.php">
    <i class="fas fa-wallet"></i>
    <span>Financeiro</span>
  </a>
  <a href="ynab_dashboard.php">
    <i class="fas fa-book-open"></i>
    <span>YNAB</span>
  </a>
</div>

<script>
const themeToggleBtn = document.querySelector('.theme-toggle i');

function applyTheme(isDark) {
  document.body.classList.toggle('dark-mode', isDark);
  themeToggleBtn.classList.toggle('fa-sun', isDark);
  themeToggleBtn.classList.toggle('fa-moon', !isDark);
  localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
  
  if (window.progressoMetaChart) {
    const chartColors = isDark ? {
      primary: '#00d884',
      secondary: '#444'
    } : {
      primary: '#00c875',
      secondary: '#dee2e6'
    };
    
    progressoMetaChart.data.datasets[0].backgroundColor = [
      chartColors.primary,
      chartColors.secondary
    ];
    progressoMetaChart.options.plugins.legend.labels.color = isDark ? '#e0e0e0' : '#333';
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

  const isDark = document.body.classList.contains('dark-mode');
  const chartColors = isDark ? {
    primary: '#00d884',
    secondary: '#444'
  } : {
    primary: '#00c875',
    secondary: '#dee2e6'
  };

  const ctxProgressoMeta = document.getElementById('progressoMetaChart')?.getContext('2d');
  if (ctxProgressoMeta) {
    window.progressoMetaChart = new Chart(ctxProgressoMeta, {
      type: 'doughnut',
      data: {
        labels: ['Concluído', 'Faltando'],
        datasets: [{
          data: [<?= $porcentagem_tempo_meta ?>, <?= 100 - $porcentagem_tempo_meta ?>],
          backgroundColor: [chartColors.primary, chartColors.secondary],
          borderWidth: 0,
          hoverOffset: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '80%',
        plugins: {
          legend: {
            display: false,
            labels: {
              color: isDark ? '#e0e0e0' : '#333'
            }
          },
          tooltip: {
            enabled: true,
            backgroundColor: isDark ? '#2c2c2c' : '#fff',
            titleColor: isDark ? '#e0e0e0' : '#333',
            bodyColor: isDark ? '#e0e0e0' : '#333',
            borderColor: isDark ? '#444' : '#dee2e6',
            borderWidth: 1
          }
        },
        animation: {
          animateScale: true,
          animateRotate: true
        }
      }
    });
  }

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