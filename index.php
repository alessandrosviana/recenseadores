<?php
require_once 'config/session.php';
include 'includes/header.php';
?>

<!-- Estilos Customizados da Página Principal -->
<style>
    .hero-wrapper {
        background: linear-gradient(135deg, #007a89 0%, #005b66 60%, #004d56 100%);
        color: white;
        padding: 4rem 0 5rem;
        position: relative;
        overflow: hidden;
    }
    .hero-wrapper::before {
        content: '';
        position: absolute;
        top: -50px;
        right: -50px;
        width: 350px;
        height: 350px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        filter: blur(50px);
    }
    .hero-badge-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.18);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.35);
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 1.25rem;
        backdrop-filter: blur(4px);
    }
    .hero-title {
        font-size: 2.75rem;
        font-weight: 800;
        line-height: 1.2;
        color: #ffffff;
        margin-bottom: 1.25rem;
    }
    .hero-title span {
        color: #b2ebf2;
    }
    .hero-subtitle {
        font-size: 1.15rem;
        color: #e0f7fa;
        line-height: 1.6;
        margin-bottom: 2rem;
        max-width: 600px;
        opacity: 0.95;
    }
    .hero-buttons {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .btn-hero-primary {
        background: #0d9488;
        color: white;
        padding: 0.9rem 1.8rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.95rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 14px rgba(13, 148, 136, 0.4);
        transition: all 0.2s ease;
    }
    .btn-hero-primary:hover {
        background: #0f766e;
        transform: translateY(-2px);
        color: white;
    }
    .btn-hero-outline {
        background: rgba(255, 255, 255, 0.05);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 0.9rem 1.8rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.95rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
    }
    .btn-hero-outline:hover {
        background: rgba(255, 255, 255, 0.15);
        color: white;
        transform: translateY(-2px);
    }
    .hero-img-box {
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        border: 1px solid rgba(255,255,255,0.1);
        overflow: hidden;
    }

    /* Grid de Recursos */
    .features-section {
        padding: 4rem 0;
        background: #f8fafc;
    }
    .feature-card {
        background: white;
        padding: 1.75rem;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        transition: all 0.25s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .feature-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 20px rgba(0,0,0,0.06);
        border-color: #cbd5e1;
    }
    .feature-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        background: #f0fdf4;
        color: #166534;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 1.25rem;
    }

    /* Grid de Acesso Rápido */
    .quick-grid-custom {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.25rem;
        margin-top: 1.5rem;
    }
    .quick-card-item {
        background: white;
        padding: 1.25rem 1.5rem;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        text-decoration: none;
        color: #1e293b;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-weight: 700;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .quick-card-item:hover {
        border-color: #0d9488;
        color: #0d9488;
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(13, 148, 136, 0.1);
    }
    .quick-card-item div {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .quick-card-item i.icon-left {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: #f0fdfa;
        color: #0d9488;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }
</style>

<!-- Hero Section -->
<div class="hero-wrapper">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 3rem; align-items: center;">
            <div>
                <div class="hero-badge-tag">
                    <i class="fas fa-building"></i> FISCALIZAÇÃO CAU/DF
                </div>
                <h1 class="hero-title">
                    Projeto <span>Recenseadores de Obras</span>
                </h1>
                <p class="hero-subtitle">
                    Cadastre-se para atuar como recenseador de obras no Distrito Federal. Preencha seus dados, envie sua documentação e acompanhe suas rotas atribuídas.
                </p>
                <div class="hero-buttons">
                    <a href="<?php echo BASE_URL; ?>pages/register.php" class="btn-hero-primary">
                        <i class="fas fa-user-plus"></i> QUERO SER RECENSEADOR
                    </a>
                    <a href="<?php echo BASE_URL; ?>pages/login.php" class="btn-hero-outline">
                        <i class="fas fa-sign-in-alt"></i> ACESSAR MINHA CONTA
                    </a>
                </div>
            </div>
            <div class="hero-img-box">
                <img src="<?php echo BASE_URL; ?>assets/img/banner_principal.png" alt="Banner Recenseadores CAU/DF" style="width: 100%; height: auto; display: block;">
            </div>
        </div>
    </div>
</div>

<!-- Section: Pilares do Projeto -->
<div class="features-section">
    <div class="container">
        <div style="text-align: center; margin-bottom: 3rem;">
            <h2 style="font-size: 1.8rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">
                COMO FUNCIONA O PROJETO
            </h2>
            <p style="color: #64748b; font-size: 1rem; max-width: 600px; margin: 0 auto;">
                Conheça a dinâmica de trabalho e fiscalização desenvolvida pelo Conselho de Arquitetura e Urbanismo do DF.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem;">
            <!-- Pilar 1 -->
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;">
                    1. Cadastro & Validação
                </h3>
                <p style="font-size: 0.9rem; color: #64748b; line-height: 1.5; margin: 0;">
                    Profissionais realizam o cadastro no portal e enviam a documentação para aprovação da equipe técnica do CAU/DF.
                </p>
            </div>

            <!-- Pilar 2 -->
            <div class="feature-card">
                <div class="feature-icon" style="background: #eff6ff; color: #1d4ed8;">
                    <i class="fas fa-route"></i>
                </div>
                <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;">
                    2. Atribuição de Rotas
                </h3>
                <p style="font-size: 0.9rem; color: #64748b; line-height: 1.5; margin: 0;">
                    Após aprovação, o recenseador recebe rotas setorizadas por regiões do DF para vistoria e coleta de dados.
                </p>
            </div>

            <!-- Pilar 3 -->
            <div class="feature-card">
                <div class="feature-icon" style="background: #fef3c7; color: #b45309;">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;">
                    3. Execução e Comprovação
                </h3>
                <p style="font-size: 0.9rem; color: #64748b; line-height: 1.5; margin: 0;">
                    Envio de relatórios, dados das obras e comprovantes diretamente pela plataforma com relógio de prazos em tempo real.
                </p>
            </div>

            <!-- Pilar 4 -->
            <div class="feature-card">
                <div class="feature-icon" style="background: #f3e8ff; color: #7e22ce;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;">
                    4. Transparência & SEI
                </h3>
                <p style="font-size: 0.9rem; color: #64748b; line-height: 1.5; margin: 0;">
                    Validação do processo, emissão do termo de aceitação e acompanhamento do processo de pagamento via SEI.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Section: Quick Access Grid -->
<div class="container" style="padding-top: 4rem; padding-bottom: 5rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0;">
                ACESSO <span>RÁPIDO</span>
            </h2>
            <p style="color: #64748b; font-size: 0.9rem; margin: 4px 0 0;">
                Atalhos diretos para os principais serviços do portal.
            </p>
        </div>
    </div>

    <div class="quick-grid-custom">
        <a href="<?php echo BASE_URL; ?>pages/register.php" class="quick-card-item">
            <div>
                <i class="fas fa-file-contract icon-left"></i>
                <span>Cadastro de Recenseador</span>
            </div>
            <i class="fas fa-arrow-right" style="color: #94a3b8; font-size: 0.85rem;"></i>
        </a>

        <a href="<?php echo BASE_URL; ?>pages/login.php" class="quick-card-item">
            <div>
                <i class="fas fa-sign-in-alt icon-left" style="background: #eff6ff; color: #2563eb;"></i>
                <span>Login do Recenseador</span>
            </div>
            <i class="fas fa-arrow-right" style="color: #94a3b8; font-size: 0.85rem;"></i>
        </a>

        <a href="<?php echo BASE_URL; ?>pages/login.php" class="quick-card-item">
            <div>
                <i class="fas fa-user-shield icon-left" style="background: #fdf2f8; color: #db2777;"></i>
                <span>Painel Administrativo</span>
            </div>
            <i class="fas fa-arrow-right" style="color: #94a3b8; font-size: 0.85rem;"></i>
        </a>

        <a href="<?php echo BASE_URL; ?>pages/forgot_password.php" class="quick-card-item">
            <div>
                <i class="fas fa-key icon-left" style="background: #fefce8; color: #ca8a04;"></i>
                <span>Recuperar Senha</span>
            </div>
            <i class="fas fa-arrow-right" style="color: #94a3b8; font-size: 0.85rem;"></i>
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>