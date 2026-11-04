<?php
session_start();
require_once __DIR__ . "/site-ust-ayar.php";

require_once __DIR__ . "/baglanti.php";

/* Dil tercihi: filtreleri ve sayfa numarasını korur. */
if (
    is_string($_GET["lang"] ?? null)
    && in_array($_GET["lang"], ["tr", "en"], true)
) {
    $_SESSION["dil"] = $_GET["lang"];

    $adres_parametreleri = $_GET;
    unset($adres_parametreleri["lang"]);

    $adres = "ilanlar.php";

    if ($adres_parametreleri) {
        $adres .= "?" . http_build_query($adres_parametreleri);
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

function cinsiyet_adi($deger)
{
    if ($deger === "Dişi") {
        return ceviri("Dişi", "Female");
    }

    if ($deger === "Erkek") {
        return ceviri("Erkek", "Male");
    }

    return ceviri("Tümü", "All");
}

function filtre_degeri($alan)
{
    return is_string($_GET[$alan] ?? null)
        ? trim($_GET[$alan])
        : "";
}

function coklu_filtre($alan)
{
    $degerler = $_GET[$alan] ?? [];

    if (is_string($degerler)) {
        $degerler = [$degerler];
    }

    if (!is_array($degerler)) {
        return [];
    }

    $sonuc = [];

    foreach ($degerler as $deger) {
        if (is_string($deger) && trim($deger) !== "") {
            $sonuc[] = trim($deger);
        }
    }

    return array_values(array_unique($sonuc));
}

function veri_getir($baglanti, $sql, $tipler = "", $degerler = [])
{
    $sorgu = mysqli_prepare($baglanti, $sql);

    if ($tipler !== "") {
        mysqli_stmt_bind_param($sorgu, $tipler, ...$degerler);
    }

    mysqli_stmt_execute($sorgu);

    $veriler = mysqli_fetch_all(
        mysqli_stmt_get_result($sorgu),
        MYSQLI_ASSOC
    );

    mysqli_stmt_close($sorgu);

    return $veriler;
}

function sayfa_linki($sayfa, $filtreler)
{
    $filtreler["sayfa"] = $sayfa;

    return "ilanlar.php?" . http_build_query($filtreler);
}

function dil_linki($yeni_dil, $filtreler, $sayfa)
{
    $filtreler["sayfa"] = $sayfa;
    $filtreler["lang"] = $yeni_dil;

    return "ilanlar.php?" . http_build_query($filtreler);
}

function filtre_sil_linki($alan, $deger, $filtreler)
{
    if (is_array($filtreler[$alan] ?? null)) {
        $filtreler[$alan] = array_values(
            array_filter(
                $filtreler[$alan],
                function ($oge) use ($deger) {
                    return $oge !== $deger;
                }
            )
        );
    } else {
        unset($filtreler[$alan]);
    }

    return "ilanlar.php?" . http_build_query($filtreler);
}

function asil_fotograf($fotograf)
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

function ilan_fotografi($ilan)
{
    $fotograf = asil_fotograf($ilan["fotograf"] ?? "");

    if ($fotograf !== "") {
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

$uye_id = (int)($_SESSION["uye_id"] ?? 0);

/* Gerçek favori sayıları */
$favori_sayilari = [];

$favori_kayitlari = veri_getir(
    $baglanti,
    "SELECT ilan_id, COUNT(DISTINCT uye_id) AS toplam
     FROM favoriler
     GROUP BY ilan_id"
);

foreach ($favori_kayitlari as $favori) {
    $favori_sayilari[(int)$favori["ilan_id"]] = (int)$favori["toplam"];
}

/* Kullanıcının favorileri ve mesaj bildirimi */
$benim_favorilerim = [];
$okunmamis_mesaj_var = false;

if ($uye_id > 0) {
    $kayitlar = veri_getir(
        $baglanti,
        "SELECT ilan_id FROM favoriler WHERE uye_id = ?",
        "i",
        [$uye_id]
    );

    foreach ($kayitlar as $kayit) {
        $benim_favorilerim[(int)$kayit["ilan_id"]] = true;
    }

    $mesajlar = veri_getir(
        $baglanti,
        "SELECT id FROM ilan_mesajlari
         WHERE alici_id = ? AND okundu = 0 LIMIT 1",
        "i",
        [$uye_id]
    );

    $okunmamis_mesaj_var = !empty($mesajlar);
}

$kategoriler = [
    "Kedi" => "kedi.png",
    "Köpek" => "kopek.png",
    "Kuş" => "kus.png",
    "Balık" => "balik.png",
    "Tavşan" => "tavsan.png",
    "Kemirgen" => "kemirgen.png",
    "Sürüngen" => "surungen.png",
    "Diğer" => "diger.png"
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

$kayitli_sehirler = veri_getir(
    $baglanti,
    "SELECT DISTINCT sehir FROM ilanlar
     WHERE durum = 'Onaylandı'
       AND sehir IS NOT NULL AND sehir <> ''
     ORDER BY sehir"
);

foreach ($kayitli_sehirler as $kayit) {
    if (!in_array($kayit["sehir"], $sehirler, true)) {
        $sehirler[] = $kayit["sehir"];
    }
}

$siralamalar = [
    "yeni" => ceviri("En Yeni", "Newest"),
    "eski" => ceviri("En Eski", "Oldest"),
    "yas_artan" => ceviri("Yaşı Küçükten Büyüğe", "Age: Youngest First"),
    "yas_azalan" => ceviri("Yaşı Büyükten Küçüğe", "Age: Oldest First")
];

$turler = array_values(
    array_intersect(coklu_filtre("tur"), array_keys($kategoriler))
);

$secili_sehirler = array_values(
    array_intersect(coklu_filtre("sehir"), $sehirler)
);

$arama = filtre_degeri("arama");
$cinsiyet = filtre_degeri("cinsiyet");
$siralama = filtre_degeri("siralama");
$min_yas = filtre_degeri("min_yas");
$max_yas = filtre_degeri("max_yas");
$fotografli = filtre_degeri("fotografli") === "1";
$filtre_hata = "";

if (!isset($siralamalar[$siralama])) {
    $siralama = "yeni";
}

if (!in_array($cinsiyet, ["Dişi", "Erkek"], true)) {
    $cinsiyet = "";
}

/* Eski yaş filtreli bağlantılar */
if ($min_yas === "" && $max_yas === "") {
    $eski_yas = filtre_degeri("yas");

    if ($eski_yas === "yavru") {
        $max_yas = "0";
    } elseif ($eski_yas === "genc") {
        $min_yas = "1";
        $max_yas = "3";
    } elseif ($eski_yas === "yetiskin") {
        $min_yas = "4";
    }
}

foreach ([$min_yas, $max_yas] as $deger) {
    if (
        $deger !== ""
        && filter_var(
            $deger,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 0]]
        ) === false
    ) {
        $filtre_hata = ceviri(
            "Yaş alanlarına sıfır veya daha büyük tam sayı giriniz.",
            "Enter a whole number of zero or greater in the age fields."
        );
    }
}

if (
    $filtre_hata === ""
    && $min_yas !== ""
    && $max_yas !== ""
    && (int)$min_yas > (int)$max_yas
) {
    $filtre_hata = ceviri(
        "En küçük yaş, en büyük yaştan fazla olamaz.",
        "The minimum age cannot be greater than the maximum age."
    );
}

/* Filtreli ilan sorgusu */
$kosullar = ["durum = 'Onaylandı'"];
$parametreler = [];
$tipler = "";

if ($arama !== "") {
    $kosullar[] = "(ilan_basligi LIKE ? OR hayvan_adi LIKE ?)";
    $parametreler[] = "%" . $arama . "%";
    $parametreler[] = "%" . $arama . "%";
    $tipler .= "ss";
}

foreach ([
    "tur" => $turler,
    "sehir" => $secili_sehirler
] as $alan => $degerler) {
    if ($degerler) {
        $isaretler = implode(",", array_fill(0, count($degerler), "?"));
        $kosullar[] = $alan . " IN (" . $isaretler . ")";

        foreach ($degerler as $deger) {
            $parametreler[] = $deger;
            $tipler .= "s";
        }
    }
}

if ($cinsiyet !== "") {
    $kosullar[] = "cinsiyet = ?";
    $parametreler[] = $cinsiyet;
    $tipler .= "s";
}

if ($filtre_hata !== "") {
    $kosullar[] = "1 = 0";
} else {
    if ($min_yas !== "") {
        $kosullar[] = "yas >= ?";
        $parametreler[] = (int)$min_yas;
        $tipler .= "i";
    }

    if ($max_yas !== "") {
        $kosullar[] = "yas <= ?";
        $parametreler[] = (int)$max_yas;
        $tipler .= "i";
    }
}

if ($fotografli) {
    $adaylar = veri_getir(
        $baglanti,
        "SELECT id, fotograf FROM ilanlar
         WHERE " . implode(" AND ", $kosullar)
            . " AND fotograf IS NOT NULL AND fotograf <> ''",
        $tipler,
        $parametreler
    );

    $fotografli_idler = [];

    foreach ($adaylar as $aday) {
        if (asil_fotograf($aday["fotograf"]) !== "") {
            $fotografli_idler[] = (int)$aday["id"];
        }
    }

    $kosullar[] = $fotografli_idler
        ? "id IN (" . implode(",", $fotografli_idler) . ")"
        : "1 = 0";
}

$where = implode(" AND ", $kosullar);

$sayim = veri_getir(
    $baglanti,
    "SELECT COUNT(*) AS toplam FROM ilanlar WHERE " . $where,
    $tipler,
    $parametreler
);

$toplam_ilan = (int)$sayim[0]["toplam"];

$ilan_sayisi = 8;

$sayfa = filter_input(INPUT_GET, "sayfa", FILTER_VALIDATE_INT);
$sayfa = $sayfa && $sayfa > 0 ? $sayfa : 1;

$toplam_sayfa = max(1, (int)ceil($toplam_ilan / $ilan_sayisi));
$sayfa = min($sayfa, $toplam_sayfa);
$baslangic = ($sayfa - 1) * $ilan_sayisi;

$siralama_kodlari = [
    "yeni" => "id DESC",
    "eski" => "id ASC",
    "yas_artan" => "yas ASC, id DESC",
    "yas_azalan" => "yas DESC, id DESC"
];

$liste_parametreleri = $parametreler;
$liste_parametreleri[] = $ilan_sayisi;
$liste_parametreleri[] = $baslangic;

$ilanlar = veri_getir(
    $baglanti,
    "SELECT * FROM ilanlar WHERE " . $where
        . " ORDER BY " . $siralama_kodlari[$siralama]
        . " LIMIT ? OFFSET ?",
    $tipler . "ii",
    $liste_parametreleri
);

$sayfa_filtreleri = [
    "tur" => $turler,
    "sehir" => $secili_sehirler,
    "arama" => $arama,
    "cinsiyet" => $cinsiyet,
    "min_yas" => $min_yas,
    "max_yas" => $max_yas,
    "siralama" => $siralama
];

if ($fotografli) {
    $sayfa_filtreleri["fotografli"] = "1";
}

/* Seçili filtre etiketleri */
$etiketler = [];

foreach ($turler as $deger) {
    $etiketler[] = ["tur", $deger, tur_adi($deger)];
}

foreach ($secili_sehirler as $deger) {
    $etiketler[] = ["sehir", $deger, $deger];
}

foreach ([
    "arama" => [
        $arama,
        ceviri("Arama: ", "Search: ") . $arama
    ],
    "cinsiyet" => [
        $cinsiyet,
        cinsiyet_adi($cinsiyet)
    ],
    "min_yas" => [
        $min_yas,
        ceviri(
            "En az " . $min_yas . " yaş",
            "Minimum age: " . $min_yas
        )
    ],
    "max_yas" => [
        $max_yas,
        ceviri(
            "En fazla " . $max_yas . " yaş",
            "Maximum age: " . $max_yas
        )
    ]
] as $alan => $bilgi) {
    if ($bilgi[0] !== "") {
        $etiketler[] = [$alan, $bilgi[0], $bilgi[1]];
    }
}

if ($fotografli) {
    $etiketler[] = [
        "fotografli",
        "1",
        ceviri("Yalnızca fotoğraflı", "With photos only")
    ];
}

/* JavaScript içinde gösterilecek metinler */
$js_metinleri = [
    "favoride" => ceviri("Favorilerinizde", "In your favourites"),
    "hata" => ceviri(
        "Eklenemedi. Lütfen tekrar dene.",
        "Could not add to favourites. Please try again."
    )
];
?>

<!DOCTYPE html>
<html lang="<?= guvenli($dil) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= guvenli(ceviri(
        "İlanlar | Aris Pati Yuvası",
        "Listings | Aris Pati Yuvası"
    )) ?></title>

    <style>
        :root {
            --yesil: #426b46;
            --koyu: #284b32;
            --krem: #f5f0e6;
            --beyaz: #fffdf8;
            --acik: #e6eddf;
            --cizgi: #dedccd;
            --metin: #687361;
        }

        * { box-sizing: border-box; }
        html { scrollbar-gutter: stable; }

        body {
            margin: 0;
            background: var(--krem);
            color: var(--koyu);
            font-family: Arial, sans-serif;
        }

        a { color: inherit; }
        button, input, select { font: inherit; }
        img { max-width: 100%; }

        a:focus-visible, button:focus-visible,
        input:focus-visible, select:focus-visible,
        summary:focus-visible {
            outline: 3px solid #8fa97d;
            outline-offset: 3px;
        }

        .ilan-ust {
            position: relative;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 18px;
            min-height: 88px;
            padding: 16px 24px;
            border-bottom: 1px solid #35583b;
            background: var(--yesil);
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
            background: var(--beyaz);
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

        .dil-secici a:hover { background: var(--koyu); }

        .dil-secici a[aria-current="true"] {
            background: var(--krem);
            color: var(--koyu);
        }

        .ilan-menu {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 4px;
            margin-left: auto;
        }

        .ilan-menu > a, .kategori-baslik {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 40px;
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
            background: var(--koyu);
        }

        .ilan-menu > a[aria-current="page"] {
            background: #eee5d3;
            color: var(--koyu);
            font-weight: bold;
        }

        .mesaj-bildirim {
            width: 9px;
            height: 9px;
            border: 1px solid white;
            border-radius: 50%;
            background: #ef5350;
        }

        .kategori-menu { position: relative; }

        .kategori-acilir {
            position: absolute;
            top: 100%;
            right: 0;
            display: none;
            width: 210px;
            max-width: calc(100vw - 36px);
            padding: 8px;
            border: 1px solid var(--cizgi);
            border-radius: 12px;
            background: var(--beyaz);
            box-shadow: 0 12px 30px rgba(40,75,50,.16);
        }

        .kategori-menu:hover .kategori-acilir,
        .kategori-menu:focus-within .kategori-acilir {
            display: block;
        }

        .kategori-acilir a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 14px;
            text-decoration: none;
        }

        .kategori-acilir a:hover { background: var(--acik); }

        .kategori-acilir img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        main {
            width: min(1440px, calc(100% - 48px));
            margin: 24px auto 45px;
        }

        .yol {
            display: flex;
            gap: 9px;
            margin-bottom: 20px;
            color: var(--metin);
            font-size: 12px;
        }

        .yol a { text-decoration: none; }

        .sayfa-baslik {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 25px;
            padding: 26px 30px;
            border: 1px solid #d7dfcc;
            border-radius: 16px;
            background: var(--acik);
        }

        .sayfa-baslik small {
            display: block;
            margin-bottom: 9px;
            color: var(--yesil);
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1.5px;
        }

        .sayfa-baslik h1 {
            margin: 0;
            font-size: clamp(25px,3vw,35px);
            letter-spacing: -1px;
        }

        .sayfa-baslik p {
            margin: 10px 0 0;
            color: var(--metin);
            font-size: 14px;
            line-height: 1.6;
        }

        .ilan-ver-link {
            padding: 12px 17px;
            border-radius: 9px;
            background: var(--yesil);
            color: white;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
        }

        .liste-duzeni {
            display: grid;
            grid-template-columns: 235px minmax(0,1fr);
            align-items: start;
            gap: 24px;
        }

        .filtre-panel {
            overflow: hidden;
            border: 1px solid var(--cizgi);
            border-radius: 13px;
            background: var(--beyaz);
        }

        .filtre-panel > summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 18px;
            border-bottom: 1px solid var(--cizgi);
            list-style: none;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .filtre-panel > summary::-webkit-details-marker { display: none; }

        .filtre-panel > summary span {
            color: var(--metin);
            font-size: 12px;
            font-weight: normal;
        }

        .filtre-icerik { padding: 18px; }

        .filtre-grup {
            margin-bottom: 20px;
            padding-bottom: 18px;
            border-bottom: 1px solid #e8e6dc;
        }

        .filtre-grup h2, .filtre-grup > label {
            display: block;
            margin: 0 0 11px;
            font-size: 13px;
            font-weight: bold;
        }

        .secenek {
            display: flex;
            align-items: center;
            gap: 9px;
            min-height: 31px;
            color: #53634e;
            font-size: 13px;
            cursor: pointer;
        }

        .secenek input {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
            margin: 0;
            accent-color: var(--yesil);
        }

        .filtre-icerik input[type="search"], .yas-alanlari input {
            width: 100%;
            min-width: 0;
            min-height: 38px;
            padding: 9px;
            border: 1px solid var(--cizgi);
            border-radius: 7px;
            background: white;
            color: var(--koyu);
            font-size: 12px;
        }

        .filtre-icerik input[type="search"] { margin-bottom: 10px; }

        .sehir-listesi {
            max-height: 210px;
            overflow-y: auto;
        }

        .yas-alanlari {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .yas-alanlari label {
            display: block;
            margin-bottom: 5px;
            color: var(--metin);
            font-size: 11px;
        }

        .filtre-not {
            margin: 8px 0 0;
            color: var(--metin);
            font-size: 11px;
            line-height: 1.5;
        }

        .filtre-buton, .temizle {
            display: block;
            width: 100%;
            padding: 11px 8px;
            border: 0;
            border-radius: 8px;
            font-size: 13px;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
        }

        .filtre-buton { background: var(--yesil); color: white; }
        .temizle { margin-top: 8px; background: #eee5d3; }
        .sonuclar { min-width: 0; }

        .arama-satiri {
            display: flex;
            gap: 10px;
            margin-bottom: 18px;
        }

        .arama-satiri input {
            flex: 1;
            min-width: 0;
            min-height: 45px;
            padding: 12px 15px;
            border: 1px solid var(--cizgi);
            border-radius: 9px;
            background: var(--beyaz);
            color: var(--koyu);
            font-size: 13px;
        }

        .arama-satiri button {
            padding: 10px 23px;
            border: 0;
            border-radius: 9px;
            background: var(--yesil);
            color: white;
            font-size: 13px;
            cursor: pointer;
        }

        .sonuc-ust {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 18px;
        }

        .sonuc-ust h2 { margin: 0; font-size: 18px; }

        .sonuc-ust h2 span {
            display: inline-block;
            margin-left: 8px;
            color: var(--metin);
            font-size: 13px;
            font-weight: normal;
        }

        .siralama { display: flex; align-items: center; gap: 8px; }
        .siralama label { color: var(--metin); font-size: 12px; }

        .siralama select {
            max-width: 200px;
            min-height: 38px;
            padding: 8px;
            border: 1px solid var(--cizgi);
            border-radius: 8px;
            background: var(--beyaz);
            color: var(--koyu);
            font-size: 12px;
        }

        .aktif-filtreler {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
        }

        .aktif-filtreler a {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            max-width: 100%;
            padding: 8px 11px;
            border: 1px solid #d5dfca;
            border-radius: 20px;
            background: var(--acik);
            font-size: 12px;
            text-decoration: none;
            overflow-wrap: anywhere;
        }

        .filtre-hata {
            margin-bottom: 18px;
            padding: 14px;
            border: 1px solid #e5bdb5;
            border-radius: 9px;
            background: #f9e7e2;
            color: #913c30;
            font-size: 13px;
            line-height: 1.6;
        }

        .ilanlar {
            display: grid;
            grid-template-columns: repeat(4,minmax(0,1fr));
            gap: 17px;
        }

        .ilan-karti {
            display: flex;
            flex-direction: column;
            min-width: 0;
            overflow: hidden;
            border: 1px solid var(--cizgi);
            border-radius: 13px;
            background: var(--beyaz);
            transition: transform .2s, box-shadow .2s;
        }

        .ilan-karti:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(40,75,50,.1);
        }

        .kart-gorsel {
            position: relative;
            overflow: hidden;
            aspect-ratio: 1 / 1.12;
            background: #e8eddf;
        }

        .fotograf-link { display: block; width: 100%; height: 100%; }

        .ilan-fotograf {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .foto-yok {
            display: grid;
            width: 100%;
            height: 100%;
            place-items: center;
            color: var(--metin);
            font-size: 13px;
        }

        .ilan-bilgi {
            display: flex;
            flex-direction: column;
            flex: 1;
            padding: 15px;
        }

        .ilan-bilgi h3 {
            display: -webkit-box;
            min-height: 44px;
            margin: 0 0 10px;
            overflow: hidden;
            font-size: 15px;
            line-height: 1.5;
            overflow-wrap: anywhere;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .ilan-bilgi h3 a { text-decoration: none; }

        .detay-buton {
            display: block;
            margin-top: auto;
            padding: 10px 7px;
            border: 1px solid #ccdac2;
            border-radius: 7px;
            background: #edf2e7;
            color: var(--yesil);
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
        }

        .detay-buton:hover, .filtre-buton:hover,
        .arama-satiri button:hover, .ilan-ver-link:hover {
            background: var(--koyu);
            color: white;
        }

        .kart-favori-form {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
            margin: 0;
        }

        .kart-favori-kalp {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            padding: 0;
            border: 1px solid #dedccd;
            border-radius: 50%;
            background: #fffdf8;
            color: #687361;
            font: 27px/1 Arial,sans-serif;
            text-decoration: none;
            box-shadow: 0 2px 7px rgba(0,0,0,.12);
            cursor: pointer;
        }

        a.kart-favori-kalp {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
        }

        .kart-favori-kalp:hover { color: #c74658; border-color: #c74658; }
        .kart-favori-kalp.secili { color: #c74658; }
        .kart-favori-kalp:disabled { cursor: default; }

        .kart-favori-sayisi {
            display: flex;
            align-items: center;
            gap: 5px;
            margin: 0 0 14px;
            color: var(--metin);
            font-size: 12px;
            line-height: 1.5;
        }

        .kalp-isaret { color: #c74658; font-size: 17px; }
        .kart-favori-sayisi strong { color: var(--yesil); }

        .kart-islem-mesaji {
            margin: 0 0 10px;
            color: #913c30;
            font-size: 12px;
        }

        .bos-sonuc {
            grid-column: 1 / -1;
            padding: 45px 20px;
            border: 1px solid var(--cizgi);
            border-radius: 12px;
            background: var(--beyaz);
            text-align: center;
        }

        .bos-sonuc h3 { margin: 0 0 12px; }

        .bos-sonuc p {
            color: var(--metin);
            font-size: 14px;
            line-height: 1.7;
        }

        .sayfalama {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 28px;
        }

        .sayfalama a {
            padding: 10px 13px;
            border: 1px solid var(--cizgi);
            border-radius: 8px;
            background: var(--beyaz);
            font-size: 13px;
            text-decoration: none;
        }

        .sayfalama a[aria-current="page"] {
            border-color: var(--yesil);
            background: var(--yesil);
            color: white;
        }

        .sayfalama a:hover { border-color: var(--yesil); }

        .sayfa-notu {
            margin-top: 18px;
            color: var(--metin);
            font-size: 12px;
            text-align: center;
        }

        footer {
            padding: 22px;
            border-top: 1px solid var(--cizgi);
            color: var(--metin);
            font-size: 12px;
            text-align: center;
        }

        @media (min-width: 801px) {
            .filtre-panel:not([open]) > .filtre-icerik { display: block; }
        }

        @media (max-width: 1100px) {
            .ust-sag { flex-basis: 100%; }
            .ilan-menu { width: 100%; }

            .liste-duzeni {
                grid-template-columns: 220px minmax(0,1fr);
                gap: 18px;
            }

            .ilanlar { grid-template-columns: repeat(3,minmax(0,1fr)); }
        }

        @media (max-width: 800px) {
            main { width: calc(100% - 30px); }
            .liste-duzeni { grid-template-columns: 1fr; }

            .filtre-panel:not([open]) > .filtre-icerik { display: none; }

            .filtre-icerik form {
                display: grid;
                grid-template-columns: repeat(2,minmax(0,1fr));
                gap: 18px;
            }

            .filtre-grup { margin: 0; }
            .filtre-islemler { align-self: end; }
            .ilanlar { grid-template-columns: repeat(2,minmax(0,1fr)); }
            .sayfa-baslik { padding: 23px; }
        }

        @media (max-width: 600px) {
            .ilan-ust { padding: 15px 18px; gap: 15px; }
            .ilan-menu > a, .kategori-baslik { padding: 8px; font-size: 13px; }

            .sonuc-ust {
                align-items: flex-start;
                flex-direction: column;
            }

            .ilanlar { gap: 12px; }
            .ilan-bilgi { padding: 12px; }
            .arama-satiri button { padding-inline: 17px; }
        }

        @media (max-width: 380px) {
            .ilanlar, .filtre-icerik form { grid-template-columns: 1fr; }
        }

        @media (prefers-reduced-motion: reduce) {
            .ilan-karti { transition: none; }
            .ilan-karti:hover { transform: none; }
        }
    </style>
<link rel="stylesheet" href="site-ust.css?v=20261102-1">
<link rel="stylesheet" href="site-alt.css?v=20261104-1">
</head>

<body>

<?php require __DIR__ . "/site-ust.php"; ?>

<main>
    <nav
        class="yol"
        aria-label="<?= guvenli(ceviri("Sayfa yolu", "Breadcrumb")) ?>"
    >
        <a href="index.php"><?= guvenli(ceviri("Ana Sayfa", "Home")) ?></a>
        <span aria-hidden="true">›</span>
        <span><?= guvenli(ceviri("İlanlar", "Listings")) ?></span>
    </nav>

    <section class="sayfa-baslik">
        <div>
            <small>
                <?= guvenli(ceviri(
                    "BİR YUVA, YENİ BİR BAŞLANGIÇ",
                    "A HOME, A NEW BEGINNING"
                )) ?>
            </small>

            <h1>
                <?= guvenli(ceviri(
                    "Yeni dostun seni bekliyor.",
                    "Your new companion is waiting."
                )) ?>
            </h1>

            <p>
                <?= guvenli(ceviri(
                    "Sana uygun dostunu keşfet, onun hikâyesine ortak ol.",
                    "Find a companion who suits your life and become part of their story."
                )) ?>
            </p>
        </div>

        <a href="ilan-ver.php" class="ilan-ver-link">
            + <?= guvenli(ceviri(
                "Sahiplendirme İlanı Ver",
                "Post an Adoption Listing"
            )) ?>
        </a>
    </section>

    <div class="liste-duzeni">
        <aside aria-label="<?= guvenli(ceviri("İlan filtreleri", "Listing filters")) ?>">
            <details class="filtre-panel">
                <summary>
                    <?= guvenli(ceviri("Filtreler", "Filters")) ?>
                    <span><?= guvenli(ceviri("Aç / Kapat", "Show / Hide")) ?></span>
                </summary>

                <div class="filtre-icerik">
                    <form action="ilanlar.php" method="GET" id="filtre-form">
                        <div class="filtre-grup">
                            <h2><?= guvenli(ceviri("Hayvan Türü", "Animal Type")) ?></h2>

                            <?php foreach ($kategoriler as $ad => $dosya): ?>
                                <label class="secenek">
                                    <input
                                        type="checkbox"
                                        name="tur[]"
                                        value="<?= guvenli($ad) ?>"
                                        <?= in_array($ad, $turler, true) ? "checked" : "" ?>
                                    >
                                    <?= guvenli(tur_adi($ad)) ?>
                                </label>
                            <?php endforeach; ?>

                            <p class="filtre-not">
                                <?= guvenli(ceviri(
                                    "Birden fazla tür seçebilirsin.",
                                    "You can select more than one animal type."
                                )) ?>
                            </p>
                        </div>

                        <div class="filtre-grup">
                            <h2><?= guvenli(ceviri("Şehir", "City")) ?></h2>

                            <input
                                type="search"
                                id="sehir-ara"
                                placeholder="<?= guvenli(ceviri("Şehir ara...", "Search cities...")) ?>"
                                aria-label="<?= guvenli(ceviri(
                                    "Filtre seçeneklerinde şehir ara",
                                    "Search city filter options"
                                )) ?>"
                            >

                            <div class="sehir-listesi">
                                <?php foreach ($sehirler as $sehir): ?>
                                    <label
                                        class="secenek sehir-secenek"
                                        data-sehir="<?= guvenli($sehir) ?>"
                                    >
                                        <input
                                            type="checkbox"
                                            name="sehir[]"
                                            value="<?= guvenli($sehir) ?>"
                                            <?= in_array($sehir, $secili_sehirler, true) ? "checked" : "" ?>
                                        >
                                        <?= guvenli($sehir) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <p class="filtre-not">
                                <?= guvenli(ceviri(
                                    "Birden fazla şehir seçebilirsin.",
                                    "You can select more than one city."
                                )) ?>
                            </p>
                        </div>

                        <div class="filtre-grup">
                            <h2><?= guvenli(ceviri("Yaş Aralığı", "Age Range")) ?></h2>

                            <div class="yas-alanlari">
                                <div>
                                    <label for="min_yas">
                                        <?= guvenli(ceviri("En küçük", "Minimum")) ?>
                                    </label>

                                    <input
                                        type="number"
                                        id="min_yas"
                                        name="min_yas"
                                        min="0"
                                        step="1"
                                        placeholder="<?= guvenli(ceviri("Örn. 0", "e.g. 0")) ?>"
                                        value="<?= guvenli($min_yas) ?>"
                                    >
                                </div>

                                <div>
                                    <label for="max_yas">
                                        <?= guvenli(ceviri("En büyük", "Maximum")) ?>
                                    </label>

                                    <input
                                        type="number"
                                        id="max_yas"
                                        name="max_yas"
                                        min="0"
                                        step="1"
                                        placeholder="<?= guvenli(ceviri("Örn. 5", "e.g. 5")) ?>"
                                        value="<?= guvenli($max_yas) ?>"
                                    >
                                </div>
                            </div>

                            <p class="filtre-not">
                                <?= guvenli(ceviri(
                                    "Yaşlar yıl cinsindendir. 0, bir yaşından küçük dostlarımızı gösterir. Sınır koymak istemediğin alanı boş bırakabilirsin.",
                                    "Ages are in years. 0 means under one year old. Leave a field empty if you do not want to set a limit."
                                )) ?>
                            </p>
                        </div>

                        <div class="filtre-grup">
                            <h2><?= guvenli(ceviri("Cinsiyet", "Sex")) ?></h2>

                            <?php foreach (["", "Dişi", "Erkek"] as $deger): ?>
                                <label class="secenek">
                                    <input
                                        type="radio"
                                        name="cinsiyet"
                                        value="<?= guvenli($deger) ?>"
                                        <?= $cinsiyet === $deger ? "checked" : "" ?>
                                    >
                                    <?= guvenli(cinsiyet_adi($deger)) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="filtre-grup">
                            <h2><?= guvenli(ceviri("Fotoğraf", "Photo")) ?></h2>

                            <label class="secenek">
                                <input
                                    type="checkbox"
                                    name="fotografli"
                                    value="1"
                                    <?= $fotografli ? "checked" : "" ?>
                                >

                                <?= guvenli(ceviri(
                                    "Yalnızca fotoğraflı ilanlar",
                                    "Listings with photos only"
                                )) ?>
                            </label>
                        </div>

                        <div class="filtre-islemler">
                            <button type="submit" class="filtre-buton">
                                <?= guvenli(ceviri("Filtreleri Uygula", "Apply Filters")) ?>
                            </button>

                            <a href="ilanlar.php" class="temizle">
                                <?= guvenli(ceviri("Tümünü Temizle", "Clear All")) ?>
                            </a>
                        </div>
                    </form>
                </div>
            </details>
        </aside>

        <section class="sonuclar" aria-labelledby="sonuc-baslik">
            <div class="arama-satiri">
                <input
                    type="search"
                    name="arama"
                    form="filtre-form"
                    value="<?= guvenli($arama) ?>"
                    placeholder="<?= guvenli(ceviri(
                        "Hayvan adı veya ilan başlığı ara...",
                        "Search by pet name or listing title..."
                    )) ?>"
                    aria-label="<?= guvenli(ceviri(
                        "Hayvan adı veya ilan başlığı ara",
                        "Search by pet name or listing title"
                    )) ?>"
                >

                <button type="submit" form="filtre-form">
                    <?= guvenli(ceviri("Ara", "Search")) ?>
                </button>
            </div>

            <div class="sonuc-ust">
                <h2 id="sonuc-baslik">
                    <?php
                    echo guvenli(
                        count($turler) === 1
                            ? tur_adi($turler[0]) . ceviri(" İlanları", " Listings")
                            : ceviri("Sahiplendirme İlanları", "Adoption Listings")
                    );
                    ?>

                    <span>
                        <?= $toplam_ilan ?>
                        <?= guvenli(ceviri("ilan bulundu", "listings found")) ?>
                    </span>
                </h2>

                <div class="siralama">
                    <label for="siralama">
                        <?= guvenli(ceviri("Sırala:", "Sort by:")) ?>
                    </label>

                    <select
                        id="siralama"
                        name="siralama"
                        form="filtre-form"
                        onchange="this.form.requestSubmit();"
                    >
                        <?php foreach ($siralamalar as $deger => $yazi): ?>
                            <option
                                value="<?= guvenli($deger) ?>"
                                <?= $siralama === $deger ? "selected" : "" ?>
                            ><?= guvenli($yazi) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if ($etiketler): ?>
                <nav
                    class="aktif-filtreler"
                    aria-label="<?= guvenli(ceviri("Seçili filtreler", "Selected filters")) ?>"
                >
                    <?php foreach ($etiketler as [$alan, $deger, $yazi]): ?>
                        <a
                            href="<?= guvenli(
                                filtre_sil_linki($alan, $deger, $sayfa_filtreleri)
                            ) ?>"
                            aria-label="<?= guvenli(ceviri(
                                $yazi . " filtresini kaldır",
                                "Remove filter: " . $yazi
                            )) ?>"
                        >
                            <?= guvenli($yazi) ?>
                            <span aria-hidden="true">×</span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>

            <?php if ($filtre_hata !== ""): ?>
                <div class="filtre-hata" role="alert">
                    <?= guvenli($filtre_hata) ?>
                </div>
            <?php endif; ?>

            <div class="ilanlar">
                <?php if ($ilanlar): ?>
                    <?php foreach ($ilanlar as $ilan): ?>
                        <?php
                        [$foto, $temsili] = ilan_fotografi($ilan);

                        $ilan_id = (int)$ilan["id"];

                        $hayvan_adi = $ilan["hayvan_adi"]
                            ?: ceviri("Can Dostumuz", "Our Companion");

                        $kart_basligi = $ilan["ilan_basligi"] ?: $hayvan_adi;
                        $favoride = isset($benim_favorilerim[$ilan_id]);
                        $favori_sayisi = $favori_sayilari[$ilan_id] ?? 0;

                        $favori_yazisi = $favoride
                            ? ceviri("Favorilerinizde", "In your favourites")
                            : ceviri("Favorilere ekle", "Add to favourites");
                        ?>

                        <article class="ilan-karti">
                            <div class="kart-gorsel">
                                <a
                                    href="ilan-detay.php?id=<?= $ilan_id ?>"
                                    class="fotograf-link"
                                    aria-label="<?= guvenli(ceviri(
                                        $kart_basligi . " ilanını incele",
                                        "View listing: " . $kart_basligi
                                    )) ?>"
                                >
                                    <?php if ($foto !== ""): ?>
                                        <img
                                            src="<?= guvenli($foto) ?>"
                                            alt="<?= guvenli($hayvan_adi) ?>"
                                            class="ilan-fotograf"
                                            loading="lazy"
                                        >
                                    <?php else: ?>
                                        <div class="foto-yok">
                                            <?= guvenli(ceviri("Fotoğraf Yok", "No Photo")) ?>
                                        </div>
                                    <?php endif; ?>
                                </a>

                                <?php if ($uye_id > 0): ?>
                                    <form
                                        action="favori-islem.php"
                                        method="POST"
                                        class="kart-favori-form"
                                    >
                                        <input
                                            type="hidden"
                                            name="ilan_id"
                                            value="<?= $ilan_id ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="kart-favori-kalp <?= $favoride ? "secili" : "" ?>"
                                            aria-label="<?= guvenli($favori_yazisi) ?>"
                                            title="<?= guvenli($favori_yazisi) ?>"
                                            <?= $favoride ? "disabled" : "" ?>
                                        ><?= $favoride ? "♥" : "♡" ?></button>
                                    </form>
                                <?php else: ?>
                                    <a
                                        href="giris.php"
                                        class="kart-favori-kalp"
                                        aria-label="<?= guvenli(ceviri(
                                            "Favorilere eklemek için giriş yap",
                                            "Sign in to add to favourites"
                                        )) ?>"
                                        title="<?= guvenli(ceviri(
                                            "Favorilere eklemek için giriş yap",
                                            "Sign in to add to favourites"
                                        )) ?>"
                                    >♡</a>
                                <?php endif; ?>
                            </div>

                            <div class="ilan-bilgi">
                                <h3>
                                    <a
                                        href="ilan-detay.php?id=<?= $ilan_id ?>"
                                        title="<?= guvenli($kart_basligi) ?>"
                                    >
                                        <?= guvenli($kart_basligi) ?>
                                    </a>
                                </h3>

                                <p class="kart-favori-sayisi" aria-live="polite">
                                    <span class="kalp-isaret" aria-hidden="true">♥</span>

                                    <span>
                                        <strong class="favori-adet"><?= $favori_sayisi ?></strong>
                                        <?= guvenli(ceviri(
                                            "kişinin favorisinde",
                                            "people have saved this"
                                        )) ?>
                                    </span>
                                </p>

                                <p
                                    class="kart-islem-mesaji"
                                    role="status"
                                    hidden
                                ></p>

                                <a
                                    href="ilan-detay.php?id=<?= $ilan_id ?>"
                                    class="detay-buton"
                                >
                                    <?= guvenli(ceviri("İlanı İncele", "View Listing")) ?> →
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>

                <?php else: ?>
                    <div class="bos-sonuc">
                        <h3>
                            <?= guvenli(ceviri(
                                "Aradığın kriterlerde ilan bulunamadı.",
                                "No listings match your criteria."
                            )) ?>
                        </h3>

                        <p>
                            <?= guvenli(ceviri(
                                "Filtreleri değiştirerek diğer dostlarımızı keşfedebilirsin.",
                                "Try changing your filters to discover other companions."
                            )) ?>
                        </p>

                        <a href="ilanlar.php">
                            <?= guvenli(ceviri(
                                "Tüm ilanları göster",
                                "Show all listings"
                            )) ?> →
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($toplam_sayfa > 1): ?>
                <nav
                    class="sayfalama"
                    aria-label="<?= guvenli(ceviri("İlan sayfaları", "Listing pages")) ?>"
                >
                    <?php if ($sayfa > 1): ?>
                        <a href="<?= guvenli(
                            sayfa_linki($sayfa - 1, $sayfa_filtreleri)
                        ) ?>">
                            « <?= guvenli(ceviri("Önceki", "Previous")) ?>
                        </a>
                    <?php endif; ?>

                    <?php
                    $ilk = max(1, $sayfa - 2);
                    $son = min($toplam_sayfa, $ilk + 4);
                    $ilk = max(1, $son - 4);
                    ?>

                    <?php for ($i = $ilk; $i <= $son; $i++): ?>
                        <a
                            href="<?= guvenli(sayfa_linki($i, $sayfa_filtreleri)) ?>"
                            <?= $i === $sayfa ? 'aria-current="page"' : "" ?>
                        ><?= $i ?></a>
                    <?php endfor; ?>

                    <?php if ($sayfa < $toplam_sayfa): ?>
                        <a href="<?= guvenli(
                            sayfa_linki($sayfa + 1, $sayfa_filtreleri)
                        ) ?>">
                            <?= guvenli(ceviri("Sonraki", "Next")) ?> »
                        </a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>

            <?php if ($toplam_ilan > 0): ?>
                <p class="sayfa-notu">
                    <?php
                    $son_ilan = min(
                        $baslangic + $ilan_sayisi,
                        $toplam_ilan
                    );

                    echo guvenli(ceviri(
                        $toplam_ilan . " ilanın "
                            . ($baslangic + 1) . "–" . $son_ilan
                            . " arasını görüntülüyorsun.",
                        "Showing " . ($baslangic + 1) . "–" . $son_ilan
                            . " of " . $toplam_ilan . " listings."
                    ));
                    ?>
                </p>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require __DIR__ . "/site-alt.php"; ?>

