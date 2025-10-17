<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/config.php';

// Apenas admins podem excluir
if ($user_tipo !== 'admin') {
    header("Location: ../pages/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;

    if (!$id) {
        header('Location: ../pages/cartoes/index.php');
        exit();
    }

    try {
        // Tenta excluir o cartão
        $stmt = $pdo->prepare("DELETE FROM cartoes WHERE id = ?");
        $stmt->execute([$id]);

        header('Location: ../pages/cartoes/index.php?deleted=1');
        exit();

    } catch (PDOException $e) {
        // Se der erro de chave estrangeira (código 1451), significa que o cartão está em uso
        if ($e->errorInfo[1] == 1451) {
            header('Location: ../pages/cartoes/index.php?error=foreign_key');
            exit();
        }
        // Outro erro de banco de dados
        header('Location: ../pages/cartoes/index.php?error=db_error');
        exit();
    }
}