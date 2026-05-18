<?php
// ============================================================
//  AdminFlow — API Bate-Ponto
//  Calcula duração automaticamente na Saída
//  Atualiza online/last_seen do funcionário
// ============================================================
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

setHeaders();
$sess   = requireAuth();
$action = $_GET['action'] ?? getBody()['action'] ?? '';
$b      = getBody();

switch ($action) {

    // ── REGISTAR ENTRADA OU SAÍDA ─────────────────────────
    case 'registar':
        $fid  = (int)($b['funcionario_id'] ?? $sess['func_id'] ?? 0);
        $tipo = $b['tipo'] ?? '';
        $obs  = clean($b['observacao'] ?? '');

        // Funcionário só pode registar o próprio
        if ($sess['role'] !== 'admin' && $sess['func_id'] !== $fid) {
            fail('Não pode registar ponto de outro funcionário.', 403);
        }
        if (!in_array($tipo, ['Entrada','Saída'], true)) fail('Tipo inválido.');
        if (!$fid) fail('Funcionário inválido.');

        $durMin = null;

        if ($tipo === 'Saída') {
            // Encontra a última Entrada sem Saída correspondente (hoje)
            $lastEnt = DB::row(
                "SELECT id, timestamp FROM bate_ponto
                 WHERE funcionario_id=? AND tipo='Entrada'
                   AND DATE(timestamp)=CURDATE()
                 ORDER BY timestamp DESC LIMIT 1",
                [$fid]
            );
            if ($lastEnt) {
                $durMin = (int)round(
                    (time() - strtotime($lastEnt['timestamp'])) / 60
                );
            }
            // Marca offline + last_seen
            DB::execute(
                "UPDATE funcionarios SET online=0, last_seen=NOW() WHERE id=?", [$fid]
            );
        } else {
            // Entrada → marca online
            DB::execute("UPDATE funcionarios SET online=1 WHERE id=?", [$fid]);
        }

        $id = DB::execute(
            "INSERT INTO bate_ponto (funcionario_id, tipo, duracao_minutos, observacao)
             VALUES (?,?,?,?)",
            [$fid, $tipo, $durMin, $obs]
        );

        ok([
            'id'              => $id,
            'tipo'            => $tipo,
            'duracao_minutos' => $durMin,
            'hora'            => date('H:i'),
            'timestamp'       => date('Y-m-d H:i:s'),
        ], "$tipo registada.");
        break;

    // ── TURNO COMPLETO (admin) ────────────────────────────
    case 'turno_completo':
        requireAdmin();
        $fid   = (int)($b['funcionario_id'] ?? 0);
        $horas = (float)($b['horas'] ?? 8);
        $obs   = clean($b['observacao'] ?? 'Turno completo');
        if (!$fid) fail('Funcionário inválido.');

        $durMin    = (int)($horas * 60);
        $saidaTs   = date('Y-m-d H:i:s');
        $entradaTs = date('Y-m-d H:i:s', time() - $durMin * 60);

        DB::execute(
            "INSERT INTO bate_ponto (funcionario_id, tipo, timestamp, observacao) VALUES (?,?,?,?)",
            [$fid, 'Entrada', $entradaTs, $obs]
        );
        DB::execute(
            "INSERT INTO bate_ponto (funcionario_id, tipo, timestamp, duracao_minutos, observacao)
             VALUES (?,?,?,?,?)",
            [$fid, 'Saída', $saidaTs, $durMin, $obs]
        );
        DB::execute("UPDATE funcionarios SET online=0, last_seen=NOW() WHERE id=?", [$fid]);

        ok(['duracao_minutos' => $durMin], "Turno de {$horas}h registado.");
        break;

    // ── REGISTOS DE HOJE ──────────────────────────────────
    case 'hoje':
        $fid = (int)($_GET['func_id'] ?? $sess['func_id'] ?? 0);
        if ($sess['role'] !== 'admin' && $sess['func_id'] !== $fid) fail('Acesso negado.',403);

        $rows = DB::query(
            "SELECT bp.*, f.nome AS func_nome, d.nome AS div_nome
             FROM bate_ponto bp
             JOIN funcionarios f ON f.id = bp.funcionario_id
             LEFT JOIN divisoes d ON d.id = f.divisao_id
             WHERE " . ($fid ? "bp.funcionario_id=? AND " : "") . "DATE(bp.timestamp)=CURDATE()
             ORDER BY bp.timestamp DESC",
            $fid ? [$fid] : []
        );
        ok($rows);
        break;

    // ── HISTÓRICO ─────────────────────────────────────────
    case 'historico':
        $fid   = (int)($_GET['func_id'] ?? 0);
        $limit = min((int)($_GET['limit'] ?? 100), 500);

        if ($sess['role'] !== 'admin') {
            $fid = (int)$sess['func_id'];
        }

        $sql    = "SELECT bp.*, f.nome AS func_nome, d.nome AS div_nome
                   FROM bate_ponto bp
                   JOIN funcionarios f ON f.id = bp.funcionario_id
                   LEFT JOIN divisoes d ON d.id = f.divisao_id";
        $params = [];
        if ($fid) { $sql .= " WHERE bp.funcionario_id=?"; $params[] = $fid; }
        $sql .= " ORDER BY bp.timestamp DESC LIMIT $limit";

        ok(DB::query($sql, $params));
        break;

    // ── RESUMO DO MÊS (todos os funcionários — admin) ────
    case 'resumo_mes':
        requireAdmin();
        $rows = DB::query(
            "SELECT f.id, f.nome, f.meta_horas, f.online,
                    COALESCE(SUM(bp.duracao_minutos),0) AS minutos_mes
             FROM funcionarios f
             LEFT JOIN bate_ponto bp ON bp.funcionario_id = f.id
               AND bp.tipo='Saída'
               AND YEAR(bp.timestamp)=YEAR(NOW())
               AND MONTH(bp.timestamp)=MONTH(NOW())
             WHERE f.ativo=1
             GROUP BY f.id
             ORDER BY f.nome"
        );
        foreach ($rows as &$r) {
            $r['horas_mes'] = round($r['minutos_mes'] / 60, 2);
            $r['pct']       = $r['meta_horas'] > 0
                ? min(100, round($r['horas_mes'] / $r['meta_horas'] * 100))
                : 0;
        }
        ok($rows);
        break;

    default:
        fail('Ação desconhecida.');
}
