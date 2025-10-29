<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';
require_once '../../includes/helpers.php';

// Apenas admins podem acessar esta página
if ($user_tipo !== 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

// Buscar todos os cartões, juntando com a tabela de usuários para pegar o nome do titular
$stmt = $pdo->query("
    SELECT c.id, c.nome_cartao, c.dia_vencimento_fatura, c.dia_fechamento_fatura, c.data_validade_cartao, u.nome as titular_nome
    FROM cartoes c
    JOIN usuarios u ON c.titular_id = u.id
    ORDER BY u.nome, c.nome_cartao
");
$cartoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Lógica de Filtragem ---
$filtro_mes_ano = $_GET['mes_ano'] ?? date('Y-m');

// Otimização (Evitar N+1 Query): Buscar todas as despesas de cartão de crédito
$all_despesas_cartao = [];
if (!empty($cartoes)) {
    // Busca despesas em um range maior para garantir que todas as faturas sejam cobertas
    // Busca despesas cuja data de vencimento (data_despesa) seja no mês filtrado.
    $data_base = new DateTime($filtro_mes_ano . '-01');
    $inicio_busca = $data_base->format('Y-m-01');
    $fim_busca = $data_base->format('Y-m-t');

    $sql_despesas = "
        SELECT id, cartao_id, descricao, valor, data_despesa, COALESCE(data_compra, data_despesa) as data_compra, status 
        FROM despesas 
        WHERE cartao_id IS NOT NULL
        AND metodo_pagamento = 'cartao_credito'
        AND data_despesa BETWEEN ? AND ? -- Filtra pelo mês de vencimento da parcela
        ORDER BY data_despesa DESC
    ";
    $stmt_despesas = $pdo->prepare($sql_despesas);
    $stmt_despesas->execute([$inicio_busca, $fim_busca]);
    $despesas_raw = $stmt_despesas->fetchAll(PDO::FETCH_ASSOC);

    // Agrupa as despesas por cartao_id para fácil acesso
    foreach ($despesas_raw as $despesa) {
        $all_despesas_cartao[$despesa['cartao_id']][] = $despesa;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Cartões</title>
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
                    <h1>Faturas de Cartão de Crédito</h1>
                </div>

                <!-- Formulário de Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="index.php" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label for="mes_ano" class="form-label">Visualizar Fatura de (Mês/Ano)</label>
                                <input type="month" class="form-control" id="mes_ano" name="mes_ano" value="<?php echo htmlspecialchars($filtro_mes_ano); ?>">
                            </div>
                            <div class="col-md-3 d-grid">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel-fill"></i> Filtrar Faturas</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">Cartão salvo com sucesso!</div>
                <?php endif; ?>
                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-success">Cartão excluído com sucesso!</div>
                <?php endif; ?>
                <?php if (isset($_GET['fatura_paga'])): ?>
                    <div class="alert alert-success">Fatura marcada como paga com sucesso!</div>
                <?php endif; ?>
                <?php if (isset($_GET['error']) && $_GET['error'] === 'foreign_key'): ?>
                    <div class="alert alert-danger"><b>Erro:</b> Não foi possível excluir o cartão, pois ele já está vinculado a uma ou mais despesas.</div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <?php if (empty($cartoes)): ?>
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle me-2"></i>
                                Nenhum cartão cadastrado ainda. Clique em "Adicionar Novo Cartão" para começar.
                            </div>
                        <?php else: ?>
                            <div class="accordion" id="accordionCartoes">
                                <?php foreach ($cartoes as $cartao): ?>
                                    <?php
                                        // A data de vencimento da fatura é o mês que está sendo filtrado
                                        $mes_vencimento_fatura = new DateTime($filtro_mes_ano . '-' . $cartao['dia_vencimento_fatura']);
                                        
                                        // As despesas já foram pré-filtradas por mês de vencimento na consulta SQL inicial.
                                        $despesas_cartao = $all_despesas_cartao[$cartao['id']] ?? [];

                                        $total_fatura = array_sum(array_column($despesas_cartao, 'valor'));

                                        // Verifica se há despesas pendentes para exibir o botão de pagar
                                        $despesas_pendentes = array_filter($despesas_cartao, function($despesa) {
                                            return $despesa['status'] === 'pendente';
                                        });
                                        $todos_pagos = empty($despesas_pendentes);
                                    ?>
                                    <?php
                                        // A extensão intl do PHP precisa estar habilitada no php.ini
                                        // Traduz o nome do mês para português
                                        $formatter = new IntlDateFormatter(
                                            'pt_BR',
                                            IntlDateFormatter::FULL,
                                            IntlDateFormatter::NONE,
                                            'America/Sao_Paulo',
                                            IntlDateFormatter::GREGORIAN,
                                            'MMMM \'de\' yyyy'
                                        );
                                        $nome_fatura_pt = ucfirst($formatter->format($mes_vencimento_fatura));
                                    ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading-<?php echo $cartao['id']; ?>">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $cartao['id']; ?>" aria-expanded="false" aria-controls="collapse-<?php echo $cartao['id']; ?>">
                                                <div class="w-100 d-flex justify-content-between pe-3">
                                                    <div>
                                                        <span><strong><?php echo htmlspecialchars($cartao['nome_cartao']); ?></strong> (Titular: <?php echo htmlspecialchars($cartao['titular_nome']); ?>)</span>
                                                        <small class="d-block text-muted">Fatura de <?php echo $nome_fatura_pt; ?> - Venc. <?php echo $mes_vencimento_fatura->format('d/m/Y'); ?></small>
                                                    </div>
                                                    <?php if ($total_fatura > 0): ?>
                                                        <span class="badge bg-primary d-flex align-items-center">
                                                            Total: R$ <?php echo number_format($total_fatura, 2, ',', '.'); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="collapse-<?php echo $cartao['id']; ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?php echo $cartao['id']; ?>" data-bs-parent="#accordionCartoes">
                                            <div class="accordion-body">
                                                <?php if (empty($despesas_cartao)): ?>
                                                    <p class="text-muted">Nenhuma despesa encontrada para este cartão neste período.</p>
                                                <?php else: ?>
                                                    <table class="table table-sm table-bordered">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Data</th>
                                                                <th>Descrição</th>
                                                                <th>Status</th>
                                                                <th class="text-end">Valor</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($despesas_cartao as $despesa): ?>
                                                                <tr>
                                                                    <td><?php echo date('d/m/Y', strtotime($despesa['data_despesa'])); ?></td>
                                                                    <td><?php echo htmlspecialchars($despesa['descricao']); ?></td>
                                                                    <td>
                                                                        <?php echo get_status_badge($despesa['status'], $despesa['data_despesa']); ?>
                                                                    </td>
                                                                    <td class="text-end">R$ <?php echo number_format($despesa['valor'], 2, ',', '.'); ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                <?php endif; ?>
                                                <div class="d-flex align-items-center gap-2 mt-2">
                                                    <?php if (!$todos_pagos): ?>
                                                        <form action="../../actions/pagar_fatura_cartao.php" method="POST" class="d-inline-block" onsubmit="return confirm('Tem certeza que deseja marcar todas as despesas pendentes desta fatura como pagas?');">
                                                            <input type="hidden" name="cartao_id" value="<?php echo $cartao['id']; ?>">
                                                            <input type="hidden" name="mes_ano" value="<?php echo $filtro_mes_ano; ?>">
                                                            <button type="submit" class="btn btn-success btn-sm">
                                                                <i class="bi bi-check-all"></i> Pagar Fatura
                                                            </button>
                                                        </form>
                                                    <?php elseif ($total_fatura > 0): ?>
                                                        <span class="badge bg-success p-2"><i class="bi bi-check-circle-fill me-1"></i> Fatura Paga</span>
                                                    <?php endif; ?>

                                                    <a href="editar.php?id=<?php echo $cartao['id']; ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-gear-fill"></i> Editar Cartão</a>
                                                    
                                                    <?php if ($total_fatura > 0): ?>
                                                        <?php $fatura_pdf_url = "../../actions/exportar_fatura_pdf.php?cartao_id={$cartao['id']}&mes_ano=$filtro_mes_ano"; ?>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-outline-secondary btn-sm btn-preview" data-bs-toggle="modal" data-bs-target="#pdfPreviewModal" data-pdf-url="<?php echo $fatura_pdf_url; ?>&output=I" title="Pré-visualizar Fatura"><i class="bi bi-eye"></i></button>
                                                            <a href="<?php echo $fatura_pdf_url; ?>&output=D" class="btn btn-outline-danger btn-sm" title="Exportar Fatura"><i class="bi bi-file-earmark-pdf"></i></a>
                                                        </div>
                                                    <?php endif; ?>
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

    <!-- Modal para Pré-visualização de PDF -->
    <div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-labelledby="pdfPreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-fullscreen-lg-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pdfPreviewModalLabel">Pré-visualização do Documento</h5>
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
        document.addEventListener('DOMContentLoaded', function () {
            const pdfPreviewModal = document.getElementById('pdfPreviewModal');
            const iframe = document.getElementById('pdf-iframe');

            pdfPreviewModal.addEventListener('show.bs.modal', function (event) {
                // Botão que acionou o modal
                const button = event.relatedTarget;
                const pdfUrl = button.getAttribute('data-pdf-url');
                iframe.setAttribute('src', pdfUrl);
            });
        });
    </script>
</body>
</html>
