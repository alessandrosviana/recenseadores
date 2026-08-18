</main>
<footer style="background: #004d56; color: #b2ebf2; padding: 4rem 0 2rem; margin-top: auto; border-top: 4px solid #007a89; font-family: 'Inter', sans-serif;">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2.5rem; margin-bottom: 3rem;">
            <!-- Coluna 1: Institucional -->
            <div>
                <h4 style="color: #ffffff; font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-building" style="color: #80deea;"></i> CAU/DF
                </h4>
                <p style="font-size: 0.88rem; line-height: 1.6; color: #e0f7fa; margin-bottom: 1.25rem;">
                    Conselho de Arquitetura e Urbanismo do Distrito Federal.<br>
                    Projeto Recenseadores de Obras e Mapeamento de Fiscalização no DF.
                </p>
                <div style="display: flex; gap: 10px;">
                    <a href="https://www.caudf.gov.br" target="_blank" title="Portal CAU/DF" style="width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.1); color: #80deea; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='#007a89'; this.style.color='#fff';" onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.color='#80deea';">
                        <i class="fas fa-globe"></i>
                    </a>
                    <a href="#" title="Instagram CAU/DF" style="width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.1); color: #80deea; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='#007a89'; this.style.color='#fff';" onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.color='#80deea';">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" title="Atendimento WhatsApp" style="width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.1); color: #80deea; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='#007a89'; this.style.color='#fff';" onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.color='#80deea';">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>

            <!-- Coluna 2: Links Rápidos -->
            <div>
                <h4 style="color: #ffffff; font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-link" style="color: #80deea;"></i> Acesso Rápido
                </h4>
                <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.88rem; display: flex; flex-direction: column; gap: 8px;">
                    <li><a href="<?php echo BASE_URL; ?>pages/register.php" style="color: #e0f7fa; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#80deea';" onmouseout="this.style.color='#e0f7fa';"><i class="fas fa-angle-right" style="font-size: 0.75rem; margin-right: 6px; color: #80deea;"></i> Cadastro de Recenseador</a></li>
                    <li><a href="<?php echo BASE_URL; ?>pages/login.php" style="color: #e0f7fa; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#80deea';" onmouseout="this.style.color='#e0f7fa';"><i class="fas fa-angle-right" style="font-size: 0.75rem; margin-right: 6px; color: #80deea;"></i> Login do Recenseador</a></li>
                    <li><a href="<?php echo BASE_URL; ?>pages/login.php" style="color: #e0f7fa; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#80deea';" onmouseout="this.style.color='#e0f7fa';"><i class="fas fa-angle-right" style="font-size: 0.75rem; margin-right: 6px; color: #80deea;"></i> Área Administrativa</a></li>
                    <li><a href="<?php echo BASE_URL; ?>pages/forgot_password.php" style="color: #e0f7fa; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#80deea';" onmouseout="this.style.color='#e0f7fa';"><i class="fas fa-angle-right" style="font-size: 0.75rem; margin-right: 6px; color: #80deea;"></i> Recuperar Senha</a></li>
                </ul>
            </div>

            <!-- Coluna 3: Atendimento e Localização -->
            <div>
                <h4 style="color: #ffffff; font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-headset" style="color: #80deea;"></i> Atendimento
                </h4>
                <div style="font-size: 0.88rem; color: #e0f7fa; display: flex; flex-direction: column; gap: 10px;">
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <i class="fas fa-envelope" style="color: #80deea; margin-top: 3px;"></i>
                        <span>atendimento@caudf.gov.br</span>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <i class="fas fa-map-marker-alt" style="color: #80deea; margin-top: 3px;"></i>
                        <span>SEPS 705/905 Bloco A, Centro Empresarial Asa Sul - Brasília/DF</span>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <i class="fas fa-clock" style="color: #80deea; margin-top: 3px;"></i>
                        <span>Segunda a Sexta, das 09h às 17h</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Copyright Inferior -->
        <div style="border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 1.5rem; text-align: center; font-size: 0.82rem; color: #b2ebf2;">
            &copy; <?php echo date('Y'); ?> <strong>CAU/DF</strong> - Conselho de Arquitetura e Urbanismo do Distrito Federal. <br class="mobile-break">Desenvolvido pelo Setor de Tecnologia do CAU/DF - Alessandro Viana.
        </div>
    </div>
</footer>
</body>

</html>