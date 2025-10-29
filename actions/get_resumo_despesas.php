<?php
header('Content-Type: application/json');
require_once '../includes/auth_check.php';
require_once '../config/config.php';

$periodo = $_GET['periodo'] ?? 'mensal';
$mes_ano = $_GET['mes_ano'] ?? date('Y-m');
$user_ids_selecionados = $_GET['usuarios'] ?? [];

$ano = date('Y', strtotime($mes_ano));
$mes = date('m', strtotime($mes_ano));

// Garante que o usuário de visualização só veja seus próprios dados
if ($user_tipo === 'visualizacao') {
    $user_ids_selecionados = [$user_id];
}

// Se for admin e nenhum usuário for selecionado, busca de todos
if ($user_tipo === 'admin' && empty($user_ids_selecionados)) {
    $stmt_all_users = $pdo->query("SELECT id FROM usuarios");
    $user_ids_selecionados = $stmt_all_users->fetchAll(PDO::FETCH_COLUMN);
}

if (empty($user_ids_selecionados)) {
    echo json_encode([]);
    exit();
}

$where_clauses = [];
$params = [];

// Cria os placeholders para a cláusula IN (...)
$in_placeholders = implode(',', array_fill(0, count($user_ids_selecionados), '?'));
$where_clauses[] = "d.dono_divida_id IN ($in_placeholders)";
$params = array_merge($params, $user_ids_selecionados);

if ($periodo === 'mensal') {
    $where_clauses[] = "YEAR(d.data_despesa) = ?";
    $params[] = $ano;
    $where_clauses[] = "MONTH(d.data_despesa) = ?";
    $params[] = $mes;
} else { // anual
    $where_clauses[] = "YEAR(d.data_despesa) = ?";
    $params[] = $ano;
}

$sql = "
    SELECT 
        u.nome, 
        COALESCE(SUM(d.valor), 0) as total_despesas
    FROM usuarios u
    LEFT JOIN despesas d ON u.id = d.dono_divida_id
    WHERE " . implode(' AND ', $where_clauses) . "
    GROUP BY u.id, u.nome
    HAVING total_despesas > 0
    ORDER BY u.nome
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($resultado);
?>