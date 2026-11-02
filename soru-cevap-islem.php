<?php
session_start();
require_once __DIR__ . '/baglanti.php';
mysqli_set_charset($baglanti, 'utf8mb4');

function qa_don($id, $kod) {
    $_SESSION['qa_bildirim'] = ['ilan_id' => $id, 'kod' => $kod];
    header('Location: ilan-detay.php?id=' . $id . '#soru-cevap', true, 303);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}
$id = filter_var($_POST['ilan_id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) { http_response_code(400); exit('Geçersiz ilan.'); }
$uye = (int) ($_SESSION['uye_id'] ?? 0);
if ($uye < 1) { qa_don($id, 'giris'); }
$token = $_POST['csrf'] ?? null;
if (!is_string($token) || empty($_SESSION['qa_csrf']) || !hash_equals($_SESSION['qa_csrf'], $token)) {
    qa_don($id, 'guvenlik');
}
$eylem = $_POST['eylem'] ?? '';
$metin = $_POST['metin'] ?? null;
if (!is_string($metin) || !in_array($eylem, ['soru', 'cevap'], true)) { qa_don($id, 'gecersiz'); }
$metin = trim($metin);
$limit = $eylem === 'soru' ? 1000 : 2000;
$uzunluk = preg_match_all('/./us', $metin);
if ($uzunluk === false || $uzunluk < 3 || $uzunluk > $limit) { qa_don($id, 'uzunluk'); }
try {
    // İlan ve üye hâlâ mevcut mu? Gizli/reddedilmiş ilanlara yazılamaz.
    $s = mysqli_prepare($baglanti, "SELECT i.uye_id FROM ilanlar i INNER JOIN uyeler u ON u.id = ? WHERE i.id = ? AND i.durum = 'Onaylandı' LIMIT 1");
    mysqli_stmt_bind_param($s, 'ii', $uye, $id);
    mysqli_stmt_execute($s);
    $ilan = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    mysqli_stmt_close($s);
    if (!$ilan) { qa_don($id, 'yetki'); }
    if ($eylem === 'soru') {
        if ($uye === (int) $ilan['uye_id']) { qa_don($id, 'kendi'); }
        $s = mysqli_prepare($baglanti, 'INSERT INTO ilan_sorulari (ilan_id, uye_id, soru) VALUES (?, ?, ?)');
        mysqli_stmt_bind_param($s, 'iis', $id, $uye, $metin);
        mysqli_stmt_execute($s);
        mysqli_stmt_close($s);
        qa_don($id, 'soru_ok');
    }
    // Cevap yetkisi yalnızca ilan sahibine aittir; form verisine güvenilmez.
    if ($uye !== (int) $ilan['uye_id']) { qa_don($id, 'yetki'); }
    $soru_id = filter_var($_POST['soru_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$soru_id || $soru_id < 1) { qa_don($id, 'gecersiz'); }
    $s = mysqli_prepare($baglanti, 'UPDATE ilan_sorulari SET cevap = ?, cevap_tarihi = CURRENT_TIMESTAMP WHERE id = ? AND ilan_id = ? AND cevap IS NULL');
    mysqli_stmt_bind_param($s, 'sii', $metin, $soru_id, $id);
    mysqli_stmt_execute($s);
    $degisti = mysqli_stmt_affected_rows($s);
    mysqli_stmt_close($s);
    qa_don($id, $degisti === 1 ? 'cevap_ok' : 'yanitli');
} catch (Throwable $e) {
    error_log('Ilan soru-cevap: ' . $e->getMessage());
    qa_don($id, 'hata');
}
