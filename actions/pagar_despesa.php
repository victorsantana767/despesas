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
        $stmt = $pdo->prepare("UPDATE despesas SET status = 'pago' WHERE id = ?");
        $stmt->execute([$id]);

        // Redireciona de volta para a página de despesas, mantendo os filtros aplicados
        $redirect_url = $_SERVER['HTTP_REFERER'] ?? '../pages/despesas/index.php';

        // Se a origem for a página de empréstimos, usa um parâmetro de sucesso diferente
        if (strpos($redirect_url, 'emprestimos') !== false) {
            header('Location: ' . $redirect_url . (strpos($redirect_url, '?') ? '&' : '?') . 'success_pago=1');
        } else {
            header('Location: ' . $redirect_url . (strpos($redirect_url, '?') ? '&' : '?') . 'success=pago');
        }
        exit();

    } catch (PDOException $e) {
        header('Location: ../pages/despesas/index.php?error=db_error');
        exit();
    }
} else {
    header('Location: ../pages/dashboard.php');
    exit();
}