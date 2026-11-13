# Entrega – Teste de Desenvolvimento

**Nome:** Weberson Lopes  
**Tempo utilizado:** 1 hora e 45 minutos

## O que foi feito

- [x] Filtros da listagem por empresa, departamento e status, inclusive combinados.
- [x] Cadastro de entregas com seleção de empresa e obrigação, competência e vencimento.
- [x] Marcar como entregue uma única linha.
- [x] Marcar várias entregas como concluídas utilizando caixas de seleção.
- [x] Validação de dados no backend e prevenção de cadastros duplicados.
- [x] Atualização da listagem e dos indicadores após operações.
- [x] Feedback visual de sucesso e erro.
- [x] Melhoria visual para indicar quando o botão de marcação múltipla está desabilitado.

## Decisões tomadas

**Estrutura do projeto:** mantive a arquitetura existente, separando a interface em HTML, CSS e JavaScript, as requisições HTTP nos arquivos da pasta `api/` e as operações de banco de dados na classe `EntregaRepository`, utilizando PHP e PDO.

**Filtros:** implementei filtros opcionais por nome da empresa, departamento e status. Os parâmetros são enviados à API e aplicados na consulta SQL. A listagem pode combinar vários filtros ao mesmo tempo.

**Cadastro:** disponibilizei empresas e obrigações existentes no banco em campos de seleção. No backend, validei os identificadores, o formato da competência, a validade da data e a existência dos registros relacionados. Também impedi o cadastro repetido da mesma obrigação para a mesma empresa e competência, utilizando validação na aplicação e restrição `UNIQUE` no MySQL.

**Conclusão individual e múltipla:** utilizei consultas `UPDATE` com a condição `data_entrega IS NULL`, evitando alterar novamente entregas já concluídas. Na conclusão múltipla, utilizei parâmetros preparados para os IDs selecionados e `rowCount()` para retornar a quantidade efetivamente atualizada.

**Indicadores:** após operações de cadastro ou conclusão, a interface consulta novamente os dados. Os indicadores representam o total geral do banco, independentemente dos filtros aplicados à listagem.

**Interface:** mantive o JavaScript sem dependências adicionais, reaproveitando as funções existentes de carregamento, renderização e apresentação de mensagens.

## Como executar

1. Iniciar Apache e MySQL pelo XAMPP.
2. Copiar a pasta `painel-entregas` para `C:\xampp\htdocs\`.
3. Acessar o phpMyAdmin e importar `database/painel_entregas.sql`. O script cria o banco `painel_entregas` e seus dados iniciais.
4. Conferir as configurações de conexão em `src/config.php`, adequando-as ao ambiente local se necessário.
5. Abrir `http://localhost/painel-entregas/index.html` no navegador.

## Como testar

**Filtros:** pesquisar parte do nome de uma empresa, selecionar um departamento e um status. Verificar o resultado individualmente e em combinações. Utilizar o botão "Limpar filtros" para restaurar a listagem.

**Cadastro:** selecionar empresa, obrigação, competência e vencimento e cadastrar uma nova entrega. Confirmar que o registro aparece na listagem e que o indicador total aumenta. Repetir o mesmo cadastro e verificar que a duplicidade é bloqueada.

**Marcação individual:** selecionar uma entrega pendente ou atrasada e clicar em "Marcar como entregue". Conferir a data da conclusão, o status e a atualização dos indicadores.

**Marcação múltipla:** selecionar duas ou mais entregas ainda não concluídas. Verificar se o contador do botão corresponde à quantidade selecionada e concluir as entregas. Conferir a mensagem de sucesso, as datas, os indicadores e o retorno do contador para zero.

**Persistência:** atualizar a página após cadastrar ou concluir entregas e verificar que as alterações permanecem armazenadas no MySQL.

## O que ficou faltando ou faria com mais tempo

Com mais tempo, aprimoraria a responsividade da tabela para dispositivos móveis e implementaria uma confirmação antes da execução de ações em massa, reduzindo o risco de alterações acidentais.

Também consideraria mecanismos adicionais de autorização para operações sensíveis, como a exigência de autenticação ou confirmação explícita do usuário.

Outra evolução interessante seria desenvolver um dashboard analítico, com gráficos e indicadores que permitissem acompanhar as entregas por empresa, departamento, competência e status, facilitando a identificação de atrasos e oportunidades de melhoria.

Por fim, aprimoraria o tratamento de erros e a experiência de carregamento durante as requisições à API.