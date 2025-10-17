<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';

$automovel_id = $_GET['automovel_id'] ?? null;
if (!$automovel_id) {
    header("Location: index.php");
    exit();
}

// Buscar dados do automóvel
$stmt_auto = $pdo->prepare("SELECT modelo FROM automoveis WHERE id = ?");
$stmt_auto->execute([$automovel_id]);
$automovel = $stmt_auto->fetch(PDO::FETCH_ASSOC);
if (!$automovel) {
    header("Location: index.php");
    exit();
}

// Buscar despesas do automóvel
$stmt_despesas = $pdo->prepare("
    SELECT d.*, da.tipo_despesa, u.nome as comprador_nome
    FROM despesas d
    JOIN despesas_automoveis da ON d.id = da.despesa_id
    JOIN usuarios u ON d.comprador_id = u.id
    WHERE da.automovel_id = ?
    ORDER BY d.data_despesa DESC
");
$stmt_despesas->execute([$automovel_id]);
$despesas = $stmt_despesas->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Despesas do <?php echo htmlspecialchars($automovel['modelo']); ?></title>
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
                    <h1>Despesas do <?php echo htmlspecialchars($automovel['modelo']); ?></h1>
                    <a href="adicionar_despesa.php?automovel_id=<?php echo $automovel_id; ?>" class="btn btn-success"><i class="bi bi-plus-circle me-2"></i>Adicionar Despesa</a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <?php if (empty($despesas)): ?>
                            <div class="alert alert-info text-center">Nenhuma despesa registrada para este veículo.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Data</th>
                                            <th>Descrição</th>
                                            <th>Tipo</th>
                                            <th>Comprador</th>
                                            <th>Valor</th>
                                            <th class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($despesas as $despesa): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($despesa['data_despesa'])); ?></td>
                                            <td><?php echo htmlspecialchars($despesa['descricao']); ?></td>
                                            <td><?php echo ucfirst($despesa['tipo_despesa']); ?></td>
                                            <td><?php echo htmlspecialchars($despesa['comprador_nome']); ?></td>
                                            <td><?php echo 'R$ ' . number_format($despesa['valor'], 2, ',', '.'); ?></td>
                                            <td class="text-center">
                                                <a href="../despesas/editar.php?id=<?php echo $despesa['id']; ?>" class="btn btn-sm btn-primary" title="Editar Despesa">
                                                    <i class="bi bi-pencil-square"></i>
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
                <div class="mt-3">
                    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Voltar para a lista de veículos</a>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/scripts.js"></script>
</body>
</html>