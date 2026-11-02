<?php
session_start();
require_once __DIR__ . "/site-ust-ayar.php";

require_once __DIR__ . "/baglanti.php";

/* Dil tercihi */
$post_istegi = $_SERVER["REQUEST_METHOD"] === "POST";

$istenen_dil = $post_istegi
    ? ($_POST["dil_secimi"] ?? null)
    : ($_GET["lang"] ?? null);

$dil_degisti = is_string($istenen_dil)
    && in_array($istenen_dil, ["tr", "en"], true);

if ($dil_degisti) {
    $_SESSION["dil"] = $istenen_dil;

    if (!$post_istegi) {
        header("Location: kayit.php");
        exit();
    }
}

$dil = in_array($_SESSION["dil"] ?? "", ["tr", "en"], true)
    ? $_SESSION["dil"]
    : "tr";

function guvenli($deger)
{
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

function ceviri($tr, $en)
{
    global $dil;

    return $dil === "en" ? $en : $tr;
}

function form_degeri($alan)
{
    return is_string($_POST[$alan] ?? null)
        ? trim($_POST[$alan])
        : "";
}

$mesaj = "";
$adsoyad = form_degeri("adsoyad");
$email = form_degeri("email");
$telefon = form_degeri("telefon");

if (
    !isset($_SESSION["kayit_token"])
    || !is_string($_SESSION["kayit_token"])
    || $_SESSION["kayit_token"] === ""
) {
    $_SESSION["kayit_token"] = bin2hex(random_bytes(32));
}

/* Dil değiştirme isteği hesap oluşturmaz. */
if ($post_istegi && !$dil_degisti) {
    $sifre = is_string($_POST["sifre"] ?? null)
        ? $_POST["sifre"]
        : "";

    $token = $_POST["token"] ?? "";

    if (
        !is_string($token)
        || !hash_equals($_SESSION["kayit_token"], $token)
    ) {
        $mesaj = ceviri(
            "Sayfayı yenileyip tekrar deneyiniz.",
            "Please refresh the page and try again."
        );
    } elseif ($adsoyad === "" || $sifre === "") {
        $mesaj = ceviri(
            "Lütfen tüm alanları doldurunuz.",
            "Please complete all fields."
        );
    } elseif (
        mb_strlen($adsoyad, "UTF-8") > 100
        || mb_strlen($email, "UTF-8") > 150
    ) {
        $mesaj = ceviri(
            "Ad soyad veya e-posta adresi izin verilen uzunluğu aşıyor.",
            "Your full name or email address exceeds the allowed length."
        );
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mesaj = ceviri(
            "Lütfen geçerli bir e-posta adresi giriniz.",
            "Please enter a valid email address."
        );
    } elseif (!preg_match('/\A05[0-9]{9}\z/', $telefon)) {
        $mesaj = ceviri(
            "Telefon numarası 05 ile başlamalı ve 11 rakamdan oluşmalıdır.",
            "The phone number must start with 05 and contain 11 digits."
        );
    } else {
        $kontrol = null;
        $sorgu = null;

        try {
            $kontrol = mysqli_prepare(
                $baglanti,
                "SELECT id FROM uyeler WHERE email = ? LIMIT 1"
            );

            if (!$kontrol) {
                $mesaj = ceviri(
                    "Kayıt oluşturulamadı. Lütfen tekrar deneyiniz.",
                    "Your account could not be created. Please try again."
                );
            } else {
                mysqli_stmt_bind_param($kontrol, "s", $email);
                mysqli_stmt_execute($kontrol);

                $sonuc = mysqli_stmt_get_result($kontrol);
                $email_kayitli = mysqli_num_rows($sonuc) > 0;

                mysqli_stmt_close($kontrol);
                $kontrol = null;

                if ($email_kayitli) {
                    $mesaj = ceviri(
                        "Bu e-posta adresi zaten kullanılıyor.",
                        "This email address is already in use."
                    );
                } else {
                    $sifre_hash = password_hash(
                        $sifre,
                        PASSWORD_DEFAULT
                    );

                    $sorgu = mysqli_prepare(
                        $baglanti,
                        "INSERT INTO uyeler
                            (ad_soyad, email, telefon, sifre)
                         VALUES (?, ?, ?, ?)"
                    );

                    $kaydedildi = false;

                    if ($sorgu) {
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
                        $sorgu = null;
                    }

                    if ($kaydedildi) {
                        unset($_SESSION["kayit_token"]);

                        header("Location: giris.php");
                        exit();
                    }

                    $mesaj = ceviri(
                        "Kayıt oluşturulamadı. Lütfen tekrar deneyiniz.",
                        "Your account could not be created. Please try again."
                    );
                }
            }
        } catch (mysqli_sql_exception $hata) {
            $mesaj = (int)$hata->getCode() === 1062
                ? ceviri(
                    "Bu bilgilerle zaten bir hesap bulunuyor.",
                    "An account with these details already exists."
                )
                : ceviri(
                    "Kayıt oluşturulamadı. Lütfen tekrar deneyiniz.",
                    "Your account could not be created. Please try again."
                );
        } finally {
            if ($kontrol instanceof mysqli_stmt) {
                mysqli_stmt_close($kontrol);
            }

            if ($sorgu instanceof mysqli_stmt) {
                mysqli_stmt_close($sorgu);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= guvenli($dil) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= guvenli(ceviri(
        "Kayıt Ol | Aris Pati Yuvası",
        "Sign Up | Aris Pati Yuvası"
    )) ?></title>

    <style>
        * {
            box-sizing: border-box;
        }

        html {
            scrollbar-gutter: stable;
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

        .ust-sag {
            display: flex;
            flex: 1;
            min-width: 0;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
        }

        .dil-secici {
            display: inline-flex;
            gap: 4px;
            padding: 3px;
            border: 1px solid #cbd7c3;
            border-radius: 8px;
        }

        .dil-secici button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 37px;
            min-height: 30px;
            margin: 0;
            padding: 5px 9px;
            border: 0;
            border-radius: 5px;
            background: transparent;
            color: #284b32;
            font: bold 12px Arial, sans-serif;
            cursor: pointer;
        }

        .dil-secici button:hover {
            background: #e6eddf;
        }

        .dil-secici button[aria-current="true"] {
            background: #426b46;
            color: white;
        }

        .kayit-menu {
            display: flex;
            align-items: center;
            justify-content: flex-end;
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
            box-shadow: 0 10px 30px rgba(40,75,50,.08);
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

        .kayit-kart input:not([type="hidden"]) {
            width: 100%;
            margin-bottom: 20px;
            padding: 13px 14px;
            border: 1px solid #d6d9c9;
            border-radius: 9px;
            background: white;
            color: #284b32;
            font: inherit;
        }

        .kayit-kart input:focus-visible {
            outline: 2px solid #426b46;
            outline-offset: 2px;
        }

        .kayit-kart #telefon {
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
            color: white;
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

        .kayit-bilgi {
            margin: 0 0 20px;
            padding: 12px;
            border: 1px solid #c8d9bc;
            border-radius: 8px;
            background: #eaf2e4;
            color: #35583b;
            font-size: 13px;
            line-height: 1.6;
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

        @media (max-width: 1100px) {
            .ust-sag {
                flex-basis: 100%;
            }

            .kayit-menu {
                width: 100%;
            }
        }

        @media (max-width: 650px) {
            .kayit-ust {
                padding: 16px;
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
<link rel="stylesheet" href="site-ust.css?v=20261102-1">
</head>

<body>

<?php require __DIR__ . "/site-ust.php"; ?>

<main class="kayit-alani">
    <section class="kayit-kart" aria-labelledby="kayit-baslik">
        <img
            src="images/aris-logoo.png"
            alt=""
            class="kayit-kart-logo"
            width="80"
            height="80"
        >

        <h1 id="kayit-baslik">
            <?= guvenli(ceviri("Aramıza Katıl!", "Join Us!")) ?>
        </h1>

        <p class="kayit-aciklama">
            <?= guvenli(ceviri(
                "Aris Pati Yuvası ailesine katılmak için bilgilerinizi doldurun.",
                "Complete your details to join the Aris Pati Yuvası family."
            )) ?>
        </p>

        <?php if ($mesaj !== ""): ?>
            <p class="kayit-mesaj" role="alert">
                <?= guvenli($mesaj) ?>
            </p>
        <?php endif; ?>

        <?php if ($dil_degisti && $post_istegi): ?>
            <p class="kayit-bilgi" role="status">
                <?= guvenli(ceviri(
                    "Dil değiştirildi. Bilgileriniz korundu. Lütfen şifrenizi yeniden giriniz.",
                    "Language changed. Your details have been kept. Please enter your password again."
                )) ?>
            </p>
        <?php endif; ?>

        <form id="kayit-form" action="kayit.php" method="POST">
            <input
                type="hidden"
                name="token"
                value="<?= guvenli($_SESSION["kayit_token"]) ?>"
            >

            <label for="adsoyad">
                <?= guvenli(ceviri("Ad Soyad", "Full Name")) ?>
            </label>

            <input
                type="text"
                id="adsoyad"
                name="adsoyad"
                placeholder="<?= guvenli(ceviri(
                    "Adınızı ve soyadınızı giriniz",
                    "Enter your full name"
                )) ?>"
                maxlength="100"
                autocomplete="name"
                value="<?= guvenli($adsoyad) ?>"
                required
            >

            <label for="email">
                <?= guvenli(ceviri(
                    "E-posta Adresi",
                    "Email Address"
                )) ?>
            </label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="<?= guvenli(ceviri(
                    "E-posta adresinizi giriniz",
                    "Enter your email address"
                )) ?>"
                maxlength="150"
                autocomplete="email"
                value="<?= guvenli($email) ?>"
                required
            >

            <label for="telefon">
                <?= guvenli(ceviri(
                    "Telefon Numarası",
                    "Phone Number"
                )) ?>
            </label>

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
                title="<?= guvenli(ceviri(
                    "Telefon numarası 05 ile başlamalı ve 11 rakamdan oluşmalıdır.",
                    "The phone number must start with 05 and contain 11 digits."
                )) ?>"
                aria-describedby="telefon-aciklama"
                value="<?= guvenli($telefon) ?>"
                required
            >

            <small id="telefon-aciklama">
                <?= guvenli(ceviri(
                    "Başındaki 0 dahil, boşluksuz 11 rakam giriniz.",
                    "Enter 11 digits without spaces, including the initial 0."
                )) ?>
            </small>

            <label for="sifre">
                <?= guvenli(ceviri("Şifre", "Password")) ?>
            </label>

            <input
                type="password"
                id="sifre"
                name="sifre"
                placeholder="<?= guvenli(ceviri(
                    "Şifrenizi oluşturunuz",
                    "Create your password"
                )) ?>"
                autocomplete="new-password"
                required
            >

            <button
                type="submit"
                name="kayit"
                value="1"
                class="kayit-buton"
            >
                <?= guvenli(ceviri("Kayıt Ol", "Sign Up")) ?>
            </button>
        </form>

        <p class="kayit-giris">
            <?= guvenli(ceviri(
                "Zaten hesabınız var mı?",
                "Already have an account?"
            )) ?>

            <a href="giris.php">
                <?= guvenli(ceviri("Giriş Yap", "Sign In")) ?>
            </a>
        </p>
    </section>
</main>

<footer class="kayit-alt">
    © <?= date("Y") ?> Aris Pati Yuvası ·
    <?= guvenli(ceviri(
        "Her can dostu sevgi dolu bir yuvayı hak eder.",
        "Every companion deserves a loving home."
    )) ?>
</footer>

</body>
</html>