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

    if (!$id) {
        header('Location: ../pages/emprestimos/index.php');
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1. Excluir explicitamente todas as despesas (parcelas) associadas.
        // Isso garante que funcione mesmo se o ON DELETE CASCADE não estiver ativo no DB.
        $stmt_despesas = $pdo->prepare("DELETE FROM despesas WHERE emprestimo_id = ?");
        $stmt_despesas->execute([$id]);

        // 2. Excluir o empréstimo principal.
        $stmt_emprestimo = $pdo->prepare("DELETE FROM emprestimos WHERE id = ?");
        $stmt_emprestimo->execute([$id]);

        $pdo->commit();

        header('Location: ../pages/emprestimos/index.php?deleted=1');
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        header('Location: ../pages/emprestimos/index.php?error=db_error');
        exit();
    }
}