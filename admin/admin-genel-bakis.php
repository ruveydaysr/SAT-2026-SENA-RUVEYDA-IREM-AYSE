<?php
session_start();

if (!isset($_SESSION["admin"])) {
    header("Location: admin-giris.php");
    exit();
}

require_once __DIR__ . "/../baglanti.php";

$sql = "SELECT
    (SELECT COUNT(*) FROM uyeler) AS toplam_uye,
    (SELECT COUNT(*) FROM ilanlar) AS toplam_ilan,
    (SELECT COUNT(*) FROM ilanlar
        WHERE durum = 'Bekliyor') AS bekleyen_ilan,
    (SELECT COUNT(*) FROM ilanlar
        WHERE durum = 'Onaylandı') AS onaylanan_ilan,
    (SELECT COUNT(*) FROM ilanlar
        WHERE durum = 'Reddedildi') AS reddedilen_ilan,
    (SELECT COUNT(*) FROM sahiplenme_basvurulari) AS toplam_basvuru,
    (SELECT COUNT(*) FROM sahiplenme_basvurulari
        WHERE durum = 'Bekliyor') AS bekleyen_basvuru,
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

    <link rel="stylesheet" href="admin-style.css?v=15">

    <style>
        .panel-aciklama {
            margin-bottom: 24px;
            color: #687361;
            line-height: 1.6;
        }

        .istatistikler {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
        }

        .istatistik-karti {
            display: flex;
            flex-direction: column;
            gap: 14px;
            min-width: 0;
            padding: 26px;
            border: 1px solid #ded6c6;
            border-top: 4px solid #80a86d;
            border-radius: 14px;
            background: #fffdf8;
            color: #284b32;
            text-decoration: none;
            box-shadow: 0 6px 20px rgba(40, 75, 50, 0.05);
        }

        .istatistik-karti:hover {
            background: #edf3e7;
        }

        .istatistik-karti:focus-visible {
            outline: 2px solid #426b46;
            outline-offset: 3px;
        }

        .istatistik-karti span {
            font-size: 16px;
            font-weight: bold;
        }

        .istatistik-karti strong {
            font-size: 36px;
            line-height: 1.2;
        }

        .istatistik-karti small {
            margin-top: auto;
            color: #687361;
            line-height: 1.5;
        }

        @media (max-width: 1100px) {
            .istatistikler {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 550px) {
            .istatistikler {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<?php require __DIR__ . "/admin-menu.php"; ?>

<main class="icerik">

    <h2>Genel Bakış</h2>

    <p class="panel-aciklama">
        Yönetim paneline hoş geldiniz.
        Sistemdeki kayıt sayılarını buradan takip edebilirsiniz.
    </p>

    <div class="istatistikler">

        <a href="admin-uyeler.php" class="istatistik-karti">
            <span>Toplam Üye</span>
            <strong><?php echo (int)$sayilar["toplam_uye"]; ?></strong>
            <small>Üyeleri görüntüle →</small>
        </a>

        <a href="admin-panel.php?durum=" class="istatistik-karti">
            <span>Toplam İlan</span>
            <strong><?php echo (int)$sayilar["toplam_ilan"]; ?></strong>
            <small>Tüm ilanları görüntüle →</small>
        </a>

        <a href="admin-panel.php?durum=Bekliyor" class="istatistik-karti">
            <span>Bekleyen İlan</span>
            <strong><?php echo (int)$sayilar["bekleyen_ilan"]; ?></strong>
            <small>Bekleyen ilanları incele →</small>
        </a>

        <a
            href="admin-panel.php?durum=<?php echo rawurlencode('Onaylandı'); ?>"
            class="istatistik-karti"
        >
            <span>Onaylanan İlan</span>
            <strong><?php echo (int)$sayilar["onaylanan_ilan"]; ?></strong>
            <small>Onaylanan ilanları görüntüle →</small>
        </a>

        <a href="admin-panel.php?durum=Reddedildi" class="istatistik-karti">
            <span>Reddedilen İlan</span>
            <strong><?php echo (int)$sayilar["reddedilen_ilan"]; ?></strong>
            <small>Reddedilen ilanları görüntüle →</small>
        </a>

        <a href="admin-basvurular.php" class="istatistik-karti">
            <span>Toplam Sahiplenme Başvurusu</span>
            <strong><?php echo (int)$sayilar["toplam_basvuru"]; ?></strong>
            <small>Tüm başvuruları görüntüle →</small>
        </a>

        <a
            href="admin-basvurular.php?durum=Bekliyor"
            class="istatistik-karti"
        >
            <span>Bekleyen Başvuru</span>
            <strong><?php echo (int)$sayilar["bekleyen_basvuru"]; ?></strong>
            <small>Bekleyen başvuruları incele →</small>
        </a>

        <a href="admin-mesajlar.php" class="istatistik-karti">
            <span>Toplam Mesaj</span>
            <strong><?php echo (int)$sayilar["toplam_mesaj"]; ?></strong>
            <small>Gelen mesajları görüntüle →</small>
        </a>

    </div>

</main>

</body>
</html>