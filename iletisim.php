<?php
session_start();
require_once __DIR__ . "/site-ust-ayar.php";

$post_istegi = $_SERVER["REQUEST_METHOD"] === "POST";

$istenen_dil = $post_istegi
    ? ($_POST["dil_secimi"] ?? null)
    : ($_GET["lang"] ?? null);

$dil_degisti = is_string($istenen_dil)
    && in_array($istenen_dil, ["tr", "en"], true);

if ($dil_degisti) {
    $_SESSION["dil"] = $istenen_dil;

    if (!$post_istegi) {
        header("Location: iletisim.php");
        exit();
    }
}

$dil = $_SESSION["dil"] ?? "tr";

if (!is_string($dil) || !in_array($dil, ["tr", "en"], true)) {
    $dil = "tr";
}

$_SESSION["dil"] = $dil;

require_once __DIR__ . "/baglanti.php";

/* Bu sayfaya özel adlar, diğer dosyalardaki fonksiyonlarla çakışmaz. */
function iletisim_guvenli($deger)
{
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

function iletisim_ceviri($turkce, $ingilizce)
{
    global $dil;
    return $dil === "en" ? $ingilizce : $turkce;
}

$kategoriler = [
    ["Kedi", "Cat", "🐱"],
    ["Köpek", "Dog", "🐶"],
    ["Kuş", "Bird", "🐦"],
    ["Balık", "Fish", "🐠"],
    ["Tavşan", "Rabbit", "🐰"],
    ["Kemirgen", "Rodent", "🐹"],
    ["Sürüngen", "Reptile", "🦎"],
    ["Diğer", "Other", "🐾"]
];

$bildirim = "";
$basarili = false;

$ad = is_string($_SESSION["uye_adsoyad"] ?? null)
    ? trim($_SESSION["uye_adsoyad"])
    : "";

$eposta = is_string($_SESSION["uye_email"] ?? null)
    ? trim($_SESSION["uye_email"])
    : "";

$mesaj = "";

if (
    !isset($_SESSION["iletisim_token"])
    || !is_string($_SESSION["iletisim_token"])
    || $_SESSION["iletisim_token"] === ""
) {
    $_SESSION["iletisim_token"] = bin2hex(random_bytes(32));
}

/* Dil değiştirildiğinde de formdaki bilgileri koru. */
if ($post_istegi) {
    $ad = is_string($_POST["ad"] ?? null)
        ? trim($_POST["ad"])
        : "";

    $eposta = is_string($_POST["eposta"] ?? null)
        ? trim($_POST["eposta"])
        : "";

    $mesaj = is_string($_POST["mesaj"] ?? null)
        ? trim($_POST["mesaj"])
        : "";
}

/* Dil seçimi mesaj gönderme işlemini çalıştırmaz. */
if ($post_istegi && !$dil_degisti) {
    $token = $_POST["token"] ?? "";

    if (
        !is_string($token)
        || !hash_equals($_SESSION["iletisim_token"], $token)
    ) {
        $bildirim = iletisim_ceviri(
            "Sayfayı yenileyip tekrar deneyiniz.",
            "Please refresh the page and try again."
        );
    } elseif ($ad === "" || $eposta === "" || $mesaj === "") {
        $bildirim = iletisim_ceviri(
            "Lütfen ad soyad, e-posta ve mesaj alanlarını doldurunuz.",
            "Please enter your full name, email address and message."
        );
    } elseif (mb_strlen($ad, "UTF-8") > 100) {
        $bildirim = iletisim_ceviri(
            "Ad soyad en fazla 100 karakter olabilir.",
            "Your full name cannot exceed 100 characters."
        );
    } elseif (
        strlen($eposta) > 150
        || !filter_var($eposta, FILTER_VALIDATE_EMAIL)
    ) {
        $bildirim = iletisim_ceviri(
            "Lütfen geçerli bir e-posta adresi yazınız.",
            "Please enter a valid email address."
        );
    } elseif (mb_strlen($mesaj, "UTF-8") > 5000) {
        $bildirim = iletisim_ceviri(
            "Mesajınız en fazla 5000 karakter olabilir.",
            "Your message cannot exceed 5,000 characters."
        );
    } else {
        $uye_id = isset($_SESSION["uye_id"])
            ? (int)$_SESSION["uye_id"]
            : null;

        $sorgu = null;

        $hata_mesaji = iletisim_ceviri(
            "Mesaj gönderilemedi. Lütfen tekrar deneyiniz.",
            "Your message could not be sent. Please try again."
        );

        try {
            $sorgu = mysqli_prepare(
                $baglanti,
                "INSERT INTO mesajlar
                 (uye_id, ad_soyad, email, mesaj)
                 VALUES (?, ?, ?, ?)"
            );

            if ($sorgu) {
                mysqli_stmt_bind_param(
                    $sorgu,
                    "isss",
                    $uye_id,
                    $ad,
                    $eposta,
                    $mesaj
                );

                if (mysqli_stmt_execute($sorgu)) {
                    $bildirim = iletisim_ceviri(
                        "Mesajınız yöneticiye gönderildi.",
                        "Your message has been sent to the administrator."
                    );

                    $basarili = true;
                    $mesaj = "";

                    $_SESSION["iletisim_token"] = bin2hex(random_bytes(32));
                } else {
                    $bildirim = $hata_mesaji;
                }
            } else {
                $bildirim = $hata_mesaji;
            }
        } catch (mysqli_sql_exception $e) {
            $bildirim = $hata_mesaji;
        } finally {
            if ($sorgu instanceof mysqli_stmt) {
                mysqli_stmt_close($sorgu);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo iletisim_guvenli($dil); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo iletisim_ceviri("İletişim", "Contact"); ?> | Aris Pati Yuvası</title>

    <style>
        * {
            box-sizing: border-box;
        }

        html {
            scrollbar-gutter: stable;
        }

        body {
            margin: 0;
            background: #f5f0e6;
            color: #284b32;
            font-family: Arial, sans-serif;
        }

        #aris-ortak-ust {
            position: relative;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 24px;
            min-height: 88px;
            padding: 16px 24px;
            background: #426b46;
            border-bottom: 1px solid #35583b;
        }

        #aris-ortak-ust .ilan-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            color: white;
            text-decoration: none;
        }

        #aris-ortak-ust .ilan-logo img {
            width: 54px;
            height: 54px;
            padding: 5px;
            border: 1px solid #eee5d3;
            border-radius: 12px;
            background: #fffdf8;
            object-fit: contain;
        }

        #aris-ortak-ust .ilan-logo strong {
            display: block;
            font-size: 20px;
            line-height: 1.3;
        }

        #aris-ortak-ust .ilan-logo small {
            display: block;
            margin-top: 5px;
            font-size: 11px;
            line-height: 1.4;
        }

        #aris-ortak-ust .ust-sag {
            display: flex;
            flex: 1;
            min-width: 0;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
        }

        #aris-ortak-ust .dil-secici {
            display: inline-flex;
            gap: 4px;
            padding: 3px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 8px;
        }

        #aris-ortak-ust .dil-secici button {
            min-width: 37px;
            min-height: 30px;
            padding: 5px 9px;
            border: 0;
            border-radius: 5px;
            background: transparent;
            color: white;
            font-family: inherit;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
        }

        #aris-ortak-ust .dil-secici button:hover {
            background: #35583b;
        }

        #aris-ortak-ust .dil-secici button.aktif {
            background: #eee5d3;
            color: #284b32;
        }

        #aris-ortak-ust .ilan-menu {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 4px;
            margin-left: auto;
        }

        #aris-ortak-ust .ilan-menu > a,
        #aris-ortak-ust .kategori-buton {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 40px;
            margin: 0;
            padding: 10px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: white;
            font: normal 14px/20px Arial, sans-serif;
            white-space: nowrap;
            text-decoration: none;
            cursor: pointer;
        }

        #aris-ortak-ust .ilan-menu > a:hover,
        #aris-ortak-ust .kategori-buton:hover {
            background: #35583b;
        }

        #aris-ortak-ust .ilan-menu > a[aria-current="page"] {
            background: #eee5d3;
            color: #284b32;
            font-weight: bold;
        }

        #aris-ortak-ust .kategori-menu {
            position: relative;
            flex-shrink: 0;
        }

        #aris-ortak-ust .kategori-acilir {
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 200;
            display: none;
            width: 205px;
            max-width: calc(100vw - 36px);
            padding: 8px;
            border: 1px solid #ded6c6;
            border-radius: 12px;
            background: #fffdf8;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.15);
        }

        #aris-ortak-ust .kategori-menu:hover .kategori-acilir,
        #aris-ortak-ust .kategori-menu:focus-within .kategori-acilir {
            display: block;
        }

        #aris-ortak-ust .kategori-acilir a {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 40px;
            padding: 10px 12px;
            border-radius: 7px;
            color: #284b32;
            font-size: 14px;
            line-height: 20px;
            text-decoration: none;
        }

        #aris-ortak-ust .kategori-acilir a:hover {
            background: #426b46;
            color: white;
        }

        main {
            width: min(650px, 92%);
            margin: 36px auto;
        }

        .iletisim {
            padding: 32px;
            border: 1px solid #ded6c6;
            border-radius: 15px;
            background: #fffdf8;
        }

        .iletisim h1 {
            margin: 0 0 22px;
            font-size: 28px;
            text-align: center;
        }

        .iletisim p {
            margin: 0 0 18px;
            color: #687361;
            font-size: 15px;
            line-height: 1.8;
        }

        .iletisim-formu {
            margin-top: 26px;
        }

        .form-grup {
            margin-bottom: 20px;
        }

        .form-grup label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: bold;
        }

        .form-grup input,
        .form-grup textarea {
            display: block;
            width: 100%;
            padding: 12px;
            border: 1px solid #ded6c6;
            border-radius: 8px;
            background: white;
            color: #284b32;
            font-family: inherit;
            font-size: 14px;
        }

        .form-grup textarea {
            min-height: 130px;
            resize: vertical;
        }

        .form-grup small {
            display: block;
            margin-top: 7px;
            color: #687361;
            font-size: 12px;
            line-height: 1.6;
        }

        .btn-gonder {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 43px;
            padding: 12px 25px;
            border: 0;
            border-radius: 8px;
            background: #426b46;
            color: white;
            font-family: inherit;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-gonder:hover {
            background: #35583b;
        }

        .iletisim .bildirim {
            padding: 14px;
            border-radius: 8px;
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .iletisim .basarili {
            background: #e6eddf;
            color: #284b32;
        }

        .iletisim .hata {
            background: #f9ecea;
            color: #a63c35;
        }

        a:focus-visible,
        button:focus-visible,
        input:focus-visible,
        textarea:focus-visible {
            outline: 2px solid #284b32;
            outline-offset: 3px;
        }

        #aris-ortak-ust a:focus-visible,
        #aris-ortak-ust button:focus-visible {
            outline-color: #eee5d3;
        }

        footer {
            padding: 0 20px 24px;
            color: #687361;
            font-size: 13px;
            text-align: center;
        }

        @media (max-width: 1100px) {
            #aris-ortak-ust .ust-sag {
                flex-basis: 100%;
            }

            #aris-ortak-ust .ilan-menu {
                width: 100%;
            }
        }

        @media (max-width: 700px) {
            #aris-ortak-ust {
                gap: 16px;
                padding: 15px 18px;
            }

            #aris-ortak-ust .ilan-menu > a,
            #aris-ortak-ust .kategori-buton {
                padding: 8px;
                font-size: 13px;
            }
        }

        @media (max-width: 600px) {
            main {
                margin: 24px auto;
            }

            .iletisim {
                padding: 24px 18px;
            }

            .iletisim h1 {
                font-size: 24px;
            }
        }
    </style>
