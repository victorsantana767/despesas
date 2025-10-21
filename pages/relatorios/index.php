<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';
require_once '../../includes/helpers.php';

// --- Lógica de Filtragem ---

// 1. Inicializar variáveis
$despesas = [];
$total_despesas = 0;
$filtros_aplicados = false;

// 2. Pegar os valores dos filtros do formulário (se existirem)
$filtro_usuario_id = $_GET['usuario_id'] ?? null;
$filtro_data_inicio = $_GET['data_inicio'] ?? null;
$filtro_data_fim = $_GET['data_fim'] ?? null;

// 3. Lógica de permissão
if ($user_tipo === 'admin') {
    // Admin pode ver o filtro de todos os usuários
    $stmt_usuarios = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome");
    $usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Usuário de visualização só pode filtrar seus próprios dados
    $filtro_usuario_id = $user_id;
}

// 4. Se o formulário foi submetido, construir e executar a query
if (isset($_GET['filtrar'])) {
    $filtros_aplicados = true;
    $sql_base = "
        SELECT 
            d.descricao, 
            d.valor, 
            d.data_despesa, 
            dono.nome AS dono_divida_nome,
            comprador.nome AS comprador_nome, 
            d.status,
            d.metodo_pagamento,
            c.nome_cartao
        FROM despesas d
        JOIN usuarios dono ON d.dono_divida_id = dono.id
        JOIN usuarios comprador ON d.comprador_id = comprador.id
        LEFT JOIN cartoes c ON d.cartao_id = c.id
    ";

    $where_clauses = [];
    $params = [];

    if (!empty($filtro_usuario_id)) {
        $where_clauses[] = "d.dono_divida_id = :usuario_id";
        $params[':usuario_id'] = $filtro_usuario_id;
    }
    if (!empty($filtro_data_inicio)) {
        $where_clauses[] = "d.data_despesa >= :data_inicio";
        $params[':data_inicio'] = $filtro_data_inicio;
    }
    if (!empty($filtro_data_fim)) {
        $where_clauses[] = "d.data_despesa <= :data_fim";
        $params[':data_fim'] = $filtro_data_fim;
    }

    if (!empty($where_clauses)) {
        $sql_base .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $sql_base .= " ORDER BY d.data_despesa DESC";

    $stmt = $pdo->prepare($sql_base);
    $stmt->execute($params);
    $despesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular o total
    foreach ($despesas as $despesa) {
        $total_despesas += $despesa['valor'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios de Despesas</title>
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
                <h1 class="mb-4">Relatórios de Despesas</h1>

                <!-- Formulário de Filtros -->
                <div class="card mb-4">
                    <div class="card-header">Filtrar Despesas</div>
                    <div class="card-body">
                        <form method="GET" action="index.php">
                            <div class="row align-items-end">
                                <?php if ($user_tipo === 'admin'): ?>
                                    <div class="col-md-4 mb-3">
                                        <label for="usuario_id" class="form-label">Pessoa (Dono da Dívida)</label>
                                        <select class="form-select" id="usuario_id" name="usuario_id">
                                            <option value="">Todas as Pessoas</option>
                                            <?php foreach ($usuarios as $usuario): ?>
                                                <option value="<?php echo $usuario['id']; ?>" <?php echo ($filtro_usuario_id == $usuario['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($usuario['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                                <div class="col-md-3 mb-3">
                                    <label for="data_inicio" class="form-label">De</label>
                                    <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo htmlspecialchars($filtro_data_inicio ?? ''); ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="data_fim" class="form-label">Até</label>
                                    <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo htmlspecialchars($filtro_data_fim ?? ''); ?>">
                                </div>
                                <div class="col-md-2 mb-3">
                                    <button type="submit" name="filtrar" value="1" class="btn btn-primary w-100"><i class="bi bi-funnel-fill me-2"></i>Filtrar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Resultados -->
                <?php if ($filtros_aplicados): ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Resultados</span>
                            <div>
                                <?php
                                    // Constrói a query string para o link do PDF
                                    $pdf_params = http_build_query([
                                        'usuario_id' => $filtro_usuario_id,
                                        'data_inicio' => $filtro_data_inicio,
                                        'data_fim' => $filtro_data_fim
                                    ]);
                                ?>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#pdfPreviewModal" data-pdf-url="../../actions/exportar_relatorio_despesas_pdf.php?<?php echo $pdf_params; ?>&output=I" title="Pré-visualizar Relatório"><i class="bi bi-eye-fill"></i> Visualizar</button>
                                    <a href="../../actions/exportar_relatorio_despesas_pdf.php?<?php echo $pdf_params; ?>&output=D" class="btn btn-danger btn-sm" target="_blank" title="Baixar Relatório em PDF"><i class="bi bi-download"></i> Baixar PDF</a>
                                </div>
                                <span class="badge bg-primary rounded-pill fs-6 ms-3">Total: R$ <?php echo number_format($total_despesas, 2, ',', '.'); ?></span>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($despesas)): ?>
                                <div class="alert alert-warning text-center">Nenhuma despesa encontrada para os filtros selecionados.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover align-middle">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>Data</th>
                                                <th>Descrição</th>
                                                <th>Dono da Dívida</th>
                                                <th>Comprador</th>
                                                <th>Pagamento</th>
                                                <th>Status</th>
                                                <th class="text-end">Valor</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($despesas as $despesa): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($despesa['data_despesa'])); ?></td>
                                                <td><?php echo htmlspecialchars($despesa['descricao']); ?></td>
                                                <td><?php echo htmlspecialchars($despesa['dono_divida_nome']); ?></td>
                                                <td><?php echo htmlspecialchars($despesa['comprador_nome']); ?></td>
                                                <td>
                                                    <?php echo ucfirst(str_replace('_', ' ', $despesa['metodo_pagamento'])); ?>
                                                    <?php if ($despesa['nome_cartao']): ?>
                                                        <small class="d-block text-muted"><?php echo htmlspecialchars($despesa['nome_cartao']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo get_status_badge($despesa['status'], $despesa['data_despesa']); ?>
                                                </td>
                                                <td class="text-end"><?php echo number_format($despesa['valor'], 2, ',', '.'); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">Selecione os filtros acima e clique em "Filtrar" para gerar um relatório.</div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Modal para Pré-visualização de PDF -->
    <div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-labelledby="pdfPreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-fullscreen-lg-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pdfPreviewModalLabel">Pré-visualização do Relatório</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" style="height: 80vh;">
                    <iframe id="pdf-iframe" src="" width="100%" height="100%" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/scripts.js"></script>
    <script>
        // Script para carregar o PDF no modal de pré-visualização
        document.addEventListener('DOMContentLoaded', function () {
            const pdfPreviewModal = document.getElementById('pdfPreviewModal');
            if (pdfPreviewModal) {
                const iframe = document.getElementById('pdf-iframe');
                pdfPreviewModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const pdfUrl = button.getAttribute('data-pdf-url');
                    iframe.setAttribute('src', pdfUrl);
                });
            }
        });
    </script>
</body>
</html>