
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
</head>

<body>

<header>
    <h1>Hayvan Sahiplendirme</h1>

    <nav>
        <a href="index.php">Ana Sayfa</a>
        <a href="ilanlar.php">İlanlar</a>
        <a href="ilan-ver.php">İlan Ver</a>
        <a href="hakkimizda.php">Hakkımızda</a>
        <a href="iletisim.php">İletişim</a>

        <?php if (isset($_SESSION["uye_id"])) { ?>

            <a href="profil.php">
                <?php
                echo htmlspecialchars(
                    $_SESSION["uye_adsoyad"],
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>
            </a>

            <a href="cikis.php">Çıkış Yap</a>

        <?php } else { ?>

            <a href="giris.php">Giriş Yap</a>

        <?php } ?>
    </nav>
</header>

<main>

    <section class="iletisim">

        <h2>Bizimle İletişime Geçin</h2>

        <p>
            Hayvan sahiplendirme sitesi hakkında soru,
            öneri veya görüşlerinizi aşağıdaki formu
            kullanarak bize iletebilirsiniz.
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
