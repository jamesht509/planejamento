<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
include 'conexao.php';

// Pegar os ganhos
$stmt = $pdo->query("
    SELECT ganhos.data, atividades.nome AS atividade, ganhos.valor_usd
    FROM ganhos
    JOIN atividades ON ganhos.atividade_id = atividades.id
    ORDER BY ganhos.data DESC
");

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=ganhos_exportados.csv');

$output = fopen('php://output', 'w');

// Cabeçalho
fputcsv($output, ['Data', 'Atividade', 'Valor USD']);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        date('d/m/Y', strtotime($row['data'])),
        $row['atividade'],
        number_format($row['valor_usd'], 2, '.', '')
    ]);
}

fclose($output);
exit;
?>