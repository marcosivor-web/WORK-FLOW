<?php
// ============================================================
//  AdminFlow — API Registros
// ============================================================
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

setHeaders();
requireAuth();
$action = $_GET['action'] ?? getBody()['action'] ?? '';
$b      = getBody();

switch ($action) {

    case 'list':
        $page  = max(1,(int)($_GET['page'] ?? 1));
        $limit = min((int)($_GET['limit'] ?? 20), 100);
        $offset= ($page-1)*$limit;
        $total = DB::scalar("SELECT COUNT(*) FROM registros");
        $rows  = DB::query(
            "SELECT r.*, c.nome AS categoria_nome, c.cor AS categoria_cor
             FROM registros r
             LEFT JOIN categorias c ON c.id = r.categoria_id
             ORDER BY r.criado_em DESC
             LIMIT $limit OFFSET $offset"
        );
        ok(['rows'=>$rows,'total'=>(int)$total,'page'=>$page,'limit'=>$limit]);
        break;

    case 'create':
        requireAdmin();
        $nome   = clean($b['nome'] ?? '');
        $catId  = (int)($b['categoria_id'] ?? 0) ?: null;
        $estado = clean($b['estado'] ?? 'Activo');
        $data   = $b['data'] ?? null;
        if (!$nome) fail('Nome é obrigatório.');
        $id = DB::execute(
            "INSERT INTO registros (nome, categoria_id, descricao, estado, data)
             VALUES (?,?,?,?,?)",
            [$nome, $catId, clean($b['descricao']??''), $estado, $data]
        );
        ok(['id'=>$id],'Registo criado.');
        break;

    case 'update':
        requireAdmin();
        $id    = (int)($b['id'] ?? 0);
        $catId = (int)($b['categoria_id'] ?? 0) ?: null;
        DB::execute(
            "UPDATE registros SET nome=?,categoria_id=?,descricao=?,estado=?,data=? WHERE id=?",
            [clean($b['nome']??''), $catId, clean($b['descricao']??''), clean($b['estado']??'Activo'), $b['data']??null, $id]
        );
        ok(null,'Registo atualizado.');
        break;

    case 'delete':
        requireAdmin();
        $id = (int)($b['id'] ?? $_GET['id'] ?? 0);
        DB::execute("DELETE FROM registros WHERE id=?",[$id]);
        ok(null,'Registo eliminado.');
        break;

    default:
        fail('Ação desconhecida.');
}
