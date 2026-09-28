<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

if (!$user_id) {
    die("Usuário não especificado.");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    csrf_verify();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $rg = trim($_POST['rg'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $cep = trim($_POST['cep'] ?? '');
    $microregion = $_POST['microregion'] ?? '';
    $processo_sei = trim($_POST['processo_sei'] ?? '');
    $contrato = trim($_POST['contrato'] ?? '');
    $status = $_POST['status'] ?? 'pending';

    // Obter role do usuário antes de atualizar
    $stmtRole = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmtRole->execute([$user_id]);
    $currentUser = $stmtRole->fetch();
    $user_role = $currentUser ? $currentUser['role'] : 'recenseador';

    if ($user_role === 'recenseador' && empty($cpf)) {
        $message = '<div class="alert danger" style="display: block; padding: 0.85rem 1rem; margin-bottom: 1rem; border-radius: var(--radius-sm); font-size: 0.88rem; line-height: 1.6; background: var(--danger-light); color: var(--danger); border: 1px solid rgba(220, 38, 38, 0.15);"><i class="ph ph-x"></i> Erro: O campo CPF é obrigatório para recenseadores.</div>';
    } else {
        $db_cpf = empty($cpf) ? null : $cpf;

        try {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, phone=?, cpf=?, rg=?, address=?, city=?, state=?, cep=?, microregion=?, processo_sei=?, contrato=?, status=? WHERE id=?");
            if ($stmt->execute([$name, $email, $phone, $db_cpf, $rg, $address, $city, $state, $cep, $microregion, $processo_sei, $contrato, $status, $user_id])) {
                $message = '<div class="alert success" style="display: block; padding: 0.85rem 1rem; margin-bottom: 1rem; border-radius: var(--radius-sm); font-size: 0.88rem; line-height: 1.6; background: var(--success-light); color: var(--success); border: 1px solid rgba(13, 157, 108, 0.15);"><i class="ph ph-check"></i> Dados do usuário atualizados com sucesso!</div>';
            } else {
                $message = '<div class="alert danger" style="display: block; padding: 0.85rem 1rem; margin-bottom: 1rem; border-radius: var(--radius-sm); font-size: 0.88rem; line-height: 1.6; background: var(--danger-light); color: var(--danger); border: 1px solid rgba(220, 38, 38, 0.15);"><i class="ph ph-x"></i> Nenhuma alteração foi feita ou ocorreu um erro.</div>';
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $message = '<div class="alert danger" style="display: block; padding: 0.85rem 1rem; margin-bottom: 1rem; border-radius: var(--radius-sm); font-size: 0.88rem; line-height: 1.6; background: var(--danger-light); color: var(--danger); border: 1px solid rgba(220, 38, 38, 0.15);"><i class="ph ph-x"></i> Erro ao atualizar: Este E-mail ou CPF já está cadastrado em outro usuário.</div>';
            } else {
                log_error($e->getMessage(), __FILE__, __LINE__);
                $message = '<div class="alert danger" style="display: block; padding: 0.85rem 1rem; margin-bottom: 1rem; border-radius: var(--radius-sm); font-size: 0.88rem; line-height: 1.6; background: var(--danger-light); color: var(--danger); border: 1px solid rgba(220, 38, 38, 0.15);"><i class="ph ph-x"></i> Erro ao atualizar. Tente novamente.</div>';
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    csrf_verify();
    try {
        $custom_pass = trim($_POST['custom_password'] ?? '');
        $plain_password = !empty($custom_pass) ? $custom_pass : '123456';
        $new_password_hash = password_hash($plain_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
        if ($stmt->execute([$new_password_hash, $user_id])) {
            $message = '<div class="alert success" style="display: block; padding: 0.85rem 1rem; margin-bottom: 1rem; border-radius: var(--radius-sm); font-size: 0.88rem; line-height: 1.6; background: var(--success-light); color: var(--success); border: 1px solid rgba(13, 157, 108, 0.15);"><i class="ph ph-key"></i> Senha redefinida para <strong>' . htmlspecialchars($plain_password) . '</strong> com sucesso! O recenseador já pode acessar o sistema com essa senha.</div>';
        } else {
            $message = '<div class="alert danger" style="display: block; padding: 0.85rem 1rem; margin-bottom: 1rem; border-radius: var(--radius-sm); font-size: 0.88rem; line-height: 1.6; background: var(--danger-light); color: var(--danger); border: 1px solid rgba(220, 38, 38, 0.15);"><i class="ph ph-x"></i> Nenhuma alteração foi feita ou ocorreu um erro na redefinição de senha.</div>';
        }
    } catch (PDOException $e) {
        log_error($e->getMessage(), __FILE__, __LINE__);
        $message = '<div class="alert danger" style="display: block; padding: 0.85rem 1rem; margin-bottom: 1rem; border-radius: var(--radius-sm); font-size: 0.88rem; line-height: 1.6; background: var(--danger-light); color: var(--danger); border: 1px solid rgba(220, 38, 38, 0.15);"><i class="ph ph-x"></i> Erro ao tentar redefinir a senha. Tente novamente.</div>';
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$edit_user = $stmt->fetch();

if (!$edit_user) {
    die("Usuário não encontrado.");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - CAU/DF</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/regular/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        .page-header {
            background: var(--surface-1);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .form-container {
            background: var(--surface-1);
            padding: 2rem;
            border-radius: var(--radius);
            border: 1px solid var(--border-subtle);
            box-shadow: var(--shadow-xs);
            max-width: 800px;
            margin: 2rem auto;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
            color: var(--ink-secondary);
            font-size: 0.82rem;
        }

        .form-control {
            width: 100%;
            padding: 0.65rem 0.85rem;
            border: 1px solid var(--border-default);
            border-radius: var(--radius-sm);
            background: var(--surface-input);
            color: var(--ink-primary);
            font-size: 0.88rem;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--petrol);
            box-shadow: 0 0 0 3px rgba(0, 122, 137, 0.12);
            background: var(--surface-1);
            outline: none;
        }

        .alert {
            padding: 0.85rem 1rem;
            margin-bottom: 1rem;
            border-radius: var(--radius-sm);
            font-size: 0.88rem;
            display: block;
            line-height: 1.5;
        }

        .alert.success {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid rgba(13, 157, 108, 0.15);
        }

        .alert.danger {
            background: var(--danger-light);
            color: var(--danger);
            border: 1px solid rgba(220, 38, 38, 0.15);
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }
    </style>
</head>

<body style="background: var(--surface-0);">
    <?php include '../../includes/header.php'; ?>

    <div class="page-header" style="justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="dashboard.php#users" style="display: inline-flex; align-items: center; gap: 5px; font-size: 0.78rem; font-weight: 700; color: var(--petrol); text-decoration: none; padding: 6px 12px; border-radius: var(--radius-xs); background: var(--petrol-tint); border: 1px solid rgba(0, 122, 137, 0.12); transition: all 0.18s var(--ease);" onmouseover="this.style.background='var(--petrol)';this.style.color='white'" onmouseout="this.style.background='var(--petrol-tint)';this.style.color='var(--petrol)'">
                <i class="ph ph-arrow-left"></i> Voltar
            </a>
            <h2 style="color: var(--petrol-deep); margin: 0; font-size: 1.1rem; font-weight: 700;">Editar Dados do Recenseador</h2>
        </div>
        <div style="display: flex; gap: 1.5rem; background: var(--petrol-tint); padding: 0.5rem 1rem; border-radius: var(--radius-sm); border: 1px solid rgba(0, 122, 137, 0.1);">
            <div style="font-size: 0.82rem; color: var(--ink-secondary);">
                <strong style="display: block; color: var(--petrol-deep); font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;">PROCESSO SEI:</strong>
                <?php echo htmlspecialchars($edit_user['processo_sei'] ?? 'NÃO INFORMADO'); ?>
            </div>
            <div style="font-size: 0.82rem; color: var(--ink-secondary);">
                <strong style="display: block; color: var(--success); font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;">Nº DO EDITAL:</strong>
                <?php echo htmlspecialchars($edit_user['contrato'] ?? 'NÃO INFORMADO'); ?>
            </div>
        </div>
    </div>

    <main class="container mb-5">
        <div class="form-container">
            <?php if (!empty($message))
                echo $message; ?>

            <form method="post">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="update_user">

                <div class="form-group">
                    <label>Status do Cadastro</label>
                    <select name="status" class="form-control"
                        style="font-weight: 700; <?php echo ($edit_user['status'] == 'rejected') ? 'color: var(--danger);' : 'color: var(--success);'; ?>">
                        <option value="pending" <?php if ($edit_user['status'] == 'pending')
                            echo 'selected'; ?>>Em
                            Análise (Pendente)</option>
                        <option value="approved" <?php if ($edit_user['status'] == 'approved')
                            echo 'selected'; ?>>
                            Aprovado</option>
                        <option value="rejected" <?php if ($edit_user['status'] == 'rejected')
                            echo 'selected'; ?>>
                            Reprovado</option>
                    </select>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Processo SEI</label>
                        <input type="text" name="processo_sei" class="form-control"
                            value="<?php echo htmlspecialchars($edit_user['processo_sei'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Nº do Edital</label>
                        <input type="text" name="contrato" class="form-control"
                            value="<?php echo htmlspecialchars($edit_user['contrato'] ?? ''); ?>">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Nome Completo</label>
                        <input type="text" name="name" class="form-control"
                            value="<?php echo htmlspecialchars($edit_user['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>E-mail</label>
                        <input type="email" name="email" class="form-control"
                            value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>CPF <?php echo ($edit_user['role'] === 'recenseador') ? '<span style="color: red;">*</span>' : ''; ?></label>
                        <input type="text" id="cpf" name="cpf" class="form-control"
                            value="<?php echo htmlspecialchars($edit_user['cpf'] ?? ''); ?>"
                            placeholder="000.000.000-00" maxlength="14"
                            <?php echo ($edit_user['role'] === 'recenseador') ? 'required' : ''; ?>>
                    </div>
                    <div class="form-group">
                        <label>RG</label>
                        <input type="text" name="rg" class="form-control"
                            value="<?php echo htmlspecialchars($edit_user['rg']); ?>">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Telefone</label>
                        <input type="text" name="phone" class="form-control"
                            value="<?php echo htmlspecialchars($edit_user['phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label>CEP</label>
                        <input type="text" name="cep" class="form-control"
                            value="<?php echo htmlspecialchars($edit_user['cep']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Endereço Completo</label>
                    <input type="text" name="address" class="form-control"
                        value="<?php echo htmlspecialchars($edit_user['address']); ?>">
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Cidade</label>
                        <input type="text" name="city" class="form-control"
                            value="<?php echo htmlspecialchars($edit_user['city']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Estado (UF)</label>
                        <input type="text" name="state" class="form-control"
                            value="<?php echo htmlspecialchars($edit_user['state']); ?>" maxlength="2">
                    </div>
                </div>

                <div class="form-group">
                    <label>Microrregião de Preferência/Atuação</label>
                    <select name="microregion" class="form-control"
                        style="padding: 0.65rem 0.85rem; border: 1px solid var(--border-default); width: 100%;">
                        <option value="">Não especificada</option>
                        <?php
                        $selected_micro = $edit_user['microregion'] ?? '';
                        $macrorregions = [
                            "Macrorregião 1" => "Macrorregião 1 (Sobradinho, Planaltina, Fercal, Arapoanga)",
                            "Macrorregião 2" => "Macrorregião 2 (Lago Norte, Varjão, Paranoá, Itapoã)",
                            "Macrorregião 3" => "Macrorregião 3 (Lago Sul, Jardim Botânico, São Sebastião)",
                            "Macrorregião 4" => "Macrorregião 4 (Plano Piloto, Cruzeiro, Sudoeste, SIA, Estrutural, Noroeste)",
                            "Macrorregião 5" => "Macrorregião 5 (Gama, Santa Maria, Água Quente)",
                            "Macrorregião 6" => "Macrorregião 6 (Riacho Fundo, Park Way, Candangolândia, Bandeirante, Recanto das Emas)",
                            "Macrorregião 7" => "Macrorregião 7 (Ceilândia, Sol Nascente, Taguatinga, Samambaia, Brazlândia)",
                            "Macrorregião 8" => "Macrorregião 8 (Guará, Águas Claras, Vicente Pires, Arniqueiras)"
                        ];

                        foreach ($macrorregions as $val => $label) {
                            // Verifica se o valor salvo coincide com a chave (ex: Macrorregião 1)
                            $sel = ($selected_micro === $val) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($val) . '" ' . $sel . '>' . htmlspecialchars($label) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <!-- Painel de Redefinição de Senha Administrativa -->
                <div style="background: var(--warning-light); border: 1px solid rgba(217, 119, 6, 0.15); border-radius: var(--radius); padding: 1.25rem; margin-top: 2rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--ink-primary); font-size: 0.92rem; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-key" style="color: var(--warning);"></i> Redefinição de Senha (Administrador)
                    </h4>
                    <p style="font-size: 0.82rem; color: var(--ink-tertiary); margin-bottom: 1rem; line-height: 1.5;">
                        Digite uma nova senha abaixo ou deixe em branco para redefinir para a senha padrão (<strong>123456</strong>).
                    </p>
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <input type="text" id="admin_custom_pass" placeholder="Nova senha (deixe em branco p/ 123456)" class="form-control" style="width: 320px; font-size: 0.85rem; padding: 0.6rem 0.85rem;">
                        <button type="button" style="background: var(--warning); color: white; font-weight: 700; font-size: 0.82rem; padding: 0.6rem 1.2rem; border: none; border-radius: var(--radius-sm); cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: var(--transition);" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'"
                                onclick="submitAdminReset()">
                            <i class="ph ph-arrows-clockwise"></i> Redefinir Senha
                        </button>
                    </div>
                </div>

                <div style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center;">
                    <a href="view_user.php?user_id=<?php echo $edit_user['id']; ?>" style="display: inline-flex; align-items: center; gap: 5px; font-size: 0.82rem; font-weight: 700; color: var(--petrol); text-decoration: none; padding: 0.6rem 1.2rem; border-radius: var(--radius-sm); background: var(--petrol-tint); border: 1px solid rgba(0, 122, 137, 0.12); transition: all 0.18s var(--ease);" onmouseover="this.style.background='var(--petrol)';this.style.color='white'" onmouseout="this.style.background='var(--petrol-tint)';this.style.color='var(--petrol)'" target="_blank">
                        <i class="ph ph-user-circle"></i> Ver Perfil Completo
                    </a>
                    <button type="submit" style="padding: 0.75rem 2rem; font-size: 0.88rem; font-weight: 700; color: white; background: linear-gradient(135deg, var(--petrol) 0%, var(--petrol-deep) 100%); border: none; border-radius: var(--radius-sm); cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: var(--transition);" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(0, 122, 137, 0.3)'" onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                        <i class="ph ph-floppy-disk"></i> Salvar Alterações
                    </button>
                </div>
            </form>

            <!-- Hidden form for resetting password -->
            <form id="resetPasswordForm" method="post" style="display: none;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="custom_password" id="hidden_custom_password" value="">
            </form>
        </div>
    </main>

    <script>
        function submitAdminReset() {
            const pass = document.getElementById('admin_custom_pass').value.trim();
            const passDisplay = pass ? pass : '123456';
            if (confirm('Tem certeza que deseja redefinir a senha deste usuário para "' + passDisplay + '"?')) {
                document.getElementById('hidden_custom_password').value = pass;
                document.getElementById('resetPasswordForm').submit();
            }
        }

        // Mascara CPF
        const cpfInput = document.getElementById('cpf');
        if (cpfInput) {
            cpfInput.addEventListener('input', function (e) {
                let v = e.target.value.replace(/\D/g, '').substring(0, 11);
                if (v.length > 9) v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
                else if (v.length > 6) v = v.replace(/(\d{3})(\d{3})(\d{0,3})/, "$1.$2.$3");
                else if (v.length > 3) v = v.replace(/(\d{3})(\d{0,3})/, "$1.$2");
                e.target.value = v;
            });
        }
    </script>
</body>

</html>