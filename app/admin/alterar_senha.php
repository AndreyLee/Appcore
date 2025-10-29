<?php
require_once '../config.php';
require_once 'includes/functions.php';
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// ... (PHP logic remains the same)

$page_title = 'Alterar Senha';
$active_page = 'alterar_senha';
require_once 'templates/header.php';

display_toast($mensagem, $mensagem_tipo);
?>

<h2>Alterar Minha Senha</h2>
<div class="row">
    <div class="col-lg-6">
        <form action="alterar_senha.php" method="POST" class="mt-4">
            <div class="mb-3">
                <label for="senha_atual" class="form-label">Senha Atual:</label>
                <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
            </div>
            <div class="mb-3">
                <label for="nova_senha" class="form-label">Nova Senha:</label>
                <input type="password" class="form-control" id="nova_senha" name="nova_senha" required minlength="6">
            </div>
            <div class="mb-3">
                <label for="confirmar_nova_senha" class="form-label">Confirmar Nova Senha:</label>
                <input type="password" class="form-control" id="confirmar_nova_senha" name="confirmar_nova_senha" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary">Alterar Senha</button>
        </form>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>
