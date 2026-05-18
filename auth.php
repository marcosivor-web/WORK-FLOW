<?php
// ============================================================
//  AdminFlow — Autenticação
//  POST /auth.php  body: { action, login, pass }
//  GET  /auth.php?action=check
//  GET  /auth.php?action=logout
// ============================================================
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

setHeaders();
startSession();

$action = $_GET['action'] ?? getBody()['action'] ?? '';

switch ($action) {

    // ── LOGIN ──────────────────────────────────────────────
    case 'login':
        $b    = getBody();
        $login = clean($b['login'] ?? '');
        $pass  = $b['pass'] ?? '';

        if (!$login || !$pass) fail('Preencha utilizador e password.');

        $u = DB::row(
            "SELECT u.*, f.nome AS func_nome, f.email AS func_email, f.telemovel AS func_tel
             FROM usuarios u
             LEFT JOIN funcionarios f ON f.id = u.funcionario_id
             WHERE u.login = ? AND u.ativo = 1 LIMIT 1",
            [$login]
        );

        if (!$u || !password_verify($pass, $u['senha_hash'])) {
            fail('Credenciais incorretas.', 401);
        }

        // Inicia sessão
        $userData = [
            'id'           => $u['id'],
            'login'        => $u['login'],
            'role'         => $u['role'],
            'func_id'      => $u['funcionario_id'],
            'func_nome'    => $u['func_nome'],
            'func_email'   => $u['func_email'],
            'func_tel'     => $u['func_tel'],
        ];
        $_SESSION['user']    = $userData;
        $_SESSION['expires'] = time() + SESSION_LIFETIME;

        // Marca funcionário como online
        if ($u['funcionario_id']) {
            DB::execute(
                "UPDATE funcionarios SET online=1 WHERE id=?",
                [$u['funcionario_id']]
            );
        }

        // Regista último login
        DB::execute(
            "UPDATE usuarios SET ultimo_login=NOW() WHERE id=?",
            [$u['id']]
        );

        ok($userData, 'Login efetuado com sucesso.');
        break;

    // ── LOGOUT ────────────────────────────────────────────
    case 'logout':
        $u = getSession();
        if ($u && $u['func_id']) {
            // Marca offline + last_seen
            DB::execute(
                "UPDATE funcionarios SET online=0, last_seen=NOW() WHERE id=?",
                [$u['func_id']]
            );
        }
        session_destroy();
        ok(null, 'Sessão terminada.');
        break;

    // ── CHECK (verificar sessão activa) ───────────────────
    case 'check':
        $u = getSession();
        if (!$u) fail('Sessão inválida ou expirada.', 401);
        ok($u);
        break;

    // ── ALTERAR PASSWORD ──────────────────────────────────
    case 'change_pass':
        $sess = requireAuth();
        $b    = getBody();
        $old  = $b['old_pass'] ?? '';
        $new  = $b['new_pass'] ?? '';
        if (strlen($new) < 6) fail('A nova password deve ter no mínimo 6 caracteres.');

        $u = DB::row("SELECT senha_hash FROM usuarios WHERE id=?", [$sess['id']]);
        if (!$u || !password_verify($old, $u['senha_hash'])) fail('Password atual incorreta.');

        DB::execute(
            "UPDATE usuarios SET senha_hash=? WHERE id=?",
            [password_hash($new, PASSWORD_BCRYPT), $sess['id']]
        );
        ok(null, 'Password alterada com sucesso.');
        break;

    default:
        fail('Ação desconhecida.');
}
