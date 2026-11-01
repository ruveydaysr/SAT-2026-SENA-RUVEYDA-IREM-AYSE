<?php
session_start();

if (!isset($_SESSION["uye_id"])) {
    header("Location: giris.php");
    exit;
}

require_once __DIR__ . "/baglanti.php";

$uye_id = (int) $_SESSION["uye_id"];

function guvenli($deger)
{
    return htmlspecialchars((string) $deger, ENT_QUOTES, "UTF-8");
}

function post_degeri($alan)
{
    return is_string($_POST[$alan] ?? null) ? $_POST[$alan] : "";
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

function sayfa_linki($sayfa, $arama, $durum, $siralama)
{
    return "profil.php?" . http_build_query([
        "arama" => $arama,
        "durum" => $durum,
        "siralama" => $siralama,
        "sayfa" => $sayfa
    ]);
}

/* Kullanıcı bilgileri */

$uyeler = veri_getir(
    $baglanti,
    "SELECT id, ad_soyad, email, telefon, sifre
     FROM uyeler WHERE id = ? LIMIT 1",
    "i",
    $uye_id
);

if (!$uyeler) {
    unset(
        $_SESSION["uye_id"],
        $_SESSION["uye_adsoyad"],
        $_SESSION["uye_email"]
    );

    header("Location: giris.php");
    exit;
}

$uye = $uyeler[0];

if (
    !isset($_SESSION["profil_csrf"])
    || !is_string($_SESSION["profil_csrf"])
) {
    $_SESSION["profil_csrf"] = bin2hex(random_bytes(32));
}

$bilgi_hata = "";
$sifre_hata = "";

$basari = is_string($_SESSION["profil_basari"] ?? null)
    ? $_SESSION["profil_basari"]
    : "";

unset($_SESSION["profil_basari"]);

$form_ad = $uye["ad_soyad"];
$form_email = $uye["email"];
$form_telefon = $uye["telefon"];

/* Bilgi ve şifre güncelleme */

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $islem = post_degeri("islem");
    $token = post_degeri("csrf_token");

    if ($islem === "bilgi_guncelle") {
        $form_ad = trim(post_degeri("ad_soyad"));
        $form_email = trim(post_degeri("email"));
        $form_telefon = trim(post_degeri("telefon"));
    }

    if (!hash_equals($_SESSION["profil_csrf"], $token)) {
        $mesaj = "İşlem doğrulanamadı. Sayfayı yenileyip tekrar deneyiniz.";

        if ($islem === "sifre_degistir") {
            $sifre_hata = $mesaj;
        } else {
            $bilgi_hata = $mesaj;
        }
    } elseif ($islem === "bilgi_guncelle") {
        $mevcut_sifre = post_degeri("bilgi_sifre");

        if (
            $form_ad === ""
            || $form_email === ""
            || $form_telefon === ""
            || $mevcut_sifre === ""
        ) {
            $bilgi_hata = "Lütfen tüm alanları doldurunuz.";
        } elseif (!filter_var($form_email, FILTER_VALIDATE_EMAIL)) {
            $bilgi_hata = "Geçerli bir e-posta adresi giriniz.";
        } elseif (!preg_match('/^05[0-9]{9}$/', $form_telefon)) {
            $bilgi_hata = "Telefon numarası 05 ile başlamalı ve 11 haneli olmalıdır.";
        } elseif (!password_verify($mevcut_sifre, $uye["sifre"])) {
            $bilgi_hata = "Mevcut şifreniz yanlış.";
        } else {
            $ayni_email = veri_getir(
                $baglanti,
                "SELECT id FROM uyeler
                 WHERE email = ? AND id <> ? LIMIT 1",
                "si",
                $form_email,
                $uye_id
            );

            if ($ayni_email) {
                $bilgi_hata = "Bu e-posta adresi başka bir hesap tarafından kullanılıyor.";
            } else {
                try {
                    $sorgu = mysqli_prepare(
                        $baglanti,
                        "UPDATE uyeler
                         SET ad_soyad = ?, email = ?, telefon = ?
                         WHERE id = ?"
                    );

                    mysqli_stmt_bind_param(
                        $sorgu,
                        "sssi",
                        $form_ad,
                        $form_email,
                        $form_telefon,
                        $uye_id
                    );

                    $kaydedildi = mysqli_stmt_execute($sorgu);
                    mysqli_stmt_close($sorgu);

                    if ($kaydedildi) {
                        $_SESSION["uye_adsoyad"] = $form_ad;
                        $_SESSION["uye_email"] = $form_email;

                        $_SESSION["profil_basari"] =
                            "Bilgileriniz başarıyla güncellendi.";

                        header("Location: profil.php#bilgilerim");
                        exit;
                    }

                    $bilgi_hata = "Bilgiler kaydedilemedi. Tekrar deneyiniz.";
                } catch (mysqli_sql_exception $e) {
                    $bilgi_hata = "Bilgiler kaydedilemedi. Girdiğiniz bilgileri kontrol ediniz.";
                }
            }
        }
    } elseif ($islem === "sifre_degistir") {
        $mevcut_sifre = post_degeri("mevcut_sifre");
        $yeni_sifre = post_degeri("yeni_sifre");
        $sifre_tekrar = post_degeri("sifre_tekrar");

        if (
            $mevcut_sifre === ""
            || $yeni_sifre === ""
            || $sifre_tekrar === ""
        ) {
            $sifre_hata = "Lütfen tüm şifre alanlarını doldurunuz.";
        } elseif (!password_verify($mevcut_sifre, $uye["sifre"])) {
            $sifre_hata = "Mevcut şifreniz yanlış.";
        } elseif (strlen($yeni_sifre) < 8) {
            $sifre_hata = "Yeni şifreniz en az 8 karakter olmalıdır.";
        } elseif (strlen($yeni_sifre) > 72) {
            $sifre_hata = "Yeni şifreniz çok uzun. Daha kısa bir şifre giriniz.";
        } elseif ($yeni_sifre !== $sifre_tekrar) {
            $sifre_hata = "Yeni şifreler birbiriyle eşleşmiyor.";
        } elseif (password_verify($yeni_sifre, $uye["sifre"])) {
            $sifre_hata = "Yeni şifreniz mevcut şifrenizden farklı olmalıdır.";
        } else {
            $yeni_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);

            try {
                $sorgu = mysqli_prepare(
                    $baglanti,
                    "UPDATE uyeler
                     SET sifre = ?
                     WHERE id = ? AND sifre = ?"
                );

                mysqli_stmt_bind_param(
                    $sorgu,
                    "sis",
                    $yeni_hash,
                    $uye_id,
                    $uye["sifre"]
                );

                $kaydedildi = mysqli_stmt_execute($sorgu);
                $degisen = mysqli_stmt_affected_rows($sorgu);

                mysqli_stmt_close($sorgu);

                if ($kaydedildi && $degisen === 1) {
                    session_regenerate_id(true);

                    $_SESSION["profil_csrf"] = bin2hex(random_bytes(32));
                    $_SESSION["profil_basari"] =
                        "Şifreniz başarıyla değiştirildi.";

                    header("Location: profil.php#sifre");
                    exit;
                }

                $sifre_hata = "Şifre değiştirilemedi. Sayfayı yenileyip tekrar deneyiniz.";
            } catch (mysqli_sql_exception $e) {
                $sifre_hata = "Şifre değiştirilemedi. Tekrar deneyiniz.";
            }
        }
    } else {
        $bilgi_hata = "Geçersiz işlem.";
    }
}

