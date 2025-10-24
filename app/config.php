<?php
define('DB_SERVER', 'db'); // Nome do serviço do banco de dados no docker-compose
define('DB_USERNAME', 'appcore_user');
define('DB_PASSWORD', 'userpassword');
define('DB_NAME', 'appcore_db');

/* Tentativa de conexão com o banco de dados MySQL */
try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    // Define o modo de erro PDO para exceção
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Não exibir detalhes do erro em produção, apenas logar ou mostrar mensagem genérica.
    // Em desenvolvimento, pode ser útil para depuração:
    die("ERRO: Não foi possível conectar ao banco de dados. " . $e->getMessage());
}
?>
