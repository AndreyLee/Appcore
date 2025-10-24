<?php
// Inclui o arquivo de configuração para acesso às credenciais do banco
require_once '../config.php';

session_start();

// Apenas 'super_admin' pode executar esta ação
if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_user_role']) || $_SESSION['admin_user_role'] !== 'super_admin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Acesso negado. Você não tem permissão para realizar esta ação.');
}

// Nome do arquivo de backup
$backup_filename = DB_NAME . '_backup_' . date("Y-m-d_H-i-s") . '.sql';

// Comando mysqldump
// As credenciais são obtidas das constantes definidas em config.php
$command = sprintf(
    "mysqldump --host=%s --user=%s --password=%s %s",
    escapeshellarg(DB_SERVER),
    escapeshellarg(DB_USERNAME),
    escapeshellarg(DB_PASSWORD),
    escapeshellarg(DB_NAME)
);

// Cabeçalhos para forçar o download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $backup_filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Executa o comando e envia a saída diretamente para o navegador
passthru($command, $return_var);

// Verifica se o comando foi executado com sucesso
if ($return_var !== 0) {
    // Se houver um erro, remove os cabeçalhos de download e exibe uma mensagem
    // (Isso pode não funcionar perfeitamente se a saída já começou)
    header_remove();
    echo "Erro ao gerar o backup. Código de retorno: " . $return_var;
}

exit;
?>
