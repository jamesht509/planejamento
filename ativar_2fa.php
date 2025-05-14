<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit;
}

// Verificar existência das dependências
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die("Erro: Arquivo vendor/autoload.php não encontrado. Execute 'composer install' para instalar as dependências.");
}

require_once $autoloadPath;
require_once "conexao.php";

// Verificar se as classes necessárias estão disponíveis
if (!class_exists('OTPHP\TOTP')) {
    die("Erro: A classe OTPHP\TOTP não está disponível. Verifique se a biblioteca OTPHP está instalada corretamente.");
}

if (!class_exists('Endroid\QrCode\Builder\Builder')) {
    die("Erro: A classe Endroid\QrCode\Builder\Builder não está disponível. Verifique se a biblioteca endroid/qr-code está instalada corretamente.");
}

use OTPHP\TOTP;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;

$email = $_SESSION["usuario"];
$mensagem = "";
$debugInfo = "";
$qrCodeDataUri = "";

try {
    // Verificar conexão com o banco de dados
    if (!$pdo) {
        throw new Exception("Erro: Conexão com o banco de dados não estabelecida.");
    }
    
    // Verificar se a tabela e o campo existem
    try {
        $checkField = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'segredo_2fa'");
        if ($checkField->rowCount() === 0) {
            throw new Exception("Erro: O campo 'segredo_2fa' não existe na tabela 'usuarios'. Adicione este campo à tabela.");
        }
    } catch (PDOException $e) {
        throw new Exception("Erro ao verificar estrutura da tabela: " . $e->getMessage());
    }

    // Buscar segredo no banco
    $stmt = $pdo->prepare("SELECT segredo_2fa FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("Erro: Usuário com email '$email' não encontrado no banco de dados.");
    }

    $secret = $user['segredo_2fa'];
    
    // Se não houver segredo ou for nulo, crie um novo
    if (empty($secret)) {
        $totp = TOTP::create();
        $totp->setLabel("Painel360:$email");
        $totp->setIssuer('Painel360');
        $secret = $totp->getSecret();
        
        // Atualizar o banco de dados com o novo segredo
        $stmt = $pdo->prepare("UPDATE usuarios SET segredo_2fa = ? WHERE email = ?");
        if (!$stmt->execute([$secret, $email])) {
            throw new Exception("Erro ao salvar o segredo 2FA no banco de dados.");
        }
        
        $debugInfo .= "Novo segredo 2FA gerado e salvo no banco de dados.<br>";
    } else {
        $debugInfo .= "Segredo 2FA existente encontrado no banco de dados.<br>";
    }
    
    // Criar objeto TOTP com o segredo existente ou novo
    $totp = TOTP::create($secret);
    $totp->setLabel("Painel360:$email");
    $totp->setIssuer('Painel360');
    
    // Gerar QR Code
    $qrCodeUrl = $totp->getProvisioningUri();
    $debugInfo .= "URI de provisionamento gerada: " . htmlspecialchars($qrCodeUrl) . "<br>";
    
    $result = Builder::create()
        ->writer(new PngWriter())
        ->data($qrCodeUrl)
        ->size(250)
        ->margin(10)
        ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
        ->build();
    
    $qrCodeDataUri = $result->getDataUri();
    $debugInfo .= "QR Code gerado com sucesso.<br>";

    // Verificar código
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $codigo = $_POST["codigo"] ?? "";
        
        if (empty($codigo)) {
            $mensagem = "❌ Por favor, digite um código.";
        } elseif (!ctype_digit($codigo)) {
            $mensagem = "❌ O código deve conter apenas dígitos.";
        } elseif (strlen($codigo) != 6) {
            $mensagem = "❌ O código deve ter exatamente 6 dígitos.";
        } elseif ($totp->verify($codigo)) {
            // Código válido
            $_SESSION["2fa_verified"] = true;
            $debugInfo .= "Código verificado com sucesso.<br>";
            header("Location: index.php");
            exit;
        } else {
            // Código inválido
            $mensagem = "❌ Código inválido. Tente novamente.";
            $debugInfo .= "Verificação de código falhou. Código fornecido: $codigo<br>";
        }
    }
} catch (Exception $e) {
    $mensagem = "Erro: " . $e->getMessage();
    $debugInfo .= "Exceção capturada: " . $e->getMessage() . "<br>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Ativar Autenticação 2FA</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #00c875;
      --secondary-color: #3498db;
      --danger-color: #e74c3c;
      --warning-color: #f39c12;
      --dark-bg: #121212;
      --dark-card: #1e1e1e;
      --light-bg: #f4f7f6;
      --light-text: #f1f1f1;
      --dark-text: #333;
      --border-radius: 16px;
      --box-shadow: 0 8px 30px rgba(0,0,0,0.1);
      --transition: all 0.3s ease;
    }
    
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background-color: var(--light-bg);
      color: var(--dark-text);
      transition: var(--transition);
      min-height: 100vh;
    }
    
    .dark-mode {
      background-color: var(--dark-bg);
      color: var(--light-text);
    }
    
    .container-2fa {
      max-width: 500px;
      margin: 60px auto;
      background: #fff;
      padding: 30px;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      transition: var(--transition);
    }
    
    .dark-mode .container-2fa {
      background: var(--dark-card);
      box-shadow: 0 8px 30px rgba(0,0,0,0.3);
    }
    
    .header-2fa {
      font-weight: 700;
      font-size: 1.8rem;
      margin-bottom: 1.5rem;
      color: var(--dark-text);
    }
    
    .dark-mode .header-2fa {
      color: var(--light-text);
    }
    
    .qr-container {
      background-color: #fff;
      padding: 15px;
      border-radius: 12px;
      display: inline-block;
      margin: 15px 0;
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }
    
    .dark-mode .qr-container {
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .form-control {
      border-radius: 8px;
      padding: 0.8rem 1rem;
      border: 1px solid #ced4da;
      font-size: 1.2rem;
      letter-spacing: 3px;
      text-align: center;
      transition: var(--transition);
    }
    
    .form-control:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(0, 200, 117, 0.25);
    }
    
    .dark-mode .form-control {
      background-color: #2c2c2c;
      color: #fff;
      border-color: #444;
    }
    
    .dark-mode .form-control::placeholder {
      color: #bbb;
    }
    
    .btn-primary-custom {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
      padding: 0.8rem 1.5rem;
      font-weight: 600;
      border-radius: 8px;
      transition: var(--transition);
    }
    
    .btn-primary-custom:hover {
      background-color: #00a060;
      border-color: #00a060;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .dark-mode .btn-primary-custom {
      box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    }
    
    .theme-toggle {
      position: fixed;
      top: 20px;
      right: 20px;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: transparent;
      border: 1px solid rgba(0,0,0,0.1);
      color: inherit;
      cursor: pointer;
      transition: var(--transition);
      z-index: 100;
    }
    
    .theme-toggle:hover {
      background: rgba(0,0,0,0.05);
      transform: scale(1.05);
    }
    
    .dark-mode .theme-toggle {
      border-color: rgba(255,255,255,0.2);
    }
    
    .dark-mode .theme-toggle:hover {
      background: rgba(255,255,255,0.1);
    }
    
    .alert {
      border-radius: 10px;
      padding: 1rem;
      margin-top: 1rem;
      position: relative;
      overflow: hidden;
    }
    
    .alert.alert-danger {
      background-color: rgba(231, 76, 60, 0.1);
      border: 1px solid rgba(231, 76, 60, 0.3);
      color: #e74c3c;
    }
    
    .dark-mode .alert.alert-danger {
      background-color: rgba(231, 76, 60, 0.2);
      border: 1px solid rgba(231, 76, 60, 0.4);
      color: #ff6b5b;
    }
    
    .debug-info {
      margin-top: 30px;
      padding: 15px;
      background-color: rgba(0,0,0,0.05);
      border-radius: 10px;
      font-family: monospace;
      font-size: 0.9rem;
      color: #666;
      white-space: pre-wrap;
      overflow-x: auto;
      position: relative;
    }
    
    .dark-mode .debug-info {
      background-color: rgba(255,255,255,0.05);
      color: #aaa;
    }
    
    .debug-title {
      position: absolute;
      top: 10px;
      right: 10px;
      font-size: 0.8rem;
      color: #999;
    }
    
    .secret-display {
      background-color: rgba(0,0,0,0.05);
      padding: 10px;
      border-radius: 5px;
      font-family: monospace;
      margin: 10px 0;
      word-break: break-all;
    }
    
    .dark-mode .secret-display {
      background-color: rgba(255,255,255,0.05);
    }
    
    .step-item {
      margin-bottom: 15px;
      position: relative;
      padding-left: 30px;
    }
    
    .step-number {
      position: absolute;
      left: 0;
      top: 0;
      width: 24px;
      height: 24px;
      background-color: var(--primary-color);
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 0.8rem;
    }
  </style>
