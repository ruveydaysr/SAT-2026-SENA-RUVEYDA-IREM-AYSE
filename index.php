<?php
session_start();
require_once("baglanti.php");

function guvenli($deger)
{
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

$sonuc = mysqli_query(
    $baglanti,
    "SELECT baslik, aciklama, buton_yazisi
     FROM anasayfa_icerik WHERE id = 1"
);

$icerik = mysqli_fetch_assoc($sonuc) ?: [
    "baslik" => "Yeni Bir Dost Edin",
    "aciklama" => "İlanları inceleyerek sana uygun yeni dostunu bulabilirsin.",
    "buton_yazisi" => "İlanları İncele"
];
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hayvan Sahiplendirme</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
        .tanitim .tanitim-aciklama {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .tanitim h2,
        .tanitim .ilan-buton {
            overflow-wrap: anywhere;
        }
    </style>
</head>

<body class="ilan-sayfasi">

<header class="ilan-ust">
    <a href="index.php" class="ilan-logo">
        <span class="logo-pati">🐾</span>
        <span>
            <strong>Aris Pati Yuvası</strong>
            <small>Her can dostuna bir yuva</small>
        </span>
    </a>

    <nav class="ilan-menu">
        <a href="index.php">Ana Sayfa</a>
        <a href="ilanlar.php">İlanlar</a>
        <a href="ilan-ver.php">İlan Ver</a>
        <a href="hakkimizda.php">Hakkımızda</a>
        <a href="iletisim.php">İletişim</a>

        <?php if (isset($_SESSION["uye_id"])): ?>
            <a href="profil.php">
                <?php echo guvenli($_SESSION["uye_adsoyad"] ?? "Profilim"); ?>
            </a>

            <a
                href="cikis.php"
                onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');"
            >
                Çıkış Yap
            </a>
        <?php else: ?>
            <a href="giris.php">Giriş Yap</a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <section class="tanitim">
        <h2><?php echo guvenli($icerik["baslik"]); ?></h2>

        <p class="tanitim-aciklama"><?php
            echo guvenli($icerik["aciklama"]);
        ?></p>

        <a href="ilanlar.php" class="ilan-buton">
            <?php echo guvenli($icerik["buton_yazisi"]); ?>
        </a>
    </section>

    <section class="kategoriler">
        <h2>Hayvan Kategorileri</h2>

        <div class="kategori-listesi">
            <div class="kategori">🐱 Kedi</div>
            <div class="kategori">🐶 Köpek</div>
            <div class="kategori">🐦 Kuş</div>
            <div class="kategori">🐠 Balık</div>
            <div class="kategori">🐰 Tavşan</div>
            <div class="kategori">🐹 Kemirgen</div>
            <div class="kategori">🦎 Sürüngen</div>
            <div class="kategori">🐾 Diğer</div>
        </div>
    </section>
</main>

</body>
</html>