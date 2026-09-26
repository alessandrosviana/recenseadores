<?php
/**
 * includes/security.php — Funcoes de seguranca reutilizaveis
 * CSFR, validacao de CPF, sanitizacao XSS, rate limiting, log de erros
 */

function log_error(string $msg, string $file, int $line): void
{
    $logPath = __DIR__ . '/../scratch/error.log';
    $entry = date('Y-m-d H:i:s') . " [ERROR] [$file:$line] $msg\n";
    @file_put_contents($logPath, $entry, FILE_APPEND);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_verify(): void
{
    $token = $_POST['csrf'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        log_error('CSRF token invalido', $_SERVER['SCRIPT_NAME'] ?? 'unknown', 0);
        exit('Erro de validacao. Recarregue a pagina e tente novamente.');
    }
}

function validar_cpf(string $cpf): bool
{
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) !== 11) {
        return false;
    }
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) {
            $soma += (int)$cpf[$i] * (($t + 1) - $i);
        }
        $digito = ((10 * $soma) % 11) % 10;
        if ((int)$cpf[$t] !== $digito) {
            return false;
        }
    }
    return true;
}

function sanitize_html(string $html): string
{
    $allowed_tags = '<p><strong><em><b><i><ul><ol><li><br><span>';
    $html = strip_tags($html, $allowed_tags);
    $html = preg_replace('/<(\w+)\s+[^>]*>/i', '<$1>', $html);
    $html = preg_replace('/on\w+\s*=\s*"[^"]*"/i', '', $html);
    $html = preg_replace('/on\w+\s*=\s*\'[^\']*\'/i', '', $html);
    $html = preg_replace('/javascript:/i', '', $html);
    return $html;
}

function check_rate_limit(PDO $pdo, string $key, int $max = 5, int $window_min = 15): bool
{
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as cnt FROM rate_limit_attempts
             WHERE limit_key = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)"
        );
        $stmt->execute([$key, $window_min]);
        $row = $stmt->fetch();
        return ($row && (int)$row['cnt'] < $max);
    } catch (PDOException $e) {
        return true;
    }
}

function record_failed_attempt(PDO $pdo, string $key): void
{
    try {
        $stmt = $pdo->prepare("INSERT INTO rate_limit_attempts (limit_key) VALUES (?)");
        $stmt->execute([$key]);
    } catch (PDOException $e) {
    }
}

function reset_rate_limit(PDO $pdo, string $key): void
{
    try {
        $stmt = $pdo->prepare("DELETE FROM rate_limit_attempts WHERE limit_key = ?");
        $stmt->execute([$key]);
    } catch (PDOException $e) {
    }
}

function validate_password_strength(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'A senha deve ter no minimo 8 caracteres.';
    }
    if (!preg_match('/[a-zA-Z]/', $password)) {
        return 'A senha deve conter pelo menos 1 letra.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'A senha deve conter pelo menos 1 numero.';
    }
    return null;
}

function validate_upload_mime(string $tmp_name, array $allowed_mimes): bool
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmp_name);
    finfo_close($finfo);
    return in_array($mime, $allowed_mimes);
}
?>
