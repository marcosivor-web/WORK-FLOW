<?php
// ============================================================
//  AdminFlow — Configuração Central
//  EDITE ESTE FICHEIRO antes de usar o sistema
// ============================================================

// ── BASE DE DADOS ─────────────────────────────────────────
define('DB_HOST', 'https://workflowpap.netlify.app/');
define('DB_USER', 'root');          // ← utilizador MySQL (XAMPP padrão: root)
define('DB_PASS', '');              // ← senha MySQL (XAMPP padrão: vazio)
define('DB_NAME', 'adminflow');     // ← nome da base de dados

// ── EMAIL / SMTP ──────────────────────────────────────────
// Para usar Gmail: ative "Senhas de App" na conta Google
// https://myaccount.google.com/apppasswords
define('SMTP_HOST',      'smtp.gmail.com');
define('SMTP_PORT',      'https://workflowpap.netlify.app/');
define('SMTP_USER',      'marcosivor@gmail.com');   // ← email remetente
define('SMTP_PASS',      'Ivor232405.');  // ← senha de app (16 chars)
define('SMTP_FROM',      'marcosivor@gmail.com');
define('SMTP_FROM_NAME', 'AdminFlow Sistema');

// ── APP ───────────────────────────────────────────────────
define('APP_URL',            'https://workflowpap.netlify.app/');
define('SESSION_LIFETIME',   7200);   // segundos (2h)
define('SESSION_NAME',       'adminflow_sess');

// ── SEGURANÇA ────────────────────────────────────────────
// Altere esta chave para uma string aleatória única
define('APP_SECRET', 'adminflow_security2007');

// Origens permitidas para CORS (separadas por vírgula)
// Em produção coloque o domínio real: 'https://seusite.pt'
define('CORS_ORIGIN', '*');
