<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
include 'conexao.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: ynab_listar_transacoes.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM transacoes WHERE id = ?");
$stmt->execute([$id]);
$transacao = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$transacao) {
    header('Location: ynab_listar_transacoes.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Editar Transação</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; color: #111; padding-bottom: 80px; }
    .dark-mode { background-color: #121212 !important; color: #f1f1f1 !important; }
    .dark-mode .form-control, .dark-mode .form-select {
      background-color: #1e1e1e; color: #fff;
    }
    .theme-toggle {
      font-size: 18px; background: transparent; border: none;
      margin-left: 12px; color: inherit; cursor: pointer;
    }
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
  <h4 class="mb-4 text-center">✏️ Editar Transação</h4>

  <div class="card p-4 shadow-sm">
    <form action="ynab_salvar_edicao_transacao.php" method="POST" class="row g-3">
      <input type="hidden" name="id" value="<?= $transacao['id'] ?>">

      <div class="col-md-4">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-select" required>
          <option value="receita" <?= $transacao['tipo'] === 'receita' ? 'selected' : '' ?>>Receita</option>
          <option value="despesa" <?= $transacao['tipo'] === 'despesa' ? 'selected' : '' ?>>Despesa</option>
          <option value="transferencia" <?= $transacao['tipo'] === 'transferencia' ? 'selected' : '' ?>>Transferência</option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label">Valor</label>
        <input type="number" step="0.01" name="valor" class="form-control" value="<?= $transacao['valor'] ?>" required>
      </div>

      <div class="col-md-4">
        <label class="form-label">Data</label>
        <input type="date" name="data" class="form-control" value="<?= $transacao['data'] ?>" required>
      </div>

      <div class="col-md-6">
        <label class="form-label">Categoria</label>
        <select name="categoria_id" class="form-select" required>
          <?php
          $res = $pdo->query("SELECT * FROM categorias ORDER BY nome");
          foreach ($res as $row) {
            $selected = $row['id'] == $transacao['categoria_id'] ? 'selected' : '';
            echo "<option value='{$row['id']}' $selected>{$row['nome']}</option>";
          }
          ?>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Conta</label>
        <select name="conta_id" class="form-select" required>
          <?php
          $res = $pdo->query("SELECT * FROM contas ORDER BY nome");
          foreach ($res as $row) {
            $selected = $row['id'] == $transacao['conta_id'] ? 'selected' : '';
            echo "<option value='{$row['id']}' $selected>{$row['nome']}</option>";
          }
          ?>
        </select>
      </div>

      <div class="col-12">
        <label class="form-label">Descrição</label>
        <textarea name="descricao" class="form-control" rows="2"><?= $transacao['descricao'] ?></textarea>
      </div>

      <div class="col-12">
        <button type="submit" class="btn btn-primary w-100">💾 Salvar Alterações</button>
      </div>
    </form>
  </div>
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