<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

// Force login check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$route_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$message = '';

if (!$route_id) {
    die("Rota não especificada.");
}

// Fetch Route Data
$stmt = $pdo->prepare("SELECT * FROM routes WHERE id = ?");
$stmt->execute([$route_id]);
$route = $stmt->fetch();

if (!$route) {
    die("Rota não encontrada.");
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $maps_url = $_POST['maps_url'];

    $cep = $_POST['address_cep'];
    $street = $_POST['address_street'];
    $number = $_POST['address_number'];
    $neighborhood = $_POST['address_neighborhood'];
    $city = $_POST['address_city'];
    $state = $_POST['address_state'];
    $complement = $_POST['address_complement'];

    $microregion = $_POST['microregion'] ?? '';

    // Construct simplified location string just in case
    $full_start_location = "$street, $number - $neighborhood, $city - $state";

    // Handle File Upload for Map Print (admin_file_1)
    $admin_file_1 = $route['admin_file_1'];
    if (isset($_FILES['admin_file_1']) && $_FILES['admin_file_1']['error'] == 0) {
        $uploadDir = '../../uploads/admin_routes/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $ext = pathinfo($_FILES['admin_file_1']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), ['pdf', 'jpg', 'jpeg', 'png'])) {
            $newName = "admin_route_edit_" . time() . "_1.$ext";
            if (move_uploaded_file($_FILES['admin_file_1']['tmp_name'], $uploadDir . $newName)) {
                $admin_file_1 = $uploadDir . $newName;
            }
        }
    }

    $sql = "UPDATE routes SET 
            user_id = ?, 
            title = ?, 
            description = ?, 
            microregion = ?,
            start_location = ?,
            address_cep = ?,
            address_street = ?,
            address_number = ?,
            address_neighborhood = ?,
            address_city = ?,
            address_state = ?,
            address_complement = ?,
            maps_url = ?,
            scheduled_start = ?,
            scheduled_end = ?,
            status = 'pending_acceptance',
            admin_file_1 = ?,
            rejected_reason = NULL
            WHERE id = ?";

    $update_stmt = $pdo->prepare($sql);
    if (
        $update_stmt->execute([
            $user_id,
            $title,
            $desc,
            $microregion,
            $full_start_location,
            $cep,
            $street,
            $number,
            $neighborhood,
            $city,
            $state,
            $complement,
            $maps_url,
            $_POST['scheduled_start'] ?: null,
            $_POST['scheduled_end'] ?: null,
            $admin_file_1,
            $route_id
        ])
    ) {
        // Redirect back to dashboard monitor
        header("Location: dashboard.php#monitor");
        exit();
    } else {
        $message = '<div class="alert danger">Erro ao atualizar a rota.</div>';
    }
}

// Fetch Users for Dropdown
$users_stmt = $pdo->query("SELECT * FROM users WHERE status = 'approved' AND role = 'recenseador'");
$users = $users_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Rota - CAU/DF</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/recenseadores/assets/css/style.css">
    <style>
        .page-header {
            background: #fff;
            padding: 2rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .form-container {
            background: #fff;
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
        }

        .btn-primary {
            padding: 1rem 2rem;
        }
    </style>
    <script>
        function buscaCep() {
            let cepInput = document.getElementById('cep');
            let cep = cepInput.value.replace(/\D/g, '');
            
            if (cep.length !== 8) return;

            // Feedback visual
            cepInput.style.borderColor = '#f59e0b';
            cepInput.placeholder = 'Buscando...';

            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (!('erro' in data)) {
                        document.getElementById('street').value = data.logradouro || '';
                        document.getElementById('neighborhood').value = data.bairro || '';
                        document.getElementById('city').value = data.localidade || '';
                        document.getElementById('state').value = data.uf || '';
                        cepInput.style.borderColor = '#22c55e';
                        document.getElementById('number').focus();
                    } else {
                        cepInput.style.borderColor = '#ef4444';
                        alert('CEP não encontrado. Verifique e tente novamente.');
                    }
                })
                .catch(() => {
                    cepInput.style.borderColor = '#ef4444';
                    alert('Erro ao consultar o CEP. Verifique sua conexão.');
                });
        }

        // Máscara de CEP
        document.addEventListener('DOMContentLoaded', function() {
            const cepInput = document.getElementById('cep');
            if (cepInput) {
                cepInput.addEventListener('input', function() {
                    let v = this.value.replace(/\D/g, '').substring(0, 8);
                    if (v.length > 5) v = v.substring(0,5) + '-' + v.substring(5);
                    this.value = v;
                });
                cepInput.addEventListener('blur', buscaCep);
            }
        });
    </script>
</head>

