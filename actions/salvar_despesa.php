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

    // Validação simples (pode ser melhorada)
    if (empty($descricao) || empty($valor) || empty($data_despesa) || empty($dono_divida_id) || empty($comprador_id)) {
        // Redirecionar de volta com uma mensagem de erro
        $redirect_url = $id ? "../pages/despesas/editar.php?id=$id" : "../pages/despesas/adicionar.php";
        header("Location: $redirect_url&error=campos_obrigatorios");
        exit();
    }

    // 2. Preparar e executar a inserção no banco de dados
    try {
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

        // 3. Redirecionar para a página de listagem com mensagem de sucesso
        header('Location: ../pages/despesas/index.php?success=despesa_adicionada');
        exit();

    } catch (PDOException $e) {
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