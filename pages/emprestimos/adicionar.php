<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';

if ($user_tipo !== 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

$stmt_admins = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo_acesso = 'admin' ORDER BY nome");
$admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Empréstimo</title>
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
                <h1 class="mb-4">Adicionar Novo Empréstimo</h1>

                <div class="card">
                    <div class="card-body">
                        <form action="../../actions/salvar_emprestimo.php" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="descricao" class="form-label">Descrição</label>
                                    <input type="text" class="form-control" id="descricao" name="descricao" placeholder="Ex: Empréstimo pessoal" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="banco" class="form-label">Banco</label>
                                    <input type="text" class="form-control" id="banco" name="banco" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="usuario_id" class="form-label">Contratante</label>
                                    <select class="form-select" id="usuario_id" name="usuario_id" required>
                                        <option value="">Selecione...</option>
                                        <?php foreach ($admins as $admin): ?>
                                            <option value="<?php echo $admin['id']; ?>"><?php echo htmlspecialchars($admin['nome']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="data_emprestimo" class="form-label">Data do Empréstimo</label>
                                    <input type="date" class="form-control" id="data_emprestimo" name="data_emprestimo" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="valor_emprestimo" class="form-label">Valor Total do Empréstimo (R$)</label>
                                    <input type="number" step="0.01" class="form-control" id="valor_emprestimo" name="valor_emprestimo" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="taxa_juros_anual" class="form-label">Taxa de Juros Anual (%)</label>
                                    <input type="number" step="0.01" class="form-control" id="taxa_juros_anual" name="taxa_juros_anual" placeholder="12.5">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="numero_parcelas" class="form-label">Número de Parcelas</label>
                                    <input type="number" class="form-control" id="numero_parcelas" name="numero_parcelas" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="valor_parcela" class="form-label">Valor da Parcela (R$)</label>
                                    <input type="number" step="0.01" class="form-control" id="valor_parcela" name="valor_parcela" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="dia_vencimento_parcela" class="form-label">Dia do Vencimento da Parcela</label>
                                    <input type="number" class="form-control" id="dia_vencimento_parcela" name="dia_vencimento_parcela" min="1" max="31" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary me-2"><i class="bi bi-save me-2"></i>Salvar Empréstimo</button>
                            <a href="index.php" class="btn btn-secondary"><i class="bi bi-x-circle me-2"></i>Cancelar</a>
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