<body style="background: #fcfcfc;">
    <?php include '../../includes/header.php'; ?>

    <div class="page-header">
        <div class="container">
            <a href="dashboard.php#monitor" class="btn btn-outline mb-2"
                style="font-size: 0.8rem; padding: 0.3rem 0.8rem;">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
            <h2 style="color: var(--primary-teal); margin: 0;">Editar Rota</h2>
        </div>
    </div>

    <main class="container">
        <?php if (!empty($message))
            echo $message; ?>

        <div class="form-container">
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Recenseador Responsável</label>
                    <select name="user_id" required class="form-control">
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo ($u['id'] == $route['user_id']) ? 'selected' : ''; ?>>
                                <?php echo mb_strtoupper(htmlspecialchars($u['name']), 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Título da Rota</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($route['title']); ?>" required
                        class="form-control">
                </div>

                <div class="form-group">
                    <label>Macrorregião / Microrregião (RA)</label>
                    <select name="microregion" required class="form-control">
                        <option value="">Selecione a Região de Trabalho...</option>

                        <?php
                        $selected_micro = $route['microregion'] ?? '';
                        $regions = [
                            'Macrorregião 1' => ['Sobradinho (RA V)', 'Sobradinho II (RA XXVI)', 'Planaltina (RA VI)', 'Fercal (RA XXXI)', 'Arapoanga (RA XXXIV)'],
                            'Macrorregião 2' => ['Lago Norte (RA XVIII)', 'Varjão (RA XXIII)', 'Paranoá (RA VII)', 'Itapoã (RA XXVIII)'],
                            'Macrorregião 3' => ['Lago Sul (RA XVI)', 'Jardim Botânico (RA XXVII)', 'São Sebastião (RA XIV)'],
                            'Macrorregião 4' => ['Plano Piloto (RA I)', 'Cruzeiro (RA XI)', 'Sudoeste/Octogonal (RA XXII)', 'SIA (RA XXIX)', 'SCIA (RA XXV)', 'Noroeste (RA XXXVI)'],
                            'Macrorregião 5' => ['Gama (RA II)', 'Santa Maria (RA XIII)', 'Água Quente (RA XXXV)'],
                            'Macrorregião 6' => ['Riacho Fundo (RA XVII)', 'Riacho Fundo II (RA XXI)', 'Park Way (RA XXIV)', 'Candangolândia (RA XIX)', 'Núcleo Bandeirante (RA VIII)', 'Recanto das Emas (RA XV)'],
                            'Macrorregião 7' => ['Ceilândia (RA IX)', 'Sol Nascente/Pôr do Sol (RA XXXII)', 'Taguatinga (RA III)', 'Samambaia (RA XII)', 'Brazlândia (RA IV)'],
                            'Macrorregião 8' => ['Guará (RA X)', 'Águas Claras (RA XX)', 'Vicente Pires (RA XXX)', 'Arniqueiras (RA XXXIII)']
                        ];

                        foreach ($regions as $macro => $micros) {
                            echo '<optgroup label="' . htmlspecialchars($macro) . '">';
                            foreach ($micros as $micro) {
                                $sel = ($selected_micro === $micro) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($micro) . '" ' . $sel . '>' . htmlspecialchars($micro) . '</option>';
                            }
                            echo '</optgroup>';
                        }
                        ?>
                    </select>
                </div>

                <h4 style="margin-top: 2rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem; color: #888;">
                    Endereço</h4>

                <div class="grid-2">
                    <div class="form-group">
                        <label>CEP</label>
                        <input type="text" name="address_cep" id="cep"
                            value="<?php echo htmlspecialchars($route['address_cep']); ?>" onblur="buscaCep()"
                            class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Logradouro</label>
                        <input type="text" name="address_street" id="street"
                            value="<?php echo htmlspecialchars($route['address_street']); ?>" class="form-control">
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label>Número</label>
                        <input type="text" name="address_number" id="number"
                            value="<?php echo htmlspecialchars($route['address_number']); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Complemento</label>
                        <input type="text" name="address_complement"
                            value="<?php echo htmlspecialchars($route['address_complement']); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Bairro</label>
                        <input type="text" name="address_neighborhood" id="neighborhood"
                            value="<?php echo htmlspecialchars($route['address_neighborhood']); ?>"
                            class="form-control">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Cidade</label>
                        <input type="text" name="address_city" id="city"
                            value="<?php echo htmlspecialchars($route['address_city']); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>UF</label>
                        <input type="text" name="address_state" id="state"
                            value="<?php echo htmlspecialchars($route['address_state']); ?>" class="form-control">
                    </div>
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label><i class="fab fa-google"></i> Link do Google Maps (Local da Vistoria)</label>
                    <input type="url" name="maps_url" value="<?php echo htmlspecialchars($route['maps_url'] ?? ''); ?>" placeholder="https://www.google.com.br/maps/place/..." class="form-control">
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label><i class="fas fa-image"></i> Arquivo 1 (Print do Mapa)</label>
                    <?php if (!empty($route['admin_file_1'])): ?>
                        <div style="background: #f0fdf4; padding: 0.5rem; border-radius: 4px; margin-bottom: 0.5rem; font-size: 0.8rem; color: #166534; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-check-circle"></i> Já possui print anexado.
                            <a href="<?php echo $route['admin_file_1']; ?>" target="_blank" style="color: #166534; text-decoration: underline;">Ver atual</a>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="admin_file_1" class="form-control" accept="image/*,.pdf" style="padding: 0.4rem;">
                    <small class="text-muted">Aparecerá automaticamente no corpo do Termo de Registro.</small>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-plus"></i> Data de Início Planejada</label>
                        <input type="datetime-local" name="scheduled_start" value="<?php echo $route['scheduled_start'] ? date('Y-m-d\TH:i', strtotime($route['scheduled_start'])) : ''; ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-check"></i> Prazo Final Planejado</label>
                        <input type="datetime-local" name="scheduled_end" value="<?php echo $route['scheduled_end'] ? date('Y-m-d\TH:i', strtotime($route['scheduled_end'])) : ''; ?>" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label>Instruções / Descrição</label>
                    <textarea name="description" rows="4"
                        class="form-control"><?php echo htmlspecialchars($route['description']); ?></textarea>
                </div>

                <div style="text-align: right; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Alterações</button>
                </div>
            </form>
        </div>
    </main>
</body>

</html>