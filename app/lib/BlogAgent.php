<?php
declare(strict_types=1);

/**
 * Agente editorial: a cada N dias (padrão 15) pesquisa na web e escreve um
 * artigo original, publica no blog, avisa o IndexNow e dispara a newsletter.
 *
 * Usa a API Responses da OpenAI em duas etapas separadas:
 * Etapa 1 — pesquisa com a ferramenta web_search (fatos recentes + fontes).
 * Etapa 2 — redação com saída estruturada (JSON validado por schema).
 * Os modelos de ai.models são tentados em ordem caso algum não esteja disponível.
 */
final class BlogAgent
{
    private const PILLARS = [
        'Como a moeda social aumenta a circulação de renda no comércio local (efeito multiplicador)',
        'Reforma Tributária (IBS/CBS) e o interesse dos municípios em reter o consumo local',
        'Lições de Maricá (Mumbuca) e Niterói (Arariboia) para municípios de pequeno e médio porte',
        'Bancos comunitários de desenvolvimento: história, Banco Palmas e o papel hoje',
        'Segurança jurídica: lei municipal, lastro 1:1, arranjo de pagamento fechado e controle externo',
        'Transparência e prestação de contas ao Tribunal de Contas em programas de moeda social',
        'Moeda social e economia circular: reciclagem, catadores e incentivos à sustentabilidade',
        'Como engajar e credenciar o comércio local em um programa de moeda social',
        'Moeda social digital versus cartões e vales: custos, inclusão e rastreabilidade',
        'Erros que levam programas de moeda social a serem suspensos e como evitá-los',
        'Moeda social para empresas: benefícios, ESG e incentivos que ficam na cidade',
        'Cases internacionais de moedas locais: o que o Brasil pode aprender',
        'Indicadores para medir o impacto de uma moeda social no município',
        'Transição de gestão: como blindar um programa de moeda social da descontinuidade',
        'Turismo e moeda social: cashback local e dinheiro novo entrando na cidade',
    ];

    public static function due(): bool
    {
        $last = (int) Db::value("SELECT MAX(published_at) FROM posts WHERE generated_by = 'agente-ia'");
        $lastAny = (int) Db::value('SELECT MAX(published_at) FROM posts');
        $ref = max($last, $lastAny);
        return time() - $ref >= cfg('ai.blog_interval_days', 15) * 86400 - 3600;
    }

    /** @return array{ok:bool, msg:string, id?:int, slug?:string} */
    public static function run(bool $force = false): array
    {
        if (!$force && !self::due()) {
            return ['ok' => false, 'msg' => 'Ainda não é hora: o próximo artigo sai ' . cfg('ai.blog_interval_days') . ' dias após o último.'];
        }
        if (!cfg('ai.openai_api_key')) {
            return ['ok' => false, 'msg' => 'Configure ai.openai_api_key para ativar o agente editorial.'];
        }
        if (Db::setting('blog_agent_lock') && time() - (int) Db::setting('blog_agent_lock') < 1800) {
            return ['ok' => false, 'msg' => 'Uma geração já está em andamento.'];
        }
        Db::setting('blog_agent_lock', (string) time());

        try {
            $previous = array_column(Db::all('SELECT title FROM posts ORDER BY published_at DESC LIMIT 25'), 'title');
            $headlines = array_column(News::latest(15), 'title');
            $pillar = self::PILLARS[(int) (time() / 86400 / 15) % count(self::PILLARS)];

            $brief = self::research($pillar, $previous, $headlines);
            $article = self::write($brief, $previous);

            $body = trim($article['body_markdown']);
            if (mb_strlen($body) < 3000) {
                throw new RuntimeException('Artigo muito curto (' . mb_strlen($body) . ' caracteres).');
            }
            $id = Blog::create([
                'slug' => $article['slug'] ?: $article['title'],
                'title' => $article['title'],
                'description' => $article['description'],
                'body' => $body,
                'tags' => implode(', ', array_slice($article['tags'], 0, 6)),
                'sources' => $article['sources'],
                'faq' => $article['faq'],
                'generated_by' => 'agente-ia',
            ]);
            $post = Db::one('SELECT * FROM posts WHERE id = ?', [$id]);
            Seo::indexNow([url('/blog/' . $post['slug'], true), url('/blog', true), url('/', true)]);
            $sent = Newsletter::broadcastPost($post);
            log_line('blog-agent', "Publicado #$id {$post['slug']} — newsletter: $sent envios");
            return ['ok' => true, 'msg' => "Artigo publicado: {$post['title']} ($sent e-mails enviados)", 'id' => $id, 'slug' => $post['slug']];
        } catch (Throwable $e) {
            return self::fail($e->getMessage());
        } finally {
            Db::setting('blog_agent_lock', '0');
        }
    }

