<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit();
}

// Buscar dados da despesa para edição
$stmt = $pdo->prepare("SELECT * FROM despesas WHERE id = ?");
$stmt->execute([$id]);
$despesa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$despesa) {
    header("Location: index.php");
    exit();
}

// Buscar dados para preencher os dropdowns
$stmt_usuarios = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome");
$usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);

$stmt_admins = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo_acesso = 'admin' ORDER BY nome");
$admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);

$stmt_cartoes = $pdo->query("SELECT id, nome_cartao FROM cartoes ORDER BY nome_cartao");
$cartoes = $stmt_cartoes->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Despesa</title>
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
                <h1 class="mb-4">Editar Despesa</h1>

                <div class="card">
                    <div class="card-body">
                        <form action="../../actions/salvar_despesa.php" method="POST">
                            <input type="hidden" name="id" value="<?php echo $despesa['id']; ?>">

                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="descricao" class="form-label">Descrição</label>
                                    <input type="text" class="form-control" id="descricao" name="descricao" value="<?php echo htmlspecialchars($despesa['descricao']); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="valor" class="form-label">Valor (R$)</label>
                                    <input type="number" step="0.01" class="form-control" id="valor" name="valor" value="<?php echo $despesa['valor']; ?>" placeholder="19.99" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="data_despesa" class="form-label">Data da Despesa</label>
                                    <input type="date" class="form-control" id="data_despesa" name="data_despesa" value="<?php echo $despesa['data_despesa']; ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="dono_divida_id" class="form-label">Dono da Dívida (Para quem foi)</label>
                                    <select class="form-select" id="dono_divida_id" name="dono_divida_id" required>
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <option value="<?php echo $usuario['id']; ?>" <?php echo ($usuario['id'] == $despesa['dono_divida_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($usuario['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="comprador_id" class="form-label">Comprador (Quem pagou)</label>
                                    <select class="form-select" id="comprador_id" name="comprador_id" required>
                                         <?php foreach ($admins as $admin): ?>
                                            <option value="<?php echo $admin['id']; ?>" <?php echo ($admin['id'] == $despesa['comprador_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($admin['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="metodo_pagamento" class="form-label">Método de Pagamento</label>
                                    <select class="form-select" id="metodo_pagamento" name="metodo_pagamento" required>
                                        <option value="dinheiro" <?php echo ($despesa['metodo_pagamento'] == 'dinheiro') ? 'selected' : ''; ?>>Dinheiro</option>
                                        <option value="pix" <?php echo ($despesa['metodo_pagamento'] == 'pix') ? 'selected' : ''; ?>>Pix</option>
                                        <option value="cartao_credito" <?php echo ($despesa['metodo_pagamento'] == 'cartao_credito') ? 'selected' : ''; ?>>Cartão de Crédito</option>
                                        <option value="bemol_crediario" <?php echo ($despesa['metodo_pagamento'] == 'bemol_crediario') ? 'selected' : ''; ?>>Crediário Bemol</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3" id="campo_cartao" style="display: <?php echo ($despesa['metodo_pagamento'] == 'cartao_credito') ? 'block' : 'none'; ?>;">
                                    <label for="cartao_id" class="form-label">Qual Cartão?</label>
                                    <select class="form-select" id="cartao_id" name="cartao_id">
                                        <option value="">Nenhum</option>
                                        <?php foreach ($cartoes as $cartao): ?>
                                            <option value="<?php echo $cartao['id']; ?>" <?php echo ($cartao['id'] == $despesa['cartao_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cartao['nome_cartao']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary me-2"><i class="bi bi-save me-2"></i>Salvar Alterações</button>
                            <a href="index.php" class="btn btn-secondary"><i class="bi bi-x-circle me-2"></i>Cancelar</a>
                        </form>
                        <hr class="my-4">
                        <form action="../../actions/excluir_despesa.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta despesa?');">
                            <input type="hidden" name="id" value="<?php echo $despesa['id']; ?>">
                            <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-2"></i>Excluir Despesa</button>
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