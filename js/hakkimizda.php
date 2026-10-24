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
    
<style>
    body {
        background-color: #f5f8f5;
    }

    .tanitim {
        max-width: 800px;
        margin: 60px auto;
        padding: 40px;
        background-color: white;
        border: 1px solid #dce8df;
        border-radius: 15px;
    }

    .tanitim h2 {
        color: #184f3c;
        text-align: center;
        font-size: 28px;
        margin-bottom: 30px;
    }

    .tanitim p {
        color: #526b5a;
        font-size: 16px;
        line-height: 1.9;
        margin-bottom: 20px;
    }

    .tanitim p:last-child {
        text-align: center;
        color: #347a53;
        font-weight: bold;
    }
</style>

</head>

<body>

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

        <?php if (isset($_SESSION["uye_id"])) { ?>
            <a href="profil.php">Profilim</a>
            <a href="cikis.php">Çıkış Yap</a>
        <?php } else { ?>
            <a href="giris.php">Giriş Yap</a>
        <?php } ?>
    </nav>
</header>

    <main>
        
<section class="tanitim">
    <h2>Küçük Patiler, Büyük Umutlar 🐾</h2>

    <p>Her canlının sevilmeye, korunmaya ve güvenle uyuyabileceği bir yuvaya ihtiyacı olduğuna inanıyoruz.</p>

    <p>Aris Pati Yuvası, sokakta ya da geçici yuvalarda yaşayan dostlarımızın kendilerini gerçekten sevecek insanlarla buluşabilmesi için oluşturuldu.</p>

    <p>Bazen bir çift meraklı göz, bazen sallanan bir kuyruk, bazen de küçücük bir pati izi hayatımızı güzelleştirmeye yeter. Biz de bu küçük dostlarımızın hayatlarında güzel bir değişime vesile olmak istiyoruz.</p>

    <p>Platformumuzda yuva arayan dostlarımızı tanıyabilir, hikâyelerini öğrenebilir ve onlara sıcak bir yuva olmak için ilk adımı atabilirsiniz.</p>

    <p>Çünkü biliyoruz ki bir hayvanı sahiplenmek sadece ona kapımızı açmak değil; sevgimizi, zamanımızı ve hayatımızı da paylaşmaktır.</p>

    <p><strong>Unutmayın, siz bir canın hayatını değiştirirken o da sizin hayatınıza tarifsiz bir sevgi katabilir.</strong></p>

    <p><em>Bir pati, bir umut, bir ömürlük dostluk... 💚</em></p>
</section>

    </main>

    <footer>
        <p>Hayvan Sahiplendirme Projesi</p>
    </footer>

    <script src="js/script.js"></script>

</body>
</html>