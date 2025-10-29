<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin - AppCore'; ?></title>
    <link rel="icon" type="image/png" href="../assets/favicon_appcore.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">Painel Administrativo</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="adminNavbar">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page === 'index') ? 'active' : ''; ?>" href="index.php">Listar Sistemas</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page === 'gerenciar_sistema') ? 'active' : ''; ?>" href="gerenciar_sistema.php">Adicionar Sistema</a>
                        </li>
                        <?php if (isset($_SESSION['admin_user_role']) && $_SESSION['admin_user_role'] === 'super_admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($active_page === 'gerenciar_usuarios') ? 'active' : ''; ?>" href="gerenciar_usuarios.php">Gerenciar Usuários</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($active_page === 'configuracoes') ? 'active' : ''; ?>" href="configuracoes.php">Configurações</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_page === 'alterar_senha') ? 'active' : ''; ?>" href="alterar_senha.php">Alterar Senha</a>
                        </li>
                    </ul>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="../index.php">Voltar a Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Sair</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main class="container-fluid py-4">