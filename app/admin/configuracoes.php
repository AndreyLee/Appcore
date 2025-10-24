<?php
require_once '../config.php';
require_once 'includes/functions.php';

session_start();

if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_user_role']) || $_SESSION['admin_user_role'] !== 'super_admin') {
    header('Location: login.php?erro=' . urlencode('Acesso negado. Apenas Super Admins podem acessar as configurações.'));
    exit;
}

$mensagem = '';
$mensagem_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'backup') {
        $backup_filename = DB_NAME . '_backup_' . date("Y-m-d_H-i-s") . '.sql';
        $command = sprintf(
            "mysqldump --host=%s --user=%s --password=%s %s",
            escapeshellarg(DB_SERVER),
            escapeshellarg(DB_USERNAME),
            escapeshellarg(DB_PASSWORD),
            escapeshellarg(DB_NAME)
        );

        ob_start();
        passthru($command, $return_var);
        $dump = ob_get_clean();

        if ($return_var === 0 && !empty($dump)) {
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $backup_filename . '"');
            header('Content-Length: ' . strlen($dump));
            echo $dump;
            exit;
        } else {
            $mensagem = "Erro ao gerar o backup. O utilitário mysqldump pode não estar instalado ou configurado corretamente no servidor.";
            $mensagem_tipo = 'erro';
        }
    }

    if ($_POST['action'] === 'restore') {
        if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['backup_file']['tmp_name'];
            $file_name = $_FILES['backup_file']['name'];
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if ($file_extension === 'sql') {
                $command = sprintf(
                    "mysql --host=%s --user=%s --password=%s %s < %s",
                    escapeshellarg(DB_SERVER),
                    escapeshellarg(DB_USERNAME),
                    escapeshellarg(DB_PASSWORD),
                    escapeshellarg(DB_NAME),
                    escapeshellarg($file_tmp_path)
                );

                $output = [];
                $return_var = -1;
                exec($command, $output, $return_var);

                if ($return_var === 0) {
                    $mensagem = "Banco de dados restaurado com sucesso a partir de " . htmlspecialchars($file_name) . ".";
                    $mensagem_tipo = 'sucesso';
                } else {
                    $mensagem = "Ocorreu um erro ao tentar restaurar o banco de dados. Verifique o arquivo de backup e as permissões. Detalhe: " . ($output ?? 'Nenhum detalhe disponível.');
                    $mensagem_tipo = 'erro';
                }
            } else {
                $mensagem = "Erro: Por favor, envie um arquivo com a extensão .sql.";
                $mensagem_tipo = 'erro';
            }
        } else {
            $mensagem = "Erro no upload do arquivo. Código: " . ($_FILES['backup_file']['error'] ?? 'Não especificado');
            $mensagem_tipo = 'erro';
        }
    }
}

$page_title = 'Configurações - Admin AppCore';
$active_page = 'configuracoes';
require_once 'templates/header.php';

display_toast($mensagem, $mensagem_tipo);
?>

<h2>Configurações do Sistema</h2>

<div class="config-section">
    <h3>Backup do Banco de Dados</h3>
    <p>Clique no botão abaixo para baixar um arquivo .sql com o backup completo do banco de dados atual.</p>
    <form action="configuracoes.php" method="POST">
        <input type="hidden" name="action" value="backup">
        <button type="submit" class="btn-admin">Fazer Backup Agora</button>
    </form>
</div>

<div class="config-section">
    <h3>Restaurar Backup do Banco de Dados</h3>
    <div class="aviso-importante">
        <h4><strong>ATENÇÃO:</strong> Ação Irreversível</h4>
        <p>Restaurar a partir de um arquivo de backup irá <strong>APAGAR TODOS OS DADOS ATUAIS</strong> e substituí-los pelos dados do arquivo.</p>
        <p>Use com extremo cuidado. Recomenda-se fazer um backup dos dados atuais antes de prosseguir.</p>
    </div>
    <form action="configuracoes.php" method="POST" enctype="multipart/form-data" onsubmit="return confirm('Você tem CERTEZA ABSOLUTA que deseja substituir todos os dados atuais por este backup? Esta ação não pode ser desfeita.');">
        <input type="hidden" name="action" value="restore">
        <div>
            <label for="backup_file">Arquivo de Backup (.sql):</label>
            <input type="file" id="backup_file" name="backup_file" accept=".sql" required>
            <small>Selecione o arquivo de backup (.sql) que você deseja restaurar.</small>
        </div>
        <div>
            <button type="submit" class="btn-admin btn-perigo">Restaurar a partir do Arquivo</button>
        </div>
    </form>
</div>

<?php require_once 'templates/footer.php'; ?>
