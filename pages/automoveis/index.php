<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';

// Apenas admins podem acessar
if ($user_tipo !== 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

$stmt = $pdo->query("SELECT * FROM automoveis ORDER BY modelo");
$automoveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Automóveis</title>
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
                    <h1>Listagem de Veículos</h1>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">Veículo salvo com sucesso!</div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <?php if (empty($automoveis)): ?>
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle me-2"></i>
                                Nenhum veículo cadastrado. Clique em "Adicionar Veículo" para começar.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Modelo</th>
                                            <th>Placa</th>
                                            <th>Ano</th>
                                            <th>Data da Compra</th>
                                            <th>Valor da Compra</th>
                                            <th class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($automoveis as $auto): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($auto['modelo']); ?></td>
                                            <td><?php echo htmlspecialchars($auto['placa']); ?></td>
                                            <td><?php echo $auto['ano']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($auto['data_compra'])); ?></td>
                                            <td><?php echo 'R$ ' . number_format($auto['valor_compra'], 2, ',', '.'); ?></td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="despesas.php?automovel_id=<?php echo $auto['id']; ?>" class="btn btn-sm btn-info" title="Ver Despesas">
                                                        <i class="bi bi-receipt"></i> Despesas
                                                    </a>
                                                    <a href="editar.php?id=<?php echo $auto['id']; ?>" class="btn btn-sm btn-primary" title="Editar Veículo">
                                                        <i class="bi bi-pencil-square"></i> Editar
                                                    </a>
                                                </div>
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