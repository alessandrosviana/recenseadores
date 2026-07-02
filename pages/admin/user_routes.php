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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        .page-header {
            background: #fff;
            padding: 2rem;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .route-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            margin-bottom: 1rem;
            padding: 1.5rem;
            border-left: 4px solid #ddd;
        }

        .status-pending_acceptance {
            border-left-color: #f0ad4e;
        }

        .status-in_progress {
            border-left-color: #28a745;
            background-color: #f9fff9;
        }

        .status-completed {
            border-left-color: #5bc0de;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
        }

        .badge-pending_acceptance {
            background: #f0ad4e;
        }

        .badge-in_progress {
            background: #28a745;
        }

        .badge-completed {
            background: #5bc0de;
        }

        .btn-edit {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #fff;
            border: 1px solid #0d6efd;
            color: #0d6efd;
            padding: 0.25rem 0.6rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.2s;
            margin-right: 8px;
        }

        .btn-edit:hover {
            background: #0d6efd;
            color: white;
        }
    </style>
</head>

<body style="background: #fcfcfc;">
    <?php include '../../includes/header.php'; ?>

    <main class="container" style="padding-top: 2rem; padding-bottom: 4rem;">

        <div class="page-header mb-4">
            <div>
                <a href="dashboard.php#users" class="btn btn-outline mb-2"
                    style="font-size: 0.8rem; padding: 0.3rem 0.8rem;">
                    <i class="fas fa-arrow-left"></i> Voltar ao Painel
                </a>
                <h2 style="color: var(--primary-teal); margin: 0;">Rotas de:
                    <?php echo mb_strtoupper(htmlspecialchars($user['name']), 'UTF-8'); ?>
                </h2>
                <p class="text-muted" style="margin-top:0.5rem; font-size:0.9rem;">
                    <?php echo htmlspecialchars($user['email']); ?> | <?php echo htmlspecialchars($user['city']); ?>
                </p>
            </div>
        </div>

        <?php if (count($routes) > 0): ?>
            <div class="grid grid-1">
                <?php foreach ($routes as $route): ?>
                    <div class="route-card status-<?php echo $route['status']; ?>">
                        <div style="display:flex; justify-content:space-between; margin-bottom: 1rem;">
                            <div>
                                <h3 style="margin:0; font-size: 1.2rem; color: #333;">
                                    <?php echo htmlspecialchars($route['title']); ?>
                                </h3>
                                <?php if (!empty($route['microregion'])): ?>
                                    <p
                                        style="margin:0.2rem 0 0 0; color: var(--primary-teal); font-weight: 600; font-size: 0.9rem;">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($route['microregion']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; align-items: center;">
                                <a href="edit_route.php?id=<?php echo $route['id']; ?>" class="btn-edit"><i
                                        class="fas fa-edit"></i> Editar</a>
                                <?php 
                                        $demandLabel = "Específica";
                                        $demandColor = "#3b82f6";
                                        if (($route['demand_type'] ?? '') === 'padrao') { $demandLabel = "Padrão"; $demandColor = "#8b5cf6"; }
                                        elseif (($route['demand_type'] ?? '') === 'mista') { $demandLabel = "Mista"; $demandColor = "#f59e0b"; }
                                    ?>
                                    <span style="background: <?php echo $demandColor; ?>20; color: <?php echo $demandColor; ?>; font-size: 0.7rem; font-weight: 800; padding: 2px 8px; border-radius: 4px; border: 1px solid <?php echo $demandColor; ?>40; margin-right: 8px; text-transform: uppercase; vertical-align: middle;">
                                        <?php echo $demandLabel; ?>
                                    </span>
                                    <span class="badge badge-<?php echo $route['status']; ?>">
                                    <?php
                                    if ($route['status'] == 'pending_acceptance')
                                        echo 'AGUARDANDO';
                                    elseif ($route['status'] == 'in_progress')
                                        echo 'EM ANDAMENTO';
                                    else
                                        echo strtoupper($route['status']);
                                    ?>
                                </span>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; color: #555; font-size: 0.95rem;">
                            <div>
                                <?php if (!empty($route['area_details']) && $route['area_details'] !== '<p><br></p>'): ?>
                                    <div style="margin-bottom: 1rem; background: #f8fafc; padding: 0.8rem; border-radius: 6px; border: 1px solid #e2e8f0; grid-column: span 2;">
                                        <strong style="color: #475569; display: block; margin-bottom: 5px;"><i class="fas fa-align-left"></i> Descrição da Área de Atuação:</strong>
                                        <div style="font-size: 0.9rem; color: #334155; line-height: 1.5;">
                                            <?php echo $route['area_details']; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <strong><i class="fas fa-map-marker-alt"></i> Local/Endereço:</strong><br>
                                <?php
                                $cleanLoc = trim($route['start_location'] ?? '', ', - ');
                                if (!empty($route['address_street'])) {
                                    echo htmlspecialchars($route['address_street']) . ", " . htmlspecialchars($route['address_number']);
                                    if (!empty($route['address_complement']))
                                        echo " - " . htmlspecialchars($route['address_complement']);
                                    echo "<br>" . htmlspecialchars($route['address_neighborhood']) . " - " . htmlspecialchars($route['address_city']) . "/" . htmlspecialchars($route['address_state']);
                                    echo "<br>CEP: " . htmlspecialchars($route['address_cep']);
                                } elseif (!empty($cleanLoc)) {
                                    echo htmlspecialchars($route['start_location']);
                                } else {
                                    echo "Área de Atuação";
                                }
                                ?>
                                
                                <?php 
                                    $mapUrl = !empty($route['google_maps_link']) ? $route['google_maps_link'] : (!empty($route['maps_url']) ? $route['maps_url'] : null);
                                    if ($mapUrl): 
                                ?>
                                    <div style="margin-top: 10px;">
                                        <a href="<?php echo htmlspecialchars($mapUrl); ?>" target="_blank" class="btn btn-outline" style="font-size: 0.8rem; padding: 0.4rem 0.8rem; color: #2563eb; border-color: #dbeafe; background: #eff6ff;">
                                            <i class="fab fa-google"></i> Abrir no Google Maps
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong><i class="fas fa-info-circle"></i> Descrição:</strong><br>
                                <?php echo nl2br(htmlspecialchars($route['description'])); ?>
                                <br><br>
                                <small>Atribuída em: <?php echo date('d/m/Y H:i', strtotime($route['created_at'])); ?></small>
                                <?php if ($route['status'] == 'completed' && !empty($route['completed_at'])): ?>
                                    <br><small>Finalizada em:
                                        <?php echo date('d/m/Y H:i', strtotime($route['completed_at'])); ?></small>
                                <?php endif; ?>

                                <?php 
                                $admFiles = array_filter([$route['admin_file_1'] ?? null, $route['admin_file_2'] ?? null, $route['admin_file_3'] ?? null]);
                                if (!empty($admFiles)): 
                                ?>
                                    <div style="margin-top: 1rem; border-top: 1px solid #eee; padding-top: 0.5rem; display: flex; gap: 10px;">
                                        <?php foreach ($admFiles as $f): ?>
                                            <a href="<?php echo str_replace('../../', BASE_URL, htmlspecialchars($f)); ?>" target="_blank" style="font-size: 0.8rem; color: #f57f17; text-decoration: none;">
                                                <i class="fas fa-paperclip"></i> Anexo Admin
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($route['status'] == 'completed'): ?>
                            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #ccc; font-size: 0.95rem;">
                                <strong style="color: #28a745;"><i class="fas fa-check-double"></i> Relatório de Conclusão:</strong>
                                <p
                                    style="background: #f8f9fa; padding: 0.8rem; border-radius: 4px; border: 1px solid #e9ecef; margin-top: 0.5rem;">
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
                                        <strong><i class="fas fa-paperclip"></i> Anexos:</strong>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.3rem;">
                                            <?php foreach ($files as $index => $file): ?>
                                                <a href="<?php echo htmlspecialchars($file); ?>" target="_blank" class="btn btn-outline"
                                                    style="font-size: 0.8rem; padding: 0.3rem 0.6rem;">
                                                    <i class="fas fa-file-pdf"></i> Anexo <?php echo $index + 1; ?>
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
            <div class="text-center py-5" style="border: 2px dashed #eee; border-radius: 8px;">
                <i class="fas fa-route" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                <p class="text-muted">Este recenseador ainda não possui rotas atribuídas.</p>
                <a href="dashboard.php#routes" class="btn btn-primary" style="margin-top: 1rem;">Atribuir Nova Rota</a>
            </div>
        <?php endif; ?>

    </main>
</body>

</html>