<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/auth_check.php';
require_once '../config/config.php';

// Apenas admins podem executar esta ação
if ($user_tipo !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $despesa_id = $data['id'] ?? null;

    if (empty($despesa_id)) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'ID da despesa não fornecido.']);
        exit();
    }

    try {
        $sql = "UPDATE despesas SET status = 'pago' WHERE id = ? AND status = 'pendente'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$despesa_id]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados.']);
    }
}