<?php
session_start();
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hakkımızda - Hayvan Sahiplendirme</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <header>
        <h1>Hayvan Sahiplendirme</h1>

        <nav>
            <a href="index.php">Ana Sayfa</a>
            <a href="ilanlar.html">İlanlar</a>
            <a href="ilan-ver.html">İlan Ver</a>
            <a href="hakkimizda.php">Hakkımızda</a>
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
            <h2>Hakkımızda</h2>
            <p>Hayvan Sahiplendirme sitesine hoş geldiniz.</p>
            <p># Hayvan Sahiplendirme Platformu
Hayvan Sahiplendirme Platformu, sahipsiz hayvanların güvenli ve sevgi dolu yuvalara kavuşmasını sağlamak amacıyla hazırlanmıştır. 
Sitemiz sayesinde kullanıcılar sahiplendirilmek istenen kedi, köpek ve diğer hayvanların ilanlarını inceleyebilir,
 hayvanlar hakkında bilgi alabilir ve sahiplendirme başvurusunda bulunabilir.
Amacımız, hayvanların sokakta kalmasını önlemek, onlara sevgi dolu bir yuva bulmak ve hayvan sahiplenmek isteyen kişileri bir araya getirmektir.
Unutmayalım: Hayvan sahiplenmek yalnızca bir hayvanı eve almak değil, onun bakımını üstlenmek ve ona ömür boyu sevgi göstermektir.
Bir cana yuva ol, hayatını güzelleştir! .</p>
        </section>
    </main>

    <footer>
        <p>Hayvan Sahiplendirme Projesi</p>
    </footer>

    <script src="js/script.js"></script>

</body>
</html>