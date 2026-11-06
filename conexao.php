<?php
// Conexao com o MySQL - Infancia Viva
// Configuracao via variaveis de ambiente do Coolify.

$host    = getenv('DB_HOST');
$porta   = getenv('DB_PORT') ?: '3306';
$banco   = getenv('DB_NAME');
$usuario = getenv('DB_USER');
$senha   = getenv('DB_PASSWORD');

// Verifica se todas as configuracoes existem.
if (
    !$host ||
    !$banco ||
    !$usuario ||
    $senha === false ||
    $senha === ''
) {
    error_log('Configuracao do banco incompleta.');
    http_response_code(500);
    exit('Erro na configuracao do banco de dados.');
}

try {
    $dsn = "mysql:host={$host};port={$porta};dbname={$banco};charset=utf8mb4";

    $pdo = new PDO($dsn, $usuario, $senha, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

} catch (PDOException $e) {
    // Registra o erro nos logs sem expor detalhes ao visitante.
    error_log('Erro de conexao MySQL: ' . $e->getMessage());

    http_response_code(500);
    exit('Nao foi possivel conectar ao banco de dados.');
}
