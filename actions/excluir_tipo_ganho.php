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
        header('Location: ../pages/ganhos/tipos_index.php');
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM tipos_ganho WHERE id = ?");
        $stmt->execute([$id]);

        header('Location: ../pages/ganhos/tipos_index.php?deleted=1');
        exit();

    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1451) {
            header('Location: ../pages/ganhos/tipos_index.php?error=Não é possível excluir, pois este tipo está vinculado a um ganho.');
            exit();
        }
        header('Location: ../pages/ganhos/tipos_index.php?error=Ocorreu um erro no banco de dados.');
        exit();
    }
}