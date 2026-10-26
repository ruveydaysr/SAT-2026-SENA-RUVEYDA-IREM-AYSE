<?php
session_start();

require_once __DIR__ . "/baglanti.php";

function guvenli($deger)
{
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

$bildirim = "";
$basarili = false;

$ad = trim((string)($_SESSION["uye_adsoyad"] ?? ""));
$eposta = trim((string)($_SESSION["uye_email"] ?? ""));
$mesaj = "";

if (empty($_SESSION["iletisim_token"])) {
    $_SESSION["iletisim_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $ad = is_string($_POST["ad"] ?? null)
        ? trim($_POST["ad"])
        : "";

    $eposta = is_string($_POST["eposta"] ?? null)
        ? trim($_POST["eposta"])
        : "";

    $mesaj = is_string($_POST["mesaj"] ?? null)
        ? trim($_POST["mesaj"])
        : "";

    $token = $_POST["token"] ?? "";

    if (
        !is_string($token)
        || !hash_equals($_SESSION["iletisim_token"], $token)
    ) {
        $bildirim = "Sayfayı yenileyip tekrar deneyiniz.";
    } elseif ($ad === "" || $eposta === "" || $mesaj === "") {
        $bildirim = "Lütfen ad soyad, e-posta ve mesaj alanlarını doldurunuz.";
    } elseif (mb_strlen($ad, "UTF-8") > 100) {
        $bildirim = "Ad soyad en fazla 100 karakter olabilir.";
    } elseif (
        strlen($eposta) > 150
        || !filter_var($eposta, FILTER_VALIDATE_EMAIL)
    ) {
        $bildirim = "Lütfen geçerli bir e-posta adresi yazınız.";
    } elseif (mb_strlen($mesaj, "UTF-8") > 5000) {
        $bildirim = "Mesajınız en fazla 5000 karakter olabilir.";
    } else {
        $uye_id = isset($_SESSION["uye_id"])
            ? (int)$_SESSION["uye_id"]
            : null;

        $sorgu = null;

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
                    $bildirim = "Mesajınız yöneticiye gönderildi.";
                    $basarili = true;
                    $mesaj = "";

                    $_SESSION["iletisim_token"] = bin2hex(random_bytes(32));
                } else {
                    $bildirim = "Mesaj gönderilemedi. Lütfen tekrar deneyiniz.";
                }
            } else {
                $bildirim = "Mesaj gönderilemedi. Lütfen tekrar deneyiniz.";
            }
        } catch (mysqli_sql_exception $e) {
            $bildirim = "Mesaj gönderilemedi. Lütfen tekrar deneyiniz.";
        } finally {
            if ($sorgu instanceof mysqli_stmt) {
                mysqli_stmt_close($sorgu);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İletişim | Aris Pati Yuvası</title>

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

        /* Üst menü */

        .ilan-ust {
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

        .ilan-logo {
            display: flex;
            align-items: center;
            gap: 10px;
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

        .ilan-menu {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 4px;
            margin-left: auto;
        }

        .ilan-menu a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 10px;
            border-radius: 8px;
            color: white;
            font-size: 14px;
            line-height: 20px;
            text-decoration: none;
            white-space: nowrap;
        }

        .ilan-menu a:hover,
        .ilan-menu a:focus-visible {
            background: #284b32;
        }

        .ilan-menu a[aria-current="page"] {
            background: #eee5d3;
            color: #284b32;
            font-weight: bold;
        }

        /* İletişim formu */

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
            font: inherit;
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
            font: inherit;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-gonder:hover {
            background: #284b32;
        }

        .iletisim .bildirim {
            padding: 14px;
            border-radius: 8px;
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .iletisim .bildirim.basarili {
            background: #e6eddf;
            color: #284b32;
        }

        .iletisim .bildirim.hata {
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

        .ilan-menu a:focus-visible {
            outline-color: #eee5d3;
        }

        footer {
            padding: 0 20px 24px;
            color: #687361;
            font-size: 13px;
            text-align: center;
        }

        @media (max-width: 1100px) {
            .ilan-menu {
                width: 100%;
            }
        }

        @media (max-width: 700px) {
            .ilan-menu a {
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
    <style>
    html {
        scrollbar-gutter: stable;
    }

    #aris-ortak-ust,
    #aris-ortak-ust * {
        box-sizing: border-box;
    }

    html body > header#aris-ortak-ust {
        position: relative;
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 24px;
        width: 100%;
        min-height: 88px;
        margin: 0;
        padding: 16px 24px;
        border-bottom: 1px solid #35583b !important;
        background: #426b46 !important;
        text-align: left;
        overflow: visible;
    }

    #aris-ortak-ust > a.ilan-logo {
        display: inline-flex;
        align-items: center;
        justify-content: flex-start;
        gap: 12px;
        flex-shrink: 0;
        width: auto;
        margin: 0;
        padding: 0;
        background: transparent;
        color: #fff !important;
        text-decoration: none;
    }

    #aris-ortak-ust .ilan-logo img {
        display: block;
        flex-shrink: 0;
        width: 54px;
        height: 54px;
        margin: 0;
        padding: 5px;
        border: 1px solid #eee5d3;
        border-radius: 12px;
        background: #fffdf8;
        object-fit: contain;
    }

    #aris-ortak-ust .ilan-logo strong {
        display: block;
        margin: 0;
        color: #fff !important;
        font: bold 20px/1.3 Arial, sans-serif;
    }

    #aris-ortak-ust .ilan-logo small {
        display: block;
        margin: 5px 0 0;
        color: #fff !important;
        font: normal 11px/1.4 Arial, sans-serif;
    }

    #aris-ortak-ust > nav.ilan-menu {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 4px;
        width: auto;
        margin: 0 0 0 auto;
        padding: 0;
        background: transparent;
    }

    #aris-ortak-ust .ilan-menu > a,
    #aris-ortak-ust .kategori-baslik {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        flex: 0 0 auto;
        width: auto;
        min-height: 40px;
        margin: 0;
        padding: 10px;
        border: 0;
        border-radius: 8px;
        background: transparent !important;
        color: #fff !important;
        font: normal 14px/20px Arial, sans-serif;
        text-align: center;
        text-decoration: none;
        white-space: nowrap;
        cursor: pointer;
    }

    #aris-ortak-ust .ilan-menu > a:hover,
    #aris-ortak-ust .ilan-menu > a:focus-visible,
    #aris-ortak-ust .kategori-menu:hover .kategori-baslik,
    #aris-ortak-ust .kategori-menu:focus-within .kategori-baslik {
        background: #284b32 !important;
        color: #fff !important;
    }

    #aris-ortak-ust .ilan-menu > a[aria-current="page"],
    #aris-ortak-ust .ilan-menu > a.menu-aktif {
        background: #eee5d3 !important;
        color: #284b32 !important;
        font-weight: bold;
    }

    #aris-ortak-ust .ilan-menu > a:focus-visible,
    #aris-ortak-ust .kategori-baslik:focus-visible {
        outline: 2px solid #eee5d3;
        outline-offset: 3px;
    }

    #aris-ortak-ust .kategori-menu {
        position: relative;
        flex-shrink: 0;
        margin: 0;
        padding: 0;
    }

    #aris-ortak-ust .kategori-acilir {
        position: absolute;
        top: 100%;
        right: 0;
        z-index: 200;
        display: none;
        width: 205px;
        max-width: calc(100vw - 36px);
        margin: 0;
        padding: 8px;
        border: 1px solid #ded6c6;
        border-radius: 12px;
        background: #fffdf8;
        box-shadow: 0 12px 28px rgba(40, 75, 50, .16);
    }

    #aris-ortak-ust .kategori-menu:hover .kategori-acilir,
    #aris-ortak-ust .kategori-menu:focus-within .kategori-acilir {
        display: block;
    }

    #aris-ortak-ust .kategori-acilir a {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 10px;
        width: 100%;
        min-height: 40px;
        margin: 0;
        padding: 10px 12px;
        border: 0;
        border-radius: 7px;
        background: transparent !important;
        color: #284b32 !important;
        font: normal 14px/20px Arial, sans-serif;
        text-align: left;
        text-decoration: none;
        white-space: nowrap;
    }

    #aris-ortak-ust .kategori-acilir a:hover,
    #aris-ortak-ust .kategori-acilir a:focus-visible {
        background: #426b46 !important;
        color: #fff !important;
    }

    @media (max-width: 1100px) {
        #aris-ortak-ust > nav.ilan-menu {
            width: 100%;
            justify-content: flex-end;
        }
    }

    @media (max-width: 700px) {
        html body > header#aris-ortak-ust {
            gap: 16px;
            padding: 15px 18px;
        }

        #aris-ortak-ust .ilan-menu > a,
        #aris-ortak-ust .kategori-baslik {
            padding: 8px;
            font-size: 13px;
        }
    }
