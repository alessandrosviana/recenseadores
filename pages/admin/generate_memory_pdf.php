<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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
    WHERE r.id = ? AND r.status = 'completed'
");
$stmt->execute([$route_id]);
$data = $stmt->fetch();

if (!$data) {
    die("Dados não encontrados ou a rota ainda não foi concluída.");
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Memória de Cálculo - Rota #<?php echo $route_id; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
        .contract-page { background: white; width: 210mm; min-height: 297mm; margin: 20px auto; padding: 25mm; box-shadow: 0 0 10px rgba(0,0,0,0.1); box-sizing: border-box; }
        
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 30px; }
        .header h1 { font-size: 18px; margin: 5px 0; text-transform: uppercase; }
        .header h2 { font-size: 14px; margin: 0; color: #555; }
        
        .title { 
            text-align: center; 
            font-weight: 800; 
            text-transform: uppercase; 
            font-size: 18px; 
            margin: 30px 0; 
            padding: 15px;
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
            background: #fdfdfd;
            letter-spacing: 0.5px;
            line-height: 1.4;
        }
        
        .section { margin-bottom: 25px; }
        .section-title { font-weight: 700; text-transform: uppercase; font-size: 13px; border-bottom: 1px solid #eee; margin-bottom: 10px; color: #000; padding-bottom: 5px; }
        
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .field { font-size: 12px; }
        .field strong { color: #555; }
        
        .table-rates { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 11px; }
        .table-rates th, .table-rates td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .table-rates th { background: #f8f9fa; font-weight: 700; text-transform: uppercase; color: #444; }
        .table-rates td { color: #222; }
        
        .highlight-row { background: #f1f8ff; font-weight: bold; }
        .total-row { background: #e6f4ea; font-weight: 800; font-size: 13px; }
        
        .signatures { margin-top: 60px; display: grid; grid-template-columns: 1fr 1fr; gap: 50px; text-align: center; }
        .sig-box { border-top: 1px solid #000; padding-top: 10px; font-size: 11px; }

        @media print {
            body { background: white; margin: 0; }
            .contract-page { margin: 0; box-shadow: none; border: none; }
            .no-print { display: none; }
        }

        .no-print-bar { background: #333; color: white; padding: 10px; text-align: center; position: sticky; top: 0; z-index: 100; }
        .btn { background: #00bfa5; color: white; border: none; padding: 8px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-print { background: #2196f3; }
    </style>
</head>
<body>

<div class="no-print-bar no-print">
    <span style="margin-right: 20px;">Visualização da Memória de Cálculo</span>
    <button onclick="window.print()" class="btn btn-print"><i class="fas fa-print"></i> Imprimir Memória (PDF)</button>
    <a href="#" onclick="window.close()" class="btn" style="background: #dc3545; margin-left: 10px;"><i class="fas fa-times"></i> Fechar Aba</a>
</div>

<div class="contract-page">
    <div class="header">
        <h1>Conselho de Arquitetura e Urbanismo do Distrito Federal</h1>
        <h2>Gerência de Fiscalização - GERFISC</h2>
    </div>

    <div class="title">Memória de Cálculo de Pagamento<br><span style="font-size: 14px; font-weight: 600; color: #555;">Anexo III - Rota #<?php echo $route_id; ?></span></div>

    <div class="section">
        <div class="section-title">1. Dados do Recenseador</div>
        <div class="grid">
            <div class="field"><strong>Nome:</strong> <?php echo mb_strtoupper(htmlspecialchars($data['name']), 'UTF-8'); ?></div>
            <div class="field"><strong>CPF:</strong> <?php echo htmlspecialchars($data['cpf']); ?></div>
            <div class="field"><strong>Processo SEI:</strong> <?php echo htmlspecialchars($data['processo_sei']); ?></div>
            <div class="field"><strong>Nº Contrato:</strong> <?php echo htmlspecialchars($data['num_contrato']); ?></div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">2. Dados da Demanda (Rota)</div>
        <div class="grid">
            <div class="field"><strong>Título da Rota:</strong> <?php echo htmlspecialchars($data['title']); ?></div>
            <div class="field"><strong>Microrregião:</strong> <?php echo htmlspecialchars($data['microregion']); ?></div>
            <div class="field"><strong>Data de Conclusão:</strong> <?php echo date('d/m/Y H:i', strtotime($data['completed_at'])); ?></div>
            <div class="field"><strong>SEI do Pagamento:</strong> <span style="font-weight: bold; background: #eee; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($data['sei_pagamento']); ?></span></div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">3. Detalhamento Financeiro Liquidado</div>
        <table class="table-rates">
            <thead>
                <tr>
                    <th style="width: 40%;">Item Remuneratório</th>
                    <th style="text-align: center;">Quantidade</th>
                    <th style="text-align: right;">Valor Unitário (R$)</th>
                    <th style="text-align: right;">Subtotal (R$)</th>
                </tr>
            </thead>
            <tbody>
                <!-- Custos Fixos -->
                <tr><td colspan="4" style="background: #fdfdfd; font-weight: 800; color: #555; font-size: 10px;">CUSTOS FIXOS (POR ROTA / DIÁRIA)</td></tr>
                <tr>
                    <td>Trabalho de Escritório / Relatório</td>
                    <td style="text-align: center;"><?php echo number_format($data['calc_q_escritorio'], 2, ',', '.'); ?></td>
                    <td style="text-align: right;"><?php echo number_format($data['calc_u_escritorio'], 2, ',', '.'); ?></td>
                    <td style="text-align: right;"><strong><?php echo number_format($data['calc_q_escritorio'] * $data['calc_u_escritorio'], 2, ',', '.'); ?></strong></td>
                </tr>
                <tr>
                    <td>Deslocamento KM Fixo (RA Base)</td>
                    <td style="text-align: center;"><?php echo number_format($data['calc_q_km_fix'], 2, ',', '.'); ?></td>
                    <td style="text-align: right;"><?php echo number_format($data['calc_u_km_fix'], 2, ',', '.'); ?></td>
                    <td style="text-align: right;"><strong><?php echo number_format($data['calc_q_km_fix'] * $data['calc_u_km_fix'], 2, ',', '.'); ?></strong></td>
                </tr>
                <tr>
                    <td>Auxílio Alimentação</td>
                    <td style="text-align: center;"><?php echo number_format($data['calc_q_alim'], 2, ',', '.'); ?></td>
                    <td style="text-align: right;"><?php echo number_format($data['calc_u_alim'], 2, ',', '.'); ?></td>
                    <td style="text-align: right;"><strong><?php echo number_format($data['calc_q_alim'] * $data['calc_u_alim'], 2, ',', '.'); ?></strong></td>
                </tr>
                <tr class="highlight-row">
                    <td colspan="3" style="text-align: right; text-transform: uppercase;">Total Custos Fixos Previstos:</td>
                    <td style="text-align: right; color: #1e40af;">R$ <?php echo number_format($data['calc_total_fixed'], 2, ',', '.'); ?></td>
                </tr>

                <!-- Custos Variáveis -->
                <tr><td colspan="4" style="background: #fdfdfd; font-weight: 800; color: #555; font-size: 10px;">CUSTOS VARIÁVEIS (POR UNIDADE / EXECUÇÃO)</td></tr>
                <tr>
                    <td>KM Rodado Adicional (Gasolina: R$ <?php echo number_format($data['calc_gas_price'], 2, ',', '.'); ?>)</td>
                    <td style="text-align: center;"><?php echo number_format($data['calc_q_km_var'], 2, ',', '.'); ?></td>
                    <td style="text-align: right;"><?php echo number_format($data['calc_u_km_var'], 2, ',', '.'); ?></td>
                    <td style="text-align: right;"><strong><?php echo number_format($data['calc_q_km_var'] * $data['calc_u_km_var'], 2, ',', '.'); ?></strong></td>
                </tr>
                <tr>
                    <td>Obras Adicionais Vistoriadas</td>
                    <td style="text-align: center;"><?php echo number_format($data['calc_q_obras'], 2, ',', '.'); ?></td>
                    <td style="text-align: right;"><?php echo number_format($data['calc_u_obras'], 2, ',', '.'); ?></td>
                    <td style="text-align: right;"><strong><?php echo number_format($data['calc_q_obras'] * $data['calc_u_obras'], 2, ',', '.'); ?></strong></td>
                </tr>
                <tr class="highlight-row">
                    <td colspan="3" style="text-align: right; text-transform: uppercase;">Total Custos Variáveis Apurados:</td>
                    <td style="text-align: right; color: #b91c1c;">R$ <?php echo number_format($data['calc_total_variable'], 2, ',', '.'); ?></td>
                </tr>
                
                <!-- Totalizador -->
                <tr class="total-row">
                    <td colspan="3" style="text-align: right; text-transform: uppercase; font-size: 14px; padding: 15px 10px;">VALOR TOTAL DO PAGAMENTO (LIQUIDADO):</td>
                    <td style="text-align: right; font-size: 14px; color: #059669; padding: 15px 10px;">R$ <?php echo number_format($data['calc_grand_total'], 2, ',', '.'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <p style="font-size: 11px; text-align: justify; color: #555;">
            * A presente Memória de Cálculo reflete os dados extraídos do sistema no momento do aceite da liquidação de pagamento da respectiva Rota. Todos os valores de KM Variável e Obras Adicionais foram auditados pelo Administrador responsável baseado nos relatórios em anexo enviados pelo recenseador.
        </p>
    </div>

    <div class="signatures">
        <div class="sig-box">
            <strong>ADMINISTRAÇÃO CAU/DF</strong><br>
            Aprovado via Sistema Digital<br>
            Data: <?php echo date('d/m/Y H:i'); ?>
        </div>
        <div class="sig-box">
            <strong><?php echo mb_strtoupper(htmlspecialchars($data['name']), 'UTF-8'); ?></strong><br>
            Recenseador Credenciado<br>
            (Assinatura Dispensada - Pagamento em Conta)
        </div>
    </div>

    <div style="margin-top: 40px; font-size: 10px; color: #888; text-align: center; border-top: 1px dashed #eee; padding-top: 10px;">
        Este documento é um comprovante interno gerado pelo Sistema de Gestão de Recenseadores CAU/DF.
        <br>Hash de Autenticidade: <?php echo strtoupper(md5($route_id . $data['calc_grand_total'] . $data['sei_pagamento'])); ?>
    </div>
</div>

</body>
</html>
