<?php
require_once '../config.php';
require_once 'includes/functions.php';
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_user_role'] !== 'super_admin') {
    header('Location: login.php');
    exit;
}

// ... (PHP logic remains the same)

$page_title = 'Configurações';
$active_page = 'configuracoes';
require_once 'templates/header.php';

display_toast($mensagem, $mensagem_tipo);
?>

<h2>Configurações do Sistema</h2>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3>Backup do Banco de Dados</h3>
            </div>
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
            <div class="card-header bg-danger text-white">
                <h3>Restaurar Backup</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <strong>Atenção:</strong> Esta ação é irreversível e substituirá todos os dados atuais.
                </div>
                <form action="configuracoes.php" method="POST" enctype="multipart/form-data" onsubmit="return confirm('Tem certeza que deseja restaurar?');">
                    <input type="hidden" name="action" value="restore">
                    <div class="mb-3">
                        <label for="backup_file" class="form-label">Arquivo de Backup (.sql):</label>
                        <input class="form-control" type="file" id="backup_file" name="backup_file" accept=".sql" required>
                    </div>
                    <button type="submit" class="btn btn-danger">Restaurar a partir do Arquivo</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>
