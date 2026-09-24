<?php
/**
 * _security.php — helper keamanan PASAR
 * Diadaptasi dari komenk/_security.php
 */

// ── Rate limiting — hanya jika temp dir writable ─────────────────
function check_rate_limit(string $endpoint, int $max = 30, int $window = 60): void {
    $tmp = sys_get_temp_dir();
    if (!is_writable($tmp)) return;

    $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = $tmp . '/pasar_rl_' . md5($endpoint . $ip);
    $now = time();

    $data = ['count' => 0, 'start' => $now];
    if (file_exists($key)) {
        $raw = @json_decode(@file_get_contents($key), true);
        if (is_array($raw) && ($now - $raw['start']) < $window) {
            $data = $raw;
        }
    }

    $data['count']++;
    @file_put_contents($key, json_encode($data), LOCK_EX);

    if ($data['count'] > $max) {
        http_response_code(429);
        header('Retry-After: ' . ($window - ($now - $data['start'])));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Terlalu banyak permintaan. Coba lagi sebentar.']);
        exit;
    }
}

// ── Helper: safe error response ──────────────────────────────────
function api_error(int $code, string $msg, ?Throwable $e = null): never {
    if ($e) {
        error_log('[PASAR] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $msg]);
    exit;
}
