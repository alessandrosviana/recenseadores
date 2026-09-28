<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

// Force login check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

if (!$user_id) {
    echo "Usuário não especificado.";
    exit();
}

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo "Usuário não encontrado.";
    exit();
}

// Fetch routes for this user
$routes_stmt = $pdo->prepare("SELECT * FROM routes WHERE user_id = ? ORDER BY created_at DESC");
$routes_stmt->execute([$user_id]);
$routes = $routes_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rotas de <?php echo mb_strtoupper(htmlspecialchars($user['name']), 'UTF-8'); ?> - CAU/DF</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/regular/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        .page-header {
            background: var(--surface-1);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .route-card {
            background: var(--surface-1);
            border: 1px solid var(--border-emphasis);
            border-radius: var(--radius);
            margin-bottom: 2.5rem;
            padding: 1.5rem;
            border-left: 5px solid var(--petrol);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }

        .route-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .status-pending_acceptance {
            border-left-color: var(--warning);
        }

        .status-accepted {
            border-left-color: var(--info);
        }

        .status-in_progress {
            border-left-color: var(--success);
        }

        .status-completed {
            border-left-color: var(--petrol);
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: var(--radius-xs);
            font-size: 0.68rem;
            font-weight: 700;
            color: white;
            letter-spacing: 0.03em;
        }

        .badge-pending_acceptance { background: var(--warning); }
        .badge-accepted { background: var(--info); }
        .badge-in_progress { background: var(--success); }
        .badge-completed { background: var(--petrol); }

        .btn-edit {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--petrol-tint);
            border: 1px solid rgba(0, 122, 137, 0.12);
            color: var(--petrol);
            padding: 0.3rem 0.7rem;
            border-radius: var(--radius-xs);
            text-decoration: none;
            font-size: 0.78rem;
            font-weight: 700;
            transition: all 0.18s var(--ease);
            margin-right: 8px;
        }

        .btn-edit:hover {
            background: var(--petrol);
            color: white;
        }
    </style>
</head>

