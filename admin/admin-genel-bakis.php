<?php
session_start();

if (!isset($_SESSION["admin"])) {
    header("Location: admin-giris.php");
    exit();
}

require_once("../baglanti.php");

/* Sistemdeki kayıt sayılarını getir */

$sql = "SELECT
    (SELECT COUNT(*) FROM uyeler) AS toplam_uye,
    (SELECT COUNT(*) FROM ilanlar) AS toplam_ilan,
    (SELECT COUNT(*) FROM ilanlar
        WHERE durum = 'Bekliyor') AS bekleyen_ilan,
    (SELECT COUNT(*) FROM ilanlar
        WHERE durum = 'Onaylandı') AS onaylanan_ilan,
    (SELECT COUNT(*) FROM ilanlar
        WHERE durum = 'Reddedildi') AS reddedilen_ilan,
    (SELECT COUNT(*) FROM mesajlar) AS toplam_mesaj";

$sonuc = mysqli_query($baglanti, $sql);
$sayilar = mysqli_fetch_assoc($sonuc);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Genel Bakış | Admin Paneli</title>

    <link rel="stylesheet" href="admin-style.css?v=13">
</head>

<body>

<?php require __DIR__ . "/admin-menu.php"; ?>

<main class="icerik">

    <h2>Genel Bakış</h2>

    <div class="istatistikler">

        <a href="admin-uyeler.php" class="istatistik-karti">
            <span>Toplam Üye</span>
            <strong>
                <?php echo (int)$sayilar["toplam_uye"]; ?>
            </strong>
            <small>Üyeleri görüntüle →</small>
        </a>

        <a href="admin-panel.php" class="istatistik-karti">
            <span>Toplam İlan</span>
            <strong>
                <?php echo (int)$sayilar["toplam_ilan"]; ?>
            </strong>
            <small>Tüm ilanları görüntüle →</small>
        </a>

        <a
            href="admin-panel.php?durum=Bekliyor"
            class="istatistik-karti"
        >
            <span>Bekleyen İlan</span>
            <strong>
                <?php echo (int)$sayilar["bekleyen_ilan"]; ?>
            </strong>
            <small>Bekleyen ilanları incele →</small>
        </a>

        <a
            href="admin-panel.php?durum=<?php echo rawurlencode('Onaylandı'); ?>"
            class="istatistik-karti"
        >
            <span>Onaylanan İlan</span>
            <strong>
                <?php echo (int)$sayilar["onaylanan_ilan"]; ?>
            </strong>
            <small>Onaylanan ilanları görüntüle →</small>
        </a>

        <a
            href="admin-panel.php?durum=Reddedildi"
            class="istatistik-karti"
        >
            <span>Reddedilen İlan</span>
            <strong>
                <?php echo (int)$sayilar["reddedilen_ilan"]; ?>
            </strong>
            <small>Reddedilen ilanları görüntüle →</small>
        </a>

        <a href="admin-mesajlar.php" class="istatistik-karti">
            <span>Toplam Mesaj</span>
            <strong>
                <?php echo (int)$sayilar["toplam_mesaj"]; ?>
            </strong>
            <small>Gelen mesajları görüntüle →</small>
        </a>

    </div>

</main>

</body>
</html>