<?php
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';

$automovel_id = $_GET['automovel_id'] ?? null;
if (!$automovel_id) {
    header("Location: index.php");
    exit();
}

// Buscar dados do automóvel
$stmt_auto = $pdo->prepare("SELECT modelo FROM automoveis WHERE id = ?");
$stmt_auto->execute([$automovel_id]);
$automovel = $stmt_auto->fetch(PDO::FETCH_ASSOC);
if (!$automovel) {
    header("Location: index.php");
    exit();
}

// Buscar despesas do automóvel
$stmt_despesas = $pdo->prepare("
    SELECT d.*, da.tipo_despesa, u.nome as comprador_nome
    FROM despesas d
    JOIN despesas_automoveis da ON d.id = da.despesa_id
    JOIN usuarios u ON d.comprador_id = u.id
    WHERE da.automovel_id = ?
    ORDER BY d.data_despesa DESC
");
$stmt_despesas->execute([$automovel_id]);
$despesas = $stmt_despesas->fetchAll(PDO::FETCH_ASSOC);

// Lógica para agrupar despesas parceladas
$despesas_agrupadas = [];
$despesas_individuais = [];
foreach ($despesas as $despesa) {
    // Verifica se a descrição contém o padrão de parcela, ex: "(Parcela 1/12)"
    if (!empty($despesa['grupo_parcela_id'])) {
        $despesas_agrupadas[$despesa['grupo_parcela_id']][] = $despesa;
    } else {
        $despesas_individuais[] = $despesa;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Despesas do <?php echo htmlspecialchars($automovel['modelo']); ?></title>
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
                    <h1>Despesas do <?php echo htmlspecialchars($automovel['modelo']); ?></h1>
                    <a href="adicionar_despesa.php?automovel_id=<?php echo $automovel_id; ?>" class="btn btn-success"><i class="bi bi-plus-circle me-2"></i>Adicionar Despesa</a>
                </div>

                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-success">Despesa(s) excluída(s) com sucesso!</div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <?php if (empty($despesas)): ?>
                            <div class="alert alert-info text-center">Nenhuma despesa registrada para este veículo.</div>
                        <?php else: ?>
                            <!-- Compras Parceladas -->
                            <?php if (!empty($despesas_agrupadas)): ?>
                                <h5 class="mb-3">Compras Parceladas</h5>
                                <div class="accordion" id="accordionParceladas">
                                    <?php foreach ($despesas_agrupadas as $grupo_id => $parcelas): ?>
                                        <?php
                                            // Pega a descrição base da primeira parcela
                                            preg_match('/(.+?)\s*\(Parcela\s*\d+\/\d+\)/', $parcelas[0]['descricao'], $matches);
                                            $nome_compra = trim($matches[1]);
                                            // Ordena as parcelas pela data
                                            usort($parcelas, function($a, $b) {
                                                return strtotime($a['data_despesa']) - strtotime($b['data_despesa']);
                                            });
                                            $total_compra = array_sum(array_column($parcelas, 'valor'));
                                        ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading-<?php echo $grupo_id; ?>">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $grupo_id; ?>">
                                                    <div class="w-100 d-flex justify-content-between pe-3">
                                                        <span><strong><?php echo htmlspecialchars($nome_compra); ?></strong></span>
                                                        <span class="badge bg-primary d-flex align-items-center">Total: R$ <?php echo number_format($total_compra, 2, ',', '.'); ?></span>
                                                    </div>
                                                </button>
                                            </h2>
                                            <div id="collapse-<?php echo $grupo_id; ?>" class="accordion-collapse collapse" data-bs-parent="#accordionParceladas">
                                                <div class="accordion-body">
                                                    <table class="table table-sm table-bordered">
                                                        <thead class="table-light">
                                                            <tr><th>Descrição</th><th>Data</th><th>Valor</th><th>Status</th><th class="text-center">Ações</th></tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($parcelas as $parcela): ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars($parcela['descricao']); ?></td>
                                                                    <td><?php echo date('d/m/Y', strtotime($parcela['data_despesa'])); ?></td>
                                                                    <td>R$ <?php echo number_format($parcela['valor'], 2, ',', '.'); ?></td>
                                                                    <td>
                                                                        <?php
                                                                            $hoje = new DateTime();
                                                                            $vencimento = new DateTime($parcela['data_despesa']);
                                                                        ?>
                                                                        <?php if ($parcela['status'] == 'pago'): ?>
                                                                            <span class="badge bg-success">Pago</span>
                                                                        <?php elseif ($parcela['status'] == 'pendente' && $vencimento->format('Y-m-d') < $hoje->format('Y-m-d')): ?>
                                                                            <span class="badge bg-danger">Atrasado</span>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-warning text-dark">Pendente</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td class="text-center"><a href="../despesas/editar.php?id=<?php echo $parcela['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar Parcela"><i class="bi bi-pencil-square"></i></a></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                    <form action="../../actions/excluir_compra_parcelada_automovel.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir TODAS as parcelas desta compra?');">
                                                        <input type="hidden" name="grupo_parcela_id" value="<?php echo $grupo_id; ?>">
                                                        <input type="hidden" name="automovel_id" value="<?php echo $automovel_id; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="bi bi-trash-fill"></i> Excluir Compra Completa
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <hr class="my-4">
                            <?php endif; ?>

                            <!-- Despesas Individuais -->
                            <h5 class="mb-3">Despesas Individuais</h5>
                            <table class="table table-striped table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Data</th>
                                        <th>Descrição</th>
                                        <th>Tipo</th>
                                        <th>Comprador</th>
                                        <th>Valor</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($despesas_individuais as $despesa): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($despesa['data_despesa'])); ?></td>
                                        <td><?php echo htmlspecialchars($despesa['descricao']); ?></td>
                                        <td><?php echo ucfirst($despesa['tipo_despesa']); ?></td>
                                        <td><?php echo htmlspecialchars($despesa['comprador_nome']); ?></td>
                                        <td><?php echo 'R$ ' . number_format($despesa['valor'], 2, ',', '.'); ?></td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a href="../despesas/editar.php?id=<?php echo $despesa['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar Despesa">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <form action="../../actions/excluir_despesa.php" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta despesa?');">
                                                    <input type="hidden" name="id" value="<?php echo $despesa['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir Despesa"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Voltar para a lista de veículos</a>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/scripts.js"></script>
</body>
</html>