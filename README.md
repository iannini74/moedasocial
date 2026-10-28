# Portal Moeda Social — moedasocial.com

Portal de conhecimento sobre moedas sociais, economia circular e desenvolvimento econômico local. Desenvolvido pela [TribeX](https://tribex.com.br), com fomento da Moeda Nobre.

## O que tem

- **Estudos** (Markdown em `content/estudos/`): conceito, economia circular, finalidades, cases mundiais e brasileiros, marco legal, TCU/TCEs e juristas, projetos de lei e autoridades, riscos, Reforma Tributária e roteiro de implantação — todos com fontes citadas.
- **Notícias diárias** agregadas de fontes públicas (`app/lib/News.php`, feeds em `app/config.php`).
- **Blog** com agente editorial (Claude, com pesquisa na web) que publica a cada 15 dias e avisa a newsletter (`app/lib/BlogAgent.php`). Artigos iniciais em `content/blog/`.
- **Por que adotar** (página de convencimento) e **Diagnóstico gratuito** para municípios, com relatório em PDF/impressão (`assets/js/diagnostico.js`).
- **Newsletter** com dupla confirmação, **formulário de apoio à implantação** (e-mail para a equipe + resposta automática), **Privacidade, Cookies e Termos** (LGPD).
- **Analytics próprio sem cookies** e **painel administrativo** em `/admin`.
- **SEO e IA**: JSON-LD, sitemap, robots com robôs de IA liberados, `llms.txt`, `llms-full.txt`, RSS e IndexNow.
- Anúncios do patrocinador sempre sinalizados como "Anúncio" (`app/views/partials/ad.php`).

## Stack

PHP 8.1+ sem framework, SQLite (ou MySQL), SDK oficial da Anthropic, Parsedown. Dependências já incluídas em `vendor/`.

## Rodar localmente

```bash
cp app/config.sample.php app/config.local.php   # ajuste: site.url = http://localhost:8000, mail.transport = log
php -S localhost:8000 index.php
```

## Publicar

Veja [DEPLOY.md](DEPLOY.md).

## Editar conteúdo

- Estudos: edite os arquivos `.md` em `content/estudos/` (front matter + Markdown; citações `[^n]` e seção `## Fontes`; perguntas em `## Perguntas frequentes`).
- Novos artigos manuais: crie `content/blog/AAAA-MM-DD-slug.md` — são importados automaticamente.
- Textos dos anúncios: `app/views/partials/ad.php` e `app/lib/Brand.php`.

© TribeX — todos os direitos reservados.
