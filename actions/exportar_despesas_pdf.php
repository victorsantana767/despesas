<?php
require_once '../includes/auth_check.php';
require_once '../config/config.php';
require_once '../includes/helpers.php';
require_once '../includes/PDF.php';

// --- Lógica de Filtragem (idêntica a pages/despesas/index.php) ---
$filtro_mes_ano = $_GET['mes_ano'] ?? date('Y-m');
$filtro_usuario_ids = isset($_GET['usuario_id']) && is_array($_GET['usuario_id']) ? $_GET['usuario_id'] : [];
$filtro_status = $_GET['status'] ?? null;
$output_mode = $_GET['output'] ?? 'D'; // D para Download, I para Inline (visualizar)

$data_inicio = date('Y-m-01', strtotime($filtro_mes_ano));
$data_fim = date('Y-m-t', strtotime($filtro_mes_ano));

$sql_base = "FROM despesas d
    JOIN usuarios dono ON d.dono_divida_id = dono.id
    JOIN usuarios comprador ON d.comprador_id = comprador.id
    LEFT JOIN cartoes c ON d.cartao_id = c.id";

$sql_select = "
    SELECT
        d.descricao, d.valor, d.data_despesa, d.status,
        dono.nome AS dono_divida_nome,
        comprador.nome AS comprador_nome
";
$where_clauses = [];
$params = [];

$where_clauses[] = "(d.data_despesa BETWEEN ? AND ?)";
$params[] = $data_inicio;
$params[] = $data_fim;

if ($user_tipo === 'visualizacao') {
    $where_clauses[] = "d.dono_divida_id = ?";
    $params[] = $user_id;
} elseif (!empty($filtro_usuario_ids)) {
    // Cria placeholders (?) para cada ID de usuário selecionado
    $placeholders = implode(',', array_fill(0, count($filtro_usuario_ids), '?'));
    $where_clauses[] = "d.dono_divida_id IN ($placeholders)";
    foreach ($filtro_usuario_ids as $uid) {
        $params[] = $uid;
    }
}

if (!empty($filtro_status)) {
    if ($filtro_status === 'atrasado') {
        $where_clauses[] = "d.status = 'pendente' AND d.data_despesa < CURDATE()";
    } elseif ($filtro_status === 'pendente') {
        $where_clauses[] = "d.status = 'pendente' AND d.data_despesa >= CURDATE()";
    } else {
        $where_clauses[] = "d.status = ?";
        $params[] = $filtro_status;
    }
}

$sql_where = " WHERE " . implode(" AND ", $where_clauses);
$sql_final = $sql_select . $sql_base . $sql_where . " ORDER BY d.data_despesa DESC";

$stmt = $pdo->prepare($sql_final);
$stmt->execute($params);
$despesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Busca nome(s) do(s) usuário(s) para o cabeçalho ---
$info_pessoa = 'Todas as Pessoas';
if ($user_tipo === 'visualizacao') {
    $info_pessoa = $user_nome;
} elseif (!empty($filtro_usuario_ids)) {
    $placeholders = implode(',', array_fill(0, count($filtro_usuario_ids), '?'));
    $stmt_nomes = $pdo->prepare("SELECT nome FROM usuarios WHERE id IN ($placeholders)");
    $stmt_nomes->execute($filtro_usuario_ids);
    $nomes = $stmt_nomes->fetchAll(PDO::FETCH_COLUMN);
    if (count($nomes) > 0) {
        $info_pessoa = implode(', ', $nomes);
    }
}


// --- Geração do PDF ---
$pdf = new PDF('P', 'mm', 'A4'); // P = Portrait
$pdf->AliasNbPages();
$pdf->SetTitle("Relatório de Despesas");
$pdf->AddPage();

// Cabeçalho do relatório
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0, 8, $pdf->TextToCell('Período: ' . date('m/Y', strtotime($filtro_mes_ano))), 0, 1, 'L');
$pdf->SetFont('Arial','',10);
$pdf->Cell(0, 6, $pdf->TextToCell('Pessoa(s): ' . $info_pessoa), 0, 1, 'L');
$pdf->Ln(8);

// Cabeçalho da Tabela
$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(230,230,230);
$pdf->Cell(70, 7, $pdf->TextToCell('Descrição'), 1, 0, 'L', true);
$pdf->Cell(25, 7, 'Valor (R$)', 1, 0, 'C', true);
$pdf->Cell(25, 7, 'Data', 1, 0, 'C', true);
$pdf->Cell(40, 7, $pdf->TextToCell('Dono da Dívida'), 1, 0, 'L', true);
$pdf->Cell(30, 7, 'Status', 1, 1, 'C', true);

// Corpo da Tabela
$pdf->SetFont('Arial','',9);
$total_valor = 0;

foreach ($despesas as $despesa) {
    $pdf->Cell(70, 8, $pdf->TextToCell($despesa['descricao']), 1);
    $pdf->Cell(25, 8, number_format($despesa['valor'], 2, ',', '.'), 1, 0, 'R');
    $pdf->Cell(25, 8, date('d/m/Y', strtotime($despesa['data_despesa'])), 1, 0, 'C');
    $pdf->Cell(40, 8, $pdf->TextToCell($despesa['dono_divida_nome']), 1);
    $pdf->StatusCell(30, 8, $despesa['status'], $despesa['data_despesa'], 1, 1);
    $total_valor += $despesa['valor'];
}

// Totalizador
$pdf->SetFont('Arial','B',10);
$pdf->Cell(70, 7, 'Total', 1, 0, 'R', true);
$pdf->Cell(25, 7, number_format($total_valor, 2, ',', '.'), 1, 1, 'R', true);

$pdf->Output($output_mode, 'despesas_'. $filtro_mes_ano .'.pdf');
exit();
?>