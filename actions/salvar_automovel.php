<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/config.php';

if ($user_tipo !== 'admin') {
    header("Location: ../pages/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $modelo = trim($_POST['modelo']);
    $placa = !empty(trim($_POST['placa'])) ? trim($_POST['placa']) : null;
    $ano = !empty($_POST['ano']) ? $_POST['ano'] : null;
    $data_compra = !empty($_POST['data_compra']) ? $_POST['data_compra'] : null;
    $valor_compra = !empty($_POST['valor_compra']) ? $_POST['valor_compra'] : null;

    // Novos campos de pagamento
    $forma_pagamento = $_POST['forma_pagamento'] ?? 'a_vista';
    $tipo_parcelamento = ($forma_pagamento === 'parcelado') ? $_POST['tipo_parcelamento'] : null;
    $cartao_id = ($tipo_parcelamento === 'cartao_credito') ? $_POST['cartao_id'] : null;
    $numero_parcelas = ($forma_pagamento === 'parcelado' && !empty($_POST['numero_parcelas'])) ? (int)$_POST['numero_parcelas'] : null;
    $valor_parcela = ($forma_pagamento === 'parcelado') ? $_POST['valor_parcela'] : null;
    $dia_vencimento_parcela = ($tipo_parcelamento === 'financiamento') ? $_POST['dia_vencimento_parcela'] : null;
    // Novos campos de consórcio
    $valor_lance = ($tipo_parcelamento === 'consorcio' && !empty($_POST['valor_lance'])) ? $_POST['valor_lance'] : null;
    $data_lance = ($tipo_parcelamento === 'consorcio' && !empty($_POST['data_lance'])) ? $_POST['data_lance'] : null;

    try {
        $pdo->beginTransaction(); // Inicia uma transação

        if ($id) { // Se é uma edição
            // 1. Apaga as despesas antigas (parcelas e lances) vinculadas a este automóvel
            $stmt_delete = $pdo->prepare("DELETE FROM despesas WHERE automovel_id = ?");
            $stmt_delete->execute([$id]);

            // 2. Atualiza o registro do automóvel
            $sql = "UPDATE automoveis SET modelo = :modelo, placa = :placa, ano = :ano, data_compra = :data_compra, valor_compra = :valor_compra, forma_pagamento = :forma_pagamento, tipo_parcelamento = :tipo_parcelamento, cartao_id = :cartao_id, numero_parcelas = :numero_parcelas, valor_parcela = :valor_parcela, dia_vencimento_parcela = :dia_vencimento_parcela, valor_lance = :valor_lance, data_lance = :data_lance WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        } else {
            // Inserir novo veículo
            $sql = "INSERT INTO automoveis (modelo, placa, ano, data_compra, valor_compra, forma_pagamento, tipo_parcelamento, cartao_id, numero_parcelas, valor_parcela, dia_vencimento_parcela, valor_lance, data_lance) VALUES (:modelo, :placa, :ano, :data_compra, :valor_compra, :forma_pagamento, :tipo_parcelamento, :cartao_id, :numero_parcelas, :valor_parcela, :dia_vencimento_parcela, :valor_lance, :data_lance)";
            $stmt = $pdo->prepare($sql);
        }

        $stmt->bindParam(':modelo', $modelo);
        $stmt->bindParam(':placa', $placa);
        $stmt->bindParam(':ano', $ano, PDO::PARAM_INT);
        $stmt->bindParam(':data_compra', $data_compra);
        $stmt->bindParam(':valor_compra', $valor_compra);
        $stmt->bindParam(':forma_pagamento', $forma_pagamento);
        $stmt->bindParam(':tipo_parcelamento', $tipo_parcelamento);
        $stmt->bindParam(':cartao_id', $cartao_id, PDO::PARAM_INT);
        $stmt->bindParam(':numero_parcelas', $numero_parcelas, PDO::PARAM_INT);
        $stmt->bindParam(':valor_parcela', $valor_parcela);
        $stmt->bindParam(':dia_vencimento_parcela', $dia_vencimento_parcela, PDO::PARAM_INT);
        $stmt->bindParam(':valor_lance', $valor_lance);
        $stmt->bindParam(':data_lance', $data_lance);
        $stmt->execute();

        $automovel_id = $id ?: $pdo->lastInsertId();

        // --- Provisionamento ou Recriação de Despesas ---
        if ($forma_pagamento === 'parcelado' && $numero_parcelas > 0 && $valor_parcela > 0) {
            $sql_despesa = "INSERT INTO despesas (descricao, valor, data_despesa, dono_divida_id, comprador_id, metodo_pagamento, cartao_id, automovel_id) VALUES (:descricao, :valor, :data_despesa, :dono_divida_id, :comprador_id, :metodo_pagamento, :cartao_id, :automovel_id)";
            $stmt_despesa = $pdo->prepare($sql_despesa);

            $data_base = new DateTime($data_compra);

            for ($i = 1; $i <= $numero_parcelas; $i++) {
                $descricao_parcela = "Parcela {$i}/{$numero_parcelas} - Veículo {$modelo}";
                
                // Calcula a data de vencimento da parcela
                $data_vencimento = clone $data_base;
                $data_vencimento->modify("+{$i} months");

                // Para financiamento ou consórcio, ajusta o dia do vencimento
                if (($tipo_parcelamento === 'financiamento' || $tipo_parcelamento === 'consorcio') && $dia_vencimento_parcela) {
                    $data_vencimento->setDate($data_vencimento->format('Y'), $data_vencimento->format('m'), $dia_vencimento_parcela);
                }

                $stmt_despesa->execute([
                    ':descricao' => $descricao_parcela,
                    ':valor' => $valor_parcela,
                    ':data_despesa' => $data_vencimento->format('Y-m-d'),
                    ':dono_divida_id' => $user_id, // O dono da dívida é o admin que está cadastrando
                    ':comprador_id' => $user_id,
                    ':metodo_pagamento' => $tipo_parcelamento,
                    ':cartao_id' => $cartao_id,
                    ':automovel_id' => $automovel_id,
                    ':emprestimo_id' => null // Garante que não haja confusão com empréstimos
                ]);
            }

            // Se houver um lance, cria uma despesa separada para ele
            if ($valor_lance > 0 && $data_lance) {
                 $stmt_despesa->execute([
                    ':descricao' => "Lance Consórcio - Veículo {$modelo}",
                    ':valor' => $valor_lance,
                    ':data_despesa' => $data_lance,
                    ':dono_divida_id' => $user_id,
                    ':comprador_id' => $user_id,
                    // O pagamento do lance é geralmente à vista
                    ':metodo_pagamento' => 'dinheiro', 
                    ':cartao_id' => null,
                    ':automovel_id' => $automovel_id,
                    ':emprestimo_id' => null // Garante que não haja confusão com empréstimos
                ]);
            }
        }

        $pdo->commit(); // Confirma a transação
        header('Location: ../pages/automoveis/index.php?success=1');
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack(); // Desfaz a transação em caso de erro
        // Tratar erro de placa duplicada
        if ($e->errorInfo[1] == 1062) {
            header('Location: ../pages/automoveis/adicionar.php?error=placa_duplicada');
        } else {
            header('Location: ../pages/automoveis/adicionar.php?error=db_error');
        }
        exit();
    }
}