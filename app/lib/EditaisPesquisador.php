<?php
declare(strict_types=1);

/**
 * Agente de pesquisa de editais na web (OpenAI, ferramenta web_search).
 * Complementa a coleta automática buscando publicações em sites de prefeituras,
 * diários oficiais, portais de transparência e órgãos estaduais e federais.
 * Os itens encontrados passam pelo agente de análise antes de serem publicados.
 *
 * Pelo cron roda direto (run). Pela web, onde o tempo de execução é curto, a
 * pesquisa roda em segundo plano na OpenAI (start) e o resultado é buscado
 * numa visita seguinte (poll).
 */
final class EditaisPesquisador
{
    public static function run(int $days): array
    {
        try {
            return self::ingest(Ai::call(self::payload($days), 12000, 'low', 'editais'), $days);
        } catch (Throwable $e) {
            return ['ok' => false, 'encontrados' => 0, 'novos' => 0, 'erro' => $e->getMessage()];
        }
    }

    /** Inicia a pesquisa em segundo plano e devolve o id da resposta. */
    public static function start(int $days): string
    {
        $res = Ai::call(self::payload($days) + ['background' => true, 'store' => true], 12000, 'low', 'editais');
        return (string) ($res['id'] ?? '');
    }

    /** @return ?array resultado, ou null enquanto a pesquisa ainda está rodando */
    public static function poll(string $id, int $days): ?array
    {
        try {
            $res = Ai::retrieve($id);
            return match ($res['status'] ?? '') {
                'queued', 'in_progress' => null,
                'completed' => self::ingest($res, $days),
                default => ['ok' => false, 'encontrados' => 0, 'novos' => 0, 'erro' => 'Pesquisa em segundo plano terminou com status ' . ($res['status'] ?? 'desconhecido') . '.'],
            };
        } catch (Throwable $e) {
            return ['ok' => false, 'encontrados' => 0, 'novos' => 0, 'erro' => $e->getMessage()];
        }
    }

    private static function payload(int $days): array
    {
        $known = array_column(Db::all("SELECT title FROM editais WHERE status = 'publicado' ORDER BY published_at DESC LIMIT 40"), 'title');
        $knownList = $known ? '- ' . implode("\n- ", $known) : '(nenhum)';
        $today = date_long_br(time());
        $from = date_long_br(time() - $days * 86400);
        $prompt = <<<TXT
Hoje é {$today}. Pesquise na web publicações oficiais de órgãos públicos brasileiros (prefeituras, câmaras municipais, governos estaduais, ministérios) feitas entre {$from} e hoje, sobre estes três temas:

1. Moeda social: lei, decreto ou projeto de lei que cria ou regulamenta moeda social ou moeda local digital; lançamento oficial de moeda social ou banco comunitário; edital, licitação ou chamamento para contratar plataforma, operador ou banco comunitário; credenciamento de comércios para aceitar a moeda.
2. Distribuição de benefícios: licitação, edital, pregão ou credenciamento para meios de pagamento de benefícios sociais à população (cartão benefício, voucher, carteira digital, cesta básica por cartão, auxílio-alimentação de programa social).
3. Benefícios sociais: lei, decreto, edital ou abertura de inscrições de programas municipais ou estaduais de transferência de renda, renda básica, auxílios e benefícios eventuais.

Não inclua: vale-alimentação ou vale-refeição de servidores públicos, compra de alimentos para merenda escolar, feiras e eventos, artigos de opinião e notícias sem uma medida oficial concreta.

Prefira fontes primárias: diário oficial, site da prefeitura ou do governo, PNCP, portal de transparência. Use notícia da imprensa só quando a fonte oficial não estiver disponível.

Itens já publicados no portal (não repita):
{$knownList}

Responda SOMENTE com um array JSON (sem texto antes ou depois), com até 25 itens, cada um no formato:
{"titulo": "...", "url": "https://...", "orgao": "...", "municipio": "...", "uf": "SP", "data_publicacao": "AAAA-MM-DD", "prazo": "AAAA-MM-DD ou vazio", "resumo": "uma ou duas frases"}
Use apenas URLs que você abriu na pesquisa. Se não encontrar nada, responda [].
TXT;
        return [
            'input' => $prompt,
            'tools' => [['type' => 'web_search', 'user_location' => ['type' => 'approximate', 'country' => 'BR', 'timezone' => 'America/Sao_Paulo']]],
        ];
    }

    private static function ingest(array $res, int $days): array
    {
        $items = Ai::json($res);
        if ($items === null) {
            return ['ok' => false, 'encontrados' => 0, 'novos' => 0, 'erro' => 'Resposta sem JSON válido.'];
        }
        $items = isset($items['itens']) ? (array) $items['itens'] : $items;
        $found = $new = 0;
        foreach (array_slice($items, 0, 25) as $it) {
            if (!is_array($it) || empty($it['url'])) {
                continue;
            }
            $found++;
            $pub = Editais::toTs($it['data_publicacao'] ?? '');
            if ($pub && $pub < time() - ($days + 5) * 86400) {
                continue;
            }
            $new += (int) Editais::add([
                'title' => (string) ($it['titulo'] ?? ''),
                'url' => (string) $it['url'],
                'orgao' => (string) ($it['orgao'] ?? ''),
                'municipio' => (string) ($it['municipio'] ?? ''),
                'uf' => (string) ($it['uf'] ?? ''),
                'trecho' => (string) ($it['resumo'] ?? ''),
                'source' => 'Pesquisa web',
                'published_at' => $pub,
                'deadline_at' => Editais::toTs($it['prazo'] ?? ''),
            ]);
        }
        return ['ok' => true, 'encontrados' => $found, 'novos' => $new, 'modelo' => (string) ($res['model'] ?? Ai::$lastModel)];
    }
}
