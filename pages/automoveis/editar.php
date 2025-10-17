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

// Buscar dados do automóvel
$stmt = $pdo->prepare("SELECT * FROM automoveis WHERE id = ?");
$stmt->execute([$id]);
$automovel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$automovel) {
    header("Location: index.php");
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
    <title>Editar Veículo</title>
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
                <h1 class="mb-4">Editar Veículo</h1>

                <div class="card">
                    <div class="card-body">
                        <form action="../../actions/salvar_automovel.php" method="POST">
                            <input type="hidden" name="id" value="<?php echo $automovel['id']; ?>">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="modelo" class="form-label">Modelo do Veículo</label>
                                    <input type="text" class="form-control" id="modelo" name="modelo" value="<?php echo htmlspecialchars($automovel['modelo']); ?>" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="placa" class="form-label">Placa</label>
                                    <input type="text" class="form-control" id="placa" name="placa" value="<?php echo htmlspecialchars($automovel['placa']); ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="ano" class="form-label">Ano</label>
                                    <input type="number" class="form-control" id="ano" name="ano" value="<?php echo $automovel['ano']; ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="data_compra" class="form-label">Data da Compra</label>
                                    <input type="date" class="form-control" id="data_compra" name="data_compra" value="<?php echo $automovel['data_compra']; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="valor_compra" class="form-label">Valor da Compra (R$)</label>
                                    <input type="number" step="0.01" class="form-control" id="valor_compra" name="valor_compra" value="<?php echo $automovel['valor_compra']; ?>">
                                </div>
                            </div>

                            <hr class="my-4">
                            <h5 class="mb-3">Informações de Pagamento</h5>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="forma_pagamento" class="form-label">Forma de Pagamento</label>
                                    <select class="form-select" id="forma_pagamento" name="forma_pagamento">
                                        <option value="a_vista" <?php echo ($automovel['forma_pagamento'] == 'a_vista') ? 'selected' : ''; ?>>À Vista</option>
                                        <option value="parcelado" <?php echo ($automovel['forma_pagamento'] == 'parcelado') ? 'selected' : ''; ?>>Parcelado</option>
                                    </select>
                                </div>
                            </div>

                            <div id="dados_parcelamento" style="display: <?php echo ($automovel['forma_pagamento'] == 'parcelado') ? 'block' : 'none'; ?>;">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="tipo_parcelamento" class="form-label">Tipo de Parcelamento</label>
                                        <select class="form-select" id="tipo_parcelamento" name="tipo_parcelamento">
                                            <option value="cartao_credito" <?php echo ($automovel['tipo_parcelamento'] == 'cartao_credito') ? 'selected' : ''; ?>>Cartão de Crédito</option>
                                            <option value="financiamento" <?php echo ($automovel['tipo_parcelamento'] == 'financiamento') ? 'selected' : ''; ?>>Financiamento</option>
                                            <option value="consorcio" <?php echo ($automovel['tipo_parcelamento'] == 'consorcio') ? 'selected' : ''; ?>>Consórcio</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3" id="campo_cartao">
                                        <label for="cartao_id" class="form-label">Cartão Utilizado</label>
                                        <select class="form-select" id="cartao_id" name="cartao_id">
                                            <option value="">Selecione o cartão...</option>
                                            <?php foreach ($cartoes as $cartao): ?>
                                                <option value="<?php echo $cartao['id']; ?>" <?php echo ($cartao['id'] == $automovel['cartao_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cartao['nome_cartao']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3" id="campo_vencimento_parcela">
                                        <label for="dia_vencimento_parcela" class="form-label">Dia do Vencimento da Parcela</label>
                                        <input type="number" class="form-control" id="dia_vencimento_parcela" name="dia_vencimento_parcela" value="<?php echo $automovel['dia_vencimento_parcela']; ?>" min="1" max="31">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="numero_parcelas" class="form-label">Número de Parcelas</label>
                                        <input type="number" class="form-control" id="numero_parcelas" name="numero_parcelas" value="<?php echo $automovel['numero_parcelas']; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="valor_parcela" class="form-label">Valor da Parcela (R$)</label>
                                        <input type="number" step="0.01" class="form-control" id="valor_parcela" name="valor_parcela" value="<?php echo $automovel['valor_parcela']; ?>">
                                    </div>
                                </div>
                                <div class="row" id="campo_consorcio">
                                    <div class="col-md-6 mb-3">
                                        <label for="valor_lance" class="form-label">Valor do Lance (se houver)</label>
                                        <input type="number" step="0.01" class="form-control" id="valor_lance" name="valor_lance" value="<?php echo $automovel['valor_lance']; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="data_lance" class="form-label">Data do Lance</label>
                                        <input type="date" class="form-control" id="data_lance" name="data_lance" value="<?php echo $automovel['data_lance']; ?>">
                                    </div>
                                </div>
                            </div>

                            <p class="text-warning"><i class="bi bi-exclamation-triangle-fill"></i> Atenção: Salvar as alterações irá apagar e recriar todas as parcelas de despesa associadas a este veículo.</p>

                            <button type="submit" class="btn btn-primary me-2"><i class="bi bi-save me-2"></i>Salvar Alterações</button>
                            <a href="index.php" class="btn btn-secondary"><i class="bi bi-x-circle me-2"></i>Cancelar</a>
                        </form>
                        <hr class="my-4">
                        <form action="../../actions/excluir_automovel.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este veículo? Todas as despesas associadas (parcelas, manutenções, etc) também serão removidas.');">
                            <input type="hidden" name="id" value="<?php echo $automovel['id']; ?>">
                            <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-2"></i>Excluir Veículo</button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/scripts.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const formaPagamento = document.getElementById('forma_pagamento');
            const tipoParcelamento = document.getElementById('tipo_parcelamento');

            function toggleParcelamento() {
                const parcelamentoDiv = document.getElementById('dados_parcelamento');
                parcelamentoDiv.style.display = formaPagamento.value === 'parcelado' ? 'block' : 'none';
            }

            function toggleTipoParcelamento() {
                const campoCartao = document.getElementById('campo_cartao');
                const campoVencimento = document.getElementById('campo_vencimento_parcela');
                const campoConsorcio = document.getElementById('campo_consorcio');

                if (tipoParcelamento.value === 'cartao_credito') {
                    campoCartao.style.display = 'block';
                    campoVencimento.style.display = 'none';
                    campoConsorcio.style.display = 'none';
                } else if (tipoParcelamento.value === 'financiamento') {
                    campoCartao.style.display = 'none';
                    campoVencimento.style.display = 'block';
                    campoConsorcio.style.display = 'none';
                } else { // consorcio
                    campoCartao.style.display = 'none';
                    campoVencimento.style.display = 'block';
                    campoConsorcio.style.display = 'block';
                }
            }

            formaPagamento.addEventListener('change', toggleParcelamento);
            tipoParcelamento.addEventListener('change', toggleTipoParcelamento);

            // Disparar eventos na carga da página para garantir o estado inicial correto
            toggleParcelamento();
            toggleTipoParcelamento();
        });
    </script>
</body>
</html>