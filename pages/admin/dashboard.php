<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

// Force login check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$active_tab = 'monitor'; // Default tab

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $userId = $_POST['user_id'] ?? 0;
        $action = $_POST['action'];

        if ($action === 'approve' || $action === 'reject') {
            $status = ($action === 'approve') ? 'approved' : 'rejected';
            
            if ($status === 'approved') {
                $processo_sei = $_POST['processo_sei'] ?? '';
                $contrato = $_POST['contrato'] ?? '';
                $stmt = $pdo->prepare("UPDATE users SET status = ?, processo_sei = ?, contrato = ?, wizard_step = 1, approved_at = NOW() WHERE id = ?");
                if ($stmt->execute([$status, $processo_sei, $contrato, $userId])) {
                    $message = '<div class="alert success"><i class="fas fa-check"></i> Cadastro aprovado com sucesso! Andamento iniciado.</div>';
                }
            } else {
                $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                if ($stmt->execute([$status, $userId])) {
                    $message = '<div class="alert success"><i class="fas fa-check"></i> Cadastro reprovado!</div>';
                }
            }
            $active_tab = 'pending';
        } elseif ($action === 'assign_route') {
            $userId = (int) ($_POST['user_id'] ?? 0);

            if ($userId <= 0) {
                $message = '<div class="alert danger">Erro: Selecione um recenseador válido.</div>';
                $active_tab = 'routes';
            } else {
                $title = $_POST['route_title'];
                $desc = $_POST['route_desc'] ?? '';
                $microregion = $_POST['microregion'] ?? '';
                $cep = $_POST['address_cep'] ?? '';
                $street = $_POST['address_street'] ?? '';
                $number = $_POST['address_number'] ?? '';
                $neighborhood = $_POST['address_neighborhood'] ?? '';
                $city = $_POST['address_city'] ?? '';
                $state = $_POST['address_state'] ?? '';
                $complement = $_POST['address_complement'] ?? '';

                $full_start_location = "$street, $number - $neighborhood, $city - $state";

                $uploadDir = '../../uploads/admin_routes/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $adminFiles = [];
                for ($i = 1; $i <= 3; $i++) {
                    $key = "admin_file_$i";
                    if (isset($_FILES[$key]) && $_FILES[$key]['error'] == 0) {
                        $ext = pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION);
                        if (in_array(strtolower($ext), ['pdf', 'jpg', 'jpeg', 'png'])) {
                            $newName = "admin_route_" . time() . "_$i.$ext";
                            if (move_uploaded_file($_FILES[$key]['tmp_name'], $uploadDir . $newName)) {
                                $adminFiles[$i] = $uploadDir . $newName;
                            }
                        }
                    }
                }

                // --- TRAVA DE DUPLICIDADE ---
                $checkStmt = $pdo->prepare("SELECT id FROM routes WHERE user_id = ? AND title = ? AND status NOT IN ('cancelled', 'rejected')");
                $checkStmt->execute([$userId, $title]);
                
                if ($checkStmt->fetch()) {
                    $message = '<div class="alert warning"><i class="fas fa-exclamation-triangle"></i> Atenção: Este recenseador já possui uma rota aberta com este exato título. A atribuição duplicada foi bloqueada.</div>';
                } else {
                    $routeData = [
                        'user_id' => $userId,
                        'title' => $title,
                        'description' => $desc,
                        'microregion' => $microregion,
                        'start_location' => $full_start_location,
                        'scheduled_start' => !empty($_POST['scheduled_start']) ? $_POST['scheduled_start'] : null,
                        'scheduled_end' => !empty($_POST['scheduled_end']) ? $_POST['scheduled_end'] : null,
                        'status' => 'assigned',
                        'address_cep' => $cep,
                        'address_street' => $street,
                        'address_number' => $number,
                        'address_neighborhood' => $neighborhood,
                        'address_city' => $city,
                        'address_state' => $state,
                        'address_complement' => $complement,
                        'admin_file_1' => $adminFiles[1] ?? null,
                        'admin_file_2' => $adminFiles[2] ?? null,
                        'admin_file_3' => $adminFiles[3] ?? null,
                        'maps_url' => $_POST['maps_url'] ?? null,
                        'wizard_step' => 2
                    ];

                    $cols = implode(', ', array_keys($routeData)) . ', created_at';
                    $places = implode(', ', array_fill(0, count($routeData), '?')) . ', NOW()';
                    $sql = "INSERT INTO routes ($cols) VALUES ($places)";

                    try {
                        $stmt = $pdo->prepare($sql);
                        if ($stmt->execute(array_values($routeData))) {
                            $message = '<div class="alert success">Rota atribuída com sucesso!</div>';
                        }
                    } catch (PDOException $e) {
                        $message = '<div class="alert danger">Erro ao atribuir rota: ' . $e->getMessage() . '</div>';
                    }
                }
            }
            $active_tab = 'routes';
        } elseif ($action === 'cancel_route') {
            $routeId = $_POST['route_id'];
            $stmt = $pdo->prepare("UPDATE routes SET status = 'cancelled' WHERE id = ?");
            if ($stmt->execute([$routeId])) {
                $message = '<div class="alert danger"><i class="fas fa-ban"></i> Rota cancelada.</div>';
            }
            $active_tab = 'monitor';
        } elseif ($action === 'mark_delayed') {
            $routeId = $_POST['route_id'];
            $stmt = $pdo->prepare("UPDATE routes SET status = 'delayed' WHERE id = ?");
            if ($stmt->execute([$routeId])) {
                $message = '<div class="alert warning"><i class="fas fa-clock"></i> Rota marcada como atrasada.</div>';
            }
            $active_tab = 'monitor';
        } elseif ($action === 'update_wizard') {
            $routeId = $_POST['route_id'];
            $step = (int) $_POST['step'];
            $sei_pag = $_POST['sei_pagamento'] ?? '';
            
            // Se o passo for 4 ou mais, garante que o status seja 'completed' e tenha data de conclusão
            if ($step >= 4) {
                // Ao mudar para passo 4 ou mais, garante que o status da rota seja 'completed' para aparecer na aba correta
                $stmt = $pdo->prepare("UPDATE routes SET wizard_step = ?, sei_pagamento = ?, status = 'completed', completed_at = IFNULL(completed_at, NOW()) WHERE id = ?");
                $execute_params = [$step, $sei_pag, $routeId];
            } else {
                $stmt = $pdo->prepare("UPDATE routes SET wizard_step = ?, sei_pagamento = ? WHERE id = ?");
                $execute_params = [$step, $sei_pag, $routeId];
            }

            if ($stmt->execute($execute_params)) {
                // Mensagem removida a pedido
            }
            $active_tab = ($step == 6) ? 'paid' : 'wizard';
        } elseif ($action === 'upload_payment_pdf') {
            $routeId = (int) $_POST['route_id'];
            $uploadDir = '../../uploads/payments/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            if (isset($_FILES['payment_pdf']) && $_FILES['payment_pdf']['error'] == 0) {
                $ext = pathinfo($_FILES['payment_pdf']['name'], PATHINFO_EXTENSION);
                if (strtolower($ext) === 'pdf') {
                    $newName = "payment_" . $routeId . "_" . time() . ".pdf";
                    if (move_uploaded_file($_FILES['payment_pdf']['tmp_name'], $uploadDir . $newName)) {
                        $filePath = $uploadDir . $newName;
                        $stmt = $pdo->prepare("UPDATE routes SET payment_pdf = ?, wizard_step = 6 WHERE id = ?");
                        if ($stmt->execute([$filePath, $routeId])) {
                            $message = '<div class="alert success"><i class="fas fa-check"></i> Comprovante de pagamento enviado com sucesso! Rota Liquidada.</div>';
                        }
                    }
                }
            }
            $active_tab = 'paid';
        } elseif ($action === 'create_admin') {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];

            if (empty($name) || empty($email) || empty($password)) {
                $message = '<div class="alert danger"><i class="fas fa-times"></i> Preencha todos os campos.</div>';
            } elseif (!preg_match('/@caudf\.gov\.br$/', $email)) {
                $message = '<div class="alert danger"><i class="fas fa-times"></i> Erro: Apenas e-mails do domínio <strong>@caudf.gov.br</strong> são permitidos.</div>';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                try {
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'admin', 'approved')");
                    if ($stmt->execute([$name, $email, $hashed_password])) {
                        $message = '<div class="alert success"><i class="fas fa-check"></i> Administrador criado com sucesso!</div>';
                    }
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $message = '<div class="alert danger"><i class="fas fa-times"></i> Erro: Este e-mail já está cadastrado.</div>';
                    } else {
                        $message = '<div class="alert danger"><i class="fas fa-times"></i> Erro ao criar admin: ' . $e->getMessage() . '</div>';
                    }
                }
            }
            $active_tab = 'admins';
        } elseif ($action === 'save_calculation') {
            $routeId = (int) $_POST['route_id'];
            $seiPagamento = trim($_POST['sei_pagamento'] ?? '');

            if ($routeId <= 0) {
                $message = '<div class="alert danger"><i class="fas fa-exclamation-triangle"></i> Erro: Você precisa selecionar uma rota concluída para realizar o cálculo.</div>';
                $active_tab = 'calculator';
            } elseif (empty($seiPagamento)) {
                $message = '<div class="alert danger"><i class="fas fa-exclamation-triangle"></i> Erro: O número do SEI de Pagamento é obrigatório para liquidar a rota.</div>';
                $active_tab = 'calculator';
            } else {
                $data = [
                    'calc_q_escritorio' => (float) $_POST['q_escritorio'],
                    'calc_u_escritorio' => (float) $_POST['u_escritorio'],
                    'calc_q_km_fix' => (float) $_POST['q_km_fix'],
                    'calc_u_km_fix' => (float) $_POST['u_km_fix'],
                    'calc_q_alim' => (float) $_POST['q_alim'],
                    'calc_u_alim' => (float) $_POST['u_alim'],
                    'calc_q_km_var' => (float) $_POST['q_km_var'],
                    'calc_u_km_var' => (float) $_POST['u_km_var'],
                    'calc_q_obras' => (float) $_POST['q_obras'],
                    'calc_u_obras' => (float) $_POST['u_obras'],
                    'calc_total_fixed' => (float) $_POST['total_fixed'],
                    'calc_total_variable' => (float) $_POST['total_variable'],
                    'calc_grand_total' => (float) $_POST['grand_total'],
                    'calc_gas_price' => (float) $_POST['gas_price'],
                    'wizard_step' => 6, // Move diretamente para Liquidado (Passo 6)
                    'sei_pagamento' => $_POST['sei_pagamento'] ?? ''
                ];

                $sql = "UPDATE routes SET 
                        calc_q_escritorio = :calc_q_escritorio,
                        calc_u_escritorio = :calc_u_escritorio,
                        calc_q_km_fix = :calc_q_km_fix,
                        calc_u_km_fix = :calc_u_km_fix,
                        calc_q_alim = :calc_q_alim,
                        calc_u_alim = :calc_u_alim,
                        calc_q_km_var = :calc_q_km_var,
                        calc_u_km_var = :calc_u_km_var,
                        calc_q_obras = :calc_q_obras,
                        calc_u_obras = :calc_u_obras,
                        calc_total_fixed = :calc_total_fixed,
                        calc_total_variable = :calc_total_variable,
                        calc_grand_total = :calc_grand_total,
                        calc_gas_price = :calc_gas_price,
                        wizard_step = :wizard_step,
                        sei_pagamento = :sei_pagamento,
                        status = 'completed',
                        completed_at = IFNULL(completed_at, NOW())
                        WHERE id = :id";
                
                try {
                    $stmt = $pdo->prepare($sql);
                    $data['id'] = $routeId;
                    if ($stmt->execute($data)) {
                        $message = '<div class="alert success"><i class="fas fa-check"></i> Cálculo salvo e Pagamento Liquidado com sucesso! Rota movida para o histórico de pagamentos.</div>';
                        echo "<script>alert('Pagamento Liquidado com sucesso!');</script>";
                    }
                } catch (PDOException $e) {
                    $message = '<div class="alert danger">Erro ao salvar cálculo: ' . $e->getMessage() . '</div>';
                }
                $active_tab = 'paid';
            }
        } elseif ($action === 'toggle_active') {
            $userId = (int) $_POST['user_id'];
            $new_status = (int) $_POST['is_active'];
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            if ($stmt->execute([$new_status, $userId])) {
                $status_msg = $new_status ? 'ativado' : 'desativado';
                $message = '<div class="alert success"><i class="fas fa-power-off"></i> Recenseador ' . $status_msg . ' com sucesso!</div>';
            }
            $active_tab = 'users';
        }
    }
}

// Fetch Data
$macro_mapping = [
    'Macrorregião 1' => ['Sobradinho (RA V)', 'Sobradinho II (RA XXVI)', 'Planaltina (RA VI)', 'Fercal (RA XXXI)', 'Arapoanga (RA XXXIV)'],
    'Macrorregião 2' => ['Lago Norte (RA XVIII)', 'Varjão (RA XXIII)', 'Paranoá (RA VII)', 'Itapoã (RA XXVIII)'],
    'Macrorregião 3' => ['Lago Sul (RA XVI)', 'Jardim Botânico (RA XXVII)', 'São Sebastião (RA XIV)'],
    'Macrorregião 4' => ['Plano Piloto (RA I)', 'Cruzeiro (RA XI)', 'Sudoeste/Octogonal (RA XXII)', 'SIA (RA XXIX)', 'SCIA (RA XXV)', 'Noroeste (RA XXXVI)'],
    'Macrorregião 5' => ['Gama (RA II)', 'Santa Maria (RA XIII)', 'Água Quente (RA XXXV)'],
    'Macrorregião 6' => ['Riacho Fundo (RA XVII)', 'Riacho Fundo II (RA XXI)', 'Park Way (RA XXIV)', 'Candangolândia (RA XIX)', 'Núcleo Bandeirante (RA VIII)', 'Recanto das Emas (RA XV)'],
    'Macrorregião 7' => ['Ceilândia (RA IX)', 'Sol Nascente/Pôr do Sol (RA XXXII)', 'Taguatinga (RA III)', 'Samambaia (RA XII)', 'Brazlândia (RA IV)'],
    'Macrorregião 8' => ['Guará (RA X)', 'Águas Claras (RA XX)', 'Vicente Pires (RA XXX)', 'Arniqueiras (RA XXXIII)']
];

$pending_users = $pdo->query("SELECT * FROM users WHERE status = 'pending' AND role = 'recenseador'")->fetchAll();
$approved_users = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM routes r WHERE r.user_id = u.id AND r.status NOT IN ('cancelled', 'rejected')) as route_count FROM users u WHERE u.status = 'approved' AND u.role = 'recenseador' ORDER BY u.approved_at ASC")->fetchAll();
$registered_users = $pdo->query("SELECT * FROM users WHERE status IN ('approved', 'rejected') AND role = 'recenseador' ORDER BY status ASC, name ASC")->fetchAll();
$admin_users = $pdo->query("SELECT * FROM users WHERE role = 'admin' ORDER BY name ASC")->fetchAll();

