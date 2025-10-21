<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';

// Apenas admins podem ver a lista completa de ganhos
if ($user_tipo !== 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

// Buscar todos os ganhos com informações detalhadas
$stmt = $pdo->query("
    SELECT 
        g.id, 
        g.valor, 
        g.data_ganho,
        u.nome AS usuario_nome,
        tg.nome AS tipo_ganho_nome
    FROM ganhos g
    JOIN usuarios u ON g.usuario_id = u.id
    JOIN tipos_ganho tg ON g.tipo_ganho_id = tg.id
    ORDER BY g.data_ganho DESC, g.id DESC
");
$ganhos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Ganhos</title>
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
                    <h1>Meus Ganhos</h1>
                    <a href="adicionar.php" class="btn btn-success"><i class="bi bi-plus-circle me-2"></i>Lançar Ganho</a>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">Ganho salvo com sucesso!</div>
                <?php endif; ?>
                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-success">Ganho excluído com sucesso!</div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <?php if (empty($ganhos)): ?>
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle me-2"></i>
                                Nenhum ganho lançado ainda. Clique em "Lançar Ganho" para começar.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Data</th>
                                            <th>Usuário</th>
                                            <th>Tipo de Ganho</th>
                                            <th>Valor</th>
                                            <th class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ganhos as $ganho): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($ganho['data_ganho'])); ?></td>
                                            <td><?php echo htmlspecialchars($ganho['usuario_nome']); ?></td>
                                            <td><?php echo htmlspecialchars($ganho['tipo_ganho_nome']); ?></td>
                                            <td><?php echo 'R$ ' . number_format($ganho['valor'], 2, ',', '.'); ?></td>
                                            <td class="text-center">
                                                <a href="editar.php?id=<?php echo $ganho['id']; ?>" class="btn btn-sm btn-primary" title="Editar Ganho">
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