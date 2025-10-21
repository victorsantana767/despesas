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
    $grupo_parcela_id = $_POST['grupo_parcela_id'] ?? null;

    if (empty($grupo_parcela_id)) {
        header('Location: ../pages/despesas/index.php?error=invalid_id');
        exit();
    }

    try {
        // A tabela 'despesas_bemol' tem ON DELETE CASCADE, então ao apagar a despesa principal,
        // a entrada correspondente em 'despesas_bemol' também será removida.
        $sql = "DELETE FROM despesas WHERE grupo_parcela_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$grupo_parcela_id]);

        header('Location: ../pages/despesas/index.php?deleted=compra_bemol');
        exit();

    } catch (PDOException $e) {
        // Em produção, logue o erro em vez de exibi-lo
        header('Location: ../pages/despesas/index.php?error=db_error');
        exit();
    }
} else {
    header('Location: ../pages/despesas/index.php');
    exit();
}