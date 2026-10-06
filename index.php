<?php
session_start();
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hayvan Sahiplendirme</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <header>
        <h1>Hayvan Sahiplendirme</h1>

        <nav>
            <a href="index.php">Ana Sayfa</a>
            <a href="ilanlar.html">İlanlar</a>
            <a href="ilan-ver.html">İlan Ver</a>
            <a href="hakkimizda.html">Hakkımızda</a>
            <a href="iletisim.html">İletişim</a>
           <?php if (isset($_SESSION["uye_id"])) { ?>

    <a href="profil.php">
        <?php echo $_SESSION["uye_adsoyad"]; ?>
    </a>

    <a href="cikis.php">Çıkış Yap</a>

<?php } else { ?>

    <a href="giris.php">Giriş Yap</a>

<?php } ?>
        
        </nav>
    </header>

    <main>
        <section class="tanitim">
    <h2>Yeni Bir Dost Edin</h2>

    <p>
        Sahiplendirilmeyi bekleyen hayvanlara sıcak bir yuva bulmalarına yardımcı ol.
        İlanları inceleyerek sana uygun yeni dostunu bulabilirsin.
    </p>

    <a href="ilanlar.html" class="ilan-buton">İlanları İncele</a>
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

    <footer>
        <p>Hayvan Sahiplendirme Projesi</p>
    </footer>

    <script src="js/script.js"></script>

</body>
</html>