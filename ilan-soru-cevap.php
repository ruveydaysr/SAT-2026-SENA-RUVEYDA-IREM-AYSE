<?php
// Yalnızca ilan-detay.php içinden, izin kontrolü yapılmış ilanla gösterilir.
if (!isset($ilan, $baglanti) || !$ilan) { return; }
mysqli_set_charset($baglanti, 'utf8mb4');
if (empty($_SESSION['qa_csrf'])) { $_SESSION['qa_csrf'] = bin2hex(random_bytes(32)); }
$qa_id = (int) $ilan['id'];
$qa_sahip = $uye_id > 0 && $uye_id === (int) $ilan['uye_id'];
$qa_acik = ($ilan['durum'] ?? '') === 'Onaylandı';
$qa_sayfa = max(1, (int) (is_scalar($_GET['qa_sayfa'] ?? 1) ? ($_GET['qa_sayfa'] ?? 1) : 1));
$qa_kayitlar = []; $qa_toplam = 0; $qa_hata = false;
try {
    $s = mysqli_prepare($baglanti, 'SELECT COUNT(*) AS adet FROM ilan_sorulari WHERE ilan_id = ?');
    mysqli_stmt_bind_param($s, 'i', $qa_id); mysqli_stmt_execute($s);
    $qa_toplam = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($s))['adet'];
    mysqli_stmt_close($s);
    $qa_son = max(1, (int) ceil($qa_toplam / 10));
    $qa_sayfa = min($qa_sayfa, $qa_son); $qa_atla = ($qa_sayfa - 1) * 10;
    $s = mysqli_prepare($baglanti, 'SELECT q.*, u.ad_soyad FROM ilan_sorulari q LEFT JOIN uyeler u ON u.id = q.uye_id WHERE q.ilan_id = ? ORDER BY q.id DESC LIMIT 10 OFFSET ?');
    mysqli_stmt_bind_param($s, 'ii', $qa_id, $qa_atla); mysqli_stmt_execute($s);
    $qa_kayitlar = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC); mysqli_stmt_close($s);
} catch (Throwable $e) { $qa_hata = true; error_log('Soru listesi: ' . $e->getMessage()); }
$qa_mesajlar = [
    'soru_ok' => ['Sorunuz yayınlandı.', 'Your question has been posted.'],
    'cevap_ok' => ['Cevabınız yayınlandı.', 'Your answer has been posted.'],
    'giris' => ['Soru sormak için giriş yapmalısınız.', 'Sign in to ask a question.'],
    'guvenlik' => ['Sayfayı yenileyip tekrar deneyin.', 'Refresh the page and try again.'],
    'gecersiz' => ['Gönderilen bilgileri kontrol edin.', 'Check the submitted information.'],
    'uzunluk' => ['Soru 3–1000, cevap 3–2000 karakter olmalıdır.', 'Questions must be 3–1000 characters; answers 3–2000.'],
    'kendi' => ['Kendi ilanınıza soru soramazsınız; gelen soruları yanıtlayabilirsiniz.', 'You can answer questions on your own listing.'],
    'yetki' => ['Bu işlemi yapmaya yetkiniz yok veya ilan artık yayında değil.', 'You cannot perform this action or the listing is no longer published.'],
    'yanitli' => ['Bu soru zaten cevaplanmış veya artık mevcut değil.', 'This question has already been answered or is no longer available.'],
    'hata' => ['İşlem tamamlanamadı. Lütfen daha sonra tekrar deneyin.', 'The action could not be completed. Please try again later.']
];
$qa_bildirim = $_SESSION['qa_bildirim'] ?? null;
if (is_array($qa_bildirim) && ($qa_bildirim['ilan_id'] ?? 0) === $qa_id) { unset($_SESSION['qa_bildirim']); } else { $qa_bildirim = null; }
?>
<style>

