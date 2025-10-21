<?php
session_start();
require_once '../includes/auth_check.php'; // Garante que o usuário está logado
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Coletar e validar os dados do formulário
    $id = $_POST['id'] ?? null;
    $descricao = trim($_POST['descricao']);
    $valor = $_POST['valor'];
    $data_despesa = $_POST['data_despesa'];
    $dono_divida_id = $_POST['dono_divida_id'];
    $comprador_id = $_POST['comprador_id'];
    $metodo_pagamento = $_POST['metodo_pagamento'];
    
    // O campo 'cartao_id' só é enviado se o método for 'cartao_credito'
    // Usamos o operador de coalescência nula para definir como NULL se não existir
    $cartao_id = $_POST['cartao_id'] ?? null;
    if (empty($cartao_id)) {
        $cartao_id = null;
    }

    // Dados específicos do crediário Bemol
    $numero_parcelas_bemol = ($metodo_pagamento === 'bemol_crediario' && !empty($_POST['numero_parcelas_bemol'])) ? (int)$_POST['numero_parcelas_bemol'] : 1;
    $teve_entrada = ($metodo_pagamento === 'bemol_crediario' && isset($_POST['teve_entrada'])) ? 1 : 0;
    $valor_entrada = ($teve_entrada && !empty($_POST['valor_entrada'])) ? $_POST['valor_entrada'] : 0;
    $dia_vencimento_parcela_bemol = !empty($_POST['dia_vencimento_parcela_bemol']) ? (int)$_POST['dia_vencimento_parcela_bemol'] : null;


    // Validação simples (pode ser melhorada)
    if (empty($descricao) || empty($valor) || empty($data_despesa) || empty($dono_divida_id) || empty($comprador_id)) {
        // Redirecionar de volta com uma mensagem de erro
        $redirect_url = $id ? "../pages/despesas/editar.php?id=$id" : "../pages/despesas/adicionar.php";
        header("Location: $redirect_url&error=campos_obrigatorios");
        exit();
    }

    // 2. Preparar e executar a inserção no banco de dados
    try {
        $pdo->beginTransaction();

        // Lógica para Bemol Crediário (cria despesas parceladas)
        if ($metodo_pagamento === 'bemol_crediario' && $numero_parcelas_bemol > 0) {
            $valor_financiado = $valor - $valor_entrada;
            $valor_parcela = round($valor_financiado / $numero_parcelas_bemol, 2);
            $grupo_parcela_id = uniqid('bemol_', true);
            $data_base = new DateTime($data_despesa);

            for ($i = 1; $i <= $numero_parcelas_bemol; $i++) {
                $descricao_parcela = "{$descricao} (Parcela {$i}/{$numero_parcelas_bemol})";
                
                // A primeira parcela é sempre no mês seguinte
                $data_vencimento = (clone $data_base)->modify("+" . $i . " months");

                // Se um dia de vencimento foi especificado, usa ele. Senão, usa o dia da data da compra.
                $dia_vencimento = $dia_vencimento_parcela_bemol ?: (int)$data_base->format('d');

                // Define o dia do vencimento na data calculada
                $data_vencimento->setDate((int)$data_vencimento->format('Y'), (int)$data_vencimento->format('m'), $dia_vencimento);

                $sql_despesa = "INSERT INTO despesas (descricao, valor, data_despesa, dono_divida_id, comprador_id, metodo_pagamento, grupo_parcela_id) 
                                VALUES (:descricao, :valor, :data_despesa, :dono_divida_id, :comprador_id, :metodo_pagamento, :grupo_parcela_id)";
                $stmt_despesa = $pdo->prepare($sql_despesa);
                $stmt_despesa->execute([
                    ':descricao' => $descricao_parcela,
                    ':valor' => $valor_parcela,
                    ':data_despesa' => $data_vencimento->format('Y-m-d'),
                    ':dono_divida_id' => $dono_divida_id,
                    ':comprador_id' => $comprador_id,
                    ':metodo_pagamento' => $metodo_pagamento,
                    ':grupo_parcela_id' => $grupo_parcela_id
                ]);
                $despesa_id = $pdo->lastInsertId();

                // Insere na tabela despesas_bemol apenas para a primeira parcela, para evitar duplicidade
                if ($i === 1) {
                    $sql_bemol = "INSERT INTO despesas_bemol (despesa_id, titular_conta_bemol_id, teve_entrada, valor_entrada, numero_parcelas)
                                  VALUES (:despesa_id, :titular_id, :teve_entrada, :valor_entrada, :numero_parcelas)";
                    $stmt_bemol = $pdo->prepare($sql_bemol);
                    $stmt_bemol->execute([
                        ':despesa_id' => $despesa_id,
                        ':titular_id' => $comprador_id,
                        ':teve_entrada' => $teve_entrada,
                        ':valor_entrada' => $valor_entrada > 0 ? $valor_entrada : null,
                        ':numero_parcelas' => $numero_parcelas_bemol
                    ]);
                }
            }

        } else { // Lógica para despesas normais (não-Bemol ou edição)
            if ($id) {
                // Atualizar despesa existente
                $sql = "UPDATE despesas SET descricao = :descricao, valor = :valor, data_despesa = :data_despesa, dono_divida_id = :dono_divida_id, comprador_id = :comprador_id, metodo_pagamento = :metodo_pagamento, cartao_id = :cartao_id WHERE id = :id";
            } else {
                // Inserir nova despesa
                $sql = "INSERT INTO despesas (descricao, valor, data_despesa, dono_divida_id, comprador_id, metodo_pagamento, cartao_id) 
                        VALUES (:descricao, :valor, :data_despesa, :dono_divida_id, :comprador_id, :metodo_pagamento, :cartao_id)";
            }
            
            $stmt = $pdo->prepare($sql);

            $params = [
                ':descricao' => $descricao,
                ':valor' => $valor,
                ':data_despesa' => $data_despesa,
                ':dono_divida_id' => $dono_divida_id,
                ':comprador_id' => $comprador_id,
                ':metodo_pagamento' => $metodo_pagamento,
                ':cartao_id' => $cartao_id
            ];

            if ($id) {
                $params[':id'] = $id;
            }

            $stmt->execute($params);
        }

        $pdo->commit();

        // 3. Redirecionar para a página de listagem com mensagem de sucesso
        header('Location: ../pages/despesas/index.php?success=despesa_adicionada');
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        // Em caso de erro, redirecionar com mensagem de erro
        // Em um ambiente de produção, você deveria logar o erro $e->getMessage()
        $redirect_url = $id ? "../pages/despesas/editar.php?id=$id" : "../pages/despesas/adicionar.php";
        header("Location: $redirect_url&error=db_error");
        exit();
    }
} else {
    // Se alguém tentar acessar o script diretamente
    header('Location: ../pages/dashboard.php');
    exit();
}