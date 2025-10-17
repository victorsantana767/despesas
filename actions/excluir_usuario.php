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
        header('Location: ../pages/usuarios/index.php');
        exit();
    }

    // Impede que o usuário logado se auto-exclua
    if ($id == $user_id) {
        header('Location: ../pages/usuarios/index.php?error=Você não pode excluir sua própria conta.');
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);

        header('Location: ../pages/usuarios/index.php?deleted=1');
        exit();

    } catch (PDOException $e) {
        // Erro de chave estrangeira: usuário está vinculado a despesas
        if ($e->errorInfo[1] == 1451) {
            header('Location: ../pages/usuarios/index.php?error=Não é possível excluir o usuário, pois ele está vinculado a despesas ou cartões.');
            exit();
        }
        header('Location: ../pages/usuarios/index.php?error=Ocorreu um erro no banco de dados.');
        exit();
    }
}