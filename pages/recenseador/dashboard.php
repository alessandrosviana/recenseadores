<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recenseador') {
    header("Location: ../login.php");
    exit();
}

$message = '';

// Handle Route Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['start_route_id'])) {
        $routeId = $_POST['start_route_id'];
        // Update status to in_progress AND set start_time
        try {
            $stmt = $pdo->prepare("UPDATE routes SET status = 'in_progress', start_time = NOW() WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$routeId, $_SESSION['user_id']])) {
                if ($stmt->rowCount() > 0) {
                    $message = '<div class="alert success"><i class="fas fa-play"></i> Rota iniciada! Bom trabalho. O administrador foi notificado.</div>';
                } else {
                    $message = '<div class="alert warning"><i class="fas fa-exclamation-triangle"></i> Nenhuma alteração feita. Verifique se a rota já foi iniciada ou pertence a você.</div>';
                }
            } else {
                $message = '<div class="alert danger"><i class="fas fa-times"></i> Erro ao atualizar status. Tente novamente.</div>';
            }
        } catch (PDOException $e) {
             // Fallback if start_time column missing: try updating only status
             $stmt = $pdo->prepare("UPDATE routes SET status = 'in_progress' WHERE id = ? AND user_id = ?");
             if ($stmt->execute([$routeId, $_SESSION['user_id']])) {
                 $message = '<div class="alert success"><i class="fas fa-play"></i> Rota iniciada (sem registro de horário)! Bom trabalho.</div>';
             } else {
                 $message = '<div class="alert danger"><i class="fas fa-times"></i> Erro crítico: ' . $e->getMessage() . '</div>';
             }
        }
    } elseif (isset($_POST['complete_route_id'])) {
        $routeId = $_POST['complete_route_id'];
        $obs = $_POST['observation'] ?? '';
        $uploadDir = '../../uploads/reports/';
        
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $filePaths = [];
        for ($i = 1; $i <= 3; $i++) {
            $inputName = "report_file_$i";
            if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] == 0) {
                $ext = pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION);
                if (strtolower($ext) == 'pdf') {
                    $newName = "report_{$routeId}_{$i}_" . time() . ".pdf";
                    if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $uploadDir . $newName)) {
                        $filePaths[$i] = $uploadDir . $newName;
                    }
                }
            }
        }
        
        $sql = "UPDATE routes SET status = 'completed', completed_at = NOW(), observation = ?, 
                report_file_1 = ?, report_file_2 = ?, report_file_3 = ? WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([
            $obs, 
            $filePaths[1] ?? null, 
            $filePaths[2] ?? null, 
            $filePaths[3] ?? null, 
            $routeId, 
            $_SESSION['user_id']
        ])) {
            $message = '<div class="alert success"><i class="fas fa-check-circle"></i> Rota finalizada e relatório enviado com sucesso!</div>';
            // Update wizard step to 4 if currently < 4
            $pdo->prepare("UPDATE routes SET wizard_step = 4 WHERE id = ? AND (wizard_step < 4 OR wizard_step IS NULL)")->execute([$routeId]);
        }
    } elseif (isset($_POST['accept_route_id'])) {
        $routeId = $_POST['accept_route_id'];
        $stmt = $pdo->prepare("UPDATE routes SET status = 'accepted', wizard_step = 3, accepted_at = NOW() WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$routeId, $_SESSION['user_id']])) {
            $message = '<div class="alert success"><i class="fas fa-file-signature"></i> Rota aceita! Por favor, baixe o seu Termo de Registro de Demanda abaixo antes de iniciar.</div>';
        }
    }
}

// Fetch user status
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Fetch assigned routes (active ones)
$routes_stmt = $pdo->prepare("SELECT * FROM routes WHERE user_id = ? AND status NOT IN ('rejected', 'cancelled') ORDER BY created_at DESC");
$routes_stmt->execute([$_SESSION['user_id']]);
$routes = $routes_stmt->fetchAll();

