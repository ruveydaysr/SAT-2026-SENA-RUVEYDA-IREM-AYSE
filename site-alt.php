<?php
/* Ortak kullanıcı sayfası alt bölümü. */
$alt_en = ($dil ?? ($_SESSION['dil'] ?? 'tr')) === 'en';
$alt_yazi = static function ($tr, $en) use ($alt_en) {
    return htmlspecialchars($alt_en ? $en : $tr, ENT_QUOTES, 'UTF-8');
};
?>
<footer class="aris-footer" aria-label="<?= $alt_yazi('Site alt bilgisi', 'Site footer') ?>">
    <div class="aris-footer-grid">
        <div class="aris-footer-brand">
            <a href="index.php" class="aris-footer-logo">
                <img src="images/aris-logoo.png" width="52" height="52" alt="Aris Pati Yuvası">
                <strong>Aris Pati Yuvası</strong>
            </a>
            <p><?= $alt_yazi('Her can dostuna bir yuva.', 'A home for every animal.') ?></p>
            <p><?= $alt_yazi('Yeni bir dostla tanışın, bir hayata sevgiyle dokunun. Sahiplenme yolculuğunuzda yanınızdayız.', 'Meet a new companion and make a difference with love. We are here for your adoption journey.') ?></p>
        </div>
        <nav aria-label="<?= $alt_yazi('Kurumsal', 'About us') ?>">
            <h2><?= $alt_yazi('Kurumsal', 'About Us') ?></h2>
            <a href="hakkimizda.php"><?= $alt_yazi('Hakkımızda', 'Our Story') ?></a>
            <a href="iletisim.php"><?= $alt_yazi('İletişim', 'Contact') ?></a>
        </nav>
        <nav aria-label="<?= $alt_yazi('Sahiplenme', 'Adoption') ?>">
            <h2><?= $alt_yazi('Sahiplenme', 'Adoption') ?></h2>
            <a href="ilanlar.php"><?= $alt_yazi('Dostunu Bul', 'Find a Companion') ?></a>
            <a href="ilan-ver.php"><?= $alt_yazi('İlan Ver', 'Post a Listing') ?></a>
            <a href="ilanlar.php?tur=Kedi"><?= $alt_yazi('Kedi İlanları', 'Cats') ?></a>
            <a href="ilanlar.php?tur=<?= rawurlencode('Köpek') ?>"><?= $alt_yazi('Köpek İlanları', 'Dogs') ?></a>
        </nav>
        <nav aria-label="<?= $alt_yazi('Faydalı Bağlantılar', 'Useful links') ?>">
            <h2><?= $alt_yazi('Faydalı Bağlantılar', 'Useful Links') ?></h2>
            <a href="blog.php"><?= $alt_yazi('Blog ve Bakım Bilgileri', 'Blog and Pet Care') ?></a>
            <a href="profil.php"><?= $alt_yazi('Hesabım', 'My Account') ?></a>
            <a href="mesajlar.php"><?= $alt_yazi('Mesajlarım', 'My Messages') ?></a>
            <a href="iletisim.php"><?= $alt_yazi('Yardım ve Destek', 'Help and Support') ?></a>
        </nav>
    </div>
    <div class="aris-footer-bottom">
        <span>© <?= date('Y') ?> Aris Pati Yuvası. <?= $alt_yazi('Tüm hakları saklıdır.', 'All rights reserved.') ?></span>
        <span><?= $alt_yazi('Satın alma, sahiplen.', 'Adopt a friend.') ?></span>
    </div>
</footer>
