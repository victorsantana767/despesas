<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';

// Buscar dados para preencher os dropdowns
// 1. Todos os usuários (para "Dono da Dívida")
$stmt_usuarios = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome");
$usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);

// 2. Apenas os admins (para "Comprador")
$stmt_admins = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo_acesso = 'admin' ORDER BY nome");
$admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);

// 3. Todos os cartões cadastrados
$stmt_cartoes = $pdo->query("SELECT id, nome_cartao FROM cartoes ORDER BY nome_cartao");
$cartoes = $stmt_cartoes->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Despesa</title>
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
                <h1 class="mb-4">Adicionar Nova Despesa</h1>

                <div class="card">
                    <div class="card-body">
                        <form action="../../actions/salvar_despesa.php" method="POST">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="descricao" class="form-label">Descrição</label>
                                    <input type="text" class="form-control" id="descricao" name="descricao" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="valor" class="form-label">Valor (R$)</label>
                                    <input type="number" step="0.01" class="form-control" id="valor" name="valor" placeholder="19.99" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="data_despesa" class="form-label">Data da Despesa</label>
                                    <input type="date" class="form-control" id="data_despesa" name="data_despesa" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="dono_divida_id" class="form-label">Dono da Dívida (Para quem foi)</label>
                                    <select class="form-select" id="dono_divida_id" name="dono_divida_id" required>
                                        <option value="">Selecione...</option>
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <option value="<?php echo $usuario['id']; ?>"><?php echo htmlspecialchars($usuario['nome']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="comprador_id" class="form-label">Comprador (Quem pagou)</label>
                                    <select class="form-select" id="comprador_id" name="comprador_id" required>
                                        <option value="">Selecione...</option>
                                         <?php foreach ($admins as $admin): ?>
                                            <option value="<?php echo $admin['id']; ?>"><?php echo htmlspecialchars($admin['nome']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="metodo_pagamento" class="form-label">Método de Pagamento</label>
                                    <select class="form-select" id="metodo_pagamento" name="metodo_pagamento" required>
                                        <option value="dinheiro">Dinheiro</option>
                                        <option value="pix">Pix</option>
                                        <option value="cartao_credito">Cartão de Crédito</option>
                                        <option value="bemol_crediario">Crediário Bemol</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3" id="campo_cartao" style="display: none;">
                                    <label for="cartao_id" class="form-label">Qual Cartão?</label>
                                    <select class="form-select" id="cartao_id" name="cartao_id">
                                        <option value="">Nenhum</option>
                                        <?php foreach ($cartoes as $cartao): ?>
                                            <option value="<?php echo $cartao['id']; ?>"><?php echo htmlspecialchars($cartao['nome_cartao']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <!-- Campos específicos para Crediário Bemol -->
                                <div class="col-md-6" id="campo_bemol" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="numero_parcelas_bemol" class="form-label">Nº de Parcelas</label>
                                            <input type="number" class="form-control" id="numero_parcelas_bemol" name="numero_parcelas_bemol" min="1" value="1">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="dia_vencimento_parcela_bemol" class="form-label">Dia Venc.</label>
                                            <input type="number" class="form-control" id="dia_vencimento_parcela_bemol" name="dia_vencimento_parcela_bemol" min="1" max="31" placeholder="Dia">
                                            <div class="form-text" style="font-size: 0.75rem;">Padrão: dia da compra</div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="form-check mt-5">
                                                <input class="form-check-input" type="checkbox" id="teve_entrada" name="teve_entrada" value="1">
                                                <label class="form-check-label" for="teve_entrada">Teve entrada?</label>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="number" step="0.01" class="form-control mt-1" id="valor_entrada" name="valor_entrada" placeholder="Valor da Entrada" style="display: none;">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary me-2"><i class="bi bi-save me-2"></i>Salvar Despesa</button>
                            <a href="index.php" class="btn btn-secondary"><i class="bi bi-x-circle me-2"></i>Cancelar</a>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para mostrar o campo de cartão apenas quando necessário
        document.getElementById('metodo_pagamento').addEventListener('change', function () {
            const campoCartao = document.getElementById('campo_cartao');
            const campoBemol = document.getElementById('campo_bemol');
            const cartaoSelect = document.getElementById('cartao_id');
            const numeroParcelasBemol = document.getElementById('numero_parcelas_bemol');

            if (this.value === 'cartao_credito') {
                campoCartao.style.display = 'block';
                campoBemol.style.display = 'none';
                cartaoSelect.setAttribute('required', 'required');
                numeroParcelasBemol.removeAttribute('required');
            } else if (this.value === 'bemol_crediario') {
                campoCartao.style.display = 'none';
                campoBemol.style.display = 'block';
                cartaoSelect.removeAttribute('required');
                numeroParcelasBemol.setAttribute('required', 'required');
            } else {
                campoCartao.style.display = 'none';
                campoBemol.style.display = 'none';
                cartaoSelect.removeAttribute('required');
                numeroParcelasBemol.removeAttribute('required');
            }
        });

        document.getElementById('teve_entrada').addEventListener('change', function() {
            document.getElementById('valor_entrada').style.display = this.checked ? 'block' : 'none';
        });
    </script>
    <script src="../../assets/js/scripts.js"></script>
</body>
</html>
            }
        });
    </script>
    <script src="../../assets/js/scripts.js"></script>
</body>
</html>