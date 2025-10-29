<?php
require_once '../includes/auth_check.php';
require_once '../config/config.php';
require_once '../includes/PDF.php';

if ($user_tipo !== 'admin') {
    header("Location: ../pages/dashboard.php");
    exit();
}

$cartao_id = $_GET['cartao_id'] ?? null;
$filtro_mes_ano = $_GET['mes_ano'] ?? null;
$output_mode = $_GET['output'] ?? 'D'; // D para Download, I para Inline (visualizar)

if (!$cartao_id || !$filtro_mes_ano) {
    die("Dados insuficientes para gerar a fatura.");
}

// 1. Buscar dados do cartão
$stmt_cartao = $pdo->prepare("
    SELECT c.nome_cartao, c.dia_vencimento_fatura, c.dia_fechamento_fatura, u.nome as titular_nome
    FROM cartoes c
    JOIN usuarios u ON c.titular_id = u.id
    WHERE c.id = ?
");
$stmt_cartao->execute([$cartao_id]);
$cartao = $stmt_cartao->fetch(PDO::FETCH_ASSOC);

if (!$cartao) {
    die("Cartão não encontrado.");
}

// 2. Buscar despesas da fatura
$dia_vencimento = (int)$cartao['dia_vencimento_fatura'];
$dia_fechamento = (int)$cartao['dia_fechamento_fatura'];
$mes_vencimento_fatura = new DateTime($filtro_mes_ano . '-' . $dia_vencimento);

// Define o período como o primeiro e último dia do mês filtrado.
$data_inicio_mes = date('Y-m-01', strtotime($filtro_mes_ano));
$data_fim_mes = date('Y-m-t', strtotime($filtro_mes_ano));

$sql_despesas = "
    SELECT descricao, valor, COALESCE(data_compra, data_despesa) as data_compra, status
    FROM despesas 
    WHERE cartao_id = ? 
    AND metodo_pagamento = 'cartao_credito'
    AND data_despesa BETWEEN ? AND ?
    ORDER BY data_despesa ASC
";
$stmt_despesas = $pdo->prepare($sql_despesas);
$stmt_despesas->execute([$cartao_id, $data_inicio_mes, $data_fim_mes]);
$despesas = $stmt_despesas->fetchAll(PDO::FETCH_ASSOC);

// Verifica se há despesas pendentes para definir o status geral da fatura
$despesas_pendentes = array_filter($despesas, function($d) {
    return $d['status'] === 'pendente';
});
$fatura_paga = empty($despesas_pendentes) && !empty($despesas);

// --- Geração do PDF ---
$pdf = new PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->SetTitle("Fatura do Cartão", true);
$pdf->AddPage();

// Adiciona a faixa diagonal de status da fatura
if ($fatura_paga) {
    $pdf->DiagonalBanner('PAGA', 'pago');
} else {
    $data_vencimento_fatura = new DateTime(date('Y-m', strtotime($filtro_mes_ano)) . '-' . $cartao['dia_vencimento_fatura']);
    $hoje = new DateTime();
    if ($data_vencimento_fatura < $hoje->setTime(0,0,0)) {
        $pdf->DiagonalBanner('ATRASADA', 'atrasado');
    }
}

// --- Bloco de Informações da Fatura ---
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0, 8, $pdf->TextToCell($cartao['nome_cartao']), 0, 1, 'L');
$pdf->SetFont('Arial','',10);
$pdf->Cell(0, 6, $pdf->TextToCell('Titular: ' . $cartao['titular_nome']), 0, 1, 'L');
$pdf->Cell(0, 6, $pdf->TextToCell('Vencimento: ' . date('d/m/Y', strtotime($data_vencimento_fatura->format('Y-m-d')))), 0, 1, 'L');
$pdf->Ln(8);

// --- Tabela de Despesas ---
// Cabeçalho da Tabela
$pdf->SetFont('Arial','B',9);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetTextColor(0);
$pdf->RoundedRect($pdf->GetX(), $pdf->GetY(), 190, 8, 2, 'F');
$pdf->Cell(30, 8, 'Data', 0, 0, 'C');
$pdf->Cell(130, 8, $pdf->TextToCell('Descrição'), 0, 0, 'L');
$pdf->Cell(30, 8, 'Valor', 0, 1, 'R');

// Corpo da Tabela
$pdf->SetFont('Arial','',9);
$y_pos = $pdf->GetY();
$total_fatura = 0;

foreach ($despesas as $despesa) {
    $cell_height = 8;
    // Desenha a linha inferior suave
    $pdf->Line($pdf->GetX(), $y_pos + $cell_height, $pdf->GetX() + 190, $y_pos + $cell_height);

    $pdf->Cell(30, $cell_height, date('d/m/Y', strtotime($despesa['data_compra'])), 0, 0, 'C');
    $pdf->Cell(130, $cell_height, $pdf->TextToCell($despesa['descricao']), 0, 0, 'L');
    $pdf->Cell(30, $cell_height, number_format($despesa['valor'], 2, ',', '.'), 0, 1, 'R');
    $y_pos += $cell_height;
    $total_fatura += $despesa['valor'];
}
$pdf->Ln(2);

// Totalizador
$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(233, 236, 239);
$pdf->RoundedRect($pdf->GetX(), $pdf->GetY(), 190, 10, 4, 'F');
$pdf->SetY($pdf->GetY() + 1);
$pdf->Cell(150, 8, $pdf->TextToCell('Total da Fatura'), 0, 0, 'R');
$pdf->Cell(40, 8, 'R$ ' . number_format($total_fatura, 2, ',', '.'), 0, 1, 'R');

$pdf->Output($output_mode, 'fatura_'. $cartao['nome_cartao'] .'_'. $filtro_mes_ano .'.pdf', true);
exit();
?>
