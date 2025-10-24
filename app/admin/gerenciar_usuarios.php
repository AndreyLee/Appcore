<?php
require_once '../config.php';
session_start();

// Autenticação e Autorização
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['admin_user_id'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['admin_user_role'] !== 'super_admin') {
    $_SESSION['mensagem_erro_permissao'] = "Você não tem permissão para acessar esta página.";
    header('Location: index.php'); // Redireciona para a página principal do admin
    exit;
}

$mensagem = '';
$mensagem_tipo = ''; // 'sucesso' ou 'erro'
$usuarios = [];
$edit_mode = false;
$usuario_edit = [
    'id' => null,
    'username' => '',
    'role' => 'admin' // Default role for new users
];

// Processar exclusão
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    // Não permitir que o super_admin se auto-exclua
    if ($delete_id == $_SESSION['admin_user_id']) {
        $mensagem = "Você não pode excluir sua própria conta.";
        $mensagem_tipo = 'erro';
    } else {
        try {
            $sql = "DELETE FROM usuarios WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                $mensagem = "Usuário excluído com sucesso!";
                $mensagem_tipo = 'sucesso';
            } else {
                $mensagem = "Erro ao excluir usuário.";
                $mensagem_tipo = 'erro';
            }
        } catch (PDOException $e) {
            $mensagem = "Erro ao excluir usuário: " . $e->getMessage();
            $mensagem_tipo = 'erro';
        }
    }
    // Redirecionar para limpar GET params e mostrar mensagem
    header('Location: gerenciar_usuarios.php?status=' . urlencode($mensagem) . '&type=' . $mensagem_tipo);
    exit;
}


// Processar formulário (Adicionar/Editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'admin';

    if (empty($username) || !in_array($role, ['admin', 'super_admin'])) {
        $mensagem = "Nome de usuário e perfil são obrigatórios e válidos.";
        $mensagem_tipo = 'erro';
    } elseif (empty($id) && empty($password)) { // Se é novo usuário, senha é obrigatória
        $mensagem = "Senha é obrigatória para novos usuários.";
        $mensagem_tipo = 'erro';
    } elseif (!empty($password) && strlen($password) < 6) {
        $mensagem = "A nova senha deve ter pelo menos 6 caracteres.";
        $mensagem_tipo = 'erro';
    } else {
        try {
            if (!empty($id)) { // Editar usuário
                $sql = "UPDATE usuarios SET username = :username, role = :role";
                if (!empty($password)) {
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);
                    $sql .= ", password_hash = :password_hash";
                }
                $sql .= " WHERE id = :id";

                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->bindParam(':role', $role, PDO::PARAM_STR);
                if (!empty($password)) {
                    $stmt->bindParam(':password_hash', $password_hash, PDO::PARAM_STR);
                }

                if ($stmt->execute()) {
                    $mensagem = "Usuário atualizado com sucesso!";
                    $mensagem_tipo = 'sucesso';
                } else {
                    $mensagem = "Erro ao atualizar usuário.";
                    $mensagem_tipo = 'erro';
                }
            } else { // Adicionar novo usuário
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $sql = "INSERT INTO usuarios (username, password_hash, role) VALUES (:username, :password_hash, :role)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->bindParam(':password_hash', $password_hash, PDO::PARAM_STR);
                $stmt->bindParam(':role', $role, PDO::PARAM_STR);
                if ($stmt->execute()) {
                    $mensagem = "Usuário adicionado com sucesso!";
                    $mensagem_tipo = 'sucesso';
                } else {
                    if ($stmt->errorCode() == '23000') { // Unique constraint violation
                         $mensagem = "Erro: Nome de usuário já existe.";
                    } else {
                        $mensagem = "Erro ao adicionar usuário.";
                    }
                    $mensagem_tipo = 'erro';
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') { // Unique constraint violation (para INSERT ou UPDATE de username)
                 $mensagem = "Erro: Nome de usuário já existe.";
            } else {
                $mensagem = "Erro na operação com o banco de dados: " . $e->getMessage();
            }
            $mensagem_tipo = 'erro';
        }
        if ($mensagem_tipo == 'sucesso') {
            header('Location: gerenciar_usuarios.php?status=' . urlencode($mensagem) . '&type=' . $mensagem_tipo);
            exit;
        }
    }
    // Repopular formulário em caso de erro
    $usuario_edit = $_POST;
    $edit_mode = !empty($id);
}

// Carregar usuário para edição
if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $edit_mode = true;
    try {
        $sql = "SELECT id, username, role FROM usuarios WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
        $stmt->execute();
        $usuario_edit = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$usuario_edit) {
            $mensagem = "Usuário não encontrado para edição.";
            $mensagem_tipo = 'erro';
            $edit_mode = false;
        }
    } catch (PDOException $e) {
        $mensagem = "Erro ao buscar usuário para edição: " . $e->getMessage();
        $mensagem_tipo = 'erro';
        $edit_mode = false;
    }
}


// Buscar todos os usuários para listagem
try {
    $sql_list = "SELECT id, username, role, created_at FROM usuarios ORDER BY username ASC";
    $stmt_list = $pdo->query($sql_list);
    $usuarios = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem = "Erro ao buscar lista de usuários: " . $e->getMessage();
    $mensagem_tipo = 'erro';
}