</style>
</head>

<body>
<?php
$aktif_sayfa = basename($_SERVER["SCRIPT_NAME"] ?? "");

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

$menu_baglantilari = [
    "ilan-ver.php" => "İlan Ver",
    "hakkimizda.php" => "Hakkımızda",
    "iletisim.php" => "İletişim"
];
?>

<header class="ilan-ust" id="aris-ortak-ust">
    <a href="index.php" class="ilan-logo">
        <img
            src="images/aris-logoo.png"
            alt="Aris Pati Yuvası logosu"
            width="54"
            height="54"
        >

        <span>
            <strong>Aris Pati Yuvası</strong>
            <small>Her can dostuna bir yuva</small>
        </span>
    </a>

    <nav class="ilan-menu" aria-label="Ana menü">
        <a
            href="index.php"
            <?= $aktif_sayfa === "index.php" ? 'aria-current="page"' : "" ?>
        >Ana Sayfa</a>

        <a
            href="ilanlar.php"
            <?= $aktif_sayfa === "ilanlar.php" ? 'aria-current="page"' : "" ?>
            <?= $aktif_sayfa === "ilan-detay.php" ? 'class="menu-aktif"' : "" ?>
        >İlanlar</a>

        <div class="kategori-menu">
            <button
                type="button"
                class="kategori-baslik"
                aria-controls="aris-kategori-listesi"
            >
                Hayvan Kategorileri
                <span aria-hidden="true">▾</span>
            </button>

            <div class="kategori-acilir" id="aris-kategori-listesi">
                <?php foreach ($menu_kategorileri as $kategori_adi => $kategori_simge): ?>
                    <a href="ilanlar.php?tur=<?= rawurlencode($kategori_adi) ?>">
                        <span aria-hidden="true"><?= $kategori_simge ?></span>
                        <span><?= htmlspecialchars(
                            $kategori_adi,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php foreach ($menu_baglantilari as $menu_dosyasi => $menu_yazisi): ?>
            <a
                href="<?= htmlspecialchars($menu_dosyasi, ENT_QUOTES, "UTF-8") ?>"
                <?= $aktif_sayfa === $menu_dosyasi ? 'aria-current="page"' : "" ?>
            ><?= htmlspecialchars($menu_yazisi, ENT_QUOTES, "UTF-8") ?></a>
        <?php endforeach; ?>

        <?php if ((int) ($_SESSION["uye_id"] ?? 0) > 0): ?>
            <a
                href="mesajlar.php"
                <?= $aktif_sayfa === "mesajlar.php" ? 'aria-current="page"' : "" ?>
            >Mesajlarım</a>

            <a
                href="profil.php"
                <?= $aktif_sayfa === "profil.php" ? 'aria-current="page"' : "" ?>
            >Profilim</a>

            <a
                href="cikis.php"
                onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');"
            >Çıkış Yap</a>
        <?php else: ?>
            <a
                href="giris.php"
                <?= $aktif_sayfa === "giris.php" ? 'aria-current="page"' : "" ?>
            >Giriş Yap</a>
        <?php endif; ?>

        <?php if (
            isset($_SESSION["admin"])
            && $aktif_sayfa === "ilan-detay.php"
        ): ?>
            <a href="admin/admin-panel.php">İlan Yönetimi</a>
        <?php endif; ?>
    </nav>
</header>
<main>
    <section class="iletisim">
        <h1>Bir Mesajınız mı Var? 🐾</h1>

        <p>
            Aris Pati Yuvası olarak sizlerden haber almak bizi mutlu eder!
            Minik dostlarımızla ilgili merak ettikleriniz,
            önerileriniz veya bizimle paylaşmak istediğiniz
            düşünceleriniz varsa buradayız.
        </p>

        <p>
            Üye olmadan da aşağıdaki formu doldurarak bize ulaşabilirsiniz.
            Her mesajınız bizim için değerli. 💚
        </p>

        <?php if ($bildirim !== ""): ?>
            <p
                class="bildirim <?php echo $basarili ? 'basarili' : 'hata'; ?>"
                role="<?php echo $basarili ? 'status' : 'alert'; ?>"
            >
                <?php echo guvenli($bildirim); ?>
            </p>
        <?php endif; ?>

        <form
            action="iletisim.php"
            method="POST"
            class="iletisim-formu"
        >
            <input
                type="hidden"
                name="token"
                value="<?php echo guvenli($_SESSION["iletisim_token"]); ?>"
            >

            <div class="form-grup">
                <label for="ad">Adınız Soyadınız:</label>
                <input
                    type="text"
                    id="ad"
                    name="ad"
                    value="<?php echo guvenli($ad); ?>"
                    autocomplete="name"
                    maxlength="100"
                    aria-describedby="ad-aciklama"
                    required
                >
                <small id="ad-aciklama">
                    Mesajınızda görünecek adınızı ve soyadınızı yazınız.
                </small>
            </div>

            <div class="form-grup">
                <label for="eposta">E-posta Adresiniz:</label>
                <input
                    type="email"
                    id="eposta"
                    name="eposta"
                    value="<?php echo guvenli($eposta); ?>"
                    autocomplete="email"
                    maxlength="150"
                    aria-describedby="eposta-aciklama"
                    required
                >
                <small id="eposta-aciklama">
                    Size ulaşılabilecek e-posta adresini yazınız.
                </small>
            </div>

            <div class="form-grup">
                <label for="mesaj">Mesajınız:</label>
                <textarea
                    id="mesaj"
                    name="mesaj"
                    rows="5"
                    maxlength="5000"
                    placeholder="Yöneticiye mesajınızı yazınız..."
                    required
                ><?php echo guvenli($mesaj); ?></textarea>
            </div>

            <button type="submit" class="btn-gonder">
                Mesajı Gönder
            </button>
        </form>
    </section>
</main>

<footer>
    <p>© 2026 Aris Pati Yuvası · Hayvan Sahiplendirme Platformu</p>
</footer>
</body>
</html>