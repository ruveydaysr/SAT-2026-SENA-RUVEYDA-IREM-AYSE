<?php
session_start();
require_once __DIR__ . "/baglanti.php";

function guvenli($deger)
{
    return htmlspecialchars((string) $deger, ENT_QUOTES, "UTF-8");
}

function filtre_degeri($alan)
{
    return is_string($_GET[$alan] ?? null)
        ? trim($_GET[$alan])
        : "";
}

/* Tek seçimli eski bağlantıları ve çoklu seçimleri birlikte destekler. */
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

/* Yalnızca ilanın kendi fotoğrafını döndürür. */
function asil_fotograf($fotograf)
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

/* Veritabanındaki diğer şehirleri de seçeneklere ekle. */
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
    "yeni" => "En Yeni",
    "eski" => "En Eski",
    "yas_artan" => "Yaşı Küçükten Büyüğe",
    "yas_azalan" => "Yaşı Büyükten Küçüğe"
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

/* Eski yaş filtreli bağlantılar için uyumluluk. */
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
        $filtre_hata = "Yaş alanlarına sıfır veya daha büyük tam sayı giriniz.";
    }
}

if (
    $filtre_hata === ""
    && $min_yas !== ""
    && $max_yas !== ""
    && (int) $min_yas > (int) $max_yas
) {
    $filtre_hata = "En küçük yaş, en büyük yaştan fazla olamaz.";
}

$uye_id = (int) ($_SESSION["uye_id"] ?? 0);

/* Filtreli sorgu */

$kosullar = ["durum = 'Onaylandı'"];
$parametreler = [];
$tipler = "";

if ($arama !== "") {
    $kosullar[] = "(ilan_basligi LIKE ? OR hayvan_adi LIKE ?)";
    $parametreler[] = "%" . $arama . "%";
    $parametreler[] = "%" . $arama . "%";
    $tipler .= "ss";
}

/* Aynı filtre içindeki seçimler VEYA, farklı filtreler VE ile çalışır. */
foreach (["tur" => $turler, "sehir" => $secili_sehirler] as $alan => $degerler) {
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
        $parametreler[] = (int) $min_yas;
        $tipler .= "i";
    }

    if ($max_yas !== "") {
        $kosullar[] = "yas <= ?";
        $parametreler[] = (int) $max_yas;
        $tipler .= "i";
    }
}

/* Fotoğraflı filtresinde dosyanın gerçekten mevcut olduğunu kontrol et. */
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
            $fotografli_idler[] = (int) $aday["id"];
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

$toplam_ilan = (int) $sayim[0]["toplam"];
$ilan_sayisi = 8;

$sayfa = filter_input(INPUT_GET, "sayfa", FILTER_VALIDATE_INT);
$sayfa = $sayfa && $sayfa > 0 ? $sayfa : 1;

$toplam_sayfa = max(1, (int) ceil($toplam_ilan / $ilan_sayisi));
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
    $etiketler[] = ["tur", $deger, $deger];
}

foreach ($secili_sehirler as $deger) {
    $etiketler[] = ["sehir", $deger, $deger];
}

foreach ([
    "arama" => [$arama, "Arama: " . $arama],
    "cinsiyet" => [$cinsiyet, $cinsiyet],
    "min_yas" => [$min_yas, "En az " . $min_yas . " yaş"],
    "max_yas" => [$max_yas, "En fazla " . $max_yas . " yaş"]
] as $alan => $bilgi) {
    if ($bilgi[0] !== "") {
        $etiketler[] = [$alan, $bilgi[0], $bilgi[1]];
    }
}

if ($fotografli) {
    $etiketler[] = ["fotografli", "1", "Yalnızca fotoğraflı"];
}

/* Favoriler ve mesaj bildirimi */

$favoriler = [];
$okunmamis_mesaj_var = false;

