
<?php
session_start();
include("baglanti.php");

$bildirim = "";
$basarili = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_SESSION["uye_id"])) {
        $bildirim = "Mesaj göndermek için giriş yapmalısınız.";
    } else {

        $uye_id = (int) $_SESSION["uye_id"];
        $ad = trim($_SESSION["uye_adsoyad"] ?? "");
        $eposta = trim($_SESSION["uye_email"] ?? "");
        $mesaj = trim($_POST["mesaj"] ?? "");

        if ($ad == "" || $eposta == "" || $mesaj == "") {
            $bildirim = "Lütfen mesajınızı yazınız.";
        } else {

            $sql = "INSERT INTO mesajlar
                    (uye_id, ad_soyad, email, mesaj)
                    VALUES (?, ?, ?, ?)";

            $sorgu = mysqli_prepare($baglanti, $sql);

            mysqli_stmt_bind_param(
                $sorgu,
                "isss",
                $uye_id,
                $ad,
                $eposta,
                $mesaj
            );

            if (mysqli_stmt_execute($sorgu)) {
                $bildirim = "Mesajınız yöneticiye gönderildi.";
                $basarili = true;
            } else {
                $bildirim = "Mesaj gönderilemedi.";
            }

            mysqli_stmt_close($sorgu);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İletişim - Hayvan Sahiplendirme</title>
    <link rel="stylesheet" href="css/style.css">
    
<style>
    body {
        background-color: #f5f8f5;
    }

    .iletisim {
        max-width: 650px;
        margin: 60px auto;
        padding: 40px;
        background-color: white;
        border: 1px solid #dce8df;
        border-radius: 15px;
    }

    .iletisim h2 {
        color: #184f3c;
        text-align: center;
        font-size: 28px;
        margin-bottom: 25px;
    }

    .iletisim p {
        color: #526b5a;
        line-height: 1.8;
        margin-bottom: 20px;
    }

    .iletisim-formu {
        margin-top: 30px;
    }

    .iletisim .form-grup {
        margin-bottom: 20px;
    }

    .iletisim .form-grup label {
        display: block;
        color: #184f3c;
        font-weight: bold;
        margin-bottom: 8px;
    }

    .iletisim .form-grup input,
    .iletisim .form-grup textarea {
        width: 100%;
        box-sizing: border-box;
        padding: 12px;
        border: 1px solid #cbded0;
        border-radius: 8px;
        font-family: Arial, sans-serif;
        font-size: 15px;
    }

    .iletisim .form-grup input[readonly] {
        background-color: #f5f8f5;
    }

    .iletisim .btn-gonder {
        background-color: #347a53;
        color: white;
        padding: 12px 25px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 15px;
    }

    .iletisim .btn-gonder:hover {
        background-color: #245c3d;
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

    <section class="iletisim">

<h2>Bir Mesajınız mı Var? 🐾</h2>

<p>
    Aris Pati Yuvası olarak sizlerden haber almak bizi mutlu eder!
    Minik dostlarımızla ilgili merak ettikleriniz,
    önerileriniz veya bizimle paylaşmak istediğiniz
    düşünceleriniz varsa buradayız.
</p>

<p>
    Aşağıdaki formu doldurarak bize kolayca ulaşabilirsiniz.
    Her mesajınız bizim için değerli. 💚
</p>


        <?php if ($bildirim != "") { ?>

            <p style="color: <?php echo $basarili ? 'green' : 'red'; ?>;
                      font-weight: bold;">

                <?php
                echo htmlspecialchars(
                    $bildirim,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>

            </p>

        <?php } ?>

        <?php if (isset($_SESSION["uye_id"])) { ?>

            <form action="iletisim.php"
                  method="POST"
                  class="iletisim-formu">

                <div class="form-grup">

                    <label for="ad">
                        Adınız Soyadınız:
                    </label>

                    <input
                        type="text"
                        id="ad"
                        value="<?php
                        echo htmlspecialchars(
                            $_SESSION["uye_adsoyad"] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        );
                        ?>"
                        readonly>

                </div>

                <div class="form-grup">

                    <label for="eposta">
                        E-posta Adresiniz:
                    </label>

                    <input
                        type="email"
                        id="eposta"
                        value="<?php
                        echo htmlspecialchars(
                            $_SESSION["uye_email"] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        );
                        ?>"
                        readonly>

                </div>

                <div class="form-grup">

                    <label for="mesaj">
                        Sorunuz:
                    </label>

                    <textarea
                        id="mesaj"
                        name="mesaj"
                        rows="5"
                        maxlength="5000"
                        required
                        placeholder="Yöneticiye sorunuzu yazınız..."></textarea>

                </div>

                <button type="submit" class="btn-gonder">
                    Soruyu Gönder
                </button>

            </form>

        <?php } else { ?>

            <p>
                Yöneticiye soru göndermek için
                üye girişi yapmalısınız.
            </p>

            <a href="giris.php">Giriş Yap</a>

        <?php } ?>

    </section>

</main>

</body>
</html>
