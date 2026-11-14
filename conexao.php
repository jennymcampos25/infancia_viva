<?php
// Conexao com o MySQL - Infancia Viva
// Configuracao via variaveis de ambiente do Coolify.

// Datas e horas do PHP no horario de Brasilia (o servidor do Coolify usa UTC).
date_default_timezone_set('America/Sao_Paulo');

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

    // Horario de Brasilia (o servidor usa UTC). O Brasil nao tem mais horario de verao desde 2019.
    $pdo->exec("SET time_zone = '-03:00'");

} catch (PDOException $e) {
    // Registra o erro nos logs sem expor detalhes ao visitante.
    error_log('Erro de conexao MySQL: ' . $e->getMessage());

    http_response_code(500);
    exit('Nao foi possivel conectar ao banco de dados.');
}
