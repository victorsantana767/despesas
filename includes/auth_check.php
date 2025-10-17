<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Se não estiver logado, redireciona para a página de login
    header('Location: ../index.php');
    exit();
}
// Define variáveis úteis para usar nas páginas
$user_id = $_SESSION['user_id'];
$user_nome = $_SESSION['user_nome'];
$user_tipo = $_SESSION['user_tipo'];
