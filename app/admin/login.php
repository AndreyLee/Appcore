<?php
session_start();
require_once '../config.php';
require_once 'includes/functions.php';

// ... (PHP logic remains the same)

if (isset($_SESSION['admin_logged_in'])) {
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/favicon_appcore.png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        html, body { height: 100%; }
        body { display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; }
        .login-container { max-width: 400px; width: 100%; }
    </style>
</head>
<body class="text-center">
    <div class="login-container">
        <main class="form-signin">
            <?php
            if (!empty($mensagem_erro)) display_toast($mensagem_erro, 'erro');
            if (!empty($mensagem_sucesso)) display_toast($mensagem_sucesso, 'sucesso');
            ?>
            <h1 class="h3 mb-3 fw-normal">Acesso ao Painel</h1>
            <form action="login.php" method="POST">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Usuário" required>
                    <label for="username">Usuário</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Senha" required>
                    <label for="password">Senha</label>
                </div>
                <button class="w-100 btn btn-lg btn-primary" type="submit">Entrar</button>
            </form>
            <p class="mt-3"><a href="../index.php">Voltar para a Home</a></p>
            <p class="mt-5 mb-3 text-muted">&copy; <?php echo date("Y"); ?> Sesc Pinheiros</p>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
