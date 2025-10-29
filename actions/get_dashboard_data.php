<?php
header('Content-Type: application/json');
require_once '../includes/auth_check.php';
require_once '../config/config.php';

$mes_ano = $_GET['mes_ano'] ?? date('Y-m');
$user_ids_selecionados = $_GET['usuarios'] ?? [];

$ano = date('Y', strtotime($mes_ano));
$mes = date('m', strtotime($mes_ano));

// Garante que o usuário de visualização só veja seus próprios dados
if ($user_tipo === 'visualizacao') {
    $user_ids_selecionados = [$user_id];
}

if (empty($user_ids_selecionados)) {
    // Se nenhum usuário for selecionado (e for admin), busca de todos
    $stmt_all_users = $pdo->query("SELECT id FROM usuarios");
    $user_ids_selecionados = $stmt_all_users->fetchAll(PDO::FETCH_COLUMN);
}

if (empty($user_ids_selecionados)) {
    // Se ainda estiver vazio (nenhum usuário no DB), retorna uma estrutura vazia para não dar erro.
    echo json_encode(['summary' => ['totalGanhos' => 0, 'totalDespesas' => 0, 'saldo' => 0], 'breakdown' => ['labels' => [], 'datasets' => []]]);
    exit();
}
// Cria os placeholders para a cláusula IN (...)
$in_placeholders = implode(',', array_fill(0, count($user_ids_selecionados), '?'));

// --- Consulta de Ganhos (Apenas para usuários ADMIN selecionados) ---
$sql_admins_selecionados = "SELECT id FROM usuarios WHERE tipo_acesso = 'admin' AND id IN ($in_placeholders)";
$stmt_admins = $pdo->prepare($sql_admins_selecionados);
$stmt_admins->execute($user_ids_selecionados);
$admin_ids_selecionados = $stmt_admins->fetchAll(PDO::FETCH_COLUMN);

$ganhos = [];
if (!empty($admin_ids_selecionados)) {
    $in_placeholders_admins = implode(',', array_fill(0, count($admin_ids_selecionados), '?'));
    $sql_ganhos = "
        SELECT u.nome, COALESCE(SUM(g.valor), 0) as total
        FROM usuarios u
        LEFT JOIN ganhos g ON u.id = g.usuario_id AND YEAR(g.data_ganho) = ? AND MONTH(g.data_ganho) = ?
        WHERE u.id IN ($in_placeholders_admins)
        GROUP BY u.id, u.nome
        ORDER BY u.nome
    ";
    $params_ganhos = array_merge([$ano, $mes], $admin_ids_selecionados);
    $stmt_ganhos = $pdo->prepare($sql_ganhos);
    $stmt_ganhos->execute($params_ganhos);
    $ganhos = $stmt_ganhos->fetchAll(PDO::FETCH_KEY_PAIR);
}

// --- Consulta de Despesas ---
$sql_despesas = "
    SELECT u.nome, COALESCE(SUM(d.valor), 0) as total
    FROM usuarios u
    LEFT JOIN despesas d ON u.id = d.dono_divida_id AND YEAR(d.data_despesa) = ? AND MONTH(d.data_despesa) = ?
    WHERE u.id IN ($in_placeholders)
    GROUP BY u.id, u.nome
    ORDER BY u.nome
";
$params_despesas = array_merge([$ano, $mes], $user_ids_selecionados);
$stmt_despesas = $pdo->prepare($sql_despesas);
$stmt_despesas->execute($params_despesas);
$despesas = $stmt_despesas->fetchAll(PDO::FETCH_KEY_PAIR);

// --- Monta a estrutura de dados para o Chart.js ---
if ($user_tipo === 'admin') {
    $total_ganhos = array_sum($ganhos);
    $total_despesas = array_sum($despesas);
    $response_data = [
        'summary' => [
            'totalGanhos' => $total_ganhos,
            'totalDespesas' => $total_despesas,
            'saldo' => $total_ganhos - $total_despesas,
        ],
        'pieChart' => [
            'labels' => ['Ganhos', 'Despesas'],
            'datasets' => [[
                'data' => [$total_ganhos, $total_despesas],
                'backgroundColor' => ['rgba(75, 192, 192, 0.8)', 'rgba(255, 99, 132, 0.8)'],
                'borderColor' => ['rgba(75, 192, 192, 1)', 'rgba(255, 99, 132, 1)'],
                'borderWidth' => 1
            ]]
        ],
        'barChart' => [
            'labels' => array_keys($ganhos),
            'datasets' => [
                [
                    'label' => 'Ganhos',
                    'data' => array_values($ganhos),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1
                ],
                [
                    'label' => 'Despesas',
                    'data' => array_values($despesas),
                    'backgroundColor' => 'rgba(255, 159, 64, 0.6)',
                    'borderColor' => 'rgba(255, 159, 64, 1)',
                    'borderWidth' => 1
                ]
            ]
        ]
    ];
} else { // Usuário de visualização
    // Para o usuário de visualização, o gráfico de barras mostrará as despesas por método de pagamento
    // A consulta de despesas já foi feita, então podemos reutilizar o resultado.
    // Como para 'visualizacao' o $user_ids_selecionados só tem o ID dele, o array $despesas terá apenas um item.
    $total_despesas_usuario = !empty($despesas) ? current($despesas) : 0;

    $response_data = [
        'summary' => [
            'totalGanhos' => 0,
            'totalDespesas' => $total_despesas_usuario,
            'saldo' => -$total_despesas_usuario,
        ],
        'pieChart' => null, // Não haverá gráfico de pizza
        'barChart' => [
            // Para o usuário de visualização, o gráfico de barras não é relevante da mesma forma.
            // Vamos deixar vazio por enquanto, ou você pode definir um gráfico específico para ele aqui.
            // Por exemplo, despesas por categoria, se essa informação existir.
            // Por simplicidade, retornaremos um gráfico vazio.
            'labels' => [],
            'datasets' => [
                ['label' => 'Minhas Despesas', 
                    'data' => [],
                    'backgroundColor' => 'rgba(255, 159, 64, 0.6)',
                    'borderColor' => 'rgba(255, 159, 64, 1)',
                    'borderWidth' => 1
                ]
            ]
        ]
    ]
];

echo json_encode($response_data);
?>