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
    $descricao = trim($_POST['descricao']) ?? null;

    try {
        if ($id) {
            // Atualizar
            $sql = "UPDATE tipos_ganho SET nome = :nome, descricao = :descricao WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        } else {
            // Inserir
            $sql = "INSERT INTO tipos_ganho (nome, descricao) VALUES (:nome, :descricao)";
            $stmt = $pdo->prepare($sql);
        }

        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->execute();

        header('Location: ../pages/ganhos/tipos_index.php?success=1');
        exit();

    } catch (PDOException $e) {
        header('Location: ../pages/ganhos/tipos_index.php?error=Ocorreu um erro no banco de dados.');
        exit();
    }
}