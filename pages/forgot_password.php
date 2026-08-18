<?php
require_once '../config/session.php';
require_once '../config/database.php';

$message = '';
$step = 1; // 1: Validação por CPF + Data Nasc | 2: Redefinição de Senha
$user_id_to_reset = null;
$user_name = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? 'verify';

    if ($action === 'verify') {
        $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
        $birth_date = trim($_POST['birth_date'] ?? '');

        if (empty($cpf) || empty($birth_date)) {
            $message = '<div class="alert danger"><i class="fas fa-exclamation-circle"></i> Por favor, informe o CPF e a Data de Nascimento.</div>';
        } else {
            // Busca recenseador por CPF limpo e Data de Nascimento
            $stmt = $pdo->prepare("SELECT id, name, cpf, birth_date FROM users WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = ? AND birth_date = ? AND role = 'recenseador'");
            $stmt->execute([$cpf, $birth_date]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $step = 2;
                $user_id_to_reset = $user['id'];
                $user_name = $user['name'];
                $_SESSION['reset_user_id'] = $user['id'];
                $_SESSION['reset_user_name'] = $user['name'];
                $message = '<div class="alert success"><i class="fas fa-check-circle"></i> Dados validados com sucesso! Crie sua nova senha abaixo.</div>';
            } else {
                $message = '<div class="alert danger"><i class="fas fa-times-circle"></i> CPF ou Data de Nascimento não conferem com o cadastro de recenseador.</div>';
            }
        }
    } elseif ($action === 'reset_password') {
        $user_id = $_SESSION['reset_user_id'] ?? null;
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!$user_id) {
            $message = '<div class="alert danger"><i class="fas fa-exclamation-triangle"></i> Sessão de recuperação expirada. Por favor, reinicie a verificação.</div>';
            $step = 1;
        } elseif (empty($password) || strlen($password) < 6) {
            $step = 2;
            $user_name = $_SESSION['reset_user_name'] ?? '';
            $message = '<div class="alert danger"><i class="fas fa-exclamation-circle"></i> A nova senha deve ter no mínimo 6 caracteres.</div>';
        } elseif ($password !== $confirm_password) {
            $step = 2;
            $user_name = $_SESSION['reset_user_name'] ?? '';
            $message = '<div class="alert danger"><i class="fas fa-exclamation-triangle"></i> As senhas digitadas não coincidem.</div>';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($updateStmt->execute([$hashed_password, $user_id])) {
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_user_name']);
                header("Location: login.php?reset=success");
                exit();
            } else {
                $step = 2;
                $message = '<div class="alert danger"><i class="fas fa-times-circle"></i> Erro ao atualizar senha no banco de dados. Tente novamente.</div>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha - CAU/DF</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        .login-card {
            background: white;
            border: 1px solid #e0e0e0;
            padding: 2.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            max-width: 450px;
            margin: 3rem auto;
            border-radius: 8px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 1.5rem;
            color: var(--primary-teal);
        }

        .login-header i {
            font-size: 2.8rem;
            margin-bottom: 0.75rem;
            display: block;
        }

        .login-header h2 {
            font-weight: 700;
            font-size: 1.4rem;
            margin: 0;
        }

        .alert {
            padding: 0.85rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.25rem;
            font-size: 0.88rem;
            line-height: 1.4;
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
            padding: 0.85rem;
            font-size: 0.95rem;
            margin-top: 1.25rem;
            font-weight: 700;
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-user-shield"></i>
                <h2>Recuperação de Senha</h2>
                <p style="color: #666; font-size: 0.85rem; margin-top: 0.4rem;">
                    <?php if ($step === 1): ?>
                        Validação de Identidade por dados cadastrais (Sem e-mail)
                    <?php else: ?>
                        Olá, <strong><?php echo htmlspecialchars($_SESSION['reset_user_name'] ?? ''); ?></strong>. Crie sua nova senha abaixo.
                    <?php endif; ?>
                </p>
            </div>

            <?php if (!empty($message)) echo $message; ?>

            <?php if ($step === 1): ?>
                <!-- ETAPA 1: VALIDAR CPF E DATA DE NASCIMENTO -->
                <form action="" method="post">
                    <input type="hidden" name="action" value="verify">
                    
                    <div class="form-group">
                        <label for="cpf"><i class="fas fa-id-card"></i> CPF do Recenseador *</label>
                        <input type="text" id="cpf" name="cpf" required placeholder="000.000.000-00" maxlength="14" class="form-control" style="padding: 0.75rem;">
                    </div>

                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="birth_date"><i class="fas fa-calendar-alt"></i> Data de Nascimento *</label>
                        <input type="date" id="birth_date" name="birth_date" required class="form-control" style="padding: 0.75rem;">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-search"></i> VALIDAR MEUS DADOS
                    </button>
                </form>
            <?php else: ?>
                <!-- ETAPA 2: CADASTRAR NOVA SENHA -->
                <form action="" method="post">
                    <input type="hidden" name="action" value="reset_password">

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label for="reg_password"><i class="fas fa-key"></i> Nova Senha *</label>
                        <div style="position: relative;">
                            <input type="password" name="password" id="reg_password" required placeholder="Mínimo de 6 caracteres" class="form-control" style="padding-right: 40px; padding: 0.75rem;">
                            <button type="button" onclick="togglePasswordVisibility('reg_password', 'eye_icon_1')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6c757d;">
                                <i class="fas fa-eye" id="eye_icon_1"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label for="reg_confirm_password"><i class="fas fa-check-double"></i> Confirmar Nova Senha *</label>
                        <div style="position: relative;">
                            <input type="password" name="confirm_password" id="reg_confirm_password" required placeholder="Repita a nova senha" class="form-control" style="padding-right: 40px; padding: 0.75rem;">
                            <button type="button" onclick="togglePasswordVisibility('reg_confirm_password', 'eye_icon_2')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6c757d;">
                                <i class="fas fa-eye" id="eye_icon_2"></i>
                            </button>
                        </div>
                    </div>

                    <div id="password_match_msg" style="font-size: 0.85rem; margin-top: 0.5rem; font-weight: 600; display: none;"></div>

                    <button type="submit" class="btn btn-primary btn-block" style="background: #28a745; border-color: #28a745;">
                        <i class="fas fa-save"></i> SALVAR NOVA SENHA
                    </button>
                </form>
            <?php endif; ?>

            <div style="margin-top: 1.5rem; border-top: 1px solid #eee; padding-top: 1rem; text-align: center;">
                <a href="login.php" class="btn btn-outline" style="border: none; color: #666; font-size: 0.88rem;">
                    <i class="fas fa-arrow-left"></i> Voltar para a Tela de Login
                </a>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Mascara de CPF
        if (document.getElementById('cpf')) {
            document.getElementById('cpf').addEventListener('input', function (e) {
                let v = e.target.value.replace(/\D/g, '').substring(0, 11);
                if (v.length > 9) v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
                else if (v.length > 6) v = v.replace(/(\d{3})(\d{3})(\d{0,3})/, "$1.$2.$3");
                else if (v.length > 3) v = v.replace(/(\d{3})(\d{0,3})/, "$1.$2");
                e.target.value = v;
            });
        }

        // Alternar visibilidade da senha
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Validação de coincidência de senhas em tempo real
        document.addEventListener('DOMContentLoaded', function() {
            const pass = document.getElementById('reg_password');
            const confirmPass = document.getElementById('reg_confirm_password');
            const msg = document.getElementById('password_match_msg');

            function checkMatch() {
                if (!pass || !confirmPass) return;
                if (!confirmPass.value && !pass.value) {
                    msg.style.display = 'none';
                    confirmPass.style.borderColor = '#ccc';
                    return;
                }
                msg.style.display = 'block';
                if (pass.value && confirmPass.value && pass.value === confirmPass.value) {
                    msg.style.color = '#155724';
                    msg.innerHTML = '<i class="fas fa-check-circle"></i> As senhas coincidem!';
                    confirmPass.style.borderColor = '#28a745';
                } else if (confirmPass.value) {
                    msg.style.color = '#721c24';
                    msg.innerHTML = '<i class="fas fa-times-circle"></i> As senhas não coincidem!';
                    confirmPass.style.borderColor = '#dc3545';
                } else {
                    msg.style.display = 'none';
                }
            }

            if (pass && confirmPass) {
                pass.addEventListener('input', checkMatch);
                confirmPass.addEventListener('input', checkMatch);
            }
        });
    </script>
</body>

</html>