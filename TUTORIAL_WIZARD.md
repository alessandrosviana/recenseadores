# 📖 Tutorial: Fluxo de Andamento e Pagamento de Rotas (Wizard)

Este guia explica como funciona o sistema de estágios (Wizard) das rotas, desde a atribuição inicial até o pagamento final ao recenseador.

---

## 🚀 Os 6 Estágios da Rota

O sistema utiliza 6 estágios para controlar o ciclo de vida de cada trabalho:

1.  **Doc. Aprovada (Documentação):** Usuário com cadastro e documentação aprovados, mas sem rota atribuída.
2.  **Rota Disponível (Atribuída):** O administrador atribuiu uma tarefa, mas o recenseador ainda não iniciou o trabalho prático.
3.  **Contrato Assinado:** O recenseador assinou o contrato/termo de adesão da rota para iniciar o levantamento de campo.
4.  **Rota Concluída (Conclusão):** O recenseador finalizou o trabalho em campo e enviou o relatório. A rota agora aparece em **"Tarefas Concluídas"** para conferência fiscal.
5.  **Envio Pagamento (Envio para Pagamento):** O fiscal revisou os dados, realizou os cálculos e inseriu o número do processo SEI de pagamento.
6.  **Pago (Liquidado):** O pagamento foi efetuado pelo financeiro. A rota sai das pendências e vai para a aba **"Pagamentos Liquidados"**.

---

## 🛠️ Passo a Passo no Painel Administrativo

### 1. Onde encontrar as rotas concluídas?
Assim que o recenseador termina uma rota, ela aparece automaticamente na aba **"Tarefas Concluídas"**.
*   As rotas são agrupadas por **Macrorregião**.
*   Nesta fase, você deve revisar as fotos e informações enviadas pelo recenseador.

### 2. Usando a Calculadora Financeira (Anexo III)
Para gerar a memória de cálculo de uma rota:
1.  Acesse a aba **"Calculadora Financeira"**.
2.  Selecione o **Recenseador**.
3.  Selecione a **Rota** (Apenas rotas nos estágios 4, 5 ou 6 aparecem aqui).
4.  O sistema puxará automaticamente o endereço e os dados do contrato.
5.  Insira o preço da gasolina e clique em **Gerar Memória de Cálculo (PDF)**.

### 3. Movendo para "Envio de Pagamento" (Passo 5)
Após gerar o cálculo:
1.  Vá na aba **"Wizard de Andamento"**.
2.  Localize a rota e mude o seletor para **"5. Envio Pagamento"**.
3.  Insira o número do **Processo SEI** de pagamento no campo que aparecerá.
4.  Clique no botão **"Atualizar"** para salvar a alteração.

### 4. Anexando a Memória de Cálculo e Liquidando (Passo 6)
Quando o pagamento for efetuado:
1.  Mude o estágio da rota para **"6. Pago (Liquidado)"**. A rota sumirá do Wizard e irá para a aba **"Pagamentos Liquidados"**.
2.  Acesse a aba **"Pagamentos Liquidados"**.
3.  Na coluna **"Memória de Cálculo (PDF)"**, clique em **"Selecionar PDF"** e anexe o arquivo gerado anteriormente pela calculadora.
4.  Clique na **setinha de upload** para salvar o documento permanentemente junto à rota.

---

## 🔄 Como "Voltar" uma Rota? (Correções)
Se você cometeu um erro ou precisa que uma rota volte para a calculadora:
*   **Se estiver na aba "Pagamentos Liquidados":** Clique no botão **"-1 Estágio"**. Ela voltará para o Passo 5.
*   **Se estiver no "Wizard":** Basta mudar o seletor para o número anterior (ex: de 5 para 4).

---

## 💡 Dicas Importantes
*   **Filtro de Visibilidade:** Se uma rota não aparece na Calculadora, verifique se ela não está nos estágios iniciais (Passos 1 a 3) por engano.
*   **Documentos:** Sempre anexe o PDF da calculadora na aba de Liquidados para manter o histórico fiscal completo e auditável.