/* Soru-cevap alanı: sayfanın genel form kurallarından bağımsız görünüm. */
#soru-cevap { padding:28px;margin:24px 0;background:#fffdf8;border:1px solid #ded6c6;border-radius:16px;color:#284b32;scroll-margin-top:155px;box-sizing:border-box; }
#soru-cevap .qa-baslik { display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin:0 0 12px; }
#soru-cevap .qa-baslik h2 { margin:0;font-size:22px;line-height:1.4; }
#soru-cevap .qa-baslik>span { display:inline-block;margin:0;padding:6px 12px;background:#e6eddf;border-radius:20px;font-size:12px;font-weight:bold; }
#soru-cevap .qa-aciklama { margin:0 0 18px;color:#65715f;font-size:13px;line-height:1.8; }
#soru-cevap .qa-bos { margin:20px 0;padding:22px;border:1px dashed #c9d4bf;border-radius:12px;background:#f3f5ed;color:#65715f;font-size:14px;line-height:1.8; }
#soru-cevap .qa-yaz-kutusu { margin-top:20px; }
#soru-cevap .qa-yaz-kutusu>summary { display:inline-flex;align-items:center;justify-content:center;gap:18px;padding:12px 19px;background:#426b46;color:#fffdf8;border:1px solid #426b46;border-radius:9px;font-size:14px;line-height:1.4;font-weight:bold;cursor:pointer;list-style:none;user-select:none; }
#soru-cevap .qa-yaz-kutusu>summary::-webkit-details-marker { display:none; }
#soru-cevap .qa-yaz-kutusu>summary::after { content:'+';font-size:19px;font-weight:normal;line-height:1; }
#soru-cevap .qa-yaz-kutusu[open]>summary::after { content:'−'; }
#soru-cevap .qa-yaz-kutusu>summary:hover { background:#355b39; }
#soru-cevap .qa-yaz-kutusu>summary:focus-visible { outline:3px solid #a9be97;outline-offset:3px; }
#soru-cevap .qa-form { margin:16px 0 0;padding:22px;background:#f7f5ee;border:1px solid #e1d9c9;border-radius:12px; }
#soru-cevap .qa-form h3 { margin:0 0 10px;font-size:17px; }
#soru-cevap .qa-form label { display:block;margin:16px 0 8px;font-size:14px;font-weight:bold; }
#soru-cevap .qa-form textarea { display:block;box-sizing:border-box;width:100%;max-width:100%;min-height:130px;margin:0;padding:14px;border:1px solid #cbd2c2;border-radius:9px;background:white;color:#284b32;font:inherit;font-size:14px;line-height:1.7;resize:vertical; }
#soru-cevap .qa-form textarea:focus { outline:2px solid #80a86d;outline-offset:2px; }
#soru-cevap .qa-form .buton { display:inline-flex;align-items:center;justify-content:center;width:auto;min-width:145px;margin:15px 0 0;padding:12px 18px;background:#426b46;color:white;border:0;border-radius:9px;font-size:14px;font-weight:bold;font-family:inherit;cursor:pointer; }
#soru-cevap .qa-soru { padding:22px 0;border-bottom:1px solid #e1d9c9; }
#soru-cevap .qa-kunye { display:flex;align-items:center;flex-wrap:wrap;gap:10px;font-size:14px; }
#soru-cevap .qa-kunye a { color:#284b32;font-weight:bold;text-underline-offset:4px; }
#soru-cevap .qa-kunye time,#soru-cevap .qa-kunye span { font-size:12px;color:#65715f; }
#soru-cevap .qa-metin { margin:12px 0;line-height:1.85;overflow-wrap:anywhere; }
#soru-cevap .qa-cevap { margin:16px 0 0 18px;padding:18px;border-left:3px solid #80a86d;border-radius:0 10px 10px 0;background:#edf2e6; }
#soru-cevap .qa-bekliyor { margin:10px 0 0;font-size:12px;color:#7a806f; }
#soru-cevap .qa-yanit-kutusu>summary { padding:9px 14px;background:#e6eddf;color:#284b32;border-color:#c7d4bc;font-size:13px; }
#soru-cevap .qa-yanit-kutusu>summary:hover { background:#dce6d4; }
#soru-cevap .qa-bildirim { padding:14px 18px;background:#e6eddf;border:1px solid #b5c7a8;border-radius:10px;line-height:1.6; }
#soru-cevap .qa-giris a { display:inline-block;padding:12px 18px;background:#426b46;color:white;font-size:14px;font-weight:bold;border-radius:9px;text-decoration:none; }
#soru-cevap .qa-sayfalama { display:flex;align-items:center;justify-content:center;gap:15px;flex-wrap:wrap;margin:22px 0; }
#soru-cevap .qa-sayfalama a { padding:9px 12px;background:#e6eddf;border-radius:8px;color:#284b32;text-decoration:none; }
@media(max-width:600px){#soru-cevap{padding:20px;margin:20px 0;}#soru-cevap .qa-baslik h2{font-size:19px;}#soru-cevap .qa-form{padding:16px;}#soru-cevap .qa-cevap{margin-left:0;}#soru-cevap .qa-form .buton{max-width:100%;}}
</style>
<section id="soru-cevap" class="kart qa-bolum">
    <div class="qa-baslik"><h2><?= guvenli(ceviri('İlan Hakkında Sorular', 'Questions About This Listing')) ?></h2><span><?= $qa_toplam ?> <?= guvenli(ceviri('soru', 'questions')) ?></span></div>
    <p class="qa-aciklama"><?= guvenli(ceviri('Sorular ve ilan sahibinin cevapları herkes tarafından görülebilir. İsimlere tıklayarak üye profillerini inceleyebilirsiniz.', 'Questions and the listing owner’s answers are visible to everyone. Select a name to view the member’s profile.')) ?></p>
    <?php if ($qa_bildirim && isset($qa_mesajlar[$qa_bildirim['kod'] ?? ''])): $m = $qa_mesajlar[$qa_bildirim['kod']]; ?>
        <p class="qa-bildirim" role="status"><?= guvenli(ceviri($m[0], $m[1])) ?></p>
    <?php endif; ?>
    <?php if ($qa_hata): ?>
        <p><?= guvenli(ceviri('Sorular şu anda yüklenemiyor. Lütfen daha sonra tekrar deneyin.', 'Questions are temporarily unavailable. Please try again later.')) ?></p>
    <?php else: ?>
        <?php if (!$qa_kayitlar): ?><p class="qa-bos"><?= guvenli(ceviri('Henüz soru sorulmamış. İlan hakkında merak ettiklerinizi sorabilirsiniz.', 'No questions yet. Ask what you would like to know about this listing.')) ?></p><?php endif; ?>
        <?php foreach ($qa_kayitlar as $q): ?>
            <article class="qa-soru">
                <div class="qa-kunye">
                    <?php if ($q['ad_soyad'] !== null): ?><a href="uye-profili.php?id=<?= (int) $q['uye_id'] ?>"><?= guvenli($q['ad_soyad']) ?></a><?php else: ?><strong><?= guvenli(ceviri('Eski üye', 'Former member')) ?></strong><?php endif; ?>
                    <time datetime="<?= guvenli(str_replace(' ', 'T', $q['soru_tarihi'])) ?>"><?= guvenli(date('d.m.Y H:i', strtotime($q['soru_tarihi']))) ?></time>
                </div>
                <p class="qa-metin"><?= nl2br(guvenli($q['soru'])) ?></p>
                <?php if ($q['cevap'] !== null): ?>
                    <div class="qa-cevap"><div class="qa-kunye"><a href="uye-profili.php?id=<?= (int) $ilan['uye_id'] ?>"><?= guvenli($ilan['sahip_adi'] ?: ceviri('İlan sahibi', 'Listing owner')) ?></a><span><?= guvenli(ceviri('İlan sahibi', 'Listing owner')) ?></span><time><?= guvenli(date('d.m.Y H:i', strtotime($q['cevap_tarihi']))) ?></time></div><p class="qa-metin"><?= nl2br(guvenli($q['cevap'])) ?></p></div>
                <?php elseif ($qa_sahip && $qa_acik): ?>
                    <details class="qa-yaz-kutusu qa-yanit-kutusu">
                        <summary><?= guvenli(ceviri('Cevap Yaz', 'Write an Answer')) ?></summary>
                    <form class="qa-form" method="post" action="soru-cevap-islem.php">
                        <input type="hidden" name="csrf" value="<?= guvenli($_SESSION['qa_csrf']) ?>"><input type="hidden" name="ilan_id" value="<?= $qa_id ?>"><input type="hidden" name="soru_id" value="<?= (int) $q['id'] ?>"><input type="hidden" name="eylem" value="cevap">
                        <label for="qa-cevap-<?= (int) $q['id'] ?>"><?= guvenli(ceviri('Cevabınız', 'Your answer')) ?></label><textarea id="qa-cevap-<?= (int) $q['id'] ?>" name="metin" minlength="3" maxlength="2000" rows="3" required></textarea>
                        <button class="buton yesil" type="submit"><?= guvenli(ceviri('Cevapla', 'Post Answer')) ?></button>
                    </form>
                    </details>
                <?php else: ?><p class="qa-bekliyor"><?= guvenli(ceviri('İlan sahibinin cevabı bekleniyor.', 'Awaiting the listing owner’s answer.')) ?></p><?php endif; ?>
            </article>
        <?php endforeach; ?>
        <?php if ($qa_toplam > 10): ?><nav class="qa-sayfalama" aria-label="<?= guvenli(ceviri('Soru sayfaları', 'Question pages')) ?>">
            <?php if ($qa_sayfa > 1): ?><a href="ilan-detay.php?id=<?= $qa_id ?>&amp;qa_sayfa=<?= $qa_sayfa - 1 ?>#soru-cevap">← <?= guvenli(ceviri('Önceki', 'Previous')) ?></a><?php endif; ?>
            <span><?= $qa_sayfa ?> / <?= $qa_son ?></span>
            <?php if ($qa_sayfa < $qa_son): ?><a href="ilan-detay.php?id=<?= $qa_id ?>&amp;qa_sayfa=<?= $qa_sayfa + 1 ?>#soru-cevap"><?= guvenli(ceviri('Sonraki', 'Next')) ?> →</a><?php endif; ?>
        </nav><?php endif; ?>
        <?php if ($qa_acik && $uye_id > 0 && !$qa_sahip): ?>
            <details class="qa-yaz-kutusu">
                <summary><?= guvenli(ceviri('Soru Sor', 'Ask a Question')) ?></summary>
            <form class="qa-form qa-yeni" method="post" action="soru-cevap-islem.php">
                <h3><?= guvenli(ceviri('İlan Sahibine Soru Sor', 'Ask the Listing Owner')) ?></h3>
                <p class="qa-aciklama"><?= guvenli(ceviri('Sorunuz adınızla birlikte herkese açık yayınlanır. Telefon, adres veya özel bilgilerinizi yazmayın.', 'Your question will be public under your name. Do not include your phone number, address or private information.')) ?></p>
                <input type="hidden" name="csrf" value="<?= guvenli($_SESSION['qa_csrf']) ?>"><input type="hidden" name="ilan_id" value="<?= $qa_id ?>"><input type="hidden" name="eylem" value="soru">
                <label for="qa-yeni-soru"><?= guvenli(ceviri('Sorunuz', 'Your question')) ?></label><textarea id="qa-yeni-soru" name="metin" rows="4" minlength="3" maxlength="1000" required></textarea>
                <button class="buton yesil" type="submit"><?= guvenli(ceviri('Soruyu Gönder', 'Post Question')) ?></button>
            </form>
            </details>
        <?php elseif ($qa_acik && $uye_id < 1): ?><p class="qa-giris"><a href="giris.php"><?= guvenli(ceviri('Soru sormak için giriş yapın', 'Sign in to ask a question')) ?></a></p>
        <?php elseif ($qa_sahip): ?><p class="qa-aciklama"><?= guvenli(ceviri('İlanınıza gelen soruları buradan cevaplayabilirsiniz.', 'You can answer questions about your listing here.')) ?></p><?php endif; ?>
    <?php endif; ?>
</section>
