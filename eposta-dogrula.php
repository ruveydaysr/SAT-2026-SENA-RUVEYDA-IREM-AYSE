<?php
session_start();
header('Cache-Control: no-store');

$geri = 'admin/admin-giris.php';
$istek = $_SESSION['aris_kod']['admin'] ?? null;

if (!$istek || $istek['bitis'] <= time() || $istek['deneme'] >= 5) {
    unset($_SESSION['aris_kod']['admin']);
    header('Location: ' . $geri);
    exit;
}

$en = $istek['en'];
$metin = static fn (string $tr, string $enMetin): string => $en ? $enMetin : $tr;
$hata = '';
$bitti = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $kod = $_POST['kod'] ?? '';
    if (is_string($token) && hash_equals($istek['token'], $token)) {
        $_SESSION['aris_kod']['admin']['deneme']++;

        if (is_string($kod) && preg_match('/^[0-9]{6}$/D', $kod)
            && password_verify($kod, $istek['hash'])) {
            unset($_SESSION['aris_kod']['admin']);
            session_regenerate_id(true);
            $_SESSION['admin'] = $istek['kullanici_adi'];
            header('Location: admin/admin-genel-bakis.php');
            exit;
        } elseif ($_SESSION['aris_kod']['admin']['deneme'] >= 5) {
            unset($_SESSION['aris_kod']['admin']);
            $hata = $metin('Deneme hakkın doldu. Yeni kod istemek için geri dön.',
                'No attempts left. Go back to request a new code.');
            $bitti = true;
        } else {
            $hata = $metin('Kod hatalı. E-postadaki kodu kontrol et.', 'Incorrect code. Check your email.');
        }
    }
}
?>
<!doctype html>
<html lang="<?= $en ? 'en' : 'tr' ?>">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $metin('E-posta doğrulama', 'Email verification') ?> | Aris Pati Yuvası</title>
<style>
    body { margin:0; min-height:100vh; display:grid; place-items:center; background:#f5f0e6; color:#284b32; font:16px Arial,sans-serif; }
    main { box-sizing:border-box; width:min(420px, calc(100% - 32px)); padding:24px; border:1px solid #ded6c6; border-radius:16px; background:#fffdf8; }
    h1 { margin-top:0; font-size:24px; }
    form { display:grid; gap:10px; }
    input, button { box-sizing:border-box; min-height:44px; border:1px solid #b2c0ae; border-radius:8px; padding:10px 12px; font:inherit; }
    button { background:#426b46; color:white; cursor:pointer; }
    [role=alert] { color:#9c342b; }
    a { color:#284b32; }
</style>
<main>
    <h1><?= $metin('E-posta doğrulama', 'Email verification') ?></h1>
    <?php if ($hata !== ''): ?><p role="alert"><?= $hata ?></p><?php endif; ?>
    <?php if (!$bitti): ?>
        <p><?= $metin('E-postana gelen 6 haneli kodu gir. Kod 10 dakika geçerli.',
                'Enter the 6-digit code from your email. The code expires after 10 minutes.') ?></p>
        <form method="post">
            <input type="hidden" name="token" value="<?= $istek['token'] ?>">
            <label for="kod"><?= $metin('Doğrulama kodu', 'Verification code') ?></label>
            <input id="kod" name="kod" inputmode="numeric" autocomplete="one-time-code"
                   maxlength="6" pattern="[0-9]{6}" required>
            <button><?= $metin('Doğrula', 'Verify') ?></button>
        </form>
    <?php endif; ?>
    <p><a href="<?= $geri ?>"><?= $metin('Geri dön', 'Go back') ?></a></p>
</main>
</html>
