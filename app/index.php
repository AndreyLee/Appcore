<?php
require_once 'config.php';

$sistemas = [];
try {
    // A consulta agora filtra para mostrar apenas sistemas com 'visivel' = 1
    $sql = "SELECT id, nome, descricao, imagem_url, link_url FROM sistemas WHERE visivel = 1 ORDER BY nome ASC";
    $stmt = $pdo->query($sql);
    $sistemas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Em um app real, logar o erro ou mostrar uma mensagem mais amigável.
    echo "Erro ao buscar sistemas: " . $e->getMessage();
}

// A parte de exibição (HTML) será adicionada depois.
// Por enquanto, vamos apenas verificar se os dados são carregados (para depuração interna).
// var_dump($sistemas);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AppCore - Sesc Pinheiros</title>
    <link rel="icon" type="image/png" href="assets/favicon_appcore.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
        <h1>AppCore - Sesc Pinheiros</h1>
        <nav>
            <a href="admin/login.php" style="color: white; text-decoration: none;">Administrador</a>
        </nav>
    </header>

    <main class="container-fluid py-4">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4">
            <?php if (!empty($sistemas)): foreach ($sistemas as $sistema): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo htmlspecialchars(!empty($sistema['imagem_url']) && file_exists($sistema['imagem_url']) ? $sistema['imagem_url'] : 'https://via.placeholder.com/300x200/CCCCCC/FFFFFF?Text=Sem+Imagem'); ?>" class="card-img-top" alt="Imagem de <?php echo htmlspecialchars($sistema['nome']); ?>" style="height: 200px; object-fit: contain; padding: 1rem;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($sistema['nome']); ?></h5>
                            <p class="card-text flex-grow-1"><?php echo nl2br(htmlspecialchars($sistema['descricao'] ?? '')); ?></p>
                            <a href="<?php echo htmlspecialchars($sistema['link_url']); ?>" target="_blank" class="btn btn-primary mt-auto">Acessar</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; else: ?>
                <div class="col-12"><p class="text-center">Nenhum sistema cadastrado no momento.</p></div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="text-white text-center p-3 fixed-bottom">
        <p class="mb-0">&copy; <?php echo date("Y"); ?> Sesc Pinheiros. Todos os direitos reservados.</p>
    </footer>

    <!-- Easter Egg -->
    <div id="dead-pixel"></div>
    <div id="surprise-container" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); justify-content: center; align-items: center; z-index: 10000;">
        <img id="surprise-image" src="assets/images/surprise.png" alt="Surpresa!" style="max-width: 80%; max-height: 80%; border-radius: 10px;">
    </div>
    <audio id="surprise-sound" src="assets/audio/surprise.mp3"></audio>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const deadPixel = document.getElementById('dead-pixel');
            const surpriseContainer = document.getElementById('surprise-container');
            const surpriseSound = document.getElementById('surprise-sound');

            deadPixel.addEventListener('contextmenu', (e) => {
                e.preventDefault();
                surpriseContainer.style.display = 'flex';
                surpriseSound.currentTime = 0;
                surpriseSound.play();
            });

            surpriseContainer.addEventListener('click', () => {
                surpriseContainer.style.display = 'none';
                surpriseSound.pause();
            });
        });
    </script>
</body>
</html>
