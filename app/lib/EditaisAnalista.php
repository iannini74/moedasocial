<?php
declare(strict_types=1);

/**
 * Agente de análise de editais (OpenAI, saída estruturada). Lê cada item
 * encontrado pelos agentes de coleta e de pesquisa e decide:
 * - se é do tema (moeda social, distribuição de benefícios, benefícios sociais);
 * - categoria, tipo de ato, órgão, município, prazo e valor;
 * - título e resumo em linguagem direta;
 * - se repete um item já publicado.
 * Com confiança alta, publica; com confiança média, deixa para revisão no painel.
 * Sem chave da OpenAI, aplica regras por palavra-chave e envia tudo para revisão.
 */
final class EditaisAnalista
{
    private const BATCH = 15;

    private const KEYWORDS = [
        'moeda-social' => ['moeda social', 'moedas sociais', 'moeda local', 'banco comunitário', 'mumbuca', 'arariboia'],
        'distribuicao-beneficios' => ['cartão benefício', 'cartão-benefício', 'voucher', 'cesta básica', 'carteira digital'],
        'beneficios-sociais' => ['renda básica', 'transferência de renda', 'benefício eventual', 'auxílio emergencial', 'programa social'],
    ];

    public static function run(int $max): array
    {
        // Itens do PNCP e da imprensa sem nenhum termo do tema são descartados sem gastar IA
        foreach (Db::all("SELECT id, title, trecho FROM editais WHERE status = 'novo' AND source IN ('PNCP', 'Imprensa')") as $row) {
            if (!Editais::onTheme($row['title'] . ' ' . $row['trecho'])) {
                Db::run("UPDATE editais SET status = 'descartado', analyzed_at = ? WHERE id = ?", [time(), $row['id']]);
            }
        }
        $rows = Db::all("SELECT * FROM editais WHERE status = 'novo' ORDER BY id LIMIT $max");
        $r = ['analisados' => 0, 'publicados' => 0, 'revisar' => 0, 'descartados' => 0, 'duplicados' => 0];
        if (!$rows) {
            return $r;
        }
        if (!Ai::enabled()) {
            foreach ($rows as $row) {
                $cat = self::keywordCategory($row['title'] . ' ' . $row['trecho']);
                self::save((int) $row['id'], $cat ? 'revisar' : 'descartado', ['categoria' => $cat ?? '']);
                $r['analisados']++;
                $r[$cat ? 'revisar' : 'descartados']++;
            }
            return $r;
        }
        foreach (array_chunk($rows, self::BATCH) as $batch) {
            try {
                $answers = self::analyze($batch);
            } catch (Throwable $e) {
                log_line('editais', 'Análise: ' . $e->getMessage());
                $r['erro'] = $e->getMessage();
                break;   // os itens continuam como "novo" e são analisados na próxima execução
            }
            $ids = array_column($batch, 'id');
            foreach ($answers as $a) {
                $id = (int) $a['id'];
                if (!in_array($id, $ids, true)) {
                    continue;
                }
                $status = match (true) {
                    !$a['relevante'] || !isset(Editais::CATEGORIES[$a['categoria']]) => 'descartado',
                    $a['duplicado'] => 'duplicado',
                    $a['confianca'] >= cfg('editais.min_confidence', 70) => 'publicado',
                    $a['confianca'] >= 40 => 'revisar',
                    default => 'descartado',
                };
                self::save($id, $status, $a);
                $r['analisados']++;
                $r[['publicado' => 'publicados', 'revisar' => 'revisar', 'descartado' => 'descartados', 'duplicado' => 'duplicados'][$status]]++;
            }
        }
        return $r;
    }

    private static function save(int $id, string $status, array $a): void
    {
        $set = ['status' => $status, 'analyzed_at' => time(), 'categoria' => $a['categoria'] ?? ''];
        if (isset($a['titulo'])) {
            $uf = strtoupper(trim($a['uf']));
            $set += [
                'title' => mb_substr(trim($a['titulo']), 0, 400),
                'summary' => mb_substr(trim($a['resumo']), 0, 1000),
                'tipo' => isset(Editais::TYPES[$a['tipo']]) ? $a['tipo'] : 'outro',
                'orgao' => mb_substr(trim($a['orgao']), 0, 255),
                'municipio' => mb_substr(trim($a['municipio']), 0, 150),
                'uf' => in_array($uf, ufs(), true) ? $uf : '',
                'confianca' => max(0, min(100, (int) $a['confianca'])),
                'tkey' => hash('sha256', preg_replace('/[^a-z0-9]/', '', slugify($a['titulo']))),
            ];
            if ($ts = Editais::toTs($a['data_publicacao'])) {
                $set['published_at'] = min($ts, time());
            }
            if ($ts = Editais::toTs($a['prazo'])) {
                $set['deadline_at'] = $ts;
            }
            if ((float) $a['valor'] > 0) {
                $set['valor'] = (float) $a['valor'];
            }
        }
        // Mesmo título já publicado por outra fonte
        if ($status === 'publicado' && !empty($set['tkey'])
            && Db::value("SELECT id FROM editais WHERE tkey = ? AND status = 'publicado' AND id <> ?", [$set['tkey'], $id])) {
            $set['status'] = 'duplicado';
        }
        $cols = implode(', ', array_map(fn($k) => "$k = ?", array_keys($set)));
        Db::run("UPDATE editais SET $cols WHERE id = ?", [...array_values($set), $id]);
    }

    private static function keywordCategory(string $text): ?string
    {
        $hay = mb_strtolower($text);
        foreach (self::KEYWORDS as $cat => $terms) {
            foreach ($terms as $t) {
                if (str_contains($hay, $t)) {
                    return $cat;
                }
            }
        }
        return null;
    }

