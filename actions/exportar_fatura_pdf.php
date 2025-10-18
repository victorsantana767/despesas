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
    SELECT c.nome_cartao, c.dia_vencimento_fatura, u.nome as titular_nome
    FROM cartoes c
    JOIN usuarios u ON c.titular_id = u.id
    WHERE c.id = ?
");
$stmt_cartao->execute([$cartao_id]);
$cartao = $stmt_cartao->fetch(PDO::FETCH_ASSOC);

if (!$cartao) {
    die("Cartão não encontrado.");
}

// 2. Buscar despesas da fatura (lógica idêntica a pages/cartoes/index.php)
$inicio_mes_filtro = date('Y-m-01', strtotime($filtro_mes_ano));
$fim_mes_filtro = date('Y-m-t', strtotime($filtro_mes_ano));

$sql_despesas = "
    SELECT descricao, valor, data_despesa, status
    FROM despesas 
    WHERE cartao_id = ? AND data_despesa BETWEEN ? AND ?
    ORDER BY data_despesa ASC
";
$stmt_despesas = $pdo->prepare($sql_despesas);
$stmt_despesas->execute([$cartao_id, $inicio_mes_filtro, $fim_mes_filtro]);
$despesas = $stmt_despesas->fetchAll(PDO::FETCH_ASSOC);

// Verifica se há despesas pendentes para definir o status geral da fatura
$despesas_pendentes = array_filter($despesas, function($d) {
    return $d['status'] === 'pendente';
});
$fatura_paga = empty($despesas_pendentes) && !empty($despesas);

// --- Geração do PDF ---
$pdf = new PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->SetTitle("Fatura do Cartão");
$pdf->AddPage();

// Adiciona a faixa diagonal de status da fatura
if ($fatura_paga) {
    $pdf->DiagonalBanner('PAGA', 'pago');
} else {
    // Verifica se a fatura está atrasada
    $data_vencimento_fatura = new DateTime($filtro_mes_ano . '-' . $cartao['dia_vencimento_fatura']);
    $hoje = new DateTime();
    if ($data_vencimento_fatura < $hoje->setTime(0,0,0)) {
        $pdf->DiagonalBanner('ATRASADA', 'atrasado');
    }
}

// --- Bloco de Resumo da Fatura ---
$pdf->SetFont('Arial','',10);
$pdf->Cell(100, 6, $pdf->TextToCell('Cartão: ' . $cartao['nome_cartao']), 0, 0, 'L');
$pdf->Cell(90, 6, $pdf->TextToCell('Vencimento: ' . $cartao['dia_vencimento_fatura'] . date('/m/Y', strtotime($filtro_mes_ano))), 0, 1, 'R');
$pdf->Cell(100, 6, $pdf->TextToCell('Titular: ' . $cartao['titular_nome']), 0, 1, 'L');

// Linha separadora
$pdf->Line(10, $pdf->GetY() + 2, 200, $pdf->GetY() + 2);
$pdf->Ln(5);

// Total da Fatura
$total_fatura = array_sum(array_column($despesas, 'valor'));
$pdf->SetFont('Arial','B',12);
$pdf->Cell(130, 8, 'TOTAL DA FATURA', 0, 0, 'R');
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(60, 8, 'R$ ' . number_format($total_fatura, 2, ',', '.'), 0, 1, 'C', true);
$pdf->SetFont('Arial','',10);
$pdf->Ln(10);

// --- Tabela de Despesas ---
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0, 8, $pdf->TextToCell('DETALHAMENTO DAS DESPESAS'), 0, 1, 'L');

// Cabeçalho da Tabela
$pdf->SetTextColor(0, 0, 0); // Garante que o texto da tabela seja preto
$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(30, 7, 'Data', 1, 0, 'C', true);
$pdf->Cell(130, 7, $pdf->TextToCell('Descrição'), 1, 0, 'L', true);
$pdf->Cell(30, 7, 'Valor (R$)', 1, 1, 'C', true);

// Corpo da Tabela
$pdf->SetFont('Arial','',9);
$fill = false;

foreach ($despesas as $despesa) {
    $pdf->SetFillColor($fill ? 245 : 255, 245, 245);
    $pdf->Cell(30, 7, date('d/m/Y', strtotime($despesa['data_despesa'])), 1, 0, 'C', $fill);
    $pdf->Cell(130, 7, $pdf->TextToCell($despesa['descricao']), 1, 0, 'L', $fill);
    $pdf->Cell(30, 7, number_format($despesa['valor'], 2, ',', '.'), 1, 1, 'R', $fill);
    $fill = !$fill;
}

$pdf->Output($output_mode, 'fatura_'. $cartao['nome_cartao'] .'_'. $filtro_mes_ano .'.pdf');
exit();
?>