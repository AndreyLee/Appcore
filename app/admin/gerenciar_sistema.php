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
    'id' => null,
    'nome' => '',
    'descricao' => '',
    'imagem_url' => '',
    'link_url' => '',
    'visivel' => 1
];
$edit_mode = false;

if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $edit_mode = true;
    try {
        $sql = "SELECT * FROM sistemas WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
        $stmt->execute();
        $sistema = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sistema) {
            $mensagem = "Sistema não encontrado.";
            $mensagem_tipo = 'erro';
            $edit_mode = false;
        }
    } catch (PDOException $e) {
        $mensagem = "Erro ao buscar sistema: " . $e->getMessage();
        $mensagem_tipo = 'erro';
        $edit_mode = false;
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
        $mensagem = "Nome e Link URL são obrigatórios.";
        $mensagem_tipo = 'erro';
    } else {
        if (isset($_FILES['imagem_arquivo']) && $_FILES['imagem_arquivo']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/imagens_sistemas/';
            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                $mensagem = "Erro: Não foi possível criar o diretório de uploads.";
                $mensagem_tipo = 'erro';
            } else {
                $nome_arquivo = uniqid('img_', true) . '_' . basename($_FILES['imagem_arquivo']['name']);
                $caminho_arquivo_completo = $upload_dir . $nome_arquivo;
                $tipo_arquivo = strtolower(pathinfo($caminho_arquivo_completo, PATHINFO_EXTENSION));
                $permitidos = ['jpg', 'jpeg', 'png', 'gif'];

                if (!in_array($tipo_arquivo, $permitidos)) {
                    $mensagem = "Erro: Apenas arquivos JPG, JPEG, PNG e GIF são permitidos.";
                    $mensagem_tipo = 'erro';
                } else if ($_FILES['imagem_arquivo']['size'] > 2097152) {
                    $mensagem = "Erro: O arquivo da imagem é muito grande (máximo 2MB).";
                    $mensagem_tipo = 'erro';
                } else {
                    if (move_uploaded_file($_FILES['imagem_arquivo']['tmp_name'], $caminho_arquivo_completo)) {
                        if ($edit_mode && !empty($imagem_existente) && file_exists('../' . $imagem_existente)) {
                            unlink('../' . $imagem_existente);
                        }
                        $imagem_url_final = 'uploads/imagens_sistemas/' . $nome_arquivo;
                    } else {
                        $mensagem = "Erro ao fazer upload da imagem.";
                        $mensagem_tipo = 'erro';
                    }
                }
            }
        } else if (isset($_FILES['imagem_arquivo']) && $_FILES['imagem_arquivo']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['imagem_arquivo']['error'] != UPLOAD_ERR_OK) {
            $mensagem = "Erro no upload do arquivo: Código " . $_FILES['imagem_arquivo']['error'];
            $mensagem_tipo = 'erro';
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

                $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
                $stmt->bindParam(':descricao', $descricao, PDO::PARAM_STR);
                $stmt->bindParam(':imagem_url', $imagem_url_final, PDO::PARAM_STR);
                $stmt->bindParam(':link_url', $link_url, PDO::PARAM_STR);
                $stmt->bindParam(':visivel', $visivel, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $mensagem_status = $edit_mode ? "Sistema atualizado com sucesso!" : "Sistema adicionado com sucesso!";
                    header('Location: index.php?status=' . urlencode($mensagem_status) . '&type=sucesso');
                    exit;
                } else {
                    $mensagem = $edit_mode ? "Erro ao atualizar sistema no banco." : "Erro ao adicionar sistema no banco.";
                    $mensagem_tipo = 'erro';
                }
            } catch (PDOException $e) {
                $mensagem = "Erro na operação com o banco de dados: " . $e->getMessage();
                $mensagem_tipo = 'erro';
            }
        }
    }
    $sistema_temp = $_POST;
    $sistema_temp['imagem_url'] = $imagem_url_final;
    $sistema = $edit_mode && !$mensagem ? $sistema : array_merge($sistema, $sistema_temp);
    $edit_mode = !empty($id);
}

$page_title = ($edit_mode ? 'Editar' : 'Adicionar') . ' Sistema - Admin AppCore';
$active_page = 'gerenciar_sistema';
require_once 'templates/header.php';

display_toast($mensagem, $mensagem_tipo);
?>

<h2><?php echo $edit_mode ? 'Editar' : 'Adicionar Novo'; ?> Sistema</h2>

<form action="gerenciar_sistema.php<?php echo $edit_mode ? '?edit_id=' . htmlspecialchars($sistema['id']) : ''; ?>" method="POST" class="form-admin" enctype="multipart/form-data">
    <?php if ($edit_mode && isset($sistema['id'])): ?>
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($sistema['id']); ?>">
        <input type="hidden" name="imagem_existente" value="<?php echo htmlspecialchars($sistema['imagem_url'] ?? ''); ?>">
    <?php endif; ?>

    <div>
        <label for="nome">Nome do Sistema:</label>
        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($sistema['nome'] ?? ''); ?>" required>
    </div>
    <div>
        <label for="descricao">Descrição:</label>
        <textarea id="descricao" name="descricao" rows="4"><?php echo htmlspecialchars($sistema['descricao'] ?? ''); ?></textarea>
    </div>
    <div>
        <label for="imagem_arquivo">Imagem do Sistema:</label>
        <input type="file" id="imagem_arquivo" name="imagem_arquivo" accept="image/png, image/jpeg, image/gif">
        <?php if ($edit_mode && !empty($sistema['imagem_url'])): ?>
            <p>Imagem atual: <img src="../<?php echo htmlspecialchars($sistema['imagem_url']); ?>" alt="Imagem atual" style="max-width: 100px; max-height: 50px; vertical-align: middle; margin-left:10px;"></p>
            <small>Envie uma nova imagem para substituí-la, ou deixe em branco para manter a atual.</small>
        <?php else: ?>
            <small>Formatos permitidos: PNG, JPG, GIF.</small>
        <?php endif; ?>
    </div>
    <div>
        <label for="link_url">Link URL do Sistema:</label>
        <input type="url" id="link_url" name="link_url" value="<?php echo htmlspecialchars($sistema['link_url'] ?? ''); ?>" required placeholder="https://sistema.exemplo.com">
    </div>
    <div class="form-group-radio">
        <label>Visibilidade:</label>
        <div class="radio-options">
            <input type="radio" id="visivel_sim" name="visivel" value="1" <?php echo (isset($sistema['visivel']) && $sistema['visivel'] == 1) ? 'checked' : ''; ?>>
            <label for="visivel_sim">Exibir Sistema</label>
        </div>
        <div class="radio-options">
            <input type="radio" id="visivel_nao" name="visivel" value="0" <?php echo (isset($sistema['visivel']) && $sistema['visivel'] == 0) ? 'checked' : ''; ?>>
            <label for="visivel_nao">Não exibir Sistema</label>
        </div>
    </div>
    <div>
        <button type="submit" class="btn-admin"><?php echo $edit_mode ? 'Atualizar' : 'Adicionar'; ?> Sistema</button>
        <a href="index.php" class="btn-admin-cancelar">Cancelar</a>
    </div>
</form>

<?php require_once 'templates/footer.php'; ?>
