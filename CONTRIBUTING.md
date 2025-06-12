# Diretrizes de Contribuição para o Simple Log Viewer

Primeiramente, obrigado pelo seu interesse em contribuir com o Simple Log Viewer! Toda ajuda é bem-vinda.

## Como Contribuir

Existem várias maneiras de contribuir:

*   **Reportando Bugs:** Se você encontrar um bug, por favor, abra uma [Issue](https://github.com/silvanei/simple-log-viewer/issues) detalhando o problema, os passos para reproduzi-lo e o comportamento esperado versus o observado.
*   **Sugerindo Melhorias:** Tem alguma ideia para uma nova funcionalidade ou melhoria? Abra uma [Issue](https://github.com/silvanei/simple-log-viewer/issues) descrevendo sua sugestão.
*   **Enviando Pull Requests:** Se você deseja corrigir um bug ou implementar uma nova funcionalidade, siga os passos abaixo.

## Processo de Desenvolvimento

1.  **Fork o Repositório:** Crie um fork do projeto para a sua conta no GitHub.
2.  **Clone o Fork:** Clone o seu fork localmente: `git clone https://github.com/SEU-USUARIO/simple-log-viewer.git`
3.  **Crie uma Branch:** Crie uma branch descritiva para sua alteração: `git checkout -b minha-nova-feature` ou `git checkout -b fix/corrige-bug-x`.
4.  **Desenvolva:** Faça as alterações necessárias no código.
5.  **Teste:** Certifique-se de que todos os testes existentes passam (`make test`). Se estiver adicionando uma nova funcionalidade ou corrigindo um bug, adicione novos testes que cubram suas alterações.
6.  **Verifique a Qualidade do Código:** Rode as ferramentas de qualidade: `make phpcs` e `make phpstan`. Corrija quaisquer problemas apontados.
7.  **Commit:** Faça commits claros e concisos: `git commit -m 'Feat: Adiciona funcionalidade X'` ou `git commit -m 'Fix: Corrige problema Y na classe Z'`.
8.  **Push:** Envie suas alterações para o seu fork: `git push origin minha-nova-feature`.
9.  **Abra um Pull Request (PR):** Vá até o repositório original (`silvanei/simple-log-viewer`) e abra um Pull Request da sua branch para a branch `main` do repositório original. Descreva suas alterações no PR.

## Padrões de Código

O projeto utiliza o PHP CodeSniffer (PHPCS) com um padrão a ser definido (geralmente PSR-12). Certifique-se de que seu código segue os padrões executando `make phpcs`.

## Reportando Vulnerabilidades de Segurança

Se você descobrir uma vulnerabilidade de segurança, por favor, **não** abra uma Issue pública. Envie um e-mail diretamente para o mantenedor (o e-mail pode ser encontrado no `composer.json` ou perfil do GitHub) com os detalhes.

Obrigado por contribuir!
