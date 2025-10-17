<?php
require_once '../includes/auth_check.php'; // Protege a página
require_once '../config/config.php';

// Buscar usuários para o filtro (apenas para admins)
$usuarios_filtro = [];
if ($user_tipo === 'admin') {
    $stmt_usuarios = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome");
    $usuarios_filtro = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <span id="sidebar-toggle" class="top-bar-toggle"><i class="bi bi-list fs-4"></i></span>
                </div>
            </nav>
            <main class="p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h1>Dashboard</h1>
                    <p class="lead">Bem-vindo, <?php echo htmlspecialchars($user_nome); ?>!</p>
                </div>
                <hr>

                <!-- Filtros do Dashboard -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label for="filtro_mes_ano" class="form-label">Mês/Ano</label>
                                <input type="month" class="form-control" id="filtro_mes_ano" value="<?php echo date('Y-m'); ?>">
                            </div>
                            <?php if ($user_tipo === 'admin'): ?>
                            <div class="col-md-8">
                                <label class="form-label">Pessoas</label>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        Selecionar Pessoas
                                    </button>
                                    <ul class="dropdown-menu w-100" aria-labelledby="dropdownMenuButton">
                                        <?php foreach ($usuarios_filtro as $usuario): ?>
                                        <li class="px-3 py-1">
                                            <div class="form-check">
                                                <input class="form-check-input filtro-usuario-check" type="checkbox" value="<?php echo $usuario['id']; ?>" id="user_<?php echo $usuario['id']; ?>" checked>
                                                <label class="form-check-label" for="user_<?php echo $usuario['id']; ?>"><?php echo htmlspecialchars($usuario['nome']); ?></label>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Cards de Resumo -->
                <div class="row mb-4" id="summary-cards">
                    <?php if ($user_tipo === 'admin'): ?>
                        <div class="col-md-4">
                            <div class="card text-white bg-success">
                                <div class="card-body">
                                    <h5 class="card-title">Total de Ganhos</h5>
                                    <p class="card-text fs-4" id="total-ganhos">R$ 0,00</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-white bg-danger">
                                <div class="card-body">
                                    <h5 class="card-title">Total de Despesas</h5>
                                    <p class="card-text fs-4" id="total-despesas">R$ 0,00</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-dark bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Saldo do Mês</h5>
                                    <p class="card-text fs-4" id="saldo-mes">R$ 0,00</p>
                                </div>
                            </div>
                        </div>
                    <?php else: // Usuário de visualização ?>
                        <div class="col-md-12">
                            <div class="card text-white bg-danger">
                                <div class="card-body">
                                    <h5 class="card-title">Total de Despesas do Mês</h5>
                                    <p class="card-text fs-4" id="total-despesas">R$ 0,00</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Gráficos -->
                <div class="row">
                    <div class="col-lg-4 mb-4" id="pie-chart-container" style="display: <?php echo ($user_tipo === 'admin') ? 'block' : 'none'; ?>;">
                        <div class="card h-100"><div class="card-body"><canvas id="pieChart"></canvas></div></div>
                    </div>
                    <div class="mb-4" id="bar-chart-container">
                        <div class="card h-100"><div class="card-body"><canvas id="barChart"></canvas></div></div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- JS do Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Nosso script customizado -->
    <script src="../assets/js/scripts.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pieCtx = document.getElementById('pieChart').getContext('2d');
            const barCtx = document.getElementById('barChart').getContext('2d');
            let pieChart, barChart;

            const totalGanhosEl = document.getElementById('total-ganhos');
            const totalDespesasEl = document.getElementById('total-despesas');
            const saldoMesEl = document.getElementById('saldo-mes');
            const pieChartContainer = document.getElementById('pie-chart-container');
            const barChartContainer = document.getElementById('bar-chart-container');

            function fetchDataAndUpdateChart() {
                const mesAno = document.getElementById('filtro_mes_ano').value;
                const userCheckboxes = document.querySelectorAll('.filtro-usuario-check:checked');
                let selectedUsers = [];
                if (userCheckboxes) {
                    selectedUsers = Array.from(userCheckboxes).map(checkbox => checkbox.value);
                }

                const params = new URLSearchParams({ mes_ano: mesAno });
                selectedUsers.forEach(user => params.append('usuarios[]', user));

                fetch(`../actions/get_dashboard_data.php?${params.toString()}`)
                    .then(response => response.json())
                    .then(data => {
                        // Atualiza os cards de resumo
                        const formatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });                        
                        if(totalGanhosEl) totalGanhosEl.textContent = formatter.format(data.summary.totalGanhos);
                        if(totalDespesasEl) totalDespesasEl.textContent = formatter.format(data.summary.totalDespesas);
                        if(saldoMesEl) {
                            saldoMesEl.textContent = formatter.format(data.summary.saldo);
                            saldoMesEl.parentElement.parentElement.className = data.summary.saldo >= 0 ? 'card text-dark bg-light' : 'card text-white bg-warning';
                        }

                        // Atualiza o gráfico de pizza
                        if (data.pieChart) {
                            pieChartContainer.style.display = 'block';
                            barChartContainer.className = 'col-lg-8 mb-4';
                            if (pieChart) {
                                pieChart.destroy();
                            }
                            pieChart = new Chart(pieCtx, {
                                type: 'doughnut',
                                data: data.pieChart,
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        title: { display: true, text: 'Composição do Mês' }
                                    }
                                }
                            });
                        } else {
                            pieChartContainer.style.display = 'none';
                            barChartContainer.className = 'col-lg-12 mb-4';
                        }

                        // Atualiza o gráfico de barras
                        if (barChart) {
                            barChart.destroy();
                        }
                        barChart = new Chart(barCtx, {
                            type: 'bar',
                            data: data.barChart,
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    title: { display: true, text: 'Detalhamento' }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    });
            }

            // Event Listeners para os filtros
            document.getElementById('filtro_mes_ano').addEventListener('change', fetchDataAndUpdateChart);
            const userCheckboxes = document.querySelectorAll('.filtro-usuario-check');
            if (userCheckboxes) {
                userCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', fetchDataAndUpdateChart);
                });
            }

            // Carregar dados iniciais
            fetchDataAndUpdateChart();
        });
    </script>
</body>
</html>
