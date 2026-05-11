# Sistema de Gestão de Recenseadores

Este é um sistema completo para gerenciamento de recenseadores, incluindo cadastro, upload de documentos, aprovação administrativa e distribuição de rotas.

## Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor Web (Apache, Nginx ou PHP Built-in Server)

## Instalação

1. **Banco de Dados**:
   - Crie um banco deados chamado `sistema_recenseadores` no seu MySQL.
   - Importe o arquivo `database.sql`.

2. **Configuração**:
   - Abra o arquivo `config/database.php`.
   - Ajuste as configurações de conexão ($host, $username, $password, etc.) conforme seu ambiente.

3. **Inicialização**:
   - Abra o terminal na pasta raiz do projeto.
   - Execute o servidor embutido do PHP:
     ```bash
     php -S localhost:8000
     ```
   - Ou configure seu servidor web (XAMPP/WAMP) para apontar para esta pasta.

4. **Primeiro Acesso**:
   - Acesse `http://localhost:8000/setup.php` para criar o usuário Administrador padrão.
     - **Email**: admin@sistema.com
     - **Senha**: admin123
   - Após a criação, você pode deletar o arquivo `setup.php` por segurança.

## Estrutura do Sistema

- **Cadastro**: Novos usuários se cadastram e fazem upload de documentos.
- **Admin**:
  - Aprova ou rejeita cadastros pendentes.
  - Visualiza documentos enviados.
  - Cria e atribui rotas para recenseadores aprovados.
- **Recenseador**:
  - Acompanha status da aprovação.
  - Visualiza rotas atribuídas (com integração Google Maps).

## Tecnologias

- HTML5 / CSS3 (Design Moderno Dark Mode)
- PHP (Sem frameworks, código nativo)
- MySQL (PDO)
