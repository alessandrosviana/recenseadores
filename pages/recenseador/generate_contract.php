<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$route_id = isset($_GET['route_id']) ? (int)$_GET['route_id'] : 0;

if (!$route_id) {
    die("Rota não especificada.");
}

// Buscar dados da rota e do recenseador
$stmt = $pdo->prepare("
    SELECT r.*, u.name, u.cpf, u.rg, u.address, u.city, u.state, u.cep, u.email, u.phone, u.processo_sei, u.contrato as num_contrato
    FROM routes r
    JOIN users u ON r.user_id = u.id
    WHERE r.id = ?
");
$stmt->execute([$route_id]);
$data = $stmt->fetch();

if (!$data) {
    die("Dados não encontrados.");
}

// Verificar se o usuário tem permissão (é o dono da rota ou admin)
if ($_SESSION['role'] !== 'admin' && $data['user_id'] != $_SESSION['user_id']) {
    die("Acesso negado.");
}

// Valores Base da Calculadora (Baseados na última versão implementada)
$gas_price = 6.36; // Valor base padrão
$km_unit = 1.39 + ($gas_price * 0.10);
$rates = [
    'escritorio' => 102.02,
    'alimentacao' => 46.35,
    'km' => round($km_unit, 2),
    'obras' => 7.61
];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Termo de Registro de Demanda - Rota #<?php echo $route_id; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
        .contract-page { background: white; width: 210mm; min-height: 297mm; margin: 5px auto; padding: 5mm 25mm 25mm 25mm; box-shadow: 0 0 10px rgba(0,0,0,0.1); box-sizing: border-box; }
        
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 25px; }
        .header img { 
            max-width: 280px; 
            height: auto; 
            display: inline-block;
        }
        
        .title { 
            text-align: center; 
            font-weight: 800; 
            text-transform: uppercase; 
            font-size: 20px; 
            margin: 20px 0; 
            padding: 15px;
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
            background: #fdfdfd;
            letter-spacing: 0.5px;
            line-height: 1.4;
            color: #1e293b;
        }
        
        .demand-banner {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 18px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .demand-type-label {
            font-size: 10px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            display: block;
            margin-bottom: 6px;
        }
        
        .demand-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: 800;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-padrao { background: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; }
        .badge-especifica { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-mista { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        .section { margin-bottom: 30px; }
        .section-title { font-weight: 700; text-transform: uppercase; font-size: 13px; border-bottom: 2px solid #f1f5f9; margin-bottom: 15px; color: #0f172a; padding-bottom: 5px; }
        
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .field { font-size: 12.5px; color: #334155; }
        .field strong { color: #64748b; font-weight: 600; margin-right: 5px; }
        
        .legal-text { font-size: 12px; text-align: justify; margin-top: 20px; color: #475569; }
        
        .table-rates { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 11.5px; }
        .table-rates th, .table-rates td { border: 1px solid #e2e8f0; padding: 10px; text-align: left; }
        .table-rates th { background: #f8fafc; color: #475569; font-weight: 700; }
        
        .signatures { margin-top: 60px; display: grid; grid-template-columns: 1fr 1fr; gap: 50px; text-align: center; }
        .sig-box { border-top: 1px solid #94a3b8; padding-top: 12px; font-size: 11px; color: #1e293b; }

        @media print {
            body { background: white; margin: 0; }
            .contract-page { margin: 0; box-shadow: none; border: none; padding: 5mm 15mm; }
            .no-print { display: none !important; }
            .no-print-bar { display: none !important; }
        }

        .no-print-bar { background: #1e293b; color: white; padding: 12px; text-align: center; position: sticky; top: 0; z-index: 100; display: flex; justify-content: center; align-items: center; gap: 20px; }
        .btn { background: #00bfa5; color: white; border: none; padding: 8px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; transition: all 0.2s; }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-print { background: #2563eb; }
    </style>
</head>
<body>

<div class="no-print-bar no-print">
    <span style="margin-right: 20px;">Visualização do Termo de Registro de Demanda</span>
    <button onclick="window.print()" class="btn btn-print">Imprimir Contrato (PDF)</button>
    <?php $dash_url = ($_SESSION['role'] === 'admin') ? '../admin/dashboard.php#monitor' : 'dashboard.php'; ?>
    <a href="<?php echo $dash_url; ?>" class="btn" style="background: #666; margin-left: 10px;">Voltar ao Painel</a>
</div>

<div class="contract-page">
    
    <div class="header">
        <img src="<?php echo BASE_URL; ?>assets/img/logo_caudf.png" alt="Logo CAU/DF">
    </div>


    <div class="title" style="margin-bottom: 15px;">Termo de Registro de Demanda</div>
    <div class="demand-banner">
        <div>
            <span class="demand-type-label">Classificação da Demanda</span>
            <?php 
                $dt = $data['demand_type'] ?? 'especifica';
                if ($dt === 'padrao'): ?>
                    <span class="demand-badge badge-padrao">Padrão (Área Aberta)</span>
                <?php elseif ($dt === 'especifica'): ?>
                    <span class="demand-badge badge-especifica">Específica (Endereços Fixos)</span>
                <?php else: ?>
                    <span class="demand-badge badge-mista">Mista (Híbrida)</span>
                <?php endif; ?>
        </div>
        <div style="text-align: right;">
            <span class="demand-type-label">Identificador</span>
            <span style="font-weight: 700; color: #334155;">#<?php echo $route_id; ?></span>
        </div>
    </div>


    <div class="section">
        <div class="section-title">1. Qualificação do Recenseador</div>
        <div class="grid">
            <div class="field"><strong>Nome:</strong> <?php echo htmlspecialchars($data['name']); ?></div>
            <div class="field"><strong>CPF:</strong> <?php echo htmlspecialchars($data['cpf']); ?></div>
            <div class="field"><strong>RG:</strong> <?php echo htmlspecialchars($data['rg']); ?></div>
            <div class="field"><strong>E-mail:</strong> <?php echo htmlspecialchars($data['email']); ?></div>
            <div class="field"><strong>Processo SEI:</strong> <?php echo htmlspecialchars($data['processo_sei']); ?></div>
            <div class="field"><strong>Nº Contrato:</strong> <?php echo htmlspecialchars($data['num_contrato']); ?></div>
        </div>
        <div class="field" style="margin-top: 5px;"><strong>Endereço:</strong> <?php echo htmlspecialchars($data['address'] . ", " . $data['city'] . " - " . $data['state'] . " (CEP: " . $data['cep'] . ")"); ?></div>
    </div>

    <div class="section">
        <div class="section-title">2. Detalhes da Rota / Demanda</div>
        <div class="field"><strong>Microrregião de Atuação:</strong> <?php echo htmlspecialchars($data['microregion'] ?? 'Não especificada'); ?></div>
        
        <?php if (($data['demand_type'] ?? 'especifica') !== 'padrao'): ?>
            <div class="field" style="margin-top: 5px;"><strong>Localização Prevista:</strong> <?php echo htmlspecialchars($data['start_location'] ?? ($data['address_street'] ? ($data['address_street'] . ', ' . $data['address_number']) : 'Não informada')); ?></div>
        <?php endif; ?>

        <?php 
            $mapUrl = !empty($data['google_maps_link']) ? $data['google_maps_link'] : (!empty($data['maps_url']) ? $data['maps_url'] : null);
            if ($mapUrl): 
        ?>
            <div class="field" style="margin-top: 5px; color: #0284c7;">
                <strong>Localização Exata (Google Maps):</strong> 
                <a href="<?php echo htmlspecialchars($mapUrl); ?>" target="_blank" style="color: #0284c7; text-decoration: none;">Clique aqui para abrir o mapa <i class="fas fa-external-link-alt" style="font-size: 10px;"></i></a>
            </div>
        <?php endif; ?>

        <?php if (!empty($data['area_details'])): ?>
            <div class="field" style="margin-top: 10px; padding: 10px; background: #f8f9fa; border: 1px solid #eee; border-radius: 4px;">
                <strong>Detalhamento da Área de Atuação:</strong>
                <div style="margin-top: 5px;"><?php echo $data['area_details']; // Contém HTML do Quill ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($data['description'])): ?>
            <div class="field" style="margin-top: 5px;"><strong>Instruções Complementares:</strong> <?php echo nl2br(htmlspecialchars($data['description'])); ?></div>
        <?php endif; ?>

        <?php 
            $refImage = !empty($data['ref_image']) ? $data['ref_image'] : (!empty($data['admin_file_1']) ? $data['admin_file_1'] : null);
            if ($refImage): 
                $file_ext = strtolower(pathinfo($refImage, PATHINFO_EXTENSION));
                if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])):
        ?>
            <div style="margin-top: 15px; text-align: center; border: 1px solid #ddd; padding: 5px; border-radius: 4px;">
                <div style="font-size: 10px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; color: #666; text-align: left;">
                    <i class="fas fa-map-marked-alt"></i> Mapa de Referência / Localização
                </div>
                <img src="<?php echo htmlspecialchars($refImage); ?>" style="max-width: 100%; max-height: 250px; border-radius: 2px;">
            </div>
        <?php endif; endif; ?>
    </div>

    <div class="section">
        <div class="section-title">3. Base de Cálculo Financeira (Referência ANEXO III)</div>
        <p style="font-size: 11px; margin: 5px 0;">Os valores de remuneração para esta rota seguirão a tabela de custos unitários abaixo, sujeitos à confirmação da execução pelo relatório final:</p>
        <table class="table-rates">
            <thead>
                <tr>
                    <th>Item de Remuneração</th>
                    <th>Unidade</th>
                    <th>Valor Unitário (R$)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Trabalho de Escritório / Relatório</td>
                    <td>Por Rota</td>
                    <td>R$ <?php echo number_format($rates['escritorio'], 2, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td>Deslocamento (KM Rodado - Base Gasolina R$ <?php echo number_format($gas_price, 2, ',', '.'); ?>)</td>
                    <td>Por KM</td>
                    <td>R$ <?php echo number_format($rates['km'], 2, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td>Auxílio Alimentação</td>
                    <td>Por Diária</td>
                    <td>R$ <?php echo number_format($rates['alimentacao'], 2, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td>Registro de Obras/Demandas Adicionais</td>
                    <td>Por Unidade</td>
                    <td>R$ <?php echo number_format($rates['obras'], 2, ',', '.'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">4. Compromisso e Prazo</div>
        <div class="legal-text">
            O RECENSEADOR acima qualificado declara aceitar a execução da rota descrita neste termo, comprometendo-se a realizar as vistorias técnicas com zelo, ética e profissionalismo, seguindo as orientações da Gerência de Fiscalização do CAU/DF. 
            <br><br>
            A conclusão dos trabalhos deverá ocorrer impreterivelmente até <strong><?php 
                $deadline = !empty($data['end_date']) ? $data['end_date'] : (!empty($data['scheduled_end']) ? $data['scheduled_end'] : null);
                echo $deadline ? date('d/m/Y', strtotime($deadline)) : 'Prazo não definido'; 
            ?></strong>, mediante envio de relatório circunstanciado e comprovantes através do sistema oficial. O não cumprimento dos prazos ou a execução em desacordo com as normas poderá acarretar em glosas ou sanções previstas no contrato principal.
        </div>
    </div>

    <div class="signatures">
        <div class="sig-box">
            <strong><?php echo htmlspecialchars($data['name']); ?></strong><br>
            Recenseador<br>
            (Aceite Digital em <?php echo !empty($data['accepted_at']) ? date('d/m/Y H:i', strtotime($data['accepted_at'])) : date('d/m/Y H:i'); ?>)
        </div>
        <div class="sig-box">
            <strong>GERFISC - CAU/DF</strong><br>
            Gerência de Fiscalização<br>
            Contratante
        </div>
    </div>

    <div style="margin-top: 40px; font-size: 10px; color: #888; text-align: center; border-top: 1px dashed #eee; padding-top: 10px;">
        Este documento foi gerado eletronicamente pelo Sistema de Gestão de Recenseadores CAU/DF.
        <br>Hash de Autenticidade: <?php echo strtoupper(md5($route_id . $data['user_id'] . $data['created_at'])); ?>
    </div>
</div>

</body>
</html>
