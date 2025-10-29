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

                <?php if ($user_tipo === 'visualizacao'): ?>
                <!-- Card de Resumo (Apenas Visualização) -->
                <div class="row mb-4" id="summary-cards-viewer">
                    <div class="col-md-4">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <h5 class="card-title">Total de Despesas do Mês</h5>
                                <p class="card-text fs-4" id="total-despesas-viewer">R$ 0,00</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($user_tipo === 'admin'): ?>
                <!-- Cards de Resumo (Apenas Admin) -->
                <div class="row mb-4" id="summary-cards">
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
                </div>

                <!-- Gráficos (Apenas Admin) -->
                <div class="row">
                    <div class="col-lg-4 mb-4" id="pie-chart-container">
                        <div class="card h-100">
                            <div class="card-body"><canvas id="pieChart"></canvas></div>
                        </div>
                    </div>
                    <div class="col-lg-8 mb-4" id="bar-chart-container">
                        <div class="card h-100">
                            <div class="card-body"><canvas id="barChart"></canvas></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Resumo de Despesas por Pessoa -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <ul class="nav nav-tabs card-header-tabs" id="resumoDespesasTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="mensal-tab" data-bs-toggle="tab" data-bs-target="#mensal-pane" type="button" role="tab" data-periodo="mensal">Resumo Mensal</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="anual-tab" data-bs-toggle="tab" data-bs-target="#anual-pane" type="button" role="tab" data-periodo="anual">Resumo Anual</button>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content" id="resumoDespesasTabContent">
                                    <div class="tab-pane fade show active" id="resumo-content-pane" role="tabpanel"></div>
                                </div>
                            </div>
                        </div>
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
            const userDropdown = document.querySelector('.dropdown-menu');
            userDropdown?.addEventListener('click', e => e.stopPropagation());

            const resumoContentPane = document.getElementById('resumo-content-pane');
            const userType = '<?php echo $user_tipo; ?>';

            let pieChart, barChart;
            // --- Funções de busca de dados para Admin ---
            function fetchDataForAdmin() {
                const pieCtx = document.getElementById('pieChart')?.getContext('2d');
                const barCtx = document.getElementById('barChart')?.getContext('2d');
                if (!pieCtx || !barCtx) return;

                const totalGanhosEl = document.getElementById('total-ganhos');
                const totalDespesasEl = document.getElementById('total-despesas');
                const saldoMesEl = document.getElementById('saldo-mes');

                const mesAno = document.getElementById('filtro_mes_ano').value;
                const userCheckboxes = document.querySelectorAll('.filtro-usuario-check:checked');
                let selectedUsers = Array.from(userCheckboxes).map(checkbox => checkbox.value);

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
                            saldoMesEl.closest('.card').className = data.summary.saldo >= 0 ? 'card text-dark bg-light' : 'card text-white bg-warning';
                        }

                        // Atualiza o gráfico de pizza
                        if (pieChart) pieChart.destroy();
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

                        // Atualiza o gráfico de barras
                        if (barChart) barChart.destroy();
                        barChart = new Chart(barCtx, {
                            type: 'bar',
                            data: data.barChart,
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    title: { display: true, text: 'Detalhamento por Pessoa' }
                                },
                                scales: {
                                    y: { beginAtZero: true }
                                }
                            }
                        });
                    });
            }

            // --- Função de busca de dados para Visualização ---
            function fetchDataForViewer() {
                const totalDespesasEl = document.getElementById('total-despesas-viewer');
                if (!totalDespesasEl) return;

                const mesAno = document.getElementById('filtro_mes_ano').value;
                const params = new URLSearchParams({ mes_ano: mesAno });

                // O backend já sabe filtrar pelo ID do usuário da sessão
                fetch(`../actions/get_dashboard_data.php?${params.toString()}`)
                    .then(response => response.json())
                    .then(data => {
                        const formatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
                        if(totalDespesasEl) {
                            totalDespesasEl.textContent = formatter.format(data.summary.totalDespesas);
                        }
                        // Poderíamos adicionar um gráfico para o viewer aqui se quiséssemos
                    });
            }


            // --- Função de busca de dados para o Resumo por Pessoa (todos os usuários) ---
            function fetchResumoDespesas() {
                const mesAno = document.getElementById('filtro_mes_ano').value;
                let userCheckboxes;

                // Se for admin, pega os selecionados. Se não, a requisição PHP já vai filtrar pelo user_id da sessão.
                if (userType === 'admin') {
                    userCheckboxes = document.querySelectorAll('.filtro-usuario-check:checked');
                } else {
                    userCheckboxes = []; // Envia vazio para o backend lidar
                }

                const activeTab = document.querySelector('#resumoDespesasTab .nav-link.active');
                const periodo = activeTab ? activeTab.getAttribute('data-periodo') : 'mensal';

                let selectedUsers = [];
                if (userCheckboxes) {
                    selectedUsers = Array.from(userCheckboxes).map(checkbox => checkbox.value);
                }

                const params = new URLSearchParams({
                    periodo: periodo,
                    mes_ano: mesAno
                });
                selectedUsers.forEach(user => params.append('usuarios[]', user));

                fetch(`../actions/get_resumo_despesas.php?${params.toString()}`)
                    .then(response => response.json())
                    .then(data => {
                        let html = '<ul class="list-group">';
                        if (data.length > 0) {
                            const formatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
                            data.forEach(item => {
                                html += `
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        ${item.nome}
                                        <span class="badge bg-danger rounded-pill">${formatter.format(item.total_despesas)}</span>
                                    </li>
                                `;
                            });
                        } else {
                            html += '<li class="list-group-item text-center text-muted">Nenhum dado para exibir.</li>';
                        }
                        html += '</ul>';
                        resumoContentPane.innerHTML = html;
                    });
            }

            // Event Listeners para os filtros
            document.getElementById('filtro_mes_ano').addEventListener('change', function() {
                fetchResumoDespesas();
                if (userType === 'admin') {
                    fetchDataForAdmin();
                } else {
                    fetchDataForViewer();
                }
            });

            const userCheckboxes = document.querySelectorAll('.filtro-usuario-check');
            if (userCheckboxes) {
                userCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        fetchResumoDespesas();
                        if (userType === 'admin') fetchDataForAdmin();
                    });
                });
            }

            // Event Listeners para as abas de resumo
            const resumoTabs = document.querySelectorAll('#resumoDespesasTab .nav-link');
            resumoTabs.forEach(tab => {
                tab.addEventListener('shown.bs.tab', function() {
                    fetchResumoDespesas();
                });
            });

            // Carregar dados iniciais
            fetchResumoDespesas();
            if (userType === 'admin') {
                fetchDataForAdmin();
            } else {
                fetchDataForViewer();
            }
        });
    </script>
</body>
</html>
