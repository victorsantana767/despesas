<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';

if ($user_tipo !== 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit();
}

// Buscar dados do ganho recorrente
$stmt = $pdo->prepare("SELECT * FROM ganhos_recorrentes WHERE id = ?");
$stmt->execute([$id]);
$rec = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rec) {
    header('Location: index.php?error=not_found');
    exit();
}

// Buscar dados para os dropdowns
$stmt_admins = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo_acesso = 'admin' ORDER BY nome");
$admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);

$stmt_tipos = $pdo->query("SELECT id, nome FROM tipos_ganho ORDER BY nome");
$tipos_ganho = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Ganho Recorrente</title>
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
                <h1 class="mb-4">Editar Ganho Recorrente</h1>
                <div class="card">
                    <div class="card-body">
                        <form action="../../actions/salvar_ganho_recorrente.php" method="POST">
                            <input type="hidden" name="id" value="<?php echo $rec['id']; ?>">
                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição</label>
                                <input type="text" class="form-control" id="descricao" name="descricao" value="<?php echo htmlspecialchars($rec['descricao']); ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="usuario_id" class="form-label">Usuário</label>
                                    <select class="form-select" id="usuario_id" name="usuario_id" required>
                                        <?php foreach ($admins as $admin): ?>
                                            <option value="<?php echo $admin['id']; ?>" <?php echo ($admin['id'] == $rec['usuario_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($admin['nome']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tipo_ganho_id" class="form-label">Tipo de Ganho</label>
                                    <select class="form-select" id="tipo_ganho_id" name="tipo_ganho_id" required>
                                        <?php foreach ($tipos_ganho as $tipo): ?>
                                            <option value="<?php echo $tipo['id']; ?>" <?php echo ($tipo['id'] == $rec['tipo_ganho_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tipo['nome']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="valor_base" class="form-label">Valor Base (R$)</label>
                                    <input type="number" step="0.01" class="form-control" id="valor_base" name="valor_base" value="<?php echo htmlspecialchars($rec['valor_base']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="dia_geracao" class="form-label">Gerar todo dia</label>
                                    <input type="number" class="form-control" id="dia_geracao" name="dia_geracao" min="1" max="31" value="<?php echo htmlspecialchars($rec['dia_geracao']); ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="data_inicio" class="form-label">Início da Recorrência</label>
                                    <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo htmlspecialchars($rec['data_inicio']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="data_fim" class="form-label">Fim da Recorrência (Opcional)</label>
                                    <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo htmlspecialchars($rec['data_fim'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" value="1" <?php echo $rec['ativo'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ativo">Recorrência Ativa</label>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <div>
                                    <button type="submit" class="btn btn-primary me-2"><i class="bi bi-save me-2"></i>Salvar Alterações</button>
                                    <a href="index.php" class="btn btn-secondary"><i class="bi bi-x-circle me-2"></i>Cancelar</a>
                                </div>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"><i class="bi bi-trash me-2"></i>Excluir</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal de Exclusão -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir este ganho recorrente? Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <form action="../../actions/excluir_ganho_recorrente.php" method="POST">
                        <input type="hidden" name="id" value="<?php echo $rec['id']; ?>">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Sim, Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/scripts.js"></script>
</body>
</html>