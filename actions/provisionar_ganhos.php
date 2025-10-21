<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/config.php';

if ($user_tipo !== 'admin') {
    header("Location: ../pages/dashboard.php");
    exit();
}

// Pega o mês e ano do filtro para provisionamento. Se não for passado, usa o mês/ano atual.
$mes_ano_filtro = $_GET['mes_ano'] ?? date('Y-m');
$mes_provisionamento = date('m', strtotime($mes_ano_filtro));
$ano_provisionamento = date('Y', strtotime($mes_ano_filtro));

// Data de referência para o provisionamento (primeiro dia do mês filtrado)
$data_referencia_provisionamento = date('Y-m-01', strtotime($mes_ano_filtro));

// 1. Buscar todos os ganhos recorrentes ativos
$sql_recorrentes = "SELECT * FROM ganhos_recorrentes WHERE ativo = 1 AND data_inicio <= LAST_DAY(?)";
$stmt_recorrentes = $pdo->prepare($sql_recorrentes);
$stmt_recorrentes->execute([$data_referencia_provisionamento]);
$recorrentes = $stmt_recorrentes->fetchAll(PDO::FETCH_ASSOC);

try {
    $pdo->beginTransaction();

    foreach ($recorrentes as $rec) {
        // Verifica se a recorrência ainda é válida (se tem data de fim)
        if ($rec['data_fim'] && $data_referencia_provisionamento > $rec['data_fim']) {
            continue; // Pula para o próximo se a recorrência já expirou no mês de provisionamento
        }

        $dia_geracao = $rec['dia_geracao'];
        // Garante que o dia seja válido para o mês atual (ex: dia 31 em fevereiro)
        $ultimo_dia_mes = date('t', strtotime($mes_ano_filtro));
        if ($dia_geracao > $ultimo_dia_mes) {
            $dia_geracao = $ultimo_dia_mes;
        }
        $data_ganho_provisionado = date('Y-m-d', mktime(0, 0, 0, $mes_provisionamento, $dia_geracao, $ano_provisionamento));

        // 2. Verificar se um ganho para este recorrente já não foi criado neste mês
        $sql_check = "SELECT id FROM ganhos WHERE tipo_ganho_id = ? AND usuario_id = ? AND data_ganho = ? AND descricao = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$rec['tipo_ganho_id'], $rec['usuario_id'], $data_ganho_provisionado, $rec['descricao'] ?? '']);
        
        if ($stmt_check->fetch()) {
            continue; // Já existe, pula para o próximo
        }

        // 3. Inserir o ganho provisionado na tabela 'ganhos'
        $sql_insert = "INSERT INTO ganhos (usuario_id, tipo_ganho_id, valor, data_ganho, descricao) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute([$rec['usuario_id'], $rec['tipo_ganho_id'], $rec['valor_base'], $data_ganho_provisionado, $rec['descricao'] ?? '']);
    }

    $pdo->commit();
    header('Location: ../pages/ganhos/index.php?provisioned=1&mes_ano=' . $mes_ano_filtro);
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    header('Location: ../pages/ganhos/index.php?error=provision_failed&mes_ano=' . $mes_ano_filtro);
    exit();
}