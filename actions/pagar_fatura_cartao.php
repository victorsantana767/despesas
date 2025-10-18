<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/config.php';

// Apenas admins podem executar esta ação
if ($user_tipo !== 'admin') {
    header("Location: ../pages/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartao_id = $_POST['cartao_id'] ?? null;
    $filtro_mes_ano = $_POST['mes_ano'] ?? null;

    if (!$cartao_id || !$filtro_mes_ano) {
        header('Location: ../pages/cartoes/index.php?error=dados_insuficientes');
        exit();
    }

    try {
        // 1. Buscar os dados do cartão para recalcular o período da fatura
        $stmt_cartao = $pdo->prepare("SELECT dia_fechamento_fatura FROM cartoes WHERE id = ?");
        $stmt_cartao->execute([$cartao_id]);
        $cartao = $stmt_cartao->fetch(PDO::FETCH_ASSOC);

        if (!$cartao) {
            throw new Exception("Cartão não encontrado.");
        }

        // 2. Recalcular o período da fatura (lógica idêntica à da página de listagem)
        $mes_vencimento_fatura = new DateTime($filtro_mes_ano . '-01');
        $dia_fechamento = (int)$cartao['dia_fechamento_fatura'];

        $data_fechamento_final = (clone $mes_vencimento_fatura)->setDate((int)$mes_vencimento_fatura->format('Y'), (int)$mes_vencimento_fatura->format('m'), $dia_fechamento);
        $data_fechamento_inicial = (clone $data_fechamento_final)->modify('-1 month');
        $data_inicio_periodo = (clone $data_fechamento_inicial)->modify('+1 day');

        // 3. Atualizar todas as despesas PENDENTES dentro do período da fatura para PAGO
        $sql = "UPDATE despesas 
                SET status = 'pago' 
                WHERE cartao_id = :cartao_id 
                AND status = 'pendente'
                AND data_despesa BETWEEN :data_inicio AND :data_fim";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':cartao_id' => $cartao_id,
            ':data_inicio' => $data_inicio_periodo->format('Y-m-d'),
            ':data_fim' => $data_fechamento_final->format('Y-m-d')
        ]);

        // 4. Redirecionar de volta com mensagem de sucesso
        header('Location: ../pages/cartoes/index.php?mes_ano=' . $filtro_mes_ano . '&fatura_paga=1');
        exit();

    } catch (Exception $e) {
        // Em um ambiente real, logar o erro: error_log($e->getMessage());
        header('Location: ../pages/cartoes/index.php?mes_ano=' . $filtro_mes_ano . '&error=db_error');
        exit();
    }
} else {
    header('Location: ../pages/dashboard.php');
    exit();
}