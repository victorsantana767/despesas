<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';

if ($user_tipo !== 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit();
}

// Buscar dados do ganho para edição
$stmt = $pdo->prepare("SELECT * FROM ganhos WHERE id = ?");
$stmt->execute([$id]);
$ganho = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ganho) {
    header("Location: index.php");
    exit();
}

// Buscar dados para preencher os dropdowns
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
    <title>Editar Ganho</title>
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
                <h1 class="mb-4">Editar Ganho</h1>

                <div class="card">
                    <div class="card-body">
                        <form action="../../actions/salvar_ganho.php" method="POST">
                            <input type="hidden" name="id" value="<?php echo $ganho['id']; ?>">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="usuario_id" class="form-label">Usuário (Quem recebeu)</label>
                                    <select class="form-select" id="usuario_id" name="usuario_id" required>
                                        <?php foreach ($admins as $admin): ?>
                                            <option value="<?php echo $admin['id']; ?>" <?php echo ($admin['id'] == $ganho['usuario_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($admin['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tipo_ganho_id" class="form-label">Tipo de Ganho</label>
                                    <select class="form-select" id="tipo_ganho_id" name="tipo_ganho_id" required>
                                        <?php foreach ($tipos_ganho as $tipo): ?>
                                            <option value="<?php echo $tipo['id']; ?>" <?php echo ($tipo['id'] == $ganho['tipo_ganho_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tipo['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="valor" class="form-label">Valor (R$)</label>
                                    <input type="number" step="0.01" class="form-control" id="valor" name="valor" value="<?php echo $ganho['valor']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="data_ganho" class="form-label">Data do Ganho</label>
                                    <input type="date" class="form-control" id="data_ganho" name="data_ganho" value="<?php echo $ganho['data_ganho']; ?>" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary me-2"><i class="bi bi-save me-2"></i>Salvar Alterações</button>
                            <a href="index.php" class="btn btn-secondary"><i class="bi bi-x-circle me-2"></i>Cancelar</a>
                        </form>
                        <hr class="my-4">
                        <form action="../../actions/excluir_ganho.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este lançamento?');">
                            <input type="hidden" name="id" value="<?php echo $ganho['id']; ?>">
                            <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-2"></i>Excluir Ganho</button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/scripts.js"></script>
</body>
</html>