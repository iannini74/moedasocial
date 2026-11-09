<?php
declare(strict_types=1);

/**
 * Área restrita de editais: cadastro com aprovação no painel e login sem senha,
 * com código de 6 dígitos enviado por e-mail a cada acesso.
 *
 * A sessão do usuário é um cookie assinado (HMAC) que expira ao fechar o navegador
 * e, no máximo, após editais.session_hours. Um novo login, a suspensão ou a
 * exclusão do cadastro invalidam a sessão anterior (session_nonce).
 * O administrador logado no painel sempre vê a página completa.
 */
final class Membros
{
    public const STATUS = [
        'pendente'  => 'Aguardando aprovação',
        'aprovado'  => 'Aprovado',
        'recusado'  => 'Recusado',
        'suspenso'  => 'Acesso suspenso',
    ];

    private const COOKIE = 'ms_area';
    private const CODE_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    private static ?array $current = null;
    private static bool $resolved = false;

    public static function master(): string
    {
        return mb_strtolower(trim((string) cfg('editais.master_email')));
    }

    /** Usuário com sessão válida, ou null. */
    public static function current(): ?array
    {
        if (self::$resolved) {
            return self::$current;
        }
        self::$resolved = true;
        $parts = explode('.', (string) ($_COOKIE[self::COOKIE] ?? ''));
        if (count($parts) !== 3 || (int) $parts[1] < time()) {
            return null;
        }
        [$id, $exp, $sig] = $parts;
        $m = Db::one("SELECT * FROM members WHERE id = ? AND status = 'aprovado'", [(int) $id]);
        if ($m && $m['session_nonce'] && hash_equals(self::sign((int) $id, (int) $exp, $m['session_nonce']), $sig)) {
            self::$current = $m;
        }
        return self::$current;
    }

    /** Administrador logado no painel (sessão lida sem bloquear). */
    public static function isAdmin(): bool
    {
        if (empty($_COOKIE['ms_admin'])) {
            return false;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name('ms_admin');
            session_start(['read_and_close' => true]);
        }
        return !empty($_SESSION['admin']) && ($_SESSION['exp'] ?? 0) > time();
    }

    public static function canView(): bool
    {
        return self::isAdmin() || self::current() !== null;
    }