// Wizard Data (Per Route) - Apenas o que NÃO foi liquidado (Passo < 5)
$wizard_routes = $pdo->query("SELECT r.*, u.name as user_name, u.cpf as user_cpf, u.processo_sei as user_sei 
    FROM routes r 
    JOIN users u ON r.user_id = u.id 
    WHERE u.status = 'approved' AND r.status NOT IN ('cancelled', 'rejected') AND (r.wizard_step < 6 OR r.wizard_step IS NULL)
    ORDER BY r.created_at DESC")->fetchAll();

// Paid Routes (Step 6)
$paid_routes = $pdo->query("SELECT r.*, u.name as user_name, u.cpf as user_cpf FROM routes r JOIN users u ON r.user_id = u.id WHERE r.wizard_step = 6 ORDER BY r.completed_at DESC")->fetchAll();

// Active routes include 'assigned', 'accepted', 'in_progress' and 'delayed'
$active_routes = $pdo->query("SELECT r.*, u.name as user_name, u.microregion as user_macroregion FROM routes r JOIN users u ON r.user_id = u.id WHERE r.status IN ('assigned', 'accepted', 'in_progress', 'delayed') ORDER BY r.created_at DESC")->fetchAll();

// Completed routes - Todas as que foram entregues mas AINDA NÃO foram pagas (Passo < 6)
$completed_routes = $pdo->query("SELECT r.*, u.name as user_name FROM routes r JOIN users u ON r.user_id = u.id WHERE r.status = 'completed' AND (r.wizard_step < 6 OR r.wizard_step IS NULL) ORDER BY r.completed_at DESC")->fetchAll();

// Rotas para a Calculadora: wizard_step >= 3, qualquer status (incluindo pagas para consulta)
$calc_routes = $pdo->query("SELECT r.*, u.name as user_name, u.processo_sei FROM routes r JOIN users u ON r.user_id = u.id WHERE r.wizard_step >= 4 ORDER BY r.completed_at DESC, r.created_at DESC")->fetchAll();

// Cancelled routes
$cancelled_routes = $pdo->query("SELECT r.*, u.name as user_name FROM routes r JOIN users u ON r.user_id = u.id WHERE r.status = 'cancelled' ORDER BY r.updated_at DESC")->fetchAll();

// Rejected routes
$rejected_routes = $pdo->query("SELECT r.*, u.name as user_name FROM routes r JOIN users u ON r.user_id = u.id WHERE r.status = 'rejected' ORDER BY r.created_at DESC")->fetchAll();

// Report Data
$report_sql = "
    SELECT u.id, u.name, u.email, u.processo_sei, u.contrato,
        COUNT(r.id) as total_routes,
        SUM(CASE WHEN r.status = 'assigned' THEN 1 ELSE 0 END) as assigned_routes,
        SUM(CASE WHEN r.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_routes,
        SUM(CASE WHEN r.status = 'delayed' THEN 1 ELSE 0 END) as delayed_routes,
        SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed_routes,
        SUM(CASE WHEN r.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_routes
    FROM users u
    LEFT JOIN routes r ON u.id = r.user_id
    WHERE u.role = 'recenseador' AND u.status = 'approved'
    GROUP BY u.id, u.name, u.email, u.processo_sei, u.contrato
    ORDER BY u.name ASC
";
$report_data = $pdo->query($report_sql)->fetchAll();

// Macroregion Report Data
$micro_routes_count = $pdo->query("SELECT microregion, COUNT(*) as total FROM routes WHERE status NOT IN ('cancelled', 'rejected') GROUP BY microregion")->fetchAll();
$macro_report = [];
foreach ($macro_mapping as $macro => $ras) {
    $macro_report[$macro] = 0;
    foreach ($micro_routes_count as $row) {
        if (in_array(trim($row['microregion']), $ras)) {
            $macro_report[$macro] += $row['total'];
        }
    }
}
$colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69', '#2c3e50'];
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - CAU/DF</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">

    <script>
        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
            const targetTab = document.getElementById(tabId);
            if(targetTab) {
                targetTab.style.setProperty('display', 'block', 'important');
            }
            document.querySelectorAll('.sidebar-link').forEach(el => el.classList.remove('active'));
            const activeLink = document.getElementById('link-' + tabId);
            if(activeLink) activeLink.classList.add('active');
            window.location.hash = tabId;
            
            // Limpa alertas ao trocar de aba (opcional, para não "perseguir" o usuário)
            document.querySelectorAll('.alert').forEach(el => el.style.display = 'none');
            
            if (tabId === 'calculator' && typeof updateCalcRouteSelect === 'function') {
                updateCalcRouteSelect();
            }
        }

        function filterCompleted(macroName) {
            const allTables = document.querySelectorAll('.completed-macro-table');
            const allCards = document.querySelectorAll('.completed-macro-card');
            
            allTables.forEach(t => t.style.display = 'none');
            allCards.forEach(c => c.style.border = '1px solid #e0e0e0');

            if (macroName) {
                const safeId = macroName.replace(/[^a-z0-9]/gi, '');
                document.getElementById('table-' + safeId).style.display = 'block';
                document.getElementById('card-' + safeId).style.border = '2px solid var(--primary-teal)';
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            let hash = window.location.hash.substring(1);
            let defaultTab = '<?php echo $active_tab; ?>';
            if (hash && document.getElementById(hash)) { showTab(hash); } else { showTab(defaultTab); }

            // Auto-ocultar alertas após 5 segundos
            setTimeout(function() {
                document.querySelectorAll('.alert').forEach(el => {
                    el.style.transition = 'opacity 0.5s ease';
                    el.style.opacity = '0';
                    setTimeout(() => el.style.display = 'none', 500);
                });
            }, 5000);
        });

        function buscaCep() {
            let cep = document.getElementById('cep').value.replace(/\D/g, '');
            if (cep != "") {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        if (!("erro" in data)) {
                            document.getElementById('street').value = data.logradouro;
                            document.getElementById('neighborhood').value = data.bairro;
                            document.getElementById('city').value = data.localidade;
                            document.getElementById('state').value = data.uf;
                            document.getElementById('number').focus();
                        }
                    });
            }
        }

        let originalOptgroups = null;

        function updateMicroregion(userSelect) {
            const microSelect = document.getElementById('route_microregion');

            // Save original options on first run to memory
            if (!originalOptgroups) {
                originalOptgroups = [];
                const groups = microSelect.getElementsByTagName('optgroup');
                for (let i = 0; i < groups.length; i++) {
                    originalOptgroups.push(groups[i].cloneNode(true));
                }
            }

            const selectedOption = userSelect.options[userSelect.selectedIndex];
            const userMacro = selectedOption.getAttribute('data-microregion'); // e.g. "Macrorregião 1"

            // Clear all existing optgroups from the select
            const existingGroups = microSelect.getElementsByTagName('optgroup');
            while (existingGroups.length > 0) {
                existingGroups[0].parentNode.removeChild(existingGroups[0]);
            }

            if (userMacro && userMacro.trim() !== '') {
                let matchFound = false;

                for (let i = 0; i < originalOptgroups.length; i++) {
                    const group = originalOptgroups[i];
                    const groupTitle = group.label; // e.g. "Macrorregião 1"
                    let shouldKeep = false;

                    // Condition 1: userMacro matches the Macrorregião label (from new registration)
                    if (userMacro.includes(groupTitle) || groupTitle.includes(userMacro)) {
                        shouldKeep = true;
                    } else {
                        // Condition 2: userMacro is a specific city/RA (from old registration)
                        const options = group.getElementsByTagName('option');
                        for (let j = 0; j < options.length; j++) {
                            if (options[j].value === userMacro) {
                                shouldKeep = true;
                                break;
                            }
                        }
                    }

                    if (shouldKeep) {
                        microSelect.appendChild(group.cloneNode(true));
                        matchFound = true;
                    }
                }

                // Fallback to all if no exact match or glitch
                if (!matchFound) {
                    for (let i = 0; i < originalOptgroups.length; i++) {
                        microSelect.appendChild(originalOptgroups[i].cloneNode(true));
                    }
                }
            } else {
                for (let i = 0; i < originalOptgroups.length; i++) {
                    microSelect.appendChild(originalOptgroups[i].cloneNode(true));
                }
            }

            microSelect.selectedIndex = 0;
        }

        // Auto-refresh Pending Users every 5 seconds
        setInterval(function () {
            fetch('fetch_dashboard_data.php?t=' + new Date().getTime())
                .then(response => response.json())
                .then(data => {
                    // Update pending list HTML
                    const pendingList = document.getElementById('pending-list');
                    if (pendingList) {
                        pendingList.innerHTML = data.html;
                    }

                    // Update sidebar counter
                    const counter = document.getElementById('pending-counter');
                    if (counter) {
                        if (data.count > 0) {
                            counter.style.display = 'inline-block';
                            counter.innerText = data.count;
                        } else {
                            counter.style.display = 'none';
                        }
                    }
                })
                .catch(error => console.error('Error fetching updates:', data));
        }, 5000);
    </script>
    
    <style>
        /* Animation for tabs */
        .tab-content { display: none; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        /* Section Specifics */
        .section-header { margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem; }
        .section-header h2 { color: var(--primary-teal); font-size: 1.5rem; margin: 0; }

        /* Alerts & Badges */
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: var(--radius-sm); border: 1px solid transparent; }
        .alert.success { background: var(--success-light); color: var(--success); border-color: var(--success); }
        .alert.danger { background: var(--danger-light); color: var(--danger); border-color: var(--danger); }
        .alert.warning { background: var(--warning-light); color: var(--warning); border-color: var(--warning); }
        
        /* Custom File Input Styling */
        input[type="file"]::file-selector-button {
            background: var(--primary-teal);
            color: white;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 5px;
            transition: all 0.2s;
        }
        input[type="file"]::file-selector-button:hover {
            background: #00897b;
        }
        input[type="file"] {
            font-family: 'Inter', sans-serif;
            font-size: 0.75rem;
            color: #666;
        }

        /* Validation feedback for required fields */
        input:required:invalid {
            border-color: #ef4444 !important;
            background-color: #fffafb !important;
        }
        
        input:required:invalid:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
        }

        /* Toggle Switch */
        .switch-toggle {
            position: relative;
            display: inline-block;
            width: 38px;
            height: 20px;
        }
        .switch-toggle input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc;
            transition: .4s;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 14px;
            width: 14px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
        }
        input:checked + .slider { background-color: var(--primary-teal); }
        input:focus + .slider { box-shadow: 0 0 1px var(--primary-teal); }
        input:checked + .slider:before { transform: translateX(18px); }
        .slider.round { border-radius: 34px; }
        .slider.round:before { border-radius: 50%; }
    </style>
</head>

