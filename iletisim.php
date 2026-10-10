<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İletişim - Hayvan Sahiplendirme</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header>
        <h1>Hayvan Sahiplendirme</h1>
        <nav>
            <a href="index.php">Ana Sayfa</a>
            <a href="ilanlar.php">İlanlar</a>
            <a href="ilan-ver.php">İlan Ver</a>
           <a href="/hayvan/hakkimizda.php">Hakkımızda</a>
            <a href="http://localhost/hayvan/iletisim.php">İletişim</a>
            <a href="giris.php">Giriş Yap</a>
        </nav>
    </header>

    <main>
        <section class="iletisim">z
            <h2>Bizimle İletişime Geçin</h2>
            <p>Hayvan sahiplendirme sitesi hakkında soru, öneri veya görüşlerinizi aşağıdaki formu kullanarak bize iletebilirsiniz.</p>

            <?php
            // Form gönderildiğinde çalışacak PHP bloğu
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $ad = htmlspecialchars($_POST['ad']);
                $eposta = htmlspecialchars($_POST['eposta']);
                $mesaj = htmlspecialchars($_POST['mesaj']);

                if (!empty($ad) && !empty($eposta) && !empty($mesaj)) {
                    echo "<div style='color: green; margin-bottom: 15px; font-weight: bold;'>Teşekkürler $ad, mesajınız başarıyla alındı!</div>";
                } else {
                    echo "<div style='color: red; margin-bottom: 15px; font-weight: bold;'>Lütfen tüm alanları doldurun.</div>";
                }
            }
            ?>

            <!-- İletişim Formu -->
            <form action="iletisim.php" method="POST" class="iletisim-formu">
                <div class="form-grup">
                    <label for="ad">Adınız Soyadınız:</label>
                    <input type="text" id="ad" name="ad" required placeholder="Adınızı giriniz">
                </div>

                <div class="form-grup">
                    <label for="eposta">E-posta Adresiniz:</label>
                    <input type="email" id="eposta" name="eposta" required placeholder="E-posta adresinizi giriniz">
                </div>

                <div class="form-grup">
                    <label for="mesaj">Mesajınız:</label>
                    <textarea id="mesaj" name="mesaj" rows="5" required placeholder="Mesajınızı yazınız..."></textarea>
                </div>

                <button type="submit" class="btn-gonder">Gönder</button>
            </form>
        </section>
    </main>

</body>
</html>