
<?php
session_start();
include("baglanti.php");

$hata = "";

// Giriş sonrasında dönülecek sayfa
$redirect = $_POST["redirect"] ?? $_GET["redirect"] ?? "";

// Yalnızca kendi sitemizdeki sahiplenme sayfasına yönlendirme
$hedef = "index.php";

if ($redirect !== "") {
    $parcalar = parse_url($redirect);

    if (
        $parcalar !== false &&
        !isset($parcalar["scheme"]) &&
        !isset($parcalar["host"]) &&
        ($parcalar["path"] ?? "") === "sahiplen.php"
    ) {
        $parametreler = [];
        parse_str($parcalar["query"] ?? "", $parametreler);

        $ilan_id = filter_var(
            $parametreler["ilan_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($ilan_id !== false && $ilan_id !== null && $ilan_id > 0) {
            $hedef = "sahiplen.php?ilan_id=" . $ilan_id;
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["giris"])) {

    $email = trim($_POST["email"] ?? "");
    $sifre = $_POST["sifre"] ?? "";

    $sql = "SELECT * FROM uyeler WHERE email = ?";
    $sorgu = mysqli_prepare($baglanti, $sql);

    mysqli_stmt_bind_param($sorgu, "s", $email);
    mysqli_stmt_execute($sorgu);

    $sonuc = mysqli_stmt_get_result($sorgu);

    if (mysqli_num_rows($sonuc) > 0) {

        $uye = mysqli_fetch_assoc($sonuc);
        $kayitli_sifre = $uye["sifre"];

        // Yeni üyeler için şifre doğrulama
        $dogru = password_verify($sifre, $kayitli_sifre);

        // Eski üyeler için geçiş desteği
        if (
            !$dogru &&
            password_get_info($kayitli_sifre)["algo"] === null
        ) {
            $dogru = hash_equals($kayitli_sifre, $sifre);

            if ($dogru) {
                $yeni_hash = password_hash($sifre, PASSWORD_DEFAULT);

                $guncelle = mysqli_prepare(
                    $baglanti,
                    "UPDATE uyeler SET sifre = ? WHERE id = ?"
                );

                mysqli_stmt_bind_param(
                    $guncelle,
                    "si",
                    $yeni_hash,
                    $uye["id"]
                );

                mysqli_stmt_execute($guncelle);
                mysqli_stmt_close($guncelle);
            }
        }

        if ($dogru) {

            session_regenerate_id(true);

            $_SESSION["uye_id"] = $uye["id"];
            $_SESSION["uye_adsoyad"] = $uye["ad_soyad"];
            $_SESSION["uye_email"] = $uye["email"];

            header("Location: " . $hedef);
            exit();

        } else {
            $hata = "E-posta veya şifre yanlış.";
        }

    } else {
        $hata = "E-posta veya şifre yanlış.";
    }

    mysqli_stmt_close($sorgu);
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
    <link rel="stylesheet" href="css/style.css">
</head>


<body class="giris-sayfasi">

    <header class="ilan-ust">
        <a href="index.php" class="ilan-logo">
            <img src="images/aris-logoo.png"
     alt="Aris Pati Yuvası logosu"
     style="width:48px; height:48px; object-fit:contain;">
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
        </nav>
    </header>

    <main class="giris-alani">

        <div class="giris-kart">
<img src="images/aris-logoo.png"
     alt="Aris Pati Yuvası logosu"
     style="width:48px; height:48px; object-fit:contain;">
            <h1>Tekrar Hoş Geldiniz!</h1>
            <p class="giris-aciklama">
                Aris Pati Yuvası hesabınıza giriş yapın.
            </p>

            <?php if ($hata !== ""): ?>
                <p class="giris-hata">
                    <?php echo htmlspecialchars($hata, ENT_QUOTES, "UTF-8"); ?>
                </p>
            <?php endif; ?>

            <form action="giris.php" method="POST">

                <input
                    type="hidden"
                    name="redirect"
                    value="<?php echo htmlspecialchars($hedef, ENT_QUOTES, "UTF-8"); ?>"
                >

                <label for="email">E-posta Adresi</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="E-posta adresinizi giriniz"
                    required
                >

                <label for="sifre">Şifre</label>
                <input
                    type="password"
                    id="sifre"
                    name="sifre"
                    placeholder="Şifrenizi giriniz"
                    required
                >

                <button type="submit" name="giris" class="giris-buton">
                    Giriş Yap
                </button>

            </form>

            <p class="giris-kayit">
                Henüz hesabınız yok mu?
                <a href="kayit.php">Kayıt Ol</a>
            </p>

        </div>

    </main>

    <footer class="giris-alt">
        © 2026 Aris Pati Yuvası · Her can dostu sevgi dolu bir yuvayı hak eder.
    </footer>

</body>
</html>
