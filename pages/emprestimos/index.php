<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';
require_once '../../includes/helpers.php';

if ($user_tipo !== 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

$stmt = $pdo->query("
    SELECT e.*, u.nome as usuario_nome 
    FROM emprestimos e
    JOIN usuarios u ON e.usuario_id = u.id
    ORDER BY e.data_emprestimo DESC
");
$emprestimos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepara uma query para buscar as parcelas de cada empréstimo
$stmt_parcelas = $pdo->prepare("
    SELECT id, descricao, valor, data_despesa, status 
    FROM despesas 
    WHERE emprestimo_id = ? 
    ORDER BY data_despesa ASC
");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Empréstimos</title>
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
                    <h1>Gerenciar Empréstimos</h1>
                    <a href="adicionar.php" class="btn btn-success"><i class="bi bi-plus-circle me-2"></i>Adicionar Empréstimo</a>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">Empréstimo salvo com sucesso!</div>
                <?php endif; ?>
                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-success">Empréstimo excluído com sucesso!</div>
                <?php endif; ?>
                <?php if (isset($_GET['success_pago'])): ?>
                    <div class="alert alert-success">Parcela marcada como paga!</div>
                <?php endif; ?>

                <?php if (empty($emprestimos)): ?>
                    <div class="alert alert-info text-center">Nenhum empréstimo cadastrado.</div>
                <?php else: ?>
                    <div class="accordion" id="accordionEmprestimos">
                        <?php foreach ($emprestimos as $emprestimo): ?>
                            <?php
                                // Busca as parcelas para o empréstimo atual
                                $stmt_parcelas->execute([$emprestimo['id']]);
                                $parcelas = $stmt_parcelas->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading-<?php echo $emprestimo['id']; ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $emprestimo['id']; ?>" aria-expanded="false" aria-controls="collapse-<?php echo $emprestimo['id']; ?>">
                                        <div class="w-100 d-flex justify-content-between pe-3">
                                            <span><strong><?php echo htmlspecialchars($emprestimo['descricao']); ?></strong> (<?php echo htmlspecialchars($emprestimo['banco']); ?>)</span>
                                            <span class="badge bg-secondary"><?php echo $emprestimo['numero_parcelas'] . 'x de R$ ' . number_format($emprestimo['valor_parcela'], 2, ',', '.'); ?></span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse-<?php echo $emprestimo['id']; ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?php echo $emprestimo['id']; ?>" data-bs-parent="#accordionEmprestimos">
                                    <div class="accordion-body">
                                        <h5 class="mb-3">Parcelas</h5>
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Vencimento</th>
                                                    <th>Valor</th>
                                                    <th>Status</th>
                                                    <th class="text-center">Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($parcelas as $parcela): ?>
                                                    <tr>
                                                        <td><?php echo date('d/m/Y', strtotime($parcela['data_despesa'])); ?></td>
                                                        <td>R$ <?php echo number_format($parcela['valor'], 2, ',', '.'); ?></td>
                                                        <td>
                                                            <?php echo get_status_badge($parcela['status'], $parcela['data_despesa']); ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="btn-group">
                                                                <?php if ($parcela['status'] == 'pendente'): ?>
                                                                    <form action="../../actions/pagar_despesa.php" method="POST" class="d-inline">
                                                                        <input type="hidden" name="id" value="<?php echo $parcela['id']; ?>">
                                                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Marcar como Paga"><i class="bi bi-check-circle"></i></button>
                                                                    </form>
                                                                <?php endif; ?>
                                                                <a href="../despesas/editar.php?id=<?php echo $parcela['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar Parcela">
                                                                    <i class="bi bi-pencil-square"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <a href="editar.php?id=<?php echo $emprestimo['id']; ?>" class="btn btn-outline-secondary btn-sm mt-2">
                                            <i class="bi bi-gear-fill"></i> Editar Dados Gerais do Empréstimo
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/scripts.js"></script>
</body>
</html>