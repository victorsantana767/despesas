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

$stmt = $pdo->prepare("SELECT id, nome, email, tipo_acesso FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário</title>
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
                <h1 class="mb-4">Editar Usuário</h1>

                <div class="card">
                    <div class="card-body">
                        <form action="../../actions/salvar_usuario.php" method="POST">
                            <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">

                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="tipo_acesso" class="form-label">Tipo de Acesso</label>
                                <select class="form-select" id="tipo_acesso" name="tipo_acesso" required>
                                    <option value="visualizacao" <?php echo ($usuario['tipo_acesso'] == 'visualizacao') ? 'selected' : ''; ?>>Visualização</option>
                                    <option value="admin" <?php echo ($usuario['tipo_acesso'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>

                            <hr>
                            <p class="text-muted">Deixe o campo de senha em branco para não alterá-la.</p>
                            <div class="mb-3">
                                <label for="senha" class="form-label">Nova Senha</label>
                                <input type="password" class="form-control" id="senha" name="senha">
                            </div>

                            <button type="submit" class="btn btn-primary me-2"><i class="bi bi-save me-2"></i>Salvar Alterações</button>
                            <a href="index.php" class="btn btn-secondary"><i class="bi bi-x-circle me-2"></i>Cancelar</a>
                        </form>
                        <hr class="my-4">
                        <form action="../../actions/excluir_usuario.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este usuário?');">
                            <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                            <button type="submit" class="btn btn-danger" <?php echo ($usuario['id'] == $user_id) ? 'disabled' : ''; ?>><i class="bi bi-trash me-2"></i>Excluir Usuário</button>
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