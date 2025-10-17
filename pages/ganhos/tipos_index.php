<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';

// Apenas admins podem acessar esta página
if ($user_tipo !== 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

$stmt = $pdo->query("SELECT id, nome, descricao FROM tipos_ganho ORDER BY nome");
$tipos_ganho = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Tipos de Ganho</title>
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
                    <h1>Gerenciar Tipos de Ganho</h1>
                    <a href="tipos_adicionar.php" class="btn btn-success"><i class="bi bi-plus-circle me-2"></i>Adicionar Tipo</a>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">Tipo de ganho salvo com sucesso!</div>
                <?php endif; ?>
                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-success">Tipo de ganho excluído com sucesso!</div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <?php if (empty($tipos_ganho)): ?>
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle me-2"></i>
                                Nenhum tipo de ganho cadastrado. Clique em "Adicionar Tipo" para começar.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Nome</th>
                                            <th>Descrição</th>
                                            <th class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tipos_ganho as $tipo): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($tipo['nome']); ?></td>
                                            <td><?php echo htmlspecialchars($tipo['descricao']); ?></td>
                                            <td class="text-center">
                                                <a href="tipos_editar.php?id=<?php echo $tipo['id']; ?>" class="btn btn-sm btn-primary" title="Editar Tipo de Ganho">
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