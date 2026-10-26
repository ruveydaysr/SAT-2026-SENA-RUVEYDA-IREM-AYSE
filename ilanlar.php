<?php
session_start();
require_once __DIR__ . "/baglanti.php";

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

$tur = filtre_degeri("tur");
$sehir = filtre_degeri("sehir");
$yas = filtre_degeri("yas");
$cinsiyet = filtre_degeri("cinsiyet");
$arama = filtre_degeri("arama");
$siralama = filtre_degeri("siralama");

$siralamalar = [
    "yeni" => "En Yeni",
    "eski" => "En Eski",
    "yas_artan" => "Yaşı Küçükten Büyüğe",
    "yas_azalan" => "Yaşı Büyükten Küçüğe"
];

if (!isset($siralamalar[$siralama])) {
    $siralama = "yeni";
}

$turler = [
    "Kedi", "Köpek", "Kuş", "Tavşan",
    "Balık", "Kemirgen", "Sürüngen", "Diğer"
];

$sehirler = [
    "Adana", "Adıyaman", "Afyonkarahisar", "Ağrı",
    "Aksaray", "Amasya", "Ankara", "Antalya",
    "Ardahan", "Artvin", "Aydın", "Balıkesir",
    "Bartın", "Batman", "Bayburt", "Bilecik",
    "Bingöl", "Bitlis", "Bolu", "Burdur",
    "Bursa", "Çanakkale", "Çankırı", "Çorum",
    "Denizli", "Diyarbakır", "Düzce", "Edirne",
    "Elazığ", "Erzincan", "Erzurum", "Eskişehir",
    "Gaziantep", "Giresun", "Gümüşhane", "Hakkari",
    "Hatay", "Iğdır", "Isparta", "İstanbul",
    "İzmir", "Kahramanmaraş", "Karabük", "Karaman",
    "Kars", "Kastamonu", "Kayseri", "Kırıkkale",
    "Kırklareli", "Kırşehir", "Kilis", "Kocaeli",
    "Konya", "Kütahya", "Malatya", "Manisa",
    "Mardin", "Mersin", "Muğla", "Muş",
    "Nevşehir", "Niğde", "Ordu", "Osmaniye",
    "Rize", "Sakarya", "Samsun", "Siirt",
    "Sinop", "Sivas", "Şanlıurfa", "Şırnak",
    "Tekirdağ", "Tokat", "Trabzon", "Tunceli",
    "Uşak", "Van", "Yalova", "Yozgat", "Zonguldak"
];

$ilan_sayisi = 8;
$sayfa = filter_input(INPUT_GET, "sayfa", FILTER_VALIDATE_INT);
$sayfa = $sayfa && $sayfa > 0 ? $sayfa : 1;

$kosullar = ["durum = 'Onaylandı'"];
$parametreler = [];
$tipler = "";

if ($arama !== "") {
    $kosullar[] = "ilan_basligi LIKE ?";
    $parametreler[] = "%" . $arama . "%";
    $tipler .= "s";
}

foreach (
    ["tur" => $tur, "sehir" => $sehir, "cinsiyet" => $cinsiyet]
    as $alan => $deger
) {
    if ($deger !== "") {
        $kosullar[] = $alan . " = ?";
        $parametreler[] = $deger;
        $tipler .= "s";
    }
}

if ($yas === "yavru") {
    $kosullar[] = "yas < 1";
} elseif ($yas === "genc") {
    $kosullar[] = "yas BETWEEN 1 AND 3";
} elseif ($yas === "yetiskin") {
    $kosullar[] = "yas > 3";
}

$where = implode(" AND ", $kosullar);

$sayma_sorgu = mysqli_prepare(
    $baglanti,
    "SELECT COUNT(*) AS toplam FROM ilanlar WHERE " . $where
);

if ($parametreler) {
    mysqli_stmt_bind_param(
        $sayma_sorgu,
        $tipler,
        ...$parametreler
    );
}

mysqli_stmt_execute($sayma_sorgu);

$toplam_ilan = (int)mysqli_fetch_assoc(
    mysqli_stmt_get_result($sayma_sorgu)
)["toplam"];

mysqli_stmt_close($sayma_sorgu);

$toplam_sayfa = max(1, (int)ceil($toplam_ilan / $ilan_sayisi));
$sayfa = min($sayfa, $toplam_sayfa);
$baslangic = ($sayfa - 1) * $ilan_sayisi;

$siralama_kodlari = [
    "yeni" => "id DESC",
    "eski" => "id ASC",
    "yas_artan" => "yas ASC, id DESC",
    "yas_azalan" => "yas DESC, id DESC"
];

$sql = "SELECT * FROM ilanlar WHERE " . $where
    . " ORDER BY " . $siralama_kodlari[$siralama]
    . " LIMIT ? OFFSET ?";

