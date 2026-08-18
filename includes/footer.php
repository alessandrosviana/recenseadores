</main>
</main>
<footer style="background: #0f172a; color: #94a3b8; padding: 4rem 0 2rem; margin-top: auto; border-top: 3px solid #0d9488; font-family: 'Inter', sans-serif;">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2.5rem; margin-bottom: 3rem;">
            <!-- Coluna 1: Institucional -->
            <div>
                <h4 style="color: #ffffff; font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-building" style="color: #2dd4bf;"></i> CAU/DF
                </h4>
                <p style="font-size: 0.88rem; line-height: 1.6; color: #cbd5e1; margin-bottom: 1.25rem;">
                    Conselho de Arquitetura e Urbanismo do Distrito Federal.<br>
                    Projeto Recenseadores de Obras e Mapeamento de Fiscalização no DF.
                </p>
                <div style="display: flex; gap: 10px;">
                    <a href="https://www.caudf.gov.br" target="_blank" title="Portal CAU/DF" style="width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.06); color: #2dd4bf; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='#0d9488'; this.style.color='#fff';" onmouseout="this.style.background='rgba(255,255,255,0.06)'; this.style.color='#2dd4bf';">
                        <i class="fas fa-globe"></i>
                    </a>
                    <a href="#" title="Instagram CAU/DF" style="width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.06); color: #2dd4bf; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='#0d9488'; this.style.color='#fff';" onmouseout="this.style.background='rgba(255,255,255,0.06)'; this.style.color='#2dd4bf';">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" title="Atendimento WhatsApp" style="width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.06); color: #2dd4bf; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.2s;" onmouseover="this.style.background='#0d9488'; this.style.color='#fff';" onmouseout="this.style.background='rgba(255,255,255,0.06)'; this.style.color='#2dd4bf';">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>

            <!-- Coluna 2: Links Rápidos -->
            <div>
                <h4 style="color: #ffffff; font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-link" style="color: #2dd4bf;"></i> Acesso Rápido
                </h4>
                <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.88rem; display: flex; flex-direction: column; gap: 8px;">
                    <li><a href="<?php echo BASE_URL; ?>pages/register.php" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#2dd4bf';" onmouseout="this.style.color='#cbd5e1';"><i class="fas fa-angle-right" style="font-size: 0.75rem; margin-right: 6px; color: #2dd4bf;"></i> Cadastro de Recenseador</a></li>
                    <li><a href="<?php echo BASE_URL; ?>pages/login.php" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#2dd4bf';" onmouseout="this.style.color='#cbd5e1';"><i class="fas fa-angle-right" style="font-size: 0.75rem; margin-right: 6px; color: #2dd4bf;"></i> Login do Recenseador</a></li>
                    <li><a href="<?php echo BASE_URL; ?>pages/login.php" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#2dd4bf';" onmouseout="this.style.color='#cbd5e1';"><i class="fas fa-angle-right" style="font-size: 0.75rem; margin-right: 6px; color: #2dd4bf;"></i> Área Administrativa</a></li>
                    <li><a href="<?php echo BASE_URL; ?>pages/forgot_password.php" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#2dd4bf';" onmouseout="this.style.color='#cbd5e1';"><i class="fas fa-angle-right" style="font-size: 0.75rem; margin-right: 6px; color: #2dd4bf;"></i> Recuperar Senha</a></li>
                </ul>
            </div>

            <!-- Coluna 3: Atendimento e Localização -->
            <div>
                <h4 style="color: #ffffff; font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-headset" style="color: #2dd4bf;"></i> Atendimento
                </h4>
                <div style="font-size: 0.88rem; color: #cbd5e1; display: flex; flex-direction: column; gap: 10px;">
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <i class="fas fa-envelope" style="color: #2dd4bf; margin-top: 3px;"></i>
                        <span>atendimento@caudf.gov.br</span>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <i class="fas fa-map-marker-alt" style="color: #2dd4bf; margin-top: 3px;"></i>
                        <span>SEPS 705/905 Bloco A, Centro Empresarial Asa Sul - Brasília/DF</span>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <i class="fas fa-clock" style="color: #2dd4bf; margin-top: 3px;"></i>
                        <span>Segunda a Sexta, das 09h às 17h</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Copyright Inferior -->
        <div style="border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 1.5rem; text-align: center; font-size: 0.82rem; color: #64748b;">
            &copy; <?php echo date('Y'); ?> <strong>CAU/DF</strong> - Conselho de Arquitetura e Urbanismo do Distrito Federal. <br class="mobile-break">Desenvolvido pelo Setor de Tecnologia do CAU/DF - Alessandro Viana.
        </div>
    </div>
</footer>
</body>

</html>