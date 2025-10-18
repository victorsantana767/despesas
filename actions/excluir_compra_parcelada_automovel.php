<?php
session_start();
require_once '../includes/auth_check.php';
require_once '../config/config.php';

if ($user_tipo !== 'admin') {
    header("Location: ../pages/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grupo_id = $_POST['grupo_parcela_id'] ?? null;
    $automovel_id = $_POST['automovel_id'] ?? null;

    if (!$grupo_id || !$automovel_id) {
        header("Location: ../pages/automoveis/index.php");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1. Encontra todos os IDs de despesa do grupo
        $stmt_find = $pdo->prepare("SELECT id FROM despesas WHERE grupo_parcela_id = ?");
        $stmt_find->execute([$grupo_id]);
        $despesa_ids = $stmt_find->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($despesa_ids)) {
            // 2. Exclui da tabela de junção 'despesas_automoveis'
            $placeholders = implode(',', array_fill(0, count($despesa_ids), '?'));
            $stmt_delete_join = $pdo->prepare("DELETE FROM despesas_automoveis WHERE despesa_id IN ($placeholders)");
            $stmt_delete_join->execute($despesa_ids);

            // 3. Exclui as despesas da tabela principal
            $stmt_delete_main = $pdo->prepare("DELETE FROM despesas WHERE grupo_parcela_id = ?");
            $stmt_delete_main->execute([$grupo_id]);
        }

        $pdo->commit();
        header("Location: ../pages/automoveis/despesas.php?automovel_id=$automovel_id&deleted=1");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        header("Location: ../pages/automoveis/despesas.php?automovel_id=$automovel_id&error=db_error");
        exit();
    }
}