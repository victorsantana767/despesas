<?php
// config/config.php

// Defina uma variável de ambiente no seu WAMP Server para 'development' ou 'production'.
// Se não for definida, usará 'development' como padrão.
define('ENVIRONMENT', getenv('APP_ENV') ?: 'development');

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'despesas');
define('DB_USER', 'root'); // Usuário padrão do WAMP
define('DB_PASS', '');     // Senha padrão do WAMP é vazia

// URLs base
if (ENVIRONMENT === 'production') {
    define('BASE_URL', 'http://www.seusite.com');
} else {
    // Assumindo que a raiz do seu projeto é 'despesas' dentro de 'www' do WAMP
    define('BASE_URL', 'http://localhost/despesas');
}

// Conexão com o Banco de Dados (PDO é recomendado por segurança)
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Em produção, logue o erro em um arquivo em vez de exibir na tela.
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
