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
  <title>Transações</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; color: #111; padding-bottom: 80px; }
    .dark-mode { background-color: #121212 !important; color: #f1f1f1 !important; }
    .dark-mode .table, .dark-mode .form-control, .dark-mode .form-select {
      background-color: #1e1e1e; color: #fff;
    }
    .theme-toggle {
      font-size: 18px; background: transparent; border: none;
      margin-left: 12px; color: inherit; cursor: pointer;
    }
    .fixed-nav {
      position: fixed; bottom: 0; width: 100%; background: #fff;
      border-top: 1px solid #ddd; display: flex; justify-content: space-around;
      padding: 6px 0; z-index: 9999;
    }
    .fixed-nav a {
      flex: 1; text-align: center; text-decoration: none;
      color: #555; font-size: 13px;
    }
    .fixed-nav a div {
      display: flex; flex-direction: column; align-items: center;
      font-weight: 500;
    }
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
  <h4 class="mb-4 text-center">📄 Minhas Transações</h4>

  <form class="row g-3 mb-4" method="GET">
    <div class="col-md-4">
      <select name="tipo" class="form-select">
        <option value="">Todos os Tipos</option>
        <option value="receita">Receita</option>
        <option value="despesa">Despesa</option>
        <option value="transferencia">Transferência</option>
      </select>
    </div>
    <div class="col-md-4">
      <select name="categoria_id" class="form-select">
        <option value="">Todas as Categorias</option>
        <?php
        $res = $pdo->query("SELECT * FROM categorias ORDER BY nome");
        foreach ($res as $row) {
          echo "<option value='{$row['id']}'>{$row['nome']}</option>";
        }
        ?>
      </select>
    </div>
    <div class="col-md-4">
      <button type="submit" class="btn btn-primary w-100">🔍 Filtrar</button>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>Data</th>
          <th>Tipo</th>
          <th>Categoria</th>
          <th>Conta</th>
          <th>Valor</th>
          <th>Descrição</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $filtro = "1=1";
        if (!empty($_GET['tipo'])) {
          $tipo = $_GET['tipo'];
          $filtro .= " AND t.tipo = '$tipo'";
        }
        if (!empty($_GET['categoria_id'])) {
          $categoria_id = intval($_GET['categoria_id']);
          $filtro .= " AND t.categoria_id = $categoria_id";
        }

        $sql = "SELECT t.*, c.nome AS categoria, ct.nome AS conta
                FROM transacoes t
                LEFT JOIN categorias c ON t.categoria_id = c.id
                LEFT JOIN contas ct ON t.conta_id = ct.id
                WHERE $filtro
                ORDER BY t.data DESC";
        $stmt = $pdo->query($sql);
        foreach ($stmt as $row) {
          $valor = 'R$ ' . number_format($row['valor'], 2, ',', '.');
          echo "<tr>
                  <td>".date('d/m/Y', strtotime($row['data']))."</td>
                  <td>{$row['tipo']}</td>
                  <td>{$row['categoria']}</td>
                  <td>{$row['conta']}</td>
                  <td>{$valor}</td>
                  <td>{$row['descricao']}</td>
                  <td>
                    <a href='ynab_editar_transacao.php?id={$row['id']}' class='btn btn-sm btn-warning'>✏️</a>
                    <a href='ynab_excluir_transacao.php?id={$row['id']}' onclick='return confirm(\"Confirma?\")' class='btn btn-sm btn-danger'>🗑️</a>
                  </td>
                </tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<div class="fixed-nav">
  <a href="ynab_dashboard.php"><div>📊<span>Dashboard</span></div></a>
  <a href="ynab_nova_transacao.php"><div>➕<span>Nova</span></div></a>
  <a href="ynab_listar_transacoes.php"><div>📄<span>Transações</span></div></a>
  <a href="ynab_categorias.php"><div>📂<span>Categorias</span></div></a>
  <a href="ynab_contas.php"><div>🏦<span>Contas</span></div></a>
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