<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';

if ($user_tipo !== 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: tipos_index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM tipos_ganho WHERE id = ?");
$stmt->execute([$id]);
$tipo_ganho = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tipo_ganho) {
    header("Location: tipos_index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Tipo de Ganho</title>
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
                <h1 class="mb-4">Editar Tipo de Ganho</h1>

                <div class="card">
                    <div class="card-body">
                        <form action="../../actions/salvar_tipo_ganho.php" method="POST">
                            <input type="hidden" name="id" value="<?php echo $tipo_ganho['id']; ?>">

                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($tipo_ganho['nome']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição (Opcional)</label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo htmlspecialchars($tipo_ganho['descricao']); ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary me-2"><i class="bi bi-save me-2"></i>Salvar Alterações</button>
                            <a href="tipos_index.php" class="btn btn-secondary"><i class="bi bi-x-circle me-2"></i>Cancelar</a>
                        </form>
                        <hr class="my-4">
                        <form action="../../actions/excluir_tipo_ganho.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este tipo de ganho?');">
                            <input type="hidden" name="id" value="<?php echo $tipo_ganho['id']; ?>">
                            <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-2"></i>Excluir</button>
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