<?php
session_start();
require_once __DIR__ . "/baglanti.php";

function guvenli($deger)
{
    return htmlspecialchars((string) $deger, ENT_QUOTES, "UTF-8");
}

/* Okunmamış mesaj bildirimi */

$okunmamis_mesaj_var = false;
$bildirim_uye_id = (int) ($_SESSION["uye_id"] ?? 0);

if ($bildirim_uye_id > 0) {
    $bildirim_sorgusu = mysqli_prepare(
        $baglanti,
        "SELECT id
         FROM ilan_mesajlari
         WHERE alici_id = ? AND okundu = 0
         LIMIT 1"
    );

    mysqli_stmt_bind_param(
        $bildirim_sorgusu,
        "i",
        $bildirim_uye_id
    );

    mysqli_stmt_execute($bildirim_sorgusu);

    $okunmamis_mesaj_var = mysqli_num_rows(
        mysqli_stmt_get_result($bildirim_sorgusu)
    ) > 0;

    mysqli_stmt_close($bildirim_sorgusu);
}

/* Ana sayfa içeriği */

$sonuc = mysqli_query(
    $baglanti,
    "SELECT baslik, aciklama, buton_yazisi
     FROM anasayfa_icerik
     WHERE id = 1"
);

$icerik = mysqli_fetch_assoc($sonuc) ?: [
    "baslik" => "Yeni Bir Dost Edin",
    "aciklama" => "İlanları inceleyerek sana uygun yeni dostunu bulabilirsin.",
    "buton_yazisi" => "İlanları İncele"
];

$varsayilan_ayarlar = [
    "buton_linki" => "ilanlar.php",
    "kapak" => null,
    "duyuru" => "",
    "duyuru_aktif" => 0,
    "tanitim_aktif" => 1,
    "kategoriler_aktif" => 1,
    "vitrin_aktif" => 1,
    "vitrin_idler" => "[]"
];

$sonuc = mysqli_query(
    $baglanti,
    "SELECT * FROM anasayfa_ayarlar WHERE id = 1"
);

$ayar = array_merge(
    $varsayilan_ayarlar,
    mysqli_fetch_assoc($sonuc) ?: []
);

$izinli_linkler = [
    "ilanlar.php",
    "ilan-ver.php",
    "hakkimizda.php",
    "iletisim.php"
];

$buton_linki = in_array(
    $ayar["buton_linki"],
    $izinli_linkler,
    true
) ? $ayar["buton_linki"] : "ilanlar.php";

/* Kapak fotoğrafı */

$kapak_yolu = is_file(__DIR__ . "/images/anasayfa-kapak.png")
    ? "images/anasayfa-kapak.png"
    : "";

if (
    !empty($ayar["kapak"])
    && preg_match(
        '/^kapak_[a-f0-9]{32}\.(jpg|png|webp)$/',
        $ayar["kapak"]
    ) === 1
    && is_file(__DIR__ . "/uploads/" . $ayar["kapak"])
) {
    $kapak_yolu = "uploads/" . $ayar["kapak"];
}

/* Vitrindeki ilanlar */

$secili_idler = json_decode(
    (string) $ayar["vitrin_idler"],
    true
);

$temiz_idler = [];

if (is_array($secili_idler)) {
    foreach ($secili_idler as $deger) {
        if (!is_int($deger) && !is_string($deger)) {
            continue;
        }

        $id = filter_var($deger, FILTER_VALIDATE_INT);

        if ($id && $id > 0) {
            $temiz_idler[] = $id;
        }
    }
}

$secili_idler = array_slice(
    array_values(array_unique($temiz_idler)),
    0,
    12
);

$vitrin = [];

if ($ayar["vitrin_aktif"] && $secili_idler) {
    $id_listesi = implode(",", $secili_idler);

    $sonuc = mysqli_query(
        $baglanti,
        "SELECT id, hayvan_adi, tur, sehir, fotograf
         FROM ilanlar
         WHERE durum = 'Onaylandı'
           AND id IN ($id_listesi)
         ORDER BY id DESC
         LIMIT 12"
    );

    while ($ilan = mysqli_fetch_assoc($sonuc)) {
        $vitrin[] = $ilan;
    }
}