</head>
<body>
<button class="theme-toggle" onclick="toggleTheme()" aria-label="Alternar tema">
  <i class="fas fa-moon"></i>
</button>

<div class="container-2fa text-center">
  <h2 class="header-2fa">
    <i class="fas fa-shield-alt me-2"></i>Ativar Autenticação 2FA
  </h2>
  
  <?php if (!empty($mensagem)): ?>
    <div class="alert alert-danger" role="alert">
      <i class="fas fa-exclamation-triangle me-2"></i>
      <?= htmlspecialchars($mensagem) ?>
    </div>
  <?php endif; ?>
  
  <div class="step-item">
    <div class="step-number">1</div>
    <p class="text-start">Instale um aplicativo autenticador no seu celular:</p>
    <div class="row justify-content-center">
      <div class="col-auto">
        <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank" class="btn btn-outline-secondary btn-sm m-1">
          <i class="fab fa-android me-1"></i> Google Authenticator
        </a>
      </div>
      <div class="col-auto">
        <a href="https://apps.apple.com/br/app/google-authenticator/id388497605" target="_blank" class="btn btn-outline-secondary btn-sm m-1">
          <i class="fab fa-apple me-1"></i> Google Authenticator
        </a>
      </div>
    </div>
  </div>
  
  <?php if (!empty($qrCodeDataUri)): ?>
    <div class="step-item">
      <div class="step-number">2</div>
      <p class="text-start">Escaneie o QR Code abaixo com seu aplicativo autenticador:</p>
      <div class="qr-container">
        <img src="<?= $qrCodeDataUri ?>" alt="QR Code do Google Authenticator" class="img-fluid">
      </div>
    </div>
    
    <div class="step-item">
      <div class="step-number">3</div>
      <p class="text-start">Ou adicione manualmente usando esta chave secreta:</p>
      <div class="secret-display"><?= $secret ?></div>
    </div>
    
    <div class="step-item">
      <div class="step-number">4</div>
      <p class="text-start">Digite o código de 6 dígitos gerado pelo aplicativo:</p>
      <form method="POST" class="mb-3">
        <div class="mb-3">
          <input type="text" name="codigo" id="codigo" maxlength="6" pattern="\d*" class="form-control" placeholder="000000" required autocomplete="off">
        </div>
        <button type="submit" class="btn btn-primary-custom w-100">
          <i class="fas fa-check-circle me-2"></i>Verificar Código
        </button>
      </form>
    </div>
  <?php endif; ?>
  
  <?php if (!empty($debugInfo) && isset($_GET['debug'])): ?>
    <div class="debug-info">
      <div class="debug-title">Debug Info</div>
      <?= $debugInfo ?>
    </div>
  <?php endif; ?>
</div>

<script>
// Toggle do tema escuro
const themeToggleBtn = document.querySelector('.theme-toggle i');

function applyTheme(isDark) {
  document.body.classList.toggle('dark-mode', isDark);
  themeToggleBtn.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
  localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

function toggleTheme() {
  const isDark = document.body.classList.contains('dark-mode');
  applyTheme(!isDark);
}

document.addEventListener('DOMContentLoaded', () => {
  if (localStorage.getItem('theme') === 'dark') {
    applyTheme(true);
  }
  
  // Foco automático no campo de código
  const codigoInput = document.getElementById('codigo');
  if (codigoInput) {
    codigoInput.focus();
  }
});
</script>
</body>
</html>