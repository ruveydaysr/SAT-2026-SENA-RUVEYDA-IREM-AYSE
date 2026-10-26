<?php
session_start();

if (!isset($_SESSION["uye_id"])) {
    header("Location: giris.php");
    exit;
}

require_once __DIR__ . "/baglanti.php";

$uye_id = (int)$_SESSION["uye_id"];

function guvenli($deger)
{
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

function filtre_degeri($alan)
{
    return is_string($_GET[$alan] ?? null)
        ? trim($_GET[$alan])
        : "";
}

function veri_getir($baglanti, $sql, $tipler, ...$degerler)
{
    $sorgu = mysqli_prepare($baglanti, $sql);
    mysqli_stmt_bind_param($sorgu, $tipler, ...$degerler);
    mysqli_stmt_execute($sorgu);

    $sonuc = mysqli_stmt_get_result($sorgu);
    $veriler = mysqli_fetch_all($sonuc, MYSQLI_ASSOC);

    mysqli_stmt_close($sorgu);
    return $veriler;
}

function durum_sinifi($durum)
{
    $siniflar = [
        "Bekliyor" => "bekliyor",
        "Onaylandı" => "onaylandi",
        "Reddedildi" => "reddedildi"
    ];

    return $siniflar[$durum] ?? "";
}

function profil_fotografi($fotograf)
{
    $fotograf = (string)$fotograf;
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

$arama = filtre_degeri("arama");
$siralama = filtre_degeri("siralama");
$durum = filtre_degeri("durum");

if (!in_array($siralama, ["yeni", "eski"], true)) {
    $siralama = "yeni";
}

$gecerli_durumlar = ["Onaylandı", "Bekliyor", "Reddedildi"];

if (!in_array($durum, $gecerli_durumlar, true)) {
    $durum = "";
}

$sayfa = filter_input(INPUT_GET, "sayfa", FILTER_VALIDATE_INT);
$sayfa = $sayfa && $sayfa > 0 ? $sayfa : 1;
$limit = 6;

$where = "uye_id = ?
          AND (hayvan_adi LIKE ? OR ilan_basligi LIKE ?)";

$arama_kelimesi = "%" . $arama . "%";

$parametreler = [$uye_id, $arama_kelimesi, $arama_kelimesi];
$tipler = "iss";

if ($durum !== "") {
    $where .= " AND durum = ?";
    $parametreler[] = $durum;
    $tipler .= "s";
}

$sayim = veri_getir(
    $baglanti,
    "SELECT COUNT(*) AS toplam FROM ilanlar WHERE " . $where,
    $tipler,
    ...$parametreler
);

$toplam_ilan = (int)$sayim[0]["toplam"];
$toplam_sayfa = max(1, (int)ceil($toplam_ilan / $limit));
$sayfa = min($sayfa, $toplam_sayfa);
$baslangic = ($sayfa - 1) * $limit;

$sira_sql = $siralama === "eski" ? "id ASC" : "id DESC";

$liste_parametreleri = $parametreler;
$liste_parametreleri[] = $limit;
$liste_parametreleri[] = $baslangic;

$ilanlar = veri_getir(
    $baglanti,
    "SELECT * FROM ilanlar WHERE " . $where
        . " ORDER BY " . $sira_sql . " LIMIT ? OFFSET ?",
    $tipler . "ii",
    ...$liste_parametreleri
);

$basvurular = veri_getir(
    $baglanti,
    "SELECT b.id, b.ilan_id, b.durum, b.basvuru_tarihi,
            i.hayvan_adi, i.tur, i.sehir
     FROM sahiplenme_basvurulari b
     LEFT JOIN ilanlar i ON b.ilan_id = i.id
     WHERE b.uye_id = ?
     ORDER BY b.id DESC",
    "i",
    $uye_id
);

$favoriler = veri_getir(
    $baglanti,
    "SELECT i.*
     FROM favoriler f
     INNER JOIN ilanlar i ON f.ilan_id = i.id
     WHERE f.uye_id = ? AND i.durum = 'Onaylandı'
     ORDER BY f.id DESC",
    "i",
    $uye_id
);

function sayfa_linki($sayfa, $arama, $durum, $siralama)
{
    return "profil.php?" . http_build_query([
        "arama" => $arama,
        "durum" => $durum,
        "siralama" => $siralama,
        "sayfa" => $sayfa
    ]);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim | Aris Pati Yuvası</title>

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

        /* Profil bölümleri */

        main {
            width: min(1000px, 92%);
            margin: 30px auto;
        }

        .profil-bolum {
            margin-bottom: 24px;
            padding: 26px;
            border: 1px solid #ded6c6;
            border-radius: 14px;
            background: #fffdf8;
        }

        .profil-bolum h1,
        .profil-bolum h2 {
            margin: 0 0 20px;
            font-size: 23px;
        }

        .profil-bolum > p {
            color: #687361;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .profil-bilgileri {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .profil-bilgileri p {
            margin: 0;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .profil-bilgileri strong {
            display: block;
            margin-bottom: 4px;
            color: #687361;
            font-size: 13px;
        }

        .bolum-baslik {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .bolum-baslik h2 {
            margin: 0;
        }

        .sayac {
            color: #687361;
            font-size: 13px;
        }

        /* Filtreler */

        .profil-filtre {
            display: grid;
            grid-template-columns: minmax(0, 2fr) repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 22px;
        }

        .filtre-grup label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: bold;
        }

        .filtre-grup input,
        .filtre-grup select {
            width: 100%;
            height: 42px;
            padding: 10px;
            border: 1px solid #ded6c6;
            border-radius: 8px;
            background: white;
            color: #284b32;
            font: inherit;
            font-size: 14px;
        }

        .filtre-islemler {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            grid-column: 1 / -1;
        }

        /* Kartlar */

        .profil-kart {
            margin-bottom: 16px;
            padding: 20px;
            border: 1px solid #ded6c6;
            border-radius: 12px;
            background: #f7f5ee;
            overflow-wrap: anywhere;
        }

        .profil-kart:last-child {
            margin-bottom: 0;
        }

        .kart-icerik {
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }

        .profil-fotograf {
            display: block;
            flex-shrink: 0;
            width: 160px;
            height: 140px;
            border-radius: 9px;
            background: #e6eddf;
            object-fit: contain;
        }

        .kart-bilgi {
            flex: 1;
            min-width: 0;
        }

        .profil-kart h3 {
            margin: 0 0 12px;
            font-size: 18px;
            line-height: 1.5;
        }

        .profil-kart p {
            margin: 7px 0;
            font-size: 14px;
            line-height: 1.65;
        }

        .kart-islemler {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }

        .kart-islemler form {
            margin: 0;
        }

        .buton {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 39px;
            padding: 10px 17px;
            border: 0;
            border-radius: 8px;
            background: #426b46;
            color: white;
            font: inherit;
            font-size: 13px;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
        }

        .buton:hover {
            background: #284b32;
        }

        .buton.acik {
            background: #eee5d3;
            color: #284b32;
        }

        .buton.acik:hover {
            background: #e2d6bd;
        }

        .buton.sil {
            background: #a64740;
            color: white;
        }

        .buton.sil:hover {
            background: #873b35;
        }

        .profil-durum {
            display: inline-block;
            padding: 4px 9px;
            border-radius: 6px;
            background: #eee5d3;
            font-size: 13px;
            font-weight: bold;
        }

        .profil-durum.bekliyor {
            background: #fff0cd;
            color: #805b00;
        }

        .profil-durum.onaylandi {
            background: #e0edda;
            color: #2f6334;
        }

        .profil-durum.reddedildi {
            background: #f8e1dd;
            color: #9b3b33;
        }

        .red-nedeni {
            color: #9b3b33;
        }

        .profil-sayfalama {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 22px;
        }

        .profil-sayfalama a {
            padding: 9px 13px;
            border: 1px solid #ded6c6;
            border-radius: 8px;
            background: #fffdf8;
            color: #284b32;
            text-decoration: none;
        }

        .profil-sayfalama a:hover {
            background: #e6eddf;
        }

        .profil-sayfalama a[aria-current="page"] {
            background: #426b46;
            color: white;
            border-color: #426b46;
        }

        footer {
            padding: 0 20px 24px;
            color: #687361;
            font-size: 13px;
            text-align: center;
        }

        a:focus-visible,
        button:focus-visible,
        input:focus-visible,
        select:focus-visible {
            outline: 2px solid #284b32;
            outline-offset: 3px;
        }

        .ilan-menu a:focus-visible {
            outline-color: #eee5d3;
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

            .profil-filtre {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .profil-bolum {
                padding: 18px;
            }

            .profil-bilgileri {
                grid-template-columns: 1fr;
            }

            .kart-icerik {
                flex-direction: column;
                gap: 15px;
            }

            .profil-fotograf {
                width: 100%;
                height: 200px;
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
    <section class="profil-bolum">
        <h1>Profil Bilgilerim</h1>

        <div class="profil-bilgileri">
            <p>
                <strong>Ad Soyad</strong>
                <?php echo guvenli($_SESSION["uye_adsoyad"] ?? ""); ?>
            </p>

            <p>
                <strong>E-posta</strong>
                <?php echo guvenli($_SESSION["uye_email"] ?? ""); ?>
            </p>
        </div>

        <div class="kart-islemler">
            <a href="mesajlar.php" class="buton">Mesajlarımı Gör</a>
            <a href="ilan-ver.php" class="buton acik">Yeni İlan Ver</a>
        </div>
    </section>

    <section class="profil-bolum">
        <div class="bolum-baslik">
            <h2>İlanlarım</h2>
            <span class="sayac"><?php echo $toplam_ilan; ?> ilan bulundu</span>
        </div>

        <form method="GET" action="profil.php" class="profil-filtre">
            <div class="filtre-grup">
                <label for="arama">İlan Ara</label>
                <input
                    type="text"
                    id="arama"
                    name="arama"
                    placeholder="Hayvan adı veya ilan başlığı..."
                    value="<?php echo guvenli($arama); ?>"
                >
            </div>

            <div class="filtre-grup">
                <label for="durum">İlan Durumu</label>
                <select id="durum" name="durum">
                    <option value="">Tüm Durumlar</option>

                    <?php foreach ($gecerli_durumlar as $secenek): ?>
                        <option
                            value="<?php echo guvenli($secenek); ?>"
                            <?php echo $durum === $secenek ? "selected" : ""; ?>
                        >
                            <?php echo guvenli($secenek); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filtre-grup">
                <label for="siralama">Sıralama</label>
                <select id="siralama" name="siralama">
                    <option value="yeni" <?php echo $siralama === "yeni" ? "selected" : ""; ?>>
                        En Yeni
                    </option>
                    <option value="eski" <?php echo $siralama === "eski" ? "selected" : ""; ?>>
                        En Eski
                    </option>
                </select>
            </div>

            <div class="filtre-islemler">
                <button type="submit" class="buton">Ara</button>
                <a href="profil.php" class="buton acik">Temizle</a>
            </div>
        </form>

        <?php if ($ilanlar): ?>
            <?php foreach ($ilanlar as $ilan): ?>
                <?php
                $foto = profil_fotografi($ilan["fotograf"] ?? "");
                ?>

                <article class="profil-kart">
                    <div class="kart-icerik">
                        <?php if ($foto !== ""): ?>
                            <img
                                src="<?php echo guvenli($foto); ?>"
                                alt="<?php echo guvenli($ilan["hayvan_adi"]); ?>"
                                class="profil-fotograf"
                                loading="lazy"
                            >
                        <?php endif; ?>

                        <div class="kart-bilgi">
                            <h3><?php echo guvenli(
                                $ilan["ilan_basligi"] ?: $ilan["hayvan_adi"]
                            ); ?></h3>

                            <p>
                                <strong>Hayvan Adı:</strong>
                                <?php echo guvenli($ilan["hayvan_adi"]); ?>
                            </p>
                            <p>
                                <strong>Tür:</strong>
                                <?php echo guvenli($ilan["tur"]); ?>
                                · <strong>Yaş:</strong>
                                <?php echo (int)$ilan["yas"]; ?>
                            </p>
                            <p>
                                <strong>Şehir:</strong>
                                <?php echo guvenli($ilan["sehir"]); ?>
                            </p>
                            <p>
                                <strong>Durum:</strong>
                                <span class="profil-durum <?php echo durum_sinifi($ilan["durum"]); ?>">
                                    <?php echo guvenli($ilan["durum"]); ?>
                                </span>
                            </p>

                            <?php if ($ilan["durum"] === "Reddedildi"): ?>
                                <p class="red-nedeni">
                                    <strong>Reddedilme Nedeni:</strong>
                                    <?php echo guvenli($ilan["red_nedeni"] ?? ""); ?>
                                </p>
                            <?php endif; ?>

                            <div class="kart-islemler">
                                <a
                                    href="ilan-duzenle.php?id=<?php echo (int)$ilan["id"]; ?>"
                                    class="buton"
                                >
                                    Düzenle
                                </a>

                                <a
                                    href="ilan-sil.php?id=<?php echo (int)$ilan["id"]; ?>"
                                    class="buton sil"
                                    onclick="return confirm('Bu ilanı silmek istediğinize emin misiniz?');"
                                >
                                    Sil
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Seçtiğiniz kriterlere uygun ilanınız bulunmuyor.</p>
        <?php endif; ?>

        <?php if ($toplam_sayfa > 1): ?>
            <nav class="profil-sayfalama" aria-label="İlanlarım sayfaları">
                <?php if ($sayfa > 1): ?>
                    <a href="<?php echo guvenli(
                        sayfa_linki($sayfa - 1, $arama, $durum, $siralama)
                    ); ?>">← Önceki</a>
                <?php endif; ?>

                <?php
                $ilk = max(1, $sayfa - 2);
                $son = min($toplam_sayfa, $ilk + 4);
                $ilk = max(1, $son - 4);
                ?>

                <?php for ($i = $ilk; $i <= $son; $i++): ?>
                    <a
                        href="<?php echo guvenli(
                            sayfa_linki($i, $arama, $durum, $siralama)
                        ); ?>"
                        <?php echo $i === $sayfa ? 'aria-current="page"' : ""; ?>
                    >
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($sayfa < $toplam_sayfa): ?>
                    <a href="<?php echo guvenli(
                        sayfa_linki($sayfa + 1, $arama, $durum, $siralama)
                    ); ?>">Sonraki →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>

    <section class="profil-bolum">
        <h2>Sahiplenme Başvurularım</h2>

        <?php if ($basvurular): ?>
            <?php foreach ($basvurular as $basvuru): ?>
                <article class="profil-kart">
                    <h3><?php echo guvenli(
                        $basvuru["hayvan_adi"] ?? "İlan silinmiş"
                    ); ?></h3>

                    <p>
                        <strong>Başvuru No:</strong>
                        <?php echo (int)$basvuru["id"]; ?>
                    </p>
                    <p>
                        <strong>Hayvan Türü:</strong>
                        <?php echo guvenli($basvuru["tur"] ?? "-"); ?>
                    </p>
                    <p>
                        <strong>Şehir:</strong>
                        <?php echo guvenli($basvuru["sehir"] ?? "-"); ?>
                    </p>
                    <p>
                        <strong>Başvuru Tarihi:</strong>
                        <?php echo guvenli($basvuru["basvuru_tarihi"]); ?>
                    </p>
                    <p>
                        <strong>Başvuru Durumu:</strong>
                        <span class="profil-durum <?php echo durum_sinifi($basvuru["durum"]); ?>">
                            <?php echo guvenli($basvuru["durum"]); ?>
                        </span>
                    </p>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Henüz sahiplenme başvurusu yapmadınız.</p>
        <?php endif; ?>
    </section>

    <section class="profil-bolum">
        <h2>❤️ Favorilerim</h2>

        <?php if ($favoriler): ?>
            <?php foreach ($favoriler as $favori): ?>
                <article class="profil-kart">
                    <h3><?php echo guvenli(
                        $favori["ilan_basligi"] ?: "Hayvan Sahiplendirme İlanı"
                    ); ?></h3>

                    <p>
                        <strong>Hayvan Adı:</strong>
                        <?php echo guvenli($favori["hayvan_adi"]); ?>
                    </p>
                    <p>
                        <strong>Tür:</strong>
                        <?php echo guvenli($favori["tur"]); ?>
                        · <strong>Yaş:</strong>
                        <?php echo (int)$favori["yas"]; ?>
                    </p>
                    <p>
                        <strong>Şehir:</strong>
                        <?php echo guvenli($favori["sehir"]); ?>
                    </p>

                    <div class="kart-islemler">
                        <a
                            href="ilan-detay.php?id=<?php echo (int)$favori["id"]; ?>"
                            class="buton"
                        >
                            İlanı İncele
                        </a>

                        <form action="favori-sil.php" method="POST">
                            <input
                                type="hidden"
                                name="ilan_id"
                                value="<?php echo (int)$favori["id"]; ?>"
                            >
                            <button type="submit" class="buton acik">
                                ♡ Favorilerden Çıkar
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Henüz favorilerinize eklediğiniz onaylı bir ilan bulunmuyor.</p>
        <?php endif; ?>
    </section>
</main>

<footer>
    <p>© 2026 Aris Pati Yuvası · Hayvan Sahiplendirme Platformu</p>
</footer>
</body>
</html>