// Pegar mensagens da URL (após redirect)
if (isset($_GET['status'])) {
    $mensagem = htmlspecialchars($_GET['status']);
    $mensagem_tipo = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'info';
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Admin AppCore</title>
    <link rel="icon" type"image/png" href="../assets/favicon_appcore.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <header class="admin-header">
        <h1>Painel Administrativo - AppCore</h1>
        <nav>
            <a href="index.php">Listar Sistemas</a>
            <a href="gerenciar_sistema.php">Adicionar Sistema</a>
            <?php if (isset($_SESSION['admin_user_role']) && $_SESSION['admin_user_role'] === 'super_admin'): ?>
                <a href="gerenciar_usuarios.php" class="active">Gerenciar Usuários</a>
                <a href="configuracoes.php">Configurações</a>
            <?php endif; ?>
            <a href="alterar_senha.php">Alterar Senha</a>
            <a href="../index.php">Voltar a Home</a>
            <a href="logout.php">Sair</a>
        </nav>
    </header>
    <main class="admin-main">
        <h2><?php echo $edit_mode ? 'Editar' : 'Adicionar'; ?> Usuário</h2>

        <?php if ($mensagem): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: '<?php echo ($mensagem_tipo === "sucesso") ? "success" : "error"; ?>',
                        title: '<?php echo addslashes(htmlspecialchars($mensagem)); ?>',
                        showConfirmButton: false,
                        showCloseButton: true,
                        timer: 5000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    });
                });
            </script>
        <?php endif; ?>

        <form action="gerenciar_usuarios.php<?php echo $edit_mode && isset($usuario_edit['id']) ? '?edit_id=' . $usuario_edit['id'] : ''; ?>" method="POST" class="form-admin" style="max-width: 600px; margin-bottom: 30px;">
            <?php if ($edit_mode && isset($usuario_edit['id'])): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario_edit['id']); ?>">
            <?php endif; ?>

            <div>
                <label for="username">Nome de Usuário:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($usuario_edit['username'] ?? ''); ?>" required <?php echo ($edit_mode && isset($usuario_edit['id']) && $usuario_edit['id'] == $_SESSION['admin_user_id']) ? '' : ''; // Permite editar o próprio nome, mas com cautela ?>>
            </div>

            <div>
                <label for="password">Nova Senha:</label>
                <input type="password" id="password" name="password" minlength="6">
                <?php if ($edit_mode): ?>
                    <small>Deixe em branco para não alterar a senha.</small>
                <?php else: ?>
                    <small>Mínimo 6 caracteres. Obrigatório para novos usuários.</small>
                <?php endif; ?>
            </div>

            <div>
                <label for="role">Perfil de Acesso:</label>
                <select id="role" name="role" required <?php echo ($edit_mode && isset($usuario_edit['id']) && $usuario_edit['id'] == $_SESSION['admin_user_id'] && $usuario_edit['role'] === 'super_admin') ? 'disabled' : '';?>>
                    <option value="admin" <?php echo (isset($usuario_edit['role']) && $usuario_edit['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="super_admin" <?php echo (isset($usuario_edit['role']) && $usuario_edit['role'] === 'super_admin') ? 'selected' : ''; ?>>Super Admin</option>
                </select>
                 <?php if ($edit_mode && isset($usuario_edit['id']) && $usuario_edit['id'] == $_SESSION['admin_user_id'] && $usuario_edit['role'] === 'super_admin'): ?>
                    <input type="hidden" name="role" value="super_admin"> <!-- Garante que o super_admin não se rebaixe acidentalmente se o select estiver disabled -->
                    <small>Você não pode alterar seu próprio perfil de Super Admin.</small>
                <?php endif; ?>
            </div>

            <div>
                <button type="submit" class="btn-admin"><?php echo $edit_mode ? 'Atualizar Usuário' : 'Adicionar Usuário'; ?></button>
                <?php if ($edit_mode): ?>
                    <a href="gerenciar_usuarios.php" class="btn-admin-cancelar">Cancelar Edição</a>
                <?php endif; ?>
            </div>
        </form>

        <hr style="margin: 30px 0;">
        <h2>Usuários Cadastrados</h2>
        <?php if (!empty($usuarios)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome de Usuário</th>
                        <th>Perfil</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['username']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($usuario['role'])); ?></td>
                            <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($usuario['created_at']))); ?></td>
                            <td>
                                <a href="gerenciar_usuarios.php?edit_id=<?php echo $usuario['id']; ?>" class="btn-admin editar">Editar</a>
                                <?php if ($usuario['id'] != $_SESSION['admin_user_id']): // Não mostrar botão de excluir para o próprio usuário ?>
                                <a href="gerenciar_usuarios.php?delete_id=<?php echo $usuario['id']; ?>" class="btn-admin deletar" onclick="return confirm('Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita.');">Excluir</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhum usuário cadastrado além de você (ou ocorreu um erro ao listar).</p>
        <?php endif; ?>

    </main>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Sesc Pinheiros. Painel Administrativo.</p>
    </footer>
</body>
</html>
