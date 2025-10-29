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

    $numero_parcelas_cartao = ($metodo_pagamento === 'cartao_credito' && !empty($_POST['numero_parcelas_cartao'])) ? (int)$_POST['numero_parcelas_cartao'] : 1;
    // Dados específicos do crediário Bemol
    $numero_parcelas_bemol = ($metodo_pagamento === 'bemol_crediario' && !empty($_POST['numero_parcelas_bemol'])) ? (int)$_POST['numero_parcelas_bemol'] : 1;
    $teve_entrada = ($metodo_pagamento === 'bemol_crediario' && isset($_POST['teve_entrada'])) ? 1 : 0;
    $valor_entrada = ($teve_entrada && !empty($_POST['valor_entrada'])) ? $_POST['valor_entrada'] : 0;
    $dia_vencimento_parcela_bemol = !empty($_POST['dia_vencimento_parcela_bemol']) ? (int)$_POST['dia_vencimento_parcela_bemol'] : null;

    // Identificador para edição de compra Bemol completa
    $bemol_grupo_parcela_id = $_POST['bemol_grupo_parcela_id'] ?? null;


    // Validação simples (pode ser melhorada)
    if (empty($descricao) || empty($valor) || empty($data_despesa) || empty($dono_divida_id) || empty($comprador_id)) {
        // Redirecionar de volta com uma mensagem de erro
        $redirect_url = $id ? "../pages/despesas/editar.php?id=$id" : "../pages/despesas/adicionar.php";
        if ($bemol_grupo_parcela_id) {
            $redirect_url = "../pages/bemol/editar.php?grupo_parcela_id=$bemol_grupo_parcela_id";
        }
        header("Location: $redirect_url&error=campos_obrigatorios");
        exit();
    }

    // 2. Preparar e executar a inserção no banco de dados
    try {
        $pdo->beginTransaction();

        // Lógica para Bemol Crediário (cria despesas parceladas)
        if ($metodo_pagamento === 'bemol_crediario' && $numero_parcelas_bemol > 0) {
            $grupo_parcela_id = $bemol_grupo_parcela_id ?: uniqid('bemol_', true); // Usa o ID existente ou gera um novo

            // Se for uma edição, primeiro remove as despesas e o registro de despesas_bemol existentes
            if ($bemol_grupo_parcela_id) {
                // Encontrar o despesa_id da primeira parcela para remover o registro em despesas_bemol
                // A entrada também tem o grupo_parcela_id, mas não é uma "parcela" no sentido de despesas_bemol
                $stmt_first_parcel_id = $pdo->prepare("SELECT id FROM despesas WHERE grupo_parcela_id = :grupo_parcela_id AND descricao NOT LIKE 'Entrada Bemol:%' ORDER BY data_despesa ASC LIMIT 1");
                $stmt_first_parcel_id->execute([':grupo_parcela_id' => $bemol_grupo_parcela_id]);
                $first_parcel_id_to_delete = $stmt_first_parcel_id->fetchColumn();

                if ($first_parcel_id_to_delete) {
                    $stmt_delete_bemol = $pdo->prepare("DELETE FROM despesas_bemol WHERE despesa_id = :despesa_id");
                    $stmt_delete_bemol->execute([':despesa_id' => $first_parcel_id_to_delete]);
                }
                
                // Remover todas as despesas associadas ao grupo
                $stmt_delete_despesas = $pdo->prepare("DELETE FROM despesas WHERE grupo_parcela_id = :grupo_parcela_id");
                $stmt_delete_despesas->execute([':grupo_parcela_id' => $bemol_grupo_parcela_id]);
            }

            $valor_financiado = $valor - $valor_entrada;
            $valor_parcela = round($valor_financiado / $numero_parcelas_bemol, 2);
            $data_base = new DateTime($data_despesa);
            $data_compra_original = $data_despesa; // Armazena a data da compra original

            // Se teve entrada, insere a entrada como uma despesa separada
            if ($teve_entrada && $valor_entrada > 0) {
                $descricao_entrada = "Entrada Bemol: {$descricao}";
                // A entrada é paga na data da despesa original
                $sql_entrada = "INSERT INTO despesas (descricao, valor, data_despesa, data_compra, dono_divida_id, comprador_id, metodo_pagamento, grupo_parcela_id)
                                VALUES (:descricao, :valor, :data_despesa, :data_compra, :dono_divida_id, :comprador_id, :metodo_pagamento, :grupo_parcela_id)";
                $stmt_entrada = $pdo->prepare($sql_entrada);
                $stmt_entrada->execute([
                    ':descricao' => $descricao_entrada,
                    ':valor' => $valor_entrada,
                    ':data_despesa' => $data_compra_original, // Data da compra original (vencimento da entrada)
                    ':data_compra' => $data_compra_original, // Data da compra original
                    ':dono_divida_id' => $dono_divida_id,
                    ':comprador_id' => $comprador_id,
                    ':metodo_pagamento' => 'dinheiro', // Assumimos que a entrada é paga em dinheiro/pix
                    ':grupo_parcela_id' => $grupo_parcela_id // Agrupa com as parcelas
                ]);
                // O despesa_id para despesas_bemol ainda será o da primeira parcela, não da entrada.
            }

            $first_parcel_despesa_id = null; // Para vincular em despesas_bemol
            for ($i = 1; $i <= $numero_parcelas_bemol; $i++) {
                $descricao_parcela = "{$descricao} (Parcela {$i}/{$numero_parcelas_bemol})";
                
                // A primeira parcela é sempre no mês seguinte
                $data_vencimento = (clone $data_base)->modify("+" . $i . " months");

                // Se um dia de vencimento foi especificado, usa ele. Senão, usa o dia da data da compra.
                $dia_vencimento = $dia_vencimento_parcela_bemol ?: (int)$data_base->format('d');

                // Define o dia do vencimento na data calculada
                $data_vencimento->setDate((int)$data_vencimento->format('Y'), (int)$data_vencimento->format('m'), $dia_vencimento);

                $sql_despesa = "INSERT INTO despesas (descricao, valor, data_despesa, data_compra, dono_divida_id, comprador_id, metodo_pagamento, grupo_parcela_id)
                                VALUES (:descricao, :valor, :data_despesa, :data_compra, :dono_divida_id, :comprador_id, :metodo_pagamento, :grupo_parcela_id)";
                $stmt_despesa = $pdo->prepare($sql_despesa);
                $stmt_despesa->execute([
                    ':descricao' => $descricao_parcela,
                    ':valor' => $valor_parcela,
                    ':data_despesa' => $data_vencimento->format('Y-m-d'),
                    ':data_compra' => $data_compra_original, // Data da compra original
                    ':dono_divida_id' => $dono_divida_id,
                    ':comprador_id' => $comprador_id,
                    ':metodo_pagamento' => $metodo_pagamento,
                    ':grupo_parcela_id' => $grupo_parcela_id
                ]);
                $despesa_id = $pdo->lastInsertId();

                // Insere na tabela despesas_bemol apenas para a primeira parcela, para evitar duplicidade
                // Se for uma edição, o grupo_parcela_id já existe, mas o despesa_id é novo.
                if ($i === 1) {
                    $sql_bemol = "INSERT INTO despesas_bemol (despesa_id, titular_conta_bemol_id, teve_entrada, valor_entrada, numero_parcelas)
                                  VALUES (:despesa_id, :titular_id, :teve_entrada, :valor_entrada, :numero_parcelas)";
                    $stmt_bemol = $pdo->prepare($sql_bemol);
                    $stmt_bemol->execute([
                        ':despesa_id' => $despesa_id,
                        ':titular_id' => $comprador_id,
                        ':teve_entrada' => $teve_entrada, // Usa o valor do formulário
                        ':valor_entrada' => $valor_entrada > 0 ? $valor_entrada : null, // Usa o valor do formulário
                        ':numero_parcelas' => $numero_parcelas_bemol
                    ]);
                }
            }
            $redirect_success_url = "../pages/despesas/index.php?success=bemol_compra_salva";

        } elseif ($metodo_pagamento === 'cartao_credito') {
            // Lógica de criação/atualização de parcelas de cartão de crédito
            $valor_parcela = round($valor / $numero_parcelas_cartao, 2);
            $grupo_parcela_id = ($numero_parcelas_cartao > 1) ? uniqid('cartao_', true) : null;
            $data_compra_obj = new DateTime($data_despesa);

            // Buscar dados do cartão para calcular o vencimento de cada parcela
            $data_primeira_fatura = clone $data_compra_obj; // Padrão é a data da compra

            $stmt_cartao = $pdo->prepare("SELECT dia_fechamento_fatura, dia_vencimento_fatura FROM cartoes WHERE id = ?");
            $stmt_cartao->execute([$cartao_id]);
            $cartao = $stmt_cartao->fetch(PDO::FETCH_ASSOC);

            if ($cartao) {
                $dia_fechamento = (int)$cartao['dia_fechamento_fatura'];
                $dia_vencimento = (int)$cartao['dia_vencimento_fatura'];
                
                // Define a data de fechamento da fatura no mesmo mês da compra
                $data_fechamento_no_mes_da_compra = (clone $data_compra_obj)->setDate((int)$data_compra_obj->format('Y'), (int)$data_compra_obj->format('m'), $dia_fechamento);
                
                if ($data_compra_obj > $data_fechamento_no_mes_da_compra) {
                    // Compra após o fechamento: a fatura vence no mês seguinte ao próximo fechamento.
                    $data_primeira_fatura = (clone $data_fechamento_no_mes_da_compra)->modify('+1 month');
                } else {
                    // Compra antes do fechamento: a fatura vence no próximo mês.
                    $data_primeira_fatura = clone $data_fechamento_no_mes_da_compra;
                }
                $data_primeira_fatura->setDate((int)$data_primeira_fatura->format('Y'), (int)$data_primeira_fatura->format('m'), $dia_vencimento);
            }

            for ($i = 1; $i <= $numero_parcelas_cartao; $i++) {
                $descricao_parcela = ($numero_parcelas_cartao > 1) ? "{$descricao} (Parcela {$i}/{$numero_parcelas_cartao})" : $descricao;
                $data_vencimento_parcela = (clone $data_primeira_fatura)->modify("+" . ($i - 1) . " months");
                $sql_despesa = "INSERT INTO despesas (descricao, valor, data_despesa, data_compra, dono_divida_id, comprador_id, metodo_pagamento, cartao_id, grupo_parcela_id) 
                                VALUES (:descricao, :valor, :data_despesa, :data_compra, :dono_divida_id, :comprador_id, :metodo_pagamento, :cartao_id, :grupo_parcela_id)";
                $stmt_despesa = $pdo->prepare($sql_despesa);
                $stmt_despesa->execute([
                    ':descricao' => $descricao_parcela,
                    ':valor' => $valor_parcela,
                    ':data_despesa' => $data_vencimento_parcela->format('Y-m-d'), // Salva a data de vencimento da parcela
                    ':data_compra' => $data_despesa, // Salva a data original da compra
                    ':dono_divida_id' => $dono_divida_id,
                    ':comprador_id' => $comprador_id,
                    ':metodo_pagamento' => $metodo_pagamento,
                    ':cartao_id' => $cartao_id,
                    ':grupo_parcela_id' => $grupo_parcela_id
                ]);
            }
            $redirect_success_url = "../pages/despesas/index.php?success=despesa_adicionada";
        } else { // Lógica para despesas normais (não parceladas ou edição)
            if ($id) {
                // Atualizar despesa existente
                $sql = "UPDATE despesas SET descricao = :descricao, valor = :valor, data_despesa = :data_despesa, data_compra = :data_compra, dono_divida_id = :dono_divida_id, comprador_id = :comprador_id, metodo_pagamento = :metodo_pagamento, cartao_id = :cartao_id WHERE id = :id";
            } else {
                // Inserir nova despesa
                $sql = "INSERT INTO despesas (descricao, valor, data_despesa, data_compra, dono_divida_id, comprador_id, metodo_pagamento, cartao_id) 
                        VALUES (:descricao, :valor, :data_despesa, :data_compra, :dono_divida_id, :comprador_id, :metodo_pagamento, :cartao_id)";
            }
            
            $stmt = $pdo->prepare($sql);

            $params = [
                ':descricao' => $descricao,
                ':valor' => $valor,
                ':data_despesa' => $data_despesa,
                ':data_compra' => $data_despesa, // Para despesas normais, data_compra é igual a data_despesa
                ':dono_divida_id' => $dono_divida_id,
                ':comprador_id' => $comprador_id,
                ':metodo_pagamento' => $metodo_pagamento,
                ':cartao_id' => $cartao_id
            ];

            if ($id) {
                $params[':id'] = $id;
            }

            $stmt->execute($params);
            $redirect_success_url = "../pages/despesas/index.php?success=despesa_adicionada";
        }

        $pdo->commit();

        // 3. Redirecionar para a página de listagem com mensagem de sucesso
        header('Location: ' . ($redirect_success_url ?? '../pages/despesas/index.php?success=despesa_adicionada'));
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        // Em caso de erro, redirecionar com mensagem de erro
        // Em um ambiente de produção, você deveria logar o erro $e->getMessage()
        $redirect_url = $id ? "../pages/despesas/editar.php?id=$id" : "../pages/despesas/adicionar.php";
        if ($bemol_grupo_parcela_id) {
            $redirect_url = "../pages/bemol/editar.php?grupo_parcela_id=$bemol_grupo_parcela_id";
        }
        header("Location: $redirect_url&error=db_error");
        exit();
    }
} else {
    // Se alguém tentar acessar o script diretamente
    header('Location: ../pages/dashboard.php');
    exit();
}