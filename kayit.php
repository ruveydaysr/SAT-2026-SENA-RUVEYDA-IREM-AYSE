<?php

include("baglanti.php");

$mesaj = "";

if (isset($_POST["kayit"])) {

    $adsoyad = trim($_POST["adsoyad"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $telefon = trim($_POST["telefon"] ?? "");
    $sifre = $_POST["sifre"] ?? "";

    if ($adsoyad === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || $telefon === "" || $sifre === "") {

        $mesaj = "Lütfen tüm alanları doğru doldurunuz.";

    } else {

        $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);

        $sql = "INSERT INTO uyeler (ad_soyad, email, telefon, sifre)
                VALUES (?, ?, ?, ?)";

        $sorgu = mysqli_prepare($baglanti, $sql);

        mysqli_stmt_bind_param(
            $sorgu, "ssss",
            $adsoyad, $email, $telefon, $sifre_hash
        );

        if (mysqli_stmt_execute($sorgu)) {
            $mesaj = "Üyelik başarıyla oluşturuldu.";
            
header("Refresh: 2; url=giris.php");
$mesaj = "Kaydınız başarıyla tamamlandı. Giriş sayfasına yönlendiriliyorsunuz...";

        } else {
            $mesaj = "Kayıt yapılamadı. E-posta adresi zaten kullanılıyor olabilir.";
        }

        mysqli_stmt_close($sorgu);
    }
}
?>




<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<?php

include("baglanti.php");

$mesaj = "";

if (isset($_POST["kayit"])) {

    $adsoyad = trim($_POST["adsoyad"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $telefon = trim($_POST["telefon"] ?? "");
    $sifre = $_POST["sifre"] ?? "";

    if ($adsoyad === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || $telefon === "" || $sifre === "") {

        $mesaj = "Lütfen tüm alanları doğru doldurunuz.";

    } else {

        $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);

        $sql = "INSERT INTO uyeler (ad_soyad, email, telefon, sifre)
                VALUES (?, ?, ?, ?)";

        $sorgu = mysqli_prepare($baglanti, $sql);

        mysqli_stmt_bind_param(
            $sorgu, "ssss",
            $adsoyad, $email, $telefon, $sifre_hash
        );

        if (mysqli_stmt_execute($sorgu)) {
            $mesaj = "Üyelik başarıyla oluşturuldu.";
            
header("Refresh: 2; url=giris.php");
$mesaj = "Kaydınız başarıyla tamamlandı. Giriş sayfasına yönlendiriliyorsunuz...";

        } else {
            $mesaj = "Kayıt yapılamadı. E-posta adresi zaten kullanılıyor olabilir.";
        }

        mysqli_stmt_close($sorgu);
    }
}
?>




<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="kayit-sayfasi">

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
            <a href="giris.php">Giriş Yap</a>
        </nav>
    </header>

    <main class="kayit-alani">

        <div class="kayit-kart">

            <div class="kayit-simge">🐾</div>

            <h1>Aramıza Katıl!</h1>

            <p class="kayit-aciklama">
                Aris Pati Yuvası ailesine katılmak için
                bilgilerini doldur.
            </p>

            <?php if ($mesaj !== ""): ?>
                <p class="kayit-mesaj">
                    <?php echo htmlspecialchars($mesaj, ENT_QUOTES, "UTF-8"); ?>
                </p>
            <?php endif; ?>

            <form method="POST" action="kayit.php">

                <label for="adsoyad">Ad Soyad</label>
                <input
                    type="text"
                    id="adsoyad"
                    name="adsoyad"
                    placeholder="Adınızı ve soyadınızı giriniz"
                    required
                >

                <label for="email">E-posta Adresi</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="E-posta adresinizi giriniz"
                    required
                >

                <label for="telefon">Telefon Numarası</label>
                <input
                    type="tel"
                    id="telefon"
                    name="telefon"
                    placeholder="Telefon numaranızı giriniz"
                    required
                >

                <label for="sifre">Şifre</label>
                <input
                    type="password"
                    id="sifre"
                    name="sifre"
                    placeholder="Şifrenizi oluşturunuz"
                    required
                >

                <button type="submit" name="kayit" class="kayit-buton">
                    Kayıt Ol
                </button>

            </form>

            <p class="kayit-giris">
                Zaten hesabınız var mı?
                <a href="giris.php">Giriş Yap</a>
            </p>

        </div>

    </main>

    <footer class="kayit-alt">
        © 2026 Aris Pati Yuvası · Her can dostu sevgi dolu bir yuvayı hak eder.
    </footer>

</body>
</html>


</html>
