<?php
// ============================================================
//  AdminFlow — API Divisões
// ============================================================
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

setHeaders();
requireAuth();
$action = $_GET['action'] ?? getBody()['action'] ?? '';
$b      = getBody();

switch ($action) {

    case 'list':
        $rows = DB::query(
            "SELECT d.*,
                    COUNT(f.id) AS total_membros,
                    SUM(f.online) AS membros_online
             FROM divisoes d
             LEFT JOIN funcionarios f ON f.divisao_id = d.id AND f.ativo = 1
             GROUP BY d.id
             ORDER BY d.nome ASC"
        );
        ok($rows);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $d  = DB::row("SELECT * FROM divisoes WHERE id=?", [$id]);
        if (!$d) fail('Divisão não encontrada.', 404);
        $membros = DB::query(
            "SELECT f.id, f.nome, f.cargo, f.online, f.last_seen,
                    CASE WHEN f.online=1 THEN 'Online agora'
                    ELSE COALESCE(f.last_seen,'Nunca') END AS status_info
             FROM funcionarios f WHERE f.divisao_id=? AND f.ativo=1",
            [$id]
        );
        $d['membros'] = $membros;
        ok($d);
        break;

    case 'create':
        requireAdmin();
        $nome = clean($b['nome'] ?? '');
        if (!$nome) fail('Nome é obrigatório.');
        $id = DB::execute(
            "INSERT INTO divisoes (nome, descricao, responsavel, ativa) VALUES (?,?,?,?)",
            [$nome, clean($b['descricao']??''), clean($b['responsavel']??''), 1]
        );
        ok(['id' => $id], 'Divisão criada.');
        break;

    case 'update':
        requireAdmin();
        $id = (int)($b['id'] ?? 0);
        if (!$id) fail('ID inválido.');
        DB::execute(
            "UPDATE divisoes SET nome=?, descricao=?, responsavel=?, ativa=? WHERE id=?",
            [clean($b['nome']??''), clean($b['descricao']??''), clean($b['responsavel']??''), (int)($b['ativa']??1), $id]
        );
        ok(null, 'Divisão atualizada.');
        break;

    case 'toggle':
        requireAdmin();
        $id = (int)($b['id'] ?? 0);
        $d  = DB::row("SELECT ativa FROM divisoes WHERE id=?", [$id]);
        if (!$d) fail('Não encontrada.', 404);
        $nova = $d['ativa'] ? 0 : 1;
        DB::execute("UPDATE divisoes SET ativa=? WHERE id=?", [$nova, $id]);
        ok(['ativa' => $nova]);
        break;

    case 'delete':
        requireAdmin();
        $id = (int)($b['id'] ?? $_GET['id'] ?? 0);
        DB::execute("UPDATE funcionarios SET divisao_id=NULL WHERE divisao_id=?", [$id]);
        DB::execute("DELETE FROM divisoes WHERE id=?", [$id]);
        ok(null, 'Divisão eliminada.');
        break;

    default:
        fail('Ação desconhecida.');
}
