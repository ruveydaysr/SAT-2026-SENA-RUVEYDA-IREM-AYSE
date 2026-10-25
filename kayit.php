<?php
require_once __DIR__ . "/baglanti.php";

$mesaj = "";
$adsoyad = "";
$email = "";
$telefon = "";

function guvenli($deger)
{
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $adsoyad = is_string($_POST["adsoyad"] ?? null)
        ? trim($_POST["adsoyad"])
        : "";

    $email = is_string($_POST["email"] ?? null)
        ? trim($_POST["email"])
        : "";

    $telefon = is_string($_POST["telefon"] ?? null)
        ? trim($_POST["telefon"])
        : "";

    $sifre = is_string($_POST["sifre"] ?? null)
        ? $_POST["sifre"]
        : "";

    if ($adsoyad === "" || $sifre === "") {

        $mesaj = "Lütfen tüm alanları doldurunuz.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $mesaj = "Lütfen geçerli bir e-posta adresi giriniz.";

    } elseif (!preg_match('/\A05[0-9]{9}\z/', $telefon)) {

        $mesaj = "Telefon numarası 05 ile başlamalı ve 11 rakamdan oluşmalıdır.";

    } else {

        try {
            $kontrol = mysqli_prepare(
                $baglanti,
                "SELECT id FROM uyeler WHERE email = ? LIMIT 1"
            );

            mysqli_stmt_bind_param($kontrol, "s", $email);
            mysqli_stmt_execute($kontrol);

            $sonuc = mysqli_stmt_get_result($kontrol);
            $email_kayitli = mysqli_num_rows($sonuc) > 0;

            mysqli_stmt_close($kontrol);

            if ($email_kayitli) {

                $mesaj = "Bu e-posta adresi zaten kullanılıyor.";

            } else {

                $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);

                $sorgu = mysqli_prepare(
                    $baglanti,
                    "INSERT INTO uyeler
                        (ad_soyad, email, telefon, sifre)
                     VALUES (?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $sorgu,
                    "ssss",
                    $adsoyad,
                    $email,
                    $telefon,
                    $sifre_hash
                );

                $kaydedildi = mysqli_stmt_execute($sorgu);
                mysqli_stmt_close($sorgu);

                if ($kaydedildi) {
                    header("Location: giris.php");
                    exit();
                }

                $mesaj = "Kayıt oluşturulamadı. Lütfen tekrar deneyiniz.";
            }

        } catch (mysqli_sql_exception $hata) {

            $mesaj = (int)$hata->getCode() === 1062
                ? "Bu bilgilerle zaten bir hesap bulunuyor."
                : "Kayıt oluşturulamadı. Lütfen tekrar deneyiniz.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kayıt Ol | Aris Pati Yuvası</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            background: #f5f0e6;
            color: #284b32;
            font-family: Arial, sans-serif;
        }

        .kayit-ust {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            padding: 16px 5%;
            border-bottom: 1px solid #ded6c6;
            background: #fffdf8;
        }

        .kayit-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: #284b32;
            text-decoration: none;
        }

        .kayit-logo img {
            display: block;
            flex-shrink: 0;
            width: 48px;
            height: 48px;
            object-fit: contain;
        }

        .kayit-logo strong {
            display: block;
            font-size: 19px;
        }

        .kayit-logo small {
            display: block;
            margin-top: 5px;
            color: #687361;
            font-size: 11px;
        }

        .kayit-menu {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 6px;
        }

        .kayit-menu a {
            padding: 10px 13px;
            border-radius: 8px;
            color: #284b32;
            font-size: 14px;
            text-decoration: none;
        }

        .kayit-menu a:hover {
            background: #e6eddf;
        }

        .kayit-alani {
            display: flex;
            flex: 1;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .kayit-kart {
            width: 100%;
            max-width: 420px;
            padding: 34px;
            border: 1px solid #ded6c6;
            border-radius: 18px;
            background: #fffdf8;
            text-align: center;
            box-shadow: 0 10px 30px rgba(40, 75, 50, 0.08);
        }

        .kayit-kart-logo {
            display: block;
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            object-fit: contain;
        }

        .kayit-kart h1 {
            margin: 0 0 12px;
            font-size: 27px;
        }

        .kayit-aciklama {
            margin: 8px 0 26px;
            color: #687361;
            font-size: 13px;
            line-height: 1.6;
        }

        .kayit-kart form {
            display: flex;
            flex-direction: column;
            text-align: left;
        }

        .kayit-kart label {
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: bold;
        }

        .kayit-kart input {
            width: 100%;
            margin-bottom: 20px;
            padding: 13px 14px;
            border: 1px solid #d6d9c9;
            border-radius: 9px;
            background: #fff;
            color: #284b32;
            font: inherit;
        }

        .kayit-kart input:focus-visible {
            outline: 2px solid #426b46;
            outline-offset: 2px;
        }

        #telefon {
            margin-bottom: 8px;
        }

        #telefon-aciklama {
            margin-bottom: 20px;
            color: #687361;
            font-size: 12px;
            line-height: 1.5;
        }

        .kayit-buton {
            width: 100%;
            margin-top: 4px;
            padding: 14px;
            border: 0;
            border-radius: 9px;
            background: #426b46;
            color: #fff;
            font: inherit;
            font-weight: bold;
            cursor: pointer;
        }

        .kayit-buton:hover {
            background: #284b32;
        }

        a:focus-visible,
        button:focus-visible {
            outline: 2px solid #426b46;
            outline-offset: 3px;
        }

        .kayit-mesaj {
            margin: 0 0 20px;
            padding: 12px;
            border: 1px solid #edccc7;
            border-radius: 8px;
            background: #f9ecea;
            color: #a63c35;
            font-size: 14px;
            line-height: 1.5;
        }

        .kayit-giris {
            margin: 24px 0 0;
            color: #687361;
            font-size: 14px;
            line-height: 1.6;
        }

        .kayit-giris a {
            color: #426b46;
            font-weight: bold;
            text-decoration: none;
        }

        .kayit-giris a:hover {
            text-decoration: underline;
        }

        .kayit-alt {
            padding: 20px;
            color: #687361;
            font-size: 12px;
            line-height: 1.6;
            text-align: center;
        }

        @media (max-width: 650px) {
            .kayit-ust {
                justify-content: center;
                padding: 16px;
            }

            .kayit-menu {
                width: 100%;
            }

            .kayit-menu a {
                padding: 8px 10px;
                font-size: 13px;
            }

            .kayit-kart {
                padding: 28px 22px;
            }

            .kayit-kart h1 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

    <header class="kayit-ust">
        <a href="index.php" class="kayit-logo">
            <img
                src="images/aris-logoo.png"
                alt="Aris Pati Yuvası logosu"
                width="48"
                height="48"
            >

            <span>
                <strong>Aris Pati Yuvası</strong>
                <small>Her can dostuna bir yuva</small>
            </span>
        </a>

        <nav class="kayit-menu" aria-label="Site menüsü">
            <a href="index.php">Ana Sayfa</a>
            <a href="ilanlar.php">İlanlar</a>
            <a href="ilan-ver.php">İlan Ver</a>
            <a href="hakkimizda.php">Hakkımızda</a>
            <a href="iletisim.php">İletişim</a>
            <a href="giris.php">Giriş Yap</a>
        </nav>
    </header>

    <main class="kayit-alani">

        <section class="kayit-kart" aria-labelledby="kayit-baslik">

            <img
                src="images/aris-logoo.png"
                alt=""
                class="kayit-kart-logo"
                width="80"
                height="80"
            >

            <h1 id="kayit-baslik">Aramıza Katıl!</h1>

            <p class="kayit-aciklama">
                Aris Pati Yuvası ailesine katılmak için
                bilgilerinizi doldurun.
            </p>

            <?php if ($mesaj !== ""): ?>
                <p class="kayit-mesaj" role="alert">
                    <?php echo guvenli($mesaj); ?>
                </p>
            <?php endif; ?>

            <form action="kayit.php" method="POST">

                <label for="adsoyad">Ad Soyad</label>
                <input
                    type="text"
                    id="adsoyad"
                    name="adsoyad"
                    placeholder="Adınızı ve soyadınızı giriniz"
                    maxlength="100"
                    autocomplete="name"
                    value="<?php echo guvenli($adsoyad); ?>"
                    required
                >

                <label for="email">E-posta Adresi</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="E-posta adresinizi giriniz"
                    maxlength="150"
                    autocomplete="email"
                    value="<?php echo guvenli($email); ?>"
                    required
                >

                <label for="telefon">Telefon Numarası</label>
                <input
                    type="tel"
                    id="telefon"
                    name="telefon"
                    placeholder="05XXXXXXXXX"
                    inputmode="numeric"
                    autocomplete="tel-national"
                    minlength="11"
                    maxlength="11"
                    pattern="05[0-9]{9}"
                    title="Telefon numarası 05 ile başlamalı ve 11 rakamdan oluşmalıdır."
                    aria-describedby="telefon-aciklama"
                    value="<?php echo guvenli($telefon); ?>"
                    required
                >

                <small id="telefon-aciklama">
                    Başındaki 0 dahil, boşluksuz 11 rakam giriniz.
                </small>

                <label for="sifre">Şifre</label>
                <input
                    type="password"
                    id="sifre"
                    name="sifre"
                    placeholder="Şifrenizi oluşturunuz"
                    autocomplete="new-password"
                    required
                >

                <button
                    type="submit"
                    name="kayit"
                    value="1"
                    class="kayit-buton"
                >
                    Kayıt Ol
                </button>

            </form>

            <p class="kayit-giris">
                Zaten hesabınız var mı?
                <a href="giris.php">Giriş Yap</a>
            </p>

        </section>

    </main>

    <footer class="kayit-alt">
        © 2026 Aris Pati Yuvası · Her can dostu sevgi dolu bir yuvayı hak eder.
    </footer>

</body>
</html>