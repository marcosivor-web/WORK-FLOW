<?php
// ============================================================
//  AdminFlow — Funções de apoio globais
// ============================================================
require_once __DIR__ . '/config.php';

// ── Cabeçalhos padrão ────────────────────────────────────
function setHeaders(): void {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: ' . CORS_ORIGIN);
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200); exit;
    }
}

// ── Resposta JSON ────────────────────────────────────────
function ok(mixed $data = null, string $msg = 'OK'): never {
    echo json_encode(['ok' => true, 'msg' => $msg, 'data' => $data]);
    exit;
}

function fail(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'msg' => $msg, 'data' => null]);
    exit;
}

// ── Sessão ────────────────────────────────────────────────
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

function getSession(): ?array {
    startSession();
    if (!isset($_SESSION['user'])) return null;
    if (time() > ($_SESSION['expires'] ?? 0)) {
        session_destroy(); return null;
    }
    return $_SESSION['user'];
}

function requireAuth(): array {
    $u = getSession();
    if (!$u) fail('Não autenticado.', 401);
    return $u;
}

function requireAdmin(): array {
    $u = requireAuth();
    if ($u['role'] !== 'admin') fail('Acesso negado — apenas administradores.', 403);
    return $u;
}

// ── Body JSON do request ─────────────────────────────────
function getBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

// ── Sanitização ──────────────────────────────────────────
function clean(mixed $v): string {
    return htmlspecialchars(trim((string)($v ?? '')), ENT_QUOTES, 'UTF-8');
}

// ── Formatar tempo "há X tempo" ──────────────────────────
function timeAgo(?string $dt): string {
    if (!$dt) return 'Nunca';
    $diff = time() - strtotime($dt);
    if ($diff < 60)     return 'agora mesmo';
    if ($diff < 3600)   return floor($diff/60) . ' min atrás';
    if ($diff < 86400)  return floor($diff/3600) . 'h atrás';
    if ($diff < 604800) return floor($diff/86400) . 'd atrás';
    return date('d/m/Y H:i', strtotime($dt));
}