if ($uye_id > 0) {
    $kayitlar = veri_getir(
        $baglanti,
        "SELECT ilan_id FROM favoriler WHERE uye_id = ?",
        "i",
        [$uye_id]
    );

    foreach ($kayitlar as $kayit) {
        $favoriler[(int) $kayit["ilan_id"]] = true;
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
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlanlar | Aris Pati Yuvası</title>

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

        /* Üst menü */

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
            box-shadow: 0 12px 30px rgba(40, 75, 50, .16);
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

        /* Sayfa düzeni */

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
            font-size: clamp(25px, 3vw, 35px);
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
            grid-template-columns: 235px minmax(0, 1fr);
            align-items: start;
            gap: 24px;
        }

        /* Filtreler */

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

        .filtre-grup h2,
        .filtre-grup > label {
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

        .filtre-icerik input[type="search"],
        .yas-alanlari input {
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
            max-height: 205px;
            overflow-y: auto;
            overscroll-behavior: contain;
            scrollbar-width: thin;
            padding: 2px 4px 2px 0;
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

        .filtre-buton {
            background: var(--yesil);
            color: white;
        }

        .temizle {
            margin-top: 8px;
            background: #eee5d3;
        }

        /* Sonuçlar */

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

        .sonuc-ust h2 {
            margin: 0;
            font-size: 18px;
        }

        .sonuc-ust h2 span {
            margin-left: 8px;
            color: var(--metin);
            font-size: 13px;
            font-weight: normal;
        }

        .siralama {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .siralama label {
            color: var(--metin);
            font-size: 12px;
        }

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

        /* İlan kartları */

        .ilanlar {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
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
            box-shadow: 0 10px 25px rgba(40, 75, 50, .1);
        }

        .kart-gorsel {
            position: relative;
            overflow: hidden;
            aspect-ratio: 1 / 1.12;
            background: #e8eddf;
        }

        .fotograf-link {
            display: block;
            width: 100%;
            height: 100%;
        }

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

        .yuva-rozet {
            position: absolute;
            bottom: 10px;
            left: 10px;
            padding: 5px 8px;
            border-radius: 6px;
            background: var(--beyaz);
            color: var(--yesil);
            font-size: 10px;
            font-weight: bold;
            pointer-events: none;
        }

        .favori-form {
            position: absolute;
            top: 10px;
            right: 10px;
            margin: 0;
        }

        .favori-kalp {
            display: grid;
            width: 37px;
            height: 37px;
            padding: 0;
            border: 1px solid #e0e3d7;
            border-radius: 50%;
            background: var(--beyaz);
            color: var(--koyu);
            font: normal 25px/1 Arial, sans-serif;
            text-decoration: none;
            place-items: center;
            box-shadow: 0 3px 8px rgba(0, 0, 0, .08);
            cursor: pointer;
        }

        a.favori-kalp {
            position: absolute;
            top: 10px;
            right: 10px;
        }

        .favori-kalp:hover { background: #edf2e6; }
        .favori-kalp.eklendi { color: var(--yesil); border-color: #c9d8bf; }
        .favori-kalp:disabled { cursor: default; }

        .foto-not {
            margin: 0;
            padding: 5px;
            background: #eee5d3;
            color: var(--metin);
            font-size: 10px;
            text-align: center;
        }

        .ilan-bilgi {
            display: flex;
            flex-direction: column;
            flex: 1;
            padding: 15px;
        }

        .ilan-tur {
            margin-bottom: 7px;
            color: var(--metin);
            font-size: 11px;
        }

        .ilan-bilgi h3 {
            margin: 0 0 8px;
            font-size: 19px;
            overflow-wrap: anywhere;
        }

        .ilan-bilgi h3 a { text-decoration: none; }

        .ilan-basligi {
            display: -webkit-box;
            min-height: 38px;
            margin: 0 0 12px;
            overflow: hidden;
            color: var(--metin);
            font-size: 12px;
            line-height: 1.6;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow-wrap: anywhere;
        }

        .kart-ozellikler {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 12px;
        }

        .kart-ozellikler span {
            padding: 5px 7px;
            border-radius: 5px;
            background: #edf1e7;
            color: #52664d;
            font-size: 10px;
        }

        .kart-sehir {
            margin: 0 0 14px;
            color: var(--metin);
            font-size: 12px;
            overflow-wrap: anywhere;
        }

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

        .detay-buton:hover,
        .filtre-buton:hover,
        .arama-satiri button:hover,
        .ilan-ver-link:hover {
            background: var(--koyu);
            color: white;
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

        .bildirim {
            position: fixed;
            bottom: 25px;
            left: 50%;
            z-index: 999;
            transform: translateX(-50%);
            width: max-content;
            max-width: calc(100% - 30px);
            padding: 14px 22px;
            border: 1px solid #b9cfae;
            border-radius: 10px;
            background: var(--beyaz);
            box-shadow: 0 8px 30px rgba(40, 75, 50, .2);
            font-size: 14px;
            text-align: center;
        }

        .bildirim[hidden] { display: none; }

        @media (min-width: 801px) {
            .filtre-panel:not([open]) > .filtre-icerik {
                display: block;
            }
        }

        @media (max-width: 1100px) {
            .ilan-menu { width: 100%; }

            .liste-duzeni {
                grid-template-columns: 220px minmax(0, 1fr);
                gap: 18px;
            }

            .ilanlar {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 800px) {
            main { width: calc(100% - 30px); }
            .liste-duzeni { grid-template-columns: 1fr; }

            .filtre-panel:not([open]) > .filtre-icerik {
                display: none;
            }

            .filtre-icerik form {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 18px;
            }

            .filtre-grup { margin: 0; }
            .filtre-islemler { align-self: end; }

            .ilanlar {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .sayfa-baslik { padding: 23px; }
        }

        @media (max-width: 600px) {
            .ilan-ust { padding: 15px 18px; gap: 15px; }

            .ilan-menu > a, .kategori-baslik {
                padding: 8px;
                font-size: 13px;
            }

            .sonuc-ust {
                align-items: flex-start;
                flex-direction: column;
            }

            .ilanlar { gap: 12px; }
            .ilan-bilgi { padding: 12px; }
            .ilan-bilgi h3 { font-size: 17px; }
            .favori-kalp { width: 33px; height: 33px; }
            .arama-satiri button { padding-inline: 17px; }
        }

        @media (max-width: 380px) {
            .ilanlar, .filtre-icerik form {
                grid-template-columns: 1fr;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .ilan-karti { transition: none; }
            .ilan-karti:hover { transform: none; }
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
        <a href="index.php">Ana Sayfa</a>
        <a href="ilanlar.php" aria-current="page">İlanlar</a>

        <div class="kategori-menu">
            <button
                type="button"
                class="kategori-baslik"
                aria-controls="kategori-listesi"
            >
                Hayvan Kategorileri
                <span aria-hidden="true">▾</span>
            </button>

            <div class="kategori-acilir" id="kategori-listesi">
                <?php foreach ($kategoriler as $ad => $dosya): ?>
                    <a href="ilanlar.php?tur=<?= rawurlencode($ad) ?>">
                        <img
                            src="images/<?= guvenli($dosya) ?>"
                            alt=""
                            width="32"
                            height="32"
                            loading="lazy"
                        >
                        <span><?= guvenli($ad) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <a href="ilan-ver.php">İlan Ver</a>
        <a href="hakkimizda.php">Hakkımızda</a>
        <a href="iletisim.php">İletişim</a>

        <?php if ($uye_id > 0): ?>
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
    <nav class="yol" aria-label="Sayfa yolu">
        <a href="index.php">Ana Sayfa</a>
        <span aria-hidden="true">›</span>
        <span>İlanlar</span>
    </nav>

    <section class="sayfa-baslik">
        <div>
            <small>BİR YUVA, YENİ BİR BAŞLANGIÇ</small>
            <h1>Yeni dostun seni bekliyor.</h1>
            <p>Sana uygun dostunu keşfet, onun hikâyesine ortak ol.</p>
        </div>
        <a href="ilan-ver.php" class="ilan-ver-link">
            + Sahiplendirme İlanı Ver
        </a>
    </section>

    <div class="liste-duzeni">
        <aside aria-label="İlan filtreleri">
            <details class="filtre-panel">
                <summary>
                    Filtreler
                    <span>Aç / Kapat</span>
                </summary>

                <div class="filtre-icerik">
                    <form action="ilanlar.php" method="GET" id="filtre-form">
                        <div class="filtre-grup">
                            <h2>Hayvan Türü</h2>

                            <?php foreach ($kategoriler as $ad => $dosya): ?>
                                <label class="secenek">
                                    <input
                                        type="checkbox"
                                        name="tur[]"
                                        value="<?= guvenli($ad) ?>"
                                        <?= in_array($ad, $turler, true) ? "checked" : "" ?>
                                    >
                                    <?= guvenli($ad) ?>
                                </label>
                            <?php endforeach; ?>

                            <p class="filtre-not">
                                Birden fazla tür seçebilirsin.
                            </p>
                        </div>

                        <div class="filtre-grup">
                            <h2>Şehir</h2>

                            <input
                                type="search"
                                id="sehir-ara"
                                placeholder="Şehir ara..."
                                aria-label="Filtre seçeneklerinde şehir ara"
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
                                Birden fazla şehir seçebilirsin.
                            </p>
                        </div>

                        <div class="filtre-grup">
                            <h2>Yaş Aralığı</h2>

                            <div class="yas-alanlari">
                                <div>
                                    <label for="min_yas">En küçük</label>
                                    <input
                                        type="number"
                                        id="min_yas"
                                        name="min_yas"
                                        min="0"
                                        step="1"
                                        placeholder="Örn. 0"
                                        value="<?= guvenli($min_yas) ?>"
                                    >
                                </div>
                                <div>
                                    <label for="max_yas">En büyük</label>
                                    <input
                                        type="number"
                                        id="max_yas"
                                        name="max_yas"
                                        min="0"
                                        step="1"
                                        placeholder="Örn. 5"
                                        value="<?= guvenli($max_yas) ?>"
                                    >
                                </div>
                            </div>

                            <p class="filtre-not">
                                Yaşlar yıl cinsindendir. 0, bir yaşından
                                küçük dostlarımızı gösterir. Sınır koymak
                                istemediğin alanı boş bırakabilirsin.
                            </p>
                        </div>

                        <div class="filtre-grup">
                            <h2>Cinsiyet</h2>

                            <?php foreach ([
                                "" => "Tümü",
                                "Dişi" => "Dişi",
                                "Erkek" => "Erkek"
                            ] as $deger => $yazi): ?>
                                <label class="secenek">
                                    <input
                                        type="radio"
                                        name="cinsiyet"
                                        value="<?= guvenli($deger) ?>"
                                        <?= $cinsiyet === $deger ? "checked" : "" ?>
                                    >
                                    <?= guvenli($yazi) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="filtre-grup">
                            <h2>Fotoğraf</h2>

                            <label class="secenek">
                                <input
                                    type="checkbox"
                                    name="fotografli"
                                    value="1"
                                    <?= $fotografli ? "checked" : "" ?>
                                >
                                Yalnızca fotoğraflı ilanlar
                            </label>
                        </div>

                        <div class="filtre-islemler">
                            <button type="submit" class="filtre-buton">
                                Filtreleri Uygula
                            </button>
                            <a href="ilanlar.php" class="temizle">
                                Tümünü Temizle
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
                    placeholder="Hayvan adı veya ilan başlığı ara..."
                    aria-label="Hayvan adı veya ilan başlığı ara"
                >
                <button type="submit" form="filtre-form">Ara</button>
            </div>

            <div class="sonuc-ust">
                <h2 id="sonuc-baslik">
                    <?= count($turler) === 1
                        ? guvenli($turler[0]) . " İlanları"
                        : "Sahiplendirme İlanları" ?>
                    <span><?= $toplam_ilan ?> ilan bulundu</span>
                </h2>

                <div class="siralama">
                    <label for="siralama">Sırala:</label>
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
                <nav class="aktif-filtreler" aria-label="Seçili filtreler">
                    <?php foreach ($etiketler as [$alan, $deger, $yazi]): ?>
                        <a
                            href="<?= guvenli(
                                filtre_sil_linki($alan, $deger, $sayfa_filtreleri)
                            ) ?>"
                            aria-label="<?= guvenli($yazi . ' filtresini kaldır') ?>"
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
                        $ilan_id = (int) $ilan["id"];
                        $favoride = isset($favoriler[$ilan_id]);
                        $hayvan_adi = $ilan["hayvan_adi"] ?: "Can Dostumuz";
                        ?>

                        <article class="ilan-karti">
                            <div class="kart-gorsel">
                                <a
                                    href="ilan-detay.php?id=<?= $ilan_id ?>"
                                    class="fotograf-link"
                                    aria-label="<?= guvenli($hayvan_adi . ' ilanını incele') ?>"
                                >
                                    <?php if ($foto !== ""): ?>
                                        <img
                                            src="<?= guvenli($foto) ?>"
                                            alt="<?= guvenli($hayvan_adi) ?>"
                                            class="ilan-fotograf"
                                            loading="lazy"
                                        >
                                    <?php else: ?>
                                        <div class="foto-yok">Fotoğraf Yok</div>
                                    <?php endif; ?>
                                </a>

                                <span class="yuva-rozet">Yuva arıyor</span>

                                <?php if ($uye_id > 0): ?>
                                    <form
                                        action="favori-islem.php"
                                        method="POST"
                                        class="favori-form"
                                    >
                                        <input
                                            type="hidden"
                                            name="ilan_id"
                                            value="<?= $ilan_id ?>"
                                        >
                                        <button
                                            type="submit"
                                            class="favori-kalp <?= $favoride ? 'eklendi' : '' ?>"
                                            aria-label="<?= $favoride ? 'Favorilerinizde' : 'Favorilere ekle' ?>"
                                            title="<?= $favoride ? 'Favorilerinizde' : 'Favorilere ekle' ?>"
                                            <?= $favoride ? "disabled" : "" ?>
                                        ><?= $favoride ? "♥" : "♡" ?></button>
                                    </form>
                                <?php else: ?>
                                    <a
                                        href="giris.php"
                                        class="favori-kalp"
                                        aria-label="Favorilere eklemek için giriş yap"
                                        title="Favorilere eklemek için giriş yap"
                                    >♡</a>
                                <?php endif; ?>
                            </div>

                            <?php if ($temsili): ?>
                                <p class="foto-not">Temsili fotoğraf</p>
                            <?php endif; ?>

                            <div class="ilan-bilgi">
                                <span class="ilan-tur">
                                    <?= guvenli($ilan["tur"]) ?>
                                </span>

                                <h3>
                                    <a href="ilan-detay.php?id=<?= $ilan_id ?>">
                                        <?= guvenli($hayvan_adi) ?>
                                    </a>
                                </h3>

                                <p class="ilan-basligi">
                                    <?= guvenli(
                                        $ilan["ilan_basligi"] ?: "Sevgi dolu bir yuva arıyor."
                                    ) ?>
                                </p>

                                <div class="kart-ozellikler">
                                    <span>
                                        <?= (int) $ilan["yas"] < 1
                                            ? "1 yaşından küçük"
                                            : (int) $ilan["yas"] . " yaşında" ?>
                                    </span>

                                    <?php if (!empty($ilan["cinsiyet"])): ?>
                                        <span><?= guvenli($ilan["cinsiyet"]) ?></span>
                                    <?php endif; ?>
                                </div>

                                <p class="kart-sehir">
                                    <?= guvenli($ilan["sehir"]) ?>
                                </p>

                                <a
                                    href="ilan-detay.php?id=<?= $ilan_id ?>"
                                    class="detay-buton"
                                >İlanı İncele →</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="bos-sonuc">
                        <h3>Aradığın kriterlerde ilan bulunamadı.</h3>
                        <p>Filtreleri değiştirerek diğer dostlarımızı keşfedebilirsin.</p>
                        <a href="ilanlar.php">Tüm ilanları göster →</a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($toplam_sayfa > 1): ?>
                <nav class="sayfalama" aria-label="İlan sayfaları">
                    <?php if ($sayfa > 1): ?>
                        <a href="<?= guvenli(
                            sayfa_linki($sayfa - 1, $sayfa_filtreleri)
                        ) ?>">« Önceki</a>
                    <?php endif; ?>

                    <?php
                    $ilk = max(1, $sayfa - 2);
                    $son = min($toplam_sayfa, $ilk + 4);
                    $ilk = max(1, $son - 4);
                    ?>

                    <?php for ($i = $ilk; $i <= $son; $i++): ?>
                        <a
                            href="<?= guvenli(
                                sayfa_linki($i, $sayfa_filtreleri)
                            ) ?>"
                            <?= $i === $sayfa ? 'aria-current="page"' : "" ?>
                        ><?= $i ?></a>
                    <?php endfor; ?>

                    <?php if ($sayfa < $toplam_sayfa): ?>
                        <a href="<?= guvenli(
                            sayfa_linki($sayfa + 1, $sayfa_filtreleri)
                        ) ?>">Sonraki »</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>

            <?php if ($toplam_ilan > 0): ?>
                <p class="sayfa-notu">
                    <?= $toplam_ilan ?> ilanın
                    <?= $baslangic + 1 ?>–<?= min($baslangic + $ilan_sayisi, $toplam_ilan) ?>
                    arasını görüntülüyorsun.
                </p>
            <?php endif; ?>
        </section>
    </div>
</main>

<footer>
    © <?= date("Y") ?> Aris Pati Yuvası · Her can dostuna bir yuva.
</footer>

<div class="bildirim" id="favori-bildirim" role="status" hidden></div>

<script>
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

    const bildirim = document.getElementById("favori-bildirim");
    let bildirimZamani;

    function mesajGoster(mesaj) {
        clearTimeout(bildirimZamani);
        bildirim.textContent = mesaj;
        bildirim.hidden = false;

        bildirimZamani = setTimeout(function () {
            bildirim.hidden = true;
        }, 2000);
    }

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

                buton.textContent = "♥";
                buton.classList.add("eklendi");
                buton.setAttribute("aria-label", "Favorilerinizde");
                buton.title = "Favorilerinizde";

                mesajGoster("İlan favorilerinize eklendi.");
            } catch (hata) {
                buton.disabled = false;
                mesajGoster("İlan eklenemedi. Lütfen tekrar deneyiniz.");
            }
        });
    });
</script>

</body>
</html>