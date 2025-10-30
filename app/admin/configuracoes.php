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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/favicon_appcore.png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        header {
            background-color: #007bff;
            color: #ffffff;
            padding: 1.5rem 1rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        footer {
            text-align: center;
            padding: 15px;
            background-color: #007bff;
            color: #ffffff;
            font-size: 0.9em;
            border-top: 1px solid #dee2e6;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Painel Administrativo</h1>
        <nav>
            <a href="index.php" style="color: white; text-decoration: none; margin-left: 15px;">Listar Sistemas</a>
            <a href="gerenciar_sistema.php" style="color: white; text-decoration: none; margin-left: 15px;">Adicionar Sistema</a>
            <?php if (isset($_SESSION['admin_user_role']) && $_SESSION['admin_user_role'] === 'super_admin'): ?>
                <a href="gerenciar_usuarios.php" style="color: white; text-decoration: none; margin-left: 15px;">Gerenciar Usuários</a>
                <a href="configuracoes.php" style="color: white; text-decoration: none; margin-left: 15px;">Configurações</a>
            <?php endif; ?>
            <a href="alterar_senha.php" style="color: white; text-decoration: none; margin-left: 15px;">Alterar Senha</a>
            <a href="../index.php" style="color: white; text-decoration: none; margin-left: 15px;">Voltar a Home</a>
            <a href="logout.php" style="color: white; text-decoration: none; margin-left: 15px;">Sair</a>
        </nav>
    </header>

    <main class="container py-4">
        <?php if ($mensagem): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: '<?php echo $mensagem_tipo === "sucesso" ? "success" : "error"; ?>',
                        title: '<?php echo addslashes(htmlspecialchars($mensagem)); ?>',
                        showConfirmButton: false,
                        showCloseButton: true,
                        timer: 5000,
                        timerProgressBar: true
                    });
                });
            </script>
        <?php endif; ?>

        <h2>Configurações do Sistema</h2>
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h3>Backup do Banco de Dados</h3></div>
                    <div class="card-body">
                        <p>Baixe um arquivo .sql com o backup completo do banco de dados.</p>
                        <form action="configuracoes.php" method="POST">
                            <input type="hidden" name="action" value="backup">
                            <button type="submit" class="btn btn-primary">Fazer Backup Agora</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mt-4 mt-md-0">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white"><h3>Restaurar Backup</h3></div>
                    <div class="card-body">
                        <div class="alert alert-warning"><strong>Atenção:</strong> Ação irreversível.</div>
                        <form action="configuracoes.php" method="POST" enctype="multipart/form-data" onsubmit="return confirm('Tem certeza?');">
                            <input type="hidden" name="action" value="restore">
                            <div class="mb-3">
                                <label for="backup_file" class="form-label">Arquivo de Backup (.sql):</label>
                                <input class="form-control" type="file" id="backup_file" name="backup_file" accept=".sql" required>
                            </div>
                            <button type="submit" class="btn btn-danger">Restaurar do Arquivo</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-dark text-white text-center p-3 mt-4 fixed-bottom">
        <p class="mb-0">&copy; <?php echo date("Y"); ?> Sesc Pinheiros. Painel Administrativo.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
