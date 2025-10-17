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
    $usuario_id = $_POST['usuario_id'];
    $tipo_ganho_id = $_POST['tipo_ganho_id'];
    $valor = $_POST['valor'];
    $data_ganho = $_POST['data_ganho'];

    try {
        if ($id) {
            // Atualizar
            $sql = "UPDATE ganhos SET usuario_id = :usuario_id, tipo_ganho_id = :tipo_ganho_id, valor = :valor, data_ganho = :data_ganho WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        } else {
            // Inserir
            $sql = "INSERT INTO ganhos (usuario_id, tipo_ganho_id, valor, data_ganho) VALUES (:usuario_id, :tipo_ganho_id, :valor, :data_ganho)";
            $stmt = $pdo->prepare($sql);
        }

        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->bindParam(':tipo_ganho_id', $tipo_ganho_id, PDO::PARAM_INT);
        $stmt->bindParam(':valor', $valor);
        $stmt->bindParam(':data_ganho', $data_ganho);
        $stmt->execute();

        header('Location: ../pages/ganhos/index.php?success=1');
        exit();

    } catch (PDOException $e) {
        $redirect_url = $id ? "../pages/ganhos/editar.php?id=$id" : "../pages/ganhos/adicionar.php";
        header("Location: $redirect_url&error=db_error");
        exit();
    }
} else {
    header('Location: ../pages/dashboard.php');
    exit();
}