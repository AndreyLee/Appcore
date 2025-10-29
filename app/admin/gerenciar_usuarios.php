<?php
require_once '../config.php';
require_once 'includes/functions.php';

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_user_role'] !== 'super_admin') {
    header('Location: login.php');
    exit;
}

// ... (PHP logic remains the same)

$page_title = 'Gerenciar Usuários';
$active_page = 'gerenciar_usuarios';
require_once 'templates/header.php';

display_toast($mensagem, $mensagem_tipo);
?>

<h2><?php echo $edit_mode ? 'Editar Usuário' : 'Adicionar Usuário'; ?></h2>
<form action="gerenciar_usuarios.php<?php echo $edit_mode ? '?edit_id=' . $usuario_edit['id'] : ''; ?>" method="POST" class="mt-4">
    <?php if ($edit_mode): ?><input type="hidden" name="id" value="<?php echo $usuario_edit['id']; ?>"><?php endif; ?>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="username" class="form-label">Nome de Usuário:</label>
            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($usuario_edit['username'] ?? ''); ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="password" class="form-label">Nova Senha:</label>
            <input type="password" class="form-control" id="password" name="password" minlength="6" placeholder="<?php echo $edit_mode ? 'Deixe em branco para não alterar' : ''; ?>">
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="role" class="form-label">Perfil:</label>
            <select class="form-select" id="role" name="role" required>
                <option value="admin" <?php echo ($usuario_edit['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                <option value="super_admin" <?php echo ($usuario_edit['role'] === 'super_admin') ? 'selected' : ''; ?>>Super Admin</option>
            </select>
        </div>
    </div>
    <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Atualizar' : 'Adicionar'; ?></button>
    <?php if ($edit_mode): ?><a href="gerenciar_usuarios.php" class="btn btn-secondary">Cancelar</a><?php endif; ?>
</form>

<hr class="my-4">

<h2>Usuários Cadastrados</h2>
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead class="table-dark">
            <tr><th>ID</th><th>Usuário</th><th>Perfil</th><th>Criado em</th><th>Ações</th></tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $usuario): ?>
            <tr>
                <td><?php echo $usuario['id']; ?></td>
                <td><?php echo htmlspecialchars($usuario['username']); ?></td>
                <td><span class="badge bg-secondary"><?php echo ucfirst($usuario['role']); ?></span></td>
                <td><?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?></td>
                <td>
                    <a href="?edit_id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
                    <?php if ($usuario['id'] != $_SESSION['admin_user_id']): ?>
                    <a href="?delete_id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza?');">Excluir</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'templates/footer.php'; ?>
