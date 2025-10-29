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
$sistema = [
    'id' => null, 'nome' => '', 'descricao' => '', 'imagem_url' => '', 'link_url' => '', 'visivel' => 1
];
$edit_mode = false;

if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $edit_mode = true;
    try {
        $stmt = $pdo->prepare("SELECT * FROM sistemas WHERE id = :id");
        $stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
        $stmt->execute();
        $sistema = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sistema) {
            $mensagem = "Sistema não encontrado."; $mensagem_tipo = 'erro'; $edit_mode = false;
        }
    } catch (PDOException $e) {
        $mensagem = "Erro ao buscar sistema: " . $e->getMessage(); $mensagem_tipo = 'erro'; $edit_mode = false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $link_url = trim($_POST['link_url'] ?? '');
    $visivel = isset($_POST['visivel']) ? (int)$_POST['visivel'] : 0;
    $imagem_existente = $_POST['imagem_existente'] ?? '';
    $imagem_url_final = $imagem_existente;

    if (empty($nome) || empty($link_url)) {
        $mensagem = "Nome e Link URL são obrigatórios."; $mensagem_tipo = 'erro';
    } else {
        if (isset($_FILES['imagem_arquivo']) && $_FILES['imagem_arquivo']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/imagens_sistemas/';
            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                $mensagem = "Erro: Não foi possível criar o diretório de uploads."; $mensagem_tipo = 'erro';
            } else {
                // ... (file upload logic remains the same)
            }
        }

        if (empty($mensagem)) {
            try {
                if (!empty($id)) {
                    $sql = "UPDATE sistemas SET nome = :nome, descricao = :descricao, imagem_url = :imagem_url, link_url = :link_url, visivel = :visivel WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                } else {
                    $sql = "INSERT INTO sistemas (nome, descricao, imagem_url, link_url, visivel) VALUES (:nome, :descricao, :imagem_url, :link_url, :visivel)";
                    $stmt = $pdo->prepare($sql);
                }
                $stmt->execute([':nome' => $nome, ':descricao' => $descricao, ':imagem_url' => $imagem_url_final, ':link_url' => $link_url, ':visivel' => $visivel]);
                header('Location: index.php?status=' . urlencode($edit_mode ? 'Sistema atualizado!' : 'Sistema adicionado!') . '&type=sucesso');
                exit;
            } catch (PDOException $e) {
                $mensagem = "Erro na operação com o banco de dados: " . $e->getMessage(); $mensagem_tipo = 'erro';
            }
        }
    }
    $sistema = array_merge($sistema, $_POST);
    $sistema['imagem_url'] = $imagem_url_final;
    $edit_mode = !empty($id);
}

$page_title = ($edit_mode ? 'Editar' : 'Adicionar') . ' Sistema';
$active_page = 'gerenciar_sistema';
require_once 'templates/header.php';

display_toast($mensagem, $mensagem_tipo);
?>

<h2><?php echo $edit_mode ? 'Editar Sistema' : 'Adicionar Novo Sistema'; ?></h2>
<form action="gerenciar_sistema.php<?php echo $edit_mode ? '?edit_id=' . htmlspecialchars($sistema['id']) : ''; ?>" method="POST" enctype="multipart/form-data" class="mt-4">
    <?php if ($edit_mode): ?>
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($sistema['id']); ?>">
        <input type="hidden" name="imagem_existente" value="<?php echo htmlspecialchars($sistema['imagem_url'] ?? ''); ?>">
    <?php endif; ?>

    <div class="mb-3">
        <label for="nome" class="form-label">Nome do Sistema:</label>
        <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($sistema['nome'] ?? ''); ?>" required>
    </div>
    <div class="mb-3">
        <label for="descricao" class="form-label">Descrição:</label>
        <textarea class="form-control" id="descricao" name="descricao" rows="4"><?php echo htmlspecialchars($sistema['descricao'] ?? ''); ?></textarea>
    </div>
    <div class="mb-3">
        <label for="imagem_arquivo" class="form-label">Imagem do Sistema:</label>
        <input class="form-control" type="file" id="imagem_arquivo" name="imagem_arquivo" accept="image/png, image/jpeg, image/gif">
        <?php if ($edit_mode && !empty($sistema['imagem_url'])): ?>
            <div class="mt-2">
                <img src="../<?php echo htmlspecialchars($sistema['imagem_url']); ?>" alt="Imagem atual" class="img-thumbnail" style="max-width: 150px;">
                <p class="form-text">Envie uma nova imagem para substituí-la.</p>
            </div>
        <?php endif; ?>
    </div>
    <div class="mb-3">
        <label for="link_url" class="form-label">Link URL:</label>
        <input type="url" class="form-control" id="link_url" name="link_url" value="<?php echo htmlspecialchars($sistema['link_url'] ?? ''); ?>" required placeholder="https://sistema.exemplo.com">
    </div>
    <div class="mb-3">
        <label class="form-label">Visibilidade:</label>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="visivel" id="visivel_sim" value="1" <?php echo ($sistema['visivel'] == 1) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="visivel_sim">Visível</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="visivel" id="visivel_nao" value="0" <?php echo ($sistema['visivel'] == 0) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="visivel_nao">Oculto</label>
        </div>
    </div>
    <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Atualizar' : 'Adicionar'; ?> Sistema</button>
    <a href="index.php" class="btn btn-secondary">Cancelar</a>
</form>

<?php require_once 'templates/footer.php'; ?>
