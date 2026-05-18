<?php
// ============================================================
//  AdminFlow — API Categorias (admin cria → vai para BD)
// ============================================================
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

setHeaders();
requireAuth();
$action = $_GET['action'] ?? getBody()['action'] ?? '';
$b      = getBody();

switch ($action) {

    case 'list':
        ok(DB::query("SELECT * FROM categorias ORDER BY nome ASC"));
        break;

    case 'create':
        requireAdmin();
        $nome = clean($b['nome'] ?? '');
        $cor  = clean($b['cor']  ?? '#7C6FFF');
        if (!$nome) fail('Nome é obrigatório.');
        $exists = DB::scalar("SELECT id FROM categorias WHERE nome=?", [$nome]);
        if ($exists) fail("Categoria '$nome' já existe.");
        $id = DB::execute(
            "INSERT INTO categorias (nome, cor) VALUES (?,?)", [$nome, $cor]
        );
        ok(['id' => $id, 'nome' => $nome, 'cor' => $cor], 'Categoria criada.');
        break;

    case 'update':
        requireAdmin();
        $id   = (int)($b['id'] ?? 0);
        $nome = clean($b['nome'] ?? '');
        $cor  = clean($b['cor']  ?? '#7C6FFF');
        if (!$id || !$nome) fail('ID e nome são obrigatórios.');
        DB::execute("UPDATE categorias SET nome=?, cor=? WHERE id=?", [$nome, $cor, $id]);
        ok(null, 'Categoria atualizada.');
        break;

    case 'delete':
        requireAdmin();
        $id = (int)($b['id'] ?? $_GET['id'] ?? 0);
        // Registros que usam esta categoria ficam com categoria NULL
        DB::execute("UPDATE registros SET categoria_id=NULL WHERE categoria_id=?", [$id]);
        DB::execute("DELETE FROM categorias WHERE id=?", [$id]);
        ok(null, 'Categoria eliminada.');
        break;

    default:
        fail('Ação desconhecida.');
}
