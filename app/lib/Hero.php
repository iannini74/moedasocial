<?php
declare(strict_types=1);

/**
 * Primeira dobra da página inicial: muda automaticamente a cada 3 dias,
 * alternando mensagens e painéis em sequência fixa.
 */
final class Hero
{
    private const EPOCH = '2026-10-28';
    private const DAYS = 3;

    private static function variants(): array
    {
        $post = Blog::published(1)[0] ?? null;
        $list = [
            [
                'kicker' => 'Moeda social · desenvolvimento local',
                'title' => 'Moeda social: mantenha no município o dinheiro que <em>hoje é gasto fora</em>',
                'lead' => 'Guia completo para prefeituras e comunidades: o que é moeda social, como funciona, o que diz a lei, o que os Tribunais de Contas exigem e quais resultados já foram medidos.',
                'cta' => ['Fazer o diagnóstico gratuito', '/diagnostico'], 'cta2' => ['Entender o conceito', '/estudos/o-que-e-moeda-social'],
                'panel' => 'flow',
            ],
            [
                'kicker' => 'Reforma Tributária',
                'title' => 'O imposto vai para a cidade <em>onde a compra acontece</em>',
                'lead' => 'De 2029 a 2077, uma parte cada vez maior do IBS vai para o município onde o morador compra. Manter as compras na cidade passa a significar mais receita.',
                'cta' => ['Entender o impacto no município', '/estudos/reforma-tributaria-e-municipios'], 'cta2' => ['Fazer o diagnóstico', '/diagnostico'],
                'panel' => 'ibs',
            ],
            [
                'kicker' => 'Caso Maricá (RJ)',
                'title' => 'Maricá paga benefícios em moeda social desde 2013. <em>Veja o que funcionou</em>',
                'lead' => 'O maior programa do país: dezenas de milhares de famílias, milhares de comércios e resultados medidos por universidades. E também os erros que levaram a correções do Tribunal de Contas.',
                'cta' => ['Ler os casos brasileiros', '/estudos/cases-brasil'], 'cta2' => ['Lições para quem vai começar', '/blog/licoes-da-mumbuca'],
                'panel' => 'marica',
            ],
            [
                'kicker' => 'Diagnóstico gratuito',
                'title' => 'Quanto a moeda social pode movimentar <em>no seu município?</em>',
                'lead' => 'Informe a população e o valor do benefício. Em 3 minutos você recebe a estimativa de vendas, empregos e impostos, o que falta para começar e um relatório em PDF.',
                'cta' => ['Começar o diagnóstico', '/diagnostico'], 'cta2' => ['Por que adotar', '/por-que-adotar-moeda-social'],
                'panel' => 'diagnostic',
            ],
            [
                'kicker' => 'Legislação',
                'title' => 'Moeda social é permitida por lei? <em>Entenda o que diz o Banco Central</em>',
                'lead' => 'Sim, quando segue algumas regras: vale 1 real, tem o mesmo valor guardado em reais, é aceita só no município e é criada por lei municipal. Veja os detalhes e os cuidados.',
                'cta' => ['Ler o estudo sobre a lei', '/estudos/marco-legal-e-regulacao'], 'cta2' => ['Perguntas frequentes', '/perguntas-frequentes'],
                'panel' => 'legal',
            ],
            [
                'kicker' => 'Tribunais de Contas',
                'title' => 'O que os Tribunais de Contas <em>exigem</em> de uma moeda social',
                'lead' => 'As decisões já publicadas mostram quatro pontos de atenção: onde fica o dinheiro, quem fica com os rendimentos, quais taxas são cobradas e como o cadastro é conferido.',
                'cta' => ['Ver a visão dos órgãos de controle', '/estudos/tcu-tce-e-juristas'], 'cta2' => ['Riscos e como evitar', '/estudos/riscos-e-entraves'],
                'panel' => 'control',
            ],
            [
                'kicker' => 'Comércio local',
                'title' => 'Mais vendas para o comércio do bairro, <em>com o mesmo orçamento social</em>',
                'lead' => 'O benefício que a prefeitura já paga passa a ser gasto nos comércios da cidade. A família continua protegida, e o comércio local ganha clientes todos os meses.',
                'cta' => ['Ver os 10 motivos para adotar', '/por-que-adotar-moeda-social'], 'cta2' => ['Fazer o diagnóstico', '/diagnostico'],
                'panel' => 'stats',
            ],
            [
                'kicker' => 'Novo no blog',
                'title' => $post ? e($post['title']) : 'Análises quinzenais sobre <em>moeda social</em>',
                'lead' => $post ? e((string) $post['description']) : 'Artigos com fontes sobre moeda social, regulação e desenvolvimento local.',
                'cta' => ['Ler o artigo', $post ? '/blog/' . $post['slug'] : '/blog'], 'cta2' => ['Ver todos os artigos', '/blog'],
                'panel' => 'article', 'post' => $post,
            ],
            [
                'kicker' => 'Caso Niterói (RJ)',
                'title' => 'Niterói: 54 mil famílias e <em>mais de 8 mil comércios</em> usando moeda social',
                'lead' => 'Criada por lei em 2021, a moeda Arariboia dobrou de tamanho em quatro anos e passou por duas gestões. Veja como funciona e o que o Tribunal de Contas apontou.',
                'cta' => ['Ler os casos brasileiros', '/estudos/cases-brasil'], 'cta2' => ['Cases no mundo', '/estudos/cases-mundiais'],
                'panel' => 'niteroi',
            ],
            [
                'kicker' => 'Implantação',
                'title' => 'Como implantar uma moeda social <em>em até 6 meses</em>',
                'lead' => 'Sete etapas, do diagnóstico ao lançamento: lei municipal, conta da prefeitura para o dinheiro do programa, tecnologia, cadastro do comércio e prestação de contas.',
                'cta' => ['Ver o roteiro completo', '/estudos/como-implantar'], 'cta2' => ['Falar com especialistas', '/contato'],
                'panel' => 'steps',
            ],
        ];
        return $list;
    }

    /** Variante da vez e data em que entrou no ar. */
    public static function current(): array
    {
        $tz = new DateTimeZone(cfg('site.timezone'));
        $epoch = new DateTimeImmutable(self::EPOCH, $tz);
        $today = new DateTimeImmutable('today', $tz);
        $days = max(0, (int) $epoch->diff($today)->days);
        $slot = intdiv($days, self::DAYS);
        $variants = self::variants();
        if (getenv("HERO_INDEX") !== false && PHP_SAPI === "cli-server") {
            $slot = (int) getenv("HERO_INDEX");
        }
        $v = $variants[$slot % count($variants)];
        $v['since'] = $epoch->modify('+' . ($slot * self::DAYS) . ' days')->getTimestamp();
        $v['index'] = $slot % count($variants);
        return $v;
    }
}
