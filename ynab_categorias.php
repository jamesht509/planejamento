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
  <title>Categorias</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; color: #111; padding-bottom: 80px; }
    .dark-mode { background-color: #121212 !important; color: #f1f1f1 !important; }
    .dark-mode .form-control, .dark-mode .form-select, .dark-mode .table {
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
  <h4 class="mb-4 text-center">📂 Categorias</h4>

  <div class="card p-4 shadow-sm mb-4">
    <form action="" method="POST" class="row g-3">
      <div class="col-md-6">
        <input type="text" name="nome" class="form-control" placeholder="Nome da categoria" required>
      </div>
      <div class="col-md-3">
        <select name="tipo" class="form-select">
          <option value="despesa">Despesa</option>
          <option value="receita">Receita</option>
        </select>
      </div>
      <div class="col-md-3">
        <button type="submit" name="adicionar" class="btn btn-primary w-100">➕ Adicionar</button>
      </div>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>Nome</th>
          <th>Tipo</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if (isset($_POST['adicionar'])) {
          $stmt = $pdo->prepare("INSERT INTO categorias (nome, tipo) VALUES (?, ?)");
          $stmt->execute([$_POST['nome'], $_POST['tipo']]);
          header("Location: ynab_categorias.php");
          exit;
        }

        if (isset($_GET['excluir'])) {
          $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
          $stmt->execute([$_GET['excluir']]);
          header("Location: ynab_categorias.php");
          exit;
        }

        $stmt = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC");
        foreach ($stmt as $cat) {
          echo "<tr>
                  <td>{$cat['nome']}</td>
                  <td>{$cat['tipo']}</td>
                  <td>
                    <a href='?excluir={$cat['id']}' onclick='return confirm(\"Excluir categoria?\")' class='btn btn-sm btn-danger'>🗑️</a>
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