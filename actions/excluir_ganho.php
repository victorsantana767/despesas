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
        header('Location: ../pages/ganhos/index.php');
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM ganhos WHERE id = ?");
        $stmt->execute([$id]);

        header('Location: ../pages/ganhos/index.php?deleted=1');
        exit();

    } catch (PDOException $e) {
        header('Location: ../pages/ganhos/index.php?error=db_error');
        exit();
    }
} else {
    header('Location: ../pages/dashboard.php');
    exit();
}