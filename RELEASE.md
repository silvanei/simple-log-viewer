# Release Process

Este documento descreve o processo completo para criar uma nova release do Simple Log Viewer.

## 📋 Pré-requisitos

- Acesso de escrita ao repositório (`silvanei/simple-log-viewer`)
- Docker instalado para build local e geração de changelog
- Imagem de desenvolvimento construída: `make image`

### 🔧 Instalação do git-cliff

O `git-cliff` já vem pré-instalado na imagem Docker de desenvolvimento (`silvanei/simple-log-viewer:dev`). Basta executar via Makefile:

```bash
# Recomendado — usa git-cliff da imagem Docker
make changelog VERSION=1.4.0

# Ou diretamente no container
make sh
/app $ git-cliff --tag v1.4.0 --unreleased
```

Se quiser instalar localmente (alternativa):
```bash
# Com cargo (Rust)
cargo install git-cliff

# macOS
brew install git-cliff
```

## 🏷️ Convenção de Commits

O projeto utiliza [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>: <description>
```

| Tipo | Uso |
|------|-----|
| `feat` | Nova funcionalidade |
| `fix` | Correção de bug |
| `docs` | Documentação |
| `test` | Testes |
| `refactor` | Refatoração |
| `style` | Formatação/código estilo |
| `chore` | Manutenção |
| `perf` | Performance |
| `ci` | CI/CD |

## 🔄 Passo a Passo para Criar uma Release

### 1. Prepare a branch de release

```bash
# Certifique-se de estar na main atualizada
git checkout main
git pull

# Crie uma branch de release
git checkout -b release/v1.4.0
```

### 2. Gere o changelog

**Opção A — Usando o subagente opencode (recomendado):**
```bash
# Invoque o subagente changelog-generator
# Ele executará git-cliff e atualizará o CHANGELOG.md
```

**Opção B — Usando Makefile:**
```bash
make changelog VERSION=1.4.0
```

**Opção C — Direto com git-cliff:**
```bash
git cliff --tag v1.4.0 --unreleased
```

### 3. Revise e enriqueça o CHANGELOG.md

O git-cliff gera uma **base** com os commits agrupados por tipo. Revise e enriqueça:

- Adicione **"Technical Details"** para mudanças complexas
- Adicione **"Usage Examples"** code snippets para novas features
- Adicione **"Dependencies Updated"** se houver mudanças no `composer.json`
- Destaque **Breaking Changes** se houver
- Mantenha o cabeçalho `[Unreleased]` para mudanças futuras

### 4. Atualize a versão no composer.json

```bash
# Altere manualmente ou use o subagente
# "version": "1.3.0" → "version": "1.4.0"
```

### 5. Commite e crie PR

```bash
git add CHANGELOG.md composer.json
git commit -m "chore: release v1.4.0"
git push origin release/v1.4.0
```

Abra um Pull Request no GitHub de `release/v1.4.0` para `main`.

### 6. Após o merge do PR, crie a tag

```bash
git checkout main
git pull
git tag v1.4.0
git push origin v1.4.0
```

### 7. CI constrói as imagens automaticamente

O GitHub Actions detecta o push da tag `v1.4.0` e gera:

| Tag Docker | Descrição |
|------------|-----------|
| `silvanei/simple-log-viewer:1.4.0` | Versão exata |
| `silvanei/simple-log-viewer:1.4` | Major.Minor |
| `silvanei/simple-log-viewer:1` | Major |
| `silvanei/simple-log-viewer:latest` | Última versão estável |

## 🐳 Build Local (Opcional)

Para testar a imagem de produção localmente antes do release:

```bash
make build-production VERSION=1.4.0
```

Isso constrói:
- `silvanei/simple-log-viewer:1.4.0`
- `silvanei/simple-log-viewer:latest`

## 📊 Exemplo Completo

```bash
# Preparação
git checkout main && git pull
git checkout -b release/v1.4.0

# Gera changelog
git cliff --tag v1.4.0 --unreleased

# Edita CHANGELOG.md (adiciona detalhes técnicos)
# Edita composer.json (bumpa versão)

# Commit e PR
git add CHANGELOG.md composer.json
git commit -m "chore: release v1.4.0"
git push origin release/v1.4.0
# → Abre PR no GitHub, revisa e mergeia

# Tag
git checkout main && git pull
git tag v1.4.0 && git push origin v1.4.0
# → CI constrói as imagens automaticamente 🎉
```

## ❓ Troubleshooting

### A tag foi criada mas o CI não disparou
Verifique se a tag segue o padrão `v*.*.*` (ex: `v1.4.0`, não `1.4.0` ou `v1.4`).

### O changelog está vazio
Verifique se os commits da branch seguem Conventional Commits. Commits sem padrão caem no grupo "Changed".

### Docker build falhou localmente
Certifique-se de que o Docker está rodando e você tem permissão para executar `docker build`.
