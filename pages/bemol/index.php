<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';
require_once '../../includes/helpers.php';

// Apenas admins podem acessar esta página
if ($user_tipo !== 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

// --- Lógica de Filtragem ---
$filtro_mes_ano = $_GET['mes_ano'] ?? date('Y-m');
$data_inicio = date('Y-m-01', strtotime($filtro_mes_ano));
$data_fim = date('Y-m-t', strtotime($filtro_mes_ano));

// --- Lógica de Busca ---
$compras_bemol = [];
try {
    // 1. Encontrar todos os grupo_parcela_id de compras Bemol que têm parcelas no mês filtrado
    $sql_grupos = "
        SELECT DISTINCT grupo_parcela_id 
        FROM despesas 
        WHERE metodo_pagamento = 'bemol_crediario' 
        AND data_despesa BETWEEN :data_inicio AND :data_fim
    ";
    $stmt_grupos = $pdo->prepare($sql_grupos);
    $stmt_grupos->execute([':data_inicio' => $data_inicio, ':data_fim' => $data_fim]);
    $grupos_ids = $stmt_grupos->fetchAll(PDO::FETCH_COLUMN);

    // 2. Se encontramos grupos, buscar todas as despesas (parcelas e entradas) relacionadas a eles
    if (!empty($grupos_ids)) {
        $placeholders = implode(',', array_fill(0, count($grupos_ids), '?'));
        $sql_despesas = "
            SELECT 
                id, descricao, valor, data_despesa, data_compra, status, grupo_parcela_id, metodo_pagamento
            FROM despesas
            WHERE grupo_parcela_id IN ($placeholders)
            ORDER BY grupo_parcela_id, data_despesa ASC
        ";
        $stmt_despesas = $pdo->prepare($sql_despesas);
        $stmt_despesas->execute($grupos_ids);
        $todas_despesas = $stmt_despesas->fetchAll(PDO::FETCH_ASSOC);

        // 3. Agrupar as despesas por compra (grupo_parcela_id)
        foreach ($todas_despesas as $despesa) {
            $grupo_id = $despesa['grupo_parcela_id'];
            if (!isset($compras_bemol[$grupo_id])) {
                // Extrai a descrição base da primeira parcela encontrada
                $descricao_base = preg_replace('/ \(Parcela \d+\/\d+\)$/', '', $despesa['descricao']);
                if (strpos($descricao_base, 'Entrada Bemol: ') === 0) {
                    $descricao_base = substr($descricao_base, strlen('Entrada Bemol: '));
                }
                
                $compras_bemol[$grupo_id] = [
                    'descricao_base' => $descricao_base,
                    'data_compra' => $despesa['data_compra'] ?? $despesa['data_despesa'],
                    'total_compra' => 0,
                    'parcelas' => []
                ];
            }
            $compras_bemol[$grupo_id]['parcelas'][] = $despesa;
            $compras_bemol[$grupo_id]['total_compra'] += $despesa['valor'];
        }
    }
} catch (PDOException $e) {
    // Em um ambiente real, logar o erro
    die("Erro ao buscar compras da Bemol: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compras Bemol</title>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Compras Bemol</h1>
                </div>

                <!-- Formulário de Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="index.php" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label for="mes_ano" class="form-label">Visualizar Parcelas de (Mês/Ano)</label>
                                <input type="month" class="form-control" id="mes_ano" name="mes_ano" value="<?php echo htmlspecialchars($filtro_mes_ano); ?>">
                            </div>
                            <div class="col-md-3 d-grid">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel-fill"></i> Filtrar</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <?php if (empty($compras_bemol)): ?>
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle me-2"></i>
                                Nenhuma compra da Bemol com parcelas vencendo neste mês.
                            </div>
                        <?php else: ?>
                            <div class="accordion" id="accordionComprasBemol">
                                <?php foreach ($compras_bemol as $grupo_id => $compra): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading-<?php echo $grupo_id; ?>">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $grupo_id; ?>" aria-expanded="false" aria-controls="collapse-<?php echo $grupo_id; ?>">
                                                <div class="w-100 d-flex justify-content-between pe-3">
                                                    <div>
                                                        <span><strong><?php echo htmlspecialchars($compra['descricao_base']); ?></strong></span>
                                                        <small class="d-block text-muted">Compra em: <?php echo date('d/m/Y', strtotime($compra['data_compra'])); ?></small>
                                                    </div>
                                                    <span class="badge bg-primary d-flex align-items-center">
                                                        Total: R$ <?php echo number_format($compra['total_compra'], 2, ',', '.'); ?>
                                                    </span>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="collapse-<?php echo $grupo_id; ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?php echo $grupo_id; ?>" data-bs-parent="#accordionComprasBemol">
                                            <div class="accordion-body">
                                                <ul class="list-group">
                                                    <?php foreach ($compra['parcelas'] as $parcela): ?>
                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <span class="me-3"><?php echo htmlspecialchars($parcela['descricao']); ?></span>
                                                                <small class="text-muted">(Venc: <?php echo date('d/m/Y', strtotime($parcela['data_despesa'])); ?>)</small>
                                                            </div>
                                                            <div>
                                                                <?php echo get_status_badge($parcela['status'], $parcela['data_despesa']); ?>
                                                                <span class="ms-3 fw-bold">R$ <?php echo number_format($parcela['valor'], 2, ',', '.'); ?></span>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                                <div class="mt-3">
                                                    <a href="editar.php?grupo_parcela_id=<?php echo htmlspecialchars($grupo_id); ?>" class="btn btn-sm btn-info"><i class="bi bi-pencil-square me-1"></i> Editar Compra Completa</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/scripts.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pagarButtons = document.querySelectorAll('.btn-pagar-parcela');

            pagarButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const despesaId = this.getAttribute('data-id');
                    
                    if (!confirm('Tem certeza que deseja marcar esta parcela como paga?')) {
                        return;
                    }

                    fetch('../../actions/pagar_parcela_ajax.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ id: despesaId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const statusContainer = document.getElementById(`status-container-${despesaId}`);
                            if (statusContainer) {
                                statusContainer.innerHTML = '<span class="badge bg-success">Pago</span>';
                            }
                        } else {
                            alert('Ocorreu um erro ao tentar pagar a parcela: ' + (data.message || 'Erro desconhecido.'));
                        }
                    })
                    .catch(error => console.error('Erro na requisição:', error));
                });
            });
        });
    </script>
</body>
</html>