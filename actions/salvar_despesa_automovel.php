<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dados gerais da despesa
    $descricao = trim($_POST['descricao']);
    $valor = $_POST['valor'];
    $data_despesa = $_POST['data_despesa'];
    $dono_divida_id = $_POST['dono_divida_id'];
    $comprador_id = $_POST['comprador_id'];
    $metodo_pagamento = $_POST['metodo_pagamento'];
    $cartao_id = ($_POST['metodo_pagamento'] === 'cartao_credito') ? $_POST['cartao_id'] : null;
    $numero_parcelas = ($_POST['metodo_pagamento'] === 'cartao_credito' && isset($_POST['numero_parcelas'])) ? (int)$_POST['numero_parcelas'] : 1;

    // Dados específicos do automóvel
    $automovel_id = $_POST['automovel_id'];
    $tipo_despesa = $_POST['tipo_despesa'];
    $quilometragem = !empty($_POST['quilometragem']) ? $_POST['quilometragem'] : null;
    $litros_combustivel = !empty($_POST['litros_combustivel']) ? $_POST['litros_combustivel'] : null;

    try {
        $pdo->beginTransaction();

        $valor_parcela = ($numero_parcelas > 1) ? round($valor / $numero_parcelas, 2) : $valor;
        // Gera um ID único para o grupo de parcelas, se for uma compra parcelada
        $grupo_parcela_id = ($numero_parcelas > 1) ? uniqid('compra_', true) : null;

        $data_compra_obj = new DateTime($data_despesa);
        // Define a data da primeira parcela como a própria data da compra por padrão
        $data_primeira_parcela = clone $data_compra_obj;
        
        // Lógica de vencimento para cartão de crédito
        if ($metodo_pagamento === 'cartao_credito' && $cartao_id) { // Aplica para qualquer compra no cartão
            // Buscar dados do cartão (dia de fechamento e vencimento)
            $stmt_cartao = $pdo->prepare("SELECT dia_fechamento_fatura, dia_vencimento_fatura FROM cartoes WHERE id = ?");
            $stmt_cartao->execute([$cartao_id]);
            $cartao = $stmt_cartao->fetch(PDO::FETCH_ASSOC);
            
            if ($cartao) {
                $dia_fechamento = (int)$cartao['dia_fechamento_fatura'];
                $dia_vencimento = (int)$cartao['dia_vencimento_fatura'];
                
                $data_fechamento_no_mes_da_compra = (clone $data_compra_obj)->setDate((int)$data_compra_obj->format('Y'), (int)$data_compra_obj->format('m'), $dia_fechamento);                
                
                if ($data_compra_obj > $data_fechamento_no_mes_da_compra) {
                    // Compra após o fechamento: a fatura vence no mês seguinte ao próximo.
                    $data_primeira_parcela = (clone $data_fechamento_no_mes_da_compra)->modify('+1 month');
                } else {
                    // Compra antes do fechamento: a fatura vence no próximo mês.
                    $data_primeira_parcela = clone $data_fechamento_no_mes_da_compra;
                }
                $data_primeira_parcela->setDate((int)$data_primeira_parcela->format('Y'), (int)$data_primeira_parcela->format('m'), $dia_vencimento);
            }
        }

        for ($i = 1; $i <= $numero_parcelas; $i++) {
            $descricao_parcela = ($numero_parcelas > 1) ? "{$descricao} (Parcela {$i}/{$numero_parcelas})" : $descricao;
            
            // Calcula a data da despesa para cada parcela, adicionando meses à data da primeira parcela
            $data_parcela = (clone $data_primeira_parcela)->modify("+" . ($i - 1) . " months");
            // 1. Inserir na tabela 'despesas'
            $sql1 = "INSERT INTO despesas (descricao, valor, data_despesa, data_compra, dono_divida_id, comprador_id, metodo_pagamento, cartao_id, automovel_id, grupo_parcela_id) 
                     VALUES (:descricao, :valor, :data_despesa, :data_compra, :dono_divida_id, :comprador_id, :metodo_pagamento, :cartao_id, :automovel_id, :grupo_parcela_id)";
            
            $stmt1 = $pdo->prepare($sql1);
            $stmt1->execute([
                ':descricao' => $descricao_parcela,
                ':valor' => $valor_parcela,
                ':data_despesa' => $data_parcela->format('Y-m-d'),
                ':data_compra' => $data_despesa, // Data original da compra
                ':dono_divida_id' => $dono_divida_id,
                ':comprador_id' => $comprador_id,
                ':metodo_pagamento' => $metodo_pagamento,
                ':cartao_id' => $cartao_id,
                ':automovel_id' => $automovel_id,
                ':grupo_parcela_id' => $grupo_parcela_id
            ]);

            $despesa_id = $pdo->lastInsertId();

            // 2. Inserir na tabela 'despesas_automoveis'
            // Apenas a primeira parcela terá os detalhes de litros/km para evitar duplicidade de dados
            $km_parcela = ($i === 1) ? $quilometragem : null;
            $litros_parcela = ($i === 1) ? $litros_combustivel : null;

            $sql2 = "INSERT INTO despesas_automoveis (despesa_id, automovel_id, tipo_despesa, quilometragem, litros_combustivel)
                     VALUES (:despesa_id, :automovel_id, :tipo_despesa, :quilometragem, :litros_combustivel)";
            
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([
                ':despesa_id' => $despesa_id,
                ':automovel_id' => $automovel_id,
                ':tipo_despesa' => $tipo_despesa,
                ':quilometragem' => $km_parcela,
                ':litros_combustivel' => $litros_parcela
            ]);
        }

        $pdo->commit();

        header("Location: ../pages/automoveis/despesas.php?automovel_id=$automovel_id&success=1");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        header("Location: ../pages/automoveis/adicionar_despesa.php?automovel_id=$automovel_id&error=db_error");
        exit();
    }
}