
<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
include 'conexao.php';

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'];
    $moeda = $_POST['moeda'];
    $valor = floatval($_POST['valor']);
    if ($valor > 0 && in_array($tipo, ['saldo', 'despesa']) && in_array($moeda, ['USD', 'BRL'])) {
        $stmt = $pdo->prepare("INSERT INTO financeiro (tipo, valor, moeda) VALUES (?, ?, ?)");
        if ($stmt->execute([$tipo, $valor, $moeda])) {
            $mensagem = '<div class="alert alert-success text-center">✅ Registro salvo com sucesso!</div>';
        } else {
            $mensagem = '<div class="alert alert-danger text-center">❌ Erro ao salvar.</div>';
        }
    } else {
        $mensagem = '<div class="alert alert-danger text-center">⚠️ Preencha corretamente todos os campos.</div>';
    }
}

$dados = $pdo->query("SELECT * FROM financeiro")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Financeiro</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      color: #111;
    }
    .dark-mode {
      background-color: #121212 !important;
      color: #f1f1f1 !important;
    }
    .dark-mode .card, 
    .dark-mode .navbar,
    .dark-mode .form-control,
    .dark-mode .form-select,
    .dark-mode .table {
      background-color: #1e1e1e !important;
      color: #eaeaea !important;
    }
    .dark-mode .form-control, 
    .dark-mode .form-select {
      border-color: #333 !important;
    }
    .dark-mode .btn-primary {
      background-color: #00c875;
      border: none;
    }
    .theme-toggle {
      font-size: 18px;
      background: transparent;
      border: none;
      margin-left: 12px;
      color: inherit;
      cursor: pointer;
    }
    .fixed-nav {
      position: fixed;
      bottom: 0;
      width: 100%;
      background: #fff;
      border-top: 1px solid #ddd;
      display: flex;
      justify-content: space-around;
      padding: 6px 0;
      z-index: 9999;
    }
    .fixed-nav a {
      flex: 1;
      text-align: center;
      text-decoration: none;
      color: #555;
      font-size: 13px;
    }
    .fixed-nav a div {
      display: flex;
      flex-direction: column;
      align-items: center;
      font-weight: 500;
    }
    .fixed-nav a span {
      font-size: 11px;
      margin-top: 2px;
    }
    .dark-mode .fixed-nav {
      background: #1e1e1e;
      border-color: #333;
    }
    .dark-mode .fixed-nav a {
      color: #ccc;
    }
    table th, table td {
      vertical-align: middle !important;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark mb-3">
  <div class="container-fluid px-3 d-flex justify-content-between align-items-center flex-wrap">
    <a class="navbar-brand text-success" href="index.php">💳 Financeiro</a>
    <div class="d-flex align-items-center">
      <a href="perfil.php" class="text-light text-decoration-none fw-bold">
        <?= $_SESSION['usuario']; ?>
      </a>
      <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
    </div>
  </div>
</nav>

<div class="container-xl px-3">
  <h4 class="text-center mb-4">📋 Adicionar Registro Financeiro</h4>

  <?= $mensagem ?>

  <form method="POST" class="card p-4 shadow-sm mb-4 mx-auto" style="max-width: 600px;">
    <div class="mb-3">
      <label>Tipo:</label>
      <select name="tipo" class="form-select" required>
        <option value="">Selecione</option>
        <option value="saldo">💰 Saldo</option>
        <option value="despesa">💳 Despesa</option>
      </select>
    </div>
    <div class="mb-3">
      <label>Moeda:</label>
      <select name="moeda" class="form-select" required>
        <option value="USD">Dólar</option>
        <option value="BRL">Real</option>
      </select>
    </div>
    <div class="mb-3">
      <label>Valor:</label>
      <input type="number" step="0.01" name="valor" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Salvar</button>
  </form>

  <h5 class="text-center mt-5 mb-3">📊 Registros Recentes</h5>
  <div class="table-responsive">
    <table class="table table-striped table-hover">
      <thead class="table-light">
        <tr>
          <th>Tipo</th>
          <th>Valor</th>
          <th>Moeda</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($dados as $d): ?>
        <tr>
          <td><?= ucfirst($d['tipo']) ?></td>
          <td><?= number_format($d['valor'], 2) ?></td>
          <td><?= $d['moeda'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="fixed-nav">
  <a href="index.php"><div>🏠<span>Resumo</span></div></a>
  <a href="ganhos.php"><div>💵<span>Ganho</span></div></a>
  <a href="nova_atividade.php"><div>➕<span>Atividade</span></div></a>
  <a href="listar_ganhos.php"><div>📄<span>Ganhos</span></div></a>
  <a href="financeiro.php"><div>💳<span>Financeiro</span></div></a>
</div>

<script>
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
