<?php
// ============================================================
//  AdminFlow — Envio de Email
//  Usa PHPMailer (pasta vendor/) ou mail() nativo como fallback
//  POST /email.php  body: { action, func_id, assunto, mensagem }
// ============================================================
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

setHeaders();
$sess   = requireAuth();
$action = $_GET['action'] ?? getBody()['action'] ?? 'send';
$b      = getBody();

switch ($action) {

    // ── ENVIAR EMAIL A UM FUNCIONÁRIO ─────────────────────
    case 'send':
        $fid      = (int)($b['func_id'] ?? 0);
        $assunto  = clean($b['assunto']  ?? '');
        $mensagem = clean($b['mensagem'] ?? '');

        if (!$fid || !$assunto || !$mensagem) {
            fail('func_id, assunto e mensagem são obrigatórios.');
        }

        $f = DB::row(
            "SELECT nome, email FROM funcionarios WHERE id=? AND ativo=1", [$fid]
        );
        if (!$f) fail('Funcionário não encontrado.', 404);
        if (!$f['email']) fail("O funcionário {$f['nome']} não tem email registado.");

        $enviado = enviarEmail(
            $f['email'],
            $f['nome'],
            $assunto,
            $mensagem,
            $sess
        );

        if ($enviado) {
            ok(null, "Email enviado para {$f['nome']} ({$f['email']}).");
        } else {
            fail('Erro ao enviar email. Verifique as configurações SMTP em config.php.');
        }
        break;

    default:
        fail('Ação desconhecida.');
}

// ── Função de envio ───────────────────────────────────────
function enviarEmail(
    string $toEmail,
    string $toNome,
    string $assunto,
    string $mensagem,
    array  $remetente
): bool {

    // ── Tenta PHPMailer se estiver instalado ──────────────
    // Para instalar: composer require phpmailer/phpmailer
    $phpmailerPath = __DIR__ . '/vendor/autoload.php';

    if (file_exists($phpmailerPath)) {
        require_once $phpmailerPath;
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($toEmail, $toNome);

            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body    = emailTemplate($toNome, $assunto, $mensagem, $remetente);
            $mail->AltBody = strip_tags($mensagem);

            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log('PHPMailer: ' . $e->getMessage());
            return false;
        }
    }

    // ── Fallback: mail() nativo ───────────────────────────
    // NOTA: Funciona apenas se o servidor tiver sendmail configurado
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";

    return mail($toEmail, $assunto, emailTemplate($toNome, $assunto, $mensagem, $remetente), $headers);
}

// ── Template HTML do email ────────────────────────────────
function emailTemplate(
    string $toNome,
    string $assunto,
    string $mensagem,
    array  $remetente
): string {
    $from = $remetente['func_nome'] ?? 'Administrador';
    $dt   = date('d/m/Y H:i');
    return "
    <!DOCTYPE html>
    <html lang='pt'>
    <body style='font-family:Arial,sans-serif;background:#f0f0f0;padding:20px'>
    <div style='max-width:560px;margin:auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.1)'>
      <div style='background:linear-gradient(135deg,#7C6FFF,#9B8FFF);padding:24px 32px'>
        <h1 style='color:#fff;margin:0;font-size:22px'>AdminFlow</h1>
        <p style='color:rgba(255,255,255,.8);margin:4px 0 0;font-size:13px'>Sistema de Gestão Interna</p>
      </div>
      <div style='padding:32px'>
        <p style='color:#444;margin:0 0 16px'>Olá, <b>{$toNome}</b></p>
        <div style='background:#f8f8ff;border-left:4px solid #7C6FFF;border-radius:0 8px 8px 0;padding:16px 20px;color:#333;line-height:1.6;font-size:14px'>
          " . nl2br(htmlspecialchars($mensagem)) . "
        </div>
        <p style='color:#888;font-size:12px;margin:24px 0 0'>
          Enviado por <b>{$from}</b> em {$dt}
        </p>
      </div>
      <div style='background:#f5f5f5;padding:16px 32px;text-align:center;font-size:11px;color:#aaa'>
        AdminFlow — Sistema de Gestão Interna
      </div>
    </div>
    </body></html>";
}
