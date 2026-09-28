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

// Buscar dados do usuário
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("Usuário não encontrado.");
}

// Buscar documentos do usuário
$stmt = $pdo->prepare("SELECT * FROM documents WHERE user_id = ?");
$stmt->execute([$user_id]);
$documents = $stmt->fetchAll();

// Mapeamento de nomes amigáveis para campos
$fields = [
    'Dados Pessoais' => [
        'Nome' => $user['name'],
        'E-mail' => $user['email'],
        'CPF' => $user['cpf'],
        'RG' => $user['rg'],
        'Telefone' => $user['phone'],
        'Data de Nascimento' => !empty($user['birth_date']) ? date('d/m/Y', strtotime($user['birth_date'])) : 'Não informado',
        'Nacionalidade' => $user['nationality'],
        'Gênero' => $user['gender'],
    ],
    'Endereço' => [
        'Endereço' => $user['address'],
        'Cidade' => $user['city'],
        'Estado' => $user['state'],
        'CEP' => $user['cep'],
    ],
    'Formação e Atuação' => [
        'Escolaridade' => $user['education_level'],
        'Curso' => $user['course_detail'],
        'Macrorregião' => $user['microregion'],
    ],
    'Dados Administrativos' => [
        'Processo SEI' => $user['processo_sei'],
        'Nº Contrato' => $user['contrato'],
        'Status' => $user['status'] === 'approved' ? 'Aprovado' : ($user['status'] === 'pending' ? 'Pendente' : 'Reprovado'),
        'Data de Aprovação' => !empty($user['approved_at']) ? date('d/m/Y \à\s H:i', strtotime($user['approved_at'])) : 'Não aprovado',
        'Acesso ao Sistema' => $user['is_active'] ? 'Ativo (Habilitado)' : 'Inativo (Suspenso)',
    ]
];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil do Recenseador - <?php echo htmlspecialchars($user['name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/regular/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        .profile-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .profile-header {
            background: var(--surface-1);
            padding: 1.75rem 2rem;
            border-radius: var(--radius);
            border: 1px solid var(--border-subtle);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .user-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .info-card {
            background: var(--surface-1);
            padding: 1.5rem;
            border-radius: var(--radius);
            border: 1px solid var(--border-subtle);
            box-shadow: var(--shadow-xs);
            transition: var(--transition);
        }

        .info-card:hover {
            box-shadow: var(--shadow-sm);
            border-color: var(--border-default);
        }

        .info-card h3 {
            margin-top: 0;
            margin-bottom: 1.25rem;
            font-size: 0.88rem;
            color: var(--ink-primary);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .info-card h3 .card-icon {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            background: var(--petrol-tint);
            color: var(--petrol);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            padding: 0.6rem 0;
            border-bottom: 1px solid var(--border-subtle);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--ink-tertiary);
            white-space: nowrap;
            flex-shrink: 0;
        }

        .info-value {
            font-size: 0.85rem;
            color: var(--ink-primary);
            font-weight: 600;
            text-align: right;
        }

        .docs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .doc-item {
            background: var(--surface-0);
            padding: 1.25rem 1rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-subtle);
            text-align: center;
            transition: var(--transition);
        }

        .doc-item:hover {
            border-color: rgba(0, 122, 137, 0.2);
            background: var(--petrol-tint);
            box-shadow: var(--shadow-xs);
        }

        .doc-icon {
            font-size: 1.8rem;
            color: var(--danger);
            margin-bottom: 0.5rem;
            display: block;
        }

        .doc-name {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--ink-secondary);
            display: block;
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 700;
            color: white;
            letter-spacing: 0.02em;
        }

        .status-approved { background: var(--success); }
        .status-pending { background: var(--warning); }
        .status-rejected { background: var(--danger); }

        .btn-action {
            font-weight: 700;
            font-size: 0.8rem;
            padding: 0.55rem 1.1rem;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-action:hover { transform: translateY(-2px); }

        .btn-print {
            background: var(--petrol-tint);
            color: var(--petrol);
            border: 1px solid rgba(0, 122, 137, 0.12);
        }
        .btn-print:hover { background: var(--petrol); color: white; }

        .btn-edit-profile {
            background: linear-gradient(135deg, var(--petrol) 0%, var(--petrol-deep) 100%);
            color: white;
            box-shadow: 0 2px 8px -2px rgba(0, 122, 137, 0.25);
        }
        .btn-edit-profile:hover { background: var(--petrol-deep); }

        .btn-back {
            background: var(--surface-1);
            color: var(--ink-tertiary);
            border: 1px solid var(--border-default);
        }
        .btn-back:hover { background: var(--surface-0); color: var(--ink-secondary); }

        @media print {
            .no-print { display: none; }
            .profile-container { margin: 0; max-width: 100%; }
        }
    </style>
</head>

<body style="background: var(--surface-0);">
    <?php include '../../includes/header.php'; ?>

    <div class="profile-container">
        <div class="profile-header">
            <div>
                <div style="display: flex; align-items: center; gap: 0.85rem; margin-bottom: 0.4rem; flex-wrap: wrap;">
                    <h1 style="margin: 0; color: var(--ink-primary); font-size: 1.5rem; font-weight: 800; letter-spacing: -0.01em;"><?php echo mb_strtoupper(htmlspecialchars($user['name']), 'UTF-8'); ?></h1>
                    <span class="badge status-<?php echo $user['status']; ?>">
                        <?php echo $user['status'] === 'approved' ? 'APROVADO' : ($user['status'] === 'pending' ? 'PENDENTE' : 'REPROVADO'); ?>
                    </span>
                </div>
                <p style="margin: 0; color: var(--ink-tertiary); font-size: 0.88rem; display: flex; align-items: center; gap: 5px;"><i class="ph ph-envelope" style="font-size: 0.85rem;"></i> <?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            <div class="no-print" style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
                <button onclick="window.print()" class="btn-action btn-print">
                    <i class="ph ph-printer"></i> IMPRIMIR
                </button>
                <a href="edit_user.php?user_id=<?php echo $user['id']; ?>" class="btn-action btn-edit-profile">
                    <i class="ph ph-pencil-simple-line"></i> EDITAR
                </a>
                <a href="dashboard.php#users" class="btn-action btn-back">
                    <i class="ph ph-arrow-left"></i> VOLTAR
                </a>
            </div>
        </div>

        <div class="user-info-grid">
            <?php 
            $icons = [
                'Dados Pessoais' => 'ph-user',
                'Endereço' => 'ph-map-pin',
                'Formação e Atuação' => 'ph-graduation-cap',
                'Dados Administrativos' => 'ph-identification-card'
            ];
            foreach ($fields as $section => $data): ?>
                <div class="info-card">
                    <h3><span class="card-icon"><i class="ph <?php echo $icons[$section]; ?>"></i></span> <?php echo $section; ?></h3>
                    <?php foreach ($data as $label => $value): ?>
                        <div class="info-item">
                            <span class="info-label"><?php echo $label; ?></span>
                            <span class="info-value"><?php echo htmlspecialchars($value ?? 'N/A'); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="info-card" style="margin-bottom: 1.5rem;">
            <h3><span class="card-icon"><i class="ph ph-folder-open"></i></span> Documentos Cadastrados</h3>
            <?php if (count($documents) > 0): ?>
                <div class="docs-grid">
                    <?php foreach ($documents as $doc): ?>
                        <div class="doc-item">
                            <i class="ph ph-file-pdf doc-icon"></i>
                            <span class="doc-name"><?php echo htmlspecialchars($doc['document_type']); ?></span>
                            <a href="serve_document.php?doc_id=<?php echo (int)$doc['id']; ?>" target="_blank" style="display: inline-flex; align-items: center; justify-content: center; gap: 4px; font-size: 0.72rem; font-weight: 700; color: var(--petrol); text-decoration: none; padding: 5px 10px; border-radius: var(--radius-xs); background: var(--petrol-tint); border: 1px solid rgba(0, 122, 137, 0.12); transition: all 0.18s var(--ease);" onmouseover="this.style.background='var(--petrol)';this.style.color='white'" onmouseout="this.style.background='var(--petrol-tint)';this.style.color='var(--petrol)'">
                                <i class="ph ph-arrow-square-out" style="font-size: 0.75rem;"></i> Visualizar
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: var(--ink-muted); font-size: 0.88rem;">Nenhum documento encontrado.</p>
            <?php endif; ?>
        </div>

        <?php if (!empty($user['additional_info'])): ?>
            <div class="info-card" style="margin-bottom: 1.5rem;">
                <h3><span class="card-icon"><i class="ph ph-chat-dots"></i></span> Informações Adicionais</h3>
                <p style="white-space: pre-line; color: var(--ink-secondary); line-height: 1.6; font-size: 0.88rem; margin: 0;">
                    <?php echo htmlspecialchars($user['additional_info']); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>

</html>