    /** Solicitação de acesso. @return array{ok:bool, errors:array, data?:array, msg?:string} */
    public static function request(): array
    {
        if (!empty($_POST['website'])) {   // honeypot
            return ['ok' => true, 'errors' => [], 'msg' => 'Solicitação recebida.'];
        }
        $d = [
            'name' => input('name', 150), 'email' => mb_strtolower(input('email', 190)), 'phone' => input('phone', 40),
            'org_type' => input('org_type', 60), 'organization' => input('organization', 190), 'role' => input('role', 120),
            'municipality' => input('municipality', 150), 'uf' => strtoupper(input('uf', 2)), 'purpose' => input('purpose', 2000),
        ];
        $errors = [];
        if (!form_token_valid($_POST['_t'] ?? null, 3)) $errors['_form'] = 'O formulário expirou. Atualize a página e envie novamente.';
        if (rate_limited('acesso:' . ip_hash(), 5, 3600)) $errors['_form'] = 'Muitas solicitações deste acesso na última hora. Tente mais tarde.';
        if (mb_strlen($d['name']) < 3) $errors['name'] = 'Informe seu nome completo.';
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Informe um e-mail válido. É nele que você vai receber o código de acesso.';
        if ($d['phone'] !== '' && strlen(preg_replace('/\D/', '', $d['phone'])) < 10) $errors['phone'] = 'Inclua o DDD no telefone.';
        if (!isset(Contact::ORG_TYPES[$d['org_type']])) $errors['org_type'] = 'Selecione o tipo de organização.';
        if ($d['organization'] === '') $errors['organization'] = 'Informe a organização.';
        if ($d['municipality'] === '') $errors['municipality'] = 'Informe o município.';
        if (!in_array($d['uf'], ufs(), true)) $errors['uf'] = 'Selecione a UF.';
        if (empty($_POST['consent'])) $errors['consent'] = 'Para analisar o pedido, precisamos do seu aceite à Política de Privacidade.';
        if ($errors) {
            return ['ok' => false, 'errors' => $errors, 'data' => $d];
        }

        $existing = Db::one('SELECT * FROM members WHERE email = ?', [$d['email']]);
        if ($existing && $existing['status'] === 'aprovado') {
            return ['ok' => true, 'errors' => [], 'msg' => 'Este e-mail já tem acesso aprovado. Use o campo "Já tenho acesso" para receber o código.'];
        }
        if ($existing && $existing['status'] === 'pendente') {
            return ['ok' => true, 'errors' => [], 'msg' => 'Já recebemos uma solicitação com este e-mail. Você será avisado assim que ela for analisada.'];
        }
        $row = $d + ['status' => 'pendente', 'consent' => 'Aceite da Política de Privacidade em ' . date('c'), 'created_at' => time()];
        if ($existing) {
            // Pedido novo de quem foi recusado ou suspenso: volta para análise
            $cols = implode(', ', array_map(fn($k) => "$k = ?", array_keys($row)));
            Db::run("UPDATE members SET $cols, decided_at = NULL WHERE id = ?", [...array_values($row), $existing['id']]);
        } else {
            Db::insert('members', $row);
        }
        self::notifyRequest($d);
        self::mail($d['email'], 'Recebemos sua solicitação de acesso aos editais', 'Recebemos sua solicitação', '<p>Olá, ' . e(explode(' ', $d['name'])[0]) . '.</p><p>Recebemos seu pedido de acesso à área de editais do Portal Moeda Social. Nossa equipe vai analisar o cadastro e você receberá um e-mail quando o acesso for liberado.</p>');
        Analytics::event('editais_access_request', $d['org_type'] . ' | ' . $d['municipality'] . '/' . $d['uf'], '/editais');
        return ['ok' => true, 'errors' => [], 'msg' => 'Solicitação enviada. Você receberá um e-mail quando o acesso for aprovado.'];
    }

