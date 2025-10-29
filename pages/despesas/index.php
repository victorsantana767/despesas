<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';
require_once '../../includes/helpers.php';

// Buscar todas as despesas com informações detalhadas
// --- Lógica de Filtragem e Paginação ---
$filtro_mes_ano = $_GET['mes_ano'] ?? date('Y-m');
$filtro_usuario_ids = isset($_GET['usuario_id']) && is_array($_GET['usuario_id']) ? $_GET['usuario_id'] : [];
$filtro_status = $_GET['status'] ?? null;
$pagina_atual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itens_por_pagina = 15; // Você pode ajustar este valor
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$data_inicio = date('Y-m-01', strtotime($filtro_mes_ano));
$data_fim = date('Y-m-t', strtotime($filtro_mes_ano));

$sql_base = "FROM despesas d
    JOIN usuarios dono ON d.dono_divida_id = dono.id
    JOIN usuarios comprador ON d.comprador_id = comprador.id
    LEFT JOIN cartoes c ON d.cartao_id = c.id";

$sql_select = "
    SELECT
        d.id, 
        d.descricao, 
        d.valor, 
        d.data_despesa, 
        d.metodo_pagamento,
        d.status,
        dono.nome AS dono_divida_nome,
        comprador.nome AS comprador_nome,
        c.nome_cartao
";
$where_clauses = [];
$params = [];

// Filtro de data sempre aplicado
$where_clauses[] = "(d.data_despesa BETWEEN ? AND ?)";
$params[] = $data_inicio;
$params[] = $data_fim;

// Filtro por tipo de usuário
if ($user_tipo === 'visualizacao') {
    $where_clauses[] = "d.dono_divida_id = ?";
    $params[] = $user_id;
} elseif (!empty($filtro_usuario_ids)) {
    // Se for admin e um ou mais usuários foram selecionados
    // Cria placeholders (?) para cada ID de usuário
    $placeholders = implode(',', array_fill(0, count($filtro_usuario_ids), '?'));
    $where_clauses[] = "d.dono_divida_id IN ($placeholders)";
    foreach ($filtro_usuario_ids as $uid) {
        $params[] = $uid;
    }
}

// Filtro por status
if (!empty($filtro_status)) {
    if ($filtro_status === 'atrasado') {
        $where_clauses[] = "d.status = 'pendente' AND d.data_despesa < CURDATE()";
    } elseif ($filtro_status === 'pendente') {
        $where_clauses[] = "d.status = 'pendente' AND d.data_despesa >= CURDATE()";
    } else { // 'pago'
        $where_clauses[] = "d.status = ?";
        $params[] = $filtro_status;
    }
}

$sql_where = "";
if (!empty($where_clauses)) {
    $sql_where = " WHERE " . implode(" AND ", $where_clauses);
}

// --- Contagem do total de itens para paginação ---
$sql_count = "SELECT COUNT(d.id) " . $sql_base . $sql_where;
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_itens = $stmt_count->fetchColumn();
$total_paginas = ceil($total_itens / $itens_por_pagina);

// --- Soma do total de despesas para o filtro atual ---
$sql_sum = "SELECT SUM(d.valor) " . $sql_base . $sql_where;
$stmt_sum = $pdo->prepare($sql_sum);
$stmt_sum->execute($params);
$total_despesas_filtradas = $stmt_sum->fetchColumn() ?: 0;

// --- Busca dos itens da página atual ---
$sql_final = $sql_select . $sql_base . $sql_where . " ORDER BY dono.nome ASC, d.status ASC, d.data_despesa DESC, d.id DESC LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql_final);

// Bind dos parâmetros da cláusula WHERE
foreach ($params as $key => $value) {
    $stmt->bindValue($key + 1, $value);
}

// Bind dos parâmetros de LIMIT e OFFSET, especificando que são inteiros
$stmt->bindValue(count($params) + 1, $itens_por_pagina, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);

$stmt->execute();

$despesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar despesas por dono da dívida
$despesas_agrupadas = [];
$totais_por_dono = [];
foreach ($despesas as $despesa) {
    $dono_nome = $despesa['dono_divida_nome'];
    $despesas_agrupadas[$dono_nome][] = $despesa;
    if (!isset($totais_por_dono[$dono_nome])) {
        $totais_por_dono[$dono_nome] = 0;
    }
    $totais_por_dono[$dono_nome] += $despesa['valor'];
}

// Buscar usuários para o dropdown de filtro (apenas para admins)
$usuarios_filtro = [];
if ($user_tipo === 'admin') {
    $stmt_usuarios = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome");
    $usuarios_filtro = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);
}

// --- Lógica para exibir os filtros ---
// Formatar o mês/ano para exibição
$formatter = new IntlDateFormatter(
    'pt_BR',
    IntlDateFormatter::FULL,
    IntlDateFormatter::NONE,
    'America/Sao_Paulo',
    IntlDateFormatter::GREGORIAN,
    'MMMM \'de\' yyyy'
);
$mes_ano_exibicao = ucfirst($formatter->format(new DateTime($filtro_mes_ano)));

// Nomes dos usuários selecionados para exibição
$nomes_usuarios_selecionados = 'Todos';
if (!empty($filtro_usuario_ids)) {
    $nomes = array_map(function($id) use ($usuarios_filtro) {
        return $usuarios_filtro[array_search($id, array_column($usuarios_filtro, 'id'))]['nome'];
    }, $filtro_usuario_ids);
    $nomes_usuarios_selecionados = implode(', ', $nomes);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Despesas</title>
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
            <main style="background-color: #f8f9fa;">
                <div class="container-fluid p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1>Listagem de Despesas</h1>
                    </div>

                    <!-- Card de Resumo do Total -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card text-white bg-secondary">
                                <div class="card-body">
                                    <h5 class="card-title">Total para Filtros Atuais</h5>
                                    <p class="card-text fs-4">
                                        <?php echo 'R$ ' . number_format($total_despesas_filtradas, 2, ',', '.'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($_GET['success']) && $_GET['success'] == 'despesa_adicionada'): ?>
                        <div class="alert alert-success">Despesa salva com sucesso!</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['success']) && $_GET['success'] == 'pago'): ?>
                        <div class="alert alert-success">Despesa marcada como paga!</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['deleted'])): ?>
                        <div class="alert alert-success">Despesa excluída com sucesso!</div>
                    <?php endif; ?>

                    <!-- Formulário de Filtros -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" action="index.php" class="row g-3 align-items-end">
                                <div class="<?php echo ($user_tipo === 'admin') ? 'col-lg-3' : 'col-lg-4'; ?> col-md-6 col-sm-12">
                                    <label for="mes_ano" class="form-label">Filtrar por Mês/Ano</label>
                                    <input type="month" class="form-control" id="mes_ano" name="mes_ano" value="<?php echo htmlspecialchars($filtro_mes_ano); ?>">
                                </div>
                                <?php if ($user_tipo === 'admin'): ?>
                                <div class="col-lg-3 col-md-6 col-sm-12">
                                    <label for="usuario_id" class="form-label">Pessoa(s)</label>
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                            Selecionar Pessoas
                                        </button>
                                        <ul class="dropdown-menu w-100" aria-labelledby="dropdownMenuButton">
                                            <?php foreach ($usuarios_filtro as $usuario): ?>
                                            <li class="px-3 py-1">
                                                <div class="form-check">
                                                    <input class="form-check-input filtro-usuario-check" type="checkbox" name="usuario_id[]" value="<?php echo $usuario['id']; ?>" id="user_<?php echo $usuario['id']; ?>" <?php echo in_array($usuario['id'], $filtro_usuario_ids) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="user_<?php echo $usuario['id']; ?>">
                                                        <?php echo htmlspecialchars($usuario['nome']); ?>
                                                    </label>
                                                </div>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="<?php echo ($user_tipo === 'admin') ? 'col-lg-3' : 'col-lg-4'; ?> col-md-6 col-sm-12">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">Todos</option>
                                        <option value="pendente" <?php echo ($filtro_status == 'pendente') ? 'selected' : ''; ?>>Pendente</option>
                                        <option value="atrasado" <?php echo ($filtro_status == 'atrasado') ? 'selected' : ''; ?>>Atrasado</option>
                                        <option value="pago" <?php echo ($filtro_status == 'pago') ? 'selected' : ''; ?>>Pago</option>
                                    </select>
                                </div>
                                <div class="col-lg-3 col-md-12 col-sm-12">
                                    <div class="d-flex w-100">
                                        <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel-fill"></i> Filtrar</button>
                                        <?php
                                            // Constrói a query string para os usuários
                                            $user_query_string = http_build_query(['usuario_id' => $filtro_usuario_ids]);
                                            // Constrói os parâmetros para o script de relatório correto
                                            $pdf_params = http_build_query([
                                                'usuario_id' => $filtro_usuario_ids,
                                                'data_inicio' => $data_inicio,
                                                'data_fim' => $data_fim
                                            ]);
                                            $pdf_url = "../../actions/exportar_relatorio_despesas_pdf.php?" . $pdf_params;
                                        ?>
                                        <div class="btn-group ms-2" role="group">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#pdfPreviewModal" data-pdf-url="<?php echo $pdf_url; ?>&output=I" title="Pré-visualizar PDF"><i class="bi bi-eye-fill me-1"></i>Visualizar</button>
                                            <a href="<?php echo $pdf_url; ?>&output=D" class="btn btn-danger btn-sm" target="_blank" title="Baixar PDF">
                                                <i class="bi bi-download me-1"></i>Baixar PDF
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($despesas)): ?>
                                <div class="alert alert-info text-center">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Nenhuma despesa cadastrada ainda. Clique em "Adicionar Despesa" para começar.
                                </div>
                            <?php else: ?>
                                <div class="accordion" id="accordionDespesas">
                                    <?php foreach ($despesas_agrupadas as $dono_nome => $despesas_do_dono): ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading-<?php echo md5($dono_nome); ?>">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo md5($dono_nome); ?>">
                                                    <div class="w-100 d-flex justify-content-between pe-3">
                                                        <span><strong><?php echo htmlspecialchars($dono_nome); ?></strong></span>
                                                        <span class="badge bg-secondary d-flex align-items-center">Total: R$ <?php echo number_format($totais_por_dono[$dono_nome], 2, ',', '.'); ?></span>
                                                    </div>
                                                </button>
                                            </h2>
                                            <div id="collapse-<?php echo md5($dono_nome); ?>" class="accordion-collapse collapse" data-bs-parent="#accordionDespesas">
                                                <div class="accordion-body p-0">
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-hover align-middle mb-0">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Descrição</th>
                                                                    <th>Valor</th>
                                                                    <th>Data</th>
                                                                    <th>Comprador</th>
                                                                    <th>Pagamento</th>
                                                                    <th>Status</th>
                                                                    <th class="text-center">Ações</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($despesas_do_dono as $despesa): ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars($despesa['descricao']); ?></td>
                                                                    <td><?php echo 'R$ ' . number_format($despesa['valor'], 2, ',', '.'); ?></td>
                                                                    <td><?php echo date('d/m/Y', strtotime($despesa['data_despesa'])); ?></td>
                                                                    <td><?php echo htmlspecialchars($despesa['comprador_nome']); ?></td>
                                                                    <td>
                                                                        <?php echo ucfirst(str_replace('_', ' ', $despesa['metodo_pagamento'])); ?>
                                                                        <?php if ($despesa['metodo_pagamento'] == 'cartao_credito' && $despesa['nome_cartao']): ?>
                                                                            <small class="d-block text-muted"><?php echo htmlspecialchars($despesa['nome_cartao']); ?></small>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php echo get_status_badge($despesa['status'], $despesa['data_despesa']); ?>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <div class="btn-group">
                                                                            <?php if ($despesa['status'] == 'pendente' && $user_tipo === 'admin'): ?>
                                                                                <form action="../../actions/pagar_despesa.php" method="POST" class="d-inline">
                                                                                    <input type="hidden" name="id" value="<?php echo $despesa['id']; ?>">
                                                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Marcar como Paga"><i class="bi bi-check-circle"></i></button>
                                                                                </form>
                                                                            <?php endif; ?>
                                                                            <?php if ($user_tipo === 'admin'): ?>
                                                                            <a href="editar.php?id=<?php echo $despesa['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar Despesa">
                                                                                <i class="bi bi-pencil-square"></i>
                                                                            </a>
                                                                            <?php endif; ?>
                                                                            <?php if ($user_tipo === 'admin'): ?>
                                                                                <form action="../../actions/excluir_despesa.php" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta despesa?');">
                                                                                    <input type="hidden" name="id" value="<?php echo $despesa['id']; ?>">
                                                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir Despesa"><i class="bi bi-trash"></i></button>
                                                                                </form>
                                                                                <?php if ($despesa['metodo_pagamento'] === 'bemol_crediario' && !empty($despesa['grupo_parcela_id'])): ?>
                                                                                <form action="../../actions/excluir_compra_bemol.php" method="POST" class="d-inline" onsubmit="return confirm('Atenção: Isso excluirá TODAS as parcelas desta compra. Deseja continuar?');">
                                                                                    <input type="hidden" name="grupo_parcela_id" value="<?php echo $despesa['grupo_parcela_id']; ?>">
                                                                                    <button type="submit" class="btn btn-sm btn-danger" title="Excluir Compra Completa">
                                                                                        <i class="bi bi-trash3-fill"></i>
                                                                                    </button>
                                                                                </form>
                                                                                <?php endif; ?>
                                                                                <?php
                                                                                    // Garante que o botão de excluir compra completa apareça apenas uma vez por grupo
                                                                                    static $displayed_card_groups = [];
                                                                                    if ($despesa['metodo_pagamento'] === 'cartao_credito' && !empty($despesa['grupo_parcela_id']) && !isset($displayed_card_groups[$despesa['grupo_parcela_id']])):
                                                                                        $displayed_card_groups[$despesa['grupo_parcela_id']] = true;
                                                                                ?>
                                                                                    <form action="../../actions/excluir_compra_cartao.php" method="POST" class="d-inline" onsubmit="return confirm('Atenção: Isso excluirá TODAS as parcelas desta compra no cartão. Deseja continuar?');">
                                                                                        <input type="hidden" name="grupo_parcela_id" value="<?php echo $despesa['grupo_parcela_id']; ?>">
                                                                                        <button type="submit" class="btn btn-sm btn-danger" title="Excluir Compra Completa do Cartão"><i class="bi bi-trash3-fill"></i></button>
                                                                                    </form>
                                                                                <?php endif; ?>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Navegação da Paginação -->
                                <nav>
                                    <ul class="pagination justify-content-center">
                                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                            <li class="page-item <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>">
                                                <?php
                                                    // Reconstrói a query string para a paginação
                                                    $pagination_query = http_build_query([
                                                        'mes_ano' => $filtro_mes_ano, 'usuario_id' => $filtro_usuario_ids, 'status' => $filtro_status, 'page' => $i
                                                    ]);
                                                ?>
                                                <a class="page-link" href="?<?php echo $pagination_query; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>

                            <?php endif; ?>
                        </div>
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
                    <h5 class="modal-title" id="pdfPreviewModalLabel">Pré-visualização do Relatório de Despesas</h5>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Impede que o dropdown de usuários feche ao clicar dentro dele
            const userDropdown = document.querySelector('.dropdown-menu');
            userDropdown?.addEventListener('click', e => e.stopPropagation());

            // Script para carregar o PDF no modal de pré-visualização
            const pdfPreviewModal = document.getElementById('pdfPreviewModal');
            pdfPreviewModal?.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const pdfUrl = button.getAttribute('data-pdf-url');
                const iframe = document.getElementById('pdf-iframe');
                iframe.setAttribute('src', pdfUrl);
            });
        });
    </script>
</body>
</html>