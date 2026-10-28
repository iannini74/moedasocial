<?php
declare(strict_types=1);

/**
 * Envio de e-mail HTML via SMTP autenticado (Hostinger) com fallback para mail().
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $html, array $opt = []): bool
    {
        $c = cfg('mail');
        $fromEmail = $c['from_email'];
        $fromName = $opt['from_name'] ?? $c['from_name'];
        $replyTo = $opt['reply_to'] ?? $c['reply_to'];
        $text = $opt['text'] ?? self::htmlToText($html);
        $boundary = 'b' . bin2hex(random_bytes(8));
        $msgId = sprintf('<%s@%s>', bin2hex(random_bytes(10)), parse_url(cfg('site.url'), PHP_URL_HOST));

        $headers = [
            'Date: ' . date('r'),
            'From: ' . self::addr($fromEmail, $fromName),
            'To: ' . self::addr($to, $opt['to_name'] ?? ''),
            'Reply-To: ' . $replyTo,
            'Subject: ' . self::encode($subject),
            'Message-ID: ' . $msgId,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];
        if (!empty($opt['unsubscribe'])) {
            $headers[] = 'List-Unsubscribe: <' . $opt['unsubscribe'] . '>';
            $headers[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
        }
        $body = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($text))
            . "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html))
            . "--$boundary--\r\n";

        try {
            if ($c['transport'] === 'log') {
                // Desenvolvimento: grava a mensagem em storage/mail.log em vez de enviar
                log_line('mail', "PARA: $to | ASSUNTO: $subject\n" . $text . "\n" . str_repeat('-', 60));
                return true;
            }
            if ($c['transport'] === 'smtp' && $c['smtp_user'] !== '') {
                self::smtp($fromEmail, $to, implode("\r\n", $headers) . "\r\n\r\n" . $body);
            } else {
                $h = array_filter($headers, fn($l) => !str_starts_with($l, 'To:') && !str_starts_with($l, 'Subject:'));
                if (!mail($to, self::encode($subject), $body, implode("\r\n", $h), '-f' . $fromEmail)) {
                    throw new RuntimeException('mail() retornou false');
                }
            }
            return true;
        } catch (Throwable $e) {
            log_line('mail', "Falha ao enviar para $to: " . $e->getMessage());
            return false;
        }
    }

    private static function smtp(string $from, string $to, string $data): void
    {
        $c = cfg('mail');
        $host = ($c['smtp_secure'] === 'ssl' ? 'ssl://' : 'tcp://') . $c['smtp_host'] . ':' . $c['smtp_port'];
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $fp = stream_socket_client($host, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            throw new RuntimeException("Conexão SMTP falhou: $errstr ($errno)");
        }
        stream_set_timeout($fp, 30);
        $read = function () use ($fp): string {
            $resp = '';
            while (($line = fgets($fp, 515)) !== false) {
                $resp .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            return $resp;
        };
        $cmd = function (string $command, array $expect) use ($fp, $read): string {
            if ($command !== '') {
                fwrite($fp, $command . "\r\n");
            }
            $resp = $read();
            if (!in_array((int) substr($resp, 0, 3), $expect, true)) {
                throw new RuntimeException('SMTP: ' . trim($resp) . ' (comando: ' . explode(' ', $command)[0] . ')');
            }
            return $resp;
        };
        $ehlo = parse_url(cfg('site.url'), PHP_URL_HOST) ?: 'localhost';
        $cmd('', [220]);
        $cmd("EHLO $ehlo", [250]);
        if ($c['smtp_secure'] === 'tls') {
            $cmd('STARTTLS', [220]);
            stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
            $cmd("EHLO $ehlo", [250]);
        }
        $cmd('AUTH LOGIN', [334]);
        $cmd(base64_encode($c['smtp_user']), [334]);
        $cmd(base64_encode($c['smtp_pass']), [235]);
        $cmd("MAIL FROM:<$from>", [250]);
        $cmd("RCPT TO:<$to>", [250, 251]);
        $cmd('DATA', [354]);
        // Dot-stuffing (RFC 5321)
        $data = preg_replace('/^\./m', '..', $data);
        $cmd($data . "\r\n.", [250]);
        $cmd('QUIT', [221]);
        fclose($fp);
    }

    private static function addr(string $email, string $name): string
    {
        return $name === '' ? $email : self::encode($name) . " <$email>";
    }

    private static function encode(string $s): string
    {
        return preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
    }

    private static function htmlToText(string $html): string
    {
        $t = preg_replace('/<(br|\/p|\/h\d|\/li|\/tr)[^>]*>/i', "\n", $html);
        $t = preg_replace('/<a [^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/i', '$2 ($1)', $t);
        $t = html_entity_decode(strip_tags($t), ENT_QUOTES, 'UTF-8');
        return trim(preg_replace("/\n{3,}/", "\n\n", preg_replace('/[ \t]+/', ' ', $t)));
    }

    /** Modelo visual dos e-mails do portal. */
    public static function template(string $title, string $bodyHtml, string $footerExtra = ''): string
    {
        $site = e(cfg('site.name'));
        $url = e(cfg('site.url'));
        $year = date('Y');
        $brandPrimary = Brand::PRIMARY;
        $ad ='<table role="presentation" width="100%" style="margin-top:28px;border-radius:12px;background:' . Brand::AD_BG . ';"><tr><td style="padding:18px 20px;font-family:Arial,sans-serif;color:#fff;">'
            . '<div style="font-size:10px;letter-spacing:1px;text-transform:uppercase;opacity:.75;margin-bottom:6px;">Anúncio</div>'
            . '<div style="font-size:16px;font-weight:bold;margin-bottom:4px;">' . e(Brand::AD_HEADLINE) . '</div>'
            . '<div style="font-size:13px;opacity:.9;margin-bottom:12px;">' . e(Brand::AD_TEXT) . '</div>'
            . '<a href="' . e(sponsor_url('email')) . '" style="display:inline-block;background:' . Brand::ACCENT . ';color:' . Brand::INK . ';padding:9px 16px;border-radius:8px;text-decoration:none;font-weight:bold;font-size:13px;">Conheça a ' . e(cfg('sponsor.name')) . '</a>'
            . '</td></tr></table>';
        return <<<HTML
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>{$title}</title></head>
<body style="margin:0;background:#f4f6f5;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f5;padding:24px 12px;"><tr><td align="center">
<table role="presentation" width="100%" style="max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;">
<tr><td style="background:{$brandPrimary};padding:20px 28px;font-family:Arial,sans-serif;">
<a href="{$url}" style="color:#fff;text-decoration:none;font-size:20px;font-weight:bold;">◎ {$site}</a>
</td></tr>
<tr><td style="padding:28px;font-family:Arial,sans-serif;font-size:15px;line-height:1.6;color:#1d2b27;">
<h1 style="font-size:22px;line-height:1.3;margin:0 0 16px;color:#10231d;">{$title}</h1>
{$bodyHtml}
{$ad}
</td></tr>
<tr><td style="padding:18px 28px;background:#f0f3f2;font-family:Arial,sans-serif;font-size:12px;color:#5b6b66;line-height:1.5;">
{$footerExtra}
© {$year} {$site} — conteúdo protegido por direitos autorais. Desenvolvido por <a href="https://tribex.com.br" style="color:#5b6b66;">TribeX</a>.<br>
Tratamos seus dados conforme a LGPD. <a href="{$url}/privacidade" style="color:#5b6b66;">Política de Privacidade</a>.
</td></tr>
</table></td></tr></table></body></html>
HTML;
    }
}
