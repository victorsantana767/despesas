<?php
// Pega o caminho da URL para saber qual item do menu marcar como 'ativo'
$request_uri = $_SERVER['REQUEST_URI'];

function is_active_section($uri, $section) {
    return strpos($uri, "/pages/{$section}/") !== false;
}
?>
<nav id="sidebar">
    <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <span class="fs-4">Gestor de Despesas</span>
    </a>
    <hr class="sidebar-divider">
    <ul class="nav nav-pills flex-column mb-auto components">
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="nav-link text-white <?php echo (strpos($request_uri, 'dashboard.php') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="#despesasSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo is_active_section($request_uri, 'despesas') ? 'true' : 'false'; ?>" class="nav-link text-white d-flex justify-content-between">
                <span><i class="bi bi-wallet2 me-2"></i> Despesas</span>
                <i class="bi bi-chevron-down arrow"></i>
            </a>
            <ul class="collapse list-unstyled <?php echo is_active_section($request_uri, 'despesas') ? 'show' : ''; ?>" id="despesasSubmenu">
                <li><a href="<?php echo BASE_URL; ?>/pages/despesas/index.php" class="nav-link sub-link text-white">Listar Despesas</a></li>
                <li><a href="<?php echo BASE_URL; ?>/pages/despesas/adicionar.php" class="nav-link sub-link text-white">Adicionar Despesa</a></li>
            </ul>
        </li>
        <!-- Apenas admins podem ver usuários e configurações -->
        <?php if ($user_tipo === 'admin'): ?>
        <li class="nav-item">
            <a href="#ganhosSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo is_active_section($request_uri, 'ganhos') ? 'true' : 'false'; ?>" class="nav-link text-white d-flex justify-content-between">
                <span><i class="bi bi-graph-up-arrow me-2"></i> Ganhos</span>
                <i class="bi bi-chevron-down arrow"></i>
            </a>
            <ul class="collapse list-unstyled <?php echo is_active_section($request_uri, 'ganhos') ? 'show' : ''; ?>" id="ganhosSubmenu">
                <li><a href="<?php echo BASE_URL; ?>/pages/ganhos/index.php" class="nav-link sub-link text-white">Lançamentos</a></li>
                <li><a href="<?php echo BASE_URL; ?>/pages/ganhos/tipos_index.php" class="nav-link sub-link text-white">Tipos de Ganho</a></li>
            </ul>
        </li>
        <li class="nav-item">
            <a href="#cartoesSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo is_active_section($request_uri, 'cartoes') ? 'true' : 'false'; ?>" class="nav-link text-white d-flex justify-content-between">
                <span><i class="bi bi-credit-card-2-front-fill me-2"></i> Cartões</span>
                <i class="bi bi-chevron-down arrow"></i>
            </a>
            <ul class="collapse list-unstyled <?php echo is_active_section($request_uri, 'cartoes') ? 'show' : ''; ?>" id="cartoesSubmenu">
                <li><a href="<?php echo BASE_URL; ?>/pages/cartoes/index.php" class="nav-link sub-link text-white">Faturas</a></li>
                <li><a href="<?php echo BASE_URL; ?>/pages/cartoes/adicionar.php" class="nav-link sub-link text-white">Adicionar Cartão</a></li>
            </ul>
        </li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/pages/bemol/index.php" class="nav-link text-white <?php echo is_active_section($request_uri, 'bemol') ? 'active' : ''; ?>">
                <i class="bi bi-shop me-2"></i> Compras Bemol
            </a>
        </li>
        <li class="nav-item">
            <a href="#usuariosSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo is_active_section($request_uri, 'usuarios') ? 'true' : 'false'; ?>" class="nav-link text-white d-flex justify-content-between">
                <span><i class="bi bi-people-fill me-2"></i> Usuários</span>
                <i class="bi bi-chevron-down arrow"></i>
            </a>
            <ul class="collapse list-unstyled <?php echo is_active_section($request_uri, 'usuarios') ? 'show' : ''; ?>" id="usuariosSubmenu">
                <li><a href="<?php echo BASE_URL; ?>/pages/usuarios/index.php" class="nav-link sub-link text-white">Listar Usuários</a></li>
                <li><a href="<?php echo BASE_URL; ?>/pages/usuarios/adicionar.php" class="nav-link sub-link text-white">Adicionar Usuário</a></li>
            </ul>
        </li>
        <li class="nav-item">
            <a href="#automoveisSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo is_active_section($request_uri, 'automoveis') ? 'true' : 'false'; ?>" class="nav-link text-white d-flex justify-content-between">
                <span><i class="bi bi-car-front-fill me-2"></i> Automóveis</span>
                <i class="bi bi-chevron-down arrow"></i>
            </a>
            <ul class="collapse list-unstyled <?php echo is_active_section($request_uri, 'automoveis') ? 'show' : ''; ?>" id="automoveisSubmenu">
                <li><a href="<?php echo BASE_URL; ?>/pages/automoveis/index.php" class="nav-link sub-link text-white">Listar Veículos</a></li>
                <li><a href="<?php echo BASE_URL; ?>/pages/automoveis/adicionar.php" class="nav-link sub-link text-white">Adicionar Veículo</a></li>
            </ul>
        </li>
        <li class="nav-item">
            <a href="#emprestimosSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo is_active_section($request_uri, 'emprestimos') ? 'true' : 'false'; ?>" class="nav-link text-white d-flex justify-content-between">
                <span><i class="bi bi-bank me-2"></i> Empréstimos</span>
                <i class="bi bi-chevron-down arrow"></i>
            </a>
            <ul class="collapse list-unstyled <?php echo is_active_section($request_uri, 'emprestimos') ? 'show' : ''; ?>" id="emprestimosSubmenu">
                <li><a href="<?php echo BASE_URL; ?>/pages/emprestimos/index.php" class="nav-link sub-link text-white">Listar Empréstimos</a></li>
                <li><a href="<?php echo BASE_URL; ?>/pages/emprestimos/adicionar.php" class="nav-link sub-link text-white">Adicionar Empréstimo</a></li>
            </ul>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/pages/relatorios/index.php" class="nav-link text-white <?php echo is_active_section($request_uri, 'relatorios') ? 'active' : ''; ?>">
                <i class="bi bi-file-earmark-bar-graph-fill me-2"></i> Relatórios
            </a>
        </li>
    </ul>
    <div class="dropdown mt-auto">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle me-2 fs-5"></i>
            <strong class="text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($user_nome); ?></strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
            <li><a class="dropdown-item" href="#">Perfil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php">Sair</a></li>
        </ul>
    </div>
</nav>
