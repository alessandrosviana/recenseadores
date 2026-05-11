<?php
require_once '../config/session.php';
require_once '../config/database.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Check password
    if ($user && password_verify($password, $user['password'])) {
        // Check if user is active
        if (isset($user['is_active']) && $user['is_active'] == 0) {
            $message = '<div class="alert alert-danger" style="color: #991b1b; background: #fee2e2; border: 1px solid #fecaca; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;"><i class="fas fa-exclamation-circle"></i> Sua conta está <strong>desativada</strong>. Entre em contato com a administração do CAU/DF.</div>';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: recenseador/dashboard.php");
            }
            exit();
        }
    } else {
        $message = '<div class="alert alert-danger" style="color: #dc3545; background: #f8d7da; border: 1px solid #f5c6cb; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">Email ou senha incorretos.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CAU/DF</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
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
            text-transform: uppercase;
            font-size: 1.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }

        .btn-block {
            width: 100%;
            padding: 0.8rem;
            font-size: 1rem;
            margin-top: 1rem;
        }

        .forgot-password {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .forgot-password:hover {
            color: var(--primary-teal);
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-user-circle"></i>
                <h2>Área Restrita</h2>
                <p style="color: #666; font-size: 0.9rem; font-weight: 400; text-transform: none; margin-top: 0.5rem;">
                    Acesse sua conta para continuar</p>
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

                <div class="form-group">
                    <label for="password">Senha</label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" required placeholder="Sua senha"
                            style="padding-left: 2.5rem;">
                        <i class="fas fa-lock"
                            style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #aaa;"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">ENTRAR</button>

                <a href="forgot_password.php" class="forgot-password">Esqueceu sua senha?</a>
            </form>

            <div style="margin-top: 2rem; border-top: 1px solid #eee; padding-top: 1rem; text-align: center;">
                <p style="font-size: 0.9rem; color: #666;">Não tem cadastro?</p>
                <a href="register.php"
                    style="color: var(--primary-teal); font-weight: 600; font-size: 0.9rem;">Inscreva-se como
                    Recenseador</a>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>

</html>