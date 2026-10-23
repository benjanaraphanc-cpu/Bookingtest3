<?php
require_once __DIR__ . '/../config.php';

// IP จริงของผู้ใช้ (ไม่เชื่อ X-Forwarded-For เพราะปลอมได้)
function client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function ip_in_cidr(string $ip, string $cidr): bool {
    [$subnet, $bits] = array_pad(explode('/', $cidr, 2), 2, null);
    $ipb = @inet_pton($ip);
    $sb  = @inet_pton($subnet);
    if ($ipb === false || $sb === false || strlen($ipb) !== strlen($sb)) return false;
    $bits  = $bits === null ? strlen($ipb) * 8 : (int)$bits;
    $bytes = intdiv($bits, 8);
    $rem   = $bits % 8;
    if ($bytes && substr($ipb, 0, $bytes) !== substr($sb, 0, $bytes)) return false;
    if ($rem) {
        $mask = (0xFF << (8 - $rem)) & 0xFF;
        if ((ord($ipb[$bytes]) & $mask) !== (ord($sb[$bytes]) & $mask)) return false;
    }
    return true;
}

function is_ku_network(): bool {
    if (!NETWORK_CHECK_ENABLED) return true;
    $ip = client_ip();
    if (DEV_ALLOW_LOCALHOST && in_array($ip, ['127.0.0.1', '::1'], true)) return true;
    foreach (KU_ALLOWED_CIDRS as $cidr) {
        if (ip_in_cidr($ip, $cidr)) return true;
    }
    return false;
}
