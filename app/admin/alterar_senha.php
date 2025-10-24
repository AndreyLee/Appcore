<?php
require_once '../config.php';
require_once 'includes/functions.php';

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['admin_user_id'])) {
    header('Location: login.php');
    exit;
}

$mensagem = '';
$mensagem_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    } elseif (strlen($nova_senha) < 6) {
        $mensagem = "A nova senha deve ter pelo menos 6 caracteres.";
        $mensagem_tipo = 'erro';
    } else {
        try {
            $sql_select = "SELECT password_hash FROM usuarios WHERE id = :id";
            $stmt_select = $pdo->prepare($sql_select);
            $stmt_select->bindParam(':id', $user_id, PDO::PARAM_INT);
            $stmt_select->execute();
            $usuario = $stmt_select->fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($senha_atual, $usuario['password_hash'])) {
                $novo_password_hash = password_hash($nova_senha, PASSWORD_BCRYPT);

                $sql_update = "UPDATE usuarios SET password_hash = :password_hash WHERE id = :id";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->bindParam(':password_hash', $novo_password_hash, PDO::PARAM_STR);
                $stmt_update->bindParam(':id', $user_id, PDO::PARAM_INT);

                if ($stmt_update->execute()) {
                    $mensagem = "Senha alterada com sucesso!";
                    $mensagem_tipo = 'sucesso';
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

$page_title = 'Alterar Senha - Admin AppCore';
$active_page = 'alterar_senha';
require_once 'templates/header.php';

display_toast($mensagem, $mensagem_tipo);
?>

<h2>Alterar Minha Senha</h2>

<form action="alterar_senha.php" method="POST" class="form-admin" style="max-width: 500px;">
    <div>
        <label for="senha_atual">Senha Atual:</label>
        <input type="password" id="senha_atual" name="senha_atual" required>
    </div>
    <div>
        <label for="nova_senha">Nova Senha:</label>
        <input type="password" id="nova_senha" name="nova_senha" required minlength="6">
    </div>
    <div>
        <label for="confirmar_nova_senha">Confirmar Nova Senha:</label>
        <input type="password" id="confirmar_nova_senha" name="confirmar_nova_senha" required minlength="6">
    </div>
    <div>
        <button type="submit" class="btn-admin">Alterar Senha</button>
        <a href="index.php" class="btn-admin-cancelar">Cancelar</a>
    </div>
</form>

<?php require_once 'templates/footer.php'; ?>
