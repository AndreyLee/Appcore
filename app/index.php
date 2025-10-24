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
    <link rel="icon" type"image/png" href="assets/favicon_appcore.png">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="main-header">
        <h1>AppCore - Sesc Pinheiros</h1>
        <nav>
            <a href="admin/login.php" class="nav-link">Administrador</a>
        </nav>
    </header>
    <main>
        <div class="sistemas-grid">
            <?php if (!empty($sistemas)): ?>
                <?php foreach ($sistemas as $sistema): ?>
                    <div class="sistema-card">
                        <?php if (!empty($sistema['imagem_url']) && file_exists($sistema['imagem_url'])): ?>
                            <img src="<?php echo htmlspecialchars($sistema['imagem_url']); ?>" alt="Imagem do <?php echo htmlspecialchars($sistema['nome']); ?>">
                        <?php elseif (!empty($sistema['imagem_url'])): // Caso seja uma URL externa antiga ou placeholder ?>
                            <img src="<?php echo htmlspecialchars($sistema['imagem_url']); ?>" alt="Imagem do <?php echo htmlspecialchars($sistema['nome']); ?>">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/150/CCCCCC/FFFFFF?Text=Sem+Imagem" alt="Sem imagem">
                        <?php endif; ?>
                        <h2><?php echo htmlspecialchars($sistema['nome']); ?></h2>
                        <p><?php echo nl2br(htmlspecialchars($sistema['descricao'] ?? '')); ?></p>
                        <a href="<?php echo htmlspecialchars($sistema['link_url']); ?>" target="_blank" class="btn-acessar">Acessar</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Nenhum sistema cadastrado no momento.</p>
            <?php endif; ?>
        </div>
    </main>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Sesc Pinheiros. Todos os direitos reservados.</p>
    </footer>

    <!-- Easter Egg -->
    <div id="dead-pixel"></div>
    <div id="surprise-container">
        <img id="surprise-image" src="assets/images/surprise.png" alt="Surpresa!">
    </div>
    <audio id="surprise-sound" src="assets/audio/surprise.mp3"></audio>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const deadPixel = document.getElementById('dead-pixel');
            const surpriseContainer = document.getElementById('surprise-container');
            const surpriseSound = document.getElementById('surprise-sound');

            deadPixel.addEventListener('contextmenu', (e) => {
                e.preventDefault(); // Impede o menu de contexto padrão

                // Mostra o container da surpresa
                surpriseContainer.style.display = 'flex';

                // Toca o som
                surpriseSound.currentTime = 0; // Reinicia o som caso já tenha tocado
                surpriseSound.play();
            });

            // Opcional: clicar na imagem/container para fechar
            surpriseContainer.addEventListener('click', () => {
                surpriseContainer.style.display = 'none';
                surpriseSound.pause(); // Para o som se estiver tocando
            });
        });
    </script>
</body>
</html>
