<?php
require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

if (!$user_id) {
    die("Usuário não especificado.");
}

$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

if (!$user) {
    die("Usuário não encontrado.");
}

// Handle document approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $doc_id = (int) ($_POST['doc_id'] ?? 0);

    if (($action === 'approve_doc' || $action === 'reject_doc') && $doc_id > 0) {
        $status = ($action === 'approve_doc') ? 'approved' : 'rejected';
        $stmt = $pdo->prepare("UPDATE documents SET status = ? WHERE id = ?");
        $stmt->execute([$status, $doc_id]);
    }
}

$docs_stmt = $pdo->prepare("SELECT * FROM documents WHERE user_id = ?");
$docs_stmt->execute([$user_id]);
$documents = $docs_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos de <?php echo htmlspecialchars($user['name']); ?> - CAU/DF</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/recenseadores/assets/css/style.css">
    <style>
        .page-header {
            background: #fff;
            padding: 2rem;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .user-details {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
            color: #555;
            font-size: 0.9rem;
        }

        .user-details div {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .doc-card {
            background: #fff;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
            overflow: hidden;
            transition: box-shadow 0.2s;
        }

        .doc-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .doc-header {
            background: #f8f9fa;
            padding: 1rem;
            border-bottom: 1px solid #eee;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .doc-preview {
            height: 200px;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .doc-preview iframe {
            width: 100%;
            height: 100%;
            border: none;
            pointer-events: none;
            /* Disable interaction in preview */
        }

        .doc-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .doc-preview .overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.02);
        }

        .doc-icon {
            font-size: 3rem;
            color: #adb5bd;
        }

        .doc-footer {
            padding: 1rem;
            text-align: center;
            border-top: 1px solid #eee;
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main class="container" style="padding-top: 2rem; padding-bottom: 4rem;">

        <div class="page-header mb-4">
            <div>
                <a href="admin/dashboard.php" class="btn btn-outline mb-2"
                    style="font-size: 0.8rem; padding: 0.3rem 0.8rem;">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
                <h2 style="color: var(--primary-teal); margin: 0;">
                    <?php echo htmlspecialchars($user['name']); ?>
                </h2>
                <div class="user-details">
                    <div><i class="fas fa-id-card"></i> CPF: <?php echo htmlspecialchars($user['cpf']); ?></div>
                    <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></div>
                    <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?></div>
                </div>
                <div style="margin-top: 0.8rem; display: flex; flex-direction: column; gap: 0.4rem;">
                    <div style="font-size: 0.9rem; color: #555;">
                        <i class="fas fa-map-marker-alt" style="width: 16px; color: var(--primary-teal);"></i> 
                        <strong>Endereço:</strong> <?php echo htmlspecialchars(($user['address'] ?? '') . ', ' . ($user['city'] ?? '') . ' - ' . ($user['state'] ?? '') . ' (CEP: ' . ($user['cep'] ?? '') . ')'); ?>
                    </div>
                    <?php 
                        $macroDisplay = $user['microregion'] ?? 'Não informada';
                        // Fix common encoding issues for "Macrorregião"
                        $macroDisplay = str_replace(['├ú', 'Ã£'], 'ã', $macroDisplay);
                    ?>
                    <div style="font-size: 0.9rem; color: #555;">
                        <i class="fas fa-layer-group" style="width: 16px; color: var(--primary-teal);"></i> 
                        <strong>Macrorregião Escolhida:</strong> <span style="color: var(--primary-teal); font-weight: 700;"><?php echo htmlspecialchars($macroDisplay); ?></span>
                    </div>
                </div>
            </div>

            <div class="actions" style="display: flex; align-items: center; gap: 0.8rem; flex-wrap: wrap; justify-content: flex-end;">
                <!-- Quick actions for admin -->
                <form method="post" action="admin/dashboard.php" style="display:flex; align-items:center; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end;" onsubmit="return confirm('Deseja aprovar este cadastro?');">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <input type="hidden" name="action" value="approve">
                    <input type="text" name="processo_sei" placeholder="Processo SEI" required class="form-control" style="width: 140px; padding: 0.4rem 0.5rem; height: 40px; font-size: 0.85rem; margin: 0;">
                    <input type="text" name="contrato" placeholder="Nº Contrato" required class="form-control" style="width: 140px; padding: 0.4rem 0.5rem; height: 40px; font-size: 0.85rem; margin: 0;">
                    <button type="submit" class="btn btn-primary" style="height: 40px; line-height: 1;"><i class="fas fa-check"></i> Aprovar Cadastro</button>
                </form>
                <form method="post" action="admin/dashboard.php" style="display:flex; justify-content: flex-end;"
                    onsubmit="return confirm('Tem certeza que deseja reprovar este cadastro?');">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn btn-outline" style="color: #dc3545; border-color: #dc3545; height: 40px;"><i
                            class="fas fa-times"></i> Reprovar Cadastro</button>
                </form>
            </div>
        </div>

        <?php if (count($documents) > 0): ?>
            <div class="grid grid-3">
                <?php foreach ($documents as $doc): ?>
                    <div class="doc-card">
                        <div class="doc-header" style="justify-content: space-between;">
                            <div>
                                <i class="fas fa-file-alt" style="color: var(--primary-teal);"></i>
                                <?php echo htmlspecialchars($doc['document_type']); ?>
                            </div>
                            <?php if (($doc['status'] ?? 'pending') === 'approved'): ?>
                                <span class="badge" style="background: #198754;">Aprovado</span>
                            <?php elseif (($doc['status'] ?? 'pending') === 'rejected'): ?>
                                <span class="badge" style="background: #dc3545;">Reprovado</span>
                            <?php else: ?>
                                <span class="badge" style="background: #ffc107; color: #000;">Pendente</span>
                            <?php endif; ?>
                        </div>

                        <div class="doc-preview">
                            <?php
                            // Adjust path for display: database likely stores relative to upload folder or full path
                            // If database has "../uploads/...", we need to make it reachable from browser.
                            // Browser is at /recenseadores/pages/view_docs.php
                            // Uploads are at /recenseadores/uploads/
                            // So we need relative path from view_docs.php -> ../uploads/
                            // Or absolute path /recenseadores/uploads/
                    
                            $filepath = $doc['file_path'];
                            // Normalize path specifically for web display
                            if (strpos($filepath, '../uploads') !== false) {
                                $displayPath = str_replace('../uploads', '../uploads', $filepath);
                                // Actually, if we are in /pages/, ../uploads is correct.
                            } else {
                                // Fallback if path is different
                                $displayPath = '../uploads/' . basename($filepath);
                            }

                            $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
                            ?>

                            <?php if ($ext === 'pdf'): ?>
                                <iframe src="<?php echo htmlspecialchars($displayPath); ?>#toolbar=0&navpanes=0&scrollbar=0"
                                    scrolling="no"></iframe>
                            <?php elseif (in_array($ext, ['jpg', 'jpeg', 'png'])): ?>
                                <img src="<?php echo htmlspecialchars($displayPath); ?>">
                            <?php else: ?>
                                <i class="fas fa-file doc-icon"></i>
                            <?php endif; ?>

                            <a href="<?php echo htmlspecialchars($displayPath); ?>" target="_blank" class="overlay"></a>
                        </div>

                        <div class="doc-footer" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <a href="<?php echo htmlspecialchars($displayPath); ?>" target="_blank" class="btn btn-outline"
                                style="flex: 1 1 100%; font-size: 0.9rem;">
                                <i class="fas fa-external-link-alt"></i> Visualizar/Baixar
                            </a>
                            <form method="post" style="flex: 1;">
                                <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                <input type="hidden" name="action" value="approve_doc">
                                <button type="submit" class="btn btn-primary"
                                    style="width: 100%; font-size: 0.8rem; padding: 0.4rem; background: #198754; border-color: #198754;">
                                    <i class="fas fa-check"></i> Aprovar
                                </button>
                            </form>
                            <form method="post" style="flex: 1;">
                                <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                <input type="hidden" name="action" value="reject_doc">
                                <button type="submit" class="btn btn-outline"
                                    style="width: 100%; font-size: 0.8rem; padding: 0.4rem; color: #dc3545; border-color: #dc3545;"
                                    onclick="return confirm('Tem certeza que deseja reprovar este documento?');">
                                    <i class="fas fa-times"></i> Reprovar
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-folder-open" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                <p class="text-muted">Este usuário ainda não enviou nenhum documento.</p>
            </div>
        <?php endif; ?>
    </main>
    <?php include '../includes/footer.php'; ?>
</body>

</html>