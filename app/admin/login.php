<?php
session_start();
require_once '../config.php'; // Agora usaremos $pdo

$mensagem_erro = '';
$mensagem_sucesso = ''; // Para a mensagem de senha alterada

// Verificar se há mensagem de status da alteração de senha
if(isset($_GET['status']) && $_GET['status'] === 'senha_alterada') {
    $mensagem_sucesso = "Senha alterada com sucesso! Por favor, faça login novamente.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_input = trim($_POST['username'] ?? '');
    $password_input = $_POST['password'] ?? ''; // Não trimar a senha aqui

    if (empty($username_input) || empty($password_input)) {
        $mensagem_erro = 'Usuário e senha são obrigatórios.';
    } else {
        try {
            $sql = "SELECT id, username, password_hash, role FROM usuarios WHERE username = :username_param";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':username_param', $username_input, PDO::PARAM_STR);
            $stmt->execute();
            $usuario_db = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario_db) {
                // Usuário encontrado, agora verificar a senha
                if (password_verify($password_input, $usuario_db['password_hash'])) {
                    // Senha correta
                    session_regenerate_id(true);
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_user_id'] = $usuario_db['id'];
                    $_SESSION['admin_username'] = $usuario_db['username'];
                    $_SESSION['admin_user_role'] = $usuario_db['role'];

                    header('Location: index.php');
                    exit;
                } else {
                    // Senha incorreta
                    $mensagem_erro = 'Usuário ou senha inválidos (debug: senha incorreta).';
                }
            } else {
                // Usuário não encontrado
                $mensagem_erro = 'Usuário ou senha inválidos (debug: usuário não encontrado).';
            }
        } catch (PDOException $e) {
            $mensagem_erro = "Erro na consulta ao banco de dados: " . $e->getMessage();
        }
    }
}

// Se já estiver logado, redireciona para o painel
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

// Verificar se há mensagem de status da alteração de senha
if(isset($_GET['status']) && $_GET['status'] === 'senha_alterada') {
    $mensagem_sucesso = "Senha alterada com sucesso! Por favor, faça login novamente.";
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Admin AppCore</title>
    <link rel="icon" type"image/png" href="../assets/favicon_appcore.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <style>
        .login-container {
            width: 100%;
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .login-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        .login-container label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .login-container button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .login-container button:hover {
            background-color: #0056b3;
        }
        .mensagem.erro {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }
        .back-to-home {
            text-align: center;
            margin-top: 20px;
        }
        .back-to-home a {
            color: #007bff;
            text-decoration: none;
            font-size: 0.9em;
        }
        .back-to-home a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>AppCore - Login</h1>
    </header>
    <main>
        <div class="login-container">
            <h2>Acesso ao Painel Administrativo</h2>
            <?php if (!empty($mensagem_erro)): ?>
                <p class="mensagem erro"><?php echo htmlspecialchars($mensagem_erro); ?></p>
            <?php endif; ?>
            <?php if (!empty($mensagem_sucesso)): ?>
                <p class="mensagem sucesso"><?php echo htmlspecialchars($mensagem_sucesso); ?></p>
            <?php endif; ?>
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
