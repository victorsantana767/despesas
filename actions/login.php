<?php
session_start();
require_once '../config/config.php'; // Inclui a conexão $pdo

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verifica se o usuário existe e se a senha está correta
    // password_verify() compara a senha digitada com o hash salvo no banco
    if ($user && password_verify($senha, $user['senha'])) {
        // Login bem-sucedido
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_tipo'] = $user['tipo_acesso'];
        header('Location: ../pages/dashboard.php');
        exit();
    } else {
        // Falha no login
        header('Location: ../index.php?error=1');
        exit();
    }
}
