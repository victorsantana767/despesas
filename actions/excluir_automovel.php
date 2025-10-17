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
        header('Location: ../pages/automoveis/index.php');
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1. Excluir da tabela de junção 'despesas_automoveis'
        $stmt1 = $pdo->prepare("DELETE FROM despesas_automoveis WHERE automovel_id = ?");
        $stmt1->execute([$id]);

        // 2. Excluir as despesas gerais associadas (parcelas, etc.)
        $stmt2 = $pdo->prepare("DELETE FROM despesas WHERE automovel_id = ?");
        $stmt2->execute([$id]);

        // 3. Excluir o automóvel
        $stmt3 = $pdo->prepare("DELETE FROM automoveis WHERE id = ?");
        $stmt3->execute([$id]);

        $pdo->commit();

        header('Location: ../pages/automoveis/index.php?deleted=1');
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        header('Location: ../pages/automoveis/index.php?error=db_error');
        exit();
    }
}