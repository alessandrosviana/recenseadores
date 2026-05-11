<?php
require_once 'config/session.php';
include 'includes/header.php';
?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                <span class="hero-tag">Fiscalização</span>
                <h1>CAU/DF no projeto <span>Recenseadores de Obras</span></h1>
                <p style="font-size: 1.25rem; color: #555; margin-bottom: 2rem;">
                    Para se tornar recenseador, faça seu cadastro. Em seguida, preencha todos os dados do formulário e
                    anexe todos os documentos.
                </p>
                <div style="display: flex; gap: 1rem;">
                    <a href="<?php echo BASE_URL; ?>pages/register.php" class="btn btn-primary"
                        style="padding: 1rem 2rem;">QUERO SER RECENSEADOR</a>
                </div>
            </div>
            <div class="hero-image-container">
                <img src="<?php echo BASE_URL; ?>assets/img/banner_principal.png" class="hero-image" alt="Edital Recenseadores de Obras">
            </div>
        </div>

        <!-- Dot Navigation (Visual Only) -->
        <div style="text-align: center; margin-top: 2rem;">
            <span
                style="display: inline-block; width: 10px; height: 10px; background: var(--primary-teal); border-radius: 50%; margin: 0 5px;"></span>
            <span
                style="display: inline-block; width: 10px; height: 10px; background: #ccc; border-radius: 50%; margin: 0 5px;"></span>
            <span
                style="display: inline-block; width: 10px; height: 10px; background: #ccc; border-radius: 50%; margin: 0 5px;"></span>
        </div>
    </div>
</div>

<!-- Quick Access Section -->
<div class="container" style="padding-top: 4rem; padding-bottom: 4rem;">
    <div class="section-title">
        ACESSO <strong>RÁPIDO</strong>
    </div>

    <div class="quick-access-grid">
        <a href="<?php echo BASE_URL; ?>pages/register.php" class="quick-btn">
            <span><i class="fas fa-file-contract"></i> Cadastro</span>
            <i class="fas fa-arrow-right"></i>
        </a>
        <a href="<?php echo BASE_URL; ?>pages/login.php" class="quick-btn">
            <span><i class="fas fa-sign-in-alt"></i> Login Recenseador</span>
            <i class="fas fa-arrow-right"></i>
        </a>
        <a href="<?php echo BASE_URL; ?>pages/login.php" class="quick-btn">
            <span><i class="fas fa-user-shield"></i> Área Administrativa</span>
            <i class="fas fa-arrow-right"></i>
        </a>
        <a href="#" class="quick-btn">
            <span><i class="fas fa-map-marked-alt"></i> Mapa de Obras</span>
            <i class="fas fa-arrow-right"></i>
        </a>
        <a href="#" class="quick-btn">
            <span><i class="fas fa-bullhorn"></i> Denúncia</span>
            <i class="fas fa-arrow-right"></i>
        </a>
        <a href="#" class="quick-btn">
            <span><i class="fas fa-question-circle"></i> Perguntas Frequentes</span>
            <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>