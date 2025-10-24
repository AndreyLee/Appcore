<?php
require_once '../config.php';

session_start();

// Apenas 'super_admin' pode acessar esta página
if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_user_role']) || $_SESSION['admin_user_role'] !== 'super_admin') {
    header('Location: login.php?erro=' . urlencode('Acesso negado.'));
    exit;
}

$mensagem = '';
$mensagem_tipo = ''; // 'sucesso' ou 'erro'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['backup_file']['tmp_name'];
        $file_name = $_FILES['backup_file']['name'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($file_extension === 'sql') {
            // Comando mysql para importar o backup
            // Nota: Este comando apaga o banco de dados existente e o recria a partir do dump.
            // É crucial que o usuário do banco tenha as permissões adequadas.
            $command = sprintf(
                "mysql --host=%s --user=%s --password=%s %s < %s",
                escapeshellarg(DB_SERVER),
                escapeshellarg(DB_USERNAME),
                escapeshellarg(DB_PASSWORD),
                escapeshellarg(DB_NAME),
                escapeshellarg($file_tmp_path)
            );

            // Executa o comando
            shell_exec($command . ' 2>&1', $output, $return_var);

            if ($return_var === 0) {
                $mensagem = "Banco de dados restaurado com sucesso a partir de " . htmlspecialchars($file_name) . ".";
                $mensagem_tipo = 'sucesso';
            } else {
                $mensagem = "Ocorreu um erro ao tentar restaurar o banco de dados. Verifique o arquivo de backup e as permissões.";
                $mensagem_tipo = 'erro';
                // Opcional: logar a saída do erro para depuração
                // error_log("Erro de restauração: " . $output);
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

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurar Backup - Admin AppCore</title>
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
            <a href="gerenciar_usuarios.php">Gerenciar Usuários</a>
            <a href="alterar_senha.php">Alterar Senha</a>
            <a href="logout.php">Sair</a>
        </nav>
    </header>
    <main class="admin-main">
        <h2>Restaurar Backup do Banco de Dados</h2>

        <?php if ($mensagem): ?>
            <p class="mensagem <?php echo $mensagem_tipo; ?>"><?php echo htmlspecialchars($mensagem); ?></p>
        <?php endif; ?>

        <div class="aviso-importante">
            <h3><strong>ATENÇÃO:</strong> Ação Irreversível</h3>
            <p>Fazer a restauração a partir de um arquivo de backup irá <strong>APAGAR TODOS OS DADOS ATUAIS</strong> do banco de dados e substituí-los pelos dados do arquivo.</p>
            <p>Use esta função com extremo cuidado. Recomenda-se fazer um backup dos dados atuais antes de prosseguir.</p>
        </div>

        <form action="restaurar.php" method="POST" class="form-admin" enctype="multipart/form-data" onsubmit="return confirm('Você tem CERTEZA ABSOLUTA que deseja substituir todos os dados atuais por este backup? Esta ação não pode ser desfeita.');">
            <div>
                <label for="backup_file">Arquivo de Backup (.sql):</label>
                <input type="file" id="backup_file" name="backup_file" accept=".sql" required>
                <small>Selecione o arquivo de backup (.sql) que você baixou anteriormente.</small>
            </div>
            <div>
                <button type="submit" class="btn-admin btn-perigo">Restaurar Backup</button>
                <a href="index.php" class="btn-admin-cancelar">Cancelar</a>
            </div>
        </form>
    </main>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Sesc Pinheiros. Painel Administrativo.</p>
    </footer>
</body>
</html>
