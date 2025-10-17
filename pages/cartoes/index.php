<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';

// Apenas admins podem acessar esta página
if ($user_tipo !== 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

// Buscar todos os cartões, juntando com a tabela de usuários para pegar o nome do titular
$stmt = $pdo->query("
    SELECT c.id, c.nome_cartao, c.dia_vencimento_fatura, c.dia_fechamento_fatura, c.data_validade_cartao, u.nome as titular_nome
    FROM cartoes c
    JOIN usuarios u ON c.titular_id = u.id
    ORDER BY u.nome, c.nome_cartao
");
$cartoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Cartões</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <span id="sidebar-toggle" class="top-bar-toggle"><i class="bi bi-list fs-4"></i></span>
                </div>
            </nav>
            <main class="p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Gerenciar Cartões de Crédito</h1>
                    <a href="adicionar.php" class="btn btn-success"><i class="bi bi-credit-card-2-front-fill me-2"></i>Adicionar Novo Cartão</a>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">Cartão salvo com sucesso!</div>
                <?php endif; ?>
                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-success">Cartão excluído com sucesso!</div>
                <?php endif; ?>
                <?php if (isset($_GET['error']) && $_GET['error'] === 'foreign_key'): ?>
                    <div class="alert alert-danger"><b>Erro:</b> Não foi possível excluir o cartão, pois ele já está vinculado a uma ou mais despesas.</div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <?php if (empty($cartoes)): ?>
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle me-2"></i>
                                Nenhum cartão cadastrado ainda. Clique em "Adicionar Novo Cartão" para começar.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Nome do Cartão</th>
                                            <th>Titular</th>
                                            <th>Dia Fechamento</th>
                                            <th>Dia Vencimento</th>
                                            <th>Validade</th>
                                            <th class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cartoes as $cartao): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($cartao['nome_cartao']); ?></td>
                                            <td><?php echo htmlspecialchars($cartao['titular_nome']); ?></td>
                                            <td><?php echo $cartao['dia_fechamento_fatura']; ?></td>
                                            <td><?php echo $cartao['dia_vencimento_fatura']; ?></td>
                                            <td><?php echo htmlspecialchars($cartao['data_validade_cartao']); ?></td>
                                            <td class="text-center">
                                                <a href="editar.php?id=<?php echo $cartao['id']; ?>" class="btn btn-sm btn-primary" title="Editar Cartão">
                                                    <i class="bi bi-pencil-square"></i> Editar
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/scripts.js"></script>
</body>
</html>