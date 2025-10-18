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

        // Redireciona para a página anterior, adicionando o parâmetro de sucesso.
        $redirect_url = $_SERVER['HTTP_REFERER'] ?? '../pages/despesas/index.php';
        $separator = strpos($redirect_url, '?') === false ? '?' : '&';
        header('Location: ' . $redirect_url . $separator . 'deleted=1');
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