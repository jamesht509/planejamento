<?php
include 'conexao.php';

$stmt = $pdo->query("
    SELECT DATE_FORMAT(data, '%b %Y') AS mes, SUM(valor_usd) as total
    FROM ganhos
    GROUP BY YEAR(data), MONTH(data)
    ORDER BY data ASC
");

$meses = [];
$valores = [];

while ($row = $stmt->fetch()) {
    $meses[] = $row['mes'];
    $valores[] = $row['total'];
}

echo json_encode([
    'meses' => $meses,
    'valores' => $valores
]);
?>