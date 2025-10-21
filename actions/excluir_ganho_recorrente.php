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
        $stmt = $pdo->prepare("DELETE FROM ganhos_recorrentes WHERE id = ?");
        $stmt->execute([$id]);

        header('Location: ../pages/ganhos/index.php?deleted=recorrente');
        exit();

    } catch (PDOException $e) {
        header('Location: ../pages/ganhos/index.php?error=db_error');
        exit();
    }
} else {
    header('Location: ../pages/ganhos/index.php');
    exit();
}
?>

Com esses dois novos arquivos, você agora tem o ciclo completo para gerenciar seus ganhos recorrentes: adicionar, editar e excluir.

<!--
[PROMPT_SUGGESTION]No dashboard, adicione um card com a previsão de ganhos para o mês atual.[/PROMPT_SUGGESTION]
[PROMPT_SUGGESTION]Na tabela de ganhos recorrentes, adicione um botão para desativar uma recorrência sem precisar excluí-la.[/PROMPT_SUGGESTION]
-->