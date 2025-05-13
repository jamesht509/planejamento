<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
include 'conexao.php';

// Filtros
$mes = $_GET['mes'] ?? date('m');
$ano = $_GET['ano'] ?? date('Y');

$stmt = $pdo->prepare("
    SELECT * FROM financeiro 
    WHERE MONTH(data_registro) = ? AND YEAR(data_registro) = ?
    ORDER BY data_registro DESC
");
$stmt->execute([$mes, $ano]);
$dados = $stmt->fetchAll();

$saldo_total = 0;
$divida_total = 0;

foreach ($dados as $d) {
    if ($d['tipo'] === 'saldo') {
        $saldo_total += $d['moeda'] === 'BRL' ? $d['valor'] / 5 : $d['valor'];
    } elseif ($d['tipo'] === 'despesa') {
        $divida_total += $d['moeda'] === 'BRL' ? $d['valor'] / 5 : $d['valor'];
    }
}

$disponivel = $saldo_total - $divida_total;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Resumo Financeiro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-size: 16px;
        }
        .fixed-nav {
            position: fixed;
            bottom: 0;
            width: 100%;
            background: #ffffff;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            z-index: 9999;
            border-top: 1px solid #dee2e6;
        }
        .fixed-nav a {
            flex: 1;
            text-align: center;
            font-size: 13px;
            text-decoration: none;
            color: #444;
        }
        .fixed-nav a span {
            display: block;
            margin-top: 4px;
        }
        @media (min-width: 768px) {
            .fixed-nav { display: none; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-primary mb-3">
    <div class="container-fluid px-3">
        <a class="navbar-brand" href="index.php">💼 Meu Painel</a>
        <span class="text-white">Olá, <?= $_SESSION['usuario']; ?>!</span>
    </div>
</nav>

<div class="container px-3">
    <h4 class="text-center mb-4">📊 Resumo Financeiro de <?= DateTime::createFromFormat('!m', $mes)->format('F') ?> / <?= $ano ?></h4>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-6">
            <select name="mes" class="form-select">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $mes == $m ? 'selected' : '' ?>>
                        <?= DateTime::createFromFormat('!m', $m)->format('F') ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-6">
            <select name="ano" class="form-select">
                <?php for ($a = date('Y'); $a >= 2023; $a--): ?>
                    <option value="<?= $a ?>" <?= $ano == $a ? 'selected' : '' ?>><?= $a ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-12">
            <button class="btn btn-primary w-100">Filtrar</button>
        </div>
    </form>

    <div class="card p-3 mb-4">
        <p><strong>💰 Saldos em dólar (convertido):</strong> $<?= number_format($saldo_total, 2) ?></p>
        <p><strong>💳 Dívidas em dólar (convertido):</strong> $<?= number_format($divida_total, 2) ?></p>
        <hr>
        <p><strong>✅ Total disponível:</strong> <span class="fw-bold text-<?= $disponivel >= 0 ? 'success' : 'danger' ?>">$<?= number_format($disponivel, 2) ?></span></p>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Descrição</th>
                    <th>Valor</th>
                    <th>Moeda</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dados as $d): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($d['data_registro'])) ?></td>
                        <td><?= $d['tipo'] === 'saldo' ? 'Saldo' : 'Despesa' ?></td>
                        <td><?= htmlspecialchars($d['descricao']) ?></td>
                        <td><?= number_format($d['valor'], 2) ?></td>
                        <td><?= $d['moeda'] ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MENU FIXO MOBILE -->
<div class="fixed-nav">
    <a href="index.php"><div>🏠<span>Resumo</span></div></a>
    <a href="ganhos.php"><div>💵<span>Ganho</span></div></a>
    <a href="nova_atividade.php"><div>➕<span>Atividade</span></div></a>
    <a href="listar_ganhos.php"><div>📄<span>Ganhos</span></div></a>
    <a href="financeiro.php"><div>💳<span>Financeiro</span></div></a>
</div>

</body>
</html>