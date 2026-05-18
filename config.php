<?php
// ============================================================
//  AdminFlow — Configuração Central
// ============================================================

// ── BASE DE DADOS ─────────────────────────────────────────S
define('DB_HOST', 'sql201.infinityfree.com');
define('DB_USER', 'if0_41950429');          // ← utilizador MySQL 
define('DB_PASS', 'Ivor232405');              // ← senha MySQL 
define('DB_NAME', 'if0_41950429_schema');     // ← nome da base de dados

// ── EMAIL / SMTP ──────────────────────────────────────────
// Para usar Gmail: ative "Senhas de App" na conta Google
// https://myaccount.google.com/apppasswords
define('SMTP_HOST',      'smtp.gmail.com');
define('SMTP_PORT',      'http://localhost/adminflow/');
define('SMTP_USER',      'marcosivor@gmail.com');   // ← email remetente
define('SMTP_PASS',      'Ivor232405.');  // ← senha de app (16 chars)
define('SMTP_FROM',      'marcosivor@gmail.com');
define('SMTP_FROM_NAME', 'AdminFlow Sistema');

// ── APP ───────────────────────────────────────────────────
define('APP_URL',            'http://localhost/adminflow');
define('SESSION_LIFETIME',   7200);   // segundos (2h)
define('SESSION_NAME',       'adminflow_sess');

// ── SEGURANÇA ────────────────────────────────────────────
// Altere esta chave para uma string aleatória única
define('APP_SECRET', 'adminflow_security2007');

// Origens permitidas para CORS (separadas por vírgula)
// Em produção coloque o domínio real: 'https://seusite.pt'
define('CORS_ORIGIN', '*');
