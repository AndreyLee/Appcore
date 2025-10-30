<?php
require_once '../config.php';
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['admin_user_id'])) {
    // Se admin_user_id não estiver setado, significa que ainda estamos usando o login antigo sem DB.
    // Poderíamos redirecionar ou mostrar uma mensagem que a funcionalidade será habilitada em breve.
    // Por ora, vamos permitir o acesso à página, mas a lógica de alteração não funcionará completamente sem o user_id.
    if (!isset($_SESSION['admin_user_id']) && isset($_SESSION['admin_logged_in'])) {
        // $mensagem = "Funcionalidade de alterar senha estará disponível após atualização do sistema de login.";
        // Não vamos bloquear, mas a funcionalidade não terá efeito prático até o login usar o DB.
    } else if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}

$mensagem = '';
$mensagem_tipo = ''; // 'sucesso' ou 'erro'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['admin_user_id'])) {
        $mensagem = "Não é possível alterar a senha no momento. O sistema de usuários ainda não está totalmente integrado.";
        $mensagem_tipo = 'erro';
    } else {
        $user_id = $_SESSION['admin_user_id'];
        $senha_atual = $_POST['senha_atual'] ?? '';
        $nova_senha = $_POST['nova_senha'] ?? '';
        $confirmar_nova_senha = $_POST['confirmar_nova_senha'] ?? '';

        if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_nova_senha)) {
            $mensagem = "Todos os campos são obrigatórios.";
            $mensagem_tipo = 'erro';
        } elseif ($nova_senha !== $confirmar_nova_senha) {
            $mensagem = "A nova senha e a confirmação não coincidem.";
            $mensagem_tipo = 'erro';
        } elseif (strlen($nova_senha) < 6) { // Exemplo de regra de complexidade mínima
            $mensagem = "A nova senha deve ter pelo menos 6 caracteres.";
            $mensagem_tipo = 'erro';
        } else {
            try {
                // 1. Buscar o hash da senha atual do usuário no banco
                $sql_select = "SELECT password_hash FROM usuarios WHERE id = :id";
                $stmt_select = $pdo->prepare($sql_select);
                $stmt_select->bindParam(':id', $user_id, PDO::PARAM_INT);
                $stmt_select->execute();
                $usuario = $stmt_select->fetch(PDO::FETCH_ASSOC);

                if ($usuario && password_verify($senha_atual, $usuario['password_hash'])) {
                    // 2. Se a senha atual estiver correta, gerar hash da nova senha
                    $novo_password_hash = password_hash($nova_senha, PASSWORD_BCRYPT);

                    // 3. Atualizar a senha no banco
                    $sql_update = "UPDATE usuarios SET password_hash = :password_hash WHERE id = :id";
                    $stmt_update = $pdo->prepare($sql_update);
                    $stmt_update->bindParam(':password_hash', $novo_password_hash, PDO::PARAM_STR);
                    $stmt_update->bindParam(':id', $user_id, PDO::PARAM_INT);

                    if ($stmt_update->execute()) {
                        $mensagem = "Senha alterada com sucesso!";
                        $mensagem_tipo = 'sucesso';
                        // Opcional: Forçar logout para o usuário logar com a nova senha
                        // unset($_SESSION['admin_logged_in']);
                        // unset($_SESSION['admin_username']);
                        // unset($_SESSION['admin_user_id']);
                        // unset($_SESSION['admin_user_role']);
                        // session_destroy();
                        // header('Location: login.php?status=senha_alterada');
                        // exit;
                    } else {
                        $mensagem = "Erro ao atualizar a senha no banco de dados.";
                        $mensagem_tipo = 'erro';
                    }
                } else {
                    $mensagem = "A senha atual está incorreta.";
                    $mensagem_tipo = 'erro';
                }
            } catch (PDOException $e) {
                $mensagem = "Erro na operação com o banco de dados: " . $e->getMessage();
                $mensagem_tipo = 'erro';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Senha - Admin AppCore</title>
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
            color: #6c757d;
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

        <div class="row">
            <div class="col-lg-6">
                <h2>Alterar Minha Senha</h2>
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
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    </main>

    <footer class="bg-dark text-white text-center p-3 mt-4">
        <p class="mb-0">&copy; <?php echo date("Y"); ?> Sesc Pinheiros. Painel Administrativo.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