<body>
    <?php include '../../includes/header.php'; ?>

    <div class="admin-layout">
        <aside class="sidebar">
            <div
                style="padding: 0 2rem 1.5rem; font-size: 0.85rem; color: #999; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">
                Menu Principal
            </div>

            <a href="#" class="sidebar-link active" id="link-monitor" onclick="showTab('monitor'); return false;">
                <i class="fas fa-chart-line"></i>
                <span class="link-label">Monitoramento</span>
                <span class="counter"><?php echo count($active_routes); ?></span>
            </a>
            <a href="#" class="sidebar-link" id="link-wizard" onclick="showTab('wizard'); return false;">
                <i class="fas fa-tasks"></i>
                <span class="link-label">Wizard de Andamento</span>
            </a>
            <a href="#" class="sidebar-link" id="link-approvals" onclick="showTab('approvals'); return false;">
                <i class="fas fa-user-check"></i>
                <span class="link-label">Aprovações</span>
                <span id="pending-counter" class="counter" style="background: #f0ad4e; color: white; display: <?php echo (count($pending_users) > 0) ? 'inline-block' : 'none'; ?>;"><?php echo count($pending_users); ?></span>
            </a>
            <a href="#" class="sidebar-link" id="link-routes" onclick="showTab('routes'); return false;">
                <i class="fas fa-map-marked-alt"></i>
                <span class="link-label">Atribuir Rota</span>
            </a>
            <a href="#" class="sidebar-link" id="link-users" onclick="showTab('users'); return false;">
                <i class="fas fa-users"></i>
                <span class="link-label">Recenseadores</span>
            </a>
            <a href="#" class="sidebar-link" id="link-completed" onclick="showTab('completed'); return false;">
                <i class="fas fa-check-double"></i>
                <span class="link-label">Tarefas Concluídas</span>
                <span class="counter" style="background: #5bc0de; color: white;"><?php echo count($completed_routes); ?></span>
            </a>
            <a href="#" class="sidebar-link" id="link-cancelled" onclick="showTab('cancelled'); return false;">
                <i class="fas fa-ban"></i>
                <span class="link-label">Tarefas Canceladas</span>
                <?php if (count($cancelled_routes) > 0): ?>
                    <span class="counter" style="background: #6c757d; color: white;"><?php echo count($cancelled_routes); ?></span>
                <?php endif; ?>
            </a>
            <a href="#" class="sidebar-link" id="link-report" onclick="showTab('report'); return false;">
                <i class="fas fa-file-contract"></i>
                <span class="link-label">Relatório Geral</span>
            </a>
            <a href="#" class="sidebar-link" id="link-micro-report" onclick="showTab('micro-report'); return false;">
                <i class="fas fa-chart-pie"></i>
                <span class="link-label">Relatório Microrregião</span>
            </a>
            <a href="#" class="sidebar-link" id="link-paid" onclick="showTab('paid'); return false;">
                <i class="fas fa-hand-holding-usd"></i>
                <span class="link-label">Pagamentos Liquidados</span>
                <span class="counter" style="background: #28a745; color: white;"><?php echo count($paid_routes); ?></span>
            </a>
            <a href="#" class="sidebar-link" id="link-rejected" onclick="showTab('rejected'); return false;">
                <i class="fas fa-user-times"></i>
                <span class="link-label">Rotas Rejeitadas</span>
                <?php if (count($rejected_routes) > 0): ?>
                    <span class="counter" style="background: #dc3545; color: white;"><?php echo count($rejected_routes); ?></span>
                <?php endif; ?>
            </a>
            <a href="#" class="sidebar-link" id="link-task-calculator" onclick="showTab('task_calculator'); return false;">
                <i class="fas fa-clipboard-check"></i>
                <span class="link-label">Calculadora de Tarefas</span>
            </a>
            <a href="#" class="sidebar-link" id="link-admins" onclick="showTab('admins'); return false;">
                <i class="fas fa-user-shield"></i>
                <span class="link-label">Gestão de Admins</span>
            </a>
        </aside>

        <main class="main-content">
            <?php if (!empty($message))
                echo $message; ?>

            <!-- SEÇÃO: CALCULADORA DE TAREFAS (NOVA) -->
            <div id="task_calculator" class="tab-content" style="display: none;">
                <div class="section-header">
                    <h2><i class="fas fa-clipboard-check"></i> Calculadora de Tarefas</h2>
                    <p class="text-muted">Valores e Composição da Remuneração de Registro de Demanda</p>
                    <p style="font-size: 0.9rem; color: #555; margin-top: 0.5rem;">O CAUDF, representado pela GERFISC - Gerência de Fiscalização, formaliza a distribuição da demanda para pagamento.</p>
                </div>

                <div class="card" id="task_calc_card" style="padding: 2rem; max-width: 900px; margin: 0 auto; border: 1px solid #e0e0e0; box-shadow: 0 4px 15px rgba(0,0,0,0.05); background: white;">
                    
                    <!-- CABEÇALHO DE IMPRESSÃO -->
                    <div class="print-header" style="display: none; text-align: center; margin-bottom: 2rem;">
                        <h3 style="font-size: 1.1rem; margin: 0 0 10px 0; font-weight: 700; color: #000; font-family: 'Inter', sans-serif;">Conselho de Arquitetura e Urbanismo do Distrito Federal</h3>
                        <h2 style="font-size: 1.4rem; margin: 0; text-transform: uppercase; font-weight: 800; color: #000; padding: 1rem 0;">MEMÓRIA DE CÁLCULO - REMUNERAÇÃO</h2>
                        
                        <div style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: flex-start; text-align: left; font-size: 1rem; color: #000;">
                            <div style="flex: 1.5;">
                                <strong>Recenseador Associado:</strong> <br>
                                <span id="task_print_user_name" style="text-transform: uppercase; font-weight: 700; font-size: 1.1rem;"></span><br>
                                <span id="task_print_user_cpf" style="font-size: 1rem;"></span>
                            </div>
                            <div style="flex: 1; text-align: right;">
                                <strong>Processo SEI:</strong> <br>
                                <span id="task_print_sei" style="font-size: 1.1rem; font-weight: 700;"></span>
                            </div>
                        </div>

                        <div style="margin-top: 1.5rem; text-align: left; font-size: 0.95rem; background: #fff; padding: 1rem; border: 1px solid #ddd; border-radius: 4px; line-height: 1.6;">
                            <div><strong>Região de Atuação:</strong> <span id="task_print_user_microregion"></span></div>
                            <div style="margin-top: 0.3rem;"><strong>Rota Referente:</strong> <span id="task_print_route_title"></span></div>
                            
                            <div style="margin-top: 0.8rem; display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                <div><strong>Data de Atribuição:</strong> <span id="task_print_route_created"></span></div>
                                <div><strong>Data de Conclusão:</strong> <span id="task_print_route_completed"></span></div>
                            </div>
                            <div style="margin-top: 0.5rem;"><strong>Endereço de Fiscalização:</strong> <span id="task_print_route_location"></span></div>
                            <div style="margin-top: 0.5rem; word-break: break-all;"><strong>Link do Mapa:</strong> <span id="task_print_route_maps" style="font-size: 0.8rem; color: #555;"></span></div>
                            <div style="margin-top: 0.5rem;"><strong>Instruções/Descrição:</strong> <span id="task_print_route_desc"></span></div>
                        </div>
                        <div style="border-bottom: 2px solid #000; margin-top: 1.5rem; margin-bottom: 1.5rem;"></div>
                    </div>
                    
                    <?php 
                    $task_user_routes_json = [];
                    foreach ($calc_routes as $cr) {
                        $uid = $cr['user_id'];
                        if(!isset($task_user_routes_json[$uid])) $task_user_routes_json[$uid] = [];
                        $u_sei = '';
                        foreach($approved_users as $au) {
                            if($au['id'] == $uid) $u_sei = $au['processo_sei'] ?? 'Não informado';
                        }
                        $task_user_routes_json[$uid][] = [
                            'id' => $cr['id'],
                            'title' => $cr['title'],
                            'sei' => $u_sei,
                            'created_at' => date('d/m/Y H:i', strtotime($cr['created_at'])),
                            'completed_at' => date('d/m/Y H:i', strtotime($cr['completed_at'])),
                            'location' => htmlspecialchars($cr['start_location'] ?? ''),
                            'maps_url' => htmlspecialchars($cr['maps_url'] ?? 'Não fornecido'),
                            'description' => htmlspecialchars($cr['description'] ?? 'Sem instruções adicionais')
                        ];
                    }
                    $task_full_routes_calc = $pdo->query("SELECT * FROM routes WHERE wizard_step >= 4")->fetchAll();
                    $task_full_calc_json = [];
                    foreach($task_full_routes_calc as $fr) {
                        $task_full_calc_json[$fr['id']] = $fr;
                    }
                    ?>
                    <script>
                        const taskUserRoutesData = <?php echo json_encode($task_user_routes_json) ?: '{}'; ?>;
                        const taskFullCalcData = <?php echo json_encode($task_full_calc_json) ?: '{}'; ?>;
                    </script>

                    <form method="post" id="task_calculator_form">
                        <input type="hidden" name="action" value="save_calculation">
                        <input type="hidden" name="total_fixed" id="task_hidden_total_fixed">
                        <input type="hidden" name="total_variable" id="task_hidden_total_variable">
                        <input type="hidden" name="grand_total" id="task_hidden_grand_total">

                        <!-- SELEÇÃO NO-PRINT -->
                        <div class="no-print" style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 1rem; border: 1px solid #eee;">
                            <div style="display: flex; align-items: center; gap: 1.5rem;">
                                <div style="font-weight: 700; color: #444; text-transform: uppercase; font-size: 0.9rem; width: 220px;"><i class="fas fa-user-tag" style="color: var(--primary-teal); margin-right: 5px;"></i> Vincular Recenseador:</div>
                                <div style="flex-grow: 1;">
                                    <select id="task_calc_user" class="form-control" style="font-weight: 600; color: #333; border: 2px solid #ddd; width: 100%; padding: 0.5rem;" onchange="taskUpdateCalcRouteSelect()">
                                        <option value="">-- Selecione o recenseador --</option>
                                        <?php foreach ($approved_users as $u): 
                                            $macroDisplay = $u['microregion'] ?? 'N/A';
                                            $macroDisplay = preg_replace('/Macrorregi.*?o/i', 'Macrorregião', $macroDisplay);
                                            $macroDisplay = preg_replace('/Microrregi.*?o/i', 'Microrregião', $macroDisplay);
                                            if(stripos($macroDisplay, 'Macrorregi') !== false) {
                                                $parts = explode('(', $macroDisplay);
                                                $macroDisplay = trim($parts[0]);
                                            }
                                        ?>
                                            <option value="<?php echo $u['id']; ?>" data-sei="<?php echo htmlspecialchars($u['processo_sei'] ?? ''); ?>" data-microregion="<?php echo htmlspecialchars($macroDisplay); ?>"><?php echo htmlspecialchars($u['name']); ?> (CPF: <?php echo htmlspecialchars($u['cpf'] ?? 'N/A'); ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 1.5rem;">
                                <div style="font-weight: 700; color: #444; text-transform: uppercase; font-size: 0.9rem; width: 220px;"><i class="fas fa-map-marked-alt" style="color: var(--primary-teal); margin-right: 5px;"></i> Selecionar Rota:</div>
                                <div style="flex-grow: 1;">
                                    <select id="task_calc_route" name="route_id" class="form-control" style="font-weight: 600; color: #333; border: 2px solid #ddd; width: 100%; padding: 0.5rem;" onchange="taskLoadSavedCalculation()">
                                        <option value="">-- Escolha primeiro o recenseador --</option>
                                    </select>
                                </div>
                            </div>
                            <div id="task_sei_pagamento_container" style="display: none; align-items: center; gap: 1.5rem;">
                                <div style="font-weight: 700; color: #444; text-transform: uppercase; font-size: 0.9rem; width: 220px;"><i class="fas fa-file-invoice-dollar" style="color: var(--primary-teal); margin-right: 5px;"></i> SEI de Pagamento: <span style="color:red;">*</span></div>
                                <div style="flex-grow: 1;">
                                    <input type="text" name="sei_pagamento" id="task_calc_sei_pagamento" required class="form-control" placeholder="Informe o SEI para liquidação" style="font-weight: 600; color: #333; border: 2px solid #ddd; width: 100%; padding: 0.5rem;">
                                </div>
                            </div>
                        </div>

                        <!-- INPUT DE GASOLINA -->
                        <div class="no-print" style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; display: flex; align-items: center; gap: 1.5rem; border: 1px solid #eee;">
                            <div style="font-weight: 700; color: #444; text-transform: uppercase; font-size: 0.9rem;">Preço Médio Gasolina DF (ANP):</div>
                            <div style="position: relative; width: 160px;">
                                <span style="position: absolute; left: 12px; top: 10px; color: #666; font-weight: 600;">R$</span>
                                <input type="number" id="task_gas_price" name="gas_price" value="6.36" step="0.001" class="form-control" style="padding-left: 40px; font-weight: 800; color: var(--primary-teal); border: 2px solid #ddd; width: 100%;" oninput="taskUpdateFromGasoline()">
                            </div>
                            <div style="font-size: 0.85rem; color: #777;">
                                <i class="fas fa-info-circle"></i> Ajuste a gasolina para atualizar os valores de KM automaticamente.
                            </div>
                        </div>

                    <!-- TABELA CÁLCULO -->
                    <div style="text-align: center; margin-bottom: 1.5rem; font-weight: 800; font-size: 1.2rem; text-transform: uppercase; letter-spacing: 1px;">CÁLCULO REMUNERAÇÃO PREVISTA</div>
                    
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; background: white; border: 1px solid transparent;">
                        <thead>
                            <tr style="border-bottom: 1px solid #eee;">
                                <th style="padding: 1rem 0; text-align: left; width: 45%; color: #555; font-weight: 800; font-size: 1.1rem;">VALOR-FIXO</th>
                                <th style="padding: 1rem 0; text-align: center; color: #333; font-weight: 800;">Quant.</th>
                                <th style="padding: 1rem 0; text-align: center; color: #333; font-weight: 800;">Unitário</th>
                                <th style="padding: 1rem 0; text-align: right; color: #333; font-weight: 800;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding: 1.2rem 0; border-bottom: 1px solid #eee; color: #555;">Escritório-Modelo (un.)</td>
                                <td style="padding: 1.2rem 0; text-align: center; border-bottom: 1px solid #eee;">
                                    <input type="number" id="task_q_escritorio" name="q_escritorio" value="1" class="form-control" style="width: 60px; margin: 0 auto; text-align: center; font-weight: 800; padding: 0.2rem;" oninput="taskUpdateAllCalculations()">
                                </td>
                                <td style="padding: 1.2rem 0; text-align: center; border-bottom: 1px solid #eee;">
                                    <input type="number" id="task_u_escritorio" name="u_escritorio" value="102.02" step="0.01" class="form-control" style="width: 80px; margin: 0 auto; text-align: center; font-weight: 800; padding: 0.2rem;" oninput="taskUpdateAllCalculations()">
                                </td>
                                <td style="padding: 1.2rem 0; text-align: right; border-bottom: 1px solid #eee; color: #555;" id="task_t_escritorio">R$ 102,02</td>
                            </tr>
                            <tr>
                                <td style="padding: 1.2rem 0; border-bottom: 1px solid #eee; color: #555;">Auxílio-combustível (km)</td>
                                <td style="padding: 1.2rem 0; text-align: center; border-bottom: 1px solid #eee;">
                                    <input type="number" id="task_q_km_fix" name="q_km_fix" value="10" class="form-control" style="width: 60px; margin: 0 auto; text-align: center; font-weight: 800; padding: 0.2rem;" oninput="taskUpdateAllCalculations()">
                                </td>
                                <td style="padding: 1.2rem 0; text-align: center; border-bottom: 1px solid #eee;">
                                    <input type="number" id="task_u_km_fix" name="u_km_fix" value="2.03" step="0.01" class="form-control" style="width: 80px; margin: 0 auto; text-align: center; font-weight: 800; padding: 0.2rem;" oninput="taskUpdateAllCalculations()">
                                </td>
                                <td style="padding: 1.2rem 0; text-align: right; border-bottom: 1px solid #eee; color: #555;" id="task_t_km_fix">R$ 20,30</td>
                            </tr>
                            <tr>
                                <td style="padding: 1.2rem 0; border-bottom: 1px solid #eee; color: #555;">Auxílio-Alimentação (un.)</td>
                                <td style="padding: 1.2rem 0; text-align: center; border-bottom: 1px solid #eee;">
                                    <input type="number" id="task_q_alim" name="q_alim" value="2" class="form-control" style="width: 60px; margin: 0 auto; text-align: center; font-weight: 800; padding: 0.2rem;" oninput="taskUpdateAllCalculations()">
                                </td>
                                <td style="padding: 1.2rem 0; text-align: center; border-bottom: 1px solid #eee;">
                                    <input type="number" id="task_u_alim" name="u_alim" value="46.35" step="0.01" class="form-control" style="width: 80px; margin: 0 auto; text-align: center; font-weight: 800; padding: 0.2rem;" oninput="taskUpdateAllCalculations()">
                                </td>
                                <td style="padding: 1.2rem 0; text-align: right; border-bottom: 1px solid #eee; color: #555;" id="task_t_alim">R$ 92,70</td>
                            </tr>
                            <tr style="font-weight: 800; font-size: 1.1rem;">
                                <td colspan="3" style="padding: 1.5rem 0; text-align: left; color: #444;">TOTAL VALOR FIXO</td>
                                <td style="padding: 1.5rem 0; text-align: right; color: #444;" id="task_total_fixed_display">R$ 215,02</td>
                            </tr>
                        </tbody>
                    </table>

                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; background: white; border: 1px solid transparent;">
                        <thead>
                            <tr style="border-bottom: 1px solid #eee;">
                                <th style="padding: 1rem 0; text-align: left; width: 45%; color: #555; font-weight: 800; font-size: 1.1rem;">VALOR-VARIÁVEL***</th>
                                <th style="padding: 1rem 0; text-align: center; color: #333; font-weight: 800;">Quant.</th>
                                <th style="padding: 1rem 0; text-align: center; color: #333; font-weight: 800;">Unitário</th>
                                <th style="padding: 1rem 0; text-align: right; color: #333; font-weight: 800;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding: 1.2rem 0; border-bottom: 1px solid #eee; color: #555;">Auxílio combustível (km)</td>
                                <td style="padding: 1.2rem 0; text-align: center; border-bottom: 1px solid #eee;">
                                    <input type="number" id="task_q_km_var" name="q_km_var" value="30" class="form-control" style="width: 60px; margin: 0 auto; text-align: center; font-weight: 800; padding: 0.2rem;" oninput="taskUpdateAllCalculations()">
                                </td>
                                <td style="padding: 1.2rem 0; text-align: center; border-bottom: 1px solid #eee;">
                                    <input type="number" id="task_u_km_var" name="u_km_var" value="2.03" step="0.01" class="form-control" style="width: 80px; margin: 0 auto; text-align: center; font-weight: 800; padding: 0.2rem;" oninput="taskUpdateAllCalculations()">
                                </td>
                                <td style="padding: 1.2rem 0; text-align: right; border-bottom: 1px solid #eee; color: #555;" id="task_t_km_var">R$ 60,90</td>
                            </tr>
                            <tr>
                                <td style="padding: 1.2rem 0; border-bottom: 1px solid #eee; color: #555;">Quant. Obras (un.)</td>
                                <td style="padding: 1.2rem 0; text-align: center; border-bottom: 1px solid #eee;">
                                    <input type="number" id="task_q_obras" name="q_obras" value="1" class="form-control" style="width: 60px; margin: 0 auto; text-align: center; font-weight: 800; padding: 0.2rem;" oninput="taskUpdateAllCalculations()">
                                </td>
                                <td style="padding: 1.2rem 0; text-align: center; border-bottom: 1px solid #eee;">
                                    <input type="number" id="task_u_obras" name="u_obras" value="7.61" step="0.01" class="form-control" style="width: 80px; margin: 0 auto; text-align: center; font-weight: 800; padding: 0.2rem;" oninput="taskUpdateAllCalculations()">
                                </td>
                                <td style="padding: 1.2rem 0; text-align: right; border-bottom: 1px solid #eee; color: #555;" id="task_t_obras">R$ 7,61</td>
                            </tr>
                            <tr style="font-weight: 800; font-size: 1.1rem;">
                                <td colspan="3" style="padding: 1.5rem 0; text-align: left; color: #444;">TOTAL VALOR VARIÁVEL</td>
                                <td style="padding: 1.5rem 0; text-align: right; color: #444;" id="task_total_var_display">R$ 68,51</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- RESULTADO FINAL -->
                    <div style="border: 1px solid #000; padding: 1.5rem 2rem; display: flex; justify-content: space-between; align-items: center; background: white; margin-top: 1rem;">
                        <div style="font-weight: 800; font-size: 0.95rem; color: #444;">REMUNERAÇÃO = TOTAL VALOR FIXO + TOTAL VALOR VARIÁVEL</div>
                        <div style="font-size: 2.2rem; font-weight: 800; color: #3b8a7c;" id="task_grand_total_display">R$ 283,53</div>
                    </div>

                    <div class="no-print" style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                        <p style="font-size: 0.75rem; color: #777; margin: 0;">
                            *** variável a depender da demanda.<br>
                            ** As DEMANDAS são limitadas em no máximo 10 (dez) obras a serem visitadas.
                        </p>
                        
                        <div style="display: flex; gap: 1rem;">
                            <button type="button" onclick="taskPrintCalculatorReport()" class="btn btn-outline" style="padding: 0.8rem 1.5rem;">
                                <i class="fas fa-file-pdf"></i> Visualizar Relatório
                            </button>
                            
                            <button type="submit" class="btn btn-primary" style="padding: 0.8rem 1.5rem; background: #28a745; border-color: #28a745;">
                                <i class="fas fa-save"></i> Salvar e Liquidar Pagamento
                            </button>
                        </div>
                    </div>
                    </form>

                    <!-- ASSINATURAS (Visíveis apenas na Impressão) -->
                    <div class="print-footer" style="display: none; margin-top: 4rem; padding-top: 2rem;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                            <div style="width: 45%; text-align: center;">
                                <div style="border-bottom: 1px solid #000; margin-bottom: 5px;"></div>
                                <div style="font-weight: 700; text-transform: uppercase;" id="task_print_signature_name">NOME DO RECENSEADOR</div>
                                <div style="font-size: 0.85rem; color: #555;" id="task_print_signature_cpf"></div>
                                <div style="font-size: 0.85rem; color: #555;">Recenseador</div>
                            </div>
                            <div style="width: 45%; text-align: center;">
                                <div style="border-bottom: 1px solid #000; margin-bottom: 5px;"></div>
                                <div style="font-weight: 700; text-transform: uppercase;">Setor Financeiro / CAU-DF</div>
                                <div style="font-size: 0.85rem; color: #555;">Autorização de Pagamento</div>
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 2rem; font-size: 0.85rem; color: #777;">
                            Documento gerado em <?php date_default_timezone_set('America/Sao_Paulo'); echo date('d/m/Y \à\s H:i'); ?>
                        </div>
                    </div>

                    <style>
                        @media print {
                            body * { visibility: hidden; }
                            .sidebar, .topbar, .dashboard-header, .no-print { display: none !important; }
                            #task_calc_card, #task_calc_card * { visibility: visible; }
                            #task_calc_card { position: absolute; left: 0; top: 0; width: 100%; border: none !important; box-shadow: none !important; padding: 0 !important; }
                            #task_calc_card select, #task_calc_card input { border: none; background: transparent; -webkit-appearance: none; -moz-appearance: none; font-weight: 800; color: #000 !important; text-align: center; padding: 0; }
                            .print-header { display: block !important; }
                            .print-footer { display: block !important; }
                            .btn { display: none !important; }
                        }
                    </style>

                    <script>
                        function taskUpdateCalcRouteSelect() {
                            const userSelect = document.getElementById('task_calc_user');
                            const routeSelect = document.getElementById('task_calc_route');
                            const userId = userSelect.value;
                            
                            routeSelect.innerHTML = '';
                            
                            if (!userId) {
                                routeSelect.innerHTML = '<option value="">-- Escolha primeiro o recenseador --</option>';
                                return;
                            }
                            
                            const routes = taskUserRoutesData[userId] || [];
                            
                            if (routes.length === 0) {
                                routeSelect.innerHTML = '<option value="">-- Nenhuma rota pendente de pagamento --</option>';
                                return;
                            }
                            
                            routeSelect.innerHTML = '<option value="">-- Selecione a rota para pagamento --</option>';
                            routes.forEach(r => {
                                const opt = document.createElement('option');
                                opt.value = r.id;
                                opt.dataset.sei = r.sei;
                                opt.dataset.sei_pagamento = taskFullCalcData[r.id] ? (taskFullCalcData[r.id].sei_pagamento || '') : '';
                                opt.dataset.created_at = r.created_at;
                                opt.dataset.completed_at = r.completed_at;
                                opt.dataset.location = r.location;
                                opt.dataset.maps_url = r.maps_url;
                                opt.dataset.description = r.description;

                                let statusSuffix = "";
                                if (taskFullCalcData[r.id] && taskFullCalcData[r.id].wizard_step == 6) {
                                    statusSuffix = " ✅ (PAGO)";
                                    opt.style.color = "#28a745";
                                    opt.style.fontWeight = "bold";
                                }

                                opt.textContent = `Rota #${r.id} - ${r.title}${statusSuffix}`;
                                routeSelect.appendChild(opt);
                            });
                        }

                        function taskPrintCalculatorReport() {
                            const userSelect = document.getElementById('task_calc_user');
                            const routeSelect = document.getElementById('task_calc_route');
                            
                            if(userSelect && userSelect.value === '') {
                                alert('Por favor, selecione um recenseador para gerar o relatório nominal.');
                                return;
                            }
                            
                            if(routeSelect && routeSelect.value === '') {
                                alert('Por favor, selecione a rota associada a este cálculo.');
                                return;
                            }
                            
                            taskUpdateAllCalculations();

                            const selectedUserText = userSelect.options[userSelect.selectedIndex].text;
                            const selectedUserOption = userSelect.options[userSelect.selectedIndex];
                            const selectedRouteOption = routeSelect.options[routeSelect.selectedIndex];
                            const seiNumber = document.getElementById('task_calc_sei_pagamento').value || selectedRouteOption.dataset.sei || 'Não informado';
                            const microregion = selectedUserOption.dataset.microregion || 'Não informada';
                            
                            const cleanName = selectedUserText.split(' (CPF:')[0].trim();
                            const cpfMatch = selectedUserText.match(/CPF:\s*([^)]+)/);
                            const cpfText = cpfMatch ? 'CPF: ' + cpfMatch[1].trim() : '';
                            
                            document.getElementById('task_print_user_name').innerText = cleanName;
                            document.getElementById('task_print_user_cpf').innerText = cpfText;
                            document.getElementById('task_print_signature_name').innerText = cleanName;
                            document.getElementById('task_print_signature_cpf').innerText = cpfText;
                            document.getElementById('task_print_sei').innerText = seiNumber;
                            document.getElementById('task_print_user_microregion').innerText = microregion;
                            document.getElementById('task_print_route_title').innerText = selectedRouteOption.textContent;
                            
                            document.getElementById('task_print_route_created').innerText = selectedRouteOption.dataset.created_at || '';
                            document.getElementById('task_print_route_completed').innerText = selectedRouteOption.dataset.completed_at || '';
                            document.getElementById('task_print_route_location').innerText = selectedRouteOption.dataset.location || '';
                            document.getElementById('task_print_route_maps').innerText = selectedRouteOption.dataset.maps_url || '';
                            document.getElementById('task_print_route_desc').innerText = selectedRouteOption.dataset.description || '';
                            
                            window.print();
                        }

                        function taskLoadSavedCalculation() {
                            const routeSelect = document.getElementById('task_calc_route');
                            const routeId = routeSelect.value;
                            
                            const seiContainer = document.getElementById('task_sei_pagamento_container');
                            if (routeId) {
                                seiContainer.style.display = 'flex';
                                const selectedRouteOption = routeSelect.options[routeSelect.selectedIndex];
                                document.getElementById('task_calc_sei_pagamento').value = selectedRouteOption.dataset.sei_pagamento || '';
                            } else {
                                seiContainer.style.display = 'none';
                            }

                            if (!routeId || !taskFullCalcData[routeId]) {
                                document.getElementById('task_gas_price').value = "6.36";
                                document.getElementById('task_q_escritorio').value = 1;
                                document.getElementById('task_u_escritorio').value = 102.02;
                                document.getElementById('task_q_km_fix').value = 10;
                                document.getElementById('task_q_alim').value = 2;
                                document.getElementById('task_u_alim').value = 46.35;
                                document.getElementById('task_q_km_var').value = 30;
                                document.getElementById('task_q_obras').value = 1;
                                document.getElementById('task_u_obras').value = 7.61;
                                taskUpdateFromGasoline();
                                return;
                            }

                            const data = taskFullCalcData[routeId];
                            
                            if (data.calc_gas_price > 0) {
                                document.getElementById('task_gas_price').value = data.calc_gas_price;
                                document.getElementById('task_q_escritorio').value = data.calc_q_escritorio;
                                document.getElementById('task_u_escritorio').value = data.calc_u_escritorio;
                                document.getElementById('task_q_km_fix').value = data.calc_q_km_fix;
                                document.getElementById('task_u_km_fix').value = data.calc_u_km_fix;
                                document.getElementById('task_q_alim').value = data.calc_q_alim;
                                document.getElementById('task_u_alim').value = data.calc_u_alim;
                                document.getElementById('task_q_km_var').value = data.calc_q_km_var;
                                document.getElementById('task_u_km_var').value = data.calc_u_km_var;
                                document.getElementById('task_q_obras').value = data.calc_q_obras;
                                document.getElementById('task_u_obras').value = data.calc_u_obras;
                                document.getElementById('task_calc_sei_pagamento').value = data.sei_pagamento || '';
                                taskUpdateAllCalculations();
                            } else {
                                taskUpdateFromGasoline();
                            }
                        }

                        function taskUpdateFromGasoline() {
                            let gasVal = document.getElementById('task_gas_price').value;
                            if (typeof gasVal === 'string') {
                                gasVal = gasVal.replace(',', '.');
                            }
                            const gasPrice = parseFloat(gasVal) || 0;
                            const kmUnit = 1.39 + (gasPrice * 0.10);
                            const kmUnitRounded = Math.round(kmUnit * 100) / 100;

                            document.getElementById('task_u_km_fix').value = kmUnitRounded.toFixed(2);
                            document.getElementById('task_u_km_var').value = kmUnitRounded.toFixed(2);
                            
                            taskUpdateAllCalculations();
                        }

                        function taskUpdateAllCalculations() {
                            const format = (v) => 'R$ ' + v.toLocaleString('pt-BR', {minimumFractionDigits: 2});

                            const qEsc = parseFloat(document.getElementById('task_q_escritorio').value) || 0;
                            const uEsc = parseFloat(document.getElementById('task_u_escritorio').value) || 0;
                            const tEsc = qEsc * uEsc;
                            document.getElementById('task_t_escritorio').innerText = format(tEsc);

                            const qKmF = parseFloat(document.getElementById('task_q_km_fix').value) || 0;
                            const uKmF = parseFloat(document.getElementById('task_u_km_fix').value) || 0;
                            const tKmF = qKmF * uKmF;
                            document.getElementById('task_t_km_fix').innerText = format(tKmF);

                            const qAlim = parseFloat(document.getElementById('task_q_alim').value) || 0;
                            const uAlim = parseFloat(document.getElementById('task_u_alim').value) || 0;
                            const tAlim = qAlim * uAlim;
                            document.getElementById('task_t_alim').innerText = format(tAlim);

                            const totalFixed = tEsc + tKmF + tAlim;
                            document.getElementById('task_total_fixed_display').innerText = format(totalFixed);
                            document.getElementById('task_hidden_total_fixed').value = totalFixed.toFixed(2);

                            const qKmV = parseFloat(document.getElementById('task_q_km_var').value) || 0;
                            const uKmV = parseFloat(document.getElementById('task_u_km_var').value) || 0;
                            const tKmV = qKmV * uKmV;
                            document.getElementById('task_t_km_var').innerText = format(tKmV);

                            const qObras = parseFloat(document.getElementById('task_q_obras').value) || 0;
                            const uObras = parseFloat(document.getElementById('task_u_obras').value) || 0;
                            const tObras = qObras * uObras;
                            document.getElementById('task_t_obras').innerText = format(tObras);

                            const totalVar = tKmV + tObras;
                            document.getElementById('task_total_var_display').innerText = format(totalVar);
                            document.getElementById('task_hidden_total_variable').value = totalVar.toFixed(2);

                            const grandTotal = totalFixed + totalVar;
                            document.getElementById('task_grand_total_display').innerText = format(grandTotal);
                            document.getElementById('task_hidden_grand_total').value = grandTotal.toFixed(2);
                        }
                    </script>
                </div>
            </div>

            <!-- SECTION 1.5: WIZARD DE ANDAMENTO -->
            <div id="wizard" class="tab-content">
                <div class="section-header">
                    <h2><i class="fas fa-magic"></i> Wizard de Andamento</h2>
                    <p class="text-muted">Acompanhe o fluxo de cada recenseador, desde a documentação até o pagamento final.</p>
                </div>

                <div style="background: white; border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: #f8f9fa;">
                            <tr>
                                <th style="padding:1rem; text-align: left;">Recenseador</th>
                                <th style="padding:1rem; text-align: left;">Fluxo de Progresso</th>
                                <th style="padding:1rem; text-align: center;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($wizard_routes as $r): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding:1rem; width: 250px;">
                                        <div style="font-weight: 600; color: #333;">
                                            <?php echo htmlspecialchars($r['title']); ?>
                                        </div>
                                        <div style="font-size: 0.85rem; color: var(--primary-teal); margin: 0.2rem 0; font-weight: 600;">
                                            <i class="fas fa-user"></i> <?php echo mb_strtoupper(htmlspecialchars($r['user_name']), 'UTF-8'); ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: #666;">
                                            CPF: <?php echo htmlspecialchars($r['user_cpf'] ?? ''); ?>
                                        </div>
                                        <?php if (!empty($r['user_sei'])): ?>
                                            <div style="font-size: 0.75rem; color: #999; margin-top: 5px;">
                                                <strong>SEI (Cad):</strong> <?php echo htmlspecialchars($r['user_sei']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:1rem;">
                                        <div style="margin-bottom: 0.8rem;">
                                            <div class="wizard-stepper" style="display: flex; justify-content: space-between; position: relative; margin: 20px 0;">
                                            <div style="position: absolute; top: 15px; left: 0; right: 0; height: 4px; background: #e0e0e0; z-index: 1;"></div>
                                            <div style="position: absolute; top: 15px; left: 0; width: <?php echo (($r['wizard_step'] - 1) * 20); ?>%; height: 4px; background: var(--primary-teal); z-index: 2; transition: width 0.3s;"></div>
                                            
                                            <?php 
                                            $steps = [
                                                1 => ['label' => 'Documentação', 'icon' => 'fa-file-signature', 'color' => '#3498db'],
                                                2 => ['label' => 'Rota Disp.', 'icon' => 'fa-map-pin', 'color' => '#f39c12'],
                                                3 => ['label' => 'Contrato Assinado', 'icon' => 'fa-file-contract', 'color' => '#e67e22'],
                                                4 => ['label' => 'Conclusão', 'icon' => 'fa-check-circle', 'color' => '#27ae60'],
                                                5 => ['label' => 'Envio Pagto', 'icon' => 'fa-paper-plane', 'color' => '#8e44ad'],
                                                6 => ['label' => 'Liquidado', 'icon' => 'fa-hand-holding-usd', 'color' => '#f1c40f']
                                            ];
                                            
                                            foreach($steps as $sNum => $sInfo):
                                                $isPast = $r['wizard_step'] > $sNum;
                                                $isCurrent = $r['wizard_step'] == $sNum;
                                                $activeColor = $sInfo['color'];
                                                $color = ($isPast || $isCurrent) ? $activeColor : '#ccc';
                                                $bg = ($isPast || $isCurrent) ? $activeColor : '#fff';
                                                $border = ($isPast || $isCurrent) ? $activeColor : '#ccc';
                                                $iconColor = ($isPast || $isCurrent) ? '#fff' : '#ccc';
                                            ?>
                                            <div style="position: relative; z-index: 3; display: flex; flex-direction: column; align-items: center; width: 60px;">
                                                <div style="width: 34px; height: 34px; border-radius: 50%; background: <?php echo $bg; ?>; border: 2px solid <?php echo $border; ?>; display: flex; align-items: center; justify-content: center; margin-bottom: 5px; color: <?php echo $iconColor; ?>; font-size: 0.9rem;">
                                                    <i class="fas <?php echo $sInfo['icon']; ?>"></i>
                                                </div>
                                                <span style="font-size: 0.65rem; text-align: center; font-weight: <?php echo $isCurrent ? '700' : '400'; ?>; color: <?php echo $isCurrent ? 'var(--primary-teal)' : '#888'; ?>;">
                                                    <?php echo $sInfo['label']; ?>
                                                </span>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <?php if ($r['wizard_step'] >= 3): ?>
                                            <div style="background: #fdfdfd; padding: 0.8rem; border-radius: 4px; border-left: 3px solid #198754; font-size: 0.85rem; color: #555; margin-top: 10px;">
                                                <strong><i class="fas fa-comment-dots"></i> Relatório do Recenseador:</strong><br>
                                                <?php echo nl2br(htmlspecialchars($r['observation'] ?? 'Sem observações.')); ?>

                                                <?php
                                                $files = array_filter([$r['report_file_1'], $r['report_file_2'], $r['report_file_3']]);
                                                if (!empty($files)):
                                                ?>
                                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.5rem;">
                                                        <?php foreach ($files as $idx => $f): ?>
                                                            <a href="<?php echo htmlspecialchars($f); ?>" target="_blank" class="btn btn-outline" style="font-size: 0.7rem; padding: 0.2rem 0.4rem; border-color: #198754; color: #198754;">
                                                                <i class="fas fa-file-pdf"></i> Anexo <?php echo $idx + 1; ?>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:1rem; text-align: center;">
                                        <form method="post" style="display: inline-block;">
                                            <input type="hidden" name="action" value="update_wizard">
                                            <input type="hidden" name="route_id" value="<?php echo $r['id']; ?>">
                                            
                                            <select name="step" class="form-control" style="font-size: 0.8rem; padding: 0.3rem; margin-bottom: 5px; width: 150px;" onchange="if(this.value == '6') { document.getElementById('sei_field_<?php echo $r['id']; ?>').style.display='block'; } else { document.getElementById('sei_field_<?php echo $r['id']; ?>').style.display='none'; }">
                                                <option value="1" <?php echo $r['wizard_step'] == 1 ? 'selected' : ''; ?>>1. Doc. Aprovada</option>
                                                <option value="2" <?php echo $r['wizard_step'] == 2 ? 'selected' : ''; ?>>2. Rota Disponível</option>
                                                <option value="3" <?php echo $r['wizard_step'] == 3 ? 'selected' : ''; ?>>3. Contrato Assinado</option>
                                                <option value="4" <?php echo $r['wizard_step'] == 4 ? 'selected' : ''; ?>>4. Rota Concluída</option>
                                                <option value="5" <?php echo $r['wizard_step'] == 5 ? 'selected' : ''; ?>>5. Envio Pagamento</option>
                                                <option value="6" <?php echo $r['wizard_step'] == 6 ? 'selected' : ''; ?>>6. Pago (Liquidado)</option>
                                            </select>
                                            
                                            <div id="sei_field_<?php echo $r['id']; ?>" style="display: <?php echo $r['wizard_step'] == 6 ? 'block' : 'none'; ?>; margin-bottom: 5px;">
                                                <input type="text" name="sei_pagamento" value="<?php echo htmlspecialchars($r['sei_pagamento'] ?? ''); ?>" placeholder="SEI de Pagamento" class="form-control" style="font-size: 0.8rem; padding: 0.3rem; width: 150px;">
                                            </div>
                                            
                                            <button type="submit" class="btn btn-primary" style="font-size: 0.75rem; padding: 0.3rem 0.8rem; width: 100%;">
                                                Atualizar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECTION 1: MONITORAMENTO -->
            <div id="monitor" class="tab-content">
                <div class="section-header">
                    <h2><i class="fas fa-satellite-dish"></i> Monitoramento de Rotas</h2>
                    <p class="text-muted">Acompanhe em tempo real as coletas iniciadas.</p>
                </div>

                <?php if (count($active_routes) > 0): ?>
                    <div class="grid grid-2">
                        <?php foreach ($active_routes as $route): ?>
                            <div class="monitor-card <?php echo ($route['status'] == 'delayed') ? 'delayed' : ''; ?>">
                                <!-- Card Header with Status & Actions -->
                                <div class="card-header-actions">
                                    <div>
                                        <?php if ($route['status'] == 'delayed'): ?>
                                            <span style="font-size: 0.65rem; font-weight: 800; background: #fee2e2; color: #b91c1c; padding: 2px 8px; border-radius: 10px; border: 1px solid #fecaca;"><i class="fas fa-exclamation-triangle"></i> ATRASADA</span>
                                        <?php elseif ($route['status'] == 'assigned'): ?>
                                            <span style="font-size: 0.65rem; font-weight: 800; background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 10px; border: 1px solid #fde68a;"><i class="fas fa-clock"></i> AGUARDANDO</span>
                                        <?php else: ?>
                                            <span style="font-size: 0.65rem; font-weight: 800; background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 10px; border: 1px solid #bbf7d0;"><i class="fas fa-spinner fa-spin"></i> EM ANDAMENTO</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="edit_route.php?id=<?php echo $route['id']; ?>" class="action-btn-circle btn-edit" title="Editar Rota"><i class="fas fa-edit"></i></a>

                                        <?php if ($route['status'] == 'in_progress'): ?>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                                                <input type="hidden" name="action" value="mark_delayed">
                                                <button type="submit" class="action-btn-circle btn-reset" title="Marcar Atraso"><i class="fas fa-history"></i></button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="post" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja cancelar esta rota?');">
                                            <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                                            <input type="hidden" name="action" value="cancel_route">
                                            <button type="submit" class="action-btn-circle btn-cancel" title="Cancelar Rota"><i class="fas fa-ban"></i></button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Card Body -->
                                <div style="padding: 1.25rem;">
                                    <!-- Date Timeline Box -->
                                    <div style="background: #f8fafc; border-radius: 8px; padding: 0.75rem; margin-bottom: 1.25rem; border: 1px solid #f1f5f9; display: grid; gap: 0.4rem;">
                                        <div style="font-size: 0.75rem; color: #64748b; display: flex; align-items: center; gap: 8px;">
                                            <i class="fas fa-play-circle" style="color: #22c55e; width: 14px;"></i> 
                                            <span>Início: <strong><?php echo ($route['start_time']) ? date('d/m/Y H:i', strtotime($route['start_time'])) : 'N/A'; ?></strong></span>
                                        </div>
                                        <div style="font-size: 0.7rem; color: #94a3b8; display: flex; align-items: center; gap: 8px;">
                                            <i class="fas fa-calendar-alt" style="width: 14px;"></i> 
                                            <span>Atribuída em: <?php echo date('d/m/Y H:i', strtotime($route['created_at'])); ?></span>
                                        </div>
                                        <?php if (!empty($route['scheduled_end'])): ?>
                                            <div style="font-size: 0.75rem; color: #ef4444; display: flex; align-items: center; gap: 8px; font-weight: 700; margin-top: 2px;">
                                                <i class="fas fa-flag-checkered" style="width: 14px;"></i> 
                                                <span>Prazo Final: <span style="background: #fee2e2; padding: 1px 6px; border-radius: 4px;"><?php echo date('d/m/Y H:i', strtotime($route['scheduled_end'])); ?></span></span>
                                            </div>
                                            <?php if ($route['status'] === 'in_progress'): ?>
                                                <div class="countdown-container" data-deadline="<?php echo $route['scheduled_end']; ?>" style="border-radius: 6px; padding: 0.6rem 0.8rem; margin-top: 0.5rem; display: flex; justify-content: center; align-items: center; gap: 6px; background: #f0f9ff; border: 1px solid #bae6fd; color: #0284c7; font-size: 0.85rem; font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.02); width: 100%;">
                                                    <i class="fas fa-hourglass-half" style="color: #0284c7; font-size: 0.8rem;"></i>
                                                    <div class="countdown-timer">00:00:00</div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Main Content -->
                                    <h3 style="margin: 0 0 0.5rem; font-size: 1rem; font-weight: 800; color: #1e293b; line-height: 1.4;">
                                        <?php echo htmlspecialchars($route['title']); ?>
                                    </h3>
                                    
                                    <div style="margin-bottom: 0.75rem;">
                                        <!-- Nome do Recenseador em destaque -->
                                        <p style="margin:0 0 4px; color: #1e40af; font-size: 0.95rem; font-weight: 800; display: flex; align-items: center; gap: 6px;">
                                            <i class="fas fa-user-circle" style="color: #3b82f6; font-size: 1rem;"></i>
                                            <?php echo mb_strtoupper(htmlspecialchars($route['user_name']), 'UTF-8'); ?>
                                        </p>
                                        <!-- Macrorregião do usuário -->
                                        <?php 
                                            $macroDisp = $route['user_macroregion'] ?? '';
                                            $macroDisp = preg_replace('/Macrorregi.*?o/i', 'Macrorregião', $macroDisp);
                                        ?>
                                        <?php if (!empty($macroDisp)): ?>
                                            <span style="display: inline-block; margin-bottom: 4px; background: #eff6ff; color: #1d4ed8; font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 10px; border: 1px solid #bfdbfe;">
                                                <i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($macroDisp); ?>
                                            </span>
                                        <?php endif; ?>
                                        <!-- Cidade/RA da rota -->
                                        <?php if (!empty($route['microregion'])): ?>
                                            <p style="margin: 2px 0 0; color: #059669; font-weight: 700; font-size: 0.8rem;">
                                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($route['microregion']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>

                                    <div style="font-size: 0.8rem; color: #64748b; background: #fdfdfd; padding: 0.5rem 0; border-top: 1px dashed #e2e8f0;">
                                        <i class="fas fa-map-signs" style="color: #cbd5e1;"></i> <?php echo htmlspecialchars($route['start_location']); ?>
                                    </div>
                                </div>

                                <!-- Card Footer: Actions -->
                                <div style="padding: 1rem; border-top: 1px solid #f1f5f9; background: #fff;">
                                    <?php if ($route['status'] !== 'assigned'): ?>
                                        <a href="../recenseador/generate_contract.php?route_id=<?php echo $route['id']; ?>" target="_blank" class="btn" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 0.8rem; color: white; background: #28a745; border-color: #28a745; padding: 0.6rem; margin-bottom: 0.5rem;" title="Baixar Termo de Registro de Demanda assinado pelo recenseador">
                                            <i class="fas fa-file-signature"></i> TERMO DE ACEITE (PDF)
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($route['maps_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($route['maps_url']); ?>" target="_blank" class="btn btn-outline" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 0.8rem; color: #2563eb; border-color: #dbeafe; background: #fdfdfd; padding: 0.6rem;">
                                            <i class="fab fa-google"></i> ABRIR NO GOOGLE MAPS
                                        </a>
                                    <?php else: ?>
                                        <div style="font-size: 0.75rem; color: #cbd5e1; text-align: center; font-style: italic;">
                                            Mapa indisponível
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5" style="border: 2px dashed #eee; border-radius: 8px;">
                        <i class="fas fa-check-circle" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                        <p class="text-muted">Nenhuma rota ativa ou atrasada no momento.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SECTION 2: APROVAÇÕES PENDENTES -->
            <div id="approvals" class="tab-content">
                <div class="section-header">
                    <h2><i class="fas fa-user-clock"></i> Aprovações Pendentes</h2>
                </div>
                <div id="pending-list">
                    <?php if (count($pending_users) > 0): ?>
                        <?php foreach ($pending_users as $user): ?>
                            <div class="pending-card">
                                <div>
                                    <h4 style="margin: 0; color: #1e293b; font-weight: 800;">
                                        <?php echo mb_strtoupper(htmlspecialchars($user['name']), 'UTF-8'); ?>
                                    </h4>
                                    <p style="margin: 0.2rem 0; color: var(--text-muted); font-size: 0.9rem;">
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                                    </p>
                                </div>
                                <div class="pending-actions">
                                    <a href="view_user.php?user_id=<?php echo $user['id']; ?>" target="_blank"
                                        class="btn btn-outline" style="height: 40px; padding: 0 1rem; font-size: 0.8rem;">
                                        <i class="fas fa-search"></i> ANALISAR PERFIL / DOCS
                                    </a>
                                    
                                    <form method="post" style="display:flex; align-items:center; gap: 0.5rem;" onsubmit="return confirm('Deseja aprovar este cadastro e documentos?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="text" name="processo_sei" placeholder="Processo SEI" required class="form-control" style="width: 140px;">
                                        <input type="text" name="contrato" placeholder="Nº Contrato" required class="form-control" style="width: 140px;">
                                        <button type="submit" class="btn-approve">
                                            <i class="fas fa-check"></i> APROVAR
                                        </button>
                                    </form>

                                    <form method="post" onsubmit="return confirm('Tem certeza que deseja reprovar este cadastro?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn-reject" title="Reprovar Cadastro">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted">Nenhum cadastro pendente.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SECTION 3: ATRIBUIR ROTA -->
            <div id="routes" class="tab-content">
                <div class="section-header">
                    <h2><i class="fas fa-map-marked-alt"></i> Atribuir Nova Rota</h2>
                </div>
                <div
                    style="background: white; padding: 2rem; border-radius: 8px; border: 1px solid #e0e0e0; max-width: 800px;">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="assign_route">
                        <div class="form-group mb-4"><label>Selecione o Recenseador</label><select name="user_id"
                                required class="form-control"
                                style="width: 100%; padding:0.8rem; border:1px solid #ccc;"
                                onchange="updateMicroregion(this)">
                                <option value="" data-microregion="">Selecione...</option>
                                <?php $count = 1; foreach ($approved_users as $u): 
                                    $macroDisplay = $u['microregion'] ?? 'N/A';
                                    
                                    // Fix encoding issues if present
                                    if (strpos($macroDisplay, '├ú') !== false) {
                                        $macroDisplay = str_replace('├ú', 'ã', $macroDisplay);
                                    }
                                    if (strpos($macroDisplay, 'Ã£') !== false) {
                                        $macroDisplay = str_replace('Ã£', 'ã', $macroDisplay);
                                    }

                                    if(stripos($macroDisplay, 'Macrorregi') !== false) {
                                        $parts = explode('(', $macroDisplay);
                                        $macroDisplay = trim($parts[0]);
                                    }
                                    $rCount = $u['route_count'] ?? 0;
                                    $color = ($rCount > 0) ? '#007bff' : '#dc3545';
                                    $dot = ($rCount > 0) ? '🔵' : '🔴';
                                ?>
                                    <option value="<?php echo $u['id']; ?>"
                                        data-microregion="<?php echo htmlspecialchars($u['microregion'] ?? ''); ?>"
                                        style="color: <?php echo $color; ?>;">
                                        <?php echo $count++; ?> - <?php echo mb_strtoupper(htmlspecialchars($u['name']), 'UTF-8') . ' (CPF: ' . htmlspecialchars($u['cpf'] ?? '') . ' - ' . htmlspecialchars($macroDisplay) . ' ' . $dot . ' [' . $rCount . ' rotas])'; ?>
                                    </option><?php endforeach; ?>
                            </select></div>
                        <div class="form-group mb-4"><label>Título</label><input type="text" name="route_title" required
                                class="form-control" style="width: 100%; padding:0.8rem; border:1px solid #ccc;"></div>

                        <div class="form-group mb-4">
                            <label>Macrorregião / Microrregião (RA)</label>
                            <select name="microregion" id="route_microregion" required class="form-control"
                                style="width: 100%; padding:0.8rem; border:1px solid #ccc; background-color: #f8f9fa;">
                                <option value="">Selecione a Região de Trabalho...</option>
                                <optgroup label="Macrorregião 1">
                                    <option value="Sobradinho (RA V)">Sobradinho (RA V)</option>
                                    <option value="Sobradinho II (RA XXVI)">Sobradinho II (RA XXVI)</option>
                                    <option value="Planaltina (RA VI)">Planaltina (RA VI)</option>
                                    <option value="Fercal (RA XXXI)">Fercal (RA XXXI)</option>
                                    <option value="Arapoanga (RA XXXIV)">Arapoanga (RA XXXIV)</option>
                                </optgroup>
                                <optgroup label="Macrorregião 2">
                                    <option value="Lago Norte (RA XVIII)">Lago Norte (RA XVIII)</option>
                                    <option value="Varjão (RA XXIII)">Varjão (RA XXIII)</option>
                                    <option value="Paranoá (RA VII)">Paranoá (RA VII)</option>
                                    <option value="Itapoã (RA XXVIII)">Itapoã (RA XXVIII)</option>
                                </optgroup>
                                <optgroup label="Macrorregião 3">
                                    <option value="Lago Sul (RA XVI)">Lago Sul (RA XVI)</option>
                                    <option value="Jardim Botânico (RA XXVII)">Jardim Botânico (RA XXVII)</option>
                                    <option value="São Sebastião (RA XIV)">São Sebastião (RA XIV)</option>
                                </optgroup>
                                <optgroup label="Macrorregião 4">
                                    <option value="Plano Piloto (RA I)">Plano Piloto (RA I)</option>
                                    <option value="Cruzeiro (RA XI)">Cruzeiro (RA XI)</option>
                                    <option value="Sudoeste/Octogonal (RA XXII)">Sudoeste/Octogonal (RA XXII)</option>
                                    <option value="SIA (RA XXIX)">SIA - Setor de Indústria e Abastecimento (RA XXIX)
                                    </option>
                                    <option value="SCIA (RA XXV)">SCIA - Estrutural (RA XXV)</option>
                                    <option value="Noroeste (RA XXXVI)">Noroeste (RA XXXVI)</option>
                                </optgroup>
                                <optgroup label="Macrorregião 5">
                                    <option value="Gama (RA II)">Gama (RA II)</option>
                                    <option value="Santa Maria (RA XIII)">Santa Maria (RA XIII)</option>
                                    <option value="Água Quente (RA XXXV)">Água Quente (RA XXXV)</option>
                                </optgroup>
                                <optgroup label="Macrorregião 6">
                                    <option value="Riacho Fundo (RA XVII)">Riacho Fundo (RA XVII)</option>
                                    <option value="Riacho Fundo II (RA XXI)">Riacho Fundo II (RA XXI)</option>
                                    <option value="Park Way (RA XXIV)">Park Way (RA XXIV)</option>
                                    <option value="Candangolândia (RA XIX)">Candangolândia (RA XIX)</option>
                                    <option value="Núcleo Bandeirante (RA VIII)">Núcleo Bandeirante (RA VIII)</option>
                                    <option value="Recanto das Emas (RA XV)">Recanto das Emas (RA XV)</option>
                                </optgroup>
                                <optgroup label="Macrorregião 7">
                                    <option value="Ceilândia (RA IX)">Ceilândia (RA IX)</option>
                                    <option value="Sol Nascente/Pôr do Sol (RA XXXII)">Sol Nascente/Pôr do Sol (RA
                                        XXXII)</option>
                                    <option value="Taguatinga (RA III)">Taguatinga (RA III)</option>
                                    <option value="Samambaia (RA XII)">Samambaia (RA XII)</option>
                                    <option value="Brazlândia (RA IV)">Brazlândia (RA IV)</option>
                                </optgroup>
                                <optgroup label="Macrorregião 8">
                                    <option value="Guará (RA X)">Guará (RA X)</option>
                                    <option value="Águas Claras (RA XX)">Águas Claras (RA XX)</option>
                                    <option value="Vicente Pires (RA XXX)">Vicente Pires (RA XXX)</option>
                                    <option value="Arniqueiras (RA XXXIII)">Arniqueiras (RA XXXIII)</option>
                                </optgroup>
                            </select>
                        </div>

                        <div style="display:grid; grid-template-columns: 150px 1fr; gap:1rem;"><input type="text"
                                name="address_cep" id="cep" placeholder="CEP" onblur="buscaCep()"
                                style="padding:0.8rem; border:1px solid #ccc;"><input type="text" name="address_street"
                                id="street" placeholder="Rua" style="padding:0.8rem; border:1px solid #ccc;"></div>
                        <br>
                        <div style="display:grid; grid-template-columns: 100px 1fr; gap:1rem; margin-bottom:1rem;">
                            <input type="text" name="address_number" id="number" placeholder="Número"
                                style="padding:0.8rem; border:1px solid #ccc;">
                            <input type="text" name="address_complement" placeholder="Complemento"
                                style="padding:0.8rem; border:1px solid #ccc;">
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:1rem; margin-bottom:1rem;">
                            <input type="text" name="address_neighborhood" id="neighborhood" placeholder="Bairro"
                                style="padding:0.8rem; border:1px solid #ccc;">
                            <input type="text" name="address_city" id="city" placeholder="Cidade"
                                style="padding:0.8rem; border:1px solid #ccc;">
                            <input type="text" name="address_state" id="state" placeholder="UF"
                                style="padding:0.8rem; border:1px solid #ccc;">
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1rem;">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-plus"></i> Data de Início</label>
                                <input type="datetime-local" name="scheduled_start" class="form-control" style="padding:0.8rem; border:1px solid #ccc;">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-calendar-check"></i> Data Final (Prazo)</label>
                                <input type="datetime-local" name="scheduled_end" class="form-control" style="padding:0.8rem; border:1px solid #ccc;">
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label><i class="fab fa-google"></i> Link do Google Maps (Local da Vistoria)</label>
                            <input type="url" name="maps_url" class="form-control" placeholder="https://www.google.com.br/maps/place/..." style="width: 100%; padding:0.8rem; border:1px solid #ccc;">
                        </div>
                        <div class="form-group mb-4"><label>Instruções / Descrição</label><textarea name="route_desc"
                                class="form-control" rows="3"
                                style="width: 100%; padding:0.8rem; border:1px solid #ccc;"></textarea></div>

                        <div class="form-group mb-4">
                            <label><i class="fas fa-paperclip"></i> Anexar Arquivos para o Recenseador (PDF ou Imagens)</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 10px;">
                                <?php for ($i = 1; $i <= 3; $i++): 
                                    $label = ($i === 1) ? "Anexo 1 (Print do Mapa)" : "Anexo $i";
                                ?>
                                    <div style="position: relative;">
                                        <label for="admin_file_<?php echo $i; ?>" class="btn btn-outline" style="width: 100%; font-size: 0.75rem; padding: 0.6rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; border: 1px dashed #ccc; background: #fafafa; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <i class="fas fa-paperclip" id="admin_icon_<?php echo $i; ?>"></i> <span id="admin_label_<?php echo $i; ?>"><?php echo $label; ?></span>
                                        </label>
                                        <input type="file" name="admin_file_<?php echo $i; ?>" id="admin_file_<?php echo $i; ?>" accept=".pdf,image/*" style="display: none;" onchange="if(this.files.length > 0) { document.getElementById('admin_label_<?php echo $i; ?>').innerText = this.files[0].name; const lbl = this.parentElement.querySelector('label'); lbl.style.borderStyle = 'solid'; lbl.style.borderColor = 'var(--primary-teal)'; lbl.style.color = 'var(--primary-teal)'; document.getElementById('admin_icon_<?php echo $i; ?>').className = 'fas fa-check-circle'; }">
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <small class="text-muted">Dica: O <strong>Anexo 1</strong> aparece automaticamente como imagem no Termo de Registro se for um print do mapa.</small>
                        </div>

                        <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2rem;" onclick="this.disabled=true; this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Gravando...'; this.form.submit();">Confirmar</button>
                    </form>
                </div>
            </div>

            <!-- SECTION 4: USERS LIST -->
            <div id="users" class="tab-content">
                <div class="section-header">
                    <h2><i class="fas fa-users"></i> Recenseadores</h2>
                </div>
                <div style="background: white; border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: #f8f9fa;">
                            <tr>
                                <th style="padding:1rem; text-align: left;">Nome</th>
                                <th style="padding:1rem; text-align: left;">E-mail</th>
                                <th style="padding:1rem; text-align: left;">Acesso</th>
                                <th style="padding:1rem; text-align: center;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registered_users as $u): ?>
                                <tr style="border-bottom: 1px solid #eee; <?php echo ($u['is_active'] == 0) ? 'background: #fdf2f2;' : ''; ?>">
                                    <td style="padding:1rem;">
                                        <div style="font-weight: 600; color: #333; display: flex; align-items: center; gap: 8px;">
                                            <?php if ($u['is_active'] == 0): ?>
                                                <i class="fas fa-user-slash" style="color: #dc3545;" title="Acesso Desativado"></i>
                                            <?php endif; ?>
                                            <?php echo mb_strtoupper(htmlspecialchars($u['name']), 'UTF-8'); ?>
                                        </div>
                                        <?php if (!empty($u['microregion'])): ?>
                                            <div style="font-size: 0.85rem; color: var(--primary-teal); margin-top: 0.2rem;">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?php echo htmlspecialchars($u['microregion']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div style="font-size: 0.75rem; color: #666; margin-top: 0.2rem;">
                                            <span style="margin-right: 10px;"><strong>SEI:</strong> <?php echo htmlspecialchars($u['processo_sei'] ?? 'N/A'); ?></span>
                                            <span><strong>CONTRATO:</strong> <?php echo htmlspecialchars($u['contrato'] ?? 'N/A'); ?></span>
                                        </div>
                                    </td>
                                    <td style="padding:1rem;"><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td style="padding:1rem;">
                                        <?php if ($u['status'] === 'approved'): ?>
                                            <span class="badge" style="background:#28a745;">Aprovado</span>
                                        <?php else: ?>
                                            <span class="badge" style="background:#dc3545;">Reprovado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:1rem;">
                                        <form method="post" style="display: flex; align-items: center; gap: 10px;">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $u['is_active'] ? 0 : 1; ?>">
                                            
                                            <label class="switch-toggle">
                                                <input type="checkbox" <?php echo $u['is_active'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                                                <span class="slider round"></span>
                                            </label>
                                            <span style="font-size: 0.75rem; font-weight: 700; color: <?php echo $u['is_active'] ? '#28a745' : '#dc3545'; ?>;">
                                                <?php echo $u['is_active'] ? 'ATIVO' : 'INATIVO'; ?>
                                            </span>
                                        </form>
                                    </td>
                                    <td style="padding:1rem; text-align: center;">
                                        <div style="display: flex; justify-content: center; gap: 0.5rem;">
                                            <a href="view_user.php?user_id=<?php echo $u['id']; ?>"
                                                    class="btn btn-outline"
                                                    style="padding: 0.3rem 0.6rem; font-size: 0.85rem; background: #f0fdfa; color: var(--primary-teal); border-color: var(--primary-teal);"><i class="fas fa-user-circle"></i> Perfil</a>
                                            
                                            <?php if ($u['status'] === 'approved'): ?>
                                                <a href="user_routes.php?user_id=<?php echo $u['id']; ?>"
                                                    class="btn btn-outline"
                                                    style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">Rotas</a>
                                            <?php endif; ?>
                                            
                                            <a href="edit_user.php?user_id=<?php echo $u['id']; ?>" class="btn btn-outline"
                                                style="color:#0d6efd; border-color:#0d6efd; padding: 0.3rem 0.6rem; font-size: 0.85rem;"><i
                                                    class="fas fa-edit"></i> Editar</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECTION 5: TAREFAS CONCLUÍDAS -->
            <div id="completed" class="tab-content">
                <div class="section-header">
                    <h2><i class="fas fa-check-double"></i> Tarefas Concluídas</h2>
                    <p class="text-muted">Abaixo estão as Macrorregiões com rotas concluídas aguardando revisão e pagamento.</p>
                </div>

                <!-- Aviso Fiscal -->
                <div style="background: #e7f3ff; color: #004085; border: 1px solid #b8daff; padding: 1.2rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; align-items: flex-start; gap: 1rem;">
                    <i class="fas fa-exclamation-circle" style="font-size: 1.4rem; margin-top: 3px;"></i>
                    <div>
                        <p style="margin: 0; font-weight: 500; margin-bottom: 0.5rem;">As rotas concluídas listadas abaixo ainda serão revisadas pelo fiscal e serão encaminhadas para pagamento posteriormente.</p>
                        <p style="margin: 0; font-size: 0.9rem; line-height: 1.4;">
                            <strong style="color: #004085;"><i class="fas fa-info-circle"></i> Importante:</strong> 
                            As rotas que aparecem aqui são rotas ainda não revisadas pelo fiscal e não liquidadas no pagamento.
                            O Fiscal validará a tarefa no Wizard de Andamento, enviará para o Passo 4 e subsequente 5 (Envio para Pagamento). Após o Passo 5, a rota sumirá desta lista e aparecerá em <strong>Pagamentos Liquidados</strong>.
                        </p>
                    </div>
                </div>

                <?php if (count($completed_routes) > 0): 
                    // Agrupar por Macrorregião
                    $grouped_completed = array_fill_keys(array_keys($macro_mapping), []);
                    foreach ($completed_routes as $c_route) {
                        $db_micro = trim($c_route['microregion']);
                        foreach ($macro_mapping as $macro => $ras) {
                            if (in_array($db_micro, $ras)) {
                                $grouped_completed[$macro][] = $c_route;
                                break;
                            }
                        }
                    }
                ?>
                    <!-- Macroregion Cards -->
                    <div class="grid grid-4" style="margin-bottom: 2rem;">
                        <?php 
                        $i = 0;
                        foreach ($grouped_completed as $macro => $routes): 
                            $count = count($routes);
                            $color = $colors[$i % count($colors)];
                            $safeId = preg_replace('/[^a-z0-9]/i', '', $macro);
                        ?>
                            <div id="card-<?php echo $safeId; ?>" class="completed-macro-card" onclick="filterCompleted('<?php echo htmlspecialchars($macro, ENT_QUOTES); ?>')" 
                                 style="background: white; padding: 1.2rem; border-radius: 8px; border: 1px solid #e0e0e0; cursor: pointer; transition: all 0.2s; text-align: center; <?php echo $count === 0 ? 'opacity: 0.6;' : ''; ?>">
                                <div style="font-size: 0.70rem; font-weight: bold; color: <?php echo $color; ?>; text-transform: uppercase;"><?php echo $macro; ?></div>
                                <div style="font-size: 1.8rem; font-weight: bold; color: #333; margin: 0.3rem 0;"><?php echo $count; ?></div>
                                <div style="font-size: 0.75rem; color: #888;">Pendentes de Pgto</div>
                                <?php if ($count > 0): ?>
                                    <div style="font-size: 0.65rem; color: var(--primary-teal); font-weight: 700; margin-top: 5px;"><i class="fas fa-eye"></i> Ver Detalhes</div>
                                <?php else: ?>
                                    <div style="font-size: 0.65rem; color: #ccc; margin-top: 5px;">Nada pendente</div>
                                <?php endif; ?>
                            </div>
                        <?php $i++; endforeach; ?>
                    </div>

                    <!-- Detailed Tables -->
                    <?php foreach ($grouped_completed as $macro => $routes): 
                        if (count($routes) === 0) continue;
                        $safeId = preg_replace('/[^a-z0-9]/i', '', $macro);
                    ?>
                        <div id="table-<?php echo $safeId; ?>" class="completed-macro-table" style="display: none; background: white; border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0; margin-top: 1rem; animation: fadeIn 0.3s;">
                            <div style="background: #f8f9fa; padding: 1rem; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                                <h3 style="margin: 0; font-size: 1rem; color: var(--primary-teal);"><i class="fas fa-map-marker-alt"></i> Detalhes: <?php echo $macro; ?></h3>
                                <button onclick="filterCompleted(null)" class="btn" style="padding: 0.2rem 0.5rem; font-size: 0.7rem; background: #eee; border: none;">Fechar</button>
                            </div>
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead style="background: #fff; border-bottom: 2px solid #eee;">
                                    <tr>
                                        <th style="padding:1rem; text-align: left;">Recenseador / RA</th>
                                        <th style="padding:1rem; text-align: left;">Título da Rota</th>
                                        <th style="padding:1rem; text-align: left;">Data</th>
                                        <th style="padding:1rem; text-align: center;">Relatório</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($routes as $c_route): ?>
                                        <tr style="border-bottom: 1px solid #eee;">
                                            <td style="padding:1rem; vertical-align: top;">
                                                <div style="font-weight: 600; color: #333;"><?php echo mb_strtoupper(htmlspecialchars($c_route['user_name']), 'UTF-8'); ?></div>
                                                <div style="font-size: 0.75rem; color: var(--primary-teal); font-weight: 600; margin-top: 2px;">📍 <?php echo htmlspecialchars($c_route['microregion']); ?></div>
                                            </td>
                                            <td style="padding:1rem; vertical-align: top;">
                                                <strong><?php echo htmlspecialchars($c_route['title']); ?></strong>
                                                <div style="background: #f8f9fa; padding: 0.5rem; border-radius: 4px; font-size: 0.8rem; margin-top: 5px; border-left: 2px solid #28a745;">
                                                    <?php echo nl2br(htmlspecialchars($c_route['observation'] ?? 'N/A')); ?>
                                                </div>
                                            </td>
                                            <td style="padding:1rem; vertical-align: top; font-size: 0.85rem;">
                                                <?php echo date('d/m/Y', strtotime($c_route['completed_at'])); ?>
                                            </td>
                                            <td style="padding:1rem; text-align: center; vertical-align: top;">
                                                <?php
                                                $files = array_filter([$c_route['report_file_1'], $c_route['report_file_2'], $c_route['report_file_3']]);
                                                foreach ($files as $idx => $file):
                                                ?>
                                                    <a href="<?php echo htmlspecialchars($file); ?>" target="_blank" title="Anexo <?php echo $idx+1; ?>" style="color: #28a745; margin: 0 3px;"><i class="fas fa-file-pdf"></i></a>
                                                <?php endforeach; ?>
                                                <a href="edit_route.php?id=<?php echo $c_route['id']; ?>" style="display: block; font-size: 0.65rem; color: #666; margin-top: 5px;">Ver</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5" style="border: 2px dashed #eee; border-radius: 8px; background: white;">
                        <i class="fas fa-folder-open" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                        <p class="text-muted">Nenhuma tarefa foi concluída até o momento.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SECTION 6: TAREFAS CANCELADAS -->
            <div id="cancelled" class="tab-content">
                <div class="section-header">
                    <h2><i class="fas fa-ban"></i> Tarefas Canceladas</h2>
                    <p class="text-muted">Rotas que foram canceladas administrativamente.</p>
                </div>

                <?php if (count($cancelled_routes) > 0): ?>
                    <div style="background: white; border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0; margin-top: 1rem;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th style="padding:1rem; text-align: left;">Recenseador</th>
                                    <th style="padding:1rem; text-align: left;">Título da Rota</th>
                                    <th style="padding:1rem; text-align: left;">Data Cancelamento</th>
                                    <th style="padding:1rem; text-align: center;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cancelled_routes as $r): ?>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding:1rem; font-weight: 600; color: #333;">
                                            <?php echo mb_strtoupper(htmlspecialchars($r['user_name']), 'UTF-8'); ?>
                                        </td>
                                        <td style="padding:1rem;">
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($r['title']); ?></div>
                                            <div style="font-size: 0.85rem; color: #666; margin-top: 2px;">
                                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($r['microregion'] ?? 'N/A'); ?>
                                            </div>
                                        </td>
                                        <td style="padding:1rem;">
                                            <?php echo date('d/m/Y H:i', strtotime($r['updated_at'])); ?>
                                        </td>
                                        <td style="padding:1rem; text-align: center;">
                                            <a href="edit_route.php?id=<?php echo $r['id']; ?>" class="btn btn-outline" style="padding: 0.3rem 0.8rem; font-size: 0.85rem;">
                                                <i class="fas fa-edit"></i> Editar/Reativar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5" style="border: 2px dashed #eee; border-radius: 8px; background: white;">
                        <i class="fas fa-check-circle" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                        <p class="text-muted">Nenhuma rota cancelada no momento.</p>
                    </div>
                <?php endif; ?>
            </div>


            <!-- SECTION 6.5: ROTAS REJEITADAS -->
            <div id="rejected" class="tab-content">
                <div class="section-header">
                    <h2><i class="fas fa-user-times"></i> Rotas Rejeitadas</h2>
                    <p class="text-muted">Lista de rotas que foram oficialmente rejeitadas pelos recenseadores com justificativa.</p>
                </div>

                <?php if (count($rejected_routes) > 0): ?>
                    <div style="background: white; border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0; margin-top: 1rem;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th style="padding:1rem; text-align: left;">Recenseador</th>
                                    <th style="padding:1rem; text-align: left;">Rota / Motivo</th>
                                    <th style="padding:1rem; text-align: left;">Data Recusa</th>
                                    <th style="padding:1rem; text-align: center;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rejected_routes as $r): ?>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding:1rem; font-weight: 600; color: #333;">
                                            <?php echo mb_strtoupper(htmlspecialchars($r['user_name']), 'UTF-8'); ?>
                                        </td>
                                        <td style="padding:1rem;">
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($r['title']); ?></div>
                                            <div style="background: #fff5f5; padding: 0.5rem; border-left: 3px solid #dc3545; color: #b91c1c; font-size: 0.85rem; margin-top: 5px;">
                                                <strong>Motivo:</strong> <?php echo htmlspecialchars($r['rejected_reason'] ?? 'Sem justificativa.'); ?>
                                            </div>
                                        </td>
                                        <td style="padding:1rem;">
                                            <?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?>
                                        </td>
                                        <td style="padding:1rem; text-align: center;">
                                            <a href="edit_route.php?id=<?php echo $r['id']; ?>" class="btn btn-outline" style="padding: 0.3rem 0.8rem; font-size: 0.85rem;">
                                                <i class="fas fa-edit"></i> Reatribuir
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5" style="border: 2px dashed #eee; border-radius: 8px; background: white;">
                        <i class="fas fa-check-circle" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                        <p class="text-muted">Nenhuma rota foi rejeitada até o momento.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SECTION 8: PAGAMENTOS LIQUIDADOS -->
            <div id="paid" class="tab-content">
                <div class="section-header">
                    <h2><i class="fas fa-hand-holding-usd"></i> Pagamentos Liquidados</h2>
                    <p class="text-muted">Histórico de rotas concluídas e com pagamento devidamente processado.</p>
                </div>

                <?php if (count($paid_routes) > 0): ?>
                    <div style="background: white; border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th style="padding:1rem; text-align: left; width: 25%;">Recenseador / Rota</th>
                                    <th style="padding:1rem; text-align: center; width: 10%;">Data Conclusão</th>
                                    <th style="padding:1rem; text-align: center; width: 15%;">SEI Pagamento</th>
                                    <th style="padding:1rem; text-align: center; width: 12%;">Valor Pago</th>
                                    <th style="padding:1rem; text-align: center; width: 18%;">Memória de Cálculo (PDF)</th>
                                    <th style="padding:1rem; text-align: center; width: 10%;">Status</th>
                                    <th style="padding:1rem; text-align: center; width: 10%;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paid_routes as $p): ?>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding:1rem;">
                                            <div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($p['title']); ?></div>
                                            <div style="font-size: 0.85rem; color: var(--primary-teal); font-weight: 600;">
                                                <i class="fas fa-user"></i> <?php echo mb_strtoupper(htmlspecialchars($p['user_name']), 'UTF-8'); ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: #999;">CPF: <?php echo htmlspecialchars($p['user_cpf']); ?></div>
                                        </td>
                                        <td style="padding:1rem; text-align: center;">
                                            <?php echo ($p['completed_at']) ? date('d/m/Y', strtotime($p['completed_at'])) : '---'; ?>
                                        </td>
                                        <td style="padding:1rem; text-align: center;">
                                            <span style="background: #f1f3f5; padding: 0.4rem 0.8rem; border-radius: 4px; font-family: monospace; font-weight: 700; color: #333; white-space: nowrap; display: inline-block; font-size: 0.85rem; border: 1px solid #dee2e6;">
                                                <?php echo htmlspecialchars($p['sei_pagamento'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td style="padding:1rem; text-align: center; font-weight: 700; color: var(--primary-teal);">
                                            <?php echo ($p['calc_grand_total'] > 0) ? 'R$ ' . number_format($p['calc_grand_total'], 2, ',', '.') : '---'; ?>
                                        </td>
                                        <td style="padding:1rem; text-align: center;">
                                            <a href="generate_memory_pdf.php?route_id=<?php echo $p['id']; ?>" target="_blank" class="btn" style="font-size: 0.75rem; padding: 0.6rem 1rem; color: white; background: #28a745; border-color: #28a745; display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-weight: bold; width: 100%; box-sizing: border-box;" title="Visualizar e Imprimir Memória de Cálculo">
                                                <i class="fas fa-file-invoice-dollar"></i> MEMÓRIA (PDF)
                                            </a>
                                        </td>
                                        <td style="padding:1rem; text-align: center;">
                                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem;">
                                                <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                                                    <i class="fas fa-check-circle" style="color: #28a745; font-size: 1.1rem;"></i>
                                                    <span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; white-space: nowrap;">Liquidado</span>
                                                </div>
                                                <button onclick="viewCalculationMemory(<?php echo $p['id']; ?>)" class="btn btn-primary" style="font-size: 0.7rem; padding: 0.3rem 0.6rem; background: #5c6bc0; border: none;">
                                                    <i class="fas fa-file-invoice"></i> Memória de Cálculo
                                                </button>
                                            </div>
                                        </td>
                                        <td style="padding:1rem; text-align: center;">
                                            <div style="display: flex; justify-content: center; gap: 0.5rem;">
                                                <a href="edit_route.php?id=<?php echo $p['id']; ?>" class="btn btn-outline" style="font-size: 0.8rem; padding: 0.3rem 0.6rem;">
                                                    <i class="fas fa-search"></i> Ver Rota
                                                </a>
                                                <form method="post" style="display: inline;" onsubmit="return confirm('Mover de volta para o Wizard de Andamento?');">
                                                    <input type="hidden" name="action" value="update_wizard">
                                                    <input type="hidden" name="route_id" value="<?php echo $p['id']; ?>">
                                                    <input type="hidden" name="step" value="5">
                                                    <button type="submit" class="btn btn-outline" style="font-size: 0.8rem; padding: 0.3rem 0.6rem; color: #f0ad4e; border-color: #f0ad4e;">-1 Estágio</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5" style="border: 2px dashed #eee; border-radius: 8px; background: white;">
                        <i class="fas fa-hand-holding-usd" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                        <p class="text-muted">Ainda não há pagamentos liquidados no sistema.</p>
                    </div>
                <?php endif; ?>
            </div>

            <script>
            function viewCalculationMemory(routeId) {
                // Switch to calculator tab
                const tab = document.querySelector('a[href="#calculator"]');
                if(tab) tab.click();
                
                // Load data into calculator form (assuming fullCalcData is available)
                if(typeof fullCalcData !== 'undefined' && fullCalcData[routeId]) {
                    const d = fullCalcData[routeId];
                    
                    // We need to find the user select and trigger change to load route select
                    const userSelect = document.getElementById('calc_user');
                    if(userSelect) {
                        userSelect.value = d.user_id;
                        updateCalcRouteSelect();
                        
                        // Wait a bit for the route select to populate
                        setTimeout(() => {
                            const routeSelect = document.getElementById('calc_route');
                            if(routeSelect) {
                                routeSelect.value = d.id;
                                loadSavedCalculation();
                                
                                // Scroll to calculator
                                document.getElementById('calculator').scrollIntoView({ behavior: 'smooth' });
                            }
                        }, 200);
                    }
                }
            }
            </script>


            <!-- SECTION 10: GESTÃO DE ADMINISTRADORES -->
            <div id="admins" class="tab-content">
                <div class="section-header">
                    <h2><i class="fas fa-user-shield"></i> Gestão de Administradores</h2>
                    <p class="text-muted">Adicione novos usuários com permissões de acesso ao painel administrativo.</p>
                </div>
                <div class="grid grid-2" style="gap: 2rem; align-items: start;">
                    <!-- Form Creation -->
                    <div style="background: white; padding: 2rem; border-radius: 8px; border: 1px solid #e0e0e0;">
                        <h3 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.1rem; color: #333;"><i class="fas fa-user-plus"></i> Novo Administrador</h3>
                        <form method="post">
                            <input type="hidden" name="action" value="create_admin">
                            <div class="form-group mb-3">
                                <label>Nome Completo</label>
                                <input type="text" name="name" required class="form-control" placeholder="Ex: João Silva" style="width: 100%; padding:0.8rem; border:1px solid #ccc;">
                            </div>
                            <div class="form-group mb-3">
                                <label>E-mail Institucional (@caudf.gov.br)</label>
                                <input type="email" name="email" required class="form-control" placeholder="usuario@caudf.gov.br" style="width: 100%; padding:0.8rem; border:1px solid #ccc;">
                            </div>
                            <div class="form-group mb-4">
                                <label>Senha de Acesso</label>
                                <input type="password" name="password" required class="form-control" placeholder="Clique para digitar" style="width: 100%; padding:0.8rem; border:1px solid #ccc;">
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.8rem;">
                                <i class="fas fa-save"></i> Criar Administrador
                            </button>
                        </form>
                    </div>

                    <!-- List Admins -->
                    <div style="background: white; border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0;">
                        <div style="background: #f8f9fa; padding: 1rem; border-bottom: 1px solid #eee;">
                            <h3 style="margin: 0; font-size: 1rem; color: #333;"><i class="fas fa-list"></i> Administradores Atuais</h3>
                        </div>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #fff; border-bottom: 1px solid #eee;">
                                    <th style="padding:1rem; text-align: left; font-size: 0.85rem;">Nome</th>
                                    <th style="padding:1rem; text-align: left; font-size: 0.85rem;">E-mail</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admin_users as $adm): ?>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding:1rem;">
                                            <div style="font-weight: 600; color: #333; font-size: 0.9rem;">
                                                <?php echo htmlspecialchars($adm['name']); ?>
                                            </div>
                                        </td>
                                        <td style="padding:1rem; font-size: 0.85rem; color: #666;">
                                            <?php echo htmlspecialchars($adm['email']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SECTION 9: RELATÓRIO POR MACRORREGIÃO -->
            <div id="micro-report" class="tab-content">
                <div class="section-header">
                    <h2><i class="fas fa-chart-pie"></i> Relatório por Microrregião</h2>
                    <p class="text-muted">Quantidade total de rotas atribuídas por Macrorregião (excluindo canceladas e rejeitadas).</p>
                </div>

                <div class="grid grid-4">
                    <?php 
                    $i = 0;
                    foreach ($macro_report as $macro => $total): 
                        $color = $colors[$i % count($colors)];
                    ?>
                        <div style="background: white; padding: 1.5rem; border-radius: 8px; border-left: 4px solid <?php echo $color; ?>; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); display: flex; flex-direction: column; justify-content: center; align-items: center;">
                            <div style="font-size: 0.7rem; font-weight: bold; color: <?php echo $color; ?>; text-transform: uppercase; margin-bottom: 0.5rem;"><?php echo $macro; ?></div>
                            <div style="font-size: 1.5rem; font-weight: bold; color: #5a5c69;"><?php echo $total; ?></div>
                            <div style="font-size: 0.75rem; color: #b7b9cc;">Rotas Atribuídas</div>
                        </div>
                    <?php $i++; endforeach; ?>
                </div>

                <div style="background: white; border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0; margin-top: 2rem;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: #f8f9fa;">
                            <tr>
                                <th style="padding:1rem; text-align: left; width: 160px;">Macrorregião</th>
                                <th style="padding:1rem; text-align: left;">Cidades / RAs Incluídas</th>
                                <th style="padding:1rem; text-align: center; width: 140px;">Total de Rotas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($macro_mapping as $macro => $ras): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding:1rem; font-weight: 700; color: #333; white-space: nowrap;"><?php echo $macro; ?></td>
                                    <td style="padding:1rem; font-size: 0.85rem; color: #666;"><?php echo implode(', ', $ras); ?></td>
                                    <td style="padding:1rem; text-align: center; font-weight: bold; font-size: 1.2rem; color: var(--primary-teal);">
                                        <?php echo $macro_report[$macro]; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECTION 7: RELATÓRIO GERAL DE RECENSEADORES -->
            <div id="report" class="tab-content">
                <div class="section-header">
                    <h2><i class="fas fa-file-contract"></i> Relatório Geral de Recenseadores</h2>
                    <p class="text-muted">Desempenho e dados de contratação por recenseador.</p>
                </div>

                <?php if (count($report_data) > 0): ?>
                    <div style="background: white; border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0; margin-top: 1rem;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th style="padding:1rem; text-align: left;">Recenseador</th>
                                    <th style="padding:1rem; text-align: left;">SEI / Contrato</th>
                                    <th style="padding:1rem; text-align: center;">Total</th>
                                    <th style="padding:1rem; text-align: center; color: #f0ad4e;" title="Rotas Atribuídas e Aguardando Início">Aguard.</th>
                                    <th style="padding:1rem; text-align: center; color: #28a745;" title="Rotas Em Andamento">Andamento</th>
                                    <th style="padding:1rem; text-align: center; color: #dc3545;" title="Rotas Atrasadas">Atrasadas</th>
                                    <th style="padding:1rem; text-align: center; color: #198754;" title="Rotas Concluídas">Concluídas</th>
                                    <th style="padding:1rem; text-align: center; color: #6c757d;" title="Rotas Canceladas">Canceladas</th>
                                    <th style="padding:1rem; text-align: center;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $row): ?>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding:1rem;">
                                            <div style="font-weight: 600; color: #333;">
                                                <?php echo mb_strtoupper(htmlspecialchars($row['name']), 'UTF-8'); ?>
                                            </div>
                                            <div style="font-size: 0.85rem; color: #666; margin-top: 0.3rem;">
                                                <?php echo htmlspecialchars($row['email']); ?>
                                            </div>
                                        </td>
                                        <td style="padding:1rem;">
                                            <div style="font-size: 0.85rem; color: #333;">
                                                <strong>SEI:</strong> <?php echo htmlspecialchars($row['processo_sei'] ?? 'N/A'); ?>
                                            </div>
                                            <div style="font-size: 0.85rem; color: #198754; font-weight: 600; margin-top: 0.2rem;">
                                                <strong>CONTRATO:</strong> <?php echo htmlspecialchars($row['contrato'] ?? 'N/A'); ?>
                                            </div>
                                        </td>
                                        <td style="padding:1rem; text-align: center; font-weight: bold; font-size: 1.1rem;"><?php echo $row['total_routes']; ?></td>
                                        <td style="padding:1rem; text-align: center;"><?php echo $row['assigned_routes']; ?></td>
                                        <td style="padding:1rem; text-align: center;"><?php echo $row['in_progress_routes']; ?></td>
                                        <td style="padding:1rem; text-align: center;"><?php echo $row['delayed_routes']; ?></td>
                                        <td style="padding:1rem; text-align: center; font-weight: 600; color: #198754;"><?php echo $row['completed_routes']; ?></td>
                                        <td style="padding:1rem; text-align: center; color: #6c757d;"><?php echo $row['cancelled_routes']; ?></td>
                                        <td style="padding:1rem; text-align: center;">
                                            <a href="user_routes.php?user_id=<?php echo $row['id']; ?>" class="btn btn-outline" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">Ver Rotas</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5" style="border: 2px dashed #eee; border-radius: 8px; background: white;">
                        <i class="fas fa-chart-line" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                        <p class="text-muted">Não há dados suficientes para gerar o relatório.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SEÇÃO: CALCULADORA FINANCEIRA (ANEXO III) -->
            <div id="calculator" class="tab-content" style="display: none;">
                <div class="section-header">
                    <h2><i class="fas fa-calculator"></i> Calculadora Financeira</h2>
                    <p class="text-muted">Valores e Composição da Remuneração de Registro de Demanda</p>
                    <p style="font-size: 0.9rem; color: #555; margin-top: 0.5rem;">O CAUDF, representado pela GERFISC - Gerência de Fiscalização, formaliza a distribuição da demanda para pagamento.</p>
                </div>

                <div class="card" id="calc_card" style="padding: 2rem; max-width: 900px; margin: 0 auto; border: 1px solid #e0e0e0; box-shadow: 0 4px 15px rgba(0,0,0,0.05); background: white;">
                    
                    <!-- CABEÇALHO DE IMPRESSÃO -->
                    <div class="print-header" style="display: none; text-align: center; margin-bottom: 2rem;">
                        <h3 style="font-size: 1.1rem; margin: 0 0 10px 0; font-weight: 700; color: #000; font-family: 'Inter', sans-serif;">Conselho de Arquitetura e Urbanismo do Distrito Federal</h3>
                        <h2 style="font-size: 1.4rem; margin: 0; text-transform: uppercase; font-weight: 800; color: #000; padding: 1rem 0;">MEMÓRIA DE CÁLCULO - REMUNERAÇÃO</h2>
                        
                        <div style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: flex-start; text-align: left; font-size: 1rem; color: #000;">
                            <div style="flex: 1.5;">
                                <strong>Recenseador Associado:</strong> <br>
                                <span id="print_user_name" style="text-transform: uppercase; font-weight: 700; font-size: 1.1rem;"></span><br>
                                <span id="print_user_cpf" style="font-size: 1rem;"></span>
                            </div>
                            <div style="flex: 1; text-align: right;">
                                <strong>Processo SEI:</strong> <br>
                                <span id="print_sei" style="font-size: 1.1rem; font-weight: 700;"></span>
                            </div>
                        </div>

                        <div style="margin-top: 1.5rem; text-align: left; font-size: 0.95rem; background: #fff; padding: 1rem; border: 1px solid #ddd; border-radius: 4px; line-height: 1.6;">
                            <div><strong>Região de Atuação:</strong> <span id="print_user_microregion"></span></div>
                            <div style="margin-top: 0.3rem;"><strong>Rota Referente:</strong> <span id="print_route_title"></span></div>
                            
                            <div style="margin-top: 0.8rem; display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                <div><strong>Data de Atribuição:</strong> <span id="print_route_created"></span></div>
                                <div><strong>Data de Conclusão:</strong> <span id="print_route_completed"></span></div>
                            </div>
                            <div style="margin-top: 0.5rem;"><strong>Endereço de Fiscalização:</strong> <span id="print_route_location"></span></div>
                            <div style="margin-top: 0.5rem; word-break: break-all;"><strong>Link do Mapa:</strong> <span id="print_route_maps" style="font-size: 0.8rem; color: #555;"></span></div>
                            <div style="margin-top: 0.5rem;"><strong>Instruções/Descrição:</strong> <span id="print_route_desc"></span></div>
                        </div>
                        <div style="border-bottom: 2px solid #000; margin-top: 1.5rem; margin-bottom: 1.5rem;"></div>
                    </div>
                    
                    <?php 
                    $user_routes_json = [];
                    foreach ($calc_routes as $cr) {
                        $uid = $cr['user_id'];
                        if(!isset($user_routes_json[$uid])) $user_routes_json[$uid] = [];
                        $u_sei = '';
                        foreach($approved_users as $au) {
                            if($au['id'] == $uid) $u_sei = $au['processo_sei'] ?? 'Não informado';
                        }
                        $user_routes_json[$uid][] = [
                            'id' => $cr['id'],
                            'title' => $cr['title'],
                            'sei' => $u_sei,
                            'created_at' => date('d/m/Y H:i', strtotime($cr['created_at'])),
                            'completed_at' => date('d/m/Y H:i', strtotime($cr['completed_at'])),
                            'location' => htmlspecialchars($cr['start_location'] ?? ''),
                            'maps_url' => htmlspecialchars($cr['maps_url'] ?? 'Não fornecido'),
                            'description' => htmlspecialchars($cr['description'] ?? 'Sem instruções adicionais')
                        ];
                    }
                    $full_routes_calc = $pdo->query("SELECT * FROM routes WHERE wizard_step >= 4")->fetchAll();
                    $full_calc_json = [];
                    foreach($full_routes_calc as $fr) {
                        $full_calc_json[$fr['id']] = $fr;
                    }
                    ?>
                    <script>
                        const userRoutesData = <?php echo json_encode($user_routes_json) ?: '{}'; ?>;
                        const fullCalcData = <?php echo json_encode($full_calc_json) ?: '{}'; ?>;
                    </script>

                    <form method="post" id="calculator_form">
                        <input type="hidden" name="action" value="save_calculation">
                        <input type="hidden" name="total_fixed" id="hidden_total_fixed">
                        <input type="hidden" name="total_variable" id="hidden_total_variable">
                        <input type="hidden" name="grand_total" id="hidden_grand_total">

                        <!-- SELEÇÃO NO-PRINT -->
                        <div class="no-print" style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 1rem; border: 1px solid #eee;">
                            <div style="display: flex; align-items: center; gap: 1.5rem;">
                                <div style="font-weight: 700; color: #444; text-transform: uppercase; font-size: 0.9rem; width: 220px;"><i class="fas fa-user-tag" style="color: var(--primary-teal); margin-right: 5px;"></i> Vincular Recenseador:</div>
                                <div style="flex-grow: 1;">
                                    <select id="calc_user" class="form-control" style="font-weight: 600; color: #333; border: 2px solid #ddd; width: 100%; padding: 0.5rem;" onchange="updateCalcRouteSelect()">
                                        <option value="">-- Selecione o recenseador --</option>
                                        <?php foreach ($approved_users as $u): 
                                            $macroDisplay = $u['microregion'] ?? 'N/A';
                                            $macroDisplay = preg_replace('/Macrorregi.*?o/i', 'Macrorregião', $macroDisplay);
                                            $macroDisplay = preg_replace('/Microrregi.*?o/i', 'Microrregião', $macroDisplay);
                                            if(stripos($macroDisplay, 'Macrorregi') !== false) {
                                                $parts = explode('(', $macroDisplay);
                                                $macroDisplay = trim($parts[0]);
                                            }
                                        ?>
                                            <option value="<?php echo $u['id']; ?>" data-sei="<?php echo htmlspecialchars($u['processo_sei'] ?? ''); ?>" data-microregion="<?php echo htmlspecialchars($macroDisplay); ?>"><?php echo htmlspecialchars($u['name']); ?> (CPF: <?php echo htmlspecialchars($u['cpf'] ?? 'N/A'); ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 1.5rem;">
                                <div style="font-weight: 700; color: #444; text-transform: uppercase; font-size: 0.9rem; width: 220px;"><i class="fas fa-map-marked-alt" style="color: var(--primary-teal); margin-right: 5px;"></i> Selecionar Rota:</div>
                                <div style="flex-grow: 1;">
                                    <select id="calc_route" name="route_id" class="form-control" style="font-weight: 600; color: #333; border: 2px solid #ddd; width: 100%; padding: 0.5rem;" onchange="loadSavedCalculation()">
                                        <option value="">-- Escolha primeiro o recenseador --</option>
                                    </select>
                                </div>
                            </div>
                            <div id="sei_pagamento_container" style="display: none; align-items: center; gap: 1.5rem;">
                                <div style="font-weight: 700; color: #444; text-transform: uppercase; font-size: 0.9rem; width: 220px;"><i class="fas fa-file-invoice-dollar" style="color: var(--primary-teal); margin-right: 5px;"></i> SEI de Pagamento: <span style="color:red;">*</span></div>
                                <div style="flex-grow: 1;">
                                    <input type="text" name="sei_pagamento" id="calc_sei_pagamento" required class="form-control" placeholder="Informe o SEI para liquidação" style="font-weight: 600; color: #333; border: 2px solid #ddd; width: 100%; padding: 0.5rem;">
                                </div>
                            </div>
                        </div>

                        <!-- INPUT DE GASOLINA -->
                        <div class="no-print" style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; display: flex; align-items: center; gap: 1.5rem; border: 1px solid #eee;">
                            <div style="font-weight: 700; color: #444; text-transform: uppercase; font-size: 0.9rem;">Preço Médio Gasolina DF (ANP):</div>
                            <div style="position: relative; width: 160px;">
                                <span style="position: absolute; left: 12px; top: 10px; color: #666; font-weight: 600;">R$</span>
                                <input type="number" id="gas_price" name="gas_price" value="6.36" step="0.001" class="form-control" style="padding-left: 40px; font-weight: 800; color: var(--primary-teal); border: 2px solid #ddd; width: 100%;" oninput="updateFromGasoline()">
                            </div>
                            <div style="font-size: 0.85rem; color: #777;">
                                <i class="fas fa-info-circle"></i> Ajuste a gasolina para atualizar os valores de KM automaticamente.
                            </div>
                        </div>

                    <!-- TABELA CÁLCULO -->
                    <div style="text-align: center; margin-bottom: 1.5rem; font-weight: 800; font-size: 1.2rem; text-transform: uppercase; letter-spacing: 1px;">CÁLCULO REMUNERAÇÃO PREVISTA</div>
                    
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; background: white; border: 1px solid transparent;">
                        <thead>
                            <tr style="border-bottom: 1px solid #eee;">
                                <th style="padding: 1rem 0; text-align: left; width: 45%; color: #555; font-weight: 800; font-size: 1.1rem;">VALOR-FIXO</th>
                                <th style="padding: 1rem 0; text-align: center; color: #333; font-weight: 800;">Quant.</th>
                                <th style="padding: 1rem 0; text-align: center; color: #333; font-weight: 800;">Unitário</th>
                                <th style="padding: 1rem 0; text-align: right; color: #333; font-weight: 800;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding: 1.2rem 0; border-bottom: 1px solid #eee; color: #555;">Escritório-Modelo (un.)</td>
                                <td style="padding: 1.2rem 0; text-align: center; border-bottom: 1px solid #eee;">
                                    <input type="number" id="q_escritorio" name="q_escritorio" value="1" class="form-control" style="width: 60px; margin: 0 auto; text-align: center; font-weight: 800; padding: 0.2rem;" oninput="updateAllCalculations()">
                                </td>
                                <td style="padding: 1.2rem 0; text-align: center; border-bottom: 1px solid #eee;">
                                    <input type="number" id="u_escritorio" name="u_escritorio" value="102.02" step="0.01" class="form-control" style="width: 80px; margin: 0 auto; text-align: center; font-weight: 800; padding: 0.2rem;" oninput="updateAllCalculations()">
                                </td>
                                <td style="padding: 1.2rem 0; text-align: right; border-bottom: 1px solid #eee; color: #555;" id="t_escritorio">R$ 102,02</td>
                            </tr>
                            <tr>
                                <td style="padding: 1.2rem 0; border-bottom: 1px solid #eee; color: #555;">Auxílio-combustível (km)</td>
                                <td style="padding: 1.2rem 0; text-align: center; border-bottom: 1px solid #eee;">
                                    <input type="number" id="q_km_fix" name="q_km_fix" value="10" class="form-control" style="width: 60px; margin: 0 auto; text-align: center; font-weight: 800; padding: 0.2rem;" oninput="updateAllCalculations()">
                                </td>
                                <td style="padding: 1.2rem 0; text-align: center; border-bottom: 1px solid #eee;">
                                    <input type="number" id="u_km_fix" name="u_km_fix" value="2.03" step="0.01" class="form-control" style="width: 80px; margin: 0 auto; text-align: center; font-weight: 800; padding: 0.2rem;" oninput="updateAllCalculations()">
                                </td>
                                <td style="padding: 1.2rem 0; text-align: right; border-bottom: 1px solid #eee; color: #555;" id="t_km_fix">R$ 20,30</td>
                            </tr>
                            <tr>
                                <td style="padding: 1.2rem 0; border-bottom: 1px solid #eee; color: #555;">Auxílio-Alimentação (un.)</td>
                                <td style="padding: 1.2rem 0; text-align: center; border-bottom: 1px solid #eee;">
                                    <input type="number" id="q_alim" name="q_alim" value="2" class="form-control" style="width: 60px; margin: 0 auto; text-align: center; font-weight: 800; padding: 0.2rem;" oninput="updateAllCalculations()">
                                </td>
                                <td style="padding: 1.2rem 0; text-align: center; border-bottom: 1px solid #eee;">
                                    <input type="number" id="u_alim" name="u_alim" value="46.35" step="0.01" class="form-control" style="width: 80px; margin: 0 auto; text-align: center; font-weight: 800; padding: 0.2rem;" oninput="updateAllCalculations()">
                                </td>
                                <td style="padding: 1.2rem 0; text-align: right; border-bottom: 1px solid #eee; color: #555;" id="t_alim">R$ 92,70</td>
                            </tr>
                            <tr style="font-weight: 800; font-size: 1.1rem;">
                                <td colspan="3" style="padding: 1.5rem 0; text-align: left; color: #444;">TOTAL VALOR FIXO</td>
                                <td style="padding: 1.5rem 0; text-align: right; color: #444;" id="total_fixed_display">R$ 215,02</td>
                            </tr>
                        </tbody>
                    </table>

                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; background: white; border: 1px solid transparent;">
                        <thead>
                            <tr style="border-bottom: 1px solid #eee;">
                                <th style="padding: 1rem 0; text-align: left; width: 45%; color: #555; font-weight: 800; font-size: 1.1rem;">VALOR-VARIÁVEL***</th>
                                <th style="padding: 1rem 0; text-align: center; color: #333; font-weight: 800;">Quant.</th>
                                <th style="padding: 1rem 0; text-align: center; color: #333; font-weight: 800;">Unitário</th>
                                <th style="padding: 1rem 0; text-align: right; color: #333; font-weight: 800;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding: 1.2rem 0; border-bottom: 1px solid #eee; color: #555;">Auxílio combustível (km)</td>
                                <td style="padding: 1.2rem 0; text-align: center; border-bottom: 1px solid #eee;">
                                    <input type="number" id="q_km_var" name="q_km_var" value="30" class="form-control" style="width: 60px; margin: 0 auto; text-align: center; font-weight: 800; padding: 0.2rem;" oninput="updateAllCalculations()">
                                </td>
                                <td style="padding: 1.2rem 0; text-align: center; border-bottom: 1px solid #eee;">
                                    <input type="number" id="u_km_var" name="u_km_var" value="2.03" step="0.01" class="form-control" style="width: 80px; margin: 0 auto; text-align: center; font-weight: 800; padding: 0.2rem;" oninput="updateAllCalculations()">
                                </td>
                                <td style="padding: 1.2rem 0; text-align: right; border-bottom: 1px solid #eee; color: #555;" id="t_km_var">R$ 60,90</td>
                            </tr>
                            <tr>
                                <td style="padding: 1.2rem 0; border-bottom: 1px solid #eee; color: #555;">Quant. Obras (un.)</td>
                                <td style="padding: 1.2rem 0; text-align: center; border-bottom: 1px solid #eee;">
                                    <input type="number" id="q_obras" name="q_obras" value="1" class="form-control" style="width: 60px; margin: 0 auto; text-align: center; font-weight: 800; padding: 0.2rem;" oninput="updateAllCalculations()">
                                </td>
                                <td style="padding: 1.2rem 0; text-align: center; border-bottom: 1px solid #eee;">
                                    <input type="number" id="u_obras" name="u_obras" value="7.61" step="0.01" class="form-control" style="width: 80px; margin: 0 auto; text-align: center; font-weight: 800; padding: 0.2rem;" oninput="updateAllCalculations()">
                                </td>
                                <td style="padding: 1.2rem 0; text-align: right; border-bottom: 1px solid #eee; color: #555;" id="t_obras">R$ 7,61</td>
                            </tr>
                            <tr style="font-weight: 800; font-size: 1.1rem;">
                                <td colspan="3" style="padding: 1.5rem 0; text-align: left; color: #444;">TOTAL VALOR VARIÁVEL</td>
                                <td style="padding: 1.5rem 0; text-align: right; color: #444;" id="total_var_display">R$ 68,51</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- RESULTADO FINAL -->
                    <div style="border: 1px solid #000; padding: 1.5rem 2rem; display: flex; justify-content: space-between; align-items: center; background: white; margin-top: 1rem;">
                        <div style="font-weight: 800; font-size: 0.95rem; color: #444;">REMUNERAÇÃO = TOTAL VALOR FIXO + TOTAL VALOR VARIÁVEL</div>
                        <div style="font-size: 2.2rem; font-weight: 800; color: #3b8a7c;" id="grand_total_display">R$ 283,53</div>
                    </div>

                    <div class="no-print" style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                        <p style="font-size: 0.75rem; color: #777; margin: 0;">
                            *** variável a depender da demanda.<br>
                            ** As DEMANDAS são limitadas em no máximo 10 (dez) obras a serem visitadas.
                        </p>
                        
                        <div style="display: flex; gap: 1rem;">
                            <button type="button" onclick="printCalculatorReport()" class="btn btn-outline" style="padding: 0.8rem 1.5rem;">
                                <i class="fas fa-file-pdf"></i> Visualizar Relatório
                            </button>
                            
                            <button type="submit" class="btn btn-primary" style="padding: 0.8rem 1.5rem; background: #28a745; border-color: #28a745;">
                                <i class="fas fa-save"></i> Salvar e Liquidar Pagamento
                            </button>
                        </div>
                    </div>
                    </form>

                    <!-- ASSINATURAS (Visíveis apenas na Impressão) -->
                    <div class="print-footer" style="display: none; margin-top: 4rem; padding-top: 2rem;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                            <div style="width: 45%; text-align: center;">
                                <div style="border-bottom: 1px solid #000; margin-bottom: 5px;"></div>
                                <div style="font-weight: 700; text-transform: uppercase;" id="print_signature_name">NOME DO RECENSEADOR</div>
                                <div style="font-size: 0.85rem; color: #555;" id="print_signature_cpf"></div>
                                <div style="font-size: 0.85rem; color: #555;">Recenseador</div>
                            </div>
                            <div style="width: 45%; text-align: center;">
                                <div style="border-bottom: 1px solid #000; margin-bottom: 5px;"></div>
                                <div style="font-weight: 700; text-transform: uppercase;">Setor Financeiro / CAU-DF</div>
                                <div style="font-size: 0.85rem; color: #555;">Autorização de Pagamento</div>
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 2rem; font-size: 0.85rem; color: #777;">
                            Documento gerado em <?php date_default_timezone_set('America/Sao_Paulo'); echo date('d/m/Y \à\s H:i'); ?>
                        </div>
                    </div>

                    <style>
                        @media print {
                            body * { visibility: hidden; }
                            .sidebar, .topbar, .dashboard-header, .no-print { display: none !important; }
                            #calc_card, #calc_card * { visibility: visible; }
                            #calc_card { position: absolute; left: 0; top: 0; width: 100%; border: none !important; box-shadow: none !important; padding: 0 !important; }
                            #calc_card select, #calc_card input { border: none; background: transparent; -webkit-appearance: none; -moz-appearance: none; font-weight: 800; color: #000 !important; text-align: center; padding: 0; }
                            .print-header { display: block !important; }
                            .print-footer { display: block !important; }
                            .btn { display: none !important; }
                        }
                    </style>

                    <script>
                        function updateCalcRouteSelect() {
                            const userSelect = document.getElementById('calc_user');
                            const routeSelect = document.getElementById('calc_route');
                            const userId = userSelect.value;
                            
                            routeSelect.innerHTML = '';
                            
                            if (!userId) {
                                routeSelect.innerHTML = '<option value="">-- Escolha primeiro o recenseador --</option>';
                                return;
                            }
                            
                            const routes = userRoutesData[userId] || [];
                            
                            if (routes.length === 0) {
                                routeSelect.innerHTML = '<option value="">-- Nenhuma rota pendente de pagamento --</option>';
                                return;
                            }
                            
                            routeSelect.innerHTML = '<option value="">-- Selecione a rota para pagamento --</option>';
                            routes.forEach(r => {
                                const opt = document.createElement('option');
                                opt.value = r.id;
                                opt.dataset.sei = r.sei;
                                opt.dataset.sei_pagamento = fullCalcData[r.id] ? (fullCalcData[r.id].sei_pagamento || '') : '';
                                opt.dataset.created_at = r.created_at;
                                opt.dataset.completed_at = r.completed_at;
                                opt.dataset.location = r.location;
                                opt.dataset.maps_url = r.maps_url;
                                opt.dataset.description = r.description;

                                let statusSuffix = "";
                                if (fullCalcData[r.id] && fullCalcData[r.id].wizard_step == 6) {
                                    statusSuffix = " ✅ (PAGO)";
                                    opt.style.color = "#28a745";
                                    opt.style.fontWeight = "bold";
                                }

                                opt.textContent = `Rota #${r.id} - ${r.title}${statusSuffix}`;
                                routeSelect.appendChild(opt);
                            });
                        }

                        function printCalculatorReport() {
                            const userSelect = document.getElementById('calc_user');
                            const routeSelect = document.getElementById('calc_route');
                            
                            if(userSelect && userSelect.value === '') {
                                alert('Por favor, selecione um recenseador para gerar o relatório nominal.');
                                return;
                            }
                            
                            if(routeSelect && routeSelect.value === '') {
                                alert('Por favor, selecione a rota associada a este cálculo.');
                                return;
                            }
                            
                            updateAllCalculations();

                            const selectedUserText = userSelect.options[userSelect.selectedIndex].text;
                            const selectedUserOption = userSelect.options[userSelect.selectedIndex];
                            const selectedRouteOption = routeSelect.options[routeSelect.selectedIndex];
                            const seiNumber = document.getElementById('calc_sei_pagamento').value || selectedRouteOption.dataset.sei || 'Não informado';
                            const microregion = selectedUserOption.dataset.microregion || 'Não informada';
                            
                            const cleanName = selectedUserText.split(' (CPF:')[0].trim();
                            const cpfMatch = selectedUserText.match(/CPF:\s*([^)]+)/);
                            const cpfText = cpfMatch ? 'CPF: ' + cpfMatch[1].trim() : '';
                            
                            document.getElementById('print_user_name').innerText = cleanName;
                            document.getElementById('print_user_cpf').innerText = cpfText;
                            document.getElementById('print_signature_name').innerText = cleanName;
                            document.getElementById('print_signature_cpf').innerText = cpfText;
                            document.getElementById('print_sei').innerText = seiNumber;
                            document.getElementById('print_user_microregion').innerText = microregion;
                            document.getElementById('print_route_title').innerText = selectedRouteOption.textContent;
                            
                            document.getElementById('print_route_created').innerText = selectedRouteOption.dataset.created_at || '';
                            document.getElementById('print_route_completed').innerText = selectedRouteOption.dataset.completed_at || '';
                            document.getElementById('print_route_location').innerText = selectedRouteOption.dataset.location || '';
                            document.getElementById('print_route_maps').innerText = selectedRouteOption.dataset.maps_url || '';
                            document.getElementById('print_route_desc').innerText = selectedRouteOption.dataset.description || '';
                            
                            window.print();
                        }

                        function loadSavedCalculation() {
                            const routeSelect = document.getElementById('calc_route');
                            const routeId = routeSelect.value;
                            
                            const seiContainer = document.getElementById('sei_pagamento_container');
                            if (routeId) {
                                seiContainer.style.display = 'flex';
                                const selectedRouteOption = routeSelect.options[routeSelect.selectedIndex];
                                document.getElementById('calc_sei_pagamento').value = selectedRouteOption.dataset.sei_pagamento || '';
                            } else {
                                seiContainer.style.display = 'none';
                            }

                            if (!routeId || !fullCalcData[routeId]) {
                                document.getElementById('gas_price').value = "6.36";
                                document.getElementById('q_escritorio').value = 1;
                                document.getElementById('u_escritorio').value = 102.02;
                                document.getElementById('q_km_fix').value = 10;
                                document.getElementById('q_alim').value = 2;
                                document.getElementById('u_alim').value = 46.35;
                                document.getElementById('q_km_var').value = 30;
                                document.getElementById('q_obras').value = 1;
                                document.getElementById('u_obras').value = 7.61;
                                updateFromGasoline();
                                return;
                            }

                            const data = fullCalcData[routeId];
                            
                            if (data.calc_gas_price > 0) {
                                document.getElementById('gas_price').value = data.calc_gas_price;
                                document.getElementById('q_escritorio').value = data.calc_q_escritorio;
                                document.getElementById('u_escritorio').value = data.calc_u_escritorio;
                                document.getElementById('q_km_fix').value = data.calc_q_km_fix;
                                document.getElementById('u_km_fix').value = data.calc_u_km_fix;
                                document.getElementById('q_alim').value = data.calc_q_alim;
                                document.getElementById('u_alim').value = data.calc_u_alim;
                                document.getElementById('q_km_var').value = data.calc_q_km_var;
                                document.getElementById('u_km_var').value = data.calc_u_km_var;
                                document.getElementById('q_obras').value = data.calc_q_obras;
                                document.getElementById('u_obras').value = data.calc_u_obras;
                                document.getElementById('calc_sei_pagamento').value = data.sei_pagamento || '';
                                updateAllCalculations();
                            } else {
                                updateFromGasoline();
                            }
                        }

                        function updateFromGasoline() {
                            let gasVal = document.getElementById('gas_price').value;
                            if (typeof gasVal === 'string') {
                                gasVal = gasVal.replace(',', '.');
                            }
                            const gasPrice = parseFloat(gasVal) || 0;
                            const kmUnit = 1.39 + (gasPrice * 0.10);
                            const kmUnitRounded = Math.round(kmUnit * 100) / 100;

                            document.getElementById('u_km_fix').value = kmUnitRounded.toFixed(2);
                            document.getElementById('u_km_var').value = kmUnitRounded.toFixed(2);
                            
                            updateAllCalculations();
                        }

                        function updateAllCalculations() {
                            const format = (v) => 'R$ ' + v.toLocaleString('pt-BR', {minimumFractionDigits: 2});

                            const qEsc = parseFloat(document.getElementById('q_escritorio').value) || 0;
                            const uEsc = parseFloat(document.getElementById('u_escritorio').value) || 0;
                            const tEsc = qEsc * uEsc;
                            document.getElementById('t_escritorio').innerText = format(tEsc);

                            const qKmF = parseFloat(document.getElementById('q_km_fix').value) || 0;
                            const uKmF = parseFloat(document.getElementById('u_km_fix').value) || 0;
                            const tKmF = qKmF * uKmF;
                            document.getElementById('t_km_fix').innerText = format(tKmF);

                            const qAlim = parseFloat(document.getElementById('q_alim').value) || 0;
                            const uAlim = parseFloat(document.getElementById('u_alim').value) || 0;
                            const tAlim = qAlim * uAlim;
                            document.getElementById('t_alim').innerText = format(tAlim);

                            const totalFixed = tEsc + tKmF + tAlim;
                            document.getElementById('total_fixed_display').innerText = format(totalFixed);
                            document.getElementById('hidden_total_fixed').value = totalFixed.toFixed(2);

                            const qKmV = parseFloat(document.getElementById('q_km_var').value) || 0;
                            const uKmV = parseFloat(document.getElementById('u_km_var').value) || 0;
                            const tKmV = qKmV * uKmV;
                            document.getElementById('t_km_var').innerText = format(tKmV);

                            const qObras = parseFloat(document.getElementById('q_obras').value) || 0;
                            const uObras = parseFloat(document.getElementById('u_obras').value) || 0;
                            const tObras = qObras * uObras;
                            document.getElementById('t_obras').innerText = format(tObras);

                            const totalVar = tKmV + tObras;
                            document.getElementById('total_var_display').innerText = format(totalVar);
                            document.getElementById('hidden_total_variable').value = totalVar.toFixed(2);

                            const grandTotal = totalFixed + totalVar;
                            document.getElementById('grand_total_display').innerText = format(grandTotal);
                            document.getElementById('hidden_grand_total').value = grandTotal.toFixed(2);
                        }
                    </script>
                </div>
            </div>

        </main>
    </div>
    
    <script>
        function updateCountdowns() {
            const containers = document.querySelectorAll('.countdown-container');
            
            containers.forEach(container => {
                const deadlineStr = container.getAttribute('data-deadline');
                if (!deadlineStr) return;
                
                const deadline = new Date(deadlineStr).getTime();
                const now = new Date().getTime();
                const diff = deadline - now;
                
                const timerSpan = container.querySelector('.countdown-timer');
                
                if (diff <= 0) {
                    timerSpan.innerHTML = "PRAZO ENCERRADO";
                    container.style.color = "#dc3545";
                    container.style.background = "#ffebeb";
                    return;
                }
                
                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                
                let timeStr = "";
                if (days > 0) timeStr += `<span style="color: #0284c7;">${days}d</span> `;
                timeStr += `${hours.toString().padStart(2, '0')}h ${minutes.toString().padStart(2, '0')}m <span style="opacity: 0.6; font-size: 0.75rem;">${seconds.toString().padStart(2, '0')}s</span>`;
                
                timerSpan.innerHTML = "Tempo Restante: " + timeStr;
            });
        }
        
        // Initial run
        updateCountdowns();
        // Update every second
        setInterval(updateCountdowns, 1000);
    </script>
</body>

</html>