<script>
const metinler = <?= json_encode(
    $js_metinleri,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?>;

/* Şehir seçenekleri içinde arama */
const sehirArama = document.getElementById("sehir-ara");

function aramaMetni(metin) {
    return metin
        .toLocaleLowerCase("tr-TR")
        .replaceAll("ı", "i")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "");
}

sehirArama.addEventListener("input", function () {
    const aranan = aramaMetni(this.value.trim());

    document.querySelectorAll(".sehir-secenek").forEach(function (secenek) {
        const sehir = aramaMetni(secenek.dataset.sehir);

        secenek.style.display = sehir.includes(aranan) ? "" : "none";
    });
});

/* Favorilere ekleme */
document.querySelectorAll(".kart-favori-form").forEach(function (form) {
    form.addEventListener("submit", async function (event) {
        event.preventDefault();

        const buton = form.querySelector("button");
        const kart = form.closest(".ilan-karti");
        const sayi = kart.querySelector(".favori-adet");
        const mesaj = kart.querySelector(".kart-islem-mesaji");

        if (buton.disabled) {
            return;
        }

        buton.disabled = true;
        mesaj.hidden = true;

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
                throw new Error(metinler.hata);
            }

            buton.textContent = "♥";
            buton.classList.add("secili");
            buton.setAttribute("aria-label", metinler.favoride);
            buton.title = metinler.favoride;

            /* Güncel favori sayısını sunucudan al. */
            try {
                const guncelCevap = await fetch(window.location.href, {
                    cache: "no-store"
                });

                if (guncelCevap.ok && !guncelCevap.redirected) {
                    const html = await guncelCevap.text();

                    const belge = new DOMParser().parseFromString(
                        html,
                        "text/html"
                    );

                    const ilanId = form.querySelector(
                        'input[name="ilan_id"]'
                    ).value;

                    const guncelForm = Array.from(
                        belge.querySelectorAll(".kart-favori-form")
                    ).find(function (oge) {
                        return oge.querySelector(
                            'input[name="ilan_id"]'
                        ).value === ilanId;
                    });

                    if (guncelForm) {
                        sayi.textContent = guncelForm
                            .closest(".ilan-karti")
                            .querySelector(".favori-adet")
                            .textContent;
                    }
                }
            } catch (hata) {
                /* Sayı, sayfa yenilendiğinde güncellenir. */
            }
        } catch (hata) {
            buton.disabled = false;
            mesaj.textContent = metinler.hata;
            mesaj.hidden = false;
        }
    });
});
</script>

</body>
</html>