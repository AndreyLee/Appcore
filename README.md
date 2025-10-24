# AppCore - Sesc Pinheiros

AppCore é um HUB de sistemas simples, projetado para apresentar links para diversos sistemas de forma organizada e com um painel administrativo para gerenciamento.

## Funcionalidades

*   **Página Principal:** Exibe os sistemas cadastrados com nome, imagem, descrição e link de acesso.
*   **Painel Administrativo:**
    *   Login seguro (atualmente com credenciais fixas para demonstração).
    *   CRUD (Criar, Ler, Atualizar, Deletar) para os sistemas.
    *   Listagem dos sistemas cadastrados.

## Tecnologias Utilizadas

*   **Backend:** PHP
*   **Frontend:** HTML, CSS
*   **Banco de Dados:** MySQL
*   **Ambiente de Execução:** Docker (com Docker Compose)

## Configuração e Execução do Projeto

### Pré-requisitos

*   Docker ([https://www.docker.com/get-started](https://www.docker.com/get-started))
*   Docker Compose (geralmente incluído na instalação do Docker Desktop)

### Passos para Execução

1.  **Clone o Repositório:**
    ```bash
    git clone <url-do-repositorio>
    cd <nome-da-pasta-do-repositorio>
    ```

2.  **Construa e Inicie os Containers Docker:**
    Na raiz do projeto (onde o arquivo `docker-compose.yml` está localizado), execute o comando:
    ```bash
    docker-compose up -d --build
    ```
    *   O parâmetro `-d` executa os containers em modo detached (em segundo plano).
    *   `--build` força a reconstrução das imagens, útil se você fizer alterações no `Dockerfile`.

3.  **Acesse a Aplicação:**
    *   **Página Principal:** Abra seu navegador e acesse `http://localhost:100`
    *   **Painel Administrativo:** Acesse `http://localhost:100/admin/`
        *   **Credenciais de Login (Admin):**
            *   **Usuário:** `admin`
            *   **Senha:** `password123`

4.  **Parar os Containers:**
    Para parar a aplicação, execute no mesmo diretório do `docker-compose.yml`:
    ```bash
    docker-compose down
    ```

5.  **Remover Volumes (Opcional):**
    Se desejar remover os dados persistentes do banco de dados (armazenados em um volume Docker), use:
    ```bash
    docker-compose down -v
    ```
    **Atenção:** Isso apagará todos os dados cadastrados no banco.

## Estrutura de Arquivos Principal

```
.
├── app/                      # Contém os arquivos da aplicação PHP e frontend
│   ├── admin/                # Arquivos do painel administrativo
│   │   ├── gerenciar_sistema.php
│   │   ├── index.php
│   │   ├── login.php
│   │   └── logout.php
│   ├── assets/               # Arquivos estáticos (CSS, JS, imagens)
│   │   └── css/
│   │       ├── admin_style.css
│   │       └── style.css
│   ├── config.php            # Configuração do banco de dados
│   ├── Dockerfile            # Define a imagem Docker para a aplicação
│   └── index.php             # Página principal da aplicação
├── db_init/                  # Scripts de inicialização do banco de dados
│   └── init.sql              # Cria a tabela 'sistemas' e insere dados de exemplo
├── docker-compose.yml        # Define os serviços Docker (app e db)
└── README.md                 # Este arquivo
```

## Próximos Passos (Sugestões)

*   Implementar um sistema de upload de imagens em vez de usar URLs.
*   Melhorar a segurança do painel admin (ex: senhas com hash, controle de sessão mais robusto).
*   Adicionar mais validações no backend e frontend.
*   Paginação no painel admin se houver muitos sistemas.
*   Testes automatizados.
```
