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
    <div class="top-bar">
        <div class="container">
            <a href="#"><i class="fas fa-headset"></i> ATENDIMENTO</a>
            <a href="#"><i class="fas fa-universal-access"></i> ACESSIBILIDADE</a>
            <a href="#"><i class="fas fa-info-circle"></i> TRANSPARÊNCIA</a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-whatsapp"></i></a>
        </div>
    </div>

    <!-- Main Header -->
    <header>
        <div class="header-main">
            <div class="container">
                <a href="<?php echo BASE_URL; ?>" class="logo">
                    <img src="<?php echo BASE_URL; ?>assets/img/logo_caudf.png" alt="CAU/DF - Conselho de Arquitetura e Urbanismo do Distrito Federal" style="height: 56px; width: auto; display: block;">
                </a>

                <div style="display: flex; gap: 1.5rem; align-items: center; flex-grow: 1; justify-content: flex-end;">
                    <!-- Search Bar -->
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Pesquisar...">
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <a href="<?php echo BASE_URL; ?>pages/dashboard.php" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                                <i class="fas fa-user-circle"></i> MINHA ÁREA
                            </a>
                            <a href="<?php echo BASE_URL; ?>logout.php" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                                SAIR
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>pages/login.php" class="btn btn-primary" style="padding: 0.7rem 1.2rem; line-height: 1.2;">
                             <div style="text-align: left;">
                                <i class="fas fa-lock" style="font-size: 1.2rem; float: left; margin-right: 10px; margin-top: 2px;"></i>
                                <span style="font-size: 0.8rem; font-weight: 800;">SICCAU</span><br>
                                <span style="font-size: 0.6rem; font-weight: 400; opacity: 0.9;">SERVIÇOS ONLINE</span>
                             </div>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="main-nav">
            <div class="container">
                <ul>
                    <li><a href="#">Portal CAUDF</a></li>
                    <li><a href="#">Fiscalização</a></li>
                    <li><a href="#">Para a Sociedade</a></li>
                    <li><a href="#">Institucional</a></li>
                    <li><a href="#">Legislação</a></li>
                    <li><a href="#">Transparência</a></li>
                    <li><a href="#">Dados Abertos</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Main Content Wrapper -->
    <main>