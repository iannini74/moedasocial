<?php
declare(strict_types=1);

/** Formulário de apoio à implantação: grava no banco, avisa a equipe e responde ao solicitante. */
final class Contact
{
    public const ORG_TYPES = [
        'prefeitura'   => 'Prefeitura / Secretaria municipal',
        'camara'       => 'Câmara Municipal / mandato',
        'consorcio'    => 'Consórcio ou associação de municípios',
        'governo'      => 'Governo estadual / federal',
        'banco-comunitario' => 'Banco comunitário / OSC / ONG',
        'associacao'   => 'Associação comercial / CDL / Sebrae',
        'empresa'      => 'Empresa (benefícios, ESG, incentivos)',
        'academia'     => 'Universidade / pesquisa',
        'outro'        => 'Outro',
    ];
    public const STAGES = [
        'conhecendo'   => 'Estou conhecendo o tema',
        'estudando'    => 'Estudando viabilidade para o município',
        'projeto-lei'  => 'Preparando ou com projeto de lei',
        'lei-aprovada' => 'Já temos lei aprovada',
        'operando'     => 'Já operamos uma moeda social e queremos evoluir',
    ];
    public const INTERESTS = [
        'diagnostico'  => 'Diagnóstico econômico do município',
        'modelagem'    => 'Modelagem do programa (público, valores, regras)',
        'juridico'     => 'Minuta de lei e segurança jurídica',
        'tecnologia'   => 'Plataforma digital (app, QR Code, painel)',
        'comercio'     => 'Credenciamento e engajamento do comércio',
        'transparencia'=> 'Transparência e prestação de contas (TCE)',
        'tributario'   => 'Impactos na arrecadação / Reforma Tributária',
        'capacitacao'  => 'Capacitação de equipes',
        'apresentacao' => 'Apresentação para prefeito(a) / Câmara',
    ];
    public const TIMELINES = [
        'imediato' => 'O quanto antes (até 3 meses)',
        'semestre' => 'Neste semestre',
        'ano'      => 'Nos próximos 12 meses',
        'sem-prazo'=> 'Ainda sem prazo definido',
    ];
    public const POPULATIONS = [
        'ate-20k' => 'Até 20 mil habitantes', '20-50k' => '20 a 50 mil', '50-100k' => '50 a 100 mil',
        '100-500k' => '100 a 500 mil', '500k+' => 'Mais de 500 mil', 'na' => 'Não se aplica',
    ];

    public static function handle(): array
    {
        $errors = [];
        if (!empty($_POST['website'])) {               // honeypot
            return ['ok' => true, 'errors' => []];
        }
        if (!form_token_valid($_POST['_t'] ?? null, 3)) {
            $errors['_form'] = 'Sua sessão do formulário expirou. Atualize a página e envie novamente — seus dados não foram perdidos se o navegador os mantiver.';
        }
        if (rate_limited('contact:' . ip_hash(), 5, 3600)) {
            $errors['_form'] = 'Recebemos várias mensagens deste acesso na última hora. Aguarde um pouco ou escreva para ' . cfg('mail.contact_to') . '.';
        }

        $d = [
            'name' => input('name', 150), 'email' => mb_strtolower(input('email', 190)), 'phone' => input('phone', 40),
            'org_type' => input('org_type', 60), 'organization' => input('organization', 190), 'role' => input('role', 120),
            'municipality' => input('municipality', 150), 'uf' => strtoupper(input('uf', 2)), 'population' => input('population', 40),
            'stage' => input('stage', 60), 'timeline' => input('timeline', 60), 'message' => input('message', 5000),
            'preferred_contact' => input('preferred_contact', 30),
        ];
        $interests = input_list('interests', array_keys(self::INTERESTS));

        if (mb_strlen($d['name']) < 3) $errors['name'] = 'Conte para nós seu nome completo.';
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Precisamos de um e-mail válido para responder.';
        if ($d['phone'] !== '' && strlen(preg_replace('/\D/', '', $d['phone'])) < 10) $errors['phone'] = 'Inclua o DDD no telefone (ex.: 31 99999-9999).';
        if (!isset(self::ORG_TYPES[$d['org_type']])) $errors['org_type'] = 'Selecione o tipo de organização.';
        if ($d['municipality'] === '') $errors['municipality'] = 'Informe o município.';
        if (!in_array($d['uf'], ufs(), true)) $errors['uf'] = 'Selecione a UF.';
        if (!isset(self::STAGES[$d['stage']])) $errors['stage'] = 'Em que momento vocês estão?';
        if (empty($_POST['consent'])) $errors['consent'] = 'Para responder, precisamos do seu aceite à Política de Privacidade.';

        if ($errors) {
            return ['ok' => false, 'errors' => $errors, 'data' => $d + ['interests' => $interests]];
        }

        $newsletter = !empty($_POST['newsletter']);
        $id = Db::insert('contacts', $d + [
            'created_at' => time(), 'interests' => implode(',', $interests), 'newsletter' => $newsletter ? 1 : 0,
            'consent' => 'Aceite da Política de Privacidade em ' . date('c'),
            'source_page' => mb_substr((string) ($_POST['source'] ?? '/contato'), 0, 255),
        ]);

        $teamOk = self::notifyTeam($id, $d, $interests);
        $userOk = self::autoReply($d, $interests);
        Db::run('UPDATE contacts SET email_status = ? WHERE id = ?', [($teamOk ? 'equipe-ok' : 'equipe-falhou') . '/' . ($userOk ? 'cliente-ok' : 'cliente-falhou'), $id]);

        if ($newsletter) {
            Newsletter::subscribe($d['email'], $d['name'], $d['org_type'] === 'prefeitura' ? 'gestor-publico' : 'cidadao', $d['municipality'], $d['uf'], 'formulario-contato');
        }
        Analytics::event('contact_submit', $d['org_type'] . ' | ' . $d['municipality'] . '/' . $d['uf'], '/contato');
        return ['ok' => true, 'errors' => [], 'name' => $d['name'], 'protocol' => self::protocol($id)];
    }

