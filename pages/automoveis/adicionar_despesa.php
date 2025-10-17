<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';

$automovel_id = $_GET['automovel_id'] ?? null;
if (!$automovel_id) {
    header("Location: index.php");
    exit();
}

// Buscar dados para preencher os dropdowns
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
    <title>Adicionar Despesa de Veículo</title>
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
                <h1 class="mb-4">Adicionar Despesa de Veículo</h1>

                <div class="card">
                    <div class="card-body">
                        <form action="../../actions/salvar_despesa_automovel.php" method="POST">
                            <input type="hidden" name="automovel_id" value="<?php echo $automovel_id; ?>">
                            <input type="hidden" name="dono_divida_id" value="<?php echo $user_id; ?>">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="descricao" class="form-label">Descrição</label>
                                    <input type="text" class="form-control" id="descricao" name="descricao" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="valor" class="form-label">Valor (R$)</label>
                                    <input type="number" step="0.01" class="form-control" id="valor" name="valor" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="data_despesa" class="form-label">Data</label>
                                    <input type="date" class="form-control" id="data_despesa" name="data_despesa" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <hr>
                            <h5 class="mb-3">Detalhes da Despesa</h5>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="tipo_despesa" class="form-label">Tipo de Despesa</label>
                                    <select class="form-select" id="tipo_despesa" name="tipo_despesa" required>
                                        <option value="combustivel">Combustível</option>
                                        <option value="manutencao">Manutenção</option>
                                        <option value="seguro">Seguro</option>
                                        <option value="ipva">IPVA</option>
                                        <option value="multa">Multa</option>
                                        <option value="estacionamento">Estacionamento</option>
                                        <option value="outros">Outros</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3" id="campo_litros" style="display: block;">
                                    <label for="litros_combustivel" class="form-label">Litros Abastecidos</label>
                                    <input type="number" step="0.01" class="form-control" id="litros_combustivel" name="litros_combustivel">
                                </div>
                                <div class="col-md-4 mb-3" id="campo_km" style="display: none;">
                                    <label for="quilometragem" class="form-label">Quilometragem</label>
                                    <input type="number" class="form-control" id="quilometragem" name="quilometragem">
                                </div>
                            </div>

                            <hr>
                            <h5 class="mb-3">Informações de Pagamento</h5>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="comprador_id" class="form-label">Quem Pagou?</label>
                                    <select class="form-select" id="comprador_id" name="comprador_id" required>
                                        <?php foreach ($admins as $admin): ?>
                                            <option value="<?php echo $admin['id']; ?>"><?php echo htmlspecialchars($admin['nome']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="metodo_pagamento" class="form-label">Método de Pagamento</label>
                                    <select class="form-select" id="metodo_pagamento" name="metodo_pagamento" required>
                                        <option value="dinheiro">Dinheiro</option>
                                        <option value="pix">Pix</option>
                                        <option value="cartao_credito">Cartão de Crédito</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3" id="campo_cartao" style="display: none;">
                                    <label for="cartao_id" class="form-label">Qual Cartão?</label>
                                    <select class="form-select" id="cartao_id" name="cartao_id">
                                        <option value="">Nenhum</option>
                                        <?php foreach ($cartoes as $cartao): ?>
                                            <option value="<?php echo $cartao['id']; ?>"><?php echo htmlspecialchars($cartao['nome_cartao']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3" id="campo_parcelas" style="display: none;">
                                    <label for="numero_parcelas" class="form-label">Nº de Parcelas</label>
                                    <input type="number" class="form-control" id="numero_parcelas" name="numero_parcelas" value="1" min="1">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary me-2"><i class="bi bi-save me-2"></i>Salvar Despesa</button>
                            <a href="despesas.php?automovel_id=<?php echo $automovel_id; ?>" class="btn btn-secondary"><i class="bi bi-x-circle me-2"></i>Cancelar</a>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/scripts.js"></script>
    <script>
        document.getElementById('tipo_despesa').addEventListener('change', function() {
            const campoLitros = document.getElementById('campo_litros');
            const campoKm = document.getElementById('campo_km');
            campoLitros.style.display = this.value === 'combustivel' ? 'block' : 'none';
            campoKm.style.display = this.value === 'manutencao' ? 'block' : 'none';
        });
        document.getElementById('metodo_pagamento').addEventListener('change', function () {
            const isCartao = this.value === 'cartao_credito';
            document.getElementById('campo_cartao').style.display = isCartao ? 'block' : 'none';
            document.getElementById('campo_parcelas').style.display = isCartao ? 'block' : 'none';
            if (isCartao) {
                document.getElementById('cartao_id').setAttribute('required', 'required');
            } else {
                document.getElementById('cartao_id').removeAttribute('required');
            }
        });
    </script>
</body>
</html>