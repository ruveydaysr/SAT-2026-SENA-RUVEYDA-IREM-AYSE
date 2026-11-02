<?php
require_once __DIR__ . "/admin-dil.php";
require_once __DIR__ . "/../baglanti.php";

$aktif_sayfa = basename($_SERVER["SCRIPT_NAME"] ?? "");

$sayac_sonuc = mysqli_query(
    $baglanti,
    "SELECT
        (SELECT COUNT(*) FROM ilanlar
         WHERE durum = 'Bekliyor') AS ilan,
        (SELECT COUNT(*) FROM sahiplenme_basvurulari
         WHERE durum = 'Bekliyor') AS basvuru"
);

$sayaclar = $sayac_sonuc
    ? mysqli_fetch_assoc($sayac_sonuc)
    : ["ilan" => 0, "basvuru" => 0];

$menu = [
    "admin-genel-bakis.php" => [
        "baslik" => "Genel Bakış",
        "sayi" => 0
    ],
    "admin-anasayfa.php" => [
        "baslik" => "Ana Sayfa Yönetimi",
        "sayi" => 0
    ],
    "admin-panel.php" => [
        "baslik" => "İlan Yönetimi",
        "sayi" => (int) $sayaclar["ilan"]
    ],
    "admin-basvurular.php" => [
        "baslik" => "Sahiplenme Başvuruları",
        "sayi" => (int) $sayaclar["basvuru"]
    ],
    "admin-uyeler.php" => [
        "baslik" => "Üye Yönetimi",
        "sayi" => 0
    ],
    "admin-mesajlar.php" => [
        "baslik" => "Gelen Mesajlar",
        "sayi" => 0
    ],
    "admin-profil.php" => [
        "baslik" => "Profilim",
        "sayi" => 0
    ]
];
?>

<?php require_once __DIR__ . "/admin-ust-ortak.php"; ?>
<header id="aris-site-header" class="aris-site-header aris-admin-header">
    <?php admin_ortak_logo(); ?>
    <div class="aris-header-right">
        <?php admin_ortak_diller(); ?>
        <nav class="aris-navigation" aria-label="<?= admin_h(admin_t('Yönetim menüsü')) ?>">
            <?php foreach ($menu as $dosya => $oge): ?>
                <a href="<?= admin_h($dosya) ?>" <?= $aktif_sayfa === $dosya ? 'aria-current="page"' : '' ?>>
                    <?= admin_h(admin_t($oge["baslik"])) ?>
                    <?php if ($oge["sayi"] > 0): ?>
                        <span class="aris-admin-counter" aria-label="<?= (int) $oge['sayi'] ?> <?= admin_h(admin_t('bekleyen kayıt')) ?>"><?= (int) $oge["sayi"] ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
            <a href="../index.php" onclick="return confirm(<?= admin_h(admin_json('Siteye gitmek istediğinize emin misiniz?')) ?>);">
                <?= admin_h(admin_t("Siteye Git")) ?>
            </a>
            <a href="admin-cikis.php" onclick="return confirm(<?= admin_h(admin_json('Çıkış yapmak istediğinize emin misiniz?')) ?>);">
                <?= admin_h(admin_t("Çıkış Yap")) ?>
            </a>
        </nav>
    </div>
</header>