    /**
     * Envia o código de acesso. A resposta é a mesma para qualquer e-mail,
     * para não revelar quem tem cadastro.
     */
    public static function sendCode(string $email): array
    {
        $email = mb_strtolower(trim($email));
        $generic = ['ok' => true, 'msg' => "Se este e-mail tiver acesso aprovado, enviamos um código de 6 dígitos. Ele vale por " . self::CODE_MINUTES . " minutos."];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'msg' => 'Informe um e-mail válido.'];
        }
        if (!form_token_valid($_POST['_t'] ?? null, 1)) {
            return ['ok' => false, 'msg' => 'O formulário expirou. Atualize a página e tente novamente.'];
        }
        if (rate_limited('codigo-ip:' . ip_hash(), 10, 3600) || rate_limited('codigo:' . $email, 5, 3600)) {
            return ['ok' => false, 'msg' => 'Muitos pedidos de código na última hora. Aguarde e tente novamente.'];
        }
        $m = self::ensureMaster($email) ?? Db::one("SELECT * FROM members WHERE email = ? AND status = 'aprovado'", [$email]);
        if (!$m) {
            return $generic;
        }
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Db::run('UPDATE login_codes SET used_at = ? WHERE member_id = ? AND used_at IS NULL', [time(), $m['id']]);
        Db::insert('login_codes', [
            'member_id' => $m['id'], 'code_hash' => self::codeHash((int) $m['id'], $code),
            'created_at' => time(), 'expires_at' => time() + self::CODE_MINUTES * 60, 'attempts' => 0,
        ]);
        $sent = self::mail($m['email'], "Seu código de acesso: $code", 'Seu código de acesso',
            '<p>Use o código abaixo para entrar na área de editais do Portal Moeda Social:</p>'
            . '<p style="font:bold 32px/1.2 Arial,sans-serif;letter-spacing:8px;background:#f0f3f2;border-radius:12px;padding:16px 20px;text-align:center;margin:20px 0;">' . $code . '</p>'
            . '<p>O código vale por ' . self::CODE_MINUTES . ' minutos e só pode ser usado uma vez. Se não foi você quem pediu, ignore este e-mail.</p>');
        if (!$sent) {
            log_line('editais', "Falha ao enviar código para {$m['email']}");
        }
        return $generic;
    }

    /** Confere o código e abre a sessão. */
    public static function verify(string $email, string $code): array
    {
        $email = mb_strtolower(trim($email));
        $code = preg_replace('/\D/', '', $code);
        $fail = ['ok' => false, 'msg' => 'Código inválido ou vencido. Confira o último e-mail recebido ou peça um novo código.'];
        if (rate_limited('verificar:' . ip_hash(), 20, 3600)) {
            return ['ok' => false, 'msg' => 'Muitas tentativas. Aguarde e tente novamente.'];
        }
        $m = Db::one("SELECT * FROM members WHERE email = ? AND status = 'aprovado'", [$email]);
        $c = $m ? Db::one('SELECT * FROM login_codes WHERE member_id = ? AND used_at IS NULL AND expires_at > ? ORDER BY id DESC LIMIT 1', [$m['id'], time()]) : null;
        if (!$c || strlen($code) !== 6) {
            return $fail;
        }
        if (!hash_equals($c['code_hash'], self::codeHash((int) $m['id'], $code))) {
            $attempts = (int) $c['attempts'] + 1;
            Db::run('UPDATE login_codes SET attempts = ?, used_at = ? WHERE id = ?', [$attempts, $attempts >= self::MAX_ATTEMPTS ? time() : null, $c['id']]);
            return $attempts >= self::MAX_ATTEMPTS ? ['ok' => false, 'msg' => 'Código bloqueado após várias tentativas. Peça um novo código.'] : $fail;
        }
        $nonce = bin2hex(random_bytes(16));
        Db::run('UPDATE login_codes SET used_at = ? WHERE id = ?', [time(), $c['id']]);
        Db::run('UPDATE members SET session_nonce = ?, last_login_at = ?, login_count = login_count + 1 WHERE id = ?', [$nonce, time(), $m['id']]);
        $exp = time() + (int) cfg('editais.session_hours', 12) * 3600;
        self::setCookie($m['id'] . '.' . $exp . '.' . self::sign((int) $m['id'], $exp, $nonce));
        Analytics::event('editais_login', (string) $m['org_type'], '/editais');
        return ['ok' => true, 'msg' => ''];
    }

    public static function logout(): void
    {
        if ($m = self::current()) {
            Db::run('UPDATE members SET session_nonce = NULL WHERE id = ?', [$m['id']]);
        }
        self::setCookie('', time() - 3600);
    }

    /** Decisão do administrador. */
    public static function decide(int $id, string $status): void
    {
        $m = Db::one('SELECT * FROM members WHERE id = ?', [$id]);
        if (!$m || !isset(self::STATUS[$status]) || ($m['email'] === self::master() && $status !== 'aprovado')) {
            return;
        }
        Db::run('UPDATE members SET status = ?, decided_at = ?, session_nonce = ? WHERE id = ?',
            [$status, time(), $status === 'aprovado' ? $m['session_nonce'] : null, $id]);
        if ($status === 'aprovado' && $m['status'] !== 'aprovado') {
            $link = url('/editais', true);
            self::mail($m['email'], 'Seu acesso aos editais foi aprovado', 'Acesso aprovado',
                '<p>Olá, ' . e(explode(' ', (string) $m['name'])[0]) . '.</p><p>Seu acesso à área de editais do Portal Moeda Social foi aprovado.</p>'
                . '<p>Para entrar, acesse <a href="' . e($link) . '">' . e($link) . '</a>, informe este e-mail e digite o código de 6 dígitos que enviaremos a cada acesso. Não é preciso criar senha.</p>'
                . '<p><a href="' . e($link) . '" style="display:inline-block;background:' . Brand::PRIMARY . ';color:#fff;padding:12px 20px;border-radius:10px;text-decoration:none;font-weight:bold;">Entrar na área de editais</a></p>');
        }
    }

    public static function delete(int $id): void
    {
        $m = Db::one('SELECT email FROM members WHERE id = ?', [$id]);
        if ($m && $m['email'] !== self::master()) {
            Db::run('DELETE FROM login_codes WHERE member_id = ?', [$id]);
            Db::run('DELETE FROM members WHERE id = ?', [$id]);   // pedido de eliminação (LGPD art. 18)
        }
    }

    public static function pendingCount(): int
    {
        return (int) Db::value("SELECT COUNT(*) FROM members WHERE status = 'pendente'");
    }

    /** O usuário master sempre tem cadastro aprovado. */
    private static function ensureMaster(string $email): ?array
    {
        if ($email !== self::master() || $email === '') {
            return null;
        }
        $m = Db::one('SELECT * FROM members WHERE email = ?', [$email]);
        if (!$m) {
            Db::insert('members', ['email' => $email, 'name' => 'Usuário master', 'status' => 'aprovado', 'created_at' => time(), 'decided_at' => time(), 'consent' => 'Administrador']);
        } elseif ($m['status'] !== 'aprovado') {
            Db::run("UPDATE members SET status = 'aprovado' WHERE id = ?", [$m['id']]);
        }
        return Db::one('SELECT * FROM members WHERE email = ?', [$email]);
    }

    private static function notifyRequest(array $d): void
    {
        $to = self::master();
        if ($to === '') {
            return;
        }
        $rows = [
            'Nome' => $d['name'], 'E-mail' => $d['email'], 'Telefone' => $d['phone'] ?: '—',
            'Tipo de organização' => Contact::ORG_TYPES[$d['org_type']] ?? $d['org_type'], 'Organização' => $d['organization'],
            'Cargo' => $d['role'] ?: '—', 'Município/UF' => $d['municipality'] . '/' . $d['uf'], 'Motivo' => $d['purpose'] ?: '—',
        ];
        $table = '<table style="width:100%;border-collapse:collapse;font-size:14px;">';
        foreach ($rows as $k => $v) {
            $table .= '<tr><td style="padding:7px 10px;border-bottom:1px solid #e4eaf1;color:#566476;width:34%;">' . e($k) . '</td><td style="padding:7px 10px;border-bottom:1px solid #e4eaf1;"><strong>' . e($v) . '</strong></td></tr>';
        }
        $admin = url('/admin/acessos', true);
        self::mail($to, '[Moeda Social] Pedido de acesso aos editais — ' . $d['name'] . ' (' . $d['municipality'] . '/' . $d['uf'] . ')', 'Novo pedido de acesso aos editais',
            "<p>Uma nova solicitação de acesso à área de editais aguarda aprovação.</p>$table<p style=\"margin-top:18px;\"><a href=\"$admin\">Aprovar ou recusar no painel</a></p>",
            ['reply_to' => $d['email']]);
    }

    private static function mail(string $to, string $subject, string $title, string $body, array $opt = []): bool
    {
        return Mailer::send($to, $subject, Mailer::template($title, $body), $opt);
    }

    private static function sign(int $id, int $exp, string $nonce): string
    {
        return hash_hmac('sha256', "membro|$id|$exp|$nonce", (string) cfg('secret'));
    }

    private static function codeHash(int $id, string $code): string
    {
        return hash_hmac('sha256', "codigo|$id|$code", (string) cfg('secret'));
    }

    private static function setCookie(string $value, int $expires = 0): void
    {
        setcookie(self::COOKIE, $value, [
            'expires' => $expires, 'path' => '/', 'secure' => !empty($_SERVER['HTTPS']) || str_starts_with((string) cfg('site.url'), 'https'),
            'httponly' => true, 'samesite' => 'Lax',
        ]);
        $_COOKIE[self::COOKIE] = $value;
        self::$resolved = false;
    }
}
