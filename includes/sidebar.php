<?php
// Pega o caminho da URL para saber qual item do menu marcar como 'ativo'
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav id="sidebar">
    <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <span class="fs-4">Gestor de Despesas</span>
    </a>
    <hr class="sidebar-divider">
    <ul class="nav nav-pills flex-column mb-auto components">
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="nav-link text-white <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/pages/despesas/index.php" class="nav-link text-white <?php echo (strpos($_SERVER['REQUEST_URI'], '/pages/despesas/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-wallet2 me-2"></i> Despesas
            </a>
        </li>
        <!-- Apenas admins podem ver usuários e configurações -->
        <?php if ($user_tipo === 'admin'): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/pages/ganhos/index.php" class="nav-link text-white <?php echo (strpos($_SERVER['REQUEST_URI'], '/ganhos/') !== false && strpos($_SERVER['REQUEST_URI'], '/ganhos/tipos_') === false) ? 'active' : ''; ?>">
                <i class="bi bi-graph-up-arrow me-2"></i> Ganhos
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/pages/ganhos/tipos_index.php" class="nav-link text-white <?php echo (strpos($_SERVER['REQUEST_URI'], '/ganhos/tipos_') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-tags-fill me-2"></i> Tipos de Ganho
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/pages/cartoes/index.php" class="nav-link text-white <?php echo (strpos($_SERVER['REQUEST_URI'], '/cartoes/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-credit-card-2-front-fill me-2"></i> Cartões
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/pages/usuarios/index.php" class="nav-link text-white <?php echo (strpos($_SERVER['REQUEST_URI'], '/usuarios/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-people-fill me-2"></i> Usuários
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/pages/automoveis/index.php" class="nav-link text-white <?php echo (strpos($_SERVER['REQUEST_URI'], '/automoveis/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-car-front-fill me-2"></i> Automóveis
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/pages/emprestimos/index.php" class="nav-link text-white <?php echo (strpos($_SERVER['REQUEST_URI'], '/emprestimos/') !== false) ? 'active' : ''; ?>">
                <i class="bi bi-bank me-2"></i> Empréstimos
            </a>
        </li>
        <?php endif; ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/pages/relatorios/index.php" class="nav-link text-white <?php echo (strpos($_SERVER['REQUEST_URI'], '/relatorios/') !== false) ? 'active' : ''; ?>">
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
