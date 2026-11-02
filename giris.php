<?php
session_start();

function guvenli($deger)
{
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

function ceviri($tr, $en)
{
    global $dil;

    return $dil === "en" ? $en : $tr;
}

function tur_adi($tur)
{
    global $dil;

    $adlar = [
        "Kedi" => "Cat",
        "Köpek" => "Dog",
        "Kuş" => "Bird",
        "Balık" => "Fish",
        "Tavşan" => "Rabbit",
        "Kemirgen" => "Rodent",
        "Sürüngen" => "Reptile",
        "Diğer" => "Other"
    ];

    return $dil === "en" ? ($adlar[$tur] ?? $tur) : $tur;
}

/* Dil tercihi */
$post_istegi = $_SERVER["REQUEST_METHOD"] === "POST";

$istenen_dil = $post_istegi
    ? ($_POST["dil_secimi"] ?? null)
    : ($_GET["lang"] ?? null);

$dil_degisti = is_string($istenen_dil)
    && in_array($istenen_dil, ["tr", "en"], true);

if ($dil_degisti) {
    $_SESSION["dil"] = $istenen_dil;
}

$dil = in_array($_SESSION["dil"] ?? "", ["tr", "en"], true)
    ? $_SESSION["dil"]
    : "tr";

/* Giriş sonrasında dönülecek sayfa */
$redirect = $post_istegi
    ? ($_POST["redirect"] ?? "")
    : ($_GET["redirect"] ?? "");

$redirect = is_string($redirect) ? $redirect : "";
$hedef = "index.php";

/* Yalnızca yerel sahiplenme sayfasına dönüşe izin ver */
if ($redirect !== "") {
    $parcalar = parse_url($redirect);

    if (
        $parcalar !== false
        && !isset($parcalar["scheme"])
        && !isset($parcalar["host"])
        && !isset($parcalar["user"])
        && !isset($parcalar["pass"])
        && ($parcalar["path"] ?? "") === "sahiplen.php"
    ) {
        $parametreler = [];
        parse_str($parcalar["query"] ?? "", $parametreler);

        $gelen_ilan_id = $parametreler["ilan_id"] ?? null;

        $ilan_id = is_string($gelen_ilan_id) || is_int($gelen_ilan_id)
            ? filter_var($gelen_ilan_id, FILTER_VALIDATE_INT)
            : false;

        if ($ilan_id && $ilan_id > 0) {
            $hedef = "sahiplen.php?ilan_id=" . $ilan_id;
        }
    }
}

if ($dil_degisti && !$post_istegi) {
    $adres = "giris.php";

    if ($hedef !== "index.php") {
        $adres .= "?" . http_build_query(["redirect" => $hedef]);
    }

    header("Location: " . $adres);
    exit();
}

require_once __DIR__ . "/baglanti.php";

$hata = "";

$email = is_string($_POST["email"] ?? null)
    ? trim($_POST["email"])
    : "";

if (
    !isset($_SESSION["giris_token"])
    || !is_string($_SESSION["giris_token"])
    || $_SESSION["giris_token"] === ""
) {
    $_SESSION["giris_token"] = bin2hex(random_bytes(32));
}

/* Dil değiştirme isteği giriş işlemi yapmaz */
if (
    $post_istegi
    && isset($_POST["giris"])
    && !$dil_degisti
) {
    $sifre = is_string($_POST["sifre"] ?? null)
        ? $_POST["sifre"]
        : "";

    $token = $_POST["token"] ?? "";

    if (
        !is_string($token)
        || !hash_equals($_SESSION["giris_token"], $token)
    ) {
        $hata = ceviri(
            "Sayfayı yenileyip tekrar deneyiniz.",
            "Please refresh the page and try again."
        );
    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL)
        || $sifre === ""
    ) {
        $hata = ceviri(
            "Lütfen geçerli bir e-posta adresi ve şifre giriniz.",
            "Please enter a valid email address and password."
        );
    } else {
        $sql = "SELECT id, ad_soyad, email, sifre
                FROM uyeler
                WHERE email = ?
                LIMIT 1";

        $sorgu = mysqli_prepare($baglanti, $sql);

        mysqli_stmt_bind_param($sorgu, "s", $email);
        mysqli_stmt_execute($sorgu);

        $sonuc = mysqli_stmt_get_result($sorgu);
        $uye = mysqli_fetch_assoc($sonuc);

        mysqli_stmt_close($sorgu);

        $dogru = false;

        if ($uye) {
            $kayitli_sifre = (string)$uye["sifre"];

            $dogru = password_verify($sifre, $kayitli_sifre);

            /* Eski düz metin şifreler için geçiş desteği */
            $sifre_bilgisi = password_get_info($kayitli_sifre);

            if (
                !$dogru
                && ($sifre_bilgisi["algoName"] ?? "unknown") === "unknown"
            ) {
                $dogru = hash_equals($kayitli_sifre, $sifre);

                if ($dogru) {
                    $yeni_hash = password_hash(
                        $sifre,
                        PASSWORD_DEFAULT
                    );

                    $uye_id = (int)$uye["id"];

                    $guncelle = mysqli_prepare(
                        $baglanti,
                        "UPDATE uyeler SET sifre = ? WHERE id = ?"
                    );

                    mysqli_stmt_bind_param(
                        $guncelle,
                        "si",
                        $yeni_hash,
                        $uye_id
                    );

                    mysqli_stmt_execute($guncelle);
                    mysqli_stmt_close($guncelle);
                }
            }
        }

        if ($dogru && $uye) {
            session_regenerate_id(true);

            $_SESSION["uye_id"] = (int)$uye["id"];
            $_SESSION["uye_adsoyad"] = $uye["ad_soyad"];
            $_SESSION["uye_email"] = $uye["email"];

            unset($_SESSION["giris_token"]);

            header("Location: " . $hedef);
            exit();
        }

        $hata = ceviri(
            "E-posta veya şifre yanlış.",
            "Incorrect email address or password."
        );
    }
}

