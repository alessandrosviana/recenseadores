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
$gas_price = 6.36;
$km_unit = 1.39 + ($gas_price * 0.10);
$rates = [
    'escritorio' => 102.02,
    'alimentacao' => 46.35,
    'km' => round($km_unit, 2),
    'obras' => 7.61
];

$doc_hash = strtoupper(md5($route_id . $data['user_id'] . $data['created_at']));
$doc_date = date('d/m/Y');
$doc_time = date('H:i');

function format_cpf($cpf) {
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) === 11) {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }
    return $cpf;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Termo de Registro de Demanda - Rota #<?php echo $route_id; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/regular/style.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; line-height: 1.6; color: #0f172a; margin: 0; padding: 0; background: #e2e8f0; }

        .contract-page {
            background: #ffffff;
            width: 210mm;
            min-height: 297mm;
            margin: 10px auto;
            padding: 18mm 22mm;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }

        /* HEADER INSTITUCIONAL */
        .doc-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #007a89;
            padding-bottom: 18px;
            margin-bottom: 28px;
        }
        .doc-header img { max-width: 80px; height: auto; }
        .doc-meta {
            text-align: right;
            font-size: 9.5px;
            line-height: 1.7;
            color: #64748b;
        }
        .doc-meta strong { color: #0f172a; font-weight: 700; }
        .doc-meta-row { display: block; }

        /* TITULO */
        .doc-title {
            text-align: center;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 14px;
            margin: 0 0 24px 0;
            padding: 14px 0;
            border-top: none;
            border-bottom: 2px solid #0f172a;
            letter-spacing: 0.8px;
            color: #0f172a;
        }

        /* BANNER DE DEMANDA */
        .demand-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #007a89;
            padding: 14px 18px;
            border-radius: 6px;
            margin-bottom: 28px;
        }
        .demand-label { font-size: 9px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 4px; }
        .demand-badge { display: inline-block; padding: 5px 12px; border-radius: 4px; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.3px; }
        .badge-padrao { background: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; }
        .badge-especifica { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-mista { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        /* SECTIONS */
        .section { margin-bottom: 26px; }
        .section-title {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            color: #007a89;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-num {
            width: 22px; height: 22px;
            border-radius: 50%;
            background: #007a89;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 800;
            flex-shrink: 0;
        }

        /* CAMPOS */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 20px; }
        .field { font-size: 12px; color: #334155; line-height: 1.7; }
        .field strong { color: #64748b; font-weight: 600; }

        /* AREA DETAILS */
        .area-box { margin-top: 10px; padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; }
        .area-box-label { font-size: 9px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
        .area-box-content { font-size: 12px; color: #334155; line-height: 1.6; }

        /* TABELA */
        .table-rates { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11.5px; }
        .table-rates th { background: #007a89; color: white; padding: 10px 12px; text-align: left; font-weight: 700; font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.03em; }
        .table-rates td { border-bottom: 1px solid #e2e8f0; padding: 10px 12px; text-align: left; color: #334155; }
        .table-rates tr:nth-child(even) td { background: #f8fafc; }
        .table-rates td:last-child { font-weight: 700; color: #0f172a; text-align: right; font-variant-numeric: tabular-nums; }

        /* TEXTO LEGAL */
        .legal-text { font-size: 12px; text-align: justify; color: #334155; line-height: 1.7; }
        .legal-text strong { color: #0f172a; }

        /* MAPA */
        .map-box { margin-top: 14px; text-align: center; border: 1px solid #e2e8f0; padding: 8px; border-radius: 6px; }
        .map-box-label { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; color: #64748b; text-align: left; display: flex; align-items: center; gap: 4px; }

        /* ASSINATURAS */
        .signatures { margin-top: 70px; display: grid; grid-template-columns: 1fr 1fr; gap: 60px; text-align: center; }
        .sig-box { padding-top: 14px; font-size: 11px; color: #334155; line-height: 1.6; }
        .sig-line { border-top: 1.5px solid #0f172a; margin-bottom: 14px; }
        .sig-name { font-weight: 700; color: #0f172a; font-size: 12px; }
        .sig-role { color: #64748b; font-size: 10px; }

        /* RODAPE */
        .doc-footer {
            margin-top: 40px;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
            line-height: 1.7;
        }
        .doc-footer strong { color: #64748b; }

        /* BARRA DE ACAO */
        .no-print-bar { background: #0f172a; color: white; padding: 12px 20px; text-align: center; position: sticky; top: 0; z-index: 100; display: flex; justify-content: center; align-items: center; gap: 16px; font-size: 13px; }
        .action-btn { background: #007a89; color: white; border: none; padding: 8px 18px; border-radius: 6px; cursor: pointer; font-weight: 700; font-size: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .action-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 122, 137, 0.3); }
        .action-btn-secondary { background: #475569; }
        .action-btn-secondary:hover { box-shadow: 0 4px 12px rgba(71, 85, 105, 0.3); }

        @page {
            size: A4;
            margin: 14mm 16mm;
            @bottom-center {
                content: "Página " counter(page) " de " counter(pages);
                font-family: 'Inter', sans-serif;
                font-size: 8.5px;
                color: #94a3b8;
            }
        }

        @media print {
            body { background: white; margin: 0; }
            .contract-page { margin: 0; box-shadow: none; border: none; padding: 0; width: auto; min-height: auto; }
            .no-print, .no-print-bar { display: none !important; }
            .section { page-break-inside: avoid; }
            .signatures { page-break-inside: avoid; }
            .table-rates { page-break-inside: avoid; }
            .area-box { page-break-inside: avoid; }
            .map-box { page-break-inside: avoid; }
            p, .legal-text, .field { orphans: 3; widows: 3; }
        }
    </style>
</head>
<body>

<div class="no-print-bar no-print">
    <span>Visualização do Termo de Registro de Demanda</span>
    <button onclick="window.print()" class="action-btn"><i class="ph ph-printer"></i> Imprimir / Salvar PDF</button>
    <?php $dash_url = ($_SESSION['role'] === 'admin') ? '../admin/dashboard.php#monitor' : 'dashboard.php'; ?>
    <a href="<?php echo $dash_url; ?>" class="action-btn action-btn-secondary"><i class="ph ph-arrow-left"></i> Voltar</a>
</div>

<div class="contract-page">

    <!-- HEADER INSTITUCIONAL -->
    <div class="doc-header">
        <img src="<?php echo BASE_URL; ?>assets/img/logo-caudf-nova.png" alt="CAU/DF - Conselho de Arquitetura e Urbanismo do Distrito Federal">
        <div class="doc-meta">
            <span class="doc-meta-row"><strong>TERMO Nº:</strong> <?php echo str_pad($route_id, 5, '0', STR_PAD_LEFT); ?>/<?php echo date('Y'); ?></span>
            <span class="doc-meta-row"><strong>PROCESSO SEI:</strong> <?php echo htmlspecialchars($data['processo_sei'] ?? 'N/I'); ?></span>
            <span class="doc-meta-row"><strong>EMITIDO EM:</strong> <?php echo $doc_date; ?> às <?php echo $doc_time; ?></span>
            <span class="doc-meta-row"><strong>HASH:</strong> <?php echo $doc_hash; ?></span>
        </div>
    </div>

    <!-- TITULO -->
    <div class="doc-title">Termo de Registro de Demanda</div>

    <!-- BANNER DE DEMANDA -->
    <div class="demand-info">
        <div>
            <span class="demand-label">Classificação da Demanda</span>
            <?php $dt = $data['demand_type'] ?? 'especifica';
                if ($dt === 'padrao'): ?>
                <span class="demand-badge badge-padrao">Padrão (Área Aberta)</span>
            <?php elseif ($dt === 'especifica'): ?>
                <span class="demand-badge badge-especifica">Específica (Endereços Fixos)</span>
            <?php else: ?>
                <span class="demand-badge badge-mista">Mista (Híbrida)</span>
            <?php endif; ?>
        </div>
        <div style="text-align: right;">
            <span class="demand-label">Data de Atribuição</span>
            <span style="font-weight: 700; color: #0f172a; font-size: 12px;"><?php echo date('d/m/Y H:i', strtotime($data['created_at'])); ?></span>
            <?php if (!empty($data['accepted_at'])): ?>
                <span class="demand-label" style="margin-top: 8px;">Aceito em</span>
                <span style="font-weight: 700; color: #007a89; font-size: 12px; display: block;"><?php echo date('d/m/Y H:i', strtotime($data['accepted_at'])); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECAO 1: QUALIFICACAO -->
    <div class="section">
        <div class="section-title"><span class="section-num">1</span> Qualificação do Recenseador</div>
        <div class="grid-2">
            <div class="field"><strong>Nome:</strong> <?php echo mb_strtoupper(htmlspecialchars($data['name']), 'UTF-8'); ?></div>
            <div class="field"><strong>CPF:</strong> <?php echo format_cpf(htmlspecialchars($data['cpf'])); ?></div>
            <div class="field"><strong>RG:</strong> <?php echo htmlspecialchars($data['rg']); ?></div>
            <div class="field"><strong>E-mail:</strong> <?php echo htmlspecialchars($data['email']); ?></div>
            <div class="field"><strong>Telefone:</strong> <?php echo htmlspecialchars($data['phone']); ?></div>
            <div class="field"><strong>Nº Contrato:</strong> <?php echo htmlspecialchars($data['num_contrato']); ?></div>
        </div>
        <div class="field" style="margin-top: 8px;"><strong>Endereço:</strong> <?php echo htmlspecialchars($data['address'] . ", " . $data['city'] . " - " . $data['state'] . " (CEP: " . $data['cep'] . ")"); ?></div>
    </div>

    <!-- SECAO 2: DESCRICAO DA ROTA -->
    <div class="section">
        <div class="section-title"><span class="section-num">2</span> Descrição da Rota e Área de Atuação</div>
        <div class="field"><strong>Microrregião de Atuação:</strong> <?php echo htmlspecialchars($data['microregion'] ?? 'Não especificada'); ?></div>

        <?php if (($data['demand_type'] ?? 'especifica') !== 'padrao'): ?>
            <div class="field" style="margin-top: 6px;"><strong>Localização Prevista:</strong> <?php echo htmlspecialchars($data['start_location'] ?? ($data['address_street'] ? ($data['address_street'] . ', ' . $data['address_number']) : 'Não informada')); ?></div>
        <?php endif; ?>

        <?php $mapUrl = !empty($data['google_maps_link']) ? $data['google_maps_link'] : (!empty($data['maps_url']) ? $data['maps_url'] : null);
            if ($mapUrl): ?>
            <div class="field" style="margin-top: 6px; color: #007a89;">
                <strong style="color: #64748b;">Localização Exata:</strong>
                <a href="<?php echo htmlspecialchars($mapUrl); ?>" target="_blank" style="color: #007a89; text-decoration: none; font-weight: 600;">Clique para abrir no Google Maps <i class="ph ph-arrow-square-out" style="font-size: 10px;"></i></a>
            </div>
        <?php endif; ?>

        <?php if (!empty($data['area_details'])): ?>
            <div class="area-box">
                <div class="area-box-label"><i class="ph ph-text-align-left" style="font-size: 10px;"></i> Detalhamento da Área de Atuação</div>
                <div class="area-box-content"><?php echo sanitize_html($data['area_details'] ?? ''); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($data['description'])): ?>
            <div class="area-box">
                <div class="area-box-label"><i class="ph ph-info" style="font-size: 10px;"></i> Instruções Complementares</div>
                <div class="area-box-content"><?php echo nl2br(htmlspecialchars($data['description'])); ?></div>
            </div>
        <?php endif; ?>

        <?php $refImage = !empty($data['ref_image']) ? $data['ref_image'] : (!empty($data['admin_file_1']) ? $data['admin_file_1'] : null);
            if ($refImage):
                $file_ext = strtolower(pathinfo($refImage, PATHINFO_EXTENSION));
                if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
            <div class="map-box">
                <div class="map-box-label"><i class="ph ph-crosshair" style="font-size: 10px; color: #007a89;"></i> Mapa de Referência / Localização</div>
                <img src="<?php echo htmlspecialchars($refImage); ?>" style="max-width: 100%; max-height: 250px; border-radius: 4px;">
            </div>
        <?php endif; endif; ?>
    </div>

    <!-- SECAO 3: BASE DE CALCULO -->
    <div class="section">
        <div class="section-title"><span class="section-num">3</span> Base de Cálculo Financeira (Anexo III)</div>
        <p style="font-size: 11px; margin: 0 0 8px 0; color: #64748b;">Os valores de remuneração seguirão a tabela abaixo, sujeitos à confirmação da execução pelo relatório final:</p>
        <table class="table-rates">
            <thead>
                <tr>
                    <th>Item de Remuneração</th>
                    <th>Unidade</th>
                    <th style="text-align: right;">Valor Unitário</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Trabalho de Escritório / Relatório</td>
                    <td>Por Rota</td>
                    <td>R$ <?php echo number_format($rates['escritorio'], 2, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td>Deslocamento (KM — Gasolina R$ <?php echo number_format($gas_price, 2, ',', '.'); ?>)</td>
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

    <!-- SECAO 4: COMPROMISSO -->
    <div class="section">
        <div class="section-title"><span class="section-num">4</span> Compromisso e Prazo</div>
        <div class="legal-text">
            O RECENSEADOR acima qualificado declara aceitar a execução da rota descrita neste termo, comprometendo-se a realizar as vistorias técnicas com zelo, ética e profissionalismo, seguindo as orientações da Gerência de Fiscalização do CAU/DF.
            <br><br>
            A conclusão dos trabalhos deverá ocorrer impreterivelmente até <strong><?php
                $deadline = !empty($data['end_date']) ? $data['end_date'] : (!empty($data['scheduled_end']) ? $data['scheduled_end'] : null);
                echo $deadline ? date('d/m/Y', strtotime($deadline)) : 'Prazo não definido';
            ?></strong>, mediante envio de relatório circunstanciado e comprovantes através do sistema oficial. O não cumprimento dos prazos ou a execução em desacordo com as normas poderá acarretar em glosas ou sanções previstas no contrato principal.
        </div>
    </div>

    <!-- ASSINATURAS -->
    <div class="signatures">
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-name"><?php echo mb_strtoupper(htmlspecialchars($data['name']), 'UTF-8'); ?></div>
            <div class="sig-role">Recenseador Credenciado</div>
            <div style="font-size: 9px; color: #94a3b8; margin-top: 4px;">Aceite digital em <?php echo !empty($data['accepted_at']) ? date('d/m/Y H:i', strtotime($data['accepted_at'])) : date('d/m/Y H:i'); ?></div>
        </div>
        <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-name">GERFISC — CAU/DF</div>
            <div class="sig-role">Gerência de Fiscalização</div>
            <div style="font-size: 9px; color: #94a3b8; margin-top: 4px;">Contratante</div>
        </div>
    </div>

    <!-- RODAPE -->
    <div class="doc-footer">
        <strong>Conselho de Arquitetura e Urbanismo do Distrito Federal — CAU/DF</strong><br>
        Documento gerado eletronicamente pelo Sistema de Gestão de Recenseadores em <?php echo $doc_date; ?> às <?php echo $doc_time; ?><br>
        <strong>Hash de Autenticidade:</strong> <?php echo $doc_hash; ?>
    </div>
</div>

</body>
</html>
