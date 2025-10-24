<?php
require_once '../config.php';

session_start();

// Acesso restrito a 'super_admin'
if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_user_role']) || $_SESSION['admin_user_role'] !== 'super_admin') {
    header('Location: login.php?erro=' . urlencode('Acesso negado. Apenas Super Admins podem acessar as configurações.'));
    exit;
}

$mensagem = '';
$mensagem_tipo = ''; // 'sucesso' ou 'erro'

// Lógica para processar as ações de backup e restauração
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Ação de Backup
    if ($_POST['action'] === 'backup') {
        $backup_filename = DB_NAME . '_backup_' . date("Y-m-d_H-i-s") . '.sql';
        $command = sprintf(
            "mysqldump --host=%s --user=%s --password=%s %s",
            escapeshellarg(DB_SERVER),
            escapeshellarg(DB_USERNAME),
            escapeshellarg(DB_PASSWORD),
            escapeshellarg(DB_NAME)
        );

        // Limpa qualquer saída que possa ter sido iniciada
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

    // Ação de Restauração
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

                // Usar exec para capturar a saída e o status de retorno corretamente
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

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Admin AppCore</title>
    <link rel="icon" type="image/png" href="../assets/favicon_appcore.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
</head>
<body>
    <header class="admin-header">
        <h1>Painel Administrativo - AppCore</h1>
        <nav>
            <a href="index.php">Listar Sistemas</a>
            <a href="gerenciar_sistema.php">Adicionar Sistema</a>
            <?php if (isset($_SESSION['admin_user_role']) && $_SESSION['admin_user_role'] === 'super_admin'): ?>
                <a href="gerenciar_usuarios.php">Gerenciar Usuários</a>
                <a href="configuracoes.php" class="active">Configurações</a>
            <?php endif; ?>
            <a href="alterar_senha.php">Alterar Senha</a>
            <a href="../index.php">Voltar a Home</a>
            <a href="logout.php">Sair</a>
        </nav>
    </header>
    <main class="admin-main">
        <h2>Configurações do Sistema</h2>

        <?php if ($mensagem): ?>
            <p class="mensagem <?php echo $mensagem_tipo; ?>"><?php echo htmlspecialchars($mensagem); ?></p>
        <?php endif; ?>

        <!-- Seção de Backup -->
        <div class="config-section">
            <h3>Backup do Banco de Dados</h3>
            <p>Clique no botão abaixo para baixar um arquivo .sql com o backup completo do banco de dados atual.</p>
            <form action="configuracoes.php" method="POST">
                <input type="hidden" name="action" value="backup">
                <button type="submit" class="btn-admin">Fazer Backup Agora</button>
            </form>
        </div>

        <!-- Seção de Restauração -->
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
    </main>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Sesc Pinheiros. Painel Administrativo.</p>
    </footer>
</body>
</html>