<body style="background: var(--surface-0);">
    <?php include '../../includes/header.php'; ?>

    <main class="container" style="padding-top: 2rem; padding-bottom: 4rem;">

        <div class="page-header mb-4">
            <div>
                <a href="dashboard.php#users" style="display: inline-flex; align-items: center; gap: 5px; font-size: 0.78rem; font-weight: 700; color: var(--petrol); text-decoration: none; padding: 6px 12px; border-radius: var(--radius-xs); background: var(--petrol-tint); border: 1px solid rgba(0, 122, 137, 0.12); transition: all 0.18s var(--ease); margin-bottom: 0.6rem; display: inline-flex;" onmouseover="this.style.background='var(--petrol)';this.style.color='white'" onmouseout="this.style.background='var(--petrol-tint)';this.style.color='var(--petrol)'">
                    <i class="ph ph-arrow-left"></i> Voltar ao Painel
                </a>
                <h2 style="color: var(--petrol-deep); margin: 0; font-size: 1.15rem; font-weight: 700;">Rotas de:
                    <?php echo mb_strtoupper(htmlspecialchars($user['name']), 'UTF-8'); ?>
                </h2>
                <p style="margin-top:0.4rem; font-size:0.85rem; color: var(--ink-tertiary); display: flex; align-items: center; gap: 5px;">
                    <i class="ph ph-envelope" style="font-size: 0.8rem;"></i> <?php echo htmlspecialchars($user['email']); ?> <span style="color: var(--ink-muted);">|</span> <i class="ph ph-map-pin" style="font-size: 0.8rem;"></i> <?php echo htmlspecialchars($user['city']); ?>
                </p>
            </div>
        </div>

        <?php if (count($routes) > 0): ?>
            <div style="display: flex; flex-direction: column; gap: 2rem;">
                <?php foreach ($routes as $routeIdx => $route): ?>
                    <div class="route-card status-<?php echo $route['status']; ?>" style="padding: 0; overflow: hidden;">
                        <!-- Card Header -->
                        <div style="padding: 1.1rem 1.25rem; border-bottom: 1px solid var(--border-subtle); background: var(--surface-0); display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 200px;">
                                <div style="display: flex; align-items: center; gap: 0.6rem;">
                                    <span style="width: 28px; height: 28px; border-radius: 50%; background: var(--petrol); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800; flex-shrink: 0;"><?php echo $routeIdx + 1; ?></span>
                                    <h3 style="margin:0; font-size: 1rem; color: var(--ink-primary); font-weight: 700; line-height: 1.3;">
                                        <?php echo htmlspecialchars($route['title']); ?>
                                    </h3>
                                </div>
                                <?php if (!empty($route['microregion'])): ?>
                                    <p style="margin:4px 0 0 0; color: var(--petrol); font-weight: 600; font-size: 0.78rem; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="ph ph-map-pin" style="font-size: 0.75rem;"></i> <?php echo htmlspecialchars($route['microregion']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <?php 
                                    $demandLabel = "Específica";
                                    $demandColor = "#3b82f6";
                                    $demandIcon = "map-pin";
                                    if (($route['demand_type'] ?? '') === 'padrao') { $demandLabel = "Padrão"; $demandColor = "#8b5cf6"; $demandIcon = "map"; }
                                    elseif (($route['demand_type'] ?? '') === 'mista') { $demandLabel = "Mista"; $demandColor = "#f59e0b"; $demandIcon = "stack"; }
                                ?>
                                <span style="background: <?php echo $demandColor; ?>15; color: <?php echo $demandColor; ?>; font-size: 0.62rem; font-weight: 800; padding: 3px 8px; border-radius: var(--radius-xs); border: 1px solid <?php echo $demandColor; ?>30; text-transform: uppercase; letter-spacing: 0.03em; display: inline-flex; align-items: center; gap: 3px;">
                                    <i class="ph ph-<?php echo $demandIcon; ?>" style="font-size: 0.65rem;"></i> <?php echo $demandLabel; ?>
                                </span>
                                <span class="badge badge-<?php echo $route['status']; ?>">
                                    <?php
                                    if ($route['status'] == 'pending_acceptance') echo 'AGUARDANDO';
                                    elseif ($route['status'] == 'accepted') echo 'ACEITA';
                                    elseif ($route['status'] == 'in_progress') echo 'EM ANDAMENTO';
                                    else echo strtoupper($route['status']);
                                    ?>
                                </span>
                                <a href="edit_route.php?id=<?php echo $route['id']; ?>" class="btn-edit"><i class="ph ph-pencil-simple-line"></i> Editar</a>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div style="padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem;">
                            <?php if (!empty($route['area_details']) && $route['area_details'] !== '<p><br></p>'): ?>
                                <div style="background: var(--surface-0); padding: 0.85rem 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                                    <div style="font-size: 0.68rem; font-weight: 700; color: var(--ink-tertiary); text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 6px; display: flex; align-items: center; gap: 4px;"><i class="ph ph-text-align-left" style="font-size: 0.75rem; color: var(--petrol);"></i> Descrição da Área de Atuação</div>
                                    <div style="font-size: 0.85rem; color: var(--ink-secondary); line-height: 1.5;">
                                        <?php echo sanitize_html($route['area_details'] ?? ''); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <!-- Local/Endereço -->
                                <div style="background: var(--surface-0); padding: 0.85rem 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                                    <div style="font-size: 0.68rem; font-weight: 700; color: var(--ink-tertiary); text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 6px; display: flex; align-items: center; gap: 4px;"><i class="ph ph-map-pin" style="font-size: 0.75rem; color: var(--petrol);"></i> Local / Endereço</div>
                                    <div style="font-size: 0.82rem; color: var(--ink-secondary); line-height: 1.5;">
                                        <?php
                                        $cleanLoc = trim($route['start_location'] ?? '', ', - ');
                                        if (!empty($route['address_street'])) {
                                            echo htmlspecialchars($route['address_street']) . ", " . htmlspecialchars($route['address_number']);
                                            if (!empty($route['address_complement']))
                                                echo " - " . htmlspecialchars($route['address_complement']);
                                            echo "<br>" . htmlspecialchars($route['address_neighborhood']) . " - " . htmlspecialchars($route['address_city']) . "/" . htmlspecialchars($route['address_state']);
                                            echo "<br><span style='color: var(--ink-tertiary);'>CEP: " . htmlspecialchars($route['address_cep']) . "</span>";
                                        } elseif (!empty($cleanLoc)) {
                                            echo htmlspecialchars($route['start_location']);
                                        } else {
                                            echo "Área de Atuação";
                                        }
                                        ?>
                                    </div>
                                    <?php 
                                        $mapUrl = !empty($route['google_maps_link']) ? $route['google_maps_link'] : (!empty($route['maps_url']) ? $route['maps_url'] : null);
                                        if ($mapUrl): 
                                    ?>
                                        <a href="<?php echo htmlspecialchars($mapUrl); ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 5px; font-size: 0.72rem; font-weight: 700; color: var(--info); text-decoration: none; padding: 4px 10px; border-radius: var(--radius-xs); background: var(--info-light); border: 1px solid rgba(59, 130, 246, 0.12); transition: all 0.18s var(--ease); margin-top: 8px;" onmouseover="this.style.background='var(--info)';this.style.color='white'" onmouseout="this.style.background='var(--info-light)';this.style.color='var(--info)'">
                                            <i class="ph ph-google-logo" style="font-size: 0.75rem;"></i> Abrir no Google Maps
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <!-- Descrição -->
                                <div style="background: var(--surface-0); padding: 0.85rem 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                                    <div style="font-size: 0.68rem; font-weight: 700; color: var(--ink-tertiary); text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 6px; display: flex; align-items: center; gap: 4px;"><i class="ph ph-info" style="font-size: 0.75rem; color: var(--petrol);"></i> Descrição</div>
                                    <div style="font-size: 0.82rem; color: var(--ink-secondary); line-height: 1.5;">
                                        <?php echo nl2br(htmlspecialchars($route['description'])); ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Datas -->
                            <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.68rem; font-weight: 600; color: var(--ink-tertiary); background: var(--surface-0); padding: 4px 10px; border-radius: var(--radius-xs); border: 1px solid var(--border-subtle);">
                                    <i class="ph ph-calendar-plus" style="font-size: 0.7rem; color: var(--petrol);"></i> Atribuída: <?php echo date('d/m/Y H:i', strtotime($route['created_at'])); ?>
                                </span>
                                <?php if (!empty($route['accepted_at'])): ?>
                                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.68rem; font-weight: 600; color: var(--info); background: var(--info-light); padding: 4px 10px; border-radius: var(--radius-xs); border: 1px solid rgba(59, 130, 246, 0.12);">
                                    <i class="ph ph-hand-tap" style="font-size: 0.7rem;"></i> Aceita: <?php echo date('d/m/Y H:i', strtotime($route['accepted_at'])); ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($route['status'] == 'completed' && !empty($route['completed_at'])): ?>
                                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.68rem; font-weight: 600; color: var(--success); background: var(--success-light); padding: 4px 10px; border-radius: var(--radius-xs); border: 1px solid rgba(13, 157, 108, 0.12);">
                                    <i class="ph ph-check-circle" style="font-size: 0.7rem;"></i> Finalizada: <?php echo date('d/m/Y H:i', strtotime($route['completed_at'])); ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <!-- Anexos Admin -->
                            <?php 
                            $admFiles = array_filter([$route['admin_file_1'] ?? null, $route['admin_file_2'] ?? null, $route['admin_file_3'] ?? null]);
                            if (!empty($admFiles)): 
                            ?>
                                <div style="display: flex; gap: 0.4rem; flex-wrap: wrap; align-items: center;">
                                    <span style="font-size: 0.68rem; font-weight: 700; color: var(--ink-tertiary); text-transform: uppercase; letter-spacing: 0.03em; display: flex; align-items: center; gap: 4px;"><i class="ph ph-paperclip" style="font-size: 0.7rem; color: var(--warning);"></i> Anexos Admin:</span>
                                    <?php foreach ($admFiles as $f): ?>
                                        <a href="<?php echo str_replace('../../', BASE_URL, htmlspecialchars($f)); ?>" target="_blank" style="font-size: 0.68rem; font-weight: 600; color: var(--warning); text-decoration: none; padding: 3px 8px; border-radius: var(--radius-xs); background: var(--warning-light); border: 1px solid rgba(217, 119, 6, 0.12); display: inline-flex; align-items: center; gap: 3px; transition: all 0.18s var(--ease);" onmouseover="this.style.background='var(--warning)';this.style.color='white'" onmouseout="this.style.background='var(--warning-light)';this.style.color='var(--warning)'">
                                            <i class="ph ph-file" style="font-size: 0.7rem;"></i> Anexo
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                        <?php if ($route['status'] == 'completed'): ?>
                            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed var(--border-default); font-size: 0.88rem;">
                                <strong style="color: var(--success); font-size: 0.82rem;"><i class="ph ph-checks"></i> Relatório de Conclusão:</strong>
                                <p style="background: var(--success-light); padding: 0.8rem; border-radius: var(--radius-sm); border: 1px solid rgba(13, 157, 108, 0.12); margin-top: 0.5rem; color: var(--ink-secondary); line-height: 1.5;">
                                    <?php echo nl2br(htmlspecialchars($route['observation'] ?? 'Sem observações.')); ?>
                                </p>

                                <?php
                                $files = [];
                                if (!empty($route['report_file_1']))
                                    $files[] = $route['report_file_1'];
                                if (!empty($route['report_file_2']))
                                    $files[] = $route['report_file_2'];
                                if (!empty($route['report_file_3']))
                                    $files[] = $route['report_file_3'];
                                ?>

                                <?php if (count($files) > 0): ?>
                                    <div style="margin-top: 0.5rem;">
                                        <strong style="font-size: 0.78rem; color: var(--ink-secondary);"><i class="ph ph-paperclip"></i> Anexos:</strong>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.3rem;">
                                            <?php foreach ($files as $index => $file): 
                                                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                                $icon = ($ext === 'pdf') ? 'file-pdf' : 'file-image';
                                            ?>
                                                <a href="<?php echo htmlspecialchars($file); ?>" target="_blank" style="font-size: 0.72rem; font-weight: 700; padding: 5px 10px; border-radius: var(--radius-xs); background: var(--success-light); color: var(--success); border: 1px solid rgba(13, 157, 108, 0.12); text-decoration: none; display: inline-flex; align-items: center; gap: 4px; transition: all 0.18s var(--ease);" onmouseover="this.style.background='var(--success)';this.style.color='white'" onmouseout="this.style.background='var(--success-light)';this.style.color='var(--success)'">
                                                    <i class="ph ph-<?php echo $icon; ?>" style="font-size: 0.75rem;"></i> Anexo <?php echo $index + 1; ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; border: 1px dashed var(--border-default); border-radius: var(--radius); background: var(--surface-1);">
                <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--surface-0); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                    <i class="ph ph-path" style="font-size: 1.6rem; color: var(--ink-muted);"></i>
                </div>
                <p style="color: var(--ink-tertiary); font-size: 0.9rem; margin: 0 0 1rem 0;">Este recenseador ainda não possui rotas atribuídas.</p>
                <a href="dashboard.php#routes" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; font-weight: 700; color: white; background: linear-gradient(135deg, var(--petrol) 0%, var(--petrol-deep) 100%); text-decoration: none; padding: 0.6rem 1.2rem; border-radius: var(--radius-sm); transition: var(--transition);" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">Atribuir Nova Rota</a>
            </div>
        <?php endif; ?>

    </main>
</body>

</html>