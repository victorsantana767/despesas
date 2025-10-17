<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';

if ($user_tipo !== 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

// Buscar cartões para o dropdown
$stmt_cartoes = $pdo->query("SELECT id, nome_cartao FROM cartoes ORDER BY nome_cartao");
$cartoes = $stmt_cartoes->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Veículo</title>
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
                <h1 class="mb-4">Adicionar Novo Veículo</h1>

                <div class="card">
                    <div class="card-body">
                        <form action="../../actions/salvar_automovel.php" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="modelo" class="form-label">Modelo do Veículo</label>
                                    <input type="text" class="form-control" id="modelo" name="modelo" placeholder="Ex: Honda Civic" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="placa" class="form-label">Placa</label>
                                    <input type="text" class="form-control" id="placa" name="placa" placeholder="ABC1D23">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="ano" class="form-label">Ano</label>
                                    <input type="number" class="form-control" id="ano" name="ano" placeholder="2023">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="data_compra" class="form-label">Data da Compra</label>
                                    <input type="date" class="form-control" id="data_compra" name="data_compra">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="valor_compra" class="form-label">Valor da Compra (R$)</label>
                                    <input type="number" step="0.01" class="form-control" id="valor_compra" name="valor_compra" placeholder="85000.00">
                                </div>
                            </div>

                            <hr class="my-4">
                            <h5 class="mb-3">Informações de Pagamento</h5>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="forma_pagamento" class="form-label">Forma de Pagamento</label>
                                    <select class="form-select" id="forma_pagamento" name="forma_pagamento">
                                        <option value="a_vista" selected>À Vista</option>
                                        <option value="parcelado">Parcelado</option>
                                    </select>
                                </div>
                            </div>

                            <div id="dados_parcelamento" style="display: none;">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="tipo_parcelamento" class="form-label">Tipo de Parcelamento</label>
                                        <select class="form-select" id="tipo_parcelamento" name="tipo_parcelamento">
                                            <option value="cartao_credito">Cartão de Crédito</option>
                                            <option value="financiamento">Financiamento</option>
                                            <option value="consorcio">Consórcio</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3" id="campo_cartao">
                                        <label for="cartao_id" class="form-label">Cartão Utilizado</label>
                                        <select class="form-select" id="cartao_id" name="cartao_id">
                                            <option value="">Selecione o cartão...</option>
                                            <?php foreach ($cartoes as $cartao): ?>
                                                <option value="<?php echo $cartao['id']; ?>"><?php echo htmlspecialchars($cartao['nome_cartao']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3" id="campo_vencimento_parcela" style="display: none;">
                                        <label for="dia_vencimento_parcela" class="form-label">Dia do Vencimento da Parcela</label>
                                        <input type="number" class="form-control" id="dia_vencimento_parcela" name="dia_vencimento_parcela" min="1" max="31">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="numero_parcelas" class="form-label">Número de Parcelas</label>
                                        <input type="number" class="form-control" id="numero_parcelas" name="numero_parcelas">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="valor_parcela" class="form-label">Valor da Parcela (R$)</label>
                                        <input type="number" step="0.01" class="form-control" id="valor_parcela" name="valor_parcela">
                                    </div>
                                </div>
                                <!-- Campos específicos para Consórcio -->
                                <div class="row" id="campo_consorcio" style="display: none;">
                                    <div class="col-md-6 mb-3">
                                        <label for="valor_lance" class="form-label">Valor do Lance (se houver)</label>
                                        <input type="number" step="0.01" class="form-control" id="valor_lance" name="valor_lance" placeholder="20000.00">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="data_lance" class="form-label">Data do Lance</label>
                                        <input type="date" class="form-control" id="data_lance" name="data_lance">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary me-2"><i class="bi bi-save me-2"></i>Salvar Veículo</button>
                            <a href="index.php" class="btn btn-secondary"><i class="bi bi-x-circle me-2"></i>Cancelar</a>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/scripts.js"></script>
    <script>
        document.getElementById('forma_pagamento').addEventListener('change', function() {
            const parcelamentoDiv = document.getElementById('dados_parcelamento');
            parcelamentoDiv.style.display = this.value === 'parcelado' ? 'block' : 'none';
        });

        document.getElementById('tipo_parcelamento').addEventListener('change', function() {
            const campoCartao = document.getElementById('campo_cartao');
            const campoVencimento = document.getElementById('campo_vencimento_parcela');
            const campoConsorcio = document.getElementById('campo_consorcio');

            if (this.value === 'cartao_credito') {
                campoCartao.style.display = 'block';
                campoVencimento.style.display = 'none';
                campoConsorcio.style.display = 'none';
            } else if (this.value === 'financiamento') {
                campoCartao.style.display = 'none';
                campoVencimento.style.display = 'block';
                campoConsorcio.style.display = 'none';
            } else { // consorcio
                campoCartao.style.display = 'none';
                campoVencimento.style.display = 'block'; // Consórcio também tem vencimento de parcela
                campoConsorcio.style.display = 'block';
            }
        });

        // Disparar o evento 'change' na carga da página para garantir o estado correto
        document.getElementById('forma_pagamento').dispatchEvent(new Event('change'));
        document.getElementById('tipo_parcelamento').dispatchEvent(new Event('change'));
    </script>
</body>
</html>