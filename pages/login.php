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
        body {
            background: #f8fafc;
        }

        .login-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 2.5rem 2.25rem;
            box-shadow: 0 20px 35px rgba(0, 0, 0, 0.06);
            max-width: 440px;
            margin: 3.5rem auto 4.5rem;
            border-radius: 16px;
            box-sizing: border-box;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-icon-badge {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #f0fdfa;
            color: #0d9488;
            border: 1px solid #ccfbf1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 10px rgba(13, 148, 136, 0.1);
        }

        .login-header h2 {
            font-weight: 800;
            text-transform: uppercase;
            font-size: 1.35rem;
            color: #0f172a;
            letter-spacing: 0.03em;
            margin: 0 0 0.4rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-weight: 700;
            color: #334155;
            font-size: 0.85rem;
            margin-bottom: 0.4rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 0.75rem 2.5rem 0.75rem 2.5rem;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #0f172a;
            outline: none;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }

        .input-wrapper input:focus {
            background: #ffffff;
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
        }

        .input-wrapper i.icon-left {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #0d9488;
            font-size: 0.95rem;
        }

        .toggle-password-btn {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 0.9rem;
            padding: 4px;
        }

        .toggle-password-btn:hover {
            color: #0d9488;
        }

        .btn-login-submit {
            width: 100%;
            padding: 0.85rem;
            font-size: 0.95rem;
            font-weight: 800;
            color: white;
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 0.5rem;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(13, 148, 136, 0.4);
        }

        .forgot-password-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }

        .forgot-password-link:hover {
            color: #0d9488;
            text-decoration: underline;
        }

        .register-footer-box {
            margin-top: 1.75rem;
            border-top: 1px solid #f1f5f9;
            padding-top: 1.25rem;
            text-align: center;
        }

        .register-btn-link {
            display: inline-block;
            margin-top: 0.4rem;
            color: #0d9488;
            font-weight: 800;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s;
        }

        .register-btn-link:hover {
            color: #0f766e;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon-badge">
                    <i class="fas fa-user-lock"></i>
                </div>
                <h2>Área Restrita</h2>
                <p style="color: #64748b; font-size: 0.88rem; font-weight: 500; margin: 0;">
                    Acesse sua conta para continuar no portal
                </p>
            </div>

            <?php if (!empty($message)) echo $message; ?>

            <form action="" method="post">
                <div class="form-group">
                    <label for="email">E-mail ou CPF</label>
                    <div class="input-wrapper">
                        <input type="text" id="email" name="email" required placeholder="seu@email.com ou CPF" autocomplete="username">
                        <i class="fas fa-envelope icon-left"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Senha</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" required placeholder="Sua senha de acesso" autocomplete="current-password">
                        <i class="fas fa-lock icon-left"></i>
                        <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility()" title="Mostrar/ocultar senha">
                            <i class="fas fa-eye" id="toggle-icon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login-submit">
                    <i class="fas fa-sign-in-alt"></i> ENTRAR NO SISTEMA
                </button>

                <a href="forgot_password.php" class="forgot-password-link">
                    <i class="fas fa-key" style="font-size: 0.75rem;"></i> Esqueceu sua senha?
                </a>
            </form>

            <div class="register-footer-box">
                <p style="font-size: 0.88rem; color: #64748b; margin: 0;">Ainda não possui cadastro?</p>
                <a href="register.php" class="register-btn-link">
                    <i class="fas fa-user-plus" style="font-size: 0.8rem;"></i> Inscreva-se como Recenseador
                </a>
            </div>
        </div>
    </main>

    <script>
        function togglePasswordVisibility() {
            var pwdInput = document.getElementById('password');
            var icon = document.getElementById('toggle-icon');
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                pwdInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>

    <?php include '../includes/footer.php'; ?>
</body>

</html>