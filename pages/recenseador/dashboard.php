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
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
        for ($i = 1; $i <= 3; $i++) {
            $inputName = "report_file_$i";
            if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] == 0) {
                $ext = strtolower(pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowedExts)) {
                    $newName = "report_{$routeId}_{$i}_" . time() . "." . $ext;
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
        } elseif (isset($_POST['replace_doc_id']) && isset($_FILES['new_document'])) {
            $docId = (int)$_POST['replace_doc_id'];
            $userId = $_SESSION['user_id'];
            
            // Buscar o documento atual para garantir propriedade e checar status
            $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ? AND user_id = ?");
            $stmt->execute([$docId, $userId]);
            $doc = $stmt->fetch();
            
            if ($doc && $doc['status'] === 'rejected') {
                $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
                $filename = $_FILES['new_document']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $uploadDir = '../../uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    // Gerar nome de arquivo único
                    $newFilename = uniqid() . '_' . basename($filename);
                    $destination = $uploadDir . $newFilename;
                    
                    if (move_uploaded_file($_FILES['new_document']['tmp_name'], $destination)) {
                        // Deletar o arquivo físico antigo se existir
                        $oldFilePath = $doc['file_path'];
                        $oldPhysicalPath = '../../uploads/' . basename($oldFilePath);
                        if (!empty($oldFilePath) && file_exists($oldPhysicalPath)) {
                            @unlink($oldPhysicalPath);
                        }
                        
                        // Gravar o novo caminho relativo compatível com o sistema ('../uploads/...')
                        $dbFilePath = '../uploads/' . $newFilename;
                        
                        // Atualizar tabela de documentos
                        $updateStmt = $pdo->prepare("UPDATE documents SET file_path = ?, original_name = ?, status = 'pending', uploaded_at = NOW() WHERE id = ?");
                        $updateStmt->execute([$dbFilePath, $filename, $docId]);
                        
                        // Se o status do recenseador for 'rejected', atualiza para 'pending'
                        $userStmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
                        $userStmt->execute([$userId]);
                        $userStatus = $userStmt->fetchColumn();
                        
                        if ($userStatus === 'rejected') {
                            $updateUserStmt = $pdo->prepare("UPDATE users SET status = 'pending' WHERE id = ?");
                            $updateUserStmt->execute([$userId]);
                        }
                        
                        $message = '<div class="alert success"><i class="fas fa-check-circle"></i> Documento enviado com sucesso! Aguarde a nova análise do administrador.</div>';
                    } else {
                        $message = '<div class="alert danger"><i class="fas fa-times"></i> Erro ao salvar o novo arquivo no servidor.</div>';
                    }
                } else {
                    $message = '<div class="alert danger"><i class="fas fa-times"></i> Formato inválido. Apenas PDF, JPG, JPEG e PNG são aceitos.</div>';
                }
            } else {
                $message = '<div class="alert danger"><i class="fas fa-times"></i> Documento não encontrado ou não está marcado como reprovado.</div>';
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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
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

        /* Modal Styles */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(4px);
            animation: fadeIn 0.2s ease-out;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
            animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }

        .modal-close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #94a3b8;
            cursor: pointer;
            transition: color 0.2s;
            line-height: 1;
            padding: 0;
        }

        .modal-close-btn:hover {
            color: #475569;
        }

        .custom-file-upload {
            border: 1px dashed #cbd5e1;
            padding: 10px;
            border-radius: 6px;
            background: #f8fafc;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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
                                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 5px;">
                                        <?php if (($doc['status'] ?? 'pending') === 'approved'): ?>
                                            <span style="background: #198754; color: white; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700;">APROVADO</span>
                                        <?php elseif (($doc['status'] ?? 'pending') === 'rejected'): ?>
                                            <span style="background: #dc3545; color: white; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700;">REPROVADO</span>
                                            
                                            <!-- Formulário inline para substituição do documento rejeitado -->
                                            <form method="post" enctype="multipart/form-data" style="margin: 0; display: inline-block;">
                                                <input type="hidden" name="replace_doc_id" value="<?php echo $doc['id']; ?>">
                                                <input type="file" name="new_document" accept=".pdf,image/*" required style="display: none;" id="replace-upload-<?php echo $doc['id']; ?>" onchange="this.form.submit()">
                                                <label for="replace-upload-<?php echo $doc['id']; ?>" class="btn btn-outline" style="font-size: 0.65rem; padding: 3px 8px; cursor: pointer; border-color: #d97706; color: #d97706; display: inline-flex; align-items: center; gap: 4px; background: white; font-weight: 700; transition: all 0.2s; border-radius: 4px; margin-top: 3px;">
                                                    <i class="fas fa-sync-alt"></i> Substituir
                                                </label>
                                            </form>
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
                                            <?php 
                                                $demandLabel = "Específica";
                                                $demandColor = "#3b82f6"; // Blue
                                                $demandIcon = "location-dot";
                                                
                                                if (($route['demand_type'] ?? '') === 'padrao') {
                                                    $demandLabel = "Padrão";
                                                    $demandColor = "#8b5cf6"; // Purple
                                                    $demandIcon = "map";
                                                } elseif (($route['demand_type'] ?? '') === 'mista') {
                                                    $demandLabel = "Mista";
                                                    $demandColor = "#f59e0b"; // Orange
                                                    $demandIcon = "layer-group";
                                                }
                                            ?>
                                            <span style="background: <?php echo $demandColor; ?>15; color: <?php echo $demandColor; ?>; font-size: 0.65rem; font-weight: 800; padding: 3px 10px; border-radius: 10px; border: 1px solid <?php echo $demandColor; ?>40; display: inline-flex; align-items: center; gap: 4px; text-transform: uppercase; letter-spacing: 0.05em; vertical-align: middle;">
                                                <i class="fas fa-<?php echo $demandIcon; ?>" style="font-size: 0.7rem;"></i> Tarefa <?php echo $demandLabel; ?>
                                            </span>
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
                                        
                                        <?php if ($route['status'] === 'completed' && !empty($route['completed_at'])): ?>
                                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 0.75rem; border-radius: 8px; display: flex; align-items: center; gap: 8px; color: #15803d; font-size: 0.8rem; font-weight: 600; margin-top: -5px;">
                                            <i class="fas fa-calendar-check" style="font-size: 1rem; color: #16a34a;"></i>
                                            <div>
                                                <small style="font-size: 0.6rem; color: #166534; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 2px;">Data da Conclusão</small>
                                                <?php echo date('d/m/Y \à\s H:i', strtotime($route['completed_at'])); ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>

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

                                        <!-- Seção de Instruções e Detalhamento da Rota -->
                                        <?php 
                                        $showDesc = !empty($route['description']);
                                        $showArea = in_array($route['demand_type'], ['padrao', 'mista']) && !empty($route['area_details']) && $route['area_details'] !== '<p><br></p>';
                                        
                                        if ($showDesc || $showArea): 
                                        ?>
                                        <div style="background: #f8fafc; padding: 0.75rem; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 10px;">
                                            <h4 style="font-size: 0.65rem; color: #475569; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin: 0; display: flex; align-items: center; gap: 6px;">
                                                <i class="fas fa-info-circle"></i> Instruções da Rota
                                            </h4>
                                            
                                            <?php if ($showArea): ?>
                                                <div>
                                                    <small style="font-size: 0.6rem; color: #dc3545; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 2px;">Descrição da Área de Atuação</small>
                                                    <div class="area-details-preview" style="font-size: 0.8rem; color: var(--slate-700); line-height: 1.4;"><?php echo trim($route['area_details']); ?></div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($showDesc): ?>
                                                <div>
                                                    <small style="font-size: 0.6rem; color: #dc3545; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 2px;">Instruções Complementares</small>
                                                    <div style="font-size: 0.8rem; color: var(--slate-700); line-height: 1.4; white-space: pre-line;"><?php echo htmlspecialchars(trim($route['description'])); ?></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>

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
                                                <a href="<?php echo str_replace('../../', BASE_URL, htmlspecialchars($file)); ?>" target="_blank" 
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
                                                    <button type="button" class="btn" 
                                                            onclick="openCompleteModal(<?php echo $route['id']; ?>, '<?php echo htmlspecialchars($route['title'], ENT_QUOTES, 'UTF-8'); ?>')" 
                                                            style="width: 100%; background: #2563eb; border-color: #2563eb; color: white; padding: 0.75rem; font-size: 0.75rem; font-weight: 600;">
                                                        <i class="fas fa-check-circle"></i> CONCLUIR ROTA (ENVIAR RELATÓRIO)
                                                    </button>
                                                </div>
                                            <?php elseif ($route['status'] === 'completed'): ?>
                                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                                    <a href="generate_contract.php?route_id=<?php echo $route['id']; ?>" target="_blank" class="btn" title="Termo de Registro de Demanda gerado no aceite" style="width: 100%; padding: 0.6rem; font-size: 0.75rem; color: white; border-color: #28a745; background: #28a745;">
                                                        <i class="fas fa-file-signature"></i> TERMO DE ACEITE (PDF)
                                                    </a>
                                                    <div style="background: var(--success-light); color: var(--success); padding: 0.75rem; border-radius: 8px; text-align: center; font-weight: 700; font-size: 0.8rem; border: 1px solid var(--success-light);">
                                                        <i class="fas fa-check-double"></i> ROTA CONCLUÍDA E ENVIADA
                                                    </div>
                                                    
                                                    <!-- Meus Envios de Comprovação -->
                                                    <div style="background: #fdfdfd; padding: 0.8rem; border-radius: 8px; border: 1px solid var(--border); font-size: 0.8rem; color: #475569; margin-top: 5px;">
                                                        <strong style="color: var(--success); display: flex; align-items: center; gap: 4px; margin-bottom: 0.5rem;">
                                                            <i class="fas fa-file-invoice"></i> Minha Comprovação Enviada
                                                        </strong>
                                                        
                                                        <div style="margin-bottom: 0.5rem; line-height: 1.4;">
                                                            <small style="font-size: 0.6rem; color: var(--slate-500); font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 2px;">Relatório de Execução</small>
                                                            <div style="color: var(--slate-700); white-space: pre-line;"><?php echo htmlspecialchars(trim($route['observation'] ?? 'Sem observações.')); ?></div>
                                                        </div>
                                                        
                                                        <?php
                                                        $myFiles = array_filter([$route['report_file_1'], $route['report_file_2'], $route['report_file_3']]);
                                                        if (!empty($myFiles)):
                                                        ?>
                                                            <div>
                                                                <small style="font-size: 0.6rem; color: var(--slate-500); font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 4px;">Arquivos Comprovantes</small>
                                                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                                                    <?php foreach ($myFiles as $idx => $f): 
                                                                        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                                                                        $icon = ($ext === 'pdf') ? 'fa-file-pdf' : 'fa-file-image';
                                                                        $link = str_replace('../../', BASE_URL, htmlspecialchars($f));
                                                                    ?>
                                                                        <a href="<?php echo $link; ?>" target="_blank" class="btn btn-outline" 
                                                                           style="font-size: 0.65rem; padding: 4px 8px; border-color: var(--success); color: var(--success); display: inline-flex; align-items: center; gap: 4px; background: white; text-decoration: none;">
                                                                            <i class="fas <?php echo $icon; ?>"></i> Anexo <?php echo $idx + 1; ?>
                                                                        </a>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
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

    <!-- Modal de Conclusão de Rota -->
    <div id="complete-route-modal" class="modal-backdrop" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-route-title" style="margin: 0; color: var(--primary-teal); font-weight: 700; font-size: 1.1rem;">Concluir Rota</h3>
                <button type="button" onclick="closeCompleteModal()" class="modal-close-btn">&times;</button>
            </div>
            <form method="post" enctype="multipart/form-data" style="margin: 0;">
                <input type="hidden" name="complete_route_id" id="modal-route-id" value="">
                
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 0.5rem; color: #475569;">
                        <i class="fas fa-align-left"></i> Relatório de Execução / Observações:
                    </label>
                    <textarea name="observation" required class="form-control" rows="6" 
                              style="font-size: 0.9rem; border-radius: 6px; padding: 10px; width: 100%; border: 1px solid #cbd5e1; box-sizing: border-box;" 
                              placeholder="Descreva detalhadamente as atividades realizadas nesta rota, visitas, coletas e eventuais ocorrências..."></textarea>
                </div>
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 0.5rem; color: #475569;">
                        <i class="fas fa-images"></i> Comprovantes, Fotos e Relatórios Finais (PDF ou Imagem, máx. 3 arquivos):
                    </label>
                    <div style="display: grid; grid-template-columns: 1fr; gap: 10px;">
                        <div class="custom-file-upload">
                            <i class="fas fa-upload" style="color: #64748b;"></i>
                            <span style="font-size: 0.8rem; color: #64748b; font-weight: 600;">Arquivo 1 (Obrigatório)</span>
                            <input type="file" name="report_file_1" accept=".pdf,image/*" required style="font-size: 0.8rem; width: 100%; margin-top: 5px;">
                        </div>
                        <div class="custom-file-upload">
                            <i class="fas fa-upload" style="color: #64748b;"></i>
                            <span style="font-size: 0.8rem; color: #64748b; font-weight: 600;">Arquivo 2 (Opcional)</span>
                            <input type="file" name="report_file_2" accept=".pdf,image/*" style="font-size: 0.8rem; width: 100%; margin-top: 5px;">
                        </div>
                        <div class="custom-file-upload">
                            <i class="fas fa-upload" style="color: #64748b;"></i>
                            <span style="font-size: 0.8rem; color: #64748b; font-weight: 600;">Arquivo 3 (Opcional)</span>
                            <input type="file" name="report_file_3" accept=".pdf,image/*" style="font-size: 0.8rem; width: 100%; margin-top: 5px;">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer" style="display: flex; gap: 10px; justify-content: flex-end; border-top: 1px solid #e2e8f0; padding-top: 1.25rem;">
                    <button type="button" onclick="closeCompleteModal()" class="btn btn-outline" style="border-color: #cbd5e1; color: #64748b; font-size: 0.85rem; padding: 0.6rem 1.2rem; background: white; border-radius: 4px; cursor: pointer;">Cancelar</button>
                    <button type="submit" class="btn" style="background: var(--success); border-color: var(--success); font-size: 0.85rem; padding: 0.6rem 1.2rem; color: white; border-radius: 4px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                        <i class="fas fa-check-circle"></i> Concluir Rota
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openCompleteModal(routeId, routeTitle) {
        document.getElementById('modal-route-id').value = routeId;
        document.getElementById('modal-route-title').innerText = 'Concluir Rota: ' + routeTitle;
        document.getElementById('complete-route-modal').style.display = 'flex';
    }

    function closeCompleteModal() {
        document.getElementById('complete-route-modal').style.display = 'none';
    }

    // Fechar ao clicar fora do modal
    window.onclick = function(event) {
        var modal = document.getElementById('complete-route-modal');
        if (event.target == modal) {
            closeCompleteModal();
        }
    }
    </script>
</body>

</html>