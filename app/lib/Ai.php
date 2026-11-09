<?php
declare(strict_types=1);

/**
 * Cliente da API Responses da OpenAI, usado pelos agentes do portal
 * (blog e editais). Os modelos de ai.models são tentados em ordem caso
 * algum não esteja disponível na conta.
 */
final class Ai
{
    public static string $lastModel = '';

    public static function enabled(): bool
    {
        return (bool) cfg('ai.openai_api_key');
    }

    /** Chama a API Responses, tentando os modelos configurados em ordem. */
    public static function call(array $payload, int $maxTokens = 32000, ?string $effort = null, string $log = 'ai'): array
    {
        $models = (array) cfg('ai.models', ['gpt-5']);
        $lastError = 'nenhum modelo configurado';
        foreach ($models as $model) {
            $body = $payload + ['model' => $model, 'max_output_tokens' => $maxTokens, 'store' => false];
            if (str_starts_with($model, 'gpt-5') || str_starts_with($model, 'o')) {
                $body['reasoning'] = ['effort' => $effort ?? ($maxTokens < 1000 ? 'low' : cfg('ai.reasoning_effort', 'medium'))];
            }
            [$code, $res] = self::post(rtrim((string) cfg('ai.openai_base_url', 'https://api.openai.com/v1'), '/') . '/responses', $body);
            if ($code === 200 && is_array($res)) {
                self::$lastModel = $model;
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
            log_line($log, "Modelo $model indisponível ($lastError); tentando o próximo.");
        }
        throw new RuntimeException('Erro da API OpenAI — ' . $lastError);
    }

    /** Junta os textos de saída (itens "message" → "output_text"). */
    public static function text(array $res): string
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

    /** Extrai JSON da resposta, mesmo quando vem cercado de texto ou de bloco de código. */
    public static function json(array $res): ?array
    {
        $t = trim(self::text($res));
        $data = json_decode($t, true);
        if (is_array($data)) {
            return $data;
        }
        foreach (['[' => ']', '{' => '}'] as $open => $close) {
            $a = strpos($t, $open);
            $b = strrpos($t, $close);
            if ($a !== false && $b > $a) {
                $data = json_decode(substr($t, $a, $b - $a + 1), true);
                if (is_array($data)) {
                    return $data;
                }
            }
        }
        return null;
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
}
