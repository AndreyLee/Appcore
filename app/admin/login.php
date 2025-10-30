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
<html lang="pt-BR" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Admin AppCore</title>
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
        }
        .form-signin { max-width: 400px; padding: 1rem; }
    </style>
</head>
<body class="d-flex flex-column h-100">
    <header>
        <h1>AppCore - Login</h1>
    </header>
    <main class="d-flex align-items-center py-4 bg-body-tertiary flex-grow-1">
        <div class="form-signin w-100 m-auto">
            <?php if (!empty($mensagem_erro)): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: '<?php echo addslashes(htmlspecialchars($mensagem_erro)); ?>',
                            showConfirmButton: false,
                            showCloseButton: true,
                            timer: 5000,
                            timerProgressBar: true
                        });
                    });
                </script>
            <?php endif; ?>
            <?php if (!empty($mensagem_sucesso)): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: '<?php echo addslashes(htmlspecialchars($mensagem_sucesso)); ?>',
                            showConfirmButton: false,
                            showCloseButton: true,
                            timer: 5000,
                            timerProgressBar: true
                        });
                    });
                </script>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <h1 class="h3 mb-3 fw-normal text-center">Acesso ao Painel</h1>
                <div class="form-floating mb-2">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Usuário" required>
                    <label for="username">Usuário</label>
                </div>
                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Senha" required>
                    <label for="password">Senha</label>
                </div>
                <button class="btn btn-primary w-100 py-2 mt-3" type="submit">Entrar</button>
                <p class="mt-3 text-center"><a href="../index.php">Voltar para a Home</a></p>
                <p class="mt-5 mb-3 text-body-secondary text-center">&copy; <?php echo date("Y"); ?> Sesc Pinheiros</p>
            </form>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
