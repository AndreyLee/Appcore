<?php
require_once '../config.php'; // Acessa o config.php na pasta pai

session_start();
// Lógica de autenticação atualizada
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['admin_user_id'])) {
    header('Location: login.php');
    exit;
}
// Nenhuma verificação de role específica para esta página por enquanto, acessível a 'admin' e 'super_admin'.

$sistemas = [];
$mensagem = ''; // Para feedback ao usuário
$mensagem_tipo = '';


// Verificar se há mensagem de erro de permissão da página de gerenciamento de usuários
if (isset($_SESSION['mensagem_erro_permissao'])) {
    $mensagem = $_SESSION['mensagem_erro_permissao'];
    $mensagem_tipo = 'erro';
    unset($_SESSION['mensagem_erro_permissao']); // Limpar a mensagem da sessão
}


// Deletar sistema
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        // Primeiro, buscar o caminho da imagem para deletar o arquivo
        $sql_select_img = "SELECT imagem_url FROM sistemas WHERE id = :id";
        $stmt_select_img = $pdo->prepare($sql_select_img);
        $stmt_select_img->bindParam(':id', $delete_id, PDO::PARAM_INT);
        $stmt_select_img->execute();
        $sistema_para_deletar = $stmt_select_img->fetch(PDO::FETCH_ASSOC);

        // Deletar do banco de dados
        $sql_delete_db = "DELETE FROM sistemas WHERE id = :id";
        $stmt_delete_db = $pdo->prepare($sql_delete_db);
        $stmt_delete_db->bindParam(':id', $delete_id, PDO::PARAM_INT);

        if ($stmt_delete_db->execute()) {
            // Se deletou do DB com sucesso, tenta deletar o arquivo da imagem
            if ($sistema_para_deletar && !empty($sistema_para_deletar['imagem_url'])) {
                $caminho_imagem_completo = '../' . $sistema_para_deletar['imagem_url'];
                if (file_exists($caminho_imagem_completo) && strpos($sistema_para_deletar['imagem_url'], 'uploads/imagens_sistemas/') === 0) { // Segurança extra
                    unlink($caminho_imagem_completo);
                }
            }
            $mensagem = "Sistema deletado com sucesso!";
        } else {
            $mensagem = "Erro ao deletar sistema do banco de dados.";
        }
    } catch (PDOException $e) {
        $mensagem = "Erro ao deletar sistema: " . $e->getMessage();
    }
    // Redirecionar para evitar reenvio do formulário ao atualizar
    header('Location: index.php?status=' . urlencode($mensagem));
    exit;
}


try {
    $sql = "SELECT id, nome, descricao, imagem_url, link_url, visivel FROM sistemas ORDER BY nome ASC";
    $stmt = $pdo->query($sql);
    $sistemas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem = "Erro ao buscar sistemas: " . $e->getMessage();
}

if (isset($_GET['status'])) {
    $mensagem = htmlspecialchars($_GET['status']);
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - AppCore Sesc Pinheiros</title>
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
            <?php if (isset($_SESSION['admin_user_role']) && $_SESSION['admin_user_role'] === 'super_admin'): ?>
                <a href="gerenciar_usuarios.php" style="color: white; text-decoration: none; margin-left: 15px;">Gerenciar Usuários</a>
                <a href="configuracoes.php" style="color: white; text-decoration: none; margin-left: 15px;">Configurações</a>
            <?php endif; ?>
            <a href="alterar_senha.php" style="color: white; text-decoration: none; margin-left: 15px;">Alterar Senha</a>
            <a href="../index.php" style="color: white; text-decoration: none; margin-left: 15px;">Voltar a Home</a>
            <a href="logout.php" style="color: white; text-decoration: none; margin-left: 15px;">Sair</a>
        </nav>
    </header>

    <main class="container-fluid py-4">
        <?php if ($mensagem): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: '<?php echo (strpos($mensagem, "Erro") === false && strpos($mensagem, "permissão") === false) ? "success" : "error"; ?>',
                        title: '<?php echo addslashes(htmlspecialchars($mensagem)); ?>',
                        showConfirmButton: false,
                        showCloseButton: true,
                        timer: 5000,
                        timerProgressBar: true
                    });
                });
            </script>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Sistemas Cadastrados</h2>
            <a href="gerenciar_sistema.php" class="btn btn-primary">Adicionar Novo Sistema</a>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr><th>ID</th><th>Nome</th><th>Descrição</th><th>Imagem</th><th>Link</th><th>Visível</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    <?php if (!empty($sistemas)): foreach ($sistemas as $sistema): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sistema['id']); ?></td>
                        <td><?php echo htmlspecialchars($sistema['nome']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars(substr($sistema['descricao'] ?? '', 0, 50) . '...')); ?></td>
                        <td>
                            <?php if (!empty($sistema['imagem_url']) && file_exists('../' . $sistema['imagem_url'])): ?>
                                <img src="../<?php echo htmlspecialchars($sistema['imagem_url']); ?>" alt="Thumb" style="max-width: 100px; max-height: 50px; object-fit: contain;">
                            <?php else: echo '<span class="text-muted">N/A</span>'; endif; ?>
                        </td>
                        <td><a href="<?php echo htmlspecialchars($sistema['link_url']); ?>" target="_blank">Acessar</a></td>
                        <td>
                            <?php if ($sistema['visivel']): ?>
                                <span class="badge bg-success">Sim</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Não</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="gerenciar_sistema.php?edit_id=<?php echo $sistema['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
                            <a href="index.php?delete_id=<?php echo $sistema['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza?');">Excluir</a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="7" class="text-center">Nenhum sistema cadastrado.</td></tr>
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
