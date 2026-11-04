<?php
require_once __DIR__ . '/site-ust-ayar.php';

function aris_ust_goster($db)
{
    $page = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    $lang = ($_SESSION['dil'] ?? 'tr') === 'en' ? 'en' : 'tr';
    $member = (int) ($_SESSION['uye_id'] ?? 0);
    $unread = false;
    $categories = [
        'Kedi' => ['Cat', 'kedi.png'],
        'Köpek' => ['Dog', 'kopek.png'],
        'Kuş' => ['Bird', 'kus.png'],
        'Balık' => ['Fish', 'balik.png'],
        'Tavşan' => ['Rabbit', 'tavsan.png'],
        'Kemirgen' => ['Rodent', 'kemirgen.png'],
        'Sürüngen' => ['Reptile', 'surungen.png'],
        'Diğer' => ['Other', 'diger.png']
    ];
    $categoryImages = [];
    if ($db) {
        try {
            $result = mysqli_query($db, 'SELECT icerik_json FROM anasayfa_bolumler WHERE id = 1');
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                $sections = json_decode((string) ($row['icerik_json'] ?? ''), true);
                $categoryImages = is_array($sections) ? ($sections['kategoriler']['ogeler'] ?? []) : [];
                mysqli_free_result($result);
            }
        } catch (mysqli_sql_exception $e) {
            // Bölüm tablosu yoksa varsayılan kategori görsellerini kullan.
        }
        if ($member > 0) {
            try {
                $stmt = mysqli_prepare($db, 'SELECT id FROM ilan_mesajlari WHERE alici_id = ? AND okundu = 0 LIMIT 1');
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'i', $member);
                    mysqli_stmt_execute($stmt);
                    $unread = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
                    mysqli_stmt_close($stmt);
                }
            } catch (mysqli_sql_exception $e) {
                // Mesaj tablosu kullanılamazsa menü yine görüntülenir.
            }
        }
    }
    $links = [
        ['index.php', 'Ana Sayfa', 'Home'],
        ['ilanlar.php', 'İlanlar', 'Listings'],
        ['categories', 'Hayvan Kategorileri', 'Animal Categories'],
        ['ilan-ver.php', 'İlan Ver', 'Post a Listing'],
        ['blog.php', 'Blog', 'Blog'],
        ['hakkimizda.php', 'Hakkımızda', 'About Us'],
        ['iletisim.php', 'İletişim', 'Contact']
    ];
    if ($member > 0) {
        $links[] = ['mesajlar.php', 'Mesajlarım', 'Messages'];
        $links[] = ['profil.php', 'Profilim', 'My Profile'];
        $links[] = ['cikis.php', 'Çıkış Yap', 'Sign Out'];
    } else {
        $links[] = ['giris.php', 'Giriş Yap', 'Sign In'];
        $links[] = ['kayit.php', 'Kayıt Ol', 'Register'];
    }
    ?>
        <header id="aris-site-header" class="aris-site-header">
        <a class="aris-brand" href="index.php">
            <img src="images/aris-logoo.png" width="54" height="54"
                 alt="<?= aris_ust_h(aris_ust_t('Aris Pati Yuvası logosu', 'Aris Pati Yuvası logo')) ?>">
            <span class="aris-brand-text">
                <strong>Aris Pati Yuvası</strong>
                <small><?= aris_ust_h(aris_ust_t('Her can dostuna bir yuva', 'A home for every companion')) ?></small>
            </span>
        </a>
        <div class="aris-header-right">
            <nav class="aris-language" aria-label="<?= aris_ust_h(aris_ust_t('Dil seçimi', 'Language selection')) ?>">
                <?php foreach (['tr' => 'TR', 'en' => 'EN'] as $code => $label): ?>
                    <a href="<?= aris_ust_h(aris_ust_dil_linki($code)) ?>"
                       lang="<?= $code ?>" hreflang="<?= $code ?>"
                       aria-label="<?= $code === 'tr' ? 'Türkçe' : 'English' ?>"
                       <?= $lang === $code ? 'aria-current="true"' : '' ?>><?= $label ?></a>
                <?php endforeach; ?>
            </nav>
            <nav class="aris-navigation" aria-label="<?= aris_ust_h(aris_ust_t('Ana menü', 'Main navigation')) ?>">
                <?php foreach ($links as [$url, $tr, $en]): ?>
                    <?php if ($url === 'categories'): ?>
                        <details class="aris-categories">
                            <summary><?= aris_ust_h(aris_ust_t($tr, $en)) ?> <span aria-hidden="true">▾</span></summary>
                            <div class="aris-category-list">
                                <?php foreach ($categories as $name => [$english, $file]): ?>
                                    <?php
                                    $img = $categoryImages[$name]['gorsel'] ?? '';
                                    if (!is_string($img)
                                        || !preg_match('~^(images/[a-zA-Z0-9_-]+\.(?:jpg|jpeg|png|webp)|uploads/kategori_[a-f0-9]{32}\.(?:jpg|png|webp))$~i', $img)
                                        || !is_file(__DIR__ . '/' . $img)) {
                                        $img = 'images/' . $file;
                                    }
                                    ?>
                                    <a href="ilanlar.php?tur=<?= rawurlencode($name) ?>">
                                        <img src="<?= aris_ust_h($img) ?>" alt="" width="32" height="32">
                                        <span><?= aris_ust_h(aris_ust_t($name, $english)) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php else: ?>
                        <a href="<?= aris_ust_h($url) ?>"
                           <?= $page === $url ? 'aria-current="page"' : '' ?>
                           <?php if ($url === 'cikis.php'): ?>
                           onclick="return confirm(<?= aris_ust_h(json_encode(aris_ust_t('Çıkış yapmak istediğinize emin misiniz?', 'Are you sure you want to sign out?'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>);"
                           <?php endif; ?>>
                            <?= aris_ust_h(aris_ust_t($tr, $en)) ?>
                            <?php if ($url === 'mesajlar.php' && $unread): ?>
                                <span class="aris-unread" role="img" aria-label="<?= aris_ust_h(aris_ust_t('Okunmamış mesajınız var', 'You have unread messages')) ?>"></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>

    <script>
    (() => {
        const header = document.getElementById('aris-site-header');
        const category = header.querySelector('.aris-categories');
        category.addEventListener('pointerenter', event => {
            if (event.pointerType === 'mouse') category.open = true;
        });
        category.addEventListener('pointerleave', event => {
            if (event.pointerType === 'mouse' && !category.contains(document.activeElement)) category.open = false;
        });
        category.addEventListener('focusout', () => {
            setTimeout(() => { if (!category.contains(document.activeElement)) category.open = false; }, 0);
        });
        document.addEventListener('click', event => {
            if (!category.contains(event.target)) category.open = false;
        });
        category.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                category.open = false;
                category.querySelector('summary').focus();
            }
        });
    })();
    </script>
    <?php
}
aris_ust_goster($baglanti ?? null);
