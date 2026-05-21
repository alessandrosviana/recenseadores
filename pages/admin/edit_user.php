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
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    $rg = $_POST['rg'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $cep = $_POST['cep'] ?? '';
    $microregion = $_POST['microregion'] ?? '';
    $processo_sei = $_POST['processo_sei'] ?? '';
    $contrato = $_POST['contrato'] ?? '';
    $status = $_POST['status'] ?? 'pending';

    try {
        $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, phone=?, cpf=?, rg=?, address=?, city=?, state=?, cep=?, microregion=?, processo_sei=?, contrato=?, status=? WHERE id=?");
        if ($stmt->execute([$name, $email, $phone, $cpf, $rg, $address, $city, $state, $cep, $microregion, $processo_sei, $contrato, $status, $user_id])) {
            $message = '<div class="alert success"><i class="fas fa-check"></i> Dados do usuário atualizados com sucesso!</div>';
        } else {
            $message = '<div class="alert danger"><i class="fas fa-times"></i> Nenhuma alteração foi feita ou ocorreu um erro.</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert danger"><i class="fas fa-times"></i> Erro ao atualizar: ' . $e->getMessage() . '</div>';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    try {
        $new_password_hash = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
        if ($stmt->execute([$new_password_hash, $user_id])) {
            $message = '<div class="alert success"><i class="fas fa-key"></i> Senha redefinida para <strong>123456</strong> com sucesso! O recenseador já pode acessar o sistema e alterá-la posteriormente.</div>';
        } else {
            $message = '<div class="alert danger"><i class="fas fa-times"></i> Nenhuma alteração foi feita ou ocorreu um erro na redefinição de senha.</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert danger"><i class="fas fa-times"></i> Erro ao tentar redefinir a senha: ' . $e->getMessage() . '</div>';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/recenseadores/assets/css/style.css">
    <style>
        .page-header {
            background: #fff;
            padding: 2rem;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .form-container {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            max-width: 800px;
            margin: 2rem auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .alert.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert.danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
    </style>
</head>

<body style="background: #fcfcfc;">
    <?php include '../../includes/header.php'; ?>

    <div class="page-header" style="justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="dashboard.php#users" class="btn btn-outline" style="font-size: 0.8rem; padding: 0.3rem 0.8rem;">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
            <h2 style="color: var(--primary-teal); margin: 0;">Editar Dados do Recenseador</h2>
        </div>
        <div style="display: flex; gap: 1.5rem; background: #f8f9fa; padding: 0.5rem 1rem; border-radius: 6px; border: 1px solid #eee;">
            <div style="font-size: 0.85rem; color: #555;">
                <strong style="display: block; color: #333; font-size: 0.75rem;">PROCESSO SEI:</strong>
                <?php echo htmlspecialchars($edit_user['processo_sei'] ?? 'NÃO INFORMADO'); ?>
            </div>
            <div style="font-size: 0.85rem; color: #555;">
                <strong style="display: block; color: #198754; font-size: 0.75rem;">Nº DO EDITAL:</strong>
                <?php echo htmlspecialchars($edit_user['contrato'] ?? 'NÃO INFORMADO'); ?>
            </div>
        </div>
    </div>

    <main class="container mb-5">
        <div class="form-container">
            <?php if (!empty($message))
                echo $message; ?>

            <form method="post">
                <input type="hidden" name="action" value="update_user">

                <div class="form-group">
                    <label>Status do Cadastro</label>
                    <select name="status" class="form-control"
                        style="font-weight: bold; <?php echo ($edit_user['status'] == 'rejected') ? 'color: #dc3545;' : 'color: #28a745;'; ?>">
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
                        <label>CPF</label>
                        <input type="text" name="cpf" class="form-control"
                            value="<?php echo htmlspecialchars($edit_user['cpf']); ?>">
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
                        style="padding: 0.8rem; border: 1px solid #ccc; width: 100%;">
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

                <div style="margin-top: 2rem; display: flex; justify-content: space-between;">
                    <a href="view_user.php?user_id=<?php echo $edit_user['id']; ?>" class="btn btn-outline"
                        target="_blank">
                        <i class="fas fa-user-circle"></i> Ver Perfil Completo
                    </a>
                    <button type="button" class="btn btn-primary" style="padding: 0.8rem 2rem;"
                        onclick="if(confirm('Tem certeza que deseja redefinir a senha deste usuário para 123456?')) { document.getElementById('resetPasswordForm').submit(); }">
                        <i class="fas fa-key"></i> Redefinir Senha (123456)
                    </button>
                    <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2rem;">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </div>
            </form>

            <!-- Hidden form for resetting password -->
            <form id="resetPasswordForm" method="post" style="display: none;">
                <input type="hidden" name="action" value="reset_password">
            </form>
        </div>
    </main>

</body>

</html>