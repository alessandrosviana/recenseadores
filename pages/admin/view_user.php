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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        .profile-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .profile-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .user-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .info-card h3 {
            margin-top: 0;
            margin-bottom: 1.2rem;
            font-size: 1.1rem;
            color: var(--primary-teal);
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-item {
            margin-bottom: 0.8rem;
        }

        .info-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #999;
            text-transform: uppercase;
        }

        .info-value {
            display: block;
            font-size: 1rem;
            color: #333;
            font-weight: 600;
        }

        .docs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .doc-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #eee;
            text-align: center;
            transition: all 0.2s;
        }

        .doc-item:hover {
            border-color: var(--primary-teal);
            background: #f0fdfa;
        }

        .doc-icon {
            font-size: 2rem;
            color: #ef4444;
            margin-bottom: 0.5rem;
            display: block;
        }

        .doc-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: #444;
            display: block;
            margin-bottom: 0.5rem;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            color: white;
        }

        .status-approved { background: #22c55e; }
        .status-pending { background: #f59e0b; }
        .status-rejected { background: #ef4444; }

        @media print {
            .no-print { display: none; }
            .profile-container { margin: 0; max-width: 100%; }
        }
    </style>
</head>

<body style="background: #f8fafc;">
    <?php include '../../includes/header.php'; ?>

    <div class="profile-container">
        <div class="profile-header">
            <div>
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                    <h1 style="margin: 0; color: #1e293b; font-size: 1.8rem;"><?php echo mb_strtoupper(htmlspecialchars($user['name']), 'UTF-8'); ?></h1>
                    <span class="badge status-<?php echo $user['status']; ?>">
                        <?php echo $user['status'] === 'approved' ? 'APROVADO' : ($user['status'] === 'pending' ? 'PENDENTE' : 'REPROVADO'); ?>
                    </span>
                </div>
                <p style="margin: 0; color: #64748b;"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            <div class="no-print" style="display: flex; gap: 0.8rem;">
                <button onclick="window.print()" class="btn btn-print">
                    <i class="fas fa-print"></i> IMPRIMIR
                </button>
                <a href="edit_user.php?user_id=<?php echo $user['id']; ?>" class="btn btn-edit-profile">
                    <i class="fas fa-edit"></i> EDITAR
                </a>
                <a href="dashboard.php#users" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> VOLTAR
                </a>
            </div>
        </div>

        <style>
            /* Cores específicas para os botões do cabeçalho */
            .btn-print {
                background: #3b82f6 !important;
                color: white !important;
                border: none !important;
                box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
            }
            .btn-print:hover { background: #2563eb !important; transform: translateY(-2px); }

            .btn-edit-profile {
                background: #f59e0b !important;
                color: white !important;
                border: none !important;
                box-shadow: 0 4px 6px rgba(245, 158, 11, 0.2);
            }
            .btn-edit-profile:hover { background: #d97706 !important; transform: translateY(-2px); }

            .btn-back {
                background: #64748b !important;
                color: white !important;
                border: none !important;
                box-shadow: 0 4px 6px rgba(100, 116, 139, 0.2);
            }
            .btn-back:hover { background: #475569 !important; transform: translateY(-2px); }
            
            .btn { transition: all 0.2s ease; font-weight: 700; letter-spacing: 0.5px; }
        </style>

        <div class="user-info-grid">
            <?php 
            $icons = [
                'Dados Pessoais' => 'fa-user',
                'Endereço' => 'fa-map-marker-alt',
                'Formação e Atuação' => 'fa-graduation-cap',
                'Dados Administrativos' => 'fa-id-card'
            ];
            foreach ($fields as $section => $data): ?>
                <div class="info-card">
                    <h3><i class="fas <?php echo $icons[$section]; ?>"></i> <?php echo $section; ?></h3>
                    <?php foreach ($data as $label => $value): ?>
                        <div class="info-item">
                            <span class="info-label"><?php echo $label; ?></span>
                            <span class="info-value"><?php echo htmlspecialchars($value ?? 'N/A'); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="info-card" style="margin-bottom: 3rem;">
            <h3><i class="fas fa-folder-open"></i> Documentos Cadastrados</h3>
            <?php if (count($documents) > 0): ?>
                <div class="docs-grid">
                    <?php foreach ($documents as $doc): ?>
                        <div class="doc-item">
                            <i class="fas fa-file-pdf doc-icon"></i>
                            <span class="doc-name"><?php echo htmlspecialchars($doc['document_type']); ?></span>
                            <a href="../<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn btn-outline" style="font-size: 0.75rem; padding: 0.3rem 0.8rem; width: 100%;">
                                <i class="fas fa-external-link-alt"></i> Visualizar
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">Nenhum documento encontrado.</p>
            <?php endif; ?>
        </div>

        <?php if (!empty($user['additional_info'])): ?>
            <div class="info-card" style="margin-bottom: 3rem;">
                <h3><i class="fas fa-info-circle"></i> Informações Adicionais</h3>
                <p style="white-space: pre-line; color: #444; line-height: 1.6;">
                    <?php echo htmlspecialchars($user['additional_info']); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>

</html>
