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

$categorias = [];
$valores_categoria = [];
$stmt = $pdo->prepare("SELECT c.nome, SUM(t.valor) as total FROM transacoes t JOIN categorias c ON t.categoria_id = c.id WHERE t.tipo = 'despesa' GROUP BY c.id ORDER BY total DESC");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $categorias[] = $row['nome'];
  $valores_categoria[] = $row['total'];
}

$contas = [];
$saldos = [];
$stmt = $pdo->prepare("SELECT ct.nome, SUM(CASE WHEN t.tipo = 'receita' THEN t.valor ELSE 0 END) - SUM(CASE WHEN t.tipo = 'despesa' THEN t.valor ELSE 0 END) as saldo FROM contas ct LEFT JOIN transacoes t ON t.conta_id = ct.id GROUP BY ct.id");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $contas[] = $row['nome'];
  $saldos[] = $row['saldo'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Dashboard YNAB</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; color: #111; padding-bottom: 80px; }
    .dark-mode { background-color: #121212 !important; color: #f1f1f1 !important; }
    .dark-mode canvas, .dark-mode .card { background-color: #1e1e1e !important; color: #fff !important; }
    .theme-toggle { font-size: 18px; background: transparent; border: none; margin-left: 12px; color: inherit; cursor: pointer; }
    .fixed-nav { position: fixed; bottom: 0; width: 100%; background: #fff; border-top: 1px solid #ddd; display: flex; justify-content: space-around; padding: 6px 0; z-index: 9999; }
    .fixed-nav a { flex: 1; text-align: center; text-decoration: none; color: #555; font-size: 13px; }
    .fixed-nav a div { display: flex; flex-direction: column; align-items: center; font-weight: 500; }
    .fixed-nav a span { font-size: 11px; margin-top: 2px; }
    .dark-mode .fixed-nav { background: #1e1e1e; border-color: #333; }
    .dark-mode .fixed-nav a { color: #ccc; }
  </style>
</head>
<body>
<nav class="navbar navbar-dark bg-dark mb-3">
  <div class="container-fluid px-3 d-flex justify-content-between align-items-center flex-wrap">
    <a class="navbar-brand text-success" href="#">📘 YNAB</a>
    <div class="d-flex align-items-center">
      <a href="perfil.php" class="text-light text-decoration-none fw-bold"><?= $_SESSION['usuario']; ?></a>
      <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
    </div>
  </div>
</nav>

<div class="container-xl px-3 pb-5">
  <h4 class="mb-4 text-center">📊 Visão Geral</h4>
  <div class="row g-4">
    <div class="col-md-6">
      <div class="card p-3">
        <h6>Total por Tipo</h6>
        <canvas id="tipoChart"></canvas>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card p-3">
        <h6>Despesas por Categoria</h6>
        <canvas id="categoriaChart"></canvas>
      </div>
    </div>
    <div class="col-12">
      <div class="card p-3 mt-3">
        <h6>Saldo por Conta</h6>
        <canvas id="contaChart"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="fixed-nav">
  <a href="ynab_dashboard.php"><div>📊<span>Dashboard</span></div></a>
  <a href="ynab_nova_transacao.php"><div>➕<span>Nova</span></div></a>
  <a href="ynab_listar_transacoes.php"><div>📄<span>Transações</span></div></a>
  <a href="ynab_categorias.php"><div>📂<span>Categorias</span></div></a>
  <a href="ynab_contas.php"><div>🏦<span>Contas</span></div></a>
  <a href="index.php"><div>🏠<span>Home</span></div></a>
</div>

<script>
const tipoChart = new Chart(document.getElementById('tipoChart'), {
  type: 'pie',
  data: {
    labels: ['Receita', 'Despesa', 'Transferência'],
    datasets: [{
      data: [<?= $totais_por_tipo['receita'] ?? 0 ?>, <?= $totais_por_tipo['despesa'] ?? 0 ?>, <?= $totais_por_tipo['transferencia'] ?? 0 ?>],
      backgroundColor: ['green', 'red', 'gray']
    }]
  }
});
const categoriaChart = new Chart(document.getElementById('categoriaChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($categorias) ?>,
    datasets: [{
      label: 'Valor (R$)',
      data: <?= json_encode($valores_categoria) ?>,
      backgroundColor: 'rgba(255, 99, 132, 0.7)'
    }]
  },
  options: { indexAxis: 'y' }
});
const contaChart = new Chart(document.getElementById('contaChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($contas) ?>,
    datasets: [{
      label: 'Saldo (R$)',
      data: <?= json_encode($saldos) ?>,
      backgroundColor: 'rgba(54, 162, 235, 0.7)'
    }]
  }
});
function toggleTheme() {
  const body = document.body;
  body.classList.toggle("dark-mode");
  const isDark = body.classList.contains("dark-mode");
  localStorage.setItem("modo_escuro", isDark);
  document.querySelector(".theme-toggle").innerText = isDark ? "🔆" : "🌙";
}
document.addEventListener("DOMContentLoaded", () => {
  const isDark = localStorage.getItem("modo_escuro") === "true";
  if (isDark) {
    document.body.classList.add("dark-mode");
    document.querySelector(".theme-toggle").innerText = "🔆";
  }
});
</script>
</body>
</html>