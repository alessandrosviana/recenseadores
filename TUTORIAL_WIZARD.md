# 📖 Tutorial: Fluxo de Andamento e Pagamento de Rotas (Wizard)

Este guia explica como funciona o sistema de estágios (Wizard) das rotas, desde a atribuição inicial até o pagamento final ao recenseador.

---

## 🚀 Os 5 Estágios da Rota

O sistema utiliza 5 estágios para controlar o ciclo de vida de cada trabalho:

1.  **Documentação:** Usuário cadastrado, mas sem rota atribuída.
2.  **Rota Disponível (Atribuída):** O administrador atribuiu uma tarefa, mas o recenseador ainda não a concluiu.
3.  **Conclusão (Revisão Fiscal):** O recenseador finalizou o trabalho no aplicativo. A rota agora aparece em **"Tarefas Concluídas"** para conferência.
4.  **Envio para Pagamento:** O administrador revisou, calculou os valores e inseriu o número do processo SEI.
5.  **Pago/Liquidado:** O pagamento foi confirmado. A rota sai das pendências e vai para o histórico definitivo.

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
3.  Selecione a **Rota** (Apenas rotas nos estágios 3 ou 4 aparecem aqui).
4.  O sistema puxará automaticamente o endereço e os dados do contrato.
5.  Insira o preço da gasolina e clique em **Gerar Memória de Cálculo (PDF)**.

### 3. Movendo para "Envio de Pagamento" (Passo 4)
Após gerar o cálculo:
1.  Vá na aba **"Wizard de Andamento"**.
2.  Localize a rota e mude o seletor para **"4. Envio Pagamento"**.
3.  Insira o número do **Processo SEI** de pagamento no campo que aparecerá.
4.  Salve a alteração.

### 4. Anexando a Memória de Cálculo e Liquidando (Passo 5)
Quando o pagamento for efetuado:
1.  Mude o estágio da rota para **"5. Pago/Liquidado"**. A rota sumirá desta lista e irá para a aba **"Pagamentos Liquidados"**.
2.  Acesse a aba **"Pagamentos Liquidados"**.
3.  Na coluna **"Memória de Cálculo (PDF)"**, clique em **"Selecionar PDF"** e anexe o arquivo gerado anteriormente pela calculadora.
4.  Clique na **setinha de upload** para salvar o documento permanentemente junto à rota.

---

## 🔄 Como "Voltar" uma Rota? (Correções)
Se você cometeu um erro ou precisa que uma rota volte para a calculadora:
*   **Se estiver na aba "Pagamentos Liquidados":** Clique no botão **"-1 Estágio"**. Ela voltará para o Passo 4.
*   **Se estiver no "Wizard":** Basta mudar o seletor para o número anterior (ex: de 4 para 3).

---

## 💡 Dicas Importantes
*   **Filtro de Visibilidade:** Se uma rota não aparece na Calculadora, verifique se ela não foi marcada como "Liquidada" (Passo 5) por engano.
*   **Documentos:** Sempre anexe o PDF da calculadora na aba de Liquidados para manter o histórico fiscal completo e auditável.
