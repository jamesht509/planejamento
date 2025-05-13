<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
include 'conexao.php';

// Buscar ganhos do banco para o calendário
$stmt = $pdo->query("
    SELECT ganhos.data, atividades.nome, ganhos.valor_usd
    FROM ganhos
    JOIN atividades ON ganhos.atividade_id = atividades.id
");

$eventos = [];
while ($row = $stmt->fetch()) {
    $eventos[] = [
        'title' => $row['nome'] . ' - $' . number_format($row['valor_usd'], 2),
        'start' => $row['data'],
        'allDay' => true
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <style>
/* Tema Claro (default) */
body {
    background-color: #f8f9fa;
    color: #111;
}
.card, .navbar, .form-control, .form-select {
    background-color: #fff;
    color: #111;
}
.dark-mode {
    background-color: #121212 !important;
    color: #f1f1f1 !important;
}
.dark-mode .card, 
.dark-mode .navbar,
.dark-mode .form-control,
.dark-mode .form-select {
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
    position: fixed;
    top: 12px;
    right: 12px;
    z-index: 9999;
    border-radius: 30px;
    font-size: 18px;
    background: #eee;
    padding: 6px 12px;
    border: none;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}
.dark-mode .theme-toggle {
    background: #333;
    color: #fff;
}
</style>
    <meta charset="UTF-8">
    <title>Calendário de Ganhos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FullCalendar CSS + JS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

    <style>
        body {
            background-color: #f8f9fa;
            font-size: 16px;
        }
        #calendar {
            max-width: 100%;
            margin: auto;
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
    <h4 class="mb-4 text-center">📅 Calendário de Ganhos</h4>
    <div id="calendar"></div>
</div>

<!-- MENU FIXO MOBILE -->
<div class="fixed-nav">
    <a href="index.php">
        <div>🏠<span>Resumo</span></div>
    </a>
    <a href="ganhos.php">
        <div>💵<span>Ganho</span></div>
    </a>
    <a href="nova_atividade.php">
        <div>➕<span>Atividade</span></div>
    </a>
    <a href="listar_ganhos.php">
        <div>📄<span>Ganhos</span></div>
    </a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt-br',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        events: <?= json_encode($eventos) ?>
    });

    calendar.render();
});
</script>
<script>
function toggleTheme() {
    const body = document.body;
    body.classList.toggle("dark-mode");

    const isDark = body.classList.contains("dark-mode");
    localStorage.setItem("modo_escuro", isDark);

    document.querySelector(".theme-toggle").innerText = isDark ? "🔆" : "🌙";
}

// Carregar preferência ao entrar na página
document.addEventListener("DOMContentLoaded", () => {
    const isDark = localStorage.getItem("modo_escuro") === "true";
    if (isDark) {
        document.body.classList.add("dark-mode");
        document.querySelector(".theme-toggle").innerText = "🔆";
    }
});
</script>
</body
<button class="theme-toggle" onclick="toggleTheme()">🌙</button>
</html>