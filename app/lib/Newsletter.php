<?php
declare(strict_types=1);

/** Newsletter com dupla confirmação (opt-in) e descadastro em um clique. */
final class Newsletter
{
    public const PROFILES = [
        'gestor-publico'  => 'Gestor(a) público(a) / prefeitura',
        'legislativo'     => 'Vereador(a) / Câmara Municipal',
        'controle'        => 'Controle externo / Tribunal de Contas / MP',
        'comunidade'      => 'Liderança comunitária / ONG / banco comunitário',
        'comercio'        => 'Comerciante / empreendedor(a)',
        'academia'        => 'Pesquisador(a) / estudante',
        'imprensa'        => 'Imprensa',
        'cidadao'         => 'Cidadão(ã) interessado(a)',
    ];

    public static function subscribe(string $email, string $name, string $profile, string $municipality, string $uf, string $source): array
    {
        $email = mb_strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'msg' => 'Informe um e-mail válido.'];
        }
        $existing = Db::one('SELECT * FROM subscribers WHERE email = ?', [$email]);
        if ($existing && $existing['status'] === 'confirmed') {
            return ['ok' => true, 'msg' => 'Este e-mail já está inscrito. Obrigado por acompanhar o portal.'];
        }
        $token = bin2hex(random_bytes(24));
        $consent = 'Aceite da Política de Privacidade em ' . date('c') . ' via ' . $source;
        if ($existing) {
            Db::run(
                'UPDATE subscribers SET name = ?, profile = ?, municipality = ?, uf = ?, status = ?, token = ?, source = ?, consent = ?, created_at = ? WHERE id = ?',
                [$name, $profile, $municipality, $uf, 'pending', $token, $source, $consent, time(), $existing['id']]
            );
        } else {
            Db::insert('subscribers', [
                'email' => $email, 'name' => $name, 'profile' => $profile, 'municipality' => $municipality, 'uf' => $uf,
                'status' => 'pending', 'token' => $token, 'source' => $source, 'consent' => $consent, 'created_at' => time(),
            ]);
        }
        $first = $name !== '' ? e(explode(' ', $name)[0]) : 'Olá';
        $link = url('/newsletter/confirmar?t=' . $token, true);
        $html = Mailer::template('Confirme sua inscrição', <<<HTML
<p>{$first}, obrigado pela inscrição.</p>
<p>Confirme seu e-mail para receber, a cada novo artigo, análises práticas sobre <strong>moedas sociais, economia circular e desenvolvimento local</strong> — com casos reais, regulação e caminhos de implantação.</p>
<p style="text-align:center;margin:28px 0;"><a href="{$link}" style="background:#036B4F;color:#fff;padding:14px 26px;border-radius:12px;text-decoration:none;font-weight:bold;display:inline-block;">Confirmar minha inscrição</a></p>
<p style="font-size:13px;color:#5b6b66;">Se o botão não funcionar, copie e cole este endereço no navegador:<br>{$link}</p>
<p style="font-size:13px;color:#5b6b66;">Não pediu esta inscrição? Pode ignorar esta mensagem — sem confirmação, não enviaremos nada.</p>
HTML);
        $sent = Mailer::send($email, 'Confirme sua inscrição no Portal Moeda Social', $html, ['to_name' => $name]);
        return [
            'ok' => true,
            'msg' => $sent
                ? 'Quase lá! Enviamos um e-mail de confirmação para ' . $email . '. Confira também a caixa de spam ou promoções.'
                : 'Inscrição registrada! Se o e-mail de confirmação não chegar em alguns minutos, fale com a gente pelo formulário de contato.',
        ];
    }

    public static function confirm(string $token): ?array
    {
        $row = self::byToken($token);
        if ($row && $row['status'] !== 'confirmed') {
            Db::run('UPDATE subscribers SET status = ?, confirmed_at = ? WHERE id = ?', ['confirmed', time(), $row['id']]);
            $row['status'] = 'confirmed';
            self::welcome($row);
        }
        return $row;
    }

    public static function unsubscribe(string $token): ?array
    {
        $row = self::byToken($token);
        if ($row) {
            Db::run('UPDATE subscribers SET status = ?, unsubscribed_at = ? WHERE id = ?', ['unsubscribed', time(), $row['id']]);
        }
        return $row;
    }

    private static function byToken(string $token): ?array
    {
        return preg_match('/^[a-f0-9]{48}$/', $token) ? Db::one('SELECT * FROM subscribers WHERE token = ?', [$token]) : null;
    }

    private static function welcome(array $s): void
    {
        $first = $s['name'] ? e(explode(' ', $s['name'])[0]) : 'Olá';
        $u = fn(string $p) => url($p . '?utm_source=newsletter&utm_medium=email&utm_campaign=boas-vindas', true);
        $html = Mailer::template('Inscrição confirmada — boas-vindas!', <<<HTML
<p>{$first}, inscrição confirmada! A partir de agora você recebe um aviso sempre que publicarmos um novo artigo.</p>
<p>Enquanto isso, separamos três pontos de partida:</p>
<ul>
<li><a href="{$u('/estudos/o-que-e-moeda-social')}">O que é moeda social (e o que ela não é)</a></li>
<li><a href="{$u('/estudos/cases-brasil')}">Cases brasileiros: o que deu certo e o que não deu</a></li>
<li><a href="{$u('/diagnostico')}">Diagnóstico gratuito: como a moeda social funcionaria no seu município</a></li>
</ul>
<p>Precisa de apoio para estruturar um programa? Responda este e-mail ou use nosso <a href="{$u('/contato')}">formulário de contato</a>.</p>
HTML);
        Mailer::send($s['email'], 'Bem-vindo(a) ao Portal Moeda Social', $html, [
            'unsubscribe' => url('/newsletter/cancelar?t=' . $s['token'], true),
        ]);
    }

    /** Envia aviso de novo artigo a todos os confirmados (em lotes, respeitando limites do SMTP). */
    public static function broadcastPost(array $post, int $batch = 80): int
    {
        $subs = Db::all("SELECT * FROM subscribers WHERE status = 'confirmed' AND (last_sent_at IS NULL OR last_sent_at < ?) LIMIT $batch", [(int) $post['published_at']]);
        $postUrl = url('/blog/' . $post['slug'] . '?utm_source=newsletter&utm_medium=email&utm_campaign=artigo', true);
        $title = e($post['title']);
        $desc = e($post['description']);
        $n = 0;
        foreach ($subs as $s) {
            $first = $s['name'] ? e(explode(' ', $s['name'])[0]) : 'Olá';
            $unsub = url('/newsletter/cancelar?t=' . $s['token'], true);
            $html = Mailer::template('Novo artigo no Portal Moeda Social', <<<HTML
<p>{$first}, acabamos de publicar:</p>
<h2 style="font-size:20px;margin:12px 0 8px;"><a href="{$postUrl}" style="color:#0F2742;text-decoration:none;">{$title}</a></h2>
<p>{$desc}</p>
<p style="margin:24px 0;"><a href="{$postUrl}" style="background:#036B4F;color:#fff;padding:12px 22px;border-radius:12px;text-decoration:none;font-weight:bold;display:inline-block;">Ler o artigo completo</a></p>
HTML, '<a href="' . e($unsub) . '" style="color:#5b6b66;">Cancelar inscrição</a> · ');
            if (Mailer::send($s['email'], $post['title'], $html, ['to_name' => (string) $s['name'], 'unsubscribe' => $unsub])) {
                Db::run('UPDATE subscribers SET last_sent_at = ? WHERE id = ?', [time(), $s['id']]);
                $n++;
            }
            usleep(250000);
        }
        return $n;
    }
}
