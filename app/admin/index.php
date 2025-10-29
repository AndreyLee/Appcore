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

if (isset($_SESSION['mensagem_erro_permissao'])) {
    $mensagem = $_SESSION['mensagem_erro_permissao'];
    $mensagem_tipo = 'erro';
    unset($_SESSION['mensagem_erro_permissao']);
}

if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        // First, get the image path to delete the file
        $sql_select_img = "SELECT imagem_url FROM sistemas WHERE id = :id";
        $stmt_select_img = $pdo->prepare($sql_select_img);
        $stmt_select_img->bindParam(':id', $delete_id, PDO::PARAM_INT);
        $stmt_select_img->execute();
        $sistema_para_deletar = $stmt_select_img->fetch(PDO::FETCH_ASSOC);

        // Delete from the database
        $sql_delete_db = "DELETE FROM sistemas WHERE id = :id";
        $stmt_delete_db = $pdo->prepare($sql_delete_db);
        $stmt_delete_db->bindParam(':id', $delete_id, PDO::PARAM_INT);

        if ($stmt_delete_db->execute()) {
            // If deleted from DB successfully, try to delete the image file
            if ($sistema_para_deletar && !empty($sistema_para_deletar['imagem_url'])) {
                $caminho_imagem_completo = '../' . $sistema_para_deletar['imagem_url'];
                if (file_exists($caminho_imagem_completo) && strpos($sistema_para_deletar['imagem_url'], 'uploads/imagens_sistemas/') === 0) {
                    unlink($caminho_imagem_completo);
                }
            }
            $mensagem = "Sistema deletado com sucesso!";
            $mensagem_tipo = 'sucesso';
        } else {
            $mensagem = "Erro ao deletar sistema do banco de dados.";
            $mensagem_tipo = 'erro';
        }
    } catch (PDOException $e) {
        $mensagem = "Erro ao deletar sistema: " . $e->getMessage();
        $mensagem_tipo = 'erro';
    }
    header('Location: index.php?status=' . urlencode($mensagem) . '&type=' . urlencode($mensagem_tipo));
    exit;
}

$sistemas = [];
try {
    $sql = "SELECT id, nome, descricao, imagem_url, link_url, visivel FROM sistemas ORDER BY nome ASC";
    $stmt = $pdo->query($sql);
    $sistemas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem = "Erro ao buscar sistemas: " . $e->getMessage();
    $mensagem_tipo = 'erro';
}

if (isset($_GET['status'])) {
    $mensagem = htmlspecialchars($_GET['status']);
    $mensagem_tipo = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'sucesso';
}

$page_title = 'Admin - AppCore Sesc Pinheiros';
$active_page = 'index';
require_once 'templates/header.php';

display_toast($mensagem, $mensagem_tipo);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Sistemas Cadastrados</h2>
    <a href="gerenciar_sistema.php" class="btn btn-primary">Adicionar Novo Sistema</a>
</div>

<?php if (!empty($sistemas)): ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Imagem</th>
                    <th>Link</th>
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
                                <img src="../<?php echo htmlspecialchars($sistema['imagem_url']); ?>" alt="Thumb" style="max-width: 100px; max-height: 50px;">
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
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
                            <a href="index.php?delete_id=<?php echo $sistema['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este sistema?');">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="text-center">Nenhum sistema cadastrado.</p>
<?php endif; ?>

<?php require_once 'templates/footer.php'; ?>
