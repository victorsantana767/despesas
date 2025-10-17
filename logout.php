<?php
// 1. Inicia a sessão para poder manipulá-la.
session_start();

// 2. Remove todas as variáveis da sessão (limpa os dados do usuário logado).
$_SESSION = array();

// 3. Destrói a sessão atual.
session_destroy();

// 4. Redireciona o usuário para a página de login.
header("Location: index.php");
exit();