    public static function protocol(int $id): string
    {
        return 'MS-' . date('Y') . '-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
    }

    private static function notifyTeam(int $id, array $d, array $interests): bool
    {
        $rows = [
            'Protocolo' => self::protocol($id), 'Nome' => $d['name'], 'E-mail' => $d['email'], 'Telefone/WhatsApp' => $d['phone'] ?: '—',
            'Prefere contato por' => $d['preferred_contact'] ?: '—', 'Tipo de organização' => self::ORG_TYPES[$d['org_type']] ?? $d['org_type'],
            'Organização' => $d['organization'] ?: '—', 'Cargo/função' => $d['role'] ?: '—',
            'Município/UF' => $d['municipality'] . '/' . $d['uf'], 'População' => self::POPULATIONS[$d['population']] ?? '—',
            'Estágio' => self::STAGES[$d['stage']] ?? '—', 'Prazo' => self::TIMELINES[$d['timeline']] ?? '—',
            'Interesses' => $interests ? implode('; ', array_map(fn($i) => self::INTERESTS[$i], $interests)) : '—',
        ];
        $table = '<table style="width:100%;border-collapse:collapse;font-size:14px;">';
        foreach ($rows as $k => $v) {
            $table .= '<tr><td style="padding:7px 10px;border-bottom:1px solid #e4eaf1;color:#566476;width:38%;">' . e($k) . '</td><td style="padding:7px 10px;border-bottom:1px solid #e4eaf1;"><strong>' . e($v) . '</strong></td></tr>';
        }
        $table .= '</table>';
        $msg = nl2br(e($d['message'] ?: '(sem mensagem)'));
        $admin = url('/admin/contatos', true);
        $html = Mailer::template('Nova solicitação de apoio à implantação', "<p>Um novo contato chegou pelo portal. Responder em até 2 dias úteis.</p>$table<h3 style=\"margin:22px 0 8px;font-size:16px;\">Mensagem</h3><div style=\"background:#f6f8fb;border-radius:10px;padding:14px;\">$msg</div><p style=\"margin-top:18px;font-size:13px;\"><a href=\"$admin\">Ver no painel administrativo</a></p>");
        return Mailer::send(
            cfg('mail.contact_to'),
            '[Moeda Social] ' . self::protocol($id) . ' — ' . $d['municipality'] . '/' . $d['uf'] . ' — ' . $d['name'],
            $html,
            ['reply_to' => $d['email']]
        );
    }

    private static function autoReply(array $d, array $interests): bool
    {
        $first = e(explode(' ', $d['name'])[0]);
        $mun = e($d['municipality'] . '/' . $d['uf']);
        $list = $interests ? '<ul>' . implode('', array_map(fn($i) => '<li>' . e(self::INTERESTS[$i]) . '</li>', $interests)) . '</ul>' : '';
        $diag = url('/diagnostico?utm_source=autoresposta&utm_medium=email', true);
        $estudos = url('/estudos?utm_source=autoresposta&utm_medium=email', true);
        $html = Mailer::template("Recebemos sua mensagem, {$first}!", <<<HTML
<p>Obrigado por entrar em contato. Recebemos sua solicitação sobre moeda social para <strong>{$mun}</strong>.</p>
<p>Sua solicitação já está com nossa equipe de especialistas em moeda social e meios de pagamento. <strong>Vamos responder em até 2 dias úteis</strong> pelo canal que você indicou.</p>
{$list}
<p><strong>Como costuma ser a conversa inicial (30 minutos, sem compromisso):</strong></p>
<ol>
<li>Entendemos a realidade do município: público, orçamento disponível e objetivos;</li>
<li>Apresentamos os modelos possíveis e os cuidados jurídicos e de controle;</li>
<li>Indicamos os próximos passos — da minuta de lei ao credenciamento do comércio.</li>
</ol>
<p><strong>Para adiantar:</strong> se ainda não fez, gere o <a href="{$diag}">diagnóstico gratuito do seu município</a> — ele ajuda a dimensionar o programa e serve de base para a nossa conversa. Você também pode explorar os <a href="{$estudos}">estudos completos</a> sobre moedas sociais.</p>
<p>Até breve!<br><strong>Equipe Portal Moeda Social</strong></p>
HTML);
        return Mailer::send($d['email'], 'Recebemos sua mensagem — Portal Moeda Social', $html, ['to_name' => $d['name'], 'reply_to' => cfg('mail.contact_to')]);
    }
}
