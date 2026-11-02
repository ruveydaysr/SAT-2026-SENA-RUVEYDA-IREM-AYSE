<?php
session_start();
require_once __DIR__ . "/site-ust-ayar.php";

require_once __DIR__ . "/baglanti.php";

/* Dil tercihi */
if (
    is_string($_GET["lang"] ?? null)
    && in_array($_GET["lang"], ["tr", "en"], true)
) {
    $_SESSION["dil"] = $_GET["lang"];

    $parametreler = $_GET;
    unset($parametreler["lang"]);

    $adres = "ilan-detay.php";

    if ($parametreler) {
        $adres .= "?" . http_build_query($parametreler);
    }

    header("Location: " . $adres);
    exit();
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

function durum_adi($durum)
{
    global $dil;

    $adlar = [
        "Bekliyor" => "Pending",
        "Onaylandı" => "Approved",
        "Reddedildi" => "Rejected"
    ];

    return $dil === "en" ? ($adlar[$durum] ?? $durum) : $durum;
}

function cinsiyet_adi($cinsiyet)
{
    global $dil;

    $adlar = [
        "Dişi" => "Female",
        "Erkek" => "Male"
    ];

    return $dil === "en"
        ? ($adlar[$cinsiyet] ?? $cinsiyet)
        : $cinsiyet;
}

function onay_metni($tr, $en)
{
    return guvenli(json_encode(
        ceviri($tr, $en),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ));
}

function dil_linki($yeni_dil, $id)
{
    $parametreler = ["lang" => $yeni_dil];

    if ($id && $id > 0) {
        $parametreler["id"] = (int)$id;
    }

    return "ilan-detay.php?" . http_build_query($parametreler);
}

$admin_mi = isset($_SESSION["admin"]);
$uye_id = (int)($_SESSION["uye_id"] ?? 0);

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

$ilan = null;

if ($id && $id > 0) {
    $sql = "SELECT i.*, u.ad_soyad AS sahip_adi,
                   u.telefon AS sahip_telefon
            FROM ilanlar i
            LEFT JOIN uyeler u ON u.id = i.uye_id
            WHERE i.id = ?";

    if (!$admin_mi) {
        $sql .= " AND i.durum = 'Onaylandı'";
    }

    $sql .= " LIMIT 1";

    $sorgu = mysqli_prepare($baglanti, $sql);

    mysqli_stmt_bind_param($sorgu, "i", $id);
    mysqli_stmt_execute($sorgu);

    $sonuc = mysqli_stmt_get_result($sorgu);
    $ilan = mysqli_fetch_assoc($sonuc);

    mysqli_stmt_close($sorgu);
}

if (!$ilan) {
    http_response_code(404);
}

$baslik = $ilan
    ? (
        trim((string)($ilan["ilan_basligi"] ?? ""))
        ?: (
            trim((string)($ilan["hayvan_adi"] ?? ""))
            ?: ceviri("Can Dostumuz", "Our Companion")
        )
    )
    : ceviri("İlan Bulunamadı", "Listing Not Found");

$foto_yolu = "";
$temsili = false;
$telefon = "";
$whatsapp = "";
$ozellikler = [];

if ($ilan) {
    $foto = trim((string)($ilan["fotograf"] ?? ""));
    $dosya = basename($foto);

    if (
        preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$/i', $dosya)
        && is_file(__DIR__ . "/uploads/" . $dosya)
    ) {
        $foto_yolu = "uploads/" . $dosya;
    } elseif (
        preg_match(
            '~^images/hayvanlar/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$~i',
            $foto
        )
        && is_file(__DIR__ . "/" . $foto)
    ) {
        $foto_yolu = $foto;
        $temsili = true;
    } else {
        $temsili_resimler = [
            "Kedi" => "kedi.jpg",
            "Köpek" => "kopek.jpg",
            "Kuş" => "kus.jpg",
            "Balık" => "balik.jpg",
            "Tavşan" => "tavsan.jpg",
            "Kemirgen" => "kemirgen.jpg",
            "Sürüngen" => "surungen.jpg",
            "Diğer" => "diger.jpg"
        ];

        $resim = $temsili_resimler[$ilan["tur"] ?? ""] ?? "";

        if (
            $resim !== ""
            && is_file(__DIR__ . "/images/hayvanlar/" . $resim)
        ) {
            $foto_yolu = "images/hayvanlar/" . $resim;
            $temsili = true;
        }
    }

    $numara = preg_replace(
        '/\D/',
        '',
        (string)($ilan["sahip_telefon"] ?? "")
    );

    if (substr($numara, 0, 2) === "00") {
        $numara = substr($numara, 2);
    }

    if (strlen($numara) === 11 && $numara[0] === "0") {
        $numara = "90" . substr($numara, 1);
    } elseif (strlen($numara) === 10 && $numara[0] === "5") {
        $numara = "90" . $numara;
    }

    if (preg_match('/^[1-9][0-9]{7,14}$/', $numara)) {
        $telefon = "+" . $numara;

        $whatsapp_mesaji = ceviri(
            "Merhaba, Aris Pati Yuvası sitesindeki "
                . $baslik
                . " başlıklı ilanınız hakkında bilgi almak istiyorum. İlan no: "
                . $ilan["id"],

            "Hello, I would like to learn more about your listing titled "
                . $baslik
                . " on Aris Pati Yuvası. Listing ID: "
                . $ilan["id"]
        );

        $whatsapp = "https://wa.me/" . $numara
            . "?text=" . rawurlencode($whatsapp_mesaji);
    }

    $ozellikler = array_filter(
        array_map(
            "trim",
            explode(
                ",",
                (string)($ilan["karakteristik_ozellikler"] ?? "")
            )
        )
    );
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

    <title><?= guvenli($baslik) ?> | Aris Pati Yuvası</title>

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

        a {
            color: inherit;
        }

        button,
        textarea {
            font: inherit;
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
            color: #fff;
            text-decoration: none;
        }

        .ilan-logo img {
            display: block;
            width: 54px;
            height: 54px;
            padding: 5px;
            object-fit: contain;
            background: #fffdf8;
            border: 1px solid #eee5d3;
            border-radius: 12px;
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

        .dil-secici a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 37px;
            min-height: 30px;
            padding: 5px 9px;
            border-radius: 5px;
            color: white;
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
        }

        .dil-secici a:hover {
            background: #284b32;
        }

        .dil-secici a[aria-current="true"] {
            background: #f5f0e6;
            color: #284b32;
        }

        .dil-secici a:focus-visible {
            outline: 2px solid #eee5d3;
            outline-offset: 3px;
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
            color: #fff;
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
            color: #fff;
        }

        .ilan-menu > a.menu-aktif {
            background: #eee5d3;
            color: #284b32;
            font-weight: bold;
        }

        .ilan-menu > a:focus-visible,
        .kategori-baslik:focus-visible {
            outline: 2px solid #eee5d3;
            outline-offset: 3px;
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
            color: #fff;
        }

        .detay-alani {
            width: min(1180px, 92%);
            margin: 26px auto 45px;
        }

        .yol {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
            color: #727b69;
            font-size: 13px;
            line-height: 1.6;
        }

        .yol a {
            text-decoration: none;
        }

        .yol a:hover {
            text-decoration: underline;
        }

        h1 {
            margin: 0 0 22px;
            font-size: clamp(23px, 3vw, 30px);
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        h2 {
            margin: 0 0 18px;
            font-size: 20px;
            line-height: 1.4;
        }

        .detay-grid {
            display: grid;
            grid-template-columns:
                minmax(0, 1.4fr)
                minmax(0, 1fr)
                minmax(0, 1fr);
            gap: 20px;
            align-items: start;
        }

        .kart {
            min-width: 0;
            overflow: hidden;
            background: #fffdf8;
            border: 1px solid #ded6c6;
            border-radius: 14px;
        }

        .foto-kart img {
            display: block;
            width: 100%;
            height: 380px;
            object-fit: contain;
            background: #eee9df;
        }

        .foto-yok {
            display: grid;
            place-items: center;
            height: 380px;
            background: #eee9df;
            font-size: 65px;
        }

        .foto-not {
            margin: 0;
            padding: 12px;
            color: #727b69;
            font-size: 13px;
            line-height: 1.5;
            text-align: center;
        }

        .bilgi-kart,
        .sahip-kart {
            padding: 20px;
        }

        .bilgi-listesi {
            margin: 0;
        }

        .bilgi-satiri {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            padding: 13px 0;
            border-bottom: 1px dashed #ded6c6;
            font-size: 14px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .bilgi-satiri:first-child {
            padding-top: 0;
        }

        .bilgi-satiri:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .bilgi-satiri dt {
            color: #727b69;
        }

        .bilgi-satiri dd {
            margin: 0;
            font-weight: bold;
        }

        .sahip-simge {
            display: grid;
            place-items: center;
            width: 56px;
            height: 56px;
            margin-bottom: 12px;
            border-radius: 50%;
            background: #e6eddf;
            font-size: 26px;
        }

        .sahip-adi {
            margin: 0 0 10px;
            font-size: 19px;
            overflow-wrap: anywhere;
        }

        .kucuk-metin {
            margin: 10px 0;
            color: #727b69;
            font-size: 13px;
            line-height: 1.7;
        }

        .buton {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 43px;
            margin-top: 12px;
            padding: 11px 14px;
            border: 1px solid #ded6c6;
            border-radius: 8px;
            background: #eee5d3;
            color: #284b32;
            font-size: 14px;
            font-weight: bold;
            line-height: 1.5;
            text-align: center;
            text-decoration: none;
            overflow-wrap: anywhere;
            cursor: pointer;
        }

        .buton:hover {
            filter: brightness(.95);
        }

        .buton:focus-visible {
            outline: 3px solid #9eb995;
            outline-offset: 3px;
        }

        .yesil {
            background: #426b46;
            border-color: #426b46;
            color: #fff;
        }

        .whatsapp {
            background: #168673;
            border-color: #168673;
            color: #fff;
        }

        .kirmizi {
            background: #ae4640;
            border-color: #ae4640;
            color: #fff;
        }

        .aciklama-kart,
        .admin-kart,
        .bulunamadi {
            margin-top: 22px;
            padding: 24px;
        }

        .ozellikler {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
        }

        .ozellik {
            padding: 7px 12px;
            border-radius: 20px;
            background: #e6eddf;
            font-size: 13px;
            line-height: 1.4;
        }

        .metin {
            margin: 0;
            line-height: 1.8;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .admin-kart form {
            margin-top: 18px;
        }

        .admin-kart label {
            display: block;
            font-size: 14px;
            font-weight: bold;
        }

        .admin-kart textarea {
            display: block;
            width: 100%;
            min-height: 100px;
            margin-top: 10px;
            padding: 12px;
            border: 1px solid #ded6c6;
            border-radius: 8px;
            background: #fff;
            color: #284b32;
            resize: vertical;
        }

        .admin-kart textarea:focus {
            outline: 2px solid #9eb995;
            border-color: #426b46;
        }

        .red-nedeni {
            color: #ae4640;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .geri-link {
            display: inline-block;
            margin-top: 24px;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
        }

        .geri-link:hover {
            text-decoration: underline;
        }

        .bulunamadi h1 {
            margin-bottom: 12px;
        }

        .bulunamadi p {
            margin: 0;
            line-height: 1.7;
        }

        .alt-bilgi {
            padding: 20px;
            color: #727b69;
            font-size: 12px;
            line-height: 1.6;
            text-align: center;
        }

        @media (max-width: 1100px) {
            .ust-sag {
                flex-basis: 100%;
            }

            .ilan-menu {
                width: 100%;
                justify-content: flex-end;
            }

            .detay-grid {
                grid-template-columns:
                    minmax(0, 1.3fr)
                    minmax(0, 1fr);
            }

            .sahip-kart {
                grid-column: 1 / -1;
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

            .detay-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .sahip-kart {
                grid-column: auto;
            }

            .foto-kart img,
            .foto-yok {
                height: 320px;
            }

            .aciklama-kart,
            .admin-kart,
            .bulunamadi {
                padding: 20px;
            }
        }
        .admin-detay-geri {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 22px;
        }
        .admin-detay-geri .buton {
            width: auto;
            text-decoration: none;
        }
    </style>
<link rel="stylesheet" href="site-ust.css?v=20261102-1">
<link rel="stylesheet" href="soru-cevap.css?v=1">
</head>

<body>

<?php require __DIR__ . "/site-ust.php"; ?>

<main class="detay-alani">

    <?php if ($admin_mi): ?>
        <nav class="admin-detay-geri" aria-label="<?= guvenli(ceviri("Admin paneli", "Admin panel")) ?>">
            <a href="admin/admin-panel.php" class="buton yesil">
                ← <?= guvenli(ceviri("İlan Yönetimine Dön", "Back to Listing Management")) ?>
            </a>
            <a href="admin/admin-genel-bakis.php" class="buton">
                <?= guvenli(ceviri("Admin Genel Bakış", "Admin Overview")) ?>
            </a>
        </nav>
    <?php endif; ?>


    <?php if ($ilan): ?>

        <nav
            class="yol"
            aria-label="<?= guvenli(ceviri(
                "Sayfa konumu",
                "Breadcrumb"
            )) ?>"
        >
            <a href="index.php">
                <?= guvenli(ceviri("Ana Sayfa", "Home")) ?>
            </a>

            <span>/</span>

            <a href="ilanlar.php">
                <?= guvenli(ceviri("İlanlar", "Listings")) ?>
            </a>

            <span>/</span>

            <span>
                <?= guvenli(tur_adi($ilan["tur"] ?? "")) ?>
            </span>
        </nav>

        <h1><?= guvenli($baslik) ?></h1>

        <div class="detay-grid">

            <section
                class="kart foto-kart"
                aria-label="<?= guvenli(ceviri(
                    "Hayvan fotoğrafı",
                    "Animal photo"
                )) ?>"
            >
                <?php if ($foto_yolu !== ""): ?>
                    <img
                        src="<?= guvenli($foto_yolu) ?>"
                        alt="<?= guvenli($ilan["hayvan_adi"] ?? $baslik) ?>"
                    >
                <?php else: ?>
                    <div
                        class="foto-yok"
                        role="img"
                        aria-label="<?= guvenli(ceviri(
                            "Fotoğraf bulunmuyor",
                            "No photo available"
                        )) ?>"
                    >
                        🐾
                    </div>
                <?php endif; ?>

                <p class="foto-not">
                    <?php if ($temsili): ?>
                        <?= guvenli(ceviri(
                            "Bu fotoğraf temsili olarak kullanılmıştır.",
                            "This photo is used for illustration purposes."
                        )) ?>
                    <?php elseif ($foto_yolu !== ""): ?>
                        <?= guvenli($ilan["hayvan_adi"] ?? $baslik) ?>
                    <?php else: ?>
                        <?= guvenli(ceviri(
                            "Bu ilan için fotoğraf eklenmemiş.",
                            "No photo has been added to this listing."
                        )) ?>
                    <?php endif; ?>
                </p>
            </section>

            <section class="kart bilgi-kart">
                <h2>
                    <?= guvenli(ceviri(
                        "Hayvan Bilgileri",
                        "Animal Information"
                    )) ?>
                </h2>

                <?php
                $yas_yazisi = ceviri(
                    "Belirtilmemiş",
                    "Not specified"
                );

                if (isset($ilan["yas"]) && $ilan["yas"] !== "") {
                    $yas = (int)$ilan["yas"];

                    if ($yas === 0) {
                        $yas_yazisi = ceviri(
                            "1 yaşından küçük",
                            "Under 1 year old"
                        );
                    } else {
                        $yas_yazisi = ceviri(
                            $yas . " yaş",
                            $yas . ($yas === 1 ? " year old" : " years old")
                        );
                    }
                }

                $bilgiler = [
                    ceviri("İlan No", "Listing ID") =>
                        $ilan["id"],

                    ceviri("Hayvanın Adı", "Name") =>
                        $ilan["hayvan_adi"] ?? "",

                    ceviri("Türü", "Animal Type") =>
                        tur_adi($ilan["tur"] ?? ""),

                    ceviri("Yaşı", "Age") =>
                        $yas_yazisi,

                    ceviri("Cinsiyeti", "Sex") =>
                        cinsiyet_adi($ilan["cinsiyet"] ?? ""),

                    ceviri("Şehir", "City") =>
                        $ilan["sehir"] ?? "",

                    ceviri("İlan Durumu", "Listing Status") =>
                        durum_adi($ilan["durum"] ?? "")
                ];
                ?>

                <dl class="bilgi-listesi">
                    <?php foreach ($bilgiler as $etiket => $deger): ?>
                        <div class="bilgi-satiri">
                            <dt><?= guvenli($etiket) ?></dt>

                            <dd>
                                <?= guvenli(
                                    trim((string)$deger) !== ""
                                        ? $deger
                                        : ceviri(
                                            "Belirtilmemiş",
                                            "Not specified"
                                        )
                                ) ?>
                            </dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </section>

            <section class="kart sahip-kart">
                <h2>
                    <?= guvenli(ceviri(
                        "İlan Sahibi",
                        "Listing Owner"
                    )) ?>
                </h2>

                <div class="sahip-simge" aria-hidden="true">👤</div>

                <h3 class="sahip-adi">
                    <?= guvenli(
                        trim((string)($ilan["sahip_adi"] ?? ""))
                            ?: ceviri("İlan sahibi", "Listing owner")
                    ) ?>
                </h3>

                <p class="kucuk-metin">
                    <?= guvenli(ceviri(
                        "Hayvan hakkında bilgi almak için ilan sahibiyle iletişime geçebilirsiniz.",
                        "Contact the listing owner to learn more about this animal."
                    )) ?>
                </p>

                <?php if ($telefon !== ""): ?>
                    <a
                        class="buton"
                        href="tel:<?= guvenli($telefon) ?>"
                    >
                        ☎ <?= guvenli($telefon) ?>
                    </a>

                    <a
                        class="buton whatsapp"
                        href="<?= guvenli($whatsapp) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <?= guvenli(ceviri(
                            "WhatsApp ile İletişim",
                            "Contact via WhatsApp"
                        )) ?>
                    </a>
                <?php else: ?>
                    <p class="kucuk-metin">
                        <?= guvenli(ceviri(
                            "İlan sahibinin geçerli bir telefon numarası bulunmuyor.",
                            "The listing owner has no valid phone number available."
                        )) ?>
                    </p>
                <?php endif; ?>

                <?php if ($uye_id > 0 && $uye_id === (int)$ilan["uye_id"]): ?>
                    <a class="buton yesil" href="mesajlar.php">
                        ✉ <?= guvenli(ceviri(
                            "İlan Mesajlarımı Gör",
                            "View My Listing Messages"
                        )) ?>
                    </a>

                <?php elseif ($uye_id > 0 && !empty($ilan["sahip_adi"])): ?>
                    <a
                        class="buton yesil"
                        href="mesajlar.php?ilan_id=<?= (int)$ilan["id"] ?>&amp;kisi=<?= (int)$ilan["uye_id"] ?>"
                    >
                        ✉ <?= guvenli(ceviri(
                            "Mesaj Gönder",
                            "Send a Message"
                        )) ?>
                    </a>

                <?php elseif (!$uye_id): ?>
                    <a class="buton yesil" href="giris.php">
                        <?= guvenli(ceviri(
                            "Mesaj Göndermek İçin Giriş Yap",
                            "Sign In to Send a Message"
                        )) ?>
                    </a>
                <?php endif; ?>

                <?php if (!$admin_mi && $uye_id !== (int)$ilan["uye_id"]): ?>
                    <a
                        class="buton"
                        href="sahiplen.php?ilan_id=<?= (int)$ilan["id"] ?>"
                    >
                        🐾 <?= guvenli(ceviri(
                            "Sahiplenme Başvurusu Yap",
                            "Apply to Adopt"
                        )) ?>
                    </a>
                <?php endif; ?>
            </section>

        </div>

        <section class="kart aciklama-kart">
            <h2>
                <?= guvenli(ceviri(
                    "İlan Açıklaması",
                    "Listing Description"
                )) ?>
            </h2>

            <?php if (!empty($ozellikler)): ?>
                <div class="ozellikler">
                    <?php foreach ($ozellikler as $ozellik): ?>
                        <span class="ozellik">
                            <?= guvenli($ozellik) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <p class="metin"><?= guvenli(
                trim((string)($ilan["aciklama"] ?? ""))
                    ?: ceviri(
                        "Açıklama eklenmemiş.",
                        "No description has been added."
                    )
            ) ?></p>
        </section>

        <?php require __DIR__ . '/ilan-soru-cevap.php'; ?>

        <?php if ($admin_mi): ?>
            <section class="kart admin-kart">
                <h2>
                    <?= guvenli(ceviri(
                        "İlan Yönetim İşlemleri",
                        "Listing Management"
                    )) ?>
                </h2>

                <p>
                    <?= guvenli(ceviri(
                        "İlan durumu:",
                        "Listing status:"
                    )) ?>

                    <strong>
                        <?= guvenli(durum_adi($ilan["durum"] ?? "")) ?>
                    </strong>
                </p>

                <?php if (!empty($ilan["red_nedeni"])): ?>
                    <p class="red-nedeni">
                        <strong>
                            <?= guvenli(ceviri(
                                "Red nedeni:",
                                "Reason for rejection:"
                            )) ?>
                        </strong>

                        <?= guvenli($ilan["red_nedeni"]) ?>
                    </p>
                <?php endif; ?>

                <?php if (($ilan["durum"] ?? "") === "Bekliyor"): ?>
                    <form
                        action="admin/onayla.php"
                        method="POST"
                        onsubmit="return confirm(<?= onay_metni(
                            "Bu ilanı onaylamak istiyor musunuz?",
                            "Do you want to approve this listing?"
                        ) ?>);"
                    >
                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int)$ilan["id"] ?>"
                        >

                        <button type="submit" class="buton yesil">
                            <?= guvenli(ceviri(
                                "İlanı Onayla",
                                "Approve Listing"
                            )) ?>
                        </button>
                    </form>

                    <form
                        action="admin/reddet.php"
                        method="POST"
                        onsubmit="return confirm(<?= onay_metni(
                            "Bu ilanı reddetmek istiyor musunuz?",
                            "Do you want to reject this listing?"
                        ) ?>);"
                    >
                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int)$ilan["id"] ?>"
                        >

                        <label for="red-nedeni">
                            <?= guvenli(ceviri(
                                "Red nedeni",
                                "Reason for rejection"
                            )) ?>
                        </label>

                        <textarea
                            id="red-nedeni"
                            name="red_nedeni"
                            rows="3"
                            placeholder="<?= guvenli(ceviri(
                                "İlanın reddedilme nedenini yazın.",
                                "Explain why this listing is being rejected."
                            )) ?>"
                            required
                        ></textarea>

                        <button type="submit" class="buton kirmizi">
                            <?= guvenli(ceviri(
                                "İlanı Reddet",
                                "Reject Listing"
                            )) ?>
                        </button>
                    </form>
                <?php endif; ?>

            </section>
        <?php endif; ?>

    <?php else: ?>

        <section class="kart bulunamadi">
            <h1>
                <?= guvenli(ceviri(
                    "İlan Bulunamadı",
                    "Listing Not Found"
                )) ?>
            </h1>

            <p>
                <?= guvenli(ceviri(
                    "Aradığınız ilan silinmiş veya henüz onaylanmamış olabilir.",
                    "The listing may have been deleted or may not have been approved yet."
                )) ?>
            </p>
        </section>

    <?php endif; ?>

    <a
        class="geri-link"
        href="<?= $admin_mi ? "admin/admin-panel.php" : "ilanlar.php" ?>"
    >
        ← <?= guvenli(
            $admin_mi
                ? ceviri("İlan Yönetimine Dön", "Back to Listing Management")
                : ceviri("İlanlara Dön", "Back to Listings")
        ) ?>
    </a>

</main>

<footer class="alt-bilgi">
    © <?= date("Y") ?> Aris Pati Yuvası ·
    <?= guvenli(ceviri(
        "Hayvan Sahiplendirme Platformu",
        "Pet Adoption Platform"
    )) ?>
</footer>

</body>
</html>