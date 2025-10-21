<?php
require_once '../includes/auth_check.php';
require_once '../config/config.php';
require_once '../includes/helpers.php';
require_once '../includes/PDF.php';

// --- Lógica de Filtragem (idêntica a pages/relatorios/index.php) ---

// 1. Pegar os valores dos filtros
$filtro_usuario_id = $_GET['usuario_id'] ?? null;
$filtro_data_inicio = $_GET['data_inicio'] ?? null;
$filtro_data_fim = $_GET['data_fim'] ?? null;
$output_mode = $_GET['output'] ?? 'D'; // D para Download, I para Inline (visualizar)

// 2. Lógica de permissão
if ($user_tipo !== 'admin') {
    $filtro_usuario_id = $user_id;
}

// 3. Construir e executar a query
$sql_base = "
    SELECT 
        d.descricao, 
        d.valor, 
        d.data_despesa, 
        dono.nome AS dono_divida_nome,
        d.grupo_parcela_id,
        comprador.nome AS comprador_nome, 
        d.status,
        d.metodo_pagamento,
        c.nome_cartao
    FROM despesas d
    JOIN usuarios dono ON d.dono_divida_id = dono.id
    JOIN usuarios comprador ON d.comprador_id = comprador.id
    LEFT JOIN cartoes c ON d.cartao_id = c.id
";

$where_clauses = [];
$params = [];

if (!empty($filtro_usuario_id)) {
    $where_clauses[] = "d.dono_divida_id = :usuario_id";
    $params[':usuario_id'] = $filtro_usuario_id;
}
if (!empty($filtro_data_inicio)) {
    $where_clauses[] = "d.data_despesa >= :data_inicio";
    $params[':data_inicio'] = $filtro_data_inicio;
}
if (!empty($filtro_data_fim)) {
    $where_clauses[] = "d.data_despesa <= :data_fim";
    $params[':data_fim'] = $filtro_data_fim;
}

