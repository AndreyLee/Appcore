CREATE TABLE IF NOT EXISTS sistemas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    imagem_url VARCHAR(255), -- Pode ser o caminho local para o arquivo de upload
    link_url VARCHAR(255) NOT NULL,
    visivel TINYINT(1) NOT NULL DEFAULT 1, -- 1 para visível, 0 para não visível
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Dados de exemplo
INSERT INTO sistemas (nome, descricao, imagem_url, link_url) VALUES
('Sistema Exemplo 1', 'Descrição do Sistema Exemplo 1.', 'https://via.placeholder.com/150/007bff/FFFFFF?Text=Sistema1', 'http://localhost/sistema1'),
('Sistema Exemplo 2', 'Outra descrição interessante.', 'https://via.placeholder.com/150/28a745/FFFFFF?Text=Sistema2', 'http://localhost/sistema2');

-- Tabela de Usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'super_admin') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserir usuário Super Admin inicial
-- Senha: 'admin_password'
INSERT INTO usuarios (username, password_hash, role) VALUES
('superadmin', '\$2y\$10\$DHK9TkhqOrEiZfAl8mqEVeBHUoUt7xDSy.ocHCrYud6.kbYOjJeyK', 'super_admin');
