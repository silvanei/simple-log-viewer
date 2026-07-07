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
7.  **Commit:** Faça commits seguindo [Conventional Commits](https://www.conventionalcommits.org/):
    ```
    <tipo>: <descrição>
    ```
    Tipos: `feat`, `fix`, `docs`, `test`, `refactor`, `style`, `chore`, `perf`, `ci`.
    Exemplos:
    ```bash
    git commit -m "feat: add column reordering"
    git commit -m "fix: correct datetime validation"
    git commit -m "test: add boundary tests for search limit"
    git commit -m "docs: update API documentation"
    ```
8.  **Push:** Envie suas alterações para o seu fork: `git push origin minha-nova-feature`.
9.  **Abra um Pull Request (PR):** Vá até o repositório original (`silvanei/simple-log-viewer`) e abra um Pull Request da sua branch para a branch `main` do repositório original. Descreva suas alterações no PR.

> 📖 Consulte o guia completo em [RELEASE.md](RELEASE.md) para instruções detalhadas.

## 🏷️ Fluxo de Release

### Como criar um release

1. **Prepare a branch de release:**
   ```bash
   git checkout main && git pull
   git checkout -b release/v1.4.0
   ```

2. **Gere o changelog automaticamente:**
   ```bash
   # Via Makefile
   make changelog VERSION=1.4.0
   
   # Ou diretamente com git-cliff
   git cliff --tag v1.4.0 --unreleased
   ```

3. **Atualize o `CHANGELOG.md`:**
   - Integre o changelog gerado
   - Enriqueça com detalhes técnicos e exemplos (se necessário)
   - Mantenha o cabeçalho `[Unreleased]` para mudanças futuras

4. **Atualize a versão no `composer.json`:**
   ```bash
   # Altere "version": "1.3.0" para "version": "1.4.0"
   ```

5. **Commite e crie PR:**
   ```bash
   git add CHANGELOG.md composer.json
   git commit -m "chore: release v1.4.0"
   git push origin release/v1.4.0
   ```
   Abra um PR de `release/v1.4.0` para `main`.

6. **Após o merge, crie a tag:**
   ```bash
   git checkout main && git pull
   git tag v1.4.0
   git push origin v1.4.0
   ```

7. **CI constrói automaticamente** as imagens Docker:
   - `silvanei/simple-log-viewer:1.4.0`
   - `silvanei/simple-log-viewer:1.4`
   - `silvanei/simple-log-viewer:1`
   - `silvanei/simple-log-viewer:latest`

### Changelog (git-cliff)

O projeto usa [git-cliff](https://git-cliff.org/) para gerar changelogs a partir do histórico de commits.

- **Configuração**: `cliff.toml` (raiz do projeto)
- **Commits seguindo Conventional Commits** são agrupados por tipo (Added, Fixed, etc.)
- **Commits antigos** (sem padrão definido) são agrupados em "Changed"
- **Sempre revise** o changelog gerado e adicione detalhes técnicos quando necessário

## Padrões de Código

O projeto utiliza o PHP CodeSniffer (PHPCS) com um padrão a ser definido (geralmente PSR-12). Certifique-se de que seu código segue os padrões executando `make phpcs`.

## Reportando Vulnerabilidades de Segurança

Se você descobrir uma vulnerabilidade de segurança, por favor, **não** abra uma Issue pública. Envie um e-mail diretamente para o mantenedor (o e-mail pode ser encontrado no `composer.json` ou perfil do GitHub) com os detalhes.

Obrigado por contribuir!
