# Implantação na Hostinger — Portal Moeda Social

Guia passo a passo para publicar o portal em **moedasocial.com** (hospedagem compartilhada/Cloud da Hostinger, PHP).

## Requisitos

- PHP **8.1 ou superior** (recomendado 8.3), com extensões `pdo_sqlite` (ou `pdo_mysql`), `curl`, `mbstring`, `openssl` — todas ativas por padrão na Hostinger.
- Uma caixa de e-mail `contato@moedasocial.com` criada no hPanel (para envio via SMTP).
- Chave da API da Anthropic (console.anthropic.com) para o agente editorial do blog.

A pasta `vendor/` já vai no repositório: **não é preciso rodar Composer no servidor**.

## 1. Publicar o código (deploy via Git)

1. hPanel → **Sites → moedasocial.com → Avançado → Git**.
2. Repositório: `https://github.com/iannini74/moedasocial` · Branch: `main` · Diretório: deixe em branco (`public_html`).
3. Clique em **Criar** e depois em **Implantar**. Ative o **Auto Deployment** (webhook do GitHub) para publicar a cada push.

> O diretório `public_html` precisa estar vazio na primeira implantação. Faça backup do que houver lá.

## 2. Arquivo de configuração (fora da pasta pública)

1. Gerenciador de Arquivos → navegue até `/home/SEU_USUARIO/domains/moedasocial.com/` (um nível **acima** de `public_html`).
2. Crie o arquivo `moedasocial.config.php` copiando o conteúdo de `app/config.sample.php` e preencha:
   - `secret` e `cron_token`: strings aleatórias longas;
   - `admin.password_hash`: gere em **Avançado → Terminal (SSH)** com
     `php -r "echo password_hash('SUA_SENHA_FORTE', PASSWORD_DEFAULT);"`;
   - `mail.smtp_user` / `mail.smtp_pass`: a caixa `contato@moedasocial.com`;
   - `ai.anthropic_api_key`: sua chave da Anthropic;
   - `seo.indexnow_key`: `php -r "echo bin2hex(random_bytes(16));"`.
3. Permissões: a pasta `public_html/storage` precisa ser gravável (755 já basta na Hostinger).

O banco SQLite é criado automaticamente em `storage/` no primeiro acesso. Para usar MySQL, crie o banco no hPanel e preencha a chave `db` (host `127.0.0.1`).

## 3. SSL e domínio

- hPanel → **Segurança → SSL**: instale o certificado gratuito. O `.htaccess` já redireciona HTTP → HTTPS e `www` → domínio sem `www`.

## 4. Rotinas automáticas (Cron Jobs)

hPanel → **Avançado → Cron Jobs → Personalizado**. Troque `SEU_USUARIO` pelo usuário da conta:

| Frequência | Comando | O que faz |
|---|---|---|
| `0 */6 * * *` | `php /home/SEU_USUARIO/domains/moedasocial.com/public_html/cron/run.php news` | Atualiza as notícias |
| `30 9 * * *` | `php /home/SEU_USUARIO/domains/moedasocial.com/public_html/cron/run.php blog` | Verifica diariamente; publica um artigo a cada 15 dias e avisa os inscritos |
| `15 * * * *` | `php /home/SEU_USUARIO/domains/moedasocial.com/public_html/cron/run.php newsletter` | Continua envios da newsletter em lotes (limite do SMTP) |
| `0 4 * * 0` | `php /home/SEU_USUARIO/domains/moedasocial.com/public_html/cron/run.php manutencao` | Limpeza conforme a política de retenção (LGPD) |

Contingência: mesmo sem cron, as notícias se atualizam sozinhas a cada 6 horas no primeiro acesso após o vencimento. Também é possível disparar por URL: `https://moedasocial.com/cron/SEU_CRON_TOKEN/news`.

Para gerar um artigo imediatamente: painel **/admin/blog → Gerar artigo agora**, ou `php cron/run.php blog --force`.

## 5. E-mail (entregabilidade)

No hPanel → **E-mails → moedasocial.com**, confirme que os registros **SPF, DKIM e DMARC** estão ativos. Sem eles, os e-mails de confirmação e os contatos para `contato@tribex.com` podem cair no spam.

## 6. Buscadores e assistentes de IA

1. **Google Search Console**: adicione a propriedade, coloque o código em `seo.google_verify` e envie `https://moedasocial.com/sitemap.xml`.
2. **Bing Webmaster Tools** (alimenta o ChatGPT Search e o Copilot): idem com `seo.bing_verify`; o IndexNow já avisa o Bing a cada novo artigo.
3. Arquivos já publicados automaticamente: `/robots.txt` (libera GPTBot, ClaudeBot, PerplexityBot, Google-Extended etc.), `/sitemap.xml`, `/llms.txt`, `/llms-full.txt`, `/feed.xml` e dados estruturados JSON-LD (Organization, Article, FAQPage, BreadcrumbList, DefinedTermSet, WebApplication) em todas as páginas.

## 7. Área administrativa

- Endereço: `https://moedasocial.com/admin` (usuário e senha definidos no passo 2).
- **Analytics**: visitas, visitantes, sessões, tempo, rolagem, rejeição, dias × horários, páginas, seções, origem (buscadores, **assistentes de IA**, redes sociais, e-mail, campanhas UTM), cidades/estados/países, dispositivos, navegadores, idiomas, funil do diagnóstico, desempenho dos anúncios (impressões, cliques, CTR), robôs de busca e de IA, tempo real e exportação CSV.
- **Contatos**, **Inscritos**, **Diagnósticos** (municípios que usaram o simulador), **Blog** e **Notícias** (fixar/ocultar).

## 8. Checklist pós-publicação

- [ ] Abrir o site em HTTPS e no celular
- [ ] Enviar um contato de teste e conferir a chegada em `contato@tribex.com` e a resposta automática
- [ ] Inscrever-se na newsletter e confirmar pelo e-mail
- [ ] Gerar um diagnóstico e baixar o PDF
- [ ] Entrar no `/admin` e verificar o Analytics
- [ ] Gerar o primeiro artigo pelo agente (opcional) e revisar
- [ ] Cadastrar o site no Google Search Console e no Bing Webmaster Tools
