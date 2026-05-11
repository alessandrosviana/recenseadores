# 🚀 Como Rodar o Sistema de Recenseadores

Como você está no Windows e parece não ter o PHP ou MySQL instalados, a maneira mais fácil de rodar este projeto é instalando o **XAMPP**. Ele já vem com tudo o que você precisa (Apache, PHP e MySQL).

Siga este passo a passo:

## 1. Instalar o XAMPP
1. Baixe o XAMPP no site oficial: [https://www.apachefriends.org/pt_br/index.html](https://www.apachefriends.org/pt_br/index.html)
2. Instale o programa (pode aceitar as opções padrão).
3. Ao final, abra o **XAMPP Control Panel**.

## 2. Iniciar os Serviços
1. No painel do XAMPP, clique no botão **Start** ao lado de **Apache**.
2. Clique no botão **Start** ao lado de **MySQL**.
   - Se tudo der certo, os nomes ficarão com fundo verde.

## 3. Configurar o Projeto
O XAMPP procura os sites na pasta `C:\xampp\htdocs`.
1. Vá até a pasta onde este projeto está salvo atualmente (`Documents\recenseadores`).
2. Copie toda a pasta `recenseadores`.
3. Cole dentro de `C:\xampp\htdocs`.
   - O caminho final deve ficar assim: `C:\xampp\htdocs\recenseadores`.

## 4. Criar o Banco de Dados
1. Abra seu navegador e acesse: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. No menu lateral, clique em **Novo**.
3. No campo "Nome do banco de dados", digite: `sistema_recenseadores`
4. Clique em **Criar**.
5. Agora, clique na aba **Importar** (no topo).
6. Clique em **Escolher arquivo** e selecione o arquivo `database.sql` que está dentro da pasta do projeto (`C:\xampp\htdocs\recenseadores\database.sql`).
7. Role até o final da página e clique em **Executar**.

## 5. Configurar a Conexão (Se necessário)
O projeto já está configurado para o padrão do XAMPP (usuário `root` e senha vazia).
Se você mudou a senha do MySQL durante a instalação, edite o arquivo `config/database.php`:
```php
$username = 'root';
$password = ''; // Coloque sua senha aqui se tiver definido uma
```

## 6. Acessar o Sistema
1. Abra seu navegador.
2. Acesse: [http://localhost/recenseadores](http://localhost/recenseadores)

### Primeiro Acesso (Admin)
Para criar o primeiro administrador:
1. Acesse: [http://localhost/recenseadores/setup.php](http://localhost/recenseadores/setup.php)
2. O sistema criará o usuário: `admin@sistema.com` / Senha: `admin123`.
3. Depois, você pode fazer login normalmente.

---

**Dica:** Se preferir não mover a pasta, você pode rodar o servidor embutido do PHP, mas precisará adicionar o PHP ao seu PATH do Windows. Mover para o `htdocs` é mais simples para começar.