$menu_kategorileri = [
    "Kedi" => "🐱",
    "Köpek" => "🐶",
    "Kuş" => "🐦",
    "Balık" => "🐠",
    "Tavşan" => "🐰",
    "Kemirgen" => "🐹",
    "Sürüngen" => "🦎",
    "Diğer" => "🐾"
];
?>

<!DOCTYPE html>
<html lang="<?= guvenli($dil) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= guvenli(ceviri(
        "Giriş Yap | Aris Pati Yuvası",
        "Sign In | Aris Pati Yuvası"
    )) ?></title>

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

        input,
        button {
            font: inherit;
        }

        a:focus-visible,
        button:focus-visible {
            outline: 3px solid #9eb995;
            outline-offset: 3px;
        }

        .ilan-ust {
            position: relative;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 24px;
            min-height: 88px;
            padding: 16px 24px;
            border-bottom: 1px solid #35583b;
            background: #426b46;
        }

        .ilan-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            color: white;
            text-decoration: none;
        }

        .ilan-logo img {
            display: block;
            width: 54px;
            height: 54px;
            padding: 5px;
            border: 1px solid #eee5d3;
            border-radius: 12px;
            background: #fffdf8;
            object-fit: contain;
        }

        .ilan-logo strong {
            display: block;
            font-size: 20px;
            line-height: 1.3;
        }

        .ilan-logo small {
            display: block;
            margin-top: 5px;
            font-size: 11px;
            line-height: 1.4;
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
            border: 1px solid rgba(255,255,255,.35);
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
            color: white;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
        }

        .dil-secici button:hover {
            background: #284b32;
        }

        .dil-secici button[aria-current="true"] {
            background: #f5f0e6;
            color: #284b32;
        }

        .ilan-menu {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 4px;
            margin-left: auto;
        }

        .ilan-menu > a,
        .kategori-baslik {
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
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
        }

        .ilan-menu > a:hover,
        .ilan-menu > a:focus-visible,
        .kategori-menu:hover .kategori-baslik,
        .kategori-menu:focus-within .kategori-baslik {
            background: #284b32;
        }

        .ilan-menu > a[aria-current="page"] {
            background: #eee5d3;
            color: #284b32;
            font-weight: bold;
        }

        .kategori-menu {
            position: relative;
            flex-shrink: 0;
        }

        .kategori-acilir {
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
            box-shadow: 0 12px 28px rgba(40,75,50,.16);
        }

        .kategori-menu:hover .kategori-acilir,
        .kategori-menu:focus-within .kategori-acilir {
            display: block;
        }

        .kategori-acilir a {
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

        .kategori-acilir a:hover,
        .kategori-acilir a:focus-visible {
            background: #426b46;
            color: white;
        }

        .giris-alani {
            width: min(460px, 92%);
            margin: 55px auto;
        }

        .giris-kart {
            padding: 35px;
            border: 1px solid #ded6c6;
            border-radius: 16px;
            background: #fffdf8;
        }

        .giris-kart > img {
            display: block;
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            object-fit: contain;
        }

        .giris-kart h1 {
            margin: 0 0 12px;
            font-size: 28px;
            line-height: 1.4;
            text-align: center;
        }

        .giris-aciklama {
            margin: 0 0 25px;
            color: #687361;
            font-size: 14px;
            line-height: 1.7;
            text-align: center;
        }

        .giris-kart label {
            display: block;
            margin: 18px 0 8px;
            font-size: 14px;
            font-weight: bold;
        }

        .giris-kart input:not([type="hidden"]) {
            display: block;
            width: 100%;
            min-height: 45px;
            padding: 12px;
            border: 1px solid #d6d0c2;
            border-radius: 8px;
            background: white;
            color: #284b32;
            font-size: 14px;
        }

        .giris-kart input:focus {
            border-color: #426b46;
            outline: 2px solid #d5e2cd;
            outline-offset: 1px;
        }

        .giris-buton {
            display: block;
            width: 100%;
            min-height: 45px;
            margin-top: 25px;
            padding: 12px;
            border: 0;
            border-radius: 8px;
            background: #426b46;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .giris-buton:hover {
            background: #284b32;
        }

        .giris-hata {
            padding: 13px 15px;
            border: 1px solid #e1bdb5;
            border-radius: 8px;
            background: #fff0ec;
            color: #8c3931;
            font-size: 14px;
            line-height: 1.6;
        }

        .giris-bilgi {
            padding: 13px 15px;
            border: 1px solid #c8d9bc;
            border-radius: 8px;
            background: #eaf2e4;
            color: #35583b;
            font-size: 13px;
            line-height: 1.6;
        }

        .giris-kayit {
            margin: 24px 0 0;
            color: #687361;
            font-size: 14px;
            line-height: 1.8;
            text-align: center;
        }

        .giris-kayit a {
            color: #426b46;
            font-weight: bold;
            text-decoration: none;
        }

        .giris-kayit a:hover {
            text-decoration: underline;
        }

        .giris-alt {
            padding: 20px;
            color: #687361;
            font-size: 12px;
            line-height: 1.7;
            text-align: center;
        }

        @media (max-width: 1100px) {
            .ust-sag {
                flex-basis: 100%;
            }

            .ilan-menu {
                width: 100%;
            }
        }

        @media (max-width: 700px) {
            .ilan-ust {
                gap: 16px;
                padding: 15px 18px;
            }

            .ilan-menu > a,
            .kategori-baslik {
                padding: 8px;
                font-size: 13px;
            }

            .giris-alani {
                margin: 30px auto;
            }

            .giris-kart {
                padding: 25px;
            }

            .giris-kart h1 {
                font-size: 25px;
            }
        }
    </style>
</head>

<body class="giris-sayfasi">

<header class="ilan-ust" id="aris-ortak-ust">
    <a href="index.php" class="ilan-logo">
        <img
            src="images/aris-logoo.png"
            width="54"
            height="54"
            alt="<?= guvenli(ceviri(
                "Aris Pati Yuvası logosu",
                "Aris Pati Yuvası logo"
            )) ?>"
        >

        <span>
            <strong>Aris Pati Yuvası</strong>

            <small>
                <?= guvenli(ceviri(
                    "Her can dostuna bir yuva",
                    "A home for every companion"
                )) ?>
            </small>
        </span>
    </a>

    <div class="ust-sag">
        <nav
            class="dil-secici"
            aria-label="<?= guvenli(ceviri(
                "Dil seçimi",
                "Language selection"
            )) ?>"
        >
            <button
                type="submit"
                form="giris-form"
                name="dil_secimi"
                value="tr"
                formnovalidate
                lang="tr"
                aria-label="Türkçe"
                <?= $dil === "tr" ? 'aria-current="true"' : "" ?>
            >TR</button>

            <button
                type="submit"
                form="giris-form"
                name="dil_secimi"
                value="en"
                formnovalidate
                lang="en"
                aria-label="English"
                <?= $dil === "en" ? 'aria-current="true"' : "" ?>
            >EN</button>
        </nav>

        <nav
            class="ilan-menu"
            aria-label="<?= guvenli(ceviri(
                "Ana menü",
                "Main navigation"
            )) ?>"
        >
            <a href="index.php">
                <?= guvenli(ceviri("Ana Sayfa", "Home")) ?>
            </a>

            <a href="ilanlar.php">
                <?= guvenli(ceviri("İlanlar", "Listings")) ?>
            </a>

            <div class="kategori-menu">
                <button
                    type="button"
                    class="kategori-baslik"
                    aria-controls="aris-kategori-listesi"
                >
                    <?= guvenli(ceviri(
                        "Hayvan Kategorileri",
                        "Animal Categories"
                    )) ?>

                    <span aria-hidden="true">▾</span>
                </button>

                <div class="kategori-acilir" id="aris-kategori-listesi">
                    <?php foreach ($menu_kategorileri as $ad => $simge): ?>
                        <a href="ilanlar.php?tur=<?= rawurlencode($ad) ?>">
                            <span aria-hidden="true"><?= $simge ?></span>
                            <span><?= guvenli(tur_adi($ad)) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <a href="ilan-ver.php">
                <?= guvenli(ceviri("İlan Ver", "Post a Listing")) ?>
            </a>

            <a href="blog.php">Blog</a>

            <a href="hakkimizda.php">
                <?= guvenli(ceviri("Hakkımızda", "About Us")) ?>
            </a>

            <a href="iletisim.php">
                <?= guvenli(ceviri("İletişim", "Contact")) ?>
            </a>

            <?php if ((int)($_SESSION["uye_id"] ?? 0) > 0): ?>
                <a href="mesajlar.php">
                    <?= guvenli(ceviri("Mesajlarım", "Messages")) ?>
                </a>

                <a href="profil.php">
                    <?= guvenli(ceviri("Profilim", "My Profile")) ?>
                </a>

                <a
                    href="cikis.php"
                    onclick="return confirm(<?= guvenli(json_encode(
                        ceviri(
                            "Çıkış yapmak istediğinize emin misiniz?",
                            "Are you sure you want to sign out?"
                        ),
                        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                    )) ?>);"
                >
                    <?= guvenli(ceviri("Çıkış Yap", "Sign Out")) ?>
                </a>
            <?php else: ?>
                <a href="giris.php" aria-current="page">
                    <?= guvenli(ceviri("Giriş Yap", "Sign In")) ?>
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="giris-alani">
    <div class="giris-kart">
        <img
            src="images/aris-logoo.png"
            alt="<?= guvenli(ceviri(
                "Aris Pati Yuvası logosu",
                "Aris Pati Yuvası logo"
            )) ?>"
        >

        <h1>
            <?= guvenli(ceviri(
                "Tekrar Hoş Geldiniz!",
                "Welcome Back!"
            )) ?>
        </h1>

        <p class="giris-aciklama">
            <?= guvenli(ceviri(
                "Aris Pati Yuvası hesabınıza giriş yapın.",
                "Sign in to your Aris Pati Yuvası account."
            )) ?>
        </p>

        <?php if ($hata !== ""): ?>
            <p class="giris-hata" role="alert">
                <?= guvenli($hata) ?>
            </p>
        <?php endif; ?>

        <?php if ($dil_degisti && $post_istegi): ?>
            <p class="giris-bilgi" role="status">
                <?= guvenli(ceviri(
                    "Dil değiştirildi. Lütfen şifrenizi yeniden giriniz.",
                    "Language changed. Please enter your password again."
                )) ?>
            </p>
        <?php endif; ?>

        <form id="giris-form" action="giris.php" method="POST">
            <input
                type="hidden"
                name="redirect"
                value="<?= guvenli($hedef) ?>"
            >

            <input
                type="hidden"
                name="token"
                value="<?= guvenli($_SESSION["giris_token"]) ?>"
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
                autocomplete="username"
                placeholder="<?= guvenli(ceviri(
                    "E-posta adresinizi giriniz",
                    "Enter your email address"
                )) ?>"
                value="<?= guvenli($email) ?>"
                required
            >

            <label for="sifre">
                <?= guvenli(ceviri("Şifre", "Password")) ?>
            </label>

            <input
                type="password"
                id="sifre"
                name="sifre"
                autocomplete="current-password"
                placeholder="<?= guvenli(ceviri(
                    "Şifrenizi giriniz",
                    "Enter your password"
                )) ?>"
                required
            >

            <button
                type="submit"
                name="giris"
                value="1"
                class="giris-buton"
            >
                <?= guvenli(ceviri("Giriş Yap", "Sign In")) ?>
            </button>
        </form>

        <p class="giris-kayit">
            <?= guvenli(ceviri(
                "Henüz hesabınız yok mu?",
                "Don't have an account yet?"
            )) ?>

            <a href="kayit.php">
                <?= guvenli(ceviri("Kayıt Ol", "Sign Up")) ?>
            </a>
        </p>
    </div>
</main>

<footer class="giris-alt">
    © <?= date("Y") ?> Aris Pati Yuvası ·
    <?= guvenli(ceviri(
        "Her can dostu sevgi dolu bir yuvayı hak eder.",
        "Every companion deserves a loving home."
    )) ?>
</footer>

</body>
</html>