    private static function fail(string $msg): array
    {
        log_line('blog-agent', 'ERRO: ' . $msg);
        return ['ok' => false, 'msg' => $msg];
    }

    private static function editorialLine(): string
    {
        return <<<TXT
Você é o editor-chefe do Portal Moeda Social (moedasocial.com), referência brasileira em moedas sociais, economia circular, economia solidária e desenvolvimento econômico local. O público é formado por prefeitos, secretários de Fazenda e Desenvolvimento, vereadores, técnicos de Tribunais de Contas, lideranças comunitárias e comerciantes.

Linha editorial:
- Didática e prática: frases claras, exemplos concretos, números com data e fonte. Português do Brasil.
- Linguagem direta e fácil de entender, mesmo quando persuasiva: sem metáforas, sem slogans, sem frases de efeito e sem emojis. Prefira termos técnicos explicados em palavras simples.
- Rigor: nunca invente números, leis, decisões ou casos. Todo dado relevante precisa de fonte verificável. Diferencie fato de estimativa.
- Equilíbrio: mostre o que funciona e o que não funciona, riscos e como mitigá-los.
- O portal defende, como boas práticas, o modelo que considera mais seguro e eficiente para municípios: moeda social 100% digital (aplicativo com pagamento por QR Code, sem máquina de cartão nem cartão físico), paridade 1:1 com o real e lastro integral mantido em conta do próprio ente fomentador em banco parceiro (sem custódia por terceiros), arranjo de pagamento fechado de circulação restrita aos comércios credenciados do município, rastreabilidade de ponta a ponta com trilha de auditoria e relatórios prontos para o Tribunal de Contas e a LRF, regras parametrizáveis (validade do saldo, limites, públicos), integração com sistemas de PDV, credenciamento gratuito do comércio e nenhum custo de tecnologia para a prefeitura (remuneração por taxa transparente sobre vendas). Ao recomendar soluções, descreva essas características como critérios técnicos — nunca cite marcas, empresas ou produtos comerciais pelo nome.
- Sempre que pertinente, conecte o tema à Reforma Tributária (IBS no destino do consumo) e à retenção de renda local, sem exageros.
- Feche com chamada natural para o diagnóstico gratuito (/diagnostico) ou para o contato de apoio à implantação (/contato).
TXT;
    }

    private static function research(string $pillar, array $previous, array $headlines): string
    {
        $prev = $previous ? '- ' . implode("\n- ", $previous) : '(nenhum)';
        $news = $headlines ? '- ' . implode("\n- ", $headlines) : '(sem manchetes)';
        $today = date_long_br(time());
        $prompt = <<<TXT
Hoje é {$today}. Vamos preparar o próximo artigo do blog.

Tema-pilar sugerido para este ciclo: "{$pillar}". Você pode ajustar o recorte se as manchetes recentes indicarem um ângulo mais atual e relevante dentro do universo de moedas sociais, economia circular/solidária e regulação de pagamentos.

Artigos já publicados (não repita o ângulo):
{$prev}

Manchetes recentes captadas pelo portal:
{$news}

Pesquise na web fatos atuais e verificáveis (de preferência fontes primárias: gov.br, bcb.gov.br, planalto.gov.br, câmara/senado, tribunais de contas, leis municipais, estudos acadêmicos, imprensa de referência). Entregue um BRIEFING em português com:
1. Recorte final e título provisório;
2. 8 a 15 fatos/dados com data, cada um seguido da URL da fonte;
3. Pontos de atenção (riscos, controvérsias, decisões de órgãos de controle);
4. Lista final de fontes (título — URL).
TXT;
        $res = self::call([
            'instructions' => self::editorialLine(),
            'input' => $prompt,
            'tools' => [['type' => 'web_search', 'user_location' => ['type' => 'approximate', 'country' => 'BR', 'timezone' => 'America/Sao_Paulo']]],
        ]);
        $text = self::outputText($res);
        if (mb_strlen(trim($text)) < 500) {
            throw new RuntimeException('Briefing de pesquisa insuficiente.');
        }
        return $text;
    }

    private static function write(string $brief, array $previous): array
    {
        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['title', 'slug', 'description', 'tags', 'body_markdown', 'faq', 'sources'],
            'properties' => [
                'title' => ['type' => 'string', 'description' => 'Título atraente e informativo, até 90 caracteres'],
                'slug' => ['type' => 'string', 'description' => 'slug-em-minusculas-sem-acentos'],
                'description' => ['type' => 'string', 'description' => 'Resumo para SEO de 140 a 160 caracteres'],
                'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
                'body_markdown' => ['type' => 'string'],
                'faq' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object', 'additionalProperties' => false, 'required' => ['q', 'a'],
                        'properties' => ['q' => ['type' => 'string'], 'a' => ['type' => 'string']],
                    ],
                ],
                'sources' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object', 'additionalProperties' => false, 'required' => ['title', 'url'],
                        'properties' => ['title' => ['type' => 'string'], 'url' => ['type' => 'string']],
                    ],
                ],
            ],
        ];
        $prevTitles = implode(' | ', array_slice($previous, 0, 15)) ?: '(nenhum)';
        $prompt = <<<TXT
