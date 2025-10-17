<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;

    if (!$id) {
        header('Location: ../pages/despesas/index.php');
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM despesas WHERE id = ?");
        $stmt->execute([$id]);

        header('Location: ../pages/despesas/index.php?deleted=1');
        exit();

    } catch (PDOException $e) {
        // Logar o erro em um ambiente real
        header('Location: ../pages/despesas/index.php?error=db_error');
        exit();
    }
} else {
    header('Location: ../pages/dashboard.php');
    exit();
}