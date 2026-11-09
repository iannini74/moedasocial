<?php
declare(strict_types=1);

/**
 * Agente de coleta de editais (sem IA). Consulta fontes públicas e registra
 * os itens novos para o agente de análise:
 * - PNCP (Portal Nacional de Contratações Públicas): editais, avisos e credenciamentos;
 * - Querido Diário: diários oficiais municipais (leis, decretos, chamamentos);
 * - Google Notícias: anúncios de editais e programas na imprensa e em sites oficiais.
 */
final class EditaisColetor
{
    private const SOURCES = ['pncp' => 'editais.pncp_terms', 'diarios' => 'editais.diario_terms', 'imprensa' => 'editais.news_queries'];

    /** Todas as consultas, de uma vez (cron). @return array<string, array{ok:bool, encontrados:int, novos:int, erro?:string}> */
    public static function run(int $since): array
    {
        $out = [];
        foreach (self::SOURCES as $key => $terms) {
            $out[$key] = self::query($key, $since, (array) cfg($terms, []));
            log_line('editais', "Coleta $key: " . json_encode($out[$key], JSON_UNESCAPED_UNICODE));
        }
        return $out;
    }

    /** Lista de consultas [fonte, termo], para a coleta em etapas pela web. */
    public static function units(): array
    {
        $units = [];
        foreach (self::SOURCES as $key => $terms) {
            foreach ((array) cfg($terms, []) as $t) {
                $units[] = [$key, $t];
            }
        }
        return $units;
    }

    /** Executa uma fonte para os termos informados. */
    public static function query(string $source, int $since, array $terms): array
    {
        try {
            return match ($source) {
                'pncp' => self::pncp($since, $terms),
                'diarios' => self::diarios($since, $terms),
                'imprensa' => self::imprensa($since, $terms),
            };
        } catch (Throwable $e) {
            return ['ok' => false, 'encontrados' => 0, 'novos' => 0, 'erro' => $e->getMessage()];
        }
    }

    /** Soma os resultados parciais de uma fonte (coleta em etapas). */
    public static function merge(?array $acc, array $r): array
    {
        if (!$acc) {
            return $r + ['consultas' => 1, 'falhas' => $r['ok'] ? 0 : 1];
        }
        $acc['encontrados'] += $r['encontrados'];
        $acc['novos'] += $r['novos'];
        $acc['consultas']++;
        $acc['falhas'] += $r['ok'] && empty($r['erro']) ? 0 : 1;
        $acc['ok'] = $acc['falhas'] < $acc['consultas'];
        if (!empty($r['erro'])) {
            $acc['erro'] = $acc['falhas'] . ' de ' . $acc['consultas'] . ' consultas falharam: ' . preg_replace('/^\d+ de \d+ consultas falharam: /', '', $r['erro']);
        }
        return $acc;
    }

    private static function pncp(int $since, array $terms): array
    {
        $found = $new = 0;
        $errors = [];
        foreach ($terms as $term) {
            $q = http_build_query(['q' => $term, 'tipos_documento' => 'edital', 'ordenacao' => '-data', 'pagina' => 1, 'tam_pagina' => 20]);
            [$data, $err] = self::getJson(cfg('editais.pncp_url') . '?' . $q);
            if ($data === null) {
                $errors[] = $err;
                continue;
            }
            foreach ($data['items'] ?? [] as $it) {
                $pub = Editais::toTs($it['data_publicacao_pncp'] ?? $it['createdAt'] ?? $it['created_at'] ?? '');
                if ($pub && $pub < $since) {
                    continue;
                }
                $found++;
                $path = (string) ($it['item_url'] ?? '');
                if (preg_match('#/compras/(\d+)/(\d+)/(\d+)#', $path, $m)) {
                    $link = "https://pncp.gov.br/app/editais/$m[1]/$m[2]/$m[3]";
                } elseif (!empty($it['orgao_cnpj']) && !empty($it['ano']) && !empty($it['numero_sequencial'])) {
                    $link = "https://pncp.gov.br/app/editais/{$it['orgao_cnpj']}/{$it['ano']}/{$it['numero_sequencial']}";
                } else {
                    continue;
                }
                $desc = trim((string) ($it['description'] ?? $it['objeto'] ?? ''));
                $modalidade = (string) ($it['modalidade_licitacao_nome'] ?? '');
                // A busca do PNCP não aceita frase exata: traz muitas compras fora do tema
                if (!Editais::onTheme(($it['title'] ?? '') . ' ' . $desc)) {
                    continue;
                }
                $new += (int) Editais::add([
                    'title' => trim((string) ($it['title'] ?? '')) ?: mb_substr($desc, 0, 160),
                    'url' => $link,
                    'orgao' => $it['orgao_nome'] ?? $it['unidade_nome'] ?? '',
                    'municipio' => $it['municipio_nome'] ?? '',
                    'uf' => $it['uf'] ?? '',
                    'tipo' => $modalidade,
                    'trecho' => trim("Objeto: $desc\nModalidade: $modalidade\nSituação: " . ($it['situacao_nome'] ?? '') . "\nTermo pesquisado: $term"),
                    'source' => 'PNCP',
                    'published_at' => $pub,
                    'deadline_at' => Editais::toTs($it['data_fim_vigencia'] ?? ''),
                    'valor' => (float) ($it['valor_global'] ?? 0),
                ]);
            }
        }
        return self::result($found, $new, $errors, count($terms));
    }

