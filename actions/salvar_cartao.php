<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/config.php';

// Apenas admins podem salvar
if ($user_tipo !== 'admin') {
    header("Location: ../pages/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coletar dados
    $id = $_POST['id'] ?? null;
    $nome_cartao = trim($_POST['nome_cartao']);
    $titular_id = $_POST['titular_id'];
    $dia_fechamento_fatura = $_POST['dia_fechamento_fatura'];
    $dia_vencimento_fatura = $_POST['dia_vencimento_fatura'];
    $data_validade_cartao = $_POST['data_validade_cartao'];

    try {
        if ($id) {
            // Atualizar (Update)
            $sql = "UPDATE cartoes SET nome_cartao = :nome_cartao, titular_id = :titular_id, dia_fechamento_fatura = :dia_fechamento_fatura, dia_vencimento_fatura = :dia_vencimento_fatura, data_validade_cartao = :data_validade_cartao WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        } else {
            // Inserir (Create)
            $sql = "INSERT INTO cartoes (nome_cartao, titular_id, dia_fechamento_fatura, dia_vencimento_fatura, data_validade_cartao) VALUES (:nome_cartao, :titular_id, :dia_fechamento_fatura, :dia_vencimento_fatura, :data_validade_cartao)";
            $stmt = $pdo->prepare($sql);
        }

        // Bind dos parâmetros comuns
        $stmt->bindParam(':nome_cartao', $nome_cartao);
        $stmt->bindParam(':titular_id', $titular_id, PDO::PARAM_INT);
        $stmt->bindParam(':dia_fechamento_fatura', $dia_fechamento_fatura, PDO::PARAM_INT);
        $stmt->bindParam(':dia_vencimento_fatura', $dia_vencimento_fatura, PDO::PARAM_INT);
        $stmt->bindParam(':data_validade_cartao', $data_validade_cartao);

        $stmt->execute();

        header('Location: ../pages/cartoes/index.php?success=1');
        exit();

    } catch (PDOException $e) {
        // Logar o erro em um ambiente real
        header('Location: ../pages/cartoes/index.php?error=db_error');
        exit();
    }
}