function vitrin_fotografi($fotograf)
{
    $fotograf = (string) $fotograf;
    $dosya = basename($fotograf);

    if (
        preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$/i', $dosya)
        && is_file(__DIR__ . "/uploads/" . $dosya)
    ) {
        return "uploads/" . $dosya;
    }

    if (
        preg_match(
            '~^images/hayvanlar/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$~i',
            $fotograf
        )
        && is_file(__DIR__ . "/" . $fotograf)
    ) {
        return $fotograf;
    }

    return "";
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
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aris Pati Yuvası | Hayvan Sahiplendirme</title>

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

        .ilan-menu > a,
        .kategori-baslik {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
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

        .ilan-menu > a[aria-current="page"] {
            background: #eee5d3;
            color: #284b32;
            font-weight: bold;
        }

        .mesaj-bildirim {
            display: inline-block;
            flex-shrink: 0;
            width: 9px;
            height: 9px;
            border: 1px solid #fff;
            border-radius: 50%;
            background: #ef5350;
        }

        /* Hayvan kategorileri */

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
            box-shadow: 0 12px 28px rgba(40, 75, 50, .16);
        }

        .kategori-menu:hover .kategori-acilir,
        .kategori-menu:focus-within .kategori-acilir {
            display: block;
        }

        .kategori-acilir a {
            display: flex;
            align-items: center;
            gap: 12px;
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

        .kategori-simge {
            width: 24px;
            font-size: 20px;
            text-align: center;
        }

        /* Duyuru ve kapak */

        .ay-duyuru {
            padding: 14px 5%;
            background: #e6eddf;
            line-height: 1.6;
            text-align: center;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .ay-kapak-bolum {
            margin-bottom: 38px;
        }

        .ay-hero {
            position: relative;
            isolation: isolate;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 450px;
            padding: 55px 24px;
            overflow: hidden;
            background: #426b46;
            text-align: center;
        }

        .ay-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -1;
            background: linear-gradient(
                90deg,
                rgba(25, 48, 29, .06),
                rgba(25, 48, 29, .45) 35%,
                rgba(25, 48, 29, .45) 65%,
                rgba(25, 48, 29, .06)
            );
        }

        .ay-hero.ay-gorselsiz::before {
            background: transparent;
        }

        .ay-kapak {
            position: absolute;
            inset: 0;
            z-index: -2;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .ay-hero-yazi {
            width: min(580px, 48%);
        }

        .ay-hero h1 {
            margin: 0 0 18px;
            color: #fffdf8;
            font-size: clamp(28px, 3.2vw, 46px);
            line-height: 1.2;
            text-shadow: 0 2px 8px rgba(0, 0, 0, .2);
            overflow-wrap: anywhere;
        }

        .ay-hero p {
            margin: 0 0 26px;
            color: #fffdf8;
            font-size: 17px;
            line-height: 1.7;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .ay-buton {
            display: inline-block;
            min-width: 220px;
            max-width: 100%;
            padding: 14px 24px;
            border: 1px solid #eee5d3;
            border-radius: 8px;
            background: #eee5d3;
            color: #284b32;
            font-weight: bold;
            text-decoration: none;
        }

        .ay-buton:hover {
            background: #fffdf8;
        }

        /* Vitrin */

        .ay-bolum {
            width: min(1200px, 92%);
            margin: 30px auto 40px;
        }

        .ay-bolum h2 {
            margin: 0 0 24px;
            font-size: 28px;
            text-align: center;
        }

        .ay-vitrin {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 22px;
        }

        .ay-ilan {
            display: block;
            overflow: hidden;
            border: 1px solid #ded6c6;
            border-radius: 14px;
            background: #fffdf8;
            color: #284b32;
            text-decoration: none;
            transition: transform .2s;
        }

        .ay-ilan:hover {
            transform: translateY(-4px);
        }

        .ay-ilan > img,
        .ay-foto-yok {
            display: block;
            width: 100%;
            height: 220px;
            object-fit: cover;
        }

        .ay-foto-yok {
            display: grid;
            place-items: center;
            background: #e6eddf;
            font-size: 48px;
        }

        .ay-ilan-bilgi {
            padding: 18px;
            overflow-wrap: anywhere;
        }

        .ay-ilan-bilgi h3 {
            margin: 0 0 8px;
        }

        .ay-ilan-bilgi p {
            margin: 0;
            color: #687361;
            line-height: 1.5;
        }

        a:focus-visible,
        button:focus-visible {
            outline: 2px solid #284b32;
            outline-offset: 3px;
        }

        .ilan-menu > a:focus-visible,
        .kategori-baslik:focus-visible {
            outline-color: #eee5d3;
        }

        @media (min-width: 1500px) {
            .ay-hero {
                min-height: 520px;
            }
        }

        @media (max-width: 1100px) {
            .ilan-menu {
                width: 100%;
            }
        }

        @media (max-width: 900px) {
            .ay-hero {
                min-height: 380px;
            }

            .ay-hero-yazi {
                width: min(550px, 75%);
            }

            .ay-vitrin {
                grid-template-columns: repeat(2, minmax(0, 1fr));
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
        }

        @media (max-width: 600px) {
            .ay-hero {
                min-height: 360px;
                padding: 35px 20px;
            }

            .ay-hero::before {
                background: rgba(25, 48, 29, .55);
            }

            .ay-hero-yazi {
                width: 100%;
            }

            .ay-hero h1 {
                font-size: 30px;
            }

            .ay-hero p {
                font-size: 15px;
            }

            .ay-vitrin {
                gap: 12px;
            }

            .ay-ilan > img,
            .ay-foto-yok {
                height: 170px;
            }
        }

        @media (max-width: 380px) {
            .ay-vitrin {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

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
        <a href="index.php" aria-current="page">Ana Sayfa</a>
        <a href="ilanlar.php">İlanlar</a>

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
                <?php foreach ($menu_kategorileri as $kategori_adi => $simge): ?>
                    <a href="ilanlar.php?tur=<?= rawurlencode($kategori_adi) ?>">
                        <span class="kategori-simge" aria-hidden="true">
                            <?= $simge ?>
                        </span>
                        <span><?= guvenli($kategori_adi) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <a href="ilan-ver.php">İlan Ver</a>
        <a href="hakkimizda.php">Hakkımızda</a>
        <a href="iletisim.php">İletişim</a>

        <?php if ($bildirim_uye_id > 0): ?>
            <a href="mesajlar.php">
                Mesajlarım

                <?php if ($okunmamis_mesaj_var): ?>
                    <span
                        class="mesaj-bildirim"
                        role="img"
                        aria-label="Okunmamış mesajınız var"
                    ></span>
                <?php endif; ?>
            </a>

            <a href="profil.php">Profilim</a>

            <a
                href="cikis.php"
                onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');"
            >Çıkış Yap</a>
        <?php else: ?>
            <a href="giris.php">Giriş Yap</a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <?php if (
        $ayar["duyuru_aktif"]
        && trim((string) $ayar["duyuru"]) !== ""
    ): ?>
        <div class="ay-duyuru"><?= guvenli($ayar["duyuru"]) ?></div>
    <?php endif; ?>

    <?php if ($ayar["tanitim_aktif"]): ?>
        <section class="ay-kapak-bolum">
            <div class="ay-hero <?= $kapak_yolu === "" ? 'ay-gorselsiz' : '' ?>">
                <div class="ay-hero-yazi">
                    <h1><?= guvenli($icerik["baslik"]) ?></h1>

                    <p><?= guvenli($icerik["aciklama"]) ?></p>

                    <a
                        href="<?= guvenli($buton_linki) ?>"
                        class="ay-buton"
                    ><?= guvenli($icerik["buton_yazisi"]) ?></a>
                </div>

                <?php if ($kapak_yolu !== ""): ?>
                    <img
                        src="<?= guvenli($kapak_yolu) ?>"
                        alt="Yuva arayan kedi ve köpekler"
                        class="ay-kapak"
                        fetchpriority="high"
                    >
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($ayar["vitrin_aktif"] && $vitrin): ?>
        <section class="ay-bolum">
            <h2>Yuva Arayan Dostlarımız</h2>

            <div class="ay-vitrin">
                <?php foreach ($vitrin as $ilan): ?>
                    <?php
                    $foto_yolu = vitrin_fotografi($ilan["fotograf"] ?? "");
                    ?>

                    <a
                        href="ilan-detay.php?id=<?= (int) $ilan["id"] ?>"
                        class="ay-ilan"
                    >
                        <?php if ($foto_yolu !== ""): ?>
                            <img
                                src="<?= guvenli($foto_yolu) ?>"
                                alt="<?= guvenli($ilan["hayvan_adi"]) ?>"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div class="ay-foto-yok" aria-label="Fotoğraf yok">
                                🐾
                            </div>
                        <?php endif; ?>

                        <div class="ay-ilan-bilgi">
                            <h3><?= guvenli($ilan["hayvan_adi"]) ?></h3>
                            <p>
                                <?= guvenli($ilan["tur"]) ?>
                                · <?= guvenli($ilan["sehir"]) ?>
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main><style>
    /* ANA SAYFANIN ALT BÖLÜMLERİ */

    .pati-sehir-bolumu {
        padding: 45px 20px;
        border-top: 1px solid #d2ddc7;
        border-bottom: 1px solid #d2ddc7;
        background: #e6eddf;
        text-align: center;
    }

    .pati-sehir-bolumu h2 {
        margin: 0 0 14px;
        color: #284b32;
        font-size: 28px;
    }

    .pati-sehir-bolumu p {
        margin: 0 0 25px;
        color: #60745b;
        line-height: 1.7;
    }

    .pati-sehirler {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .pati-sehirler a {
        padding: 12px 22px;
        border: 1px solid #cbd8bf;
        border-radius: 8px;
        background: #fffdf8;
        color: #284b32;
        text-decoration: none;
    }

    .pati-sehirler a:hover {
        background: #426b46;
        color: white;
    }

    .pati-tum-ilanlar {
        display: inline-block;
        margin-top: 25px;
        color: #284b32;
        font-weight: bold;
        text-underline-offset: 5px;
    }

    /* SAHİPLENME MESAJI */

    .pati-mesaj-bolumu {
        padding: 50px 20px;
        background: #f5f0e6;
        text-align: center;
    }

    .pati-soz-kutusu {
        max-width: 720px;
        margin: 0 auto;
        padding: 30px 20px;
        border: 1px solid #bacba9;
        border-radius: 12px;
        background: #edf2e6;
    }

    .pati-soz-simge {
        color: #6b8c56;
        font: 60px/1 Georgia, serif;
    }

    .pati-soz-kutusu p {
        margin: 12px 0 18px;
        color: #284b32;
        font: italic 26px/1.6 Georgia, serif;
    }

    .pati-soz-kutusu small {
        color: #60745b;
        font-size: 14px;
    }

    .pati-davet-yazisi {
        margin: 30px 0 20px;
        color: #60745b;
        line-height: 1.8;
    }

    .pati-ilan-butonu {
        display: inline-block;
        padding: 14px 24px;
        border-radius: 8px;
        background: #426b46;
        color: white;
        font-weight: bold;
        text-decoration: none;
    }

    .pati-ilan-butonu:hover {
        background: #284b32;
    }

    /* EN ALT BİLGİ ALANI */

    .pati-footer {
        background: #35583b;
        color: #fffdf8;
    }

    .pati-footer-icerik {
        display: grid;
        grid-template-columns: 1.6fr 1fr 1fr 1fr;
        gap: 35px;
        width: min(1200px, 92%);
        margin: auto;
        padding: 45px 0;
    }

    .pati-footer-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #fffdf8;
        font-size: 20px;
        text-decoration: none;
    }

    .pati-footer-logo img {
        width: 52px;
        height: 52px;
        padding: 5px;
        border-radius: 10px;
        background: #fffdf8;
        object-fit: contain;
    }

    .pati-footer-tanitim {
        max-width: 320px;
        margin: 16px 0 0;
        color: #dce6d5;
        font-size: 14px;
        line-height: 1.8;
    }

    .pati-footer h3 {
        margin: 4px 0 20px;
        color: #edf2df;
        font-size: 16px;
    }

    .pati-footer-linkler {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 13px;
    }

    .pati-footer-linkler a {
        color: #dce6d5;
        font-size: 14px;
        text-decoration: none;
    }

    .pati-footer-linkler a:hover {
        color: white;
        text-decoration: underline;
        text-underline-offset: 4px;
    }

    .pati-footer-taban {
        border-top: 1px solid #547358;
    }

    .pati-footer-taban-icerik {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
        width: min(1200px, 92%);
        margin: auto;
        padding: 18px 0;
        color: #dce6d5;
        font-size: 13px;
    }

    .pati-footer-taban a {
        color: #fffdf8;
        text-underline-offset: 4px;
    }

    @media (max-width: 850px) {
        .pati-footer-icerik {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 500px) {
        .pati-footer-icerik {
            grid-template-columns: 1fr;
            gap: 30px;
        }

        .pati-sehir-bolumu h2 {
            font-size: 24px;
        }

        .pati-soz-kutusu p {
            font-size: 22px;
        }

        .pati-sehirler a {
            padding: 10px 15px;
        }
    }
    .pati-sehir-bolumu {
    background: #426b46;
    border-color: #35583b;
}

.pati-sehir-bolumu h2,
.pati-sehir-bolumu p,
.pati-sehir-bolumu .pati-tum-ilanlar {
    color: #fffdf8;
}
</style>

<!-- ŞEHİRLERE GÖRE İLANLAR -->

<section class="pati-sehir-bolumu">
    <h2>Yakınındaki Dostunu Bul</h2>

    <p>
        Şehrindeki sahiplendirme ilanlarını incele,
        yuva arayan bir dostumuzla tanış.
    </p>

    <div class="pati-sehirler">
        <a href="ilanlar.php?sehir=İstanbul">İstanbul</a>
        <a href="ilanlar.php?sehir=Ankara">Ankara</a>
        <a href="ilanlar.php?sehir=İzmir">İzmir</a>
        <a href="ilanlar.php?sehir=Bursa">Bursa</a>
        <a href="ilanlar.php?sehir=Antalya">Antalya</a>
        <a href="ilanlar.php?sehir=Eskişehir">Eskişehir</a>
    </div>

    <a href="ilanlar.php" class="pati-tum-ilanlar">
        Tüm şehirlerdeki ilanları incele →
    </a>
</section>

<!-- SAHİPLENME MESAJI -->

<section class="pati-mesaj-bolumu">
    <div class="pati-soz-kutusu">
        <span class="pati-soz-simge" aria-hidden="true">“</span>

        <p>
            Senin için bir dost,<br>
            onun için bütün bir dünya.
        </p>

        <small>Bir yuva, bir hayatı değiştirir.</small>
    </div>

    <p class="pati-davet-yazisi">
        Birlikte daha fazla patinin sıcak bir yuvaya
        kavuşmasına yardımcı olabiliriz.
    </p>

    <a href="ilan-ver.php" class="pati-ilan-butonu">
        Sahiplendirme İlanı Ver
    </a>
</section>

<!-- EN ALT BİLGİ ALANI -->

<footer class="pati-footer">
    <div class="pati-footer-icerik">

        <div>
            <a href="index.php" class="pati-footer-logo">
                <img
                    src="images/aris-logoo.png"
                    alt=""
                    width="52"
                    height="52"
                >
                <strong>Aris Pati Yuvası</strong>
            </a>

            <p class="pati-footer-tanitim">
                Her can dostuna bir yuva. Yuva arayan hayvanlarla
                onlara sevgiyle bakmak isteyen insanları
                bir araya getiriyoruz.
            </p>
        </div>

        <div>
            <h3>Hızlı Bağlantılar</h3>

            <nav class="pati-footer-linkler" aria-label="Alt menü">
                <a href="index.php">Ana Sayfa</a>
                <a href="ilanlar.php">Sahiplendirme İlanları</a>
                <a href="ilan-ver.php">İlan Ver</a>
            </nav>
        </div>

        <div>
            <h3>Dostlarımız</h3>

            <nav class="pati-footer-linkler" aria-label="Hayvan türleri">
                <a href="ilanlar.php?tur=Kedi">Kedi İlanları</a>
                <a href="ilanlar.php?tur=Köpek">Köpek İlanları</a>
                <a href="ilanlar.php?tur=Kuş">Kuş İlanları</a>
                <a href="ilanlar.php?tur=Diğer">Diğer Dostlarımız</a>
            </nav>
        </div>

        <div>
            <h3>Bizi Tanıyın</h3>

            <nav class="pati-footer-linkler" aria-label="Bilgi menüsü">
                <a href="hakkimizda.php">Hakkımızda</a>
                <a href="iletisim.php">Bize Ulaşın</a>
                <a href="fotograf-kaynaklari.html">Fotoğraf Kaynakları</a>
            </nav>
        </div>

    </div>

    <div class="pati-footer-taban">
        <div class="pati-footer-taban-icerik">
            <span>© Aris Pati Yuvası</span>
            <span>Satın alma, sahiplen.</span>
            <a href="#aris-ortak-ust">Yukarı dön ↑</a>
        </div>
    </div>
</footer>

</body>
</html>