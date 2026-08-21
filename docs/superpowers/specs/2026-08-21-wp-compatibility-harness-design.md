# Harness de Compatibilidade WordPress — WP24H MD Importer

**Data:** 2026-08-21  
**Status:** aguardando revisão humana antes do planejamento de implementação  
**Repositório:** asllanmaciel/wp24h-md-importer  
**Origem:** radar/2026/08/2026-08-20.md

## Propósito

Criar um harness de compatibilidade permanente e reproduzível para validar o WP24H MD Importer contra versões específicas do WordPress antes de alterar a compatibilidade declarada no readme.

A primeira matriz é:

| WordPress | Objetivo |
|---|---|
| 7.1 final | validar a próxima compatibilidade declarada |
| 7.0.4 | regressão curta contra a versão estável anterior e seu fluxo de mídia |

## Resultado desejado

Um único comando local deve iniciar um ambiente descartável em Docker, instalar/ativar o plugin, executar a matriz de smoke/regressão e produzir um relatório que registre versão do WordPress, versão do PHP, versão do plugin, checks executados e resultado.

O harness não altera Tested up to. Essa alteração é um PR posterior, permitido somente se o gate do WordPress 7.1 passar e o relatório ficar versionado.

## Arquitetura

O harness usa containers efêmeros de WordPress e banco de dados. O plugin é montado como volume no diretório de plugins e o runner usa WP-CLI dentro do ambiente para instalar o Core, ativar o plugin, criar usuários/posts de teste e executar verificações determinísticas.

A versão do WordPress é um parâmetro obrigatório. O script deve encerrar com código diferente de zero se a imagem/versão pedida não estiver disponível ou se qualquer check falhar. Nenhum resultado parcial deve ser confundido com compatibilidade aprovada.

## Estrutura proposta

~~~text
docker/
  compatibility.compose.yml
scripts/
  compatibility-check.ps1
  compatibility-check.sh
tests/
  compatibility/
    run.php
    fixtures/
      basic.md
      complete.md
      featured.png
      featured.jpg
      featured.webp
docs/
  compatibility.md
reports/
  .gitkeep
~~~

- docker/compatibility.compose.yml: WordPress, MariaDB e runner WP-CLI; volumes, health checks e nomes isolados pelo perfil de versão.
- scripts/compatibility-check.ps1: comando canônico para Windows; valida Docker, inicia a matriz, coleta relatório e limpa recursos.
- scripts/compatibility-check.sh: interface equivalente para CI/Linux/macOS, sem lógica divergente.
- tests/compatibility/run.php: runner determinístico de checks usando APIs WordPress e o plugin real.
- tests/compatibility/fixtures/: Markdown e imagens estáveis, sem dependência de rede pública.
- docs/compatibility.md: versões testadas, pré-requisitos, comando, checks e interpretação de falhas.
- reports/: artefatos locais ignorados pelo Git, exceto relatórios resumidos que venham a ser promovidos deliberadamente.

## Matriz de checks

### Gate comum

1. WordPress instala e fica saudável.
2. Plugin ativa sem erro.
3. Importação Markdown básica cria post com conteúdo esperado.
4. Importação Markdown completa aplica front matter, categorias, tags, excerpt e metadados.
5. Reimportar o mesmo slug atualiza o post, sem criar duplicata.
6. Endpoint REST permanece desabilitado por padrão.
7. Endpoint REST habilitado exige autenticação e capability edit_posts.
8. Usuário sem upload_files não pode importar featured image.
9. Usuário sem publish_posts não pode forçar status publish/private.

### Gate de mídia

1. Fixture PNG cria featured image.
2. Fixture JPEG cria featured image.
3. Fixture WebP cria featured image.
4. Reimportar a mesma source URL reutiliza attachment.
5. URL inválida é rejeitada sem criar attachment.
6. Logs não contêm PHP warning, notice ou fatal novo.

O runner não baixa arquivos externos: o ambiente serve fixtures localmente para que o teste seja determinístico.

## Segurança e isolamento

- Nenhuma credencial real, URL de produção ou dado de cliente é usado.
- Banco, volumes e rede recebem prefixo exclusivo do harness.
- O script faz cleanup apenas dos recursos que criou e nomeou.
- A execução não altera readme.txt, release, tag ou estado de produção.
- O relatório indica claramente PASS, FAIL ou BLOCKED; BLOCKED não autoriza mudança de compatibilidade.

## Fluxo de execução

~~~text
validar Docker
→ iniciar banco e WordPress para uma versão
→ aguardar health check
→ instalar/ativar plugin
→ servir fixtures locais
→ rodar tests/compatibility/run.php
→ salvar versão, checks e resultado
→ repetir para próxima versão
→ remover somente recursos do harness
~~~

## Critérios de aceite

O harness está pronto quando:

- ambos os comandos PowerShell e shell aceitam --wp-version;
- a imagem WordPress solicitada é verificada antes de iniciar a matriz;
- um erro de check retorna exit code não zero;
- as duas versões iniciais executam o mesmo conjunto de checks;
- um relatório por execução identifica WordPress, PHP, plugin e resultado;
- os fixtures não dependem de internet;
- a limpeza não remove containers/volumes sem o prefixo do harness;
- documentação permite que outra pessoa repita o gate em Docker.

## Limites de escopo

Esta iniciativa não:

- altera Tested up to;
- publica release ou tag;
- configura CI remoto;
- testa staging ou produção;
- altera comportamento de importação do plugin;
- substitui teste manual exploratório de UX administrativa.

Um failure no harness deve primeiro gerar diagnóstico e, se necessário, uma mudança de plugin orientada por teste em plano/PR separado.
