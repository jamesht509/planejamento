<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
include 'conexao.php';

$tipos = ['receita', 'despesa', 'transferencia'];
$totais_por_tipo = [];
foreach ($tipos as $tipo) {
  $stmt = $pdo->prepare("SELECT SUM(valor) as total FROM transacoes WHERE tipo = ?");
  $stmt->execute([$tipo]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $totais_por_tipo[$tipo] = $row['total'] ?? 0;
}

// Total receitas e despesas para saldo atual
$stmt = $pdo->prepare("SELECT 
                        SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) as total_receitas,
                        SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) as total_despesas
                      FROM transacoes");
$stmt->execute();
$totais = $stmt->fetch(PDO::FETCH_ASSOC);
$saldo_atual = ($totais['total_receitas'] ?? 0) - ($totais['total_despesas'] ?? 0);

// Despesas por categoria
$categorias = [];
$valores_categoria = [];
$cores_categoria = [];
$stmt = $pdo->prepare("SELECT c.nome, SUM(t.valor) as total FROM transacoes t JOIN categorias c ON t.categoria_id = c.id WHERE t.tipo = 'despesa' GROUP BY c.id ORDER BY total DESC");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $categorias[] = $row['nome'];
  $valores_categoria[] = $row['total'];
  // Gerar cores diferentes para cada categoria
  $cores_categoria[] = 'hsl(' . rand(0, 360) . ', 70%, 60%)';
}

// Saldo por conta
$contas = [];
$saldos = [];
$stmt = $pdo->prepare("SELECT ct.nome, SUM(CASE WHEN t.tipo = 'receita' THEN t.valor ELSE 0 END) - SUM(CASE WHEN t.tipo = 'despesa' THEN t.valor ELSE 0 END) as saldo FROM contas ct LEFT JOIN transacoes t ON t.conta_id = ct.id GROUP BY ct.id");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $contas[] = $row['nome'];
  $saldos[] = $row['saldo'];
}

