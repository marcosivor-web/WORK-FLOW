<?php
// ============================================================
//  AdminFlow — API Funcionários
//  Inclui: telemovel, email, online(0/1), last_seen
// ============================================================
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

setHeaders();
$sess   = requireAuth();
$action = $_GET['action'] ?? getBody()['action'] ?? '';
$b      = getBody();

switch ($action) {

    // ── LISTAR TODOS ──────────────────────────────────────
    case 'list':
        $rows = DB::query(
            "SELECT f.*, d.nome AS divisao_nome,
                    u.login,
                    CASE WHEN f.online=1 THEN 'online'
                         ELSE COALESCE(f.last_seen,'') END AS status_info
             FROM funcionarios f
             LEFT JOIN divisoes  d ON d.id = f.divisao_id
             LEFT JOIN usuarios  u ON u.funcionario_id = f.id
             WHERE f.ativo = 1
             ORDER BY f.nome ASC"
        );
        // Adiciona label legível de last_seen
        foreach ($rows as &$r) {
            $r['last_seen_label'] = $r['online'] ? 'Online agora' : timeAgo($r['last_seen']);
        }
        ok($rows);
        break;

    // ── OBTER UM ──────────────────────────────────────────
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $r  = DB::row(
            "SELECT f.*, d.nome AS divisao_nome, u.login
             FROM funcionarios f
             LEFT JOIN divisoes d ON d.id = f.divisao_id
             LEFT JOIN usuarios u ON u.funcionario_id = f.id
             WHERE f.id=? AND f.ativo=1",
            [$id]
        );
        if (!$r) fail('Funcionário não encontrado.', 404);
        $r['last_seen_label'] = $r['online'] ? 'Online agora' : timeAgo($r['last_seen']);
        ok($r);
        break;

    // ── CRIAR (apenas admin) ──────────────────────────────
    case 'create':
        requireAdmin();

        $nome      = clean($b['nome']      ?? '');
        $cargo     = clean($b['cargo']     ?? '');
        $turno     = clean($b['turno']     ?? '');
        $divId     = (int)($b['divisao_id'] ?? 0) ?: null;
        $tel       = clean($b['telemovel'] ?? '');
        $email     = filter_var($b['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $meta      = (int)($b['meta_horas'] ?? 160);
        $login     = clean($b['login']     ?? '');
        $pass      = $b['pass']  ?? 'ponto123';
        $online    = (int)($b['online']    ?? 0);

        if (!$nome) fail('Nome é obrigatório.');
        if (!$login) fail('Login é obrigatório.');

        // Verifica login único
        $exists = DB::scalar("SELECT id FROM usuarios WHERE login=?", [$login]);
        if ($exists) fail("Login '$login' já existe.");

        // Insere funcionário
        $fid = DB::execute(
            "INSERT INTO funcionarios (nome,cargo,turno,divisao_id,telemovel,email,meta_horas,online)
             VALUES (?,?,?,?,?,?,?,?)",
            [$nome, $cargo, $turno, $divId, $tel, $email, $meta, $online]
        );

        // Cria utilizador
        DB::execute(
            "INSERT INTO usuarios (login, senha_hash, role, funcionario_id)
             VALUES (?, ?, 'func', ?)",
            [$login, password_hash($pass, PASSWORD_BCRYPT), $fid]
        );

        ok(['id' => $fid], 'Funcionário criado com sucesso.');
        break;

    // ── ACTUALIZAR (apenas admin) ─────────────────────────
    case 'update':
        requireAdmin();

        $id    = (int)($b['id'] ?? 0);
        $nome  = clean($b['nome']      ?? '');
        $cargo = clean($b['cargo']     ?? '');
        $turno = clean($b['turno']     ?? '');
        $divId = (int)($b['divisao_id'] ?? 0) ?: null;
        $tel   = clean($b['telemovel'] ?? '');
        $email = filter_var($b['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $meta  = (int)($b['meta_horas'] ?? 160);
        $login = clean($b['login']     ?? '');
        $pass  = $b['pass'] ?? '';

        if (!$id || !$nome) fail('ID e nome são obrigatórios.');

        DB::execute(
            "UPDATE funcionarios SET nome=?,cargo=?,turno=?,divisao_id=?,
             telemovel=?,email=?,meta_horas=? WHERE id=?",
            [$nome, $cargo, $turno, $divId, $tel, $email, $meta, $id]
        );

        // Atualiza login se fornecido
        if ($login) {
            $uid = DB::scalar("SELECT id FROM usuarios WHERE funcionario_id=?", [$id]);
            if ($uid) {
                $other = DB::scalar(
                    "SELECT id FROM usuarios WHERE login=? AND id!=?", [$login, $uid]
                );
                if ($other) fail("Login '$login' já está em uso.");
                DB::execute("UPDATE usuarios SET login=? WHERE id=?", [$login, $uid]);
            }
        }

        // Atualiza password se fornecida
        if ($pass && strlen($pass) >= 6) {
            DB::execute(
                "UPDATE usuarios SET senha_hash=? WHERE funcionario_id=?",
                [password_hash($pass, PASSWORD_BCRYPT), $id]
            );
        }

        ok(null, 'Funcionário atualizado.');
        break;

    // ── APAGAR (apenas admin) ────────────────────────────
    case 'delete':
        requireAdmin();
        $id = (int)($b['id'] ?? $_GET['id'] ?? 0);
        if (!$id) fail('ID inválido.');
        // Soft delete
        DB::execute("UPDATE funcionarios SET ativo=0 WHERE id=?", [$id]);
        DB::execute("UPDATE usuarios SET ativo=0 WHERE funcionario_id=?", [$id]);
        ok(null, 'Funcionário removido.');
        break;

    // ── TOGGLE ONLINE (0↔1) ───────────────────────────────
    // Atualiza online e last_seen automaticamente
    case 'toggle_online':
        requireAdmin();
        $id = (int)($b['id'] ?? 0);
        $f  = DB::row("SELECT online FROM funcionarios WHERE id=?", [$id]);
        if (!$f) fail('Funcionário não encontrado.', 404);

        $novoOnline = $f['online'] ? 0 : 1;
        if ($novoOnline === 0) {
            // A ficar offline → guarda last_seen
            DB::execute(
                "UPDATE funcionarios SET online=0, last_seen=NOW() WHERE id=?", [$id]
            );
        } else {
            DB::execute(
                "UPDATE funcionarios SET online=1 WHERE id=?", [$id]
            );
        }
        ok(['online' => $novoOnline]);
        break;

    // ── DEFINIR STATUS DIRECTO ────────────────────────────
    case 'set_online':
        $id     = (int)($b['id'] ?? 0);
        $online = (int)($b['online'] ?? 0);

        // Funcionário só pode atualizar o seu próprio status
        if ($sess['role'] !== 'admin' && $sess['func_id'] !== $id) {
            fail('Acesso negado.', 403);
        }

        if ($online === 0) {
            DB::execute(
                "UPDATE funcionarios SET online=0, last_seen=NOW() WHERE id=?", [$id]
            );
        } else {
            DB::execute("UPDATE funcionarios SET online=1 WHERE id=?", [$id]);
        }
        ok(['online' => $online]);
        break;

    // ── HORAS DO MÊS ─────────────────────────────────────
    case 'horas_mes':
        $fid = (int)($_GET['func_id'] ?? $sess['func_id'] ?? 0);
        if (!$fid) fail('func_id é obrigatório.');
        // Apenas o próprio ou admin
        if ($sess['role'] !== 'admin' && $sess['func_id'] !== $fid) fail('Acesso negado.', 403);

        $rows = DB::query(
            "SELECT tipo, timestamp, duracao_minutos
             FROM bate_ponto
             WHERE funcionario_id=?
               AND YEAR(timestamp)=YEAR(NOW())
               AND MONTH(timestamp)=MONTH(NOW())
             ORDER BY timestamp ASC",
            [$fid]
        );

        $totalMin = 0;
        $lastEnt  = null;
        foreach ($rows as $r) {
            if ($r['tipo'] === 'Entrada') {
                $lastEnt = $r['timestamp'];
            } elseif ($r['tipo'] === 'Saída' && $lastEnt) {
                $totalMin += (int)$r['duracao_minutos'];
                $lastEnt = null;
            }
        }
        // Sessão aberta agora
        $sessaoAberta = 0;
        if ($lastEnt) {
            $sessaoAberta = (int)round((time() - strtotime($lastEnt)) / 60);
            $totalMin    += $sessaoAberta;
        }

        $meta = DB::scalar("SELECT meta_horas FROM funcionarios WHERE id=?", [$fid]);
        ok([
            'minutos'       => $totalMin,
            'horas'         => round($totalMin / 60, 2),
            'meta_horas'    => (int)$meta,
            'sessao_aberta' => $sessaoAberta > 0,
            'pct'           => $meta > 0 ? min(100, round(($totalMin/60)/$meta*100)) : 0,
        ]);
        break;

    default:
        fail('Ação desconhecida.');
}
