<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';

// Buscar todas as despesas com informações detalhadas
// --- Lógica de Filtragem e Paginação ---
$filtro_mes_ano = $_GET['mes_ano'] ?? date('Y-m');
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
// --- Busca dos itens da página atual ---
$sql_final = $sql_select . $sql_base . $sql_where . " ORDER BY d.status ASC, d.data_despesa DESC, d.id DESC LIMIT ? OFFSET ?";

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
            <main class="p-4" style="background-color: #f8f9fa;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Minhas Despesas</h1>
                    <a href="adicionar.php" class="btn btn-success"><i class="bi bi-plus-circle me-2"></i>Adicionar Despesa</a>
                </div>

                <!-- Formulário de Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="index.php" class="row align-items-end">
                            <div class="col-md-4">
                                <label for="mes_ano" class="form-label">Filtrar por Mês/Ano</label>
                                <input type="month" class="form-control" id="mes_ano" name="mes_ano" value="<?php echo htmlspecialchars($filtro_mes_ano); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button>
                            </div>
                        </form>
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

                <div class="card">
                    <div class="card-body">
                        <?php if (empty($despesas)): ?>
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle me-2"></i>
                                Nenhuma despesa cadastrada ainda. Clique em "Adicionar Despesa" para começar.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Descrição</th>
                                            <th>Valor</th>
                                            <th>Data</th>
                                            <th>Dono da Dívida</th>
                                            <th>Comprador</th>
                                            <th>Pagamento</th>
                                            <th>Status</th>
                                            <th class="text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($despesas as $despesa): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($despesa['descricao']); ?></td>
                                            <td><?php echo 'R$ ' . number_format($despesa['valor'], 2, ',', '.'); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($despesa['data_despesa'])); ?></td>
                                            <td><?php echo htmlspecialchars($despesa['dono_divida_nome']); ?></td>
                                            <td><?php echo htmlspecialchars($despesa['comprador_nome']); ?></td>
                                            <td>
                                                <?php echo ucfirst(str_replace('_', ' ', $despesa['metodo_pagamento'])); ?>
                                                <?php if ($despesa['metodo_pagamento'] == 'cartao_credito' && $despesa['nome_cartao']): ?>
                                                    <small class="d-block text-muted"><?php echo htmlspecialchars($despesa['nome_cartao']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                    $hoje = new DateTime();
                                                    $vencimento = new DateTime($despesa['data_despesa']);
                                                ?>
                                                <?php if ($despesa['status'] == 'pago'): ?>
                                                    <span class="badge bg-success">Pago</span>
                                                <?php elseif ($despesa['status'] == 'pendente' && $vencimento->format('Y-m-d') < $hoje->format('Y-m-d')): ?>
                                                    <span class="badge bg-danger">Atrasado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Pendente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <?php if ($despesa['status'] == 'pendente' && $user_tipo === 'admin'): ?>
                                                        <form action="../../actions/pagar_despesa.php" method="POST" class="d-inline">
                                                            <input type="hidden" name="id" value="<?php echo $despesa['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Marcar como Paga"><i class="bi bi-check-circle"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <a href="editar.php?id=<?php echo $despesa['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar Despesa">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Navegação da Paginação -->
                            <nav>
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                        <li class="page-item <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?mes_ano=<?php echo $filtro_mes_ano; ?>&page=<?php echo $i; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>

                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/scripts.js"></script>
</body>
</html>