<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Recenseadores - CAU/DF Style</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <!-- Using FontAwesome for icons as seen in the screenshot -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <!-- Note: Uses BASE_URL defined in database.php -->
</head>

<body>
    <!-- Top Bar -->
    <div style="background: #005b66; color: #b2ebf2; padding: 6px 0; font-size: 0.78rem; border-bottom: 1px solid rgba(255,255,255,0.1); font-family: 'Inter', sans-serif;">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; gap: 1.25rem; align-items: center;">
                <a href="http://www.caudf.gov.br" target="_blank" rel="noopener noreferrer" style="color: #e0f7fa; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: color 0.2s;" onmouseover="this.style.color='#80deea';" onmouseout="this.style.color='#e0f7fa';">
                    <i class="fas fa-headset" style="color: #80deea;"></i> ATENDIMENTO
                </a>
                <a href="http://www.caudf.gov.br" target="_blank" rel="noopener noreferrer" style="color: #e0f7fa; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: color 0.2s;" onmouseover="this.style.color='#80deea';" onmouseout="this.style.color='#e0f7fa';">
                    <i class="fas fa-universal-access" style="color: #80deea;"></i> ACESSIBILIDADE
                </a>
                <a href="https://transparencia.caudf.gov.br/" target="_blank" rel="noopener noreferrer" style="color: #e0f7fa; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: color 0.2s;" onmouseover="this.style.color='#80deea';" onmouseout="this.style.color='#e0f7fa';">
                    <i class="fas fa-info-circle" style="color: #80deea;"></i> TRANSPARÊNCIA
                </a>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <a href="https://www.instagram.com/caudfoficial/" target="_blank" rel="noopener noreferrer" style="color: #e0f7fa; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#80deea';" onmouseout="this.style.color='#e0f7fa';" title="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="http://www.caudf.gov.br" target="_blank" rel="noopener noreferrer" style="color: #e0f7fa; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#80deea';" onmouseout="this.style.color='#e0f7fa';" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header style="background: white; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 1000; box-shadow: 0 4px 15px rgba(0,0,0,0.03); font-family: 'Inter', sans-serif;">
        <div class="header-main" style="padding: 0.85rem 0;">
            <div class="container" style="display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap;">
                <a href="<?php echo BASE_URL; ?>" class="logo" style="display: flex; align-items: center;">
                    <img src="<?php echo BASE_URL; ?>assets/img/logo_caudf.png" alt="CAU/DF - Conselho de Arquitetura e Urbanismo do Distrito Federal" style="height: 52px; width: auto; display: block; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.02)';" onmouseout="this.style.transform='scale(1)';">
                </a>

                <div style="display: flex; gap: 1.25rem; align-items: center; flex-grow: 1; justify-content: flex-end;">
                    <!-- Search Bar Modern -->
                    <div class="search-container" style="position: relative; max-width: 320px; width: 100%;">
                        <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem;"></i>
                        <input type="text" placeholder="Pesquisar no portal..." style="width: 100%; padding: 0.65rem 1rem 0.65rem 2.4rem; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 20px; font-size: 0.88rem; color: #1e293b; outline: none; transition: all 0.2s;" onfocus="this.style.background='#fff'; this.style.borderColor='#007a89'; this.style.boxShadow='0 0 0 3px rgba(0, 122, 137, 0.15)';" onblur="this.style.background='#f8fafc'; this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';">
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <a href="<?php echo BASE_URL; ?><?php echo ($_SESSION['role'] === 'admin') ? 'pages/admin/dashboard.php' : 'pages/recenseador/dashboard.php'; ?>" class="btn btn-primary" style="background: #007a89; color: white; border: none; padding: 0.65rem 1.2rem; font-size: 0.82rem; font-weight: 700; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 10px rgba(0, 122, 137, 0.25);">
                                <i class="fas fa-user-circle"></i> MINHA ÁREA
                            </a>
                            <a href="<?php echo BASE_URL; ?>logout.php" class="btn btn-outline" style="border: 1px solid #cbd5e1; color: #64748b; padding: 0.65rem 1rem; font-size: 0.82rem; font-weight: 600; border-radius: 8px; text-decoration: none;">
                                SAIR
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="https://acesso.caubr.gov.br/" target="_blank" rel="noopener noreferrer" style="background: linear-gradient(135deg, #007a89 0%, #005b66 100%); color: white; padding: 0.6rem 1.25rem; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 4px 12px rgba(0, 122, 137, 0.3); transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(0, 122, 137, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0, 122, 137, 0.3)';">
                             <div style="background: rgba(255,255,255,0.2); width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-lock" style="font-size: 0.95rem; color: white;"></i>
                             </div>
                             <div style="text-align: left; line-height: 1.1;">
                                <span style="font-size: 0.85rem; font-weight: 800; letter-spacing: 0.03em;">SICCAU</span><br>
                                <span style="font-size: 0.62rem; font-weight: 600; text-transform: uppercase; opacity: 0.9;">SERVIÇOS ONLINE</span>
                             </div>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav style="background: #ffffff; border-top: 1px solid #f1f5f9; padding: 0;">
            <div class="container">
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; gap: 1.75rem; flex-wrap: wrap; align-items: center;">
                    <li><a href="http://www.caudf.gov.br" target="_blank" rel="noopener noreferrer" style="display: block; padding: 0.75rem 0; color: #007a89; font-weight: 800; font-size: 0.82rem; text-decoration: none; border-bottom: 2px solid #007a89; text-transform: uppercase; letter-spacing: 0.03em;">PORTAL CAUDF</a></li>
                    <li><a href="https://www.caudf.gov.br/sobre-a-fiscalizacao/" target="_blank" rel="noopener noreferrer" style="display: block; padding: 0.75rem 0; color: #475569; font-weight: 700; font-size: 0.82rem; text-decoration: none; text-transform: uppercase; letter-spacing: 0.03em; transition: color 0.2s;" onmouseover="this.style.color='#007a89';" onmouseout="this.style.color='#475569';">FISCALIZAÇÃO</a></li>
                    <li><a href="https://www.caudf.gov.br/como-fazer-uma-denuncia-ao-cau-df/" target="_blank" rel="noopener noreferrer" style="display: block; padding: 0.75rem 0; color: #475569; font-weight: 700; font-size: 0.82rem; text-decoration: none; text-transform: uppercase; letter-spacing: 0.03em; transition: color 0.2s;" onmouseover="this.style.color='#007a89';" onmouseout="this.style.color='#475569';">PARA A SOCIEDADE</a></li>
                    <li><a href="https://www.caudf.gov.br/institucional-conselho-arquitetura-urbanismo-df/" target="_blank" rel="noopener noreferrer" style="display: block; padding: 0.75rem 0; color: #475569; font-weight: 700; font-size: 0.82rem; text-decoration: none; text-transform: uppercase; letter-spacing: 0.03em; transition: color 0.2s;" onmouseover="this.style.color='#007a89';" onmouseout="this.style.color='#475569';">INSTITUCIONAL</a></li>
                    <li><a href="https://www.caudf.gov.br/legislacao-federal/" target="_blank" rel="noopener noreferrer" style="display: block; padding: 0.75rem 0; color: #475569; font-weight: 700; font-size: 0.82rem; text-decoration: none; text-transform: uppercase; letter-spacing: 0.03em; transition: color 0.2s;" onmouseover="this.style.color='#007a89';" onmouseout="this.style.color='#475569';">LEGISLAÇÃO</a></li>
                    <li><a href="https://transparencia.caudf.gov.br/" target="_blank" rel="noopener noreferrer" style="display: block; padding: 0.75rem 0; color: #475569; font-weight: 700; font-size: 0.82rem; text-decoration: none; text-transform: uppercase; letter-spacing: 0.03em; transition: color 0.2s;" onmouseover="this.style.color='#007a89';" onmouseout="this.style.color='#475569';">TRANSPARÊNCIA</a></li>
                    <li><a href="https://cau-df.implanta.net.br/portaltransparencia/api/dadosabertos#/" target="_blank" rel="noopener noreferrer" style="display: block; padding: 0.75rem 0; color: #475569; font-weight: 700; font-size: 0.82rem; text-decoration: none; text-transform: uppercase; letter-spacing: 0.03em; transition: color 0.2s;" onmouseover="this.style.color='#007a89';" onmouseout="this.style.color='#475569';">DADOS ABERTOS</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Main Content Wrapper -->
    <main>