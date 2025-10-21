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
    $descricao = trim($_POST['descricao']);
    $usuario_id = $_POST['usuario_id'];
    $tipo_ganho_id = $_POST['tipo_ganho_id'];
    $valor_base = $_POST['valor_base'];
    $dia_geracao = $_POST['dia_geracao'];
    $data_inicio = $_POST['data_inicio'];
    $data_fim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    try {
        if ($id) {
            // Atualizar
            $sql = "UPDATE ganhos_recorrentes SET descricao = ?, usuario_id = ?, tipo_ganho_id = ?, valor_base = ?, dia_geracao = ?, data_inicio = ?, data_fim = ?, ativo = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$descricao, $usuario_id, $tipo_ganho_id, $valor_base, $dia_geracao, $data_inicio, $data_fim, $ativo, $id]);
        } else {
            // Inserir
            $sql = "INSERT INTO ganhos_recorrentes (descricao, usuario_id, tipo_ganho_id, valor_base, dia_geracao, data_inicio, data_fim) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$descricao, $usuario_id, $tipo_ganho_id, $valor_base, $dia_geracao, $data_inicio, $data_fim]);
        }

        header('Location: ../pages/ganhos/index.php?success=recorrente_salvo');
        exit();

    } catch (PDOException $e) {
        // Em produção, logar o erro
        $redirect_url = $id ? "../pages/ganhos/recorrentes_editar.php?id=$id" : "../pages/ganhos/recorrentes_adicionar.php";
        header("Location: $redirect_url&error=db_error");
        exit();
    }
} else {
    header('Location: ../pages/ganhos/index.php');
    exit();
}