    private static function diarios(int $since, array $terms): array
    {
        $found = $new = 0;
        $errors = [];
        foreach ($terms as $term) {
            $q = http_build_query([
                'querystring' => $term, 'published_since' => date('Y-m-d', $since), 'size' => 20,
                'excerpt_size' => 700, 'number_of_excerpts' => 2, 'sort_by' => 'descending_date',
            ]);
            [$data, $err] = self::getJson(cfg('editais.diario_url') . '?' . $q);
            if ($data === null) {
                $errors[] = $err;
                continue;
            }
            foreach ($data['gazettes'] ?? [] as $g) {
                $found++;
                $excerpts = array_map(fn($x) => trim(preg_replace('/\s+/', ' ', (string) $x)), (array) ($g['excerpts'] ?? []));
                $city = (string) ($g['territory_name'] ?? '');
                $uf = (string) ($g['state_code'] ?? '');
                $pub = Editais::toTs($g['date'] ?? '');
                $new += (int) Editais::add([
                    // Um diário traz vários atos: a chave inclui o trecho encontrado
                    'guid' => Editais::normalizeUrl((string) ($g['url'] ?? '')) . '#' . md5($excerpts[0] ?? $term),
                    'title' => "Diário Oficial de $city ($uf), " . date_br($pub),
                    'url' => (string) ($g['url'] ?? ''),
                    'orgao' => $city ? "Prefeitura de $city" : '',
                    'municipio' => $city,
                    'uf' => $uf,
                    'trecho' => implode("\n[...]\n", $excerpts) . "\nTermo pesquisado: $term",
                    'source' => 'Diário oficial',
                    'published_at' => $pub,
                ]);
            }
        }
        return self::result($found, $new, $errors, count($terms));
    }

    private static function imprensa(int $since, array $terms): array
    {
        $found = $new = 0;
        $errors = [];
        $days = max(1, (int) ceil((time() - $since) / 86400));
        foreach ($terms as $query) {
            $feed = 'https://news.google.com/rss/search?q=' . rawurlencode("$query when:{$days}d") . '&hl=pt-BR&gl=BR&ceid=BR:pt-419';
            $xml = News::fetch($feed);
            if (!$xml) {
                $errors[] = 'Google Notícias indisponível';
                continue;
            }
            foreach (News::items($xml) as $item) {
                $pub = strtotime($item['date']) ?: time();
                if ($pub < $since || News::isForeign($item['source_url'] ?: $item['link'])) {
                    continue;
                }
                $found++;
                $title = trim(html_entity_decode($item['title'], ENT_QUOTES, 'UTF-8'));
                if ($item['source'] !== '' && str_ends_with($title, ' - ' . $item['source'])) {
                    $title = trim(mb_substr($title, 0, -mb_strlen(' - ' . $item['source'])));
                }
                $desc = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($item['desc']), ENT_QUOTES, 'UTF-8')));
                if (!Editais::onTheme("$title $desc")) {
                    continue;
                }
                $new += (int) Editais::add([
                    'guid' => 'news:' . preg_replace('/[^a-z0-9]/', '', slugify($title)),
                    'title' => $title,
                    'url' => $item['link'],
                    'trecho' => trim("Veículo: {$item['source']}\n$desc"),
                    'source' => 'Imprensa',
                    'published_at' => $pub,
                ]);
            }
        }
        return self::result($found, $new, $errors, count($terms));
    }

    /** A fonte só é considerada indisponível quando todas as consultas falham. */
    private static function result(int $found, int $new, array $errors, int $queries): array
    {
        $r = ['ok' => count($errors) < max(1, $queries), 'encontrados' => $found, 'novos' => $new];
        if ($errors) {
            $r['erro'] = count($errors) . ' de ' . $queries . ' consultas falharam: ' . implode('; ', array_unique($errors));
        }
        return $r;
    }

    /** @return array{0: ?array, 1: string} dados decodificados e mensagem de erro */
    private static function getJson(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 15, CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_USERAGENT => 'MoedaSocialEditaisBot/1.0 (+' . cfg('site.url') . ')',
            CURLOPT_HTTPHEADER => ['Accept: application/json'], CURLOPT_ENCODING => '',
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        $host = (string) parse_url($url, PHP_URL_HOST);
        if ($body === false || $code >= 400 || $code === 0) {
            return [null, "$host: " . ($err ?: "HTTP $code")];
        }
        $data = json_decode((string) $body, true);
        return is_array($data) ? [$data, ''] : [null, "$host: resposta não é JSON"];
    }
}
