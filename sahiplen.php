<?php
session_start();
require_once __DIR__ . "/site-ust-ayar.php";

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

$ilan_id = filter_input(
    $post_istegi ? INPUT_POST : INPUT_GET,
    "ilan_id",
    FILTER_VALIDATE_INT
);

/* Giriş sonrası aynı ilana dön */
if ((int)($_SESSION["uye_id"] ?? 0) < 1) {
    $hedef = "sahiplen.php";

    if ($ilan_id && $ilan_id > 0) {
        $hedef .= "?ilan_id=" . $ilan_id;
    }

    header("Location: giris.php?redirect=" . urlencode($hedef));
    exit();
}

/* GET ile dil değiştirildiğinde temiz adrese dön */
if ($dil_degisti && !$post_istegi) {
    $adres = "sahiplen.php";

    if ($ilan_id && $ilan_id > 0) {
        $adres .= "?ilan_id=" . $ilan_id;
    }

    header("Location: " . $adres);
    exit();
}

require_once __DIR__ . "/baglanti.php";

$mesaj = "";
$basarili = false;
$ilan = null;

if (
    !isset($_SESSION["sahiplen_token"])
    || !is_string($_SESSION["sahiplen_token"])
    || $_SESSION["sahiplen_token"] === ""
) {
    $_SESSION["sahiplen_token"] = bin2hex(random_bytes(32));
}

if ($ilan_id && $ilan_id > 0) {
    $sql = "SELECT id, hayvan_adi, tur, yas, sehir
            FROM ilanlar
            WHERE id = ? AND durum = 'Onaylandı'
            LIMIT 1";

    $sorgu = mysqli_prepare($baglanti, $sql);

    mysqli_stmt_bind_param($sorgu, "i", $ilan_id);
    mysqli_stmt_execute($sorgu);

    $sonuc = mysqli_stmt_get_result($sorgu);
    $ilan = mysqli_fetch_assoc($sonuc);

    mysqli_stmt_close($sorgu);
}

if (!$ilan) {
    http_response_code(404);
}

