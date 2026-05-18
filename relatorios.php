<?php
// ============================================================
//  AdminFlow — API Relatórios
//  Devolve dados agregados para os gráficos e PDF
// ============================================================
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

setHeaders();
requireAuth();

$action = $_GET['action'] ?? 'dashboard';

switch ($action) {

    // ── Dados completos do dashboard de relatórios ─────────
    case 'dashboard':
        $mes   = (int)($_GET['mes']  ?? date('m'));
        $ano   = (int)($_GET['ano']  ?? date('Y'));

        // Tickets por estado
        $tkEstado = DB::query(
            "SELECT estado, COUNT(*) AS total FROM tickets GROUP BY estado"
        );

        // Tickets por prioridade
        $tkPrio = DB::query(
            "SELECT prioridade, COUNT(*) AS total FROM tickets GROUP BY prioridade"
        );

        // Funcionários por divisão
        $fnDiv = DB::query(
            "SELECT d.nome AS divisao, COUNT(f.id) AS total,
                    SUM(f.online) AS online
             FROM divisoes d
             LEFT JOIN funcionarios f ON f.divisao_id = d.id AND f.ativo = 1
             GROUP BY d.id ORDER BY total DESC"
        );

        // Horas trabalhadas por funcionário (mês actual)
        $horas = DB::query(
            "SELECT f.nome, f.meta_horas,
                    COALESCE(SUM(bp.duracao_minutos),0) AS minutos
             FROM funcionarios f
             LEFT JOIN bate_ponto bp ON bp.funcionario_id = f.id
               AND bp.tipo = 'Saída'
               AND MONTH(bp.timestamp) = ?
               AND YEAR(bp.timestamp)  = ?
             WHERE f.ativo = 1
             GROUP BY f.id
             ORDER BY minutos DESC
             LIMIT 10",
            [$mes, $ano]
        );

        // Pontos por dia da semana (últimos 30 dias)
        $pontoDia = DB::query(
            "SELECT DAYOFWEEK(timestamp) AS dow,
                    DAYNAME(timestamp)   AS dia,
                    COUNT(*) AS total
             FROM bate_ponto
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
               AND tipo = 'Entrada'
             GROUP BY dow, dia ORDER BY dow"
        );

        // Tickets abertos nos últimos 6 meses
        $tkMes = DB::query(
            "SELECT DATE_FORMAT(criado_em,'%Y-%m') AS mes,
                    COUNT(*) AS total
             FROM tickets
             WHERE criado_em >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
             GROUP BY mes ORDER BY mes"
        );

        // Resumo geral
        $resumo = [
            'total_funcionarios' => (int)DB::scalar("SELECT COUNT(*) FROM funcionarios WHERE ativo=1"),
            'online_agora'       => (int)DB::scalar("SELECT COUNT(*) FROM funcionarios WHERE online=1"),
            'tickets_pendentes'  => (int)DB::scalar("SELECT COUNT(*) FROM tickets WHERE estado='Pendente'"),
            'tickets_resolvidos' => (int)DB::scalar("SELECT COUNT(*) FROM tickets WHERE estado='Resolvido'"),
            'divisoes_ativas'    => (int)DB::scalar("SELECT COUNT(*) FROM divisoes WHERE ativa=1"),
            'horas_mes_total'    => (int)DB::scalar(
                "SELECT COALESCE(SUM(duracao_minutos),0) FROM bate_ponto
                 WHERE tipo='Saída' AND MONTH(timestamp)=? AND YEAR(timestamp)=?",
                [$mes, $ano]
            ),
        ];

        ok([
            'resumo'      => $resumo,
            'tk_estado'   => $tkEstado,
            'tk_prio'     => $tkPrio,
            'fn_divisao'  => $fnDiv,
            'horas'       => $horas,
            'ponto_dia'   => $pontoDia,
            'tk_mes'      => $tkMes,
            'mes'         => $mes,
            'ano'         => $ano,
        ]);
        break;

    default:
        fail('Ação desconhecida.');
}
ENDPHP
echo "relatorios.php ok"