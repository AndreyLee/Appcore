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

        <h2><?php echo $edit_mode ? 'Editar Usuário' : 'Adicionar Novo Usuário'; ?></h2>
        <form action="gerenciar_usuarios.php<?php echo $edit_mode ? '?edit_id=' . $usuario_edit['id'] : ''; ?>" method="POST" class="mt-4">
            <?php if ($edit_mode): ?><input type="hidden" name="id" value="<?php echo $usuario_edit['id']; ?>"><?php endif; ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="username" class="form-label">Nome de Usuário:</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($usuario_edit['username'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Nova Senha:</label>
                    <input type="password" class="form-control" id="password" name="password" minlength="6" placeholder="<?php echo $edit_mode ? 'Deixe em branco para não alterar' : 'Mínimo 6 caracteres'; ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="role" class="form-label">Perfil:</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="admin" <?php echo ($usuario_edit['role'] ?? 'admin') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="super_admin" <?php echo ($usuario_edit['role'] ?? '') === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Atualizar Usuário' : 'Adicionar Usuário'; ?></button>
            <?php if ($edit_mode): ?><a href="gerenciar_usuarios.php" class="btn btn-secondary">Cancelar Edição</a><?php endif; ?>
        </form>

        <hr class="my-4">

        <h2>Usuários Cadastrados</h2>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr><th>ID</th><th>Usuário</th><th>Perfil</th><th>Criado em</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    <?php if (!empty($usuarios)): foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo $usuario['id']; ?></td>
                        <td><?php echo htmlspecialchars($usuario['username']); ?></td>
                        <td><span class="badge bg-info text-dark"><?php echo ucfirst($usuario['role']); ?></span></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?></td>
                        <td>
                            <a href="?edit_id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
                            <?php if ($usuario['id'] != $_SESSION['admin_user_id']): ?>
                            <a href="?delete_id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza?');">Excluir</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5" class="text-center">Nenhum usuário cadastrado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer class="bg-dark text-white text-center p-3 mt-4">
        <p class="mb-0">&copy; <?php echo date("Y"); ?> Sesc Pinheiros. Painel Administrativo.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
