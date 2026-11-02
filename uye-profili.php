<?php
session_start();
require_once __DIR__ . '/site-ust-ayar.php';
require_once __DIR__ . '/baglanti.php';
mysqli_set_charset($baglanti, 'utf8mb4');
function up_h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
function up_t($tr, $en) { return ($_SESSION['dil'] ?? 'tr') === 'en' ? $en : $tr; }
function up_foto($foto) {
    $foto = trim((string) $foto); $ad = basename($foto);
    if (preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$/i', $ad) && is_file(__DIR__ . '/uploads/' . $ad)) { return 'uploads/' . $ad; }
    if (preg_match('~^images/hayvanlar/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$~i', $foto) && is_file(__DIR__ . '/' . $foto)) { return $foto; }
    return '';
}
$profil_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$profil = null; $ilanlar = []; $adet = 0; $son = 1;
$sayfa = max(1, (int) (is_scalar($_GET['sayfa'] ?? 1) ? ($_GET['sayfa'] ?? 1) : 1));
if ($profil_id && $profil_id > 0) {
    // Yalnızca herkese açık profil verileri: e-posta/telefon/şifre alınmaz.
    $s = mysqli_prepare($baglanti, 'SELECT id, ad_soyad FROM uyeler WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($s, 'i', $profil_id); mysqli_stmt_execute($s);
    $profil = mysqli_fetch_assoc(mysqli_stmt_get_result($s)); mysqli_stmt_close($s);
}
if ($profil) {
    $s = mysqli_prepare($baglanti, "SELECT COUNT(*) AS adet FROM ilanlar WHERE uye_id = ? AND durum = 'Onaylandı'");
    mysqli_stmt_bind_param($s, 'i', $profil_id); mysqli_stmt_execute($s);
    $adet = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($s))['adet']; mysqli_stmt_close($s);
    $son = max(1, (int) ceil($adet / 12)); $sayfa = min($sayfa, $son); $atla = ($sayfa - 1) * 12;
    $s = mysqli_prepare($baglanti, "SELECT id, hayvan_adi, ilan_basligi, tur, sehir, fotograf FROM ilanlar WHERE uye_id = ? AND durum = 'Onaylandı' ORDER BY id DESC LIMIT 12 OFFSET ?");
    mysqli_stmt_bind_param($s, 'ii', $profil_id, $atla); mysqli_stmt_execute($s);
    $ilanlar = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC); mysqli_stmt_close($s);
} else { http_response_code(404); }
?>
<!DOCTYPE html>
<html lang="<?= up_h($_SESSION['dil'] ?? 'tr') ?>">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= up_h($profil ? $profil['ad_soyad'] . ' | Aris Pati Yuvası' : up_t('Üye Bulunamadı', 'Member Not Found')) ?></title>
<link rel="stylesheet" href="site-ust.css?v=20261102-1"><link rel="stylesheet" href="soru-cevap.css?v=1">
</head>
<body class="up-sayfa">
<?php require __DIR__ . '/site-ust.php'; ?>
<main class="up-alan">
<?php if ($profil): ?>
    <section class="up-baslik"><span class="up-etiket"><?= up_h(up_t('ÜYE PROFİLİ', 'MEMBER PROFILE')) ?></span><h1><?= up_h($profil['ad_soyad']) ?></h1><p><?= $adet ?> <?= up_h(up_t('onaylı ilan', 'approved listings')) ?></p></section>
    <h2><?= up_h(up_t('Üyenin İlanları', 'Member’s Listings')) ?></h2>
    <?php if (!$ilanlar): ?><p class="up-bos"><?= up_h(up_t('Bu üyenin yayında olan ilanı bulunmuyor.', 'This member has no published listings.')) ?></p><?php endif; ?>
    <div class="up-grid">
    <?php foreach ($ilanlar as $kayit): $foto = up_foto($kayit['fotograf']); ?>
        <article class="up-kart"><a class="up-kart-link" href="ilan-detay.php?id=<?= (int) $kayit['id'] ?>">
            <?php if ($foto): ?><img src="<?= up_h($foto) ?>" alt="<?= up_h($kayit['hayvan_adi']) ?>" loading="lazy" width="360" height="220"><?php else: ?><div class="up-foto-yok"><?= up_h(up_t('Fotoğraf eklenmemiş', 'No photo added')) ?></div><?php endif; ?>
            <div class="up-kart-icerik"><h3><?= up_h($kayit['hayvan_adi']) ?></h3><p><?= up_h($kayit['ilan_basligi']) ?></p><span><?= up_h($kayit['sehir']) ?></span><strong><?= up_h(up_t('İlanı İncele', 'View Listing')) ?> →</strong></div>
        </a></article>
    <?php endforeach; ?>
    </div>
    <?php if ($son > 1): ?><nav class="qa-sayfalama" aria-label="<?= up_h(up_t('İlan sayfaları', 'Listing pages')) ?>">
        <?php if ($sayfa > 1): ?><a href="uye-profili.php?id=<?= (int) $profil_id ?>&amp;sayfa=<?= $sayfa - 1 ?>">← <?= up_h(up_t('Önceki', 'Previous')) ?></a><?php endif; ?><span><?= $sayfa ?> / <?= $son ?></span>
        <?php if ($sayfa < $son): ?><a href="uye-profili.php?id=<?= (int) $profil_id ?>&amp;sayfa=<?= $sayfa + 1 ?>"><?= up_h(up_t('Sonraki', 'Next')) ?> →</a><?php endif; ?>
    </nav><?php endif; ?>
<?php else: ?><section class="up-baslik"><h1><?= up_h(up_t('Üye Bulunamadı', 'Member Not Found')) ?></h1><p><?= up_h(up_t('Bu profil artık mevcut olmayabilir.', 'This profile may no longer exist.')) ?></p></section><?php endif; ?>
<a class="up-don" href="ilanlar.php">← <?= up_h(up_t('İlanlara Dön', 'Back to Listings')) ?></a>
</main>
<footer class="up-alt">© <?= date('Y') ?> Aris Pati Yuvası</footer>
</body></html>
