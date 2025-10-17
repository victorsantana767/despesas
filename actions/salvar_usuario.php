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
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $tipo_acesso = $_POST['tipo_acesso'];

    try {
        if ($id) {
            // Atualizar
            if (!empty($senha)) {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET nome = :nome, email = :email, senha = :senha, tipo_acesso = :tipo_acesso WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':senha', $senha_hash);
            } else {
                $sql = "UPDATE usuarios SET nome = :nome, email = :email, tipo_acesso = :tipo_acesso WHERE id = :id";
                $stmt = $pdo->prepare($sql);
            }
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        } else {
            // Inserir
            if (empty($senha)) {
                header('Location: ../pages/usuarios/adicionar.php?error=Senha é obrigatória');
                exit();
            }
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (nome, email, senha, tipo_acesso) VALUES (:nome, :email, :senha, :tipo_acesso)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':senha', $senha_hash);
        }

        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':tipo_acesso', $tipo_acesso);
        $stmt->execute();

        header('Location: ../pages/usuarios/index.php?success=1');
        exit();

    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) { // Erro de entrada duplicada (email)
            header('Location: ../pages/usuarios/index.php?error=Email já cadastrado.');
        } else {
            header('Location: ../pages/usuarios/index.php?error=Ocorreu um erro no banco de dados.');
        }
        exit();
    }
}