<?php
// ============================================================
//  AdminFlow — API Tickets de Suporte
// ============================================================
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

setHeaders();
$sess   = requireAuth();
$action = $_GET['action'] ?? getBody()['action'] ?? '';
$b      = getBody();

switch ($action) {

    case 'list':
        // Admin vê todos; funcionário vê só os seus
        if ($sess['role'] === 'admin') {
            $rows = DB::query(
                "SELECT t.*,
                        fa.nome AS atribuido_nome,
                        fc.nome AS criado_por_nome
                 FROM tickets t
                 LEFT JOIN funcionarios fa ON fa.id = t.atribuido_id
                 LEFT JOIN funcionarios fc ON fc.id = t.criado_por_id
                 ORDER BY t.criado_em DESC"
            );
        } else {
            $rows = DB::query(
                "SELECT t.*,
                        fa.nome AS atribuido_nome,
                        fc.nome AS criado_por_nome
                 FROM tickets t
                 LEFT JOIN funcionarios fa ON fa.id = t.atribuido_id
                 LEFT JOIN funcionarios fc ON fc.id = t.criado_por_id
                 WHERE t.criado_por_id = ?
                 ORDER BY t.criado_em DESC",
                [$sess['func_id']]
            );
        }
        ok($rows);
        break;

    case 'create':
        $assunto   = clean($b['assunto']      ?? '');
        $descricao = clean($b['descricao']    ?? '');
        $prioridade= clean($b['prioridade']   ?? 'Média');
        $atrib     = (int)($b['atribuido_id'] ?? 0) ?: null;

        // Funcionário → criado_por é ele próprio, não pode atribuir a outro
        if ($sess['role'] === 'func') {
            $criadoPor = (int)$sess['func_id'];
            $atrib     = $criadoPor; // ticket fica em seu nome
        } else {
            $criadoPor = null;
        }

        if (!$assunto) fail('Assunto é obrigatório.');

        $id = DB::execute(
            "INSERT INTO tickets (assunto, descricao, prioridade, atribuido_id, criado_por_id)
             VALUES (?,?,?,?,?)",
            [$assunto, $descricao, $prioridade, $atrib, $criadoPor]
        );
        ok(['id' => $id], 'Ticket criado.');
        break;

    case 'resolve':
        requireAdmin();
        $id = (int)($b['id'] ?? 0);
        DB::execute(
            "UPDATE tickets SET estado='Resolvido', resolvido_em=NOW() WHERE id=?", [$id]
        );
        ok(null, 'Ticket resolvido.');
        break;

    case 'reopen':
        requireAdmin();
        $id = (int)($b['id'] ?? 0);
        DB::execute(
            "UPDATE tickets SET estado='Pendente', resolvido_em=NULL WHERE id=?", [$id]
        );
        ok(null, 'Ticket reaberto.');
        break;

    case 'delete':
        requireAdmin();
        $id = (int)($b['id'] ?? $_GET['id'] ?? 0);
        DB::execute("DELETE FROM tickets WHERE id=?", [$id]);
        ok(null, 'Ticket eliminado.');
        break;

    default:
        fail('Ação desconhecida.');
}
