<?php
// Pega o caminho da URL para saber qual item do menu marcar como 'ativo'
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div id="sidebar" class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark" style="width: 280px; min-height: 100vh;">
    <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <span class="fs-4">Gestor de Despesas</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="nav-link text-white <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                Dashboard
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/pages/despesas/index.php" class="nav-link text-white <?php echo (strpos($_SERVER['REQUEST_URI'], '/pages/despesas/') !== false) ? 'active' : ''; ?>">
                Despesas
            </a>
        </li>
        <!-- Apenas admins podem ver usuários e configurações -->
        <?php if ($user_tipo === 'admin'): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/pages/ganhos/index.php" class="nav-link text-white <?php echo (strpos($_SERVER['REQUEST_URI'], '/ganhos/') !== false && strpos($_SERVER['REQUEST_URI'], '/ganhos/tipos_') === false) ? 'active' : ''; ?>">
                Ganhos
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/pages/ganhos/tipos_index.php" class="nav-link text-white <?php echo (strpos($_SERVER['REQUEST_URI'], '/ganhos/tipos_') !== false) ? 'active' : ''; ?>">
                Tipos de Ganho
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/pages/cartoes/index.php" class="nav-link text-white <?php echo (strpos($_SERVER['REQUEST_URI'], '/cartoes/') !== false) ? 'active' : ''; ?>">
                Cartões
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/pages/usuarios/index.php" class="nav-link text-white <?php echo (strpos($_SERVER['REQUEST_URI'], '/usuarios/') !== false) ? 'active' : ''; ?>">
                Usuários
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/pages/automoveis/index.php" class="nav-link text-white <?php echo (strpos($_SERVER['REQUEST_URI'], '/automoveis/') !== false) ? 'active' : ''; ?>">
                Automóveis
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/pages/emprestimos/index.php" class="nav-link text-white <?php echo (strpos($_SERVER['REQUEST_URI'], '/emprestimos/') !== false) ? 'active' : ''; ?>">
                Empréstimos
            </a>
        </li>
        <?php endif; ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/pages/relatorios/index.php" class="nav-link text-white <?php echo (strpos($_SERVER['REQUEST_URI'], '/relatorios/') !== false) ? 'active' : ''; ?>">
                Relatórios
            </a>
        </li>
    </ul>
    <div class="mt-auto dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
            <strong><?php echo htmlspecialchars($user_nome); ?></strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
            <li><a class="dropdown-item" href="#">Perfil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php">Sair</a></li>
        </ul>
    </div>
</div>
