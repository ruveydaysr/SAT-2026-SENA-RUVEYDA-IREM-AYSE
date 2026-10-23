<?php
require_once __DIR__ . "/../baglanti.php";

$aktif_sayfa = basename($_SERVER["SCRIPT_NAME"]);

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
        "sayi" => (int)$sayaclar["ilan"]
    ],
    "admin-basvurular.php" => [
        "baslik" => "Sahiplenme Başvuruları",
        "sayi" => (int)$sayaclar["basvuru"]
    ],
    "admin-uyeler.php" => [
        "baslik" => "Üye Yönetimi",
        "sayi" => 0
    ],
    "admin-mesajlar.php" => [
        "baslik" => "Gelen Mesajlar",
        "sayi" => 0
    ]
];
?>

<header>
    <a
        href="../index.php"
        class="admin-logo"
        onclick="return confirm('Siteye gitmek istediğinize emin misiniz?');"
    >
        <span class="logo-pati">🐾</span>

        <span>
            <strong>Aris Pati Yuvası</strong>
            <small>Her can dostuna bir yuva</small>
        </span>
    </a>

    <h2>Admin Paneli</h2>
</header>

<nav class="admin-menu" aria-label="Yönetim menüsü">

    <?php foreach ($menu as $dosya => $oge): ?>
        <a
            href="<?php echo htmlspecialchars($dosya, ENT_QUOTES, 'UTF-8'); ?>"
            <?php if ($aktif_sayfa === $dosya): ?>
                aria-current="page"
            <?php endif; ?>
        >
            <?php echo htmlspecialchars($oge["baslik"], ENT_QUOTES, "UTF-8"); ?>

            <?php if ($oge["sayi"] > 0): ?>
                <span
                    aria-label="<?php echo $oge["sayi"]; ?> bekleyen kayıt"
                    style="
                        display: inline-block;
                        margin-left: 5px;
                        padding: 3px 7px;
                        border-radius: 12px;
                        background: #eee5d3;
                        color: #284b32;
                        font-size: 12px;
                        font-weight: bold;
                        line-height: 1.2;
                    "
                >
                    <?php echo $oge["sayi"]; ?>
                </span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>

    <a
        href="../index.php"
        onclick="return confirm('Siteye gitmek istediğinize emin misiniz?');"
    >
        Siteye Git
    </a>

    <a
        href="admin-cikis.php"
        class="cikis-buton"
        onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');"
    >
        Çıkış Yap
    </a>

</nav>