/* Dil değiştirme isteği başvuru oluşturmaz */
if ($post_istegi && $ilan && !$dil_degisti) {
    $uye_id = (int)$_SESSION["uye_id"];
    $adsoyad = form_degeri("adsoyad");
    $telefon = form_degeri("telefon");
    $email = form_degeri("email");
    $ev = form_degeri("ev");
    $ek_bilgi = form_degeri("mesaj");

    $token = $_POST["token"] ?? "";

    if (
        !is_string($token)
        || !hash_equals($_SESSION["sahiplen_token"], $token)
    ) {
        $mesaj = ceviri(
            "Sayfayı yenileyip tekrar deneyiniz.",
            "Please refresh the page and try again."
        );
    } elseif (
        $adsoyad === ""
        || $telefon === ""
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || !in_array($ev, ["apartman", "mustakil"], true)
    ) {
        $mesaj = ceviri(
            "Lütfen bilgilerinizi doğru şekilde doldurunuz.",
            "Please complete your information correctly."
        );
    } else {
        $sorgu = null;

        try {
            $sql = "INSERT INTO sahiplenme_basvurulari
                    (
                        ilan_id, uye_id, adsoyad,
                        telefon, email, ev, mesaj, durum
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Bekliyor')";

            $sorgu = mysqli_prepare($baglanti, $sql);

            if (!$sorgu) {
                $mesaj = ceviri(
                    "Başvuru hazırlanırken bir hata oluştu.",
                    "An error occurred while preparing your application."
                );
            } else {
                mysqli_stmt_bind_param(
                    $sorgu,
                    "iisssss",
                    $ilan_id,
                    $uye_id,
                    $adsoyad,
                    $telefon,
                    $email,
                    $ev,
                    $ek_bilgi
                );

                if (mysqli_stmt_execute($sorgu)) {
                    $mesaj = ceviri(
                        "Sahiplenme başvurunuz başarıyla gönderildi.",
                        "Your adoption application has been submitted successfully."
                    );

                    $basarili = true;

                    $_SESSION["sahiplen_token"] = bin2hex(random_bytes(32));
                } else {
                    $mesaj = ceviri(
                        "Başvuru kaydedilirken bir hata oluştu.",
                        "An error occurred while saving your application."
                    );
                }
            }
        } catch (mysqli_sql_exception $e) {
            $mesaj = ceviri(
                "Başvuru kaydedilirken bir hata oluştu.",
                "An error occurred while saving your application."
            );
        } finally {
            if ($sorgu instanceof mysqli_stmt) {
                mysqli_stmt_close($sorgu);
            }
        }
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

function dil_linki($yeni_dil, $ilan_id)
{
    $parametreler = ["lang" => $yeni_dil];

    if ($ilan_id && $ilan_id > 0) {
        $parametreler["ilan_id"] = (int)$ilan_id;
    }

    return "sahiplen.php?" . http_build_query($parametreler);
}
?>

<!DOCTYPE html>
<html lang="<?= guvenli($dil) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= guvenli(ceviri(
        "Hayvan Sahiplen | Aris Pati Yuvası",
        "Apply to Adopt | Aris Pati Yuvası"
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
            background: #f5f8f5;
            color: #184f3c;
            font-family: Arial, sans-serif;
        }

        input,
        select,
        textarea,
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
            background: #426b46;
            border-bottom: 1px solid #35583b;
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
            object-fit: contain;
            border: 1px solid #eee5d3;
            border-radius: 12px;
            background: #fffdf8;
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

        .dil-secici a,
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
            text-decoration: none;
            cursor: pointer;
        }

        .dil-secici a:hover,
        .dil-secici button:hover {
            background: #284b32;
        }

        .dil-secici [aria-current="true"] {
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
        .kategori-menu:hover .kategori-baslik,
        .kategori-menu:focus-within .kategori-baslik {
            background: #284b32;
        }

        .ilan-menu > a.menu-aktif {
            background: #eee5d3;
            color: #284b32;
            font-weight: bold;
        }

        .kategori-menu {
            position: relative;
        }

        .kategori-acilir {
            position: absolute;
            top: 100%;
            right: 0;
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

        .basvuru-alani {
            width: min(650px, 92%);
            margin: 60px auto;
            padding: 40px;
            background: white;
            border: 1px solid #dce8df;
            border-radius: 15px;
        }

        .basvuru-alani h1 {
            margin: 0 0 25px;
            color: #184f3c;
            text-align: center;
            font-size: 28px;
            line-height: 1.4;
        }

        .basvuru-giris {
            color: #526b5a;
            text-align: center;
            line-height: 1.8;
        }

        .basvuru-alani label {
            display: block;
            margin-top: 18px;
            margin-bottom: 8px;
            color: #184f3c;
            font-weight: bold;
        }

        .basvuru-alani input,
        .basvuru-alani select,
        .basvuru-alani textarea {
            display: block;
            width: 100%;
            padding: 12px;
            border: 1px solid #cbded0;
            border-radius: 8px;
            background: white;
            color: #184f3c;
            font-size: 15px;
        }

        .basvuru-alani input:focus,
        .basvuru-alani select:focus,
        .basvuru-alani textarea:focus {
            outline: 2px solid #d5e2cd;
            border-color: #347a53;
        }

        .basvuru-alani textarea {
            resize: vertical;
            line-height: 1.6;
        }

        .basvuru-gonder {
            margin-top: 25px;
            padding: 12px 25px;
            background: #347a53;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
        }

        .basvuru-gonder:hover {
            background: #245c3d;
        }

        .hayvan-ozet {
            padding: 20px;
            background: #f5f8f5;
            border: 1px solid #dce8df;
            border-radius: 10px;
            margin: 25px 0;
        }

        .hayvan-ozet h2 {
            color: #184f3c;
            margin: 0 0 15px;
            overflow-wrap: anywhere;
        }

        .hayvan-ozet p {
            color: #526b5a;
            margin: 0 0 10px;
            line-height: 1.6;
        }

        .hayvan-ozet p:last-child {
            margin-bottom: 0;
        }

        .basvuru-mesaj {
            padding: 15px;
            background: #fff0ec;
            color: #8c3931;
            border: 1px solid #e1bdb5;
            border-radius: 8px;
            margin-bottom: 20px;
            line-height: 1.7;
        }

        .basvuru-mesaj.basarili {
            border-color: #c8d9bc;
            background: #eef5ee;
            color: #184f3c;
        }

        .basvuru-alani a {
            color: #347a53;
            text-decoration: none;
        }

        .basvuru-alani a:hover {
            text-decoration: underline;
        }

        .geri-alani {
            margin: 25px 0 0;
        }

        footer {
            padding: 20px;
            color: #526b5a;
            font-size: 12px;
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
                padding: 15px 18px;
                gap: 16px;
            }

            .ilan-menu > a,
            .kategori-baslik {
                padding: 8px;
                font-size: 13px;
            }

            .basvuru-alani {
                margin: 30px auto;
                padding: 24px;
            }

            .basvuru-alani h1 {
                font-size: 24px;
            }
        }
    </style>
<link rel="stylesheet" href="site-ust.css?v=20261102-1">
</head>

<body>

<?php require __DIR__ . "/site-ust.php"; ?>

<main>
    <section class="basvuru-alani">
        <h1>
            <?= guvenli(ceviri(
                "Bir Cana Yuva Olmaya Hazır mısınız?",
                "Ready to Give a Companion a Home?"
            )) ?> 🐾
        </h1>

        <p class="basvuru-giris">
            <?= guvenli(ceviri(
                "Minik dostlarımızdan birine sevgi dolu bir yuva açmak istemeniz bizi çok mutlu ediyor! Başvurunuzu tamamlamak için aşağıdaki bilgileri doldurmanız yeterli.",
                "We are happy that you would like to offer one of our companions a loving home! Complete the information below to submit your application."
            )) ?> 💚
        </p>

        <?php if (!$ilan): ?>

            <p>
                <?= guvenli(ceviri(
                    "Başvuru yapılacak ilan bulunamadı veya onaylanmamış.",
                    "The listing could not be found or has not been approved."
                )) ?>
            </p>

        <?php else: ?>

            <div class="hayvan-ozet">
                <h2><?= guvenli($ilan["hayvan_adi"]) ?></h2>

                <p>
                    <strong>
                        <?= guvenli(ceviri("Tür:", "Animal Type:")) ?>
                    </strong>

                    <?= guvenli(tur_adi($ilan["tur"])) ?>
                </p>

                <p>
                    <strong><?= guvenli(ceviri("Yaş:", "Age:")) ?></strong>

                    <?php
                    if ($ilan["yas"] === null || $ilan["yas"] === "") {
                        echo guvenli(ceviri(
                            "Belirtilmemiş",
                            "Not specified"
                        ));
                    } elseif ((int)$ilan["yas"] === 0) {
                        echo guvenli(ceviri(
                            "1 yaşından küçük",
                            "Under 1 year old"
                        ));
                    } else {
                        $yas = (int)$ilan["yas"];

                        echo guvenli(ceviri(
                            $yas . " yaş",
                            $yas . ($yas === 1 ? " year old" : " years old")
                        ));
                    }
                    ?>
                </p>

                <p>
                    <strong><?= guvenli(ceviri("Şehir:", "City:")) ?></strong>
                    <?= guvenli($ilan["sehir"]) ?>
                </p>
            </div>

            <?php if ($mesaj !== ""): ?>
                <p
                    class="basvuru-mesaj<?= $basarili ? " basarili" : "" ?>"
                    role="status"
                >
                    <?= guvenli($mesaj) ?>
                </p>
            <?php endif; ?>

            <?php if ($dil_degisti && $post_istegi): ?>
                <p class="basvuru-mesaj basarili" role="status">
                    <?= guvenli(ceviri(
                        "Dil değiştirildi. Form bilgileriniz korundu.",
                        "Language changed. Your form details have been kept."
                    )) ?>
                </p>
            <?php endif; ?>

            <?php if (!$basarili): ?>
                <form
                    id="basvuru-form"
                    action="sahiplen.php"
                    method="POST"
                >
                    <input
                        type="hidden"
                        name="ilan_id"
                        value="<?= (int)$ilan["id"] ?>"
                    >

                    <input
                        type="hidden"
                        name="token"
                        value="<?= guvenli($_SESSION["sahiplen_token"]) ?>"
                    >

                    <label for="adsoyad">
                        <?= guvenli(ceviri("Ad Soyad:", "Full Name:")) ?>
                    </label>

                    <input
                        type="text"
                        id="adsoyad"
                        name="adsoyad"
                        autocomplete="name"
                        value="<?= guvenli(form_degeri("adsoyad")) ?>"
                        required
                    >

                    <label for="telefon">
                        <?= guvenli(ceviri("Telefon:", "Phone:")) ?>
                    </label>

                    <input
                        type="tel"
                        id="telefon"
                        name="telefon"
                        autocomplete="tel"
                        value="<?= guvenli(form_degeri("telefon")) ?>"
                        required
                    >

                    <label for="email">
                        <?= guvenli(ceviri("E-posta:", "Email:")) ?>
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        autocomplete="email"
                        value="<?= guvenli(form_degeri("email")) ?>"
                        required
                    >

                    <label for="ev">
                        <?= guvenli(ceviri(
                            "Yaşadığınız Yer:",
                            "Type of Home:"
                        )) ?>
                    </label>

                    <select id="ev" name="ev" required>
                        <option value="">
                            <?= guvenli(ceviri("Seçiniz", "Please select")) ?>
                        </option>

                        <option
                            value="apartman"
                            <?= form_degeri("ev") === "apartman" ? "selected" : "" ?>
                        >
                            <?= guvenli(ceviri("Apartman", "Apartment")) ?>
                        </option>

                        <option
                            value="mustakil"
                            <?= form_degeri("ev") === "mustakil" ? "selected" : "" ?>
                        >
                            <?= guvenli(ceviri(
                                "Müstakil Ev",
                                "Detached House"
                            )) ?>
                        </option>
                    </select>

                    <label for="mesaj">
                        <?= guvenli(ceviri(
                            "Ek Bilgi:",
                            "Additional Information:"
                        )) ?>
                    </label>

                    <textarea
                        id="mesaj"
                        name="mesaj"
                        rows="5"
                        placeholder="<?= guvenli(ceviri(
                            "Kendiniz ve hayvana sağlayacağınız ortam hakkında bilgi verebilirsiniz.",
                            "Tell us about yourself and the home you can provide for the animal."
                        )) ?>"
                    ><?= guvenli(form_degeri("mesaj")) ?></textarea>

                    <button type="submit" class="basvuru-gonder">
                        <?= guvenli(ceviri(
                            "Başvuru Gönder",
                            "Submit Application"
                        )) ?>
                    </button>
                </form>

            <?php else: ?>

                <p>
                    <a href="profil.php">
                        <?= guvenli(ceviri(
                            "Başvurularımı Gör →",
                            "View My Applications →"
                        )) ?>
                    </a>
                </p>

            <?php endif; ?>

        <?php endif; ?>

        <p class="geri-alani">
            <a href="ilanlar.php">
                ← <?= guvenli(ceviri(
                    "İlanlara Geri Dön",
                    "Back to Listings"
                )) ?>
            </a>
        </p>
    </section>
</main>

<footer>
    © <?= date("Y") ?> Aris Pati Yuvası ·
    <?= guvenli(ceviri(
        "Hayvan Sahiplendirme Platformu",
        "Pet Adoption Platform"
    )) ?>
</footer>

</body>
</html>