Com base no briefing abaixo, escreva o artigo completo.

Requisitos do campo body_markdown:
- 1.600 a 2.400 palavras, em Markdown, SEM o título H1 (ele é exibido à parte).
- Abra com um parágrafo-resumo de 2 a 3 frases que responda diretamente à pergunta central (bom para buscadores e assistentes de IA).
- Use subtítulos ## e ### descritivos, listas e pelo menos uma tabela comparativa quando fizer sentido.
- Cite fontes no texto com marcadores [^1], [^2]... na ordem em que aparecem.
- Inclua um bloco "> **Na prática:** ..." com orientação aplicável ao município.
- Inclua a seção "## Perguntas frequentes" com 3 a 5 perguntas em "### Pergunta?" seguidas da resposta.
- Termine com "## Fontes" contendo lista numerada "1. [Título](URL)" na mesma ordem dos marcadores.
- Use somente URLs presentes no briefing.

Os campos faq e sources devem repetir, de forma estruturada, as perguntas e fontes do texto.

Títulos já usados (evite semelhança): {$prevTitles}

BRIEFING:
{$brief}
TXT;
        $res = self::call([
            'instructions' => self::editorialLine(),
            'input' => $prompt,
            'text' => ['format' => ['type' => 'json_schema', 'name' => 'artigo', 'strict' => true, 'schema' => $schema]],
        ]);
        if (($res['status'] ?? '') === 'incomplete') {
            throw new RuntimeException('A redação ficou incompleta (' . ($res['incomplete_details']['reason'] ?? 'motivo desconhecido') . ').');
        }
        $data = json_decode(self::outputText($res), true);
        if (is_array($data) && !empty($data['title']) && !empty($data['body_markdown'])) {
            return $data;
        }
        throw new RuntimeException('Resposta da redação sem JSON válido.');
    }

    /** Chama a API Responses da OpenAI, tentando os modelos configurados em ordem. */
    private static function call(array $payload): array
    {
        $models = (array) cfg('ai.models', ['gpt-5']);
        $lastError = 'nenhum modelo configurado';
        foreach ($models as $model) {
            $body = $payload + ['model' => $model, 'max_output_tokens' => 32000, 'store' => false];
            if (str_starts_with($model, 'gpt-5') || str_starts_with($model, 'o')) {
                $body['reasoning'] = ['effort' => cfg('ai.reasoning_effort', 'medium')];
            }
            [$code, $res] = self::post(rtrim((string) cfg('ai.openai_base_url', 'https://api.openai.com/v1'), '/') . '/responses', $body);
            if ($code === 200 && is_array($res)) {
                return $res;
            }
            $err = $res['error'] ?? [];
            $lastError = "HTTP $code" . (isset($err['message']) ? ': ' . $err['message'] : '');
            // Modelo inexistente ou sem acesso: tenta o próximo. Outros erros interrompem.
            $modelProblem = in_array($code, [400, 403, 404], true)
                && preg_match('/model|does not exist|not found|access/i', (string) ($err['message'] ?? '') . ' ' . ($err['code'] ?? ''));
            if (!$modelProblem) {
                break;
            }
            log_line('blog-agent', "Modelo $model indisponível ($lastError); tentando o próximo.");
        }
        throw new RuntimeException('Erro da API OpenAI — ' . $lastError);
    }

    private static function post(string $url, array $body): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 900,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . cfg('ai.openai_api_key'),
            ],
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            throw new RuntimeException('Falha de conexão com a API OpenAI: ' . $err);
        }
        return [$code, json_decode((string) $raw, true)];
    }

    /** Junta os textos de saída (itens "message" → "output_text"). */
    private static function outputText(array $res): string
    {
        $text = '';
        foreach ($res['output'] ?? [] as $item) {
            if (($item['type'] ?? '') !== 'message') {
                continue;
            }
            foreach ($item['content'] ?? [] as $c) {
                if (($c['type'] ?? '') === 'output_text') {
                    $text .= $c['text'];
                } elseif (($c['type'] ?? '') === 'refusal') {
                    throw new RuntimeException('O modelo recusou a solicitação.');
                }
            }
        }
        return $text;
    }
}
