<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';

// Apenas admins podem ver a lista completa de ganhos
if ($user_tipo !== 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

// --- Lógica de Filtragem ---
$filtro_mes_ano = $_GET['mes_ano'] ?? date('Y-m');
$data_inicio = date('Y-m-01', strtotime($filtro_mes_ano));
$data_fim = date('Y-m-t', strtotime($filtro_mes_ano));

// Buscar ganhos confirmados para o mês filtrado
$sql_ganhos = "
    SELECT 
        g.id, 
        g.valor, 
        g.data_ganho,
        g.descricao,
        u.nome AS usuario_nome,
        tg.nome AS tipo_ganho_nome
    FROM ganhos g
    JOIN usuarios u ON g.usuario_id = u.id
    JOIN tipos_ganho tg ON g.tipo_ganho_id = tg.id
    WHERE g.data_ganho BETWEEN ? AND ?
    ORDER BY g.data_ganho DESC, g.id DESC";
$stmt = $pdo->prepare($sql_ganhos);
$stmt->execute([$data_inicio, $data_fim]);
$ganhos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Soma do total de ganhos para o filtro atual ---
$sql_sum = "SELECT SUM(g.valor) FROM ganhos g WHERE g.data_ganho BETWEEN ? AND ?";
$stmt_sum = $pdo->prepare($sql_sum);
$stmt_sum->execute([$data_inicio, $data_fim]);
$total_ganhos_filtrados = $stmt_sum->fetchColumn() ?: 0;

// Buscar todos os modelos de ganhos recorrentes
$sql_recorrentes = "
    SELECT gr.*, u.nome as usuario_nome, tg.nome as tipo_ganho_nome
    FROM ganhos_recorrentes gr
    JOIN usuarios u ON gr.usuario_id = u.id
    JOIN tipos_ganho tg ON gr.tipo_ganho_id = tg.id
    ORDER BY gr.ativo DESC, gr.descricao ASC";
$ganhos_recorrentes = $pdo->query($sql_recorrentes)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Ganhos</title>
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
                    <h1>Ganhos</h1>
                    <div class="btn-group">
                        <a href="adicionar.php" class="btn btn-success"><i class="bi bi-plus-circle me-2"></i>Lançar Ganho Único</a>
                        <a href="recorrentes_adicionar.php" class="btn btn-info"><i class="bi bi-arrow-repeat me-2"></i>Novo Ganho Recorrente</a>
                    </div>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">Ganho salvo com sucesso!</div>
                <?php endif; ?>
                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-success">Ganho excluído com sucesso!</div>
                <?php endif; ?>
                <?php if (isset($_GET['provisioned'])): ?>
                    <div class="alert alert-info">Ganhos recorrentes provisionados para o mês!</div>
                <?php endif; ?>

                <!-- Abas de Navegação -->
                <ul class="nav nav-tabs" id="ganhosTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="mensal-tab" data-bs-toggle="tab" data-bs-target="#mensal" type="button" role="tab">Ganhos do Mês</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="recorrentes-tab" data-bs-toggle="tab" data-bs-target="#recorrentes" type="button" role="tab">Ganhos Recorrentes</button>
                    </li>
                </ul>

                <div class="tab-content" id="ganhosTabContent">
                    <!-- Aba de Ganhos do Mês -->
                    <div class="tab-pane fade show active" id="mensal" role="tabpanel">
                        <div class="card card-body border-top-0">
                            <form method="GET" class="row g-3 align-items-end mb-4">
                                <div class="col-md-3">
                                    <label for="mes_ano" class="form-label">Filtrar Mês/Ano</label>
                                    <input type="month" class="form-control" id="mes_ano" name="mes_ano" value="<?php echo htmlspecialchars($filtro_mes_ano); ?>">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel-fill"></i> Filtrar</button>
                                </div>
                                <div class="col-md-6 text-end">
                                    <a id="btn-provisionar" href="../../actions/provisionar_ganhos.php?mes_ano=<?php echo htmlspecialchars($filtro_mes_ano); ?>" class="btn btn-outline-primary" onclick="return confirm('Isso irá gerar os ganhos recorrentes para o mês selecionado. Ganhos já existentes não serão duplicados. Continuar?');">
                                        <i class="bi bi-calendar-check"></i> Provisionar Ganhos do Mês
                                    </a>
                                </div>
                            </form>

                            <!-- Card de Resumo do Total -->
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="card text-white bg-success">
                                        <div class="card-body">
                                            <h5 class="card-title">Total de Ganhos no Mês</h5>
                                            <p class="card-text fs-4">
                                                <?php echo 'R$ ' . number_format($total_ganhos_filtrados, 2, ',', '.'); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if (empty($ganhos)): ?>
                                <div class="alert alert-info text-center">Nenhum ganho lançado para este mês.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover align-middle">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Data</th>
                                                <th>Usuário</th>
                                                <th>Descrição</th>
                                                <th>Tipo de Ganho</th>
                                                <th>Valor</th>
                                                <th class="text-center">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ganhos as $ganho): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($ganho['data_ganho'])); ?></td>
                                                <td><?php echo htmlspecialchars($ganho['usuario_nome']); ?></td>
                                                <td><?php echo htmlspecialchars($ganho['descricao'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($ganho['tipo_ganho_nome']); ?></td>
                                                <td><?php echo 'R$ ' . number_format($ganho['valor'], 2, ',', '.'); ?></td>
                                                <td class="text-center">
                                                    <a href="editar.php?id=<?php echo $ganho['id']; ?>" class="btn btn-sm btn-primary" title="Editar Ganho"><i class="bi bi-pencil-square"></i> Editar</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Aba de Ganhos Recorrentes -->
                    <div class="tab-pane fade" id="recorrentes" role="tabpanel">
                        <div class="card card-body border-top-0">
                            <?php if (empty($ganhos_recorrentes)): ?>
                                <div class="alert alert-info text-center">Nenhum ganho recorrente cadastrado.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover align-middle">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Descrição</th>
                                                <th>Usuário</th>
                                                <th>Valor Base</th>
                                                <th>Gera dia</th>
                                                <th>Período</th>
                                                <th>Status</th>
                                                <th class="text-center">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ganhos_recorrentes as $rec): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($rec['descricao']); ?></td>
                                                <td><?php echo htmlspecialchars($rec['usuario_nome']); ?></td>
                                                <td><?php echo 'R$ ' . number_format($rec['valor_base'], 2, ',', '.'); ?></td>
                                                <td><?php echo $rec['dia_geracao']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($rec['data_inicio'])); ?> até <?php echo $rec['data_fim'] ? date('d/m/Y', strtotime($rec['data_fim'])) : 'Indefinido'; ?></td>
                                                <td><span class="badge bg-<?php echo $rec['ativo'] ? 'success' : 'secondary'; ?>"><?php echo $rec['ativo'] ? 'Ativo' : 'Inativo'; ?></span></td>
                                                <td class="text-center">
                                                    <a href="recorrentes_editar.php?id=<?php echo $rec['id']; ?>" class="btn btn-sm btn-primary" title="Editar Recorrência"><i class="bi bi-pencil-square"></i> Editar</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/scripts.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filtroMesAno = document.getElementById('mes_ano');
            const btnProvisionar = document.getElementById('btn-provisionar');
            
            filtroMesAno.addEventListener('change', function() {
                btnProvisionar.href = `../../actions/provisionar_ganhos.php?mes_ano=${this.value}`;
            });
        });
    </script>
</body>
</html>