if (!empty($where_clauses)) {
    $sql_base .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql_base .= " ORDER BY d.data_despesa ASC";

$stmt = $pdo->prepare($sql_base);
$stmt->execute($params);
$despesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar despesas por compra
$compras = [];
foreach ($despesas as $despesa) {
    // Se for uma parcela, a chave é o grupo. Se for compra única, a chave é o próprio id.
    $key = $despesa['grupo_parcela_id'] ?? 'compra_unica_' . uniqid();

    if (!isset($compras[$key])) {
        // Remove o sufixo "(Parcela X/Y)" para ter a descrição base da compra
        $descricao_base = preg_replace('/ \(Parcela \d+\/\d+\)$/', '', $despesa['descricao']);
        
        $compras[$key] = [
            'descricao_base' => $descricao_base,
            'dono' => $despesa['dono_divida_nome'],
            'comprador' => $despesa['comprador_nome'],
            'total' => 0,
            'parcelas' => []
        ];
    }
    $compras[$key]['parcelas'][] = $despesa;
}

// --- Geração do PDF ---
$pdf = new PDF('P', 'mm', 'A4'); // P = Portrait (retrato)
$pdf->AliasNbPages();
$pdf->SetTitle("Relatório de Despesas", true);
$pdf->AddPage();

// Cabeçalho do relatório
$pdf->SetFont('Arial','B',12);
$periodo_str = 'Período: ';
$periodo_str .= !empty($filtro_data_inicio) ? date('d/m/Y', strtotime($filtro_data_inicio)) : 'N/A';
$periodo_str .= ' a ';
$periodo_str .= !empty($filtro_data_fim) ? date('d/m/Y', strtotime($filtro_data_fim)) : 'N/A';
$pdf->Cell(0, 8, $pdf->TextToCell($periodo_str), 0, 1, 'L');

if (!empty($filtro_usuario_id)) {
    $stmt_user = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
    $stmt_user->execute([$filtro_usuario_id]);
    $nome_usuario = $stmt_user->fetchColumn();
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(0, 6, $pdf->TextToCell($nome_usuario), 0, 1, 'L');
}
$pdf->Ln(8);

// Corpo da Tabela
$pdf->SetFont('Arial','',8);
$total_valor = 0;

foreach ($compras as $key => $compra) {
    $total_compra = array_sum(array_column($compra['parcelas'], 'valor'));
    $total_valor += $total_compra;
    $y_inicial_bloco = $pdf->GetY();
    
    // Cabeçalho do Bloco da Compra
    $pdf->SetFont('Arial','B',10);
    $pdf->SetFillColor(233, 236, 239); // Cinza (bg-light do Bootstrap)
    $pdf->SetTextColor(0);
    $pdf->RoundedRect($pdf->GetX(), $y_inicial_bloco, 190, 10, 4, 'F');
    $pdf->SetY($y_inicial_bloco + 1); // Ajuste para centralizar o texto verticalmente
    $pdf->Cell(150, 8, $pdf->TextToCell($compra['descricao_base']), 0, 0, 'L');
    $pdf->Cell(40, 8, 'R$ ' . number_format($total_compra, 2, ',', '.'), 0, 1, 'R');
    $pdf->SetY($y_inicial_bloco + 10); // Pula para depois do cabeçalho
    
    // Corpo do Bloco (Parcelas)
    $pdf->SetFont('Arial','',9);
    $pdf->SetTextColor(80, 80, 80);
    
    foreach ($compra['parcelas'] as $parcela) {
        $pdf->Cell(5); // Indentação
        $pdf->Cell(25, 7, date('d/m/Y', strtotime($parcela['data_despesa'])), 0, 0, 'L');
        
        // Se for parcela, mostra qual é. Se não, mostra o valor.
        $detalhe_descricao = preg_match('/\(Parcela \d+\/\d+\)$/', $parcela['descricao'], $matches) ? $matches[0] : 'R$ ' . number_format($parcela['valor'], 2, ',', '.');
        $pdf->Cell(95, 7, $pdf->TextToCell($detalhe_descricao), 0, 0, 'L');

        // Salva a posição Y atual para desenhar o status
        $status_y = $pdf->GetY();
        $pdf->Cell(35, 7, 'R$ ' . number_format($parcela['valor'], 2, ',', '.'), 0, 0, 'R');
        $status_x = $pdf->GetX(); // Pega a posição X APÓS o valor
        $pdf->Cell(30, 7, '', 0, 1); // Célula vazia para o status e para avançar a linha

        // Desenha o status
        $pdf->SetXY($status_x + 3, $status_y + 1);
        $pdf->StatusCell(22, 5, $parcela['status'], $parcela['data_despesa']);
        $pdf->SetXY(10, $status_y + 7); // Volta para a posição da próxima linha
    }
    
    // Desenha o contorno arredondado do bloco inteiro
    $altura_bloco = $pdf->GetY() - $y_inicial_bloco;
    $pdf->SetDrawColor(222, 226, 230); // Cinza claro para a borda
    $pdf->RoundedRect(10, $y_inicial_bloco, 190, $altura_bloco, 4, 'S');

    $pdf->Ln(5); // Espaço entre os blocos
}

// Totalizador
$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(240, 240, 240);
$pdf->RoundedRect($pdf->GetX(), $pdf->GetY(), 190, 10, 4, 'F');
$pdf->SetY($pdf->GetY() + 1);
$pdf->Cell(150, 8, $pdf->TextToCell('Total Geral do Relatório'), 0, 0, 'R');
$pdf->Cell(40, 8, 'R$ ' . number_format($total_valor, 2, ',', '.'), 0, 1, 'R');

// --- Construção do nome do arquivo ---
$filename_person = 'Geral';
if (!empty($nome_usuario)) {
    $filename_person = $nome_usuario;
}

$filename_period = '';
if (!empty($filtro_data_inicio)) {
    $filename_period .= date('M-Y', strtotime($filtro_data_inicio));
}
if (!empty($filtro_data_fim)) {
    $filename_period .= '_a_' . date('M-Y', strtotime($filtro_data_fim));
}

$filename_base = "Relatorio_{$filename_person}_{$filename_period}";
// Sanitiza o nome do arquivo para remover acentos e caracteres especiais
$filename_sanitized = iconv('UTF-8', 'ASCII//TRANSLIT', $filename_base);
$filename_sanitized = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename_sanitized);
$filename_sanitized = preg_replace('/_+/', '_', $filename_sanitized) . '.pdf';

$pdf->Output($output_mode, $filename_sanitized, true);
exit();
?>
