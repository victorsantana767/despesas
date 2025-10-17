<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/config.php';

if ($user_tipo !== 'admin') {
    header("Location: ../pages/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coleta de dados do formulário
    $id = $_POST['id'] ?? null;
    $usuario_id = $_POST['usuario_id'];
    $banco = trim($_POST['banco']);
    $descricao = trim($_POST['descricao']);
    $valor_emprestimo = $_POST['valor_emprestimo'];
    $taxa_juros_anual = !empty($_POST['taxa_juros_anual']) ? $_POST['taxa_juros_anual'] : null;
    $numero_parcelas = $_POST['numero_parcelas'];
    $valor_parcela = $_POST['valor_parcela'];
    $data_emprestimo = $_POST['data_emprestimo'];
    $dia_vencimento_parcela = $_POST['dia_vencimento_parcela'];

    try {
        $pdo->beginTransaction();

        if ($id) {
            // 1. Apaga as despesas antigas vinculadas a este empréstimo
            $stmt_delete = $pdo->prepare("DELETE FROM despesas WHERE emprestimo_id = ?");
            $stmt_delete->execute([$id]);

            // 2. Atualiza o registro do empréstimo
            $sql = "UPDATE emprestimos SET usuario_id = :usuario_id, banco = :banco, descricao = :descricao, valor_emprestimo = :valor_emprestimo, taxa_juros_anual = :taxa_juros_anual, numero_parcelas = :numero_parcelas, valor_parcela = :valor_parcela, data_emprestimo = :data_emprestimo, dia_vencimento_parcela = :dia_vencimento_parcela WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        } else {
            // Inserir novo empréstimo
            $sql = "INSERT INTO emprestimos (usuario_id, banco, descricao, valor_emprestimo, taxa_juros_anual, numero_parcelas, valor_parcela, data_emprestimo, dia_vencimento_parcela) 
                    VALUES (:usuario_id, :banco, :descricao, :valor_emprestimo, :taxa_juros_anual, :numero_parcelas, :valor_parcela, :data_emprestimo, :dia_vencimento_parcela)";
            $stmt = $pdo->prepare($sql);
        }

        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->bindParam(':banco', $banco);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':valor_emprestimo', $valor_emprestimo);
        $stmt->bindParam(':taxa_juros_anual', $taxa_juros_anual);
        $stmt->bindParam(':numero_parcelas', $numero_parcelas, PDO::PARAM_INT);
        $stmt->bindParam(':valor_parcela', $valor_parcela);
        $stmt->bindParam(':data_emprestimo', $data_emprestimo);
        $stmt->bindParam(':dia_vencimento_parcela', $dia_vencimento_parcela, PDO::PARAM_INT);
        $stmt->execute();

        $emprestimo_id = $id ?: $pdo->lastInsertId();

        // --- Recria as Parcelas como Despesas ---
        if ($numero_parcelas > 0) {
            $sql_despesa = "INSERT INTO despesas (descricao, valor, data_despesa, dono_divida_id, comprador_id, metodo_pagamento, emprestimo_id)
                            VALUES (:descricao, :valor, :data_despesa, :dono_divida_id, :comprador_id, 'emprestimo', :emprestimo_id)";
            $stmt_despesa = $pdo->prepare($sql_despesa);

            $data_base = new DateTime($data_emprestimo);

            for ($i = 1; $i <= $numero_parcelas; $i++) {
                $descricao_parcela = "Parcela {$i}/{$numero_parcelas} - Empréstimo {$banco}";
                
                // Lógica de data corrigida
                $data_vencimento = clone $data_base;
                $data_vencimento->modify("+{$i} month");
                $data_vencimento->setDate($data_vencimento->format('Y'), $data_vencimento->format('m'), $dia_vencimento_parcela);

                $data_vencimento_formatada = $data_vencimento->format('Y-m-d');

                $stmt_despesa->execute([
                    ':descricao' => $descricao_parcela,
                    ':valor' => $valor_parcela,
                    ':data_despesa' => $data_vencimento_formatada,
                    ':dono_divida_id' => $usuario_id,
                    ':comprador_id' => $usuario_id,
                    ':emprestimo_id' => $emprestimo_id
                ]);
            }
        }

        $pdo->commit();
        header('Location: ../pages/emprestimos/index.php?success=1');
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        header('Location: ../pages/emprestimos/adicionar.php?error=db_error&msg=' . urlencode($e->getMessage()));
        exit();
    }
}