    private static function analyze(array $batch): array
    {
        $recent = Db::all("SELECT title, municipio, uf FROM editais WHERE status = 'publicado' AND published_at > ? ORDER BY published_at DESC LIMIT 80", [time() - 120 * 86400]);
        $recentList = $recent ? implode("\n", array_map(fn($x) => "- {$x['title']} ({$x['municipio']}/{$x['uf']})", $recent)) : '(nenhum)';
        $items = '';
        foreach ($batch as $row) {
            $items .= "\n### ID {$row['id']}\nFonte: {$row['source']}\nTítulo original: {$row['title']}\nÓrgão: {$row['orgao']}\nMunicípio/UF: {$row['municipio']}/{$row['uf']}\n"
                . 'Data: ' . date('Y-m-d', (int) $row['published_at']) . "\nURL: {$row['url']}\nTrecho:\n" . mb_substr((string) $row['trecho'], 0, 1800) . "\n";
        }
        $today = date('Y-m-d');
        $categorias = implode(', ', array_keys(Editais::CATEGORIES));
        $tipos = implode(', ', array_keys(Editais::TYPES));
        $instructions = <<<TXT
Você é o analista de editais do Portal Moeda Social. Sua tarefa é decidir quais publicações entram na página de editais e escrever um título e um resumo claros para gestores públicos.

Categorias aceitas:
- moeda-social: lei, decreto ou projeto de lei que cria ou regulamenta moeda social ou moeda local; lançamento oficial de moeda social ou de banco comunitário por prefeitura; edital, licitação, chamamento ou contratação de plataforma, operador ou banco comunitário; credenciamento de comércios para aceitar a moeda; calendário de pagamento ou parcela extra de moeda social.
- distribuicao-beneficios: licitação, edital, pregão, credenciamento ou contrato para meios de pagamento de benefícios sociais à população (cartão benefício, voucher, carteira digital, cesta básica por cartão).
- beneficios-sociais: lei, decreto, edital, abertura de inscrições ou calendário de pagamento de programa de transferência de renda, renda básica, auxílio ou benefício eventual para a população.

Notícias da imprensa ou de sites oficiais entram quando relatam uma medida oficial concreta de um órgão público (criação, lançamento, projeto de lei, edital, credenciamento, inscrições, data de pagamento). Nesse caso use tipo "anuncio", a menos que o item seja o próprio ato (lei, decreto, edital etc.).

Marque relevante = false (categoria "nenhuma") para: vale-alimentação ou vale-refeição de servidores públicos, compra de alimentos ou merenda, compras de materiais em geral, feiras, eventos, oficinas e cursos, artigos de opinião, análises, histórias e balanços de resultados sem nova medida, temas de outros países, e qualquer item fora das três categorias.

Regras de escrita:
- titulo: até 110 caracteres, diz quem fez o quê e onde. Exemplo: "Maricá (RJ) abre credenciamento de comércios para a moeda Mumbuca".
- resumo: uma ou duas frases diretas, com o objeto, o público e o prazo quando houver. Sem metáforas, sem adjetivos promocionais, sem emojis.
- Não invente dados: deixe orgao, municipio, uf, prazo e data_publicacao vazios se não estiverem no item; valor = 0 se não houver.
- tipo: um destes valores: {$tipos}.
- data_publicacao e prazo no formato AAAA-MM-DD. Hoje é {$today}.
- duplicado = true se o item descreve o mesmo ato de um item já publicado (lista abaixo) ou de um item anterior deste lote.
- confianca: 0 a 100, o quanto você tem certeza de que o item é relevante e de que os dados estão corretos.

Itens já publicados (últimos 120 dias):
{$recentList}
TXT;
        $schema = [
            'type' => 'object', 'additionalProperties' => false, 'required' => ['itens'],
            'properties' => ['itens' => ['type' => 'array', 'items' => [
                'type' => 'object', 'additionalProperties' => false,
                'required' => ['id', 'relevante', 'categoria', 'tipo', 'titulo', 'resumo', 'orgao', 'municipio', 'uf', 'data_publicacao', 'prazo', 'valor', 'duplicado', 'confianca'],
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'relevante' => ['type' => 'boolean'],
                    'categoria' => ['type' => 'string', 'enum' => [...array_keys(Editais::CATEGORIES), 'nenhuma']],
                    'tipo' => ['type' => 'string', 'enum' => array_keys(Editais::TYPES)],
                    'titulo' => ['type' => 'string'],
                    'resumo' => ['type' => 'string'],
                    'orgao' => ['type' => 'string'],
                    'municipio' => ['type' => 'string'],
                    'uf' => ['type' => 'string'],
                    'data_publicacao' => ['type' => 'string'],
                    'prazo' => ['type' => 'string'],
                    'valor' => ['type' => 'number'],
                    'duplicado' => ['type' => 'boolean'],
                    'confianca' => ['type' => 'integer'],
                ],
            ]]],
        ];
        $res = Ai::call([
            'instructions' => $instructions,
            'input' => "Analise os itens abaixo (categorias: $categorias) e responda um objeto por ID.\n$items",
            'text' => ['format' => ['type' => 'json_schema', 'name' => 'analise_editais', 'strict' => true, 'schema' => $schema]],
        ], 16000, 'low', 'editais');
        $data = json_decode(Ai::text($res), true);
        if (!is_array($data['itens'] ?? null)) {
            throw new RuntimeException('Resposta da análise sem JSON válido.');
        }
        return $data['itens'];
    }
}
