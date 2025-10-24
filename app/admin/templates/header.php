<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin - AppCore'; ?></title>
    <link rel="icon" type="image/png" href="../assets/favicon_appcore.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <header class="admin-header">
        <h1>Painel Administrativo - AppCore</h1>
        <nav>
            <a href="index.php" class="<?php echo ($active_page === 'index') ? 'active' : ''; ?>">Listar Sistemas</a>
            <a href="gerenciar_sistema.php" class="<?php echo ($active_page === 'gerenciar_sistema') ? 'active' : ''; ?>">Adicionar Sistema</a>
            <?php if (isset($_SESSION['admin_user_role']) && $_SESSION['admin_user_role'] === 'super_admin'): ?>
                <a href="gerenciar_usuarios.php" class="<?php echo ($active_page === 'gerenciar_usuarios') ? 'active' : ''; ?>">Gerenciar Usuários</a>
                <a href="configuracoes.php" class="<?php echo ($active_page === 'configuracoes') ? 'active' : ''; ?>">Configurações</a>
            <?php endif; ?>
            <a href="alterar_senha.php" class="<?php echo ($active_page === 'alterar_senha') ? 'active' : ''; ?>">Alterar Senha</a>
            <a href="../index.php">Voltar a Home</a>
            <a href="logout.php">Sair</a>
        </nav>
    </header>
    <main class="admin-main">