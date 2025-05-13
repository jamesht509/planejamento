<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

include 'conexao.php';

$email = $_SESSION["usuario"];
$mensagem = '';
$mensagem_tipo = 'success'; // To control alert class (success, danger, warning)

// Fetch current user data
$stmt_fetch = $pdo->prepare("SELECT nome, meta_valor, meta_prazo, segredo_2fa FROM usuarios WHERE email = ?");
$stmt_fetch->execute([$email]);
$dados_usuario = $stmt_fetch->fetch();

$nomeAtual = $dados_usuario["nome"] ?? explode('@', $email)[0];
$meta_valor_atual = $dados_usuario["meta_valor"] ?? 1000000;
$meta_prazo_atual = $dados_usuario["meta_prazo"] ?? 36;
$segredo_2fa_existe = !empty($dados_usuario["segredo_2fa"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["atualizar_perfil"])) {
        $novoNome = trim($_POST["nome"]);
        // Meta update
        $nova_meta_valor_str = str_replace(['.', ','], ['', '.'], $_POST["meta_valor"]);
        $nova_meta_valor = filter_var($nova_meta_valor_str, FILTER_VALIDATE_FLOAT);
        $nova_meta_prazo = filter_var($_POST["meta_prazo"], FILTER_VALIDATE_INT);

        if (empty($novoNome)) {
            $mensagem = "O nome não pode estar vazio.";
            $mensagem_tipo = "danger";
        } elseif ($nova_meta_valor === false || $nova_meta_valor <= 0) {
            $mensagem = "Valor da meta inválido.";
            $mensagem_tipo = "danger";
        } elseif ($nova_meta_prazo === false || $nova_meta_prazo <= 0) {
            $mensagem = "Prazo da meta inválido.";
            $mensagem_tipo = "danger";
        } else {
            $stmt_update = $pdo->prepare("UPDATE usuarios SET nome = ?, meta_valor = ?, meta_prazo = ? WHERE email = ?");
            if ($stmt_update->execute([$novoNome, $nova_meta_valor, $nova_meta_prazo, $email])) {
                $nomeAtual = $novoNome;
                $meta_valor_atual = $nova_meta_valor;
                $meta_prazo_atual = $nova_meta_prazo;
                $mensagem = "Perfil e meta atualizados com sucesso!";
                $mensagem_tipo = "success";
            } else {
                $mensagem = "Erro ao atualizar o perfil. Tente novamente.";
                $mensagem_tipo = "danger";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Meu Perfil - Painel Financeiro</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f4f7f6;
      color: #333;
      padding-bottom: 100px; /* Space for fixed nav */
    }
    .dark-mode {
      background-color: #1a1a1a !important;
      color: #e0e0e0 !important;
    }
    .dark-mode .card, .dark-mode .form-control, .dark-mode .form-select {
      background-color: #2c2c2c !important;
      border-color: #444 !important;
      color: #e0e0e0 !important;
    }
    .dark-mode .form-control::placeholder {
        color: #bbb;
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
      color: #00c875 !important;
    }
    .dark-mode .btn-primary-custom {
        background-color: #00c875;
        border-color: #00c875;
        color: #fff;
    }
    .dark-mode .btn-primary-custom:hover {
        background-color: #00a060;
        border-color: #00a060;
    }
    .dark-mode .btn-outline-danger-custom {
        color: #f16272;
        border-color: #f16272;
    }
    .dark-mode .btn-outline-danger-custom:hover {
        background-color: #f16272;
        color: #1a1a1a;
    }
    .dark-mode .btn-info-custom {
        background-color: #17a2b8; /* Bootstrap info color */
        border-color: #17a2b8;
        color: #fff;
    }
    .dark-mode .btn-info-custom:hover {
        background-color: #138496;
        border-color: #117a8b;
    }

    .navbar-custom {
      background-color: #ffffff;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .navbar-brand-custom {
      font-weight: 600;
      color: #00c875 !important;
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

    .form-card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      padding: 2rem;
    }
    .form-label {
        font-weight: 500;
        margin-bottom: 0.5rem;
    }
    .form-control, .form-select {
        border-radius: 8px;
        padding: 0.8rem 1rem;
        border: 1px solid #ced4da;
    }
    .form-control:focus, .form-select:focus {
        border-color: #00c875;
        box-shadow: 0 0 0 0.2rem rgba(0, 200, 117, 0.25);
    }
    .btn-primary-custom {
        background-color: #00c875;
        border-color: #00c875;
        padding: 0.8rem 1.5rem;
        font-weight: 600;
        border-radius: 8px;
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }
    .btn-primary-custom:hover {
        background-color: #00a060;
        border-color: #00a060;
    }
    .btn-outline-danger-custom {
        padding: 0.8rem 1.5rem;
        font-weight: 600;
        border-radius: 8px;
        transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
    }
    .btn-info-custom {
        padding: 0.8rem 1.5rem;
        font-weight: 600;
        border-radius: 8px;
        transition: background-color 0.2s ease, border-color 0.2s ease;
        background-color: #17a2b8;
        border-color: #17a2b8;
        color: #fff;
    }
    .btn-info-custom:hover {
        background-color: #138496;
        border-color: #117a8b;
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
      font-size: 0.75rem;
      padding: 0.3rem 0;
    }
    .fixed-nav a i {
      font-size: 1.3rem;
      display: block;
      margin-bottom: 0.2rem;
    }
    .fixed-nav a.active {
      color: #00c875;
      font-weight: 600;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-custom sticky-top">
  <div class="container-fluid px-3">
    <a class="navbar-brand navbar-brand-custom" href="index.php">
      <i class="fas fa-user-cog me-2"></i>Meu Perfil
    </a>
    <div class="d-flex align-items-center">
      <a href="perfil.php" class="profile-link me-3 active">
        <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($nomeAtual); ?>
      </a>
      <button class="theme-toggle" onclick="toggleTheme()" aria-label="Alternar tema">
        <i class="fas fa-moon"></i>
      </button>
    </div>
  </div>
</nav>

<div class="container mt-4 px-3">
  <h1 class="main-header text-center mb-4">Configurações do Perfil</h1>

  <?php if ($mensagem): ?>
    <div class="row justify-content-center mb-3">
        <div class="col-md-8 col-lg-6">
            <div class="alert alert-<?= $mensagem_tipo == 'success' ? 'success' : 'danger' ?> text-center" role="alert">
                <i class="fas <?= $mensagem_tipo == 'success' ? 'fa-check-circle' : 'fa-times-circle' ?> me-2"></i><?= htmlspecialchars($mensagem) ?>
            </div>
        </div>
    </div>
  <?php endif; ?>

  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
      <div class="card form-card mb-4">
        <form method="POST">
          <input type="hidden" name="atualizar_perfil" value="1">
          <h5 class="mb-3"><i class="fas fa-user-edit me-2"></i>Informações Pessoais</h5>
          <div class="mb-3">
            <label for="nome" class="form-label">Nome de Exibição:</label>
            <input type="text" name="nome" id="nome" class="form-control" value="<?= htmlspecialchars($nomeAtual) ?>" required>
          </div>
          
          <h5 class="mt-4 mb-3"><i class="fas fa-bullseye me-2"></i>Minha Meta Financeira</h5>
          <div class="mb-3">
            <label for="meta_valor" class="form-label">Valor da Meta (R$):</label>
            <div class="input-group">
                <span class="input-group-text">R$</span>
                <input type="text" name="meta_valor" id="meta_valor" class="form-control" 
                       value="<?= number_format($meta_valor_atual, 2, ',', '.') ?>" placeholder="Ex: 1.000.000,00" required>
            </div>
          </div>
          <div class="mb-4">
            <label for="meta_prazo" class="form-label">Prazo da Meta (em meses):</label>
            <input type="number" name="meta_prazo" id="meta_prazo" class="form-control" value="<?= $meta_prazo_atual ?>" placeholder="Ex: 36" required>
          </div>
          <button type="submit" class="btn btn-primary-custom w-100"><i class="fas fa-save me-2"></i>Salvar Alterações</button>
        </form>
      </div>

      <div class="card form-card mb-4">
        <h5 class="mb-3"><i class="fas fa-shield-alt me-2"></i>Segurança</h5>
        <a href="ativar_2fa.php" class="btn <?= $segredo_2fa_existe ? 'btn-outline-secondary-custom disabled' : 'btn-info-custom' ?> w-100 mb-3" <?= $segredo_2fa_existe ? 'aria-disabled="true"' : '' ?>>
            <i class="fas fa-key me-2"></i><?= $segredo_2fa_existe ? 'Autenticação 2FA Ativada' : 'Ativar Autenticação de Dois Fatores (2FA)' ?>
        </a>
        <small class="text-muted d-block text-center">
            <?= $segredo_2fa_existe ? 'Para desativar ou reconfigurar o 2FA, entre em contato com o suporte.' : 'Aumente a segurança da sua conta ativando o 2FA.' ?>
        </small>
      </div>

      <div class="card form-card text-center">
         <h5 class="mb-3"><i class="fas fa-sign-out-alt me-2"></i>Sessão</h5>
        <form action="logout.php" method="POST">
            <button type="submit" class="btn btn-outline-danger-custom w-100"><i class="fas fa-door-open me-2"></i>Sair do Sistema</button>
        </form>
      </div>

    </div>
  </div>
</div>

<div class="fixed-nav">
  <a href="index.php"><i class="fas fa-home"></i><span>Resumo</span></a>
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
  }

  function toggleTheme() {
    const isDark = document.body.classList.contains('dark-mode');
    applyTheme(!isDark);
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('darkMode') === 'enabled') {
      applyTheme(true);
    }
    // Update active link in fixed-nav
    const currentPath = window.location.pathname.split('/').pop();
    document.querySelectorAll('.fixed-nav a').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
    // Perfil page doesn't have a direct link in the standard nav, so no specific active state here for it.
    // The top nav profile link is already styled as active on this page.
  });
</script>

</body>
</html>

