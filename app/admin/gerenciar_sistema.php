<?php
require_once '../config.php'; // Acessa o config.php na pasta pai

session_start();
// Lógica de autenticação atualizada
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['admin_user_id'])) {
    header('Location: login.php');
    exit;
}
// Nenhuma verificação de role específica para esta página por enquanto, acessível a 'admin' e 'super_admin'.

$mensagem = '';
$sistema = [
    'id' => null,
    'nome' => '',
    'descricao' => '',
    'imagem_url' => '',
    'link_url' => '',
    'visivel' => 1 // Padrão para novos sistemas é 'Visível'
];
$edit_mode = false;

// Verificar se está em modo de edição
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
            $edit_mode = false; // Sai do modo de edição se não encontrar
        }
    } catch (PDOException $e) {
        $mensagem = "Erro ao buscar sistema: " . $e->getMessage();
        $edit_mode = false;
    }
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null; // Para edição
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $link_url = trim($_POST['link_url'] ?? '');
    $visivel = isset($_POST['visivel']) ? (int)$_POST['visivel'] : 0; // Pega o valor do radio button
    $imagem_existente = $_POST['imagem_existente'] ?? ''; // Caminho da imagem atual, se houver
    $imagem_url_final = $imagem_existente; // Por padrão, mantém a imagem existente

    // Validação básica
    if (empty($nome) || empty($link_url)) {
        $mensagem = "Nome e Link URL são obrigatórios.";
    } else {
        // Lógica de Upload da Imagem
        if (isset($_FILES['imagem_arquivo']) && $_FILES['imagem_arquivo']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/imagens_sistemas/';
            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                $mensagem = "Erro: Não foi possível criar o diretório de uploads.";
            } else {
                $nome_arquivo = uniqid('img_', true) . '_' . basename($_FILES['imagem_arquivo']['name']);
                $caminho_arquivo_completo = $upload_dir . $nome_arquivo;
                $tipo_arquivo = strtolower(pathinfo($caminho_arquivo_completo, PATHINFO_EXTENSION));

                // Verificar tipo de arquivo
                $permitidos = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($tipo_arquivo, $permitidos)) {
                    $mensagem = "Erro: Apenas arquivos JPG, JPEG, PNG e GIF são permitidos.";
                } else if ($_FILES['imagem_arquivo']['size'] > 2097152) { // 2MB limite
                    $mensagem = "Erro: O arquivo da imagem é muito grande (máximo 2MB).";
                } else {
                    if (move_uploaded_file($_FILES['imagem_arquivo']['tmp_name'], $caminho_arquivo_completo)) {
                        // Se upload deu certo, e era uma edição com imagem antiga, deleta a antiga
                        if ($edit_mode && !empty($imagem_existente) && file_exists('../' . $imagem_existente)) {
                            unlink('../' . $imagem_existente);
                        }
                        $imagem_url_final = 'uploads/imagens_sistemas/' . $nome_arquivo; // Caminho relativo a partir da raiz da app
                    } else {
                        $mensagem = "Erro ao fazer upload da imagem.";
                    }
                }
            }
        } else if (isset($_FILES['imagem_arquivo']) && $_FILES['imagem_arquivo']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['imagem_arquivo']['error'] != UPLOAD_ERR_OK) {
            $mensagem = "Erro no upload do arquivo: Código " . $_FILES['imagem_arquivo']['error'];
        }
        // Continua apenas se não houve erro de upload ou se nenhum arquivo novo foi enviado (mantendo o existente)
        if (empty($mensagem)) {
            try {
                if (!empty($id)) { // Atualizar
                    $sql = "UPDATE sistemas SET nome = :nome, descricao = :descricao, imagem_url = :imagem_url, link_url = :link_url, visivel = :visivel WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                } else { // Inserir
                    // Se é inserção e não houve upload de imagem, pode-se definir uma imagem padrão ou deixar em branco
                    if ($imagem_url_final === '' && !(isset($_FILES['imagem_arquivo']) && $_FILES['imagem_arquivo']['error'] == UPLOAD_ERR_OK) ) {
                         // $imagem_url_final = 'caminho/para/imagem/padrao.png'; // opcional
                    }
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
                    header('Location: index.php?status=' . urlencode($mensagem_status));
                    exit;
                } else {
                    $mensagem = $edit_mode ? "Erro ao atualizar sistema no banco." : "Erro ao adicionar sistema no banco.";
                }
            } catch (PDOException $e) {
                $mensagem = "Erro na operação com o banco de dados: " . $e->getMessage();
            }
        }
    }
    // Preencher $sistema com os dados postados em caso de erro para repopular o formulário
    $sistema_temp = $_POST; // Usar uma variável temporária para não sobrescrever $sistema original se estiver em modo de edição e falhar
    $sistema_temp['imagem_url'] = $imagem_url_final; // Manter a imagem que foi processada
    $sistema = $edit_mode && !$mensagem ? $sistema : array_merge($sistema, $sistema_temp); // Mescla se for erro, senão mantém o $sistema carregado
    $edit_mode = !empty($id); // Manter modo de edição se ID estava presente
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Editar' : 'Adicionar'; ?> Sistema - Admin AppCore</title>
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
            color: #ffffff;
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
            <a href="gerenciar_sistema.php" style="color: white; text-decoration: none; margin-left: 15px;">Adicionar Sistema</a>
            <?php if (isset($_SESSION['admin_user_role']) && $_SESSION['admin_user_role'] === 'super_admin'): ?>
                <a href="gerenciar_usuarios.php" style="color: white; text-decoration: none; margin-left: 15px;">Gerenciar Usuários</a>
                <a href="configuracoes.php" style="color: white; text-decoration: none; margin-left: 15px;">Configurações</a>
            <?php endif; ?>
            <a href="alterar_senha.php" style="color: white; text-decoration: none; margin-left: 15px;">Alterar Senha</a>
            <a href="../index.php" style="color: white; text-decoration: none; margin-left: 15px;">Voltar a Home</a>
            <a href="logout.php" style="color: white; text-decoration: none; margin-left: 15px;">Sair</a>
        </nav>
    </header>

    <main class="container py-4">
        <?php if ($mensagem): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: '<?php echo (strpos($mensagem, "Erro") === false) ? "success" : "error"; ?>',
                        title: '<?php echo addslashes(htmlspecialchars($mensagem)); ?>',
                        showConfirmButton: false,
                        showCloseButton: true,
                        timer: 5000,
                        timerProgressBar: true
                    });
                });
            </script>
        <?php endif; ?>

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
                    <div class="mt-2"><img src="../<?php echo htmlspecialchars($sistema['imagem_url']); ?>" alt="Imagem atual" class="img-thumbnail" style="max-width: 150px;"></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="link_url" class="form-label">Link URL:</label>
                <input type="url" class="form-control" id="link_url" name="link_url" value="<?php echo htmlspecialchars($sistema['link_url'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Visibilidade:</label>
                <div class="form-check"><input class="form-check-input" type="radio" name="visivel" id="visivel_sim" value="1" <?php echo ($sistema['visivel'] ?? 1) == 1 ? 'checked' : ''; ?>><label class="form-check-label" for="visivel_sim">Visível</label></div>
                <div class="form-check"><input class="form-check-input" type="radio" name="visivel" id="visivel_nao" value="0" <?php echo ($sistema['visivel'] ?? 1) == 0 ? 'checked' : ''; ?>><label class="form-check-label" for="visivel_nao">Oculto</label></div>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Atualizar Sistema' : 'Adicionar Sistema'; ?></button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </main>

    <footer class="bg-dark text-white text-center p-3 mt-4">
        <p class="mb-0">&copy; <?php echo date("Y"); ?> Sesc Pinheiros. Painel Administrativo.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
