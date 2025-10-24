<?php
session_start();
require_once '../config.php';
require_once 'includes/functions.php';

$mensagem_erro = '';
$mensagem_sucesso = '';

if (isset($_GET['status']) && $_GET['status'] === 'senha_alterada') {
    $mensagem_sucesso = "Senha alterada com sucesso! Por favor, faça login novamente.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_input = trim($_POST['username'] ?? '');
    $password_input = $_POST['password'] ?? '';

    if (empty($username_input) || empty($password_input)) {
        $mensagem_erro = 'Usuário e senha são obrigatórios.';
    } else {
        try {
            $sql = "SELECT id, username, password_hash, role FROM usuarios WHERE username = :username_param";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':username_param', $username_input, PDO::PARAM_STR);
            $stmt->execute();
            $usuario_db = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario_db && password_verify($password_input, $usuario_db['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user_id'] = $usuario_db['id'];
                $_SESSION['admin_username'] = $usuario_db['username'];
                $_SESSION['admin_user_role'] = $usuario_db['role'];

                header('Location: index.php');
                exit;
            } else {
                $mensagem_erro = 'Usuário ou senha inválidos.';
            }
        } catch (PDOException $e) {
            $mensagem_erro = "Erro na consulta ao banco de dados: " . $e->getMessage();
        }
    }
}

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Admin AppCore</title>
    <link rel="icon" type="image/png" href="../assets/favicon_appcore.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { display: flex; flex-direction: column; min-height: 100vh; }
        .admin-main { flex: 1; display: flex; align-items: center; justify-content: center; }
        .login-container { width: 100%; max-width: 400px; margin: 0 auto; }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>AppCore - Login</h1>
    </header>
    <main class="admin-main">
        <div class="login-container">
            <h2>Acesso ao Painel Administrativo</h2>
            <?php
            if (!empty($mensagem_erro)) display_toast($mensagem_erro, 'erro');
            if (!empty($mensagem_sucesso)) display_toast($mensagem_sucesso, 'sucesso');
            ?>
            <form action="login.php" method="POST">
                <div>
                    <label for="username">Usuário:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div>
                    <label for="password">Senha:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">Entrar</button>
            </form>
            <div class="back-to-home">
                <a href="../index.php">Voltar para a Home</a>
            </div>
        </div>
    </main>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Sesc Pinheiros.</p>
    </footer>
</body>
</html>