<link rel="stylesheet" href="site-ust.css?v=20261102-1">
</head>

<body>

<?php require __DIR__ . "/site-ust.php"; ?>

<main>
    <section class="iletisim">
        <h1>
            <?php echo iletisim_ceviri(
                "Bir Mesajınız mı Var? 🐾",
                "Have a Message for Us? 🐾"
            ); ?>
        </h1>

        <p>
            <?php echo iletisim_ceviri(
                "Aris Pati Yuvası olarak sizlerden haber almak bizi mutlu eder! Minik dostlarımızla ilgili merak ettikleriniz, önerileriniz veya bizimle paylaşmak istediğiniz düşünceleriniz varsa buradayız.",
                "We love hearing from you at Aris Pati Yuvası! Whether you have questions about our animal friends, suggestions or thoughts you would like to share, we are here to listen."
            ); ?>
        </p>

        <p>
            <?php echo iletisim_ceviri(
                "Üye olmadan da aşağıdaki formu doldurarak bize ulaşabilirsiniz. Her mesajınız bizim için değerli. 💚",
                "You can contact us using the form below, even without an account. Every message matters to us. 💚"
            ); ?>
        </p>

        <?php if ($dil_degisti && $post_istegi): ?>
            <p class="bildirim basarili" role="status">
                <?php echo iletisim_ceviri(
                    "Dil değiştirildi. Form bilgileriniz korundu.",
                    "Language changed. Your form details have been kept."
                ); ?>
            </p>
        <?php endif; ?>

        <?php if ($bildirim !== ""): ?>
            <p
                class="bildirim <?php echo $basarili ? "basarili" : "hata"; ?>"
                role="<?php echo $basarili ? "status" : "alert"; ?>"
            >
                <?php echo iletisim_guvenli($bildirim); ?>
            </p>
        <?php endif; ?>

        <form
            action="iletisim.php"
            method="POST"
            class="iletisim-formu"
            id="iletisim-form"
        >
            <input
                type="hidden"
                name="token"
                value="<?php echo iletisim_guvenli(
                    $_SESSION["iletisim_token"]
                ); ?>"
            >

            <div class="form-grup">
                <label for="ad">
                    <?php echo iletisim_ceviri(
                        "Adınız Soyadınız:",
                        "Full Name:"
                    ); ?>
                </label>

                <input
                    type="text"
                    id="ad"
                    name="ad"
                    value="<?php echo iletisim_guvenli($ad); ?>"
                    autocomplete="name"
                    maxlength="100"
                    aria-describedby="ad-aciklama"
                    required
                >

                <small id="ad-aciklama">
                    <?php echo iletisim_ceviri(
                        "Mesajınızda görünecek adınızı ve soyadınızı yazınız.",
                        "Enter the full name that will appear with your message."
                    ); ?>
                </small>
            </div>

            <div class="form-grup">
                <label for="eposta">
                    <?php echo iletisim_ceviri(
                        "E-posta Adresiniz:",
                        "Email Address:"
                    ); ?>
                </label>

                <input
                    type="email"
                    id="eposta"
                    name="eposta"
                    value="<?php echo iletisim_guvenli($eposta); ?>"
                    autocomplete="email"
                    maxlength="150"
                    aria-describedby="eposta-aciklama"
                    required
                >

                <small id="eposta-aciklama">
                    <?php echo iletisim_ceviri(
                        "Size ulaşılabilecek e-posta adresini yazınız.",
                        "Enter an email address where we can reach you."
                    ); ?>
                </small>
            </div>

            <div class="form-grup">
                <label for="mesaj">
                    <?php echo iletisim_ceviri("Mesajınız:", "Your Message:"); ?>
                </label>

                <textarea
                    id="mesaj"
                    name="mesaj"
                    rows="5"
                    maxlength="5000"
                    placeholder="<?php echo iletisim_ceviri(
                        "Yöneticiye mesajınızı yazınız...",
                        "Write your message to the administrator..."
                    ); ?>"
                    required
                ><?php echo iletisim_guvenli($mesaj); ?></textarea>
            </div>

            <button type="submit" class="btn-gonder">
                <?php echo iletisim_ceviri("Mesajı Gönder", "Send Message"); ?>
            </button>
        </form>
    </section>
</main>

<footer>
    <p>
        © <?php echo date("Y"); ?> Aris Pati Yuvası ·
        <?php echo iletisim_ceviri(
            "Hayvan Sahiplendirme Platformu",
            "Pet Adoption Platform"
        ); ?>
    </p>
</footer>

</body>
</html>