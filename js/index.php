<?php
session_start();

require_once __DIR__ . "/baglanti.php";

function guvenli($deger)
{
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

$sonuc = mysqli_query(
    $baglanti,
    "SELECT baslik, aciklama, buton_yazisi
     FROM anasayfa_icerik WHERE id = 1"
);

$icerik = mysqli_fetch_assoc($sonuc) ?: [
    "baslik" => "Yeni Bir Dost Edin",
    "aciklama" => "İlanları inceleyerek sana uygun yeni dostunu bulabilirsin.",
    "buton_yazisi" => "İlanları İncele"
];

$sonuc = mysqli_query(
    $baglanti,
    "SELECT * FROM anasayfa_ayarlar WHERE id = 1"
);

$ayar = mysqli_fetch_assoc($sonuc) ?: [
    "buton_linki" => "ilanlar.php",
    "kapak" => null,
    "duyuru" => "",
    "duyuru_aktif" => 0,
    "tanitim_aktif" => 1,
    "kategoriler_aktif" => 1,
    "vitrin_aktif" => 1,
    "vitrin_idler" => "[]"
];

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

// Varsayılan kapak görseli
$kapak_yolu = is_file(__DIR__ . "/images/anasayfa-kapak.png")
    ? "images/anasayfa-kapak.png"
    : "";

// Adminin yüklediği kapak varsa onu kullan
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

$secili_idler = json_decode($ayar["vitrin_idler"], true);
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
    // Liste yalnızca doğrulanmış pozitif tam sayılardan oluşur.
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

$kategoriler = [
    "🐱 Kedi",
    "🐶 Köpek",
    "🐦 Kuş",
    "🐠 Balık",
    "🐰 Tavşan",
    "🐹 Kemirgen",
    "🦎 Sürüngen",
    "🐾 Diğer"
];
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Aris Pati Yuvası | Hayvan Sahiplendirme</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/anasayfa.css?v=3">

    <style>
        body.ay-sayfa {
            margin: 0;
            background: #f5f0e6;
            color: #284b32;
        }

        .ay-sayfa main {
            padding: 0;
        }

        .ay-sayfa .ilan-ust {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 18px;
            padding: 14px 5%;
            background: #fffdf8;
            text-align: left;
        }

        .ay-sayfa .ilan-logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #284b32;
            text-decoration: none;
        }

        .ay-sayfa .ilan-logo img {
            display: block;
            width: 48px;
            height: 48px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .ay-sayfa .ilan-logo strong,
        .ay-sayfa .ilan-logo small {
            display: block;
        }

        .ay-sayfa .ilan-logo strong {
            font-size: 20px;
        }

        .ay-sayfa .ilan-logo small {
            margin-top: 5px;
            color: #687361;
            font-size: 11px;
        }

        .ay-sayfa .ilan-menu {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 5px;
            padding: 0;
        }

        .ay-sayfa .ilan-menu a {
            margin: 0;
            padding: 10px 12px;
            border-radius: 8px;
            background: transparent;
            color: #284b32;
            font-size: 14px;
            text-decoration: none;
        }

        .ay-sayfa .ilan-menu a:hover,
        .ay-sayfa .ilan-menu a[aria-current="page"] {
            background: #e6eddf;
        }

        /* Tam genişlikte kapak */

        .ay-sayfa .ay-kapak-bolum {
            width: 100%;
            margin: 0 0 38px;
        }

        .ay-sayfa .ay-hero {
            position: relative;
            isolation: isolate;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 450px;
            padding: 55px 24px;
            overflow: hidden;
            border: 0;
            border-radius: 0;
            background: #426b46;
            text-align: center;
        }

        .ay-sayfa .ay-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -1;
            background: linear-gradient(
                90deg,
                rgba(25, 48, 29, 0.06),
                rgba(25, 48, 29, 0.45) 35%,
                rgba(25, 48, 29, 0.45) 65%,
                rgba(25, 48, 29, 0.06)
            );
        }

        .ay-sayfa .ay-hero.ay-gorselsiz::before {
            background: transparent;
        }

        .ay-sayfa .ay-hero .ay-kapak {
            position: absolute;
            inset: 0;
            z-index: -2;
            display: block;
            width: 100%;
            height: 100%;
            border-radius: 0;
            object-fit: cover;
            object-position: center;
        }

        .ay-sayfa .ay-hero > .ay-hero-yazi {
            width: min(580px, 48%);
        }

        .ay-sayfa .ay-hero h2 {
            margin: 0 0 18px;
            color: #fffdf8;
            font-size: clamp(28px, 3.2vw, 46px);
            line-height: 1.2;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            overflow-wrap: anywhere;
        }

        .ay-sayfa .ay-hero p {
            margin: 0 0 26px;
            color: #fffdf8;
            font-size: 17px;
            line-height: 1.7;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .ay-sayfa .ay-hero .ay-buton {
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
            overflow-wrap: anywhere;
        }

        .ay-sayfa .ay-hero .ay-buton:hover {
            background: #fffdf8;
        }

        .ay-sayfa .ay-kategoriler {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .ay-sayfa .ay-vitrin {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        @media (min-width: 1500px) {
            .ay-sayfa .ay-hero {
                min-height: 520px;
            }
        }

        @media (max-width: 900px) {
            .ay-sayfa .ay-hero {
                min-height: 380px;
            }

            .ay-sayfa .ay-hero > .ay-hero-yazi {
                width: min(550px, 75%);
            }

            .ay-sayfa .ay-vitrin {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .ay-sayfa .ilan-ust {
                padding: 14px 4%;
            }

            .ay-sayfa .ilan-menu {
                width: 100%;
            }

            .ay-sayfa .ilan-menu a {
                padding: 9px;
                font-size: 13px;
            }

            .ay-sayfa .ay-hero {
                min-height: 360px;
                padding: 35px 20px;
            }

            .ay-sayfa .ay-hero::before {
                background: rgba(25, 48, 29, 0.55);
            }

            .ay-sayfa .ay-hero > .ay-hero-yazi {
                width: 100%;
            }

            .ay-sayfa .ay-hero h2 {
                font-size: 30px;
            }

            .ay-sayfa .ay-hero p {
                font-size: 15px;
            }

            .ay-sayfa .ay-kategoriler,
            .ay-sayfa .ay-vitrin {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
</head>

<body class="ay-sayfa">

<header class="ilan-ust">

    <a href="index.php" class="ilan-logo">
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

    <nav class="ilan-menu" aria-label="Site menüsü">
        <a href="index.php" aria-current="page">Ana Sayfa</a>
        <a href="ilanlar.php">İlanlar</a>
        <a href="ilan-ver.php">İlan Ver</a>
        <a href="hakkimizda.php">Hakkımızda</a>
        <a href="iletisim.php">İletişim</a>

        <?php if (isset($_SESSION["uye_id"])): ?>

            <a href="profil.php">
                <?php echo guvenli($_SESSION["uye_adsoyad"] ?? "Profilim"); ?>
            </a>

            <a
                href="cikis.php"
                onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');"
            >
                Çıkış Yap
            </a>

        <?php else: ?>

            <a href="giris.php">Giriş Yap</a>

        <?php endif; ?>
    </nav>

</header>

<main class="ay-kapsam">

    <?php if (
        $ayar["duyuru_aktif"]
        && trim((string)$ayar["duyuru"]) !== ""
    ): ?>
        <div class="ay-duyuru"><?php
            echo guvenli($ayar["duyuru"]);
        ?></div>
    <?php endif; ?>

    <?php if ($ayar["tanitim_aktif"]): ?>

        <section class="ay-kapak-bolum">
            <div class="ay-hero <?php echo $kapak_yolu === "" ? "ay-gorselsiz" : ""; ?>">

                <div class="ay-hero-yazi">
                    <h2><?php echo guvenli($icerik["baslik"]); ?></h2>

                    <p><?php echo guvenli($icerik["aciklama"]); ?></p>

                    <a
                        href="<?php echo guvenli($buton_linki); ?>"
                        class="ay-buton"
                    >
                        <?php echo guvenli($icerik["buton_yazisi"]); ?>
                    </a>
                </div>

                <?php if ($kapak_yolu !== ""): ?>
                    <img
                        src="<?php echo guvenli($kapak_yolu); ?>"
                        alt="Yuva arayan kedi ve köpekler"
                        class="ay-kapak"
                        fetchpriority="high"
                    >
                <?php endif; ?>

            </div>
        </section>

    <?php endif; ?>

    <?php if ($ayar["kategoriler_aktif"]): ?>

        <section class="ay-bolum">
            <h2>Hayvan Kategorileri</h2>

            <div class="ay-kategoriler">
                <?php foreach ($kategoriler as $kategori): ?>
                    <div class="ay-kategori">
                        <?php echo guvenli($kategori); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

    <?php endif; ?>

    <?php if ($ayar["vitrin_aktif"] && $vitrin): ?>

        <section class="ay-bolum">
            <h2>Yuva Arayan Dostlarımız</h2>

            <div class="ay-vitrin">

                <?php foreach ($vitrin as $ilan): ?>

                    <?php
                    $foto = basename(
                        (string)($ilan["fotograf"] ?? "")
                    );

                    $foto_var = preg_match(
                        '/^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$/i',
                        $foto
                    ) === 1
                        && is_file(__DIR__ . "/uploads/" . $foto);
                    ?>

                    <a
                        href="ilan-detay.php?id=<?php echo (int)$ilan["id"]; ?>"
                        class="ay-ilan"
                    >

                        <?php if ($foto_var): ?>
                            <img
                                src="uploads/<?php echo guvenli($foto); ?>"
                                alt="<?php echo guvenli($ilan["hayvan_adi"]); ?>"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div class="ay-foto-yok" aria-label="Fotoğraf yok">
                                🐾
                            </div>
                        <?php endif; ?>

                        <div class="ay-ilan-bilgi">
                            <h3>
                                <?php echo guvenli($ilan["hayvan_adi"]); ?>
                            </h3>

                            <p>
                                <?php echo guvenli($ilan["tur"]); ?>
                                · <?php echo guvenli($ilan["sehir"]); ?>
                            </p>
                        </div>

                    </a>

                <?php endforeach; ?>

            </div>
        </section>

    <?php endif; ?>

</main>

</body>
</html>