// Fetch user documents
$docs_stmt = $pdo->prepare("SELECT * FROM documents WHERE user_id = ?");
$docs_stmt->execute([$_SESSION['user_id']]);
$user_docs = $docs_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Recenseador - CAU/DF</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/recenseadores/assets/css/style.css">
    <style>
        .dashboard-header {
            background: white;
            padding: 2.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid var(--border);
        }

        .status-badge-lg {
            padding: 0.5rem 1.5rem;
            border-radius: 30px;
            font-weight: 800;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .route-card {
            background: white;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            transition: var(--transition);
            overflow: hidden;
            border-top: 5px solid var(--primary-teal);
            position: relative;
        }

        .route-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .status-label {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-weight: 800;
            font-size: 0.7rem;
            text-transform: uppercase;
            box-shadow: var(--shadow-sm);
        }

        .bg-pending_acceptance { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .bg-accepted { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .bg-in_progress { background: var(--info-light); color: var(--info); border: 1px solid var(--info); }
        .bg-completed { background: var(--success-light); color: var(--success); }
        .bg-rejected { background: var(--danger-light); color: var(--danger); }

        .countdown-timer {
            font-family: 'Courier New', Courier, monospace;
            background: #0f172a;
            color: #22c55e;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: bold;
            font-size: 1.1rem;
            text-align: center;
            border: 2px solid #1e293b;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);
        }
    </style>
</head>

<body>
    <?php include '../../includes/header.php'; ?>

    <main class="container" style="padding-top: 3rem; padding-bottom: 5rem;">

        <div class="dashboard-header">
            <div class="user-welcome">
                <h2 style="margin:0; font-size: 1.8rem; letter-spacing: -0.5px;">Olá, <span style="color: var(--primary-teal);"><?php echo explode(' ', $user['name'])[0]; ?></span>.</h2>
                <p class="text-muted" style="margin-top: 5px; font-weight: 500;">Bem-vindo ao seu painel de controle de coletas.</p>
            </div>
            <div class="user-status-summary">
                <?php if ($user['status'] === 'approved'): ?>
                    <span class="status-badge-lg" style="background: var(--success-light); color: var(--success); border: 1px solid var(--success);">
                        <i class="fas fa-check-circle"></i> Documentação Aprovada
                    </span>
                <?php elseif ($user['status'] === 'pending'): ?>
                    <span class="status-badge-lg" style="background: var(--warning-light); color: var(--warning); border: 1px solid var(--warning);">
                        <i class="fas fa-clock"></i> Aguardando Aprovação
                    </span>
                <?php else: ?>
                    <span class="status-badge-lg" style="background: var(--danger-light); color: var(--danger); border: 1px solid var(--danger);">
                        <i class="fas fa-times-circle"></i> Documentação Reprovada
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($message))
            echo $message; ?>

        <div class="grid dashboard-grid" style="grid-template-columns: 1fr 3fr; gap: 2rem;">

            <div class="sidebar-status">
                <div class="status-card status-<?php echo $user['status']; ?>">
                    <?php if ($user['status'] == 'pending'): ?>
                        <i class="fas fa-clock status-icon"></i>
                        <span class="status-badge badge-pending">Em Análise</span>
                        <h3 class="mb-2">Cadastro em Análise</h3>
                        <p class="text-muted">Seus documentos estão sendo analisados.</p>
                    <?php elseif ($user['status'] == 'approved'): ?>
                        <i class="fas fa-check-circle status-icon"></i>
                        <span class="status-badge badge-approved">Aprovado</span>
                        <h3 class="mb-2">Cadastro Ativo</h3>
                        <p class="text-muted">Você está apto a realizar coletas.</p>
                    <?php else: ?>
                        <i class="fas fa-times-circle status-icon"></i>
                        <span class="status-badge badge-rejected">Rejeitado</span>
                        <h3 class="mb-2">Cadastro Reprovado</h3>
                        <p class="text-muted" style="color: #dc3545; font-weight: 500; font-size: 0.9rem;">Procurar o CAU/DF para regularização.</p>
                    <?php endif; ?>
                </div>
                
                <div class="status-card" style="margin-top: 1rem; text-align: left; padding: 1.5rem;">
                    <h3 style="font-size: 1.1rem; color: #333; margin-bottom: 1rem; border-bottom: 2px solid var(--primary-teal); padding-bottom: 0.5rem;"><i class="fas fa-file-alt"></i> Meus Documentos</h3>
                    
                    <?php if (count($user_docs) > 0): ?>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <?php foreach ($user_docs as $doc): ?>
                                <li style="border-bottom: 1px solid #eee; padding: 0.8rem 0; display: flex; justify-content: space-between; align-items: center;">
                                    <div style="font-size: 0.9rem; font-weight: 500; color: #555;">
                                        <i class="far fa-file-pdf" style="color: #dc3545; margin-right: 5px;"></i> 
                                        <?php echo htmlspecialchars($doc['document_type']); ?>
                                    </div>
                                    <div>
                                        <?php if (($doc['status'] ?? 'pending') === 'approved'): ?>
                                            <span style="background: #198754; color: white; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700;">APROVADO</span>
                                        <?php elseif (($doc['status'] ?? 'pending') === 'rejected'): ?>
                                            <span style="background: #dc3545; color: white; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700;">REPROVADO</span>
                                        <?php else: ?>
                                            <span style="background: #ffc107; color: #000; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700;">PENDENTE</span>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="font-size: 0.85rem; color: #888; text-align: center; margin: 1rem 0;">Nenhum documento anexado.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="main-content">
                <?php if ($user['status'] == 'approved'): ?>
                    <h3 class="mb-4" style="border-left: 4px solid var(--primary-teal); padding-left: 1rem; color: #333;">
                        Minhas Rotas de Trabalho
                    </h3>

                    <?php if (count($routes) > 0): ?>
                        <div class="grid grid-2">
                            <?php foreach ($routes as $route): 
                                $deadline = !empty($route['scheduled_end']) ? $route['scheduled_end'] : (!empty($route['end_date']) ? $route['end_date'] : null);
                            ?>
                                <div class="route-card" style="display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--border); transition: transform 0.2s;">
                                    <!-- Header: Simplified & Elegant -->
                                    <div style="background: var(--slate-50); padding: 1.25rem; border-bottom: 1px solid var(--border); position: relative;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                            <span style="font-size: 0.65rem; font-weight: 800; color: var(--primary-teal); letter-spacing: 0.05em; text-transform: uppercase;">Tarefa Atribuída</span>
                                            <span class="status-label bg-<?php echo $route['status']; ?>" style="font-size: 0.65rem; padding: 4px 10px;">
                                                <?php
                                                if ($route['status'] == 'pending_acceptance') echo 'Pendente Aceite';
                                                elseif ($route['status'] == 'accepted') echo 'Aceita / Preparando';
                                                elseif ($route['status'] == 'in_progress') echo 'Em Andamento';
                                                elseif ($route['status'] == 'completed') echo 'Concluída';
                                                elseif ($route['status'] == 'rejected') echo 'Rejeitada';
                                                 else echo htmlspecialchars($route['status']);
                                                ?>
                                            </span>
                                        </div>
                                        <h3 style="margin: 0; font-size: 1.1rem; color: var(--slate-900); font-weight: 700; line-height: 1.3;">
                                            <?php echo htmlspecialchars($route['title']); ?>
                                        </h3>
                                        
                                        <?php 
                                        $mapUrl = !empty($route['google_maps_link']) ? $route['google_maps_link'] : (!empty($route['maps_url']) ? $route['maps_url'] : null);
                                        if ($mapUrl): 
                                     ?>
                                         <a href="<?php echo htmlspecialchars($mapUrl); ?>" target="_blank" 
                                           style="display: inline-flex; align-items: center; gap: 6px; margin-top: 0.8rem; color: #2563eb; font-size: 0.7rem; font-weight: 700; text-decoration: none; padding: 4px 0;">
                                            <i class="fas fa-external-link-alt"></i> ABRIR NO GOOGLE MAPS
                                        </a>
                                        <?php endif; ?>
                                    </div>

                                    <div class="route-body" style="padding: 1.25rem; flex-grow: 1; display: flex; flex-direction: column; gap: 1.25rem;">
                                        
                                        <!-- Dates & Alerts Grid -->
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                            <div style="background: white; border: 1px solid var(--border); padding: 0.75rem; border-radius: 8px;">
                                                <small style="font-size: 0.6rem; color: var(--slate-500); font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 4px;">Atribuição</small>
                                                <div style="font-size: 0.8rem; font-weight: 600; color: var(--slate-700);">
                                                    <i class="far fa-calendar-check" style="color: var(--primary-teal); margin-right: 4px;"></i>
                                                    <?php echo date('d/m/Y', strtotime($route['created_at'])); ?>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($deadline)): ?>
                                            <div style="background: #fff5f5; border: 1px solid #fee2e2; padding: 0.75rem; border-radius: 8px;">
                                                <small style="font-size: 0.6rem; color: #b91c1c; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 4px;">Prazo Final</small>
                                                <div style="font-size: 0.8rem; font-weight: 700; color: #b91c1c;">
                                                    <i class="far fa-clock" style="margin-right: 4px;"></i>
                                                    <?php echo date('d/m/Y H:i', strtotime($deadline)); ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Urgent Countdown if in progress -->
                                        <?php if (in_array($route['status'], ['pending_acceptance', 'accepted', 'in_progress', 'delayed']) && !empty($deadline)): ?>
                                            <div class="countdown-container" data-deadline="<?php echo $deadline; ?>" style="border-radius: 6px; padding: 0.6rem 0.8rem; margin-top: 0.5rem; display: flex; justify-content: center; align-items: center; gap: 6px; background: #f0f9ff; border: 1px solid #bae6fd; color: #0284c7; font-size: 0.85rem; font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.02); width: 100%;">
                                                <i class="fas fa-hourglass-half" style="color: #0284c7; font-size: 0.8rem;"></i>
                                                <div class="countdown-timer">00:00:00</div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Simplified Address Section -->
                                        <div>
                                            <h4 style="font-size: 0.65rem; color: var(--slate-500); font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 6px;">
                                                <i class="fas fa-map-pin"></i> Localização
                                            </h4>
                                            <?php
                                            $addressComponents = [];
                                            if (!empty($route['address_street'])) {
                                                $line1 = array_filter([$route['address_street'], $route['address_number'], $route['address_complement']]);
                                                $addressComponents[] = "<strong>" . implode(', ', array_map('htmlspecialchars', $line1)) . "</strong>";
                                                $rest = array_filter([$route['address_neighborhood'], $route['address_city']]);
                                                if (!empty($rest)) $addressComponents[] = implode(', ', array_map('htmlspecialchars', $rest));
                                            } else if (!empty($route['start_location'])) {
                                                $addressComponents[] = "<strong>" . htmlspecialchars($route['start_location']) . "</strong>";
                                            }
                                            ?>
                                            <div style="font-size: 0.85rem; color: var(--slate-600); line-height: 1.4;">
                                                <?php echo implode('<br>', $addressComponents); ?>
                                            </div>
                                        </div>

                                        <!-- Attachments: Compact Pills -->
                                        <?php 
                                        $adminFiles = array_filter([
                                             $route['ref_image'] ?? $route['admin_file_1'] ?? null, 
                                             $route['ref_pdf_1'] ?? $route['admin_file_2'] ?? null, 
                                             $route['ref_pdf_2'] ?? $route['admin_file_3'] ?? null
                                         ]);
                                        if (!empty($adminFiles)): 
                                        ?>
                                        <div style="background: #fffbeb; padding: 0.75rem; border-radius: 8px; border: 1px solid #fef3c7;">
                                            <div style="font-size: 0.6rem; color: #92400e; font-weight: 800; text-transform: uppercase; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 4px;">
                                                <i class="fas fa-paperclip"></i> Anexos Admin
                                            </div>
                                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                                <?php foreach ($adminFiles as $file): 
                                                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                                    $icon = ($ext === 'pdf') ? 'fa-file-pdf' : 'fa-file-image';
                                                ?>
                                                <a href="<?php echo str_replace('../../', '/recenseadores/', htmlspecialchars($file)); ?>" target="_blank" 
                                                   style="background: white; border: 1px solid #fde68a; padding: 4px 10px; border-radius: 4px; font-size: 0.65rem; color: #92400e; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                                    <i class="fas <?php echo $icon; ?>"></i> Abrir
                                                </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <!-- Action Buttons -->
                                        <div style="margin-top: auto;">
                                            <?php if ($route['status'] === 'pending_acceptance'): ?>
                                                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 8px;">
                                                    <form method="post">
                                                        <input type="hidden" name="accept_route_id" value="<?php echo $route['id']; ?>">
                                                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; font-size: 0.75rem; background: #00897b;">
                                                            <i class="fas fa-check"></i> ACEITAR E GERAR TERMO
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-outline" style="color: var(--danger); border-color: var(--danger); padding: 0.75rem; font-size: 0.75rem;" onclick="document.getElementById('reject_form_<?php echo $route['id']; ?>').style.display='block'; this.parentElement.style.display='none';">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                
                                                <div id="reject_form_<?php echo $route['id']; ?>" style="display: none; background: #fff5f5; padding: 1rem; border-radius: 8px; border: 1px solid #fee2e2; margin-top: 10px;">
                                                    <form method="post">
                                                        <input type="hidden" name="reject_route_id" value="<?php echo $route['id']; ?>">
                                                        <textarea name="reject_reason" required class="form-control mb-2" rows="2" style="width: 100%; font-size: 0.8rem;" placeholder="Motivo da rejeição..."></textarea>
                                                        <div style="display: flex; gap: 5px;">
                                                            <button type="submit" class="btn btn-primary" style="background: var(--danger); flex: 1; font-size: 0.7rem;">REJEITAR</button>
                                                            <button type="button" class="btn btn-outline" style="flex: 1; font-size: 0.7rem;" onclick="location.reload();">VOLTAR</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            <?php elseif ($route['status'] === 'accepted'): ?>
                                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                                    <a href="generate_contract.php?route_id=<?php echo $route['id']; ?>" target="_blank" class="btn" style="width: 100%; padding: 0.75rem; font-size: 0.75rem; color: white; border-color: #28a745; background: #28a745;">
                                                        <i class="fas fa-file-pdf"></i> BAIXAR TERMO DE REGISTRO DE DEMANDA
                                                    </a>
                                                    <form method="post">
                                                        <input type="hidden" name="start_route_id" value="<?php echo $route['id']; ?>">
                                                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; font-size: 0.75rem;">
                                                            <i class="fas fa-play"></i> INICIAR TRABALHO NA ROTA
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php elseif ($route['status'] === 'in_progress' || $route['status'] === 'delayed'): ?>
                                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                                    <a href="generate_contract.php?route_id=<?php echo $route['id']; ?>" target="_blank" class="btn" title="Termo de Registro de Demanda gerado no aceite" style="width: 100%; padding: 0.6rem; font-size: 0.75rem; color: white; border-color: #28a745; background: #28a745;">
                                                        <i class="fas fa-file-signature"></i> TERMO DE ACEITE (PDF)
                                                    </a>
                                                </div>
                                                <div style="border-top: 1px solid var(--border); padding-top: 1rem; margin-top: 10px;">
                                                    <form method="post" enctype="multipart/form-data">
                                                        <input type="hidden" name="complete_route_id" value="<?php echo $route['id']; ?>">
                                                        <div class="form-group" style="margin-bottom: 0.75rem;">
                                                            <label style="font-size: 0.7rem; font-weight: 700;">Relatório de Execução</label>
                                                            <textarea name="observation" required class="form-control" rows="2" style="font-size: 0.8rem; border-radius: 6px;" placeholder="Descreva os detalhes da visita..."></textarea>
                                                        </div>
                                                        <div class="form-group" style="margin-bottom: 1rem;">
                                                            <label style="font-size: 0.7rem; font-weight: 700;">Comprovantes (PDF)</label>
                                                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                                                <input type="file" name="report_file_1" accept=".pdf" style="font-size: 0.7rem;">
                                                                <input type="file" name="report_file_2" accept=".pdf" style="font-size: 0.7rem;">
                                                                <input type="file" name="report_file_3" accept=".pdf" style="font-size: 0.7rem;">
                                                            </div>
                                                        </div>
                                                        <button type="submit" class="btn btn-primary" style="width: 100%; background: var(--success); box-shadow: none; padding: 0.75rem; font-size: 0.75rem;">
                                                            <i class="fas fa-check-circle"></i> CONCLUIR TAREFA
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php elseif ($route['status'] === 'completed'): ?>
                                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                                    <a href="generate_contract.php?route_id=<?php echo $route['id']; ?>" target="_blank" class="btn" title="Termo de Registro de Demanda gerado no aceite" style="width: 100%; padding: 0.6rem; font-size: 0.75rem; color: white; border-color: #28a745; background: #28a745;">
                                                        <i class="fas fa-file-signature"></i> TERMO DE ACEITE (PDF)
                                                    </a>
                                                    <div style="background: var(--success-light); color: var(--success); padding: 0.75rem; border-radius: 8px; text-align: center; font-weight: 700; font-size: 0.8rem; border: 1px solid var(--success-light);">
                                                        <i class="fas fa-check-double"></i> ROTA CONCLUÍDA E ENVIADA
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5" style="border: 1px dashed #ccc; background: #f9f9f9;">
                            <p class="text-muted">Nenhuma rota atribuída.</p>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div style="background: #fff; padding: 2rem; border-radius: 4px; border: 1px solid #e0e0e0; text-align: center;">
                        <?php if ($user['status'] == 'rejected'): ?>
                            <div style="color: #dc3545;">
                                <h3><i class="fas fa-exclamation-triangle"></i> Cadastro Reprovado</h3>
                                <p style="font-size: 1.1rem; margin-top: 1rem; color: #333;">Seu cadastro não foi aprovado pela administração. Por favor, <strong>procure o CAU/DF para regularização</strong>.</p>
                            </div>
                        <?php else: ?>
                            <p>Aguarde a aprovação do seu cadastro.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <?php include '../../includes/footer.php'; ?>

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