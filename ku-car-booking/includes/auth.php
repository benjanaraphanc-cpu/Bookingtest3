<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/network.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function url(string $p = ''): string { return BASE_URL . '/' . ltrim($p, '/'); }
function redirect(string $p): never { header('Location: ' . url($p)); exit; }

function current_user(): ?array { return $_SESSION['user'] ?? null; }
function is_admin(): bool { return (current_user()['role'] ?? '') === 'admin'; }

function require_login(): void {
    if (!current_user()) {
        flash('danger', 'กรุณาเข้าสู่ระบบก่อน');
        redirect('login.php');
    }
}
// ผู้ใช้ทั่วไปเข้าหน้า Admin ไม่ได้เด็ดขาด
function require_admin(): void {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('403 Forbidden: เฉพาะผู้ดูแลระบบเท่านั้น <a href="' . e(url('index.php')) . '">กลับหน้าแรก</a>');
    }
}

function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('CSRF token ไม่ถูกต้อง');
    }
}

// รูปรถ: ถ้าไม่มีไฟล์จริงให้ใช้ภาพสำรอง
function car_img(?string $file): string {
    if ($file && is_file(__DIR__ . '/../assets/img/cars/' . $file)) {
        return url('assets/img/cars/' . rawurlencode($file));
    }
    return url('assets/img/car-placeholder.svg');
}

const STATUS_TH  = ['pending'=>'รออนุมัติ','approved'=>'อนุมัติแล้ว','rejected'=>'ไม่อนุมัติ','cancelled'=>'ยกเลิก'];
const STATUS_CLR = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','cancelled'=>'secondary'];