// Últimas transações
$ultimas_transacoes = [];
$stmt = $pdo->prepare("SELECT t.id, t.descricao, t.valor, t.data, t.tipo, c.nome as categoria, ct.nome as conta 
                      FROM transacoes t 
                      JOIN categorias c ON t.categoria_id = c.id 
                      JOIN contas ct ON t.conta_id = ct.id 
                      ORDER BY t.data DESC LIMIT 5");
$stmt->execute();
$ultimas_transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Formatar valores monetários
function formatarMoeda($valor) {
  return 'R$ ' . number_format($valor, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Financeiro</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
      background-color: var(--light-bg);
      color: var(--dark-text);
      padding-bottom: 80px;
      transition: var(--transition);
    }

    .dark-mode {
      background-color: var(--dark-bg) !important;
      color: var(--light-text) !important;
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
    
    .dark-mode .fixed-nav a {
      color: #bbb !important;
    }
    
    .dark-mode .table {
      color: var(--light-text) !important;
    }
    
    .navbar {
      box-shadow: var(--box-shadow);
      transition: var(--transition);
    }
    
    .card {
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      transition: var(--transition);
      border: none;
      overflow: hidden;
    }
    
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    }
    
    .card-header {
      border-bottom: 1px solid rgba(0,0,0,0.1);
      padding: 1rem 1.5rem;
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
    
    .stats-card {
      position: relative;
      padding: 20px;
      margin-bottom: 20px;
      overflow: hidden;
    }
    
    .stats-card .stats-icon {
      font-size: 32px;
      opacity: 0.2;
      position: absolute;
      right: 20px;
      top: 50%;
      transform: translateY(-50%);
    }
    
    .stats-card h2 {
      margin-bottom: 5px;
      font-weight: 700;
    }
    
    .stats-card p {
      margin-bottom: 0;
      opacity: 0.7;
    }
    
    .card-positivo {
      background: linear-gradient(135deg, var(--secondary-color), #27ae60);
      color: white;
    }
    
    .card-negativo {
      background: linear-gradient(135deg, var(--danger-color), #c0392b);
      color: white;
    }
    
    .card-neutro {
      background: linear-gradient(135deg, var(--primary-color), #2980b9);
      color: white;
    }
    
    .chart-container {
      position: relative;
      height: 250px;
    }
    
    .transacao-row {
      transition: var(--transition);
    }
    
    .transacao-row:hover {
      background-color: rgba(0,0,0,0.03);
    }
    
    .dark-mode .transacao-row:hover {
      background-color: rgba(255,255,255,0.05);
    }
    
    .transacao-receita {
      color: var(--secondary-color);
    }
    
    .transacao-despesa {
      color: var(--danger-color);
    }
    
    .transacao-transferencia {
      color: var(--warning-color);
    }
    
    .action-btn {
      font-size: 0.9rem;
      padding: 0.25rem 0.5rem;
      margin-right: 0.3rem;
      border-radius: 4px;
    }
    
    @media (max-width: 768px) {
      .container-xl {
        padding-left: 12px;
        padding-right: 12px;
      }
      
      .stats-card {
        margin-bottom: 15px;
        padding: 15px;
      }
      
      .stats-card .stats-icon {
        font-size: 24px;
      }
      
      .stats-card h2 {
        font-size: 1.4rem;
      }
    }
    
    .dashboard-icon {
      position: relative;
      top: 2px;
    }
    
    .tooltip-inner {
      max-width: 220px;
      padding: 8px 12px;
      border-radius: 6px;
    }
    
    .animate-pulse {
      animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
      0% {
        opacity: 1;
      }
      50% {
        opacity: 0.6;
      }
      100% {
        opacity: 1;
      }
    }
  </style>
</head>
<body>
<nav class="navbar navbar-dark bg-primary mb-4 py-3">
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
  <div class="row mb-4">
    <div class="col-12">
      <h4 class="mb-4 fw-bold">
        <i class="fa-solid fa-chart-line me-2 dashboard-icon"></i>Visão Geral
      </h4>
    </div>
    
    <!-- Cards de estatísticas -->
    <div class="col-md-4">
      <div class="card stats-card card-neutro">
        <h2><?= formatarMoeda($saldo_atual) ?></h2>
        <p>Saldo Atual</p>
        <div class="stats-icon">
          <i class="fa-solid fa-wallet"></i>
        </div>
      </div>
    </div>
    
    <div class="col-md-4">
      <div class="card stats-card card-positivo">
        <h2><?= formatarMoeda($totais_por_tipo['receita'] ?? 0) ?></h2>
        <p>Receitas Totais</p>
        <div class="stats-icon">
          <i class="fa-solid fa-arrow-trend-up"></i>
        </div>
      </div>
    </div>
    
    <div class="col-md-4">
      <div class="card stats-card card-negativo">
        <h2><?= formatarMoeda($totais_por_tipo['despesa'] ?? 0) ?></h2>
        <p>Despesas Totais</p>
        <div class="stats-icon">
          <i class="fa-solid fa-arrow-trend-down"></i>
        </div>
      </div>
    </div>
  </div>
  
  <div class="row g-4">
    <div class="col-md-6">
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">
            <i class="fa-solid fa-chart-pie me-2"></i>
            Distribuição por Tipo
          </h6>
          <button class="btn btn-sm btn-outline-secondary" 
                  data-bs-toggle="tooltip" 
                  data-bs-placement="top" 
                  title="Distribuição dos valores por tipo de transação">
            <i class="fa-solid fa-info-circle"></i>
          </button>
        </div>
        <div class="card-body">
          <div class="chart-container">
            <canvas id="tipoChart"></canvas>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-md-6">
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">
            <i class="fa-solid fa-tags me-2"></i>
            Despesas por Categoria
          </h6>
          <button class="btn btn-sm btn-outline-secondary" 
                  data-bs-toggle="tooltip" 
                  data-bs-placement="top" 
                  title="Principais categorias de despesas ordenadas por valor total">
            <i class="fa-solid fa-info-circle"></i>
          </button>
        </div>
        <div class="card-body">
          <div class="chart-container">
            <canvas id="categoriaChart"></canvas>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-md-6">
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">
            <i class="fa-solid fa-building-columns me-2"></i>
            Saldo por Conta
          </h6>
          <button class="btn btn-sm btn-outline-secondary" 
                  data-bs-toggle="tooltip" 
                  data-bs-placement="top" 
                  title="Saldo disponível em cada conta">
            <i class="fa-solid fa-info-circle"></i>
          </button>
        </div>
        <div class="card-body">
          <div class="chart-container">
            <canvas id="contaChart"></canvas>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-md-6">
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">
            <i class="fa-solid fa-clock-rotate-left me-2"></i>
            Últimas Transações
          </h6>
          <a href="ynab_listar_transacoes.php" class="btn btn-sm btn-outline-primary">
            Ver Todas <i class="fa-solid fa-arrow-right ms-1"></i>
          </a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr class="text-muted">
                  <th scope="col">Descrição</th>
                  <th scope="col">Valor</th>
                  <th scope="col">Data</th>
                  <th scope="col">Tipo</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($ultimas_transacoes)): ?>
                  <tr>
                    <td colspan="4" class="text-center py-3">Nenhuma transação encontrada</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($ultimas_transacoes as $transacao): ?>
                    <tr class="transacao-row">
                      <td><?= $transacao['descricao'] ?></td>
                      <td class="transacao-<?= $transacao['tipo'] ?>">
                        <?= formatarMoeda($transacao['valor']) ?>
                      </td>
                      <td><?= date('d/m/Y', strtotime($transacao['data'])) ?></td>
                      <td>
                        <span class="badge bg-<?= 
                          $transacao['tipo'] == 'receita' ? 'success' : 
                          ($transacao['tipo'] == 'despesa' ? 'danger' : 'warning') 
                        ?>">
                          <?= ucfirst($transacao['tipo']) ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="fixed-nav">
  <a href="ynab_dashboard.php" class="active">
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
  <a href="index.php">
    <div>
      <i class="fa-solid fa-house"></i>
      <span>Home</span>
    </div>
  </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Inicializar tooltips do Bootstrap
const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl);
});

// Estilo comum para gráficos
Chart.defaults.font.family = "'Roboto', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.plugins.tooltip.padding = 10;
Chart.defaults.plugins.tooltip.cornerRadius = 6;
Chart.defaults.plugins.tooltip.titleFont.weight = 'bold';

// Definir cores para modo claro e escuro
function getChartColors() {
  const isDark = document.body.classList.contains("dark-mode");
  return {
    textColor: isDark ? '#e1e1e1' : '#666',
    gridColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
    background: isDark ? '#1e1e1e' : '#fff'
  };
}

// Atualizar cores dos gráficos
function updateChartColors() {
  const colors = getChartColors();
  
  [tipoChart, categoriaChart, contaChart].forEach(chart => {
    chart.options.scales.x.ticks.color = colors.textColor;
    chart.options.scales.y.ticks.color = colors.textColor;
    chart.options.scales.x.grid.color = colors.gridColor;
    chart.options.scales.y.grid.color = colors.gridColor;
    chart.update();
  });
}

// Gráfico de tipo de transação
const tipoChart = new Chart(document.getElementById('tipoChart'), {
  type: 'doughnut',
  data: {
    labels: ['Receita', 'Despesa', 'Transferência'],
    datasets: [{
      data: [<?= $totais_por_tipo['receita'] ?? 0 ?>, <?= $totais_por_tipo['despesa'] ?? 0 ?>, <?= $totais_por_tipo['transferencia'] ?? 0 ?>],
      backgroundColor: ['rgba(46, 204, 113, 0.9)', 'rgba(231, 76, 60, 0.9)', 'rgba(52, 152, 219, 0.9)'],
      borderWidth: 1,
      borderColor: getChartColors().background
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '65%',
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          padding: 15,
          usePointStyle: true,
          pointStyle: 'circle',
          color: getChartColors().textColor
        }
      },
      tooltip: {
        callbacks: {
          label: function(context) {
            let label = context.label || '';
            let value = context.formattedValue;
            let percentage = Math.round((context.raw / context.dataset.data.reduce((a, b) => a + b, 0)) * 100);
            return `${label}: ${value} (${percentage}%)`;
          }
        }
      }
    },
    animation: {
      animateScale: true,
      animateRotate: true,
      duration: 1000
    }
  }
});

// Gráfico de despesas por categoria
const categoriaChart = new Chart(document.getElementById('categoriaChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($categorias) ?>,
    datasets: [{
      label: 'Valor (R$)',
      data: <?= json_encode($valores_categoria) ?>,
      backgroundColor: <?= json_encode($cores_categoria) ?>,
      borderWidth: 0,
      borderRadius: 5,
      barPercentage: 0.6
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y',
    plugins: {
      legend: {
        display: false
      },
      tooltip: {
        callbacks: {
          label: function(context) {
            let value = context.raw;
            return 'R$ ' + value.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
          }
        }
      }
    },
    scales: {
      x: {
        grid: {
          color: getChartColors().gridColor
        },
        ticks: {
          color: getChartColors().textColor,
          callback: function(value) {
            return 'R$ ' + value.toLocaleString('pt-BR');
          }
        }
      },
      y: {
        grid: {
          display: false
        },
        ticks: {
          color: getChartColors().textColor
        }
      }
    },
    animation: {
      delay: (context) => context.dataIndex * 100
    }
  }
});

// Gráfico de saldo por conta
const contaChart = new Chart(document.getElementById('contaChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($contas) ?>,
    datasets: [{
      label: 'Saldo (R$)',
      data: <?= json_encode($saldos) ?>,
      backgroundColor: function(context) {
        const value = context.dataset.data[context.dataIndex];
        return value >= 0 ? 'rgba(52, 152, 219, 0.9)' : 'rgba(231, 76, 60, 0.9)';
      },
      borderWidth: 0,
      borderRadius: 5
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false
      },
      tooltip: {
        callbacks: {
          label: function(context) {
            let value = context.raw;
            return 'R$ ' + value.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
          }
        }
      }
    },
    scales: {
      x: {
        grid: {
          display: false
        },
        ticks: {
          color: getChartColors().textColor
        }
      },
      y: {
        grid: {
          color: getChartColors().gridColor
        },
        ticks: {
          color: getChartColors().textColor,
          callback: function(value) {
            return 'R$ ' + value.toLocaleString('pt-BR');
          }
        }
      }
    },
    animation: {
      duration: 1500
    }
  }
});

// Alternar tema
function toggleTheme() {
  const body = document.body;
  body.classList.toggle("dark-mode");
  const isDark = body.classList.contains("dark-mode");
  localStorage.setItem("modo_escuro", isDark);
  
  // Mudar ícone do toggle
  document.querySelector(".theme-toggle i").className = isDark ? "fa-solid fa-sun" : "fa-solid fa-moon";
  
  // Atualizar cores dos gráficos
  updateChartColors();
}

// Carregar tema salvo
document.addEventListener("DOMContentLoaded", () => {
  const isDark = localStorage.getItem("modo_escuro") === "true";
  if (isDark) {
    document.body.classList.add("dark-mode");
    document.querySelector(".theme-toggle i").className = "fa-solid fa-sun";
  }
  
  // Adicionar classe 'active' ao link atual
  const currentUrl = window.location.pathname;
  document.querySelectorAll('.fixed-nav a').forEach(link => {
    if (link.getAttribute('href') === currentUrl.split('/').pop()) {
      link.style.color = '#3498db';
    }
  });
  
  // Atualizar cores dos gráficos iniciais
  updateChartColors();
  
  // Adicionar animação de entrada aos cards
  document.querySelectorAll('.card').forEach((card, index) => {
    setTimeout(() => {
      card.style.opacity = '1';
      card.style.transform = 'translateY(0)';
    }, 100 * index);
  });
});
</script>
</body>
</html>