$_SESSION["uye_adsoyad"] = $uye["ad_soyad"];
$_SESSION["uye_email"] = $uye["email"];

/* İlanlar ve sayfalama */

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

$toplam_ilan = (int) $sayim[0]["toplam"];
$toplam_sayfa = max(1, (int) ceil($toplam_ilan / $limit));
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

        #aris-ortak-ust {
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
            flex-shrink: 0;
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
            gap: 6px;
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
            box-shadow: 0 12px 28px rgba(40, 75, 50, .16);
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
            text-decoration: none;
        }

        .kategori-acilir a:hover,
        .kategori-acilir a:focus-visible {
            background: #426b46;
            color: white;
        }

        /* Sayfa düzeni */

        main {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: start;
            gap: 18px;
            width: min(1100px, 94%);
            margin: 24px auto;
        }

        main > .profil-bolum {
            grid-column: 1 / -1;
            min-width: 0;
        }

        main > #bilgilerim {
            grid-column: 1;
        }

        main > #sifre {
            grid-column: 2;
        }

        .profil-bolum {
            padding: 20px;
            border: 1px solid #ded6c6;
            border-radius: 12px;
            background: #fffdf8;
            scroll-margin-top: 20px;
        }

        .profil-bolum h1,
        .profil-bolum h2 {
            margin: 0 0 14px;
            font-size: 20px;
        }

        .profil-bolum > p {
            margin: 0 0 16px;
            color: #687361;
            font-size: 13px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .profil-bilgileri {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .profil-bilgileri p {
            margin: 0;
            font-size: 14px;
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
            margin-bottom: 18px;
        }

        .bolum-baslik h2 {
            margin: 0;
        }

        .sayac {
            color: #687361;
            font-size: 13px;
        }

        /* Formlar */

        .hesap-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .form-grup label,
        .filtre-grup label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: bold;
        }

        .form-grup input,
        .filtre-grup input,
        .filtre-grup select {
            width: 100%;
            min-height: 39px;
            padding: 9px 11px;
            border: 1px solid #ded6c6;
            border-radius: 8px;
            background: white;
            color: #284b32;
            font: inherit;
            font-size: 13px;
        }

        .alan-notu {
            display: block;
            margin-top: 5px;
            color: #687361;
            font-size: 12px;
            line-height: 1.4;
        }

        .form-islemler {
            margin-top: 2px;
        }

        .bildirim.hata {
            margin-bottom: 16px;
            padding: 12px 15px;
            border: 1px solid #e1b4ad;
            border-radius: 9px;
            background: #f8e1dd;
            color: #9b3b33;
            font-size: 14px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        /* Ortada görünen başarı kutusu */

        .basari-kutusu {
            position: fixed;
            top: 50%;
            left: 50%;
            z-index: 9999;
            transform: translate(-50%, -50%);
            width: min(380px, calc(100% - 40px));
            padding: 25px;
            border: 1px solid #b7d2ad;
            border-radius: 14px;
            background: #fffdf8;
            color: #284b32;
            box-shadow: 0 12px 45px rgba(0, 0, 0, .22);
            text-align: center;
            pointer-events: none;
            animation: basari-kaybol 2s forwards;
        }

        .basari-isaret {
            display: block;
            width: 44px;
            height: 44px;
            margin: 0 auto 12px;
            border-radius: 50%;
            background: #e0edda;
            color: #426b46;
            font-size: 28px;
            font-weight: bold;
            line-height: 44px;
        }

        .basari-kutusu p {
            margin: 0;
            font-size: 15px;
            line-height: 1.6;
        }

        @keyframes basari-kaybol {
            0%, 85% {
                opacity: 1;
                visibility: visible;
            }

            100% {
                opacity: 0;
                visibility: hidden;
            }
        }

        /* İlan filtreleri */

        .profil-filtre {
            display: grid;
            grid-template-columns: minmax(0, 2fr) repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 22px;
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
            padding: 16px;
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
            gap: 8px;
            margin-top: 14px;
        }

        .kart-islemler form {
            margin: 0;
        }

        .buton {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            padding: 9px 13px;
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
            border-color: #426b46;
            background: #426b46;
            color: white;
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

        .ilan-menu > a:focus-visible,
        .kategori-baslik:focus-visible {
            outline-color: #eee5d3;
        }

        @media (max-width: 1100px) {
            .ilan-menu {
                width: 100%;
            }
        }

        @media (max-width: 760px) {
            main {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            main > #bilgilerim,
            main > #sifre {
                grid-column: 1 / -1;
            }

            .profil-bilgileri,
            .profil-filtre {
                grid-template-columns: 1fr;
            }

            .profil-bolum {
                padding: 17px;
            }
        }

        @media (max-width: 700px) {
            #aris-ortak-ust {
                gap: 16px;
                padding: 15px 18px;
            }

            .ilan-menu > a,
            .kategori-baslik {
                padding: 8px;
                font-size: 13px;
            }
        }

        @media (max-width: 600px) {
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
</head>

<body>

<?php if ($basari !== ""): ?>
    <div class="basari-kutusu" id="basari-kutusu" role="status">
        <span class="basari-isaret" aria-hidden="true">✓</span>
        <p><?= guvenli($basari) ?></p>
    </div>
<?php endif; ?>

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
                <?php foreach ($menu_kategorileri as $ad => $simge): ?>
                    <a href="ilanlar.php?tur=<?= rawurlencode($ad) ?>">
                        <span aria-hidden="true"><?= $simge ?></span>
                        <span><?= guvenli($ad) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <a href="ilan-ver.php">İlan Ver</a>
        <a href="hakkimizda.php">Hakkımızda</a>
        <a href="iletisim.php">İletişim</a>
        <a href="mesajlar.php">Mesajlarım</a>
        <a href="profil.php" aria-current="page">Profilim</a>

        <a
            href="cikis.php"
            onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');"
        >Çıkış Yap</a>
    </nav>
</header>

<main>
    <section class="profil-bolum">
        <h1>Profil Bilgilerim</h1>

        <div class="profil-bilgileri">
            <p>
                <strong>Ad Soyad</strong>
                <?= guvenli($uye["ad_soyad"]) ?>
            </p>
            <p>
                <strong>E-posta</strong>
                <?= guvenli($uye["email"]) ?>
            </p>
            <p>
                <strong>Telefon</strong>
                <?= guvenli($uye["telefon"]) ?>
            </p>
        </div>

        <div class="kart-islemler">
            <a href="#bilgilerim" class="buton">Bilgilerimi Düzenle</a>
            <a href="#sifre" class="buton acik">Şifremi Değiştir</a>
            <a href="mesajlar.php" class="buton acik">Mesajlarımı Gör</a>
            <a href="ilan-ver.php" class="buton acik">Yeni İlan Ver</a>
        </div>
    </section>

    <section class="profil-bolum" id="bilgilerim">
        <h2>Bilgilerimi Düzenle</h2>
        <p>Bilgilerinizi güncellemek için mevcut şifrenizi giriniz.</p>

        <?php if ($bilgi_hata !== ""): ?>
            <div class="bildirim hata" role="alert">
                <?= guvenli($bilgi_hata) ?>
            </div>
        <?php endif; ?>

        <form action="profil.php#bilgilerim" method="POST" class="hesap-form">
            <input type="hidden" name="islem" value="bilgi_guncelle">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= guvenli($_SESSION["profil_csrf"]) ?>"
            >

            <div class="form-grup">
                <label for="ad_soyad">Ad Soyad</label>
                <input
                    type="text"
                    id="ad_soyad"
                    name="ad_soyad"
                    value="<?= guvenli($form_ad) ?>"
                    autocomplete="name"
                    required
                >
            </div>

            <div class="form-grup">
                <label for="email">E-posta Adresi</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= guvenli($form_email) ?>"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="form-grup">
                <label for="telefon">Telefon Numarası</label>
                <input
                    type="tel"
                    id="telefon"
                    name="telefon"
                    value="<?= guvenli($form_telefon) ?>"
                    placeholder="05XXXXXXXXX"
                    pattern="05[0-9]{9}"
                    maxlength="11"
                    inputmode="numeric"
                    autocomplete="tel"
                    aria-describedby="telefon-notu"
                    required
                >
                <small class="alan-notu" id="telefon-notu">
                    05 ile başlayan 11 haneli numaranızı boşluksuz yazınız.
                </small>
            </div>

            <div class="form-grup">
                <label for="bilgi_sifre">Mevcut Şifreniz</label>
                <input
                    type="password"
                    id="bilgi_sifre"
                    name="bilgi_sifre"
                    autocomplete="current-password"
                    required
                >
            </div>

            <div class="form-islemler">
                <button type="submit" class="buton">Bilgilerimi Kaydet</button>
            </div>
        </form>
    </section>

    <section class="profil-bolum" id="sifre">
        <h2>Şifremi Değiştir</h2>
        <p>Yeni şifreniz en az 8 karakter olmalıdır.</p>

        <?php if ($sifre_hata !== ""): ?>
            <div class="bildirim hata" role="alert">
                <?= guvenli($sifre_hata) ?>
            </div>
        <?php endif; ?>

        <form action="profil.php#sifre" method="POST" class="hesap-form">
            <input type="hidden" name="islem" value="sifre_degistir">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= guvenli($_SESSION["profil_csrf"]) ?>"
            >

            <div class="form-grup">
                <label for="mevcut_sifre">Mevcut Şifre</label>
                <input
                    type="password"
                    id="mevcut_sifre"
                    name="mevcut_sifre"
                    autocomplete="current-password"
                    required
                >
            </div>

            <div class="form-grup">
                <label for="yeni_sifre">Yeni Şifre</label>
                <input
                    type="password"
                    id="yeni_sifre"
                    name="yeni_sifre"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <div class="form-grup">
                <label for="sifre_tekrar">Yeni Şifre Tekrarı</label>
                <input
                    type="password"
                    id="sifre_tekrar"
                    name="sifre_tekrar"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
            </div>

            <div class="form-islemler">
                <button type="submit" class="buton">Şifremi Değiştir</button>
            </div>
        </form>
    </section>

    <section class="profil-bolum">
        <div class="bolum-baslik">
            <h2>İlanlarım</h2>
            <span class="sayac"><?= $toplam_ilan ?> ilan bulundu</span>
        </div>

        <form method="GET" action="profil.php" class="profil-filtre">
            <div class="filtre-grup">
                <label for="arama">İlan Ara</label>
                <input
                    type="text"
                    id="arama"
                    name="arama"
                    placeholder="Hayvan adı veya ilan başlığı..."
                    value="<?= guvenli($arama) ?>"
                >
            </div>

            <div class="filtre-grup">
                <label for="durum">İlan Durumu</label>
                <select id="durum" name="durum">
                    <option value="">Tüm Durumlar</option>
                    <?php foreach ($gecerli_durumlar as $secenek): ?>
                        <option
                            value="<?= guvenli($secenek) ?>"
                            <?= $durum === $secenek ? "selected" : "" ?>
                        ><?= guvenli($secenek) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filtre-grup">
                <label for="siralama">Sıralama</label>
                <select id="siralama" name="siralama">
                    <option
                        value="yeni"
                        <?= $siralama === "yeni" ? "selected" : "" ?>
                    >En Yeni</option>
                    <option
                        value="eski"
                        <?= $siralama === "eski" ? "selected" : "" ?>
                    >En Eski</option>
                </select>
            </div>

            <div class="filtre-islemler">
                <button type="submit" class="buton">Ara</button>
                <a href="profil.php" class="buton acik">Temizle</a>
            </div>
        </form>

        <?php if ($ilanlar): ?>
            <?php foreach ($ilanlar as $ilan): ?>
                <?php $foto = profil_fotografi($ilan["fotograf"] ?? ""); ?>

                <article class="profil-kart">
                    <div class="kart-icerik">
                        <?php if ($foto !== ""): ?>
                            <img
                                src="<?= guvenli($foto) ?>"
                                alt="<?= guvenli($ilan["hayvan_adi"]) ?>"
                                class="profil-fotograf"
                                loading="lazy"
                            >
                        <?php endif; ?>

                        <div class="kart-bilgi">
                            <h3><?= guvenli(
                                $ilan["ilan_basligi"] ?: $ilan["hayvan_adi"]
                            ) ?></h3>

                            <p>
                                <strong>Hayvan Adı:</strong>
                                <?= guvenli($ilan["hayvan_adi"]) ?>
                            </p>
                            <p>
                                <strong>Tür:</strong>
                                <?= guvenli($ilan["tur"]) ?>
                                · <strong>Yaş:</strong>
                                <?= (int) $ilan["yas"] ?>
                            </p>
                            <p>
                                <strong>Şehir:</strong>
                                <?= guvenli($ilan["sehir"]) ?>
                            </p>
                            <p>
                                <strong>Durum:</strong>
                                <span class="profil-durum <?= durum_sinifi($ilan["durum"]) ?>">
                                    <?= guvenli($ilan["durum"]) ?>
                                </span>
                            </p>

                            <?php if ($ilan["durum"] === "Reddedildi"): ?>
                                <p class="red-nedeni">
                                    <strong>Reddedilme Nedeni:</strong>
                                    <?= guvenli($ilan["red_nedeni"] ?? "") ?>
                                </p>
                            <?php endif; ?>

                            <div class="kart-islemler">
                                <a
                                    href="ilan-duzenle.php?id=<?= (int) $ilan["id"] ?>"
                                    class="buton"
                                >Düzenle</a>

                                <a
                                    href="ilan-sil.php?id=<?= (int) $ilan["id"] ?>"
                                    class="buton sil"
                                    onclick="return confirm('Bu ilanı silmek istediğinize emin misiniz?');"
                                >Sil</a>
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
                    <a href="<?= guvenli(
                        sayfa_linki($sayfa - 1, $arama, $durum, $siralama)
                    ) ?>">← Önceki</a>
                <?php endif; ?>

                <?php
                $ilk = max(1, $sayfa - 2);
                $son = min($toplam_sayfa, $ilk + 4);
                $ilk = max(1, $son - 4);
                ?>

                <?php for ($i = $ilk; $i <= $son; $i++): ?>
                    <a
                        href="<?= guvenli(
                            sayfa_linki($i, $arama, $durum, $siralama)
                        ) ?>"
                        <?= $i === $sayfa ? 'aria-current="page"' : "" ?>
                    ><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($sayfa < $toplam_sayfa): ?>
                    <a href="<?= guvenli(
                        sayfa_linki($sayfa + 1, $arama, $durum, $siralama)
                    ) ?>">Sonraki →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>

    <section class="profil-bolum">
        <h2>Sahiplenme Başvurularım</h2>

        <?php if ($basvurular): ?>
            <?php foreach ($basvurular as $basvuru): ?>
                <article class="profil-kart">
                    <h3><?= guvenli(
                        $basvuru["hayvan_adi"] ?? "İlan silinmiş"
                    ) ?></h3>

                    <p>
                        <strong>Başvuru No:</strong>
                        <?= (int) $basvuru["id"] ?>
                    </p>
                    <p>
                        <strong>Hayvan Türü:</strong>
                        <?= guvenli($basvuru["tur"] ?? "-") ?>
                    </p>
                    <p>
                        <strong>Şehir:</strong>
                        <?= guvenli($basvuru["sehir"] ?? "-") ?>
                    </p>
                    <p>
                        <strong>Başvuru Tarihi:</strong>
                        <?= guvenli($basvuru["basvuru_tarihi"]) ?>
                    </p>
                    <p>
                        <strong>Başvuru Durumu:</strong>
                        <span class="profil-durum <?= durum_sinifi($basvuru["durum"]) ?>">
                            <?= guvenli($basvuru["durum"]) ?>
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
                    <h3><?= guvenli(
                        $favori["ilan_basligi"] ?: "Hayvan Sahiplendirme İlanı"
                    ) ?></h3>

                    <p>
                        <strong>Hayvan Adı:</strong>
                        <?= guvenli($favori["hayvan_adi"]) ?>
                    </p>
                    <p>
                        <strong>Tür:</strong>
                        <?= guvenli($favori["tur"]) ?>
                        · <strong>Yaş:</strong>
                        <?= (int) $favori["yas"] ?>
                    </p>
                    <p>
                        <strong>Şehir:</strong>
                        <?= guvenli($favori["sehir"]) ?>
                    </p>

                    <div class="kart-islemler">
                        <a
                            href="ilan-detay.php?id=<?= (int) $favori["id"] ?>"
                            class="buton"
                        >İlanı İncele</a>

                        <form action="favori-sil.php" method="POST">
                            <input
                                type="hidden"
                                name="ilan_id"
                                value="<?= (int) $favori["id"] ?>"
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

<script>
    const basariKutusu = document.getElementById("basari-kutusu");

    if (basariKutusu) {
        setTimeout(function () {
            basariKutusu.remove();
        }, 2000);
    }
</script>

</body>
</html>