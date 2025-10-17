<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';

// Apenas admins podem acessar
if ($user_tipo !== 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit();
}

// Buscar dados do cartão para edição
$stmt = $pdo->prepare("SELECT * FROM cartoes WHERE id = ?");
$stmt->execute([$id]);
$cartao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cartao) {
    header("Location: index.php");
    exit();
}

// Buscar usuários administradores para serem os titulares
$stmt_admins = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo_acesso = 'admin' ORDER BY nome");
$admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cartão</title>
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
                <h1 class="mb-4">Editar Cartão</h1>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Ocorreu um erro ao salvar as alterações do cartão. Por favor, tente novamente.
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form action="../../actions/salvar_cartao.php" method="POST">
                            <input type="hidden" name="id" value="<?php echo $cartao['id']; ?>">

                            <div class="mb-3">
                                <label for="nome_cartao" class="form-label">Nome do Cartão (Ex: Nubank, Inter Gold)</label>
                                <input type="text" class="form-control" id="nome_cartao" name="nome_cartao" value="<?php echo htmlspecialchars($cartao['nome_cartao']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="titular_id" class="form-label">Titular</label>
                                <select class="form-select" id="titular_id" name="titular_id" required>
                                    <option value="">Selecione o titular...</option>
                                    <?php foreach ($admins as $admin): ?>
                                        <option value="<?php echo $admin['id']; ?>" <?php echo ($admin['id'] == $cartao['titular_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($admin['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="dia_fechamento_fatura" class="form-label">Dia do Fechamento da Fatura</label>
                                    <input type="number" class="form-control" id="dia_fechamento_fatura" name="dia_fechamento_fatura" value="<?php echo $cartao['dia_fechamento_fatura']; ?>" min="1" max="31" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="dia_vencimento_fatura" class="form-label">Dia do Vencimento da Fatura</label>
                                    <input type="number" class="form-control" id="dia_vencimento_fatura" name="dia_vencimento_fatura" value="<?php echo $cartao['dia_vencimento_fatura']; ?>" min="1" max="31" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="data_validade_cartao" class="form-label">Validade (MM/AA)</label>
                                    <input type="text" class="form-control" id="data_validade_cartao" name="data_validade_cartao" value="<?php echo htmlspecialchars($cartao['data_validade_cartao']); ?>" placeholder="MM/AA" pattern="(0[1-9]|1[0-2])\/([0-9]{2})" maxlength="5" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary me-2"><i class="bi bi-save me-2"></i>Salvar Alterações</button>
                            <a href="index.php" class="btn btn-secondary"><i class="bi bi-x-circle me-2"></i>Cancelar</a>
                        </form>
                        <hr class="my-4">
                        <form action="../../actions/excluir_cartao.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este cartão? Esta ação não pode ser desfeita.');">
                            <input type="hidden" name="id" value="<?php echo $cartao['id']; ?>">
                            <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-2"></i>Excluir Cartão</button>
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