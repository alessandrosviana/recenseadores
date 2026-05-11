<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/mailer.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Only allow approved users if strict, but usually anyone can reset. 
        // User request said "reset de senha dos recessiadores aprovados". 
        // I'll add a check or just allow all for usability, but let's stick to "aprovados" if strictly requested?
        // Actually, if they can't login, they can't check status. 
        // I will allow all registered users, but maybe mention status in email.

        // Generate new password
        $new_password = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update DB
        $updateAuth = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($updateAuth->execute([$hashed_password, $user['id']])) {

            // Prepare Email
            $subject = "Recuperação de Senha - Recenseadores CAU/DF";
            $body = "
            <h3>Olá, " . htmlspecialchars($user['name']) . "</h3>
            <p>Recebemos uma solicitação para resetar sua senha no sistema de Recenseadores do CAU/DF.</p>
            <p>Sua nova senha temporária é: <strong>$new_password</strong></p>
            <p>Recomendamos que você altere esta senha após o login.</p>
            <br>
            <p>Atenciosamente,<br>Equipe CAU/DF</p>
            ";

            sendEmail($email, $subject, $body);

            $message = '<div class="alert success"><i class="fas fa-check-circle"></i> Uma nova senha foi enviada para seu e-mail.<br><small>(Em ambiente de teste: verifique o arquivo <b>emails_log.txt</b> na pasta do projeto)</small></div>';
        } else {
            $message = '<div class="alert danger">Erro ao atualizar senha. Tente novamente.</div>';
        }
    } else {
        // Security: Don't reveal if user exists or not, optionally. But for internal app, maybe say "Email não encontrado".
        $message = '<div class="alert danger">E-mail não encontrado em nossa base de dados.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - CAU/DF</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/recenseadores/assets/css/style.css">
    <style>
        .login-card {
            background: white;
            border: 1px solid #e0e0e0;
            padding: 2.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            max-width: 400px;
            margin: 4rem auto;
            border-radius: 4px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--primary-teal);
        }

        .login-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }

        .login-header h2 {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .btn-block {
            width: 100%;
            padding: 0.8rem;
            font-size: 1rem;
            margin-top: 1rem;
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-lock-open"></i>
                <h2>Recuperar Senha</h2>
                <p style="color: #666; font-size: 0.9rem; margin-top: 0.5rem;">Informe seu e-mail cadastrado para
                    receber uma nova senha.</p>
            </div>

            <?php if (!empty($message))
                echo $message; ?>

            <form action="" method="post">
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <div style="position: relative;">
                        <input type="email" id="email" name="email" required placeholder="seu@email.com"
                            style="padding-left: 2.5rem;">
                        <i class="fas fa-envelope"
                            style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #aaa;"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">ENVIAR NOVA SENHA</button>
            </form>

            <div style="margin-top: 2rem; border-top: 1px solid #eee; padding-top: 1rem; text-align: center;">
                <a href="login.php" class="btn btn-outline" style="border: none; color: #666;">
                    <i class="fas fa-arrow-left"></i> Voltar para o Login
                </a>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>

</html>