$liste_parametreleri = $parametreler;
$liste_parametreleri[] = $ilan_sayisi;
$liste_parametreleri[] = $baslangic;
$liste_tipleri = $tipler . "ii";

$sorgu = mysqli_prepare($baglanti, $sql);

mysqli_stmt_bind_param(
    $sorgu,
    $liste_tipleri,
    ...$liste_parametreleri
);

mysqli_stmt_execute($sorgu);
$sonuc = mysqli_stmt_get_result($sorgu);
$ilanlar = mysqli_fetch_all($sonuc, MYSQLI_ASSOC);
mysqli_stmt_close($sorgu);

$sayfa_filtreleri = [
    "arama" => $arama,
    "tur" => $tur,
    "sehir" => $sehir,
    "yas" => $yas,
    "cinsiyet" => $cinsiyet,
    "siralama" => $siralama
];

function sayfa_linki($numara, $filtreler)
{
    $filtreler["sayfa"] = $numara;
    return "ilanlar.php?" . http_build_query($filtreler);
}

function ilan_fotografi($ilan)
{
    $fotograf = (string)($ilan["fotograf"] ?? "");
    $dosya = basename($fotograf);

    if (
        preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$/i', $dosya)
        && is_file(__DIR__ . "/uploads/" . $dosya)
    ) {
        return ["uploads/" . $dosya, false];
    }

    if (
        preg_match(
            '~^images/hayvanlar/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$~i',
            $fotograf
        )
        && is_file(__DIR__ . "/" . $fotograf)
    ) {
        return [$fotograf, false];
    }

    $temsili = [
        "Kedi" => "kedi.jpg",
        "Köpek" => "kopek.jpg",
        "Kuş" => "kus.jpg"
    ];

    $resim = $temsili[$ilan["tur"]] ?? "";

    if ($resim && is_file(__DIR__ . "/images/hayvanlar/" . $resim)) {
        return ["images/hayvanlar/" . $resim, true];
    }

    return ["", false];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlanlar | Aris Pati Yuvası</title>

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

        /* Sayfa içeriği */

        main {
            width: min(1180px, 92%);
            margin: 32px auto;
        }

        .ilan-baslik {
            margin-bottom: 26px;
            text-align: center;
        }

        .ilan-baslik h1 {
            margin: 0 0 12px;
            font-size: 30px;
        }

        .ilan-baslik p {
            margin: 0;
            color: #687361;
            line-height: 1.7;
        }

        .filtre-alani {
            padding: 24px;
            border: 1px solid #ded6c6;
            border-radius: 14px;
            background: #fffdf8;
        }

        .filtre-alani h2 {
            margin: 0 0 20px;
            font-size: 21px;
        }

        .filtreler {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .filtre-grup label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: bold;
        }

        .filtre-grup input,
        .filtre-grup select {
            width: 100%;
            height: 43px;
            padding: 10px;
            border: 1px solid #ded6c6;
            border-radius: 8px;
            background: white;
            color: #284b32;
            font: inherit;
            font-size: 14px;
        }

        .filtre-butonlar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .filtre-buton,
        .temizle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 24px;
            border: 0;
            border-radius: 8px;
            font: inherit;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
        }

        .filtre-buton {
            background: #426b46;
            color: white;
        }

        .temizle {
            background: #eee5d3;
            color: #284b32;
        }

        .sonuc-sayisi {
            margin: 24px 0 16px;
            color: #687361;
            font-size: 14px;
        }

        /* Dört sütun ilan */

        .ilanlar {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            align-items: stretch;
            gap: 20px;
        }

        .ilan-karti {
            display: flex;
            flex-direction: column;
            min-width: 0;
            overflow: hidden;
            border: 1px solid #ded6c6;
            border-radius: 14px;
            background: #fffdf8;
        }

        .ilan-fotograf,
        .foto-yok {
            display: block;
            width: 100%;
            height: 210px;
            margin: 0;
            background: #e6eddf;
            object-fit: contain;
        }

        .foto-yok {
            display: grid;
            place-items: center;
            color: #687361;
            font-size: 18px;
        }

        .foto-not {
            margin: 0;
            padding: 6px;
            background: #eee5d3;
            color: #687361;
            font-size: 11px;
            text-align: center;
        }

        .ilan-bilgi {
            display: flex;
            flex-direction: column;
            flex: 1;
            padding: 18px;
            overflow-wrap: anywhere;
        }

        .ilan-bilgi h3 {
            margin: 0 0 14px;
            font-size: 18px;
            line-height: 1.45;
        }

        .ilan-bilgi p {
            margin: 5px 0;
            font-size: 14px;
            line-height: 1.6;
        }

        .kart-butonlar {
            display: grid;
            gap: 10px;
            margin-top: auto;
            padding-top: 18px;
        }

        .detay-buton,
        .favori-buton {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 41px;
            padding: 10px;
            border: 1px solid #426b46;
            border-radius: 8px;
            font: inherit;
            font-size: 13px;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
        }

        .detay-buton {
            background: #426b46;
            color: white;
        }

        .favori-buton {
            background: transparent;
            color: #426b46;
        }

        .favori-form {
            margin: 0;
        }

        .detay-buton:hover,
        .filtre-buton:hover {
            background: #284b32;
        }

        .favori-buton:hover {
            background: #e6eddf;
        }

        .favori-buton:disabled {
            opacity: 0.7;
            cursor: default;
        }

        .bos-sonuc {
            grid-column: 1 / -1;
            padding: 26px;
            border: 1px solid #ded6c6;
            border-radius: 12px;
            background: #fffdf8;
            line-height: 1.7;
            text-align: center;
        }

        .sayfalama {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
            margin: 30px 0;
        }

        .sayfalama a {
            padding: 10px 15px;
            border: 1px solid #ded6c6;
            border-radius: 8px;
            background: #fffdf8;
            color: #284b32;
            text-decoration: none;
        }

        .sayfalama a:hover {
            background: #e6eddf;
        }

        .sayfalama a[aria-current="page"] {
            background: #426b46;
            color: white;
            border-color: #426b46;
        }

        footer {
            padding: 22px;
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

            .ilanlar {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .filtreler {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ilan-menu a {
                padding: 8px;
                font-size: 13px;
            }
        }

        @media (max-width: 500px) {
            .ilanlar,
            .filtreler {
                grid-template-columns: 1fr;
            }

            .filtre-alani {
                padding: 18px;
            }

            .ilan-baslik h1 {
                font-size: 25px;
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
    <section class="ilan-baslik">
        <h1>Yuva Arayan Hayvanlar</h1>
        <p>Sana uygun dostunu bulmak için filtreleri kullanabilirsin.</p>
    </section>

    <section class="filtre-alani">
        <h2>Hayvan Ara</h2>

        <form action="ilanlar.php" method="GET">
            <div class="filtreler">
                <div class="filtre-grup">
                    <label for="arama">İlan Başlığı</label>
                    <input type="text" id="arama" name="arama"
                           placeholder="Örn: Yuva arayan yavru kedi"
                           value="<?php echo guvenli($arama); ?>">
                </div>

                <div class="filtre-grup">
                    <label for="siralama">Sıralama</label>
                    <select id="siralama" name="siralama">
                        <?php foreach ($siralamalar as $deger => $yazi): ?>
                            <option value="<?php echo guvenli($deger); ?>"
                                <?php echo $siralama === $deger ? "selected" : ""; ?>>
                                <?php echo guvenli($yazi); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filtre-grup">
                    <label for="tur">Hayvan Türü</label>
                    <select id="tur" name="tur">
                        <option value="">Tümü</option>
                        <?php foreach ($turler as $secenek): ?>
                            <option value="<?php echo guvenli($secenek); ?>"
                                <?php echo $tur === $secenek ? "selected" : ""; ?>>
                                <?php echo guvenli($secenek); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filtre-grup">
                    <label for="sehir">Şehir</label>
                    <select id="sehir" name="sehir">
                        <option value="">Tümü</option>
                        <?php foreach ($sehirler as $secenek): ?>
                            <option value="<?php echo guvenli($secenek); ?>"
                                <?php echo $sehir === $secenek ? "selected" : ""; ?>>
                                <?php echo guvenli($secenek); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filtre-grup">
                    <label for="yas">Yaş</label>
                    <select id="yas" name="yas">
                        <option value="">Tümü</option>

                        <?php foreach ([
                            "yavru" => "1 yaşından küçük",
                            "genc" => "1 - 3 yaş",
                            "yetiskin" => "3 yaşından büyük"
                        ] as $deger => $yazi): ?>
                            <option value="<?php echo guvenli($deger); ?>"
                                <?php echo $yas === $deger ? "selected" : ""; ?>>
                                <?php echo guvenli($yazi); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filtre-grup">
                    <label for="cinsiyet">Cinsiyet</label>
                    <select id="cinsiyet" name="cinsiyet">
                        <option value="">Tümü</option>
                        <?php foreach (["Dişi", "Erkek"] as $secenek): ?>
                            <option value="<?php echo guvenli($secenek); ?>"
                                <?php echo $cinsiyet === $secenek ? "selected" : ""; ?>>
                                <?php echo guvenli($secenek); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="filtre-butonlar">
                <button type="submit" class="filtre-buton">Filtrele</button>
                <a href="ilanlar.php" class="temizle">Temizle</a>
            </div>
        </form>
    </section>

    <p class="sonuc-sayisi">
        <?php echo $toplam_ilan; ?> ilan bulundu.
    </p>

    <section class="ilanlar" aria-label="Hayvan ilanları">
        <?php if ($ilanlar): ?>
            <?php foreach ($ilanlar as $ilan): ?>
                <?php
                [$foto_yolu, $temsili] = ilan_fotografi($ilan);
                ?>

                <article class="ilan-karti">
                    <?php if ($foto_yolu): ?>
                        <img src="<?php echo guvenli($foto_yolu); ?>"
                             alt="<?php echo guvenli($ilan["hayvan_adi"]); ?>"
                             class="ilan-fotograf"
                             loading="lazy">
                    <?php else: ?>
                        <div class="foto-yok">Fotoğraf Yok</div>
                    <?php endif; ?>

                    <?php if ($temsili): ?>
                        <p class="foto-not">Temsili fotoğraf</p>
                    <?php endif; ?>

                    <div class="ilan-bilgi">
                        <h3><?php echo guvenli(
                            $ilan["ilan_basligi"] ?: "Hayvan Sahiplendirme İlanı"
                        ); ?></h3>

                        <p>
                            <strong>Hayvan Adı:</strong>
                            <?php echo guvenli($ilan["hayvan_adi"]); ?>
                        </p>
                        <p>
                            <strong>Tür:</strong>
                            <?php echo guvenli($ilan["tur"]); ?>
                        </p>
                        <p>
                            <strong>Yaş:</strong>
                            <?php echo (int)$ilan["yas"]; ?>
                        </p>
                        <p>
                            <strong>Cinsiyet:</strong>
                            <?php echo guvenli($ilan["cinsiyet"]); ?>
                        </p>
                        <p>
                            <strong>Şehir:</strong>
                            <?php echo guvenli($ilan["sehir"]); ?>
                        </p>

                        <div class="kart-butonlar">
                            <a href="ilan-detay.php?id=<?php echo (int)$ilan["id"]; ?>"
                               class="detay-buton">
                                İlanı İncele
                            </a>

                            <?php if (isset($_SESSION["uye_id"])): ?>
                                <form action="favori-islem.php" method="POST"
                                      class="favori-form">
                                    <input type="hidden" name="ilan_id"
                                           value="<?php echo (int)$ilan["id"]; ?>">
                                    <button type="submit" class="favori-buton">
                                        ♡ Favorilere Ekle
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="giris.php" class="favori-buton">
                                    ♡ Favorilere Ekle
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="bos-sonuc">
                Aradığınız kriterlere uygun onaylanmış ilan bulunmuyor.
            </p>
        <?php endif; ?>
    </section>

    <?php if ($toplam_sayfa > 1): ?>
        <nav class="sayfalama" aria-label="İlan sayfaları">
            <?php if ($sayfa > 1): ?>
                <a href="<?php echo guvenli(
                    sayfa_linki($sayfa - 1, $sayfa_filtreleri)
                ); ?>">« Önceki</a>
            <?php endif; ?>

            <?php
            $ilk = max(1, $sayfa - 2);
            $son = min($toplam_sayfa, $ilk + 4);
            $ilk = max(1, $son - 4);
            ?>

            <?php for ($i = $ilk; $i <= $son; $i++): ?>
                <a href="<?php echo guvenli(
                    sayfa_linki($i, $sayfa_filtreleri)
                ); ?>"
                   <?php echo $i === $sayfa ? 'aria-current="page"' : ""; ?>>
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($sayfa < $toplam_sayfa): ?>
                <a href="<?php echo guvenli(
                    sayfa_linki($sayfa + 1, $sayfa_filtreleri)
                ); ?>">Sonraki »</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</main>

<footer>
    <p>© 2026 Aris Pati Yuvası · Hayvan Sahiplendirme Platformu</p>
</footer>

<script>
document.querySelectorAll(".favori-form").forEach(function (form) {
    form.addEventListener("submit", async function (event) {
        event.preventDefault();

        const buton = form.querySelector("button");
        buton.disabled = true;

        try {
            const cevap = await fetch(form.action, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: new FormData(form)
            });

            if (cevap.redirected) {
                window.location.href = cevap.url;
                return;
            }

            const sonuc = await cevap.json();

            if (!cevap.ok || !sonuc.basarili) {
                throw new Error("Favori işlemi başarısız.");
            }

            buton.textContent = "♥ Favorilere Eklendi";
        } catch (hata) {
            alert("İlan favorilere eklenemedi. Lütfen tekrar deneyiniz.");
            buton.disabled = false;
        }
    });
});
</script>
</body>
</html>