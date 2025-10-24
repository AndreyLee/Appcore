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
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Usando o mesmo CSS por enquanto -->
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <link rel="icon" type"image/png" href="../assets/favicon_appcore.png">
</head>
<body>
    <header class="admin-header">
        <h1>Painel Administrativo - AppCore</h1>
        <nav>
            <a href="index.php">Listar Sistemas</a>
            <?php if (isset($_SESSION['admin_user_role']) && $_SESSION['admin_user_role'] === 'super_admin'): ?>
                <a href="gerenciar_usuarios.php">Gerenciar Usuários</a>
                <a href="configuracoes.php">Configurações</a>
            <?php endif; ?>
            <a href="alterar_senha.php">Alterar Senha</a>
            <a href="../index.php">Voltar a Home</a>
            <a href="logout.php">Sair</a>
        </nav>
    </header>
    <main class="admin-main">
        <h2>Sistemas Cadastrados</h2>
        <?php if ($mensagem): // Usa a $mensagem_tipo definida anteriormente ou determina com base no conteúdo ?>
            <p class="mensagem <?php echo !empty($mensagem_tipo) ? $mensagem_tipo : (strpos($mensagem, 'Erro') !== false || strpos($mensagem, 'permissão') !== false ? 'erro' : 'sucesso'); ?>"><?php echo $mensagem; ?></p>
        <?php endif; ?>

        <a href="gerenciar_sistema.php" class="btn-admin">Adicionar Novo Sistema</a>

        <?php if (!empty($sistemas)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Imagem URL</th>
                        <th>Link URL</th>
                        <th>Visível</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sistemas as $sistema): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sistema['id']); ?></td>
                            <td><?php echo htmlspecialchars($sistema['nome']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars(substr($sistema['descricao'] ?? '', 0, 50) . (strlen($sistema['descricao'] ?? '') > 50 ? '...' : ''))); ?></td>
                            <td>
                                <?php if (!empty($sistema['imagem_url']) && file_exists('../' . $sistema['imagem_url'])): ?>
                                    <a href="../<?php echo htmlspecialchars($sistema['imagem_url']); ?>" target="_blank">
                                        <img src="../<?php echo htmlspecialchars($sistema['imagem_url']); ?>" alt="Thumb" style="max-width: 70px; max-height: 35px; vertical-align: middle;">
                                    </a>
                                <?php elseif (!empty($sistema['imagem_url'])): // URL externa ou placeholder ?>
                                    <a href="<?php echo htmlspecialchars($sistema['imagem_url']); ?>" target="_blank">Ver URL</a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><a href="<?php echo htmlspecialchars($sistema['link_url']); ?>" target="_blank">Acessar Link</a></td>
                            <td>
                                <?php if ($sistema['visivel']): ?>
                                    <span class="status-visivel">Sim</span>
                                <?php else: ?>
                                    <span class="status-nao-visivel">Não</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="gerenciar_sistema.php?edit_id=<?php echo $sistema['id']; ?>" class="btn-admin editar">Editar</a>
                                <a href="index.php?delete_id=<?php echo $sistema['id']; ?>" class="btn-admin deletar" onclick="return confirm('Tem certeza que deseja excluir este sistema?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhum sistema cadastrado.</p>
        <?php endif; ?>
    </main>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Sesc Pinheiros. Painel Administrativo.</p>
    </footer>
</body>
</html>
