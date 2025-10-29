<?php
require_once 'config.php';

$sistemas = [];
try {
    $sql = "SELECT id, nome, descricao, imagem_url, link_url FROM sistemas WHERE visivel = 1 ORDER BY nome ASC";
    $stmt = $pdo->query($sql);
    $sistemas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // For a real app, log this error or show a more user-friendly message.
    die("Erro ao buscar sistemas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AppCore - Sesc Pinheiros</title>
    <link rel="icon" type="image/png" href="assets/favicon_appcore.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">AppCore - Sesc Pinheiros</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="admin/login.php">Administrador</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="container-fluid py-4">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4">
            <?php if (!empty($sistemas)): ?>
                <?php foreach ($sistemas as $sistema): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <?php
                            $image_path = !empty($sistema['imagem_url']) && file_exists($sistema['imagem_url']) ? $sistema['imagem_url'] : 'https://via.placeholder.com/300x200/CCCCCC/FFFFFF?Text=Sem+Imagem';
                            ?>
                            <img src="<?php echo htmlspecialchars($image_path); ?>" class="card-img-top" alt="Imagem do <?php echo htmlspecialchars($sistema['nome']); ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($sistema['nome']); ?></h5>
                                <p class="card-text flex-grow-1"><?php echo nl2br(htmlspecialchars($sistema['descricao'] ?? '')); ?></p>
                                <a href="<?php echo htmlspecialchars($sistema['link_url']); ?>" target="_blank" class="btn btn-primary mt-auto">Acessar</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-center">Nenhum sistema cadastrado no momento.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-dark text-white text-center p-3 mt-4">
        <p class="mb-0">&copy; <?php echo date("Y"); ?> Sesc Pinheiros. Todos os direitos reservados.</p>
    </footer>

    <!-- Easter Egg -->
    <div id="dead-pixel"></div>
    <div id="surprise-container">
        <img id="surprise-image" src="assets/images/surprise.png" alt="Surpresa!">
    </div>
    <audio id="surprise-sound" src="assets/audio/surprise.mp3"></audio>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
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
