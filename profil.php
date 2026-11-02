<?php
session_start();
require_once __DIR__ . "/site-ust-ayar.php";

/* Dil tercihi */
if (
    is_string($_GET["lang"] ?? null)
    && in_array($_GET["lang"], ["tr", "en"], true)
) {
    $_SESSION["dil"] = $_GET["lang"];

    $parametreler = $_GET;
    unset($parametreler["lang"]);

    $adres = "profil.php";

    if ($parametreler) {
        $adres .= "?" . http_build_query($parametreler);
    }

    header("Location: " . $adres);
    exit();
}

$dil = in_array($_SESSION["dil"] ?? "", ["tr", "en"], true)
    ? $_SESSION["dil"]
    : "tr";

if ((int)($_SESSION["uye_id"] ?? 0) < 1) {
    header("Location: giris.php");
    exit();
}

require_once __DIR__ . "/baglanti.php";

$uye_id = (int)$_SESSION["uye_id"];

function guvenli($deger)
{
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

function ceviri($tr, $en)
{
    global $dil;

    return $dil === "en" ? $en : $tr;
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

    $veriler = mysqli_fetch_all(
        mysqli_stmt_get_result($sorgu),
        MYSQLI_ASSOC
    );

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

function yas_yazisi($yas)
{
    if ($yas === null || $yas === "") {
        return ceviri("Belirtilmemiş", "Not specified");
    }

    $yas = (int)$yas;

    if ($yas === 0) {
        return ceviri("1 yaşından küçük", "Under 1 year old");
    }

    return ceviri(
        $yas . " yaş",
        $yas . ($yas === 1 ? " year old" : " years old")
    );
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

function sayfa_linki($sayfa, $arama, $durum, $siralama)
{
    return "profil.php?" . http_build_query([
        "arama" => $arama,
        "durum" => $durum,
        "siralama" => $siralama,
        "sayfa" => $sayfa
    ]);
}

function dil_linki($yeni_dil, $sayfa, $arama, $durum, $siralama)
{
    return "profil.php?" . http_build_query([
        "lang" => $yeni_dil,
        "arama" => $arama,
        "durum" => $durum,
        "siralama" => $siralama,
        "sayfa" => $sayfa
    ]);
}

function onay_metni($tr, $en)
{
    return guvenli(json_encode(
        ceviri($tr, $en),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ));
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
    exit();
}

$uye = $uyeler[0];

if (
    !isset($_SESSION["profil_csrf"])
    || !is_string($_SESSION["profil_csrf"])
    || $_SESSION["profil_csrf"] === ""
) {
    $_SESSION["profil_csrf"] = bin2hex(random_bytes(32));
}

$bilgi_hata = "";
$sifre_hata = "";
$basari = "";

$basari_kodu = $_SESSION["profil_basari"] ?? "";
unset($_SESSION["profil_basari"]);

if ($basari_kodu === "bilgi_guncellendi") {
    $basari = ceviri(
        "Bilgileriniz başarıyla güncellendi.",
        "Your details have been updated successfully."
    );
} elseif ($basari_kodu === "sifre_degisti") {
    $basari = ceviri(
        "Şifreniz başarıyla değiştirildi.",
        "Your password has been changed successfully."
    );
}

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
        $mesaj = ceviri(
            "İşlem doğrulanamadı. Sayfayı yenileyip tekrar deneyiniz.",
            "The request could not be verified. Refresh the page and try again."
        );

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
            $bilgi_hata = ceviri(
                "Lütfen tüm alanları doldurunuz.",
                "Please complete all fields."
            );
        } elseif (!filter_var($form_email, FILTER_VALIDATE_EMAIL)) {
            $bilgi_hata = ceviri(
                "Geçerli bir e-posta adresi giriniz.",
                "Please enter a valid email address."
            );
        } elseif (!preg_match('/\A05[0-9]{9}\z/', $form_telefon)) {
            $bilgi_hata = ceviri(
                "Telefon numarası 05 ile başlamalı ve 11 haneli olmalıdır.",
                "The phone number must start with 05 and contain 11 digits."
            );
        } elseif (!password_verify($mevcut_sifre, $uye["sifre"])) {
            $bilgi_hata = ceviri(
                "Mevcut şifreniz yanlış.",
                "Your current password is incorrect."
            );
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
                $bilgi_hata = ceviri(
                    "Bu e-posta adresi başka bir hesap tarafından kullanılıyor.",
                    "This email address is used by another account."
                );
            } else {
                $sorgu = null;

                try {
                    $sorgu = mysqli_prepare(
                        $baglanti,
                        "UPDATE uyeler
                         SET ad_soyad = ?, email = ?, telefon = ?
                         WHERE id = ?"
                    );

                    $kaydedildi = false;

                    if ($sorgu) {
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
                        $sorgu = null;
                    }

                    if ($kaydedildi) {
                        $_SESSION["uye_adsoyad"] = $form_ad;
                        $_SESSION["uye_email"] = $form_email;
                        $_SESSION["profil_basari"] = "bilgi_guncellendi";

                        header("Location: profil.php#bilgilerim");
                        exit();
                    }

                    $bilgi_hata = ceviri(
                        "Bilgiler kaydedilemedi. Tekrar deneyiniz.",
                        "Your details could not be saved. Please try again."
                    );
                } catch (mysqli_sql_exception $e) {
                    $bilgi_hata = ceviri(
                        "Bilgiler kaydedilemedi. Girdiğiniz bilgileri kontrol ediniz.",
                        "Your details could not be saved. Please check your information."
                    );
                } finally {
                    if ($sorgu instanceof mysqli_stmt) {
                        mysqli_stmt_close($sorgu);
                    }
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
            $sifre_hata = ceviri(
                "Lütfen tüm şifre alanlarını doldurunuz.",
                "Please complete all password fields."
            );
        } elseif (!password_verify($mevcut_sifre, $uye["sifre"])) {
            $sifre_hata = ceviri(
                "Mevcut şifreniz yanlış.",
                "Your current password is incorrect."
            );
        } elseif (strlen($yeni_sifre) < 8) {
            $sifre_hata = ceviri(
                "Yeni şifreniz en az 8 karakter olmalıdır.",
                "Your new password must contain at least 8 characters."
            );
        } elseif (strlen($yeni_sifre) > 72) {
            $sifre_hata = ceviri(
                "Yeni şifreniz çok uzun. Daha kısa bir şifre giriniz.",
                "Your new password is too long. Please use a shorter password."
            );
        } elseif ($yeni_sifre !== $sifre_tekrar) {
            $sifre_hata = ceviri(
                "Yeni şifreler birbiriyle eşleşmiyor.",
                "The new passwords do not match."
            );
        } elseif (password_verify($yeni_sifre, $uye["sifre"])) {
            $sifre_hata = ceviri(
                "Yeni şifreniz mevcut şifrenizden farklı olmalıdır.",
                "Your new password must be different from your current password."
            );
        } else {
            $yeni_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);
            $sorgu = null;

            try {
                $sorgu = mysqli_prepare(
                    $baglanti,
                    "UPDATE uyeler
                     SET sifre = ?
                     WHERE id = ? AND sifre = ?"
                );

                $kaydedildi = false;
                $degisen = 0;

                if ($sorgu) {
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
                    $sorgu = null;
                }

                if ($kaydedildi && $degisen === 1) {
                    session_regenerate_id(true);

                    $_SESSION["profil_csrf"] = bin2hex(random_bytes(32));
                    $_SESSION["profil_basari"] = "sifre_degisti";

                    header("Location: profil.php#sifre");
                    exit();
                }

                $sifre_hata = ceviri(
                    "Şifre değiştirilemedi. Sayfayı yenileyip tekrar deneyiniz.",
                    "Your password could not be changed. Refresh the page and try again."
                );
            } catch (mysqli_sql_exception $e) {
                $sifre_hata = ceviri(
                    "Şifre değiştirilemedi. Tekrar deneyiniz.",
                    "Your password could not be changed. Please try again."
                );
            } finally {
                if ($sorgu instanceof mysqli_stmt) {
                    mysqli_stmt_close($sorgu);
                }
            }
        }
    } else {
        $bilgi_hata = ceviri("Geçersiz işlem.", "Invalid request.");
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

    <title><?= guvenli(ceviri(
        "Profilim | Aris Pati Yuvası",
        "My Profile | Aris Pati Yuvası"
    )) ?></title>

    <style>
        * { box-sizing: border-box; }
        html { scrollbar-gutter: stable; }

        body {
            margin: 0;
            background: #f5f0e6;
            color: #284b32;
            font-family: Arial, sans-serif;
        }

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

        .dil-secici a:hover { background: #284b32; }

        .dil-secici a[aria-current="true"] {
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

        .ilan-menu > a, .kategori-baslik {
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
            font: normal 14px/20px Arial,sans-serif;
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

        .kategori-menu { position: relative; flex-shrink: 0; }

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
        .kategori-menu:focus-within .kategori-acilir { display: block; }

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

        main {
            display: grid;
            grid-template-columns: repeat(2,minmax(0,1fr));
            align-items: start;
            gap: 18px;
            width: min(1100px,94%);
            margin: 24px auto;
        }

        main > .profil-bolum { grid-column: 1 / -1; min-width: 0; }
        main > #bilgilerim { grid-column: 1; }
        main > #sifre { grid-column: 2; }

        .profil-bolum {
            padding: 20px;
            border: 1px solid #ded6c6;
            border-radius: 12px;
            background: #fffdf8;
            scroll-margin-top: 20px;
        }

        .profil-bolum h1, .profil-bolum h2 {
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
            grid-template-columns: repeat(3,minmax(0,1fr));
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

        .bolum-baslik h2 { margin: 0; }
        .sayac { color: #687361; font-size: 13px; }

        .hesap-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .form-grup label, .filtre-grup label {
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

        .form-islemler { margin-top: 2px; }

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

        .basari-kutusu {
            position: fixed;
            top: 50%;
            left: 50%;
            z-index: 9999;
            transform: translate(-50%,-50%);
            width: min(380px,calc(100% - 40px));
            padding: 25px;
            border: 1px solid #b7d2ad;
            border-radius: 14px;
            background: #fffdf8;
            color: #284b32;
            box-shadow: 0 12px 45px rgba(0,0,0,.22);
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

        .basari-kutusu p { margin: 0; font-size: 15px; line-height: 1.6; }

        @keyframes basari-kaybol {
            0%,85% { opacity: 1; visibility: visible; }
            100% { opacity: 0; visibility: hidden; }
        }

        .profil-filtre {
            display: grid;
            grid-template-columns: minmax(0,2fr) repeat(2,minmax(0,1fr));
            gap: 12px;
            margin-bottom: 22px;
        }

        .filtre-islemler {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            grid-column: 1 / -1;
        }

        .profil-kart {
            margin-bottom: 16px;
            padding: 16px;
            border: 1px solid #ded6c6;
            border-radius: 12px;
            background: #f7f5ee;
            overflow-wrap: anywhere;
        }

        .profil-kart:last-child { margin-bottom: 0; }

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

        .kart-bilgi { flex: 1; min-width: 0; }

        .profil-kart h3 {
            margin: 0 0 12px;
            font-size: 18px;
            line-height: 1.5;
        }

        .profil-kart p { margin: 7px 0; font-size: 14px; line-height: 1.65; }

        .kart-islemler {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        .kart-islemler form { margin: 0; }

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

        .buton:hover { background: #284b32; }
        .buton.acik { background: #eee5d3; color: #284b32; }
        .buton.acik:hover { background: #e2d6bd; }
        .buton.sil { background: #a64740; color: white; }
        .buton.sil:hover { background: #873b35; }

        .profil-durum {
            display: inline-block;
            padding: 4px 9px;
            border-radius: 6px;
            background: #eee5d3;
            font-size: 13px;
            font-weight: bold;
        }

        .profil-durum.bekliyor { background: #fff0cd; color: #805b00; }
        .profil-durum.onaylandi { background: #e0edda; color: #2f6334; }
        .profil-durum.reddedildi { background: #f8e1dd; color: #9b3b33; }
        .red-nedeni { color: #9b3b33; }

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

        .profil-sayfalama a:hover { background: #e6eddf; }

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

        a:focus-visible, button:focus-visible,
        input:focus-visible, select:focus-visible {
            outline: 2px solid #284b32;
            outline-offset: 3px;
        }

        .ilan-menu > a:focus-visible,
        .kategori-baslik:focus-visible,
        .dil-secici a:focus-visible { outline-color: #eee5d3; }

        @media (max-width: 1100px) {
            .ust-sag { flex-basis: 100%; }
            .ilan-menu { width: 100%; }
        }

        @media (max-width: 760px) {
            main { grid-template-columns: 1fr; gap: 14px; }
            main > #bilgilerim, main > #sifre { grid-column: 1 / -1; }
            .profil-bilgileri, .profil-filtre { grid-template-columns: 1fr; }
            .profil-bolum { padding: 17px; }
        }

        @media (max-width: 700px) {
            #aris-ortak-ust { gap: 16px; padding: 15px 18px; }
            .ilan-menu > a, .kategori-baslik { padding: 8px; font-size: 13px; }
        }

        @media (max-width: 600px) {
            .kart-icerik { flex-direction: column; gap: 15px; }
            .profil-fotograf { width: 100%; height: 200px; }
        }
    </style>
<link rel="stylesheet" href="site-ust.css?v=20261102-1">
</head>

<body>

<?php if ($basari !== ""): ?>
    <div class="basari-kutusu" id="basari-kutusu" role="status">
        <span class="basari-isaret" aria-hidden="true">✓</span>
        <p><?= guvenli($basari) ?></p>
    </div>
<?php endif; ?>

<?php require __DIR__ . "/site-ust.php"; ?>

<main>
    <section class="profil-bolum">
        <h1><?= guvenli(ceviri("Profil Bilgilerim", "My Profile Details")) ?></h1>

        <div class="profil-bilgileri">
            <p>
                <strong><?= guvenli(ceviri("Ad Soyad", "Full Name")) ?></strong>
                <?= guvenli($uye["ad_soyad"]) ?>
            </p>
            <p>
                <strong><?= guvenli(ceviri("E-posta", "Email")) ?></strong>
                <?= guvenli($uye["email"]) ?>
            </p>
            <p>
                <strong><?= guvenli(ceviri("Telefon", "Phone")) ?></strong>
                <?= guvenli($uye["telefon"]) ?>
            </p>
        </div>

        <div class="kart-islemler">
            <a href="#bilgilerim" class="buton">
                <?= guvenli(ceviri("Bilgilerimi Düzenle", "Edit My Details")) ?>
            </a>
            <a href="#sifre" class="buton acik">
                <?= guvenli(ceviri("Şifremi Değiştir", "Change Password")) ?>
            </a>
            <a href="mesajlar.php" class="buton acik">
                <?= guvenli(ceviri("Mesajlarımı Gör", "View Messages")) ?>
            </a>
            <a href="ilan-ver.php" class="buton acik">
                <?= guvenli(ceviri("Yeni İlan Ver", "Post a New Listing")) ?>
            </a>
        </div>
    </section>

    <section class="profil-bolum" id="bilgilerim">
        <h2><?= guvenli(ceviri("Bilgilerimi Düzenle", "Edit My Details")) ?></h2>

        <p>
            <?= guvenli(ceviri(
                "Bilgilerinizi güncellemek için mevcut şifrenizi giriniz.",
                "Enter your current password to update your details."
            )) ?>
        </p>

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
                <label for="ad_soyad">
                    <?= guvenli(ceviri("Ad Soyad", "Full Name")) ?>
                </label>
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
                <label for="email">
                    <?= guvenli(ceviri("E-posta Adresi", "Email Address")) ?>
                </label>
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
                <label for="telefon">
                    <?= guvenli(ceviri("Telefon Numarası", "Phone Number")) ?>
                </label>
                <input
                    type="tel"
                    id="telefon"
                    name="telefon"
                    value="<?= guvenli($form_telefon) ?>"
                    placeholder="05XXXXXXXXX"
                    pattern="05[0-9]{9}"
                    minlength="11"
                    maxlength="11"
                    inputmode="numeric"
                    autocomplete="tel"
                    aria-describedby="telefon-notu"
                    required
                >
                <small class="alan-notu" id="telefon-notu">
                    <?= guvenli(ceviri(
                        "05 ile başlayan 11 haneli numaranızı boşluksuz yazınız.",
                        "Enter your 11-digit number starting with 05, without spaces."
                    )) ?>
                </small>
            </div>

            <div class="form-grup">
                <label for="bilgi_sifre">
                    <?= guvenli(ceviri("Mevcut Şifreniz", "Current Password")) ?>
                </label>
                <input
                    type="password"
                    id="bilgi_sifre"
                    name="bilgi_sifre"
                    autocomplete="current-password"
                    required
                >
            </div>

            <div class="form-islemler">
                <button type="submit" class="buton">
                    <?= guvenli(ceviri("Bilgilerimi Kaydet", "Save My Details")) ?>
                </button>
            </div>
        </form>
    </section>

    <section class="profil-bolum" id="sifre">
        <h2><?= guvenli(ceviri("Şifremi Değiştir", "Change Password")) ?></h2>

        <p>
            <?= guvenli(ceviri(
                "Yeni şifreniz en az 8 karakter olmalıdır.",
                "Your new password must contain at least 8 characters."
            )) ?>
        </p>

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
                <label for="mevcut_sifre">
                    <?= guvenli(ceviri("Mevcut Şifre", "Current Password")) ?>
                </label>
                <input
                    type="password"
                    id="mevcut_sifre"
                    name="mevcut_sifre"
                    autocomplete="current-password"
                    required
                >
            </div>

            <div class="form-grup">
                <label for="yeni_sifre">
                    <?= guvenli(ceviri("Yeni Şifre", "New Password")) ?>
                </label>
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
                <label for="sifre_tekrar">
                    <?= guvenli(ceviri("Yeni Şifre Tekrarı", "Confirm New Password")) ?>
                </label>
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
                <button type="submit" class="buton">
                    <?= guvenli(ceviri("Şifremi Değiştir", "Change Password")) ?>
                </button>
            </div>
        </form>
    </section>

    <section class="profil-bolum">
        <div class="bolum-baslik">
            <h2><?= guvenli(ceviri("İlanlarım", "My Listings")) ?></h2>
            <span class="sayac">
                <?= $toplam_ilan ?>
                <?= guvenli(ceviri("ilan bulundu", "listings found")) ?>
            </span>
        </div>

        <form method="GET" action="profil.php" class="profil-filtre">
            <div class="filtre-grup">
                <label for="arama">
                    <?= guvenli(ceviri("İlan Ara", "Search Listings")) ?>
                </label>
                <input
                    type="text"
                    id="arama"
                    name="arama"
                    placeholder="<?= guvenli(ceviri(
                        "Hayvan adı veya ilan başlığı...",
                        "Pet name or listing title..."
                    )) ?>"
                    value="<?= guvenli($arama) ?>"
                >
            </div>

            <div class="filtre-grup">
                <label for="durum">
                    <?= guvenli(ceviri("İlan Durumu", "Listing Status")) ?>
                </label>
                <select id="durum" name="durum">
                    <option value="">
                        <?= guvenli(ceviri("Tüm Durumlar", "All Statuses")) ?>
                    </option>

                    <?php foreach ($gecerli_durumlar as $secenek): ?>
                        <option
                            value="<?= guvenli($secenek) ?>"
                            <?= $durum === $secenek ? "selected" : "" ?>
                        ><?= guvenli(durum_adi($secenek)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filtre-grup">
                <label for="siralama">
                    <?= guvenli(ceviri("Sıralama", "Sort Order")) ?>
                </label>
                <select id="siralama" name="siralama">
                    <option value="yeni" <?= $siralama === "yeni" ? "selected" : "" ?>>
                        <?= guvenli(ceviri("En Yeni", "Newest")) ?>
                    </option>
                    <option value="eski" <?= $siralama === "eski" ? "selected" : "" ?>>
                        <?= guvenli(ceviri("En Eski", "Oldest")) ?>
                    </option>
                </select>
            </div>

            <div class="filtre-islemler">
                <button type="submit" class="buton">
                    <?= guvenli(ceviri("Ara", "Search")) ?>
                </button>
                <a href="profil.php" class="buton acik">
                    <?= guvenli(ceviri("Temizle", "Clear")) ?>
                </a>
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
                                <strong><?= guvenli(ceviri("Hayvan Adı:", "Animal Name:")) ?></strong>
                                <?= guvenli($ilan["hayvan_adi"]) ?>
                            </p>

                            <p>
                                <strong><?= guvenli(ceviri("Tür:", "Type:")) ?></strong>
                                <?= guvenli(tur_adi($ilan["tur"])) ?>
                                ·
                                <strong><?= guvenli(ceviri("Yaş:", "Age:")) ?></strong>
                                <?= guvenli(yas_yazisi($ilan["yas"])) ?>
                            </p>

                            <p>
                                <strong><?= guvenli(ceviri("Şehir:", "City:")) ?></strong>
                                <?= guvenli($ilan["sehir"]) ?>
                            </p>

                            <p>
                                <strong><?= guvenli(ceviri("Durum:", "Status:")) ?></strong>
                                <span class="profil-durum <?= durum_sinifi($ilan["durum"]) ?>">
                                    <?= guvenli(durum_adi($ilan["durum"])) ?>
                                </span>
                            </p>

                            <?php if ($ilan["durum"] === "Reddedildi"): ?>
                                <p class="red-nedeni">
                                    <strong>
                                        <?= guvenli(ceviri(
                                            "Reddedilme Nedeni:",
                                            "Reason for Rejection:"
                                        )) ?>
                                    </strong>
                                    <?= guvenli($ilan["red_nedeni"] ?? "") ?>
                                </p>
                            <?php endif; ?>

                            <div class="kart-islemler">
                                <a
                                    href="ilan-duzenle.php?id=<?= (int)$ilan["id"] ?>"
                                    class="buton"
                                ><?= guvenli(ceviri("Düzenle", "Edit")) ?></a>

                                <a
                                    href="ilan-sil.php?id=<?= (int)$ilan["id"] ?>"
                                    class="buton sil"
                                    onclick="return confirm(<?= onay_metni(
                                        "Bu ilanı silmek istediğinize emin misiniz?",
                                        "Are you sure you want to delete this listing?"
                                    ) ?>);"
                                ><?= guvenli(ceviri("Sil", "Delete")) ?></a>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p>
                <?= guvenli(ceviri(
                    "Seçtiğiniz kriterlere uygun ilanınız bulunmuyor.",
                    "You have no listings matching these criteria."
                )) ?>
            </p>
        <?php endif; ?>

        <?php if ($toplam_sayfa > 1): ?>
            <nav
                class="profil-sayfalama"
                aria-label="<?= guvenli(ceviri(
                    "İlanlarım sayfaları",
                    "My listings pagination"
                )) ?>"
            >
                <?php if ($sayfa > 1): ?>
                    <a href="<?= guvenli(
                        sayfa_linki($sayfa - 1, $arama, $durum, $siralama)
                    ) ?>">← <?= guvenli(ceviri("Önceki", "Previous")) ?></a>
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
                    ) ?>"><?= guvenli(ceviri("Sonraki", "Next")) ?> →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>

    <section class="profil-bolum">
        <h2>
            <?= guvenli(ceviri(
                "Sahiplenme Başvurularım",
                "My Adoption Applications"
            )) ?>
        </h2>

        <?php if ($basvurular): ?>
            <?php foreach ($basvurular as $basvuru): ?>
                <article class="profil-kart">
                    <h3><?= guvenli(
                        $basvuru["hayvan_adi"]
                            ?? ceviri("İlan silinmiş", "Listing deleted")
                    ) ?></h3>

                    <p>
                        <strong><?= guvenli(ceviri("Başvuru No:", "Application ID:")) ?></strong>
                        <?= (int)$basvuru["id"] ?>
                    </p>

                    <p>
                        <strong><?= guvenli(ceviri("Hayvan Türü:", "Animal Type:")) ?></strong>
                        <?= guvenli(tur_adi($basvuru["tur"] ?? "-")) ?>
                    </p>

                    <p>
                        <strong><?= guvenli(ceviri("Şehir:", "City:")) ?></strong>
                        <?= guvenli($basvuru["sehir"] ?? "-") ?>
                    </p>

                    <p>
                        <strong>
                            <?= guvenli(ceviri("Başvuru Tarihi:", "Application Date:")) ?>
                        </strong>
                        <?= guvenli($basvuru["basvuru_tarihi"]) ?>
                    </p>

                    <p>
                        <strong>
                            <?= guvenli(ceviri("Başvuru Durumu:", "Application Status:")) ?>
                        </strong>
                        <span class="profil-durum <?= durum_sinifi($basvuru["durum"]) ?>">
                            <?= guvenli(durum_adi($basvuru["durum"])) ?>
                        </span>
                    </p>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p>
                <?= guvenli(ceviri(
                    "Henüz sahiplenme başvurusu yapmadınız.",
                    "You have not submitted an adoption application yet."
                )) ?>
            </p>
        <?php endif; ?>
    </section>

    <section class="profil-bolum">
        <h2>❤️ <?= guvenli(ceviri("Favorilerim", "My Favourites")) ?></h2>

        <?php if ($favoriler): ?>
            <?php foreach ($favoriler as $favori): ?>
                <article class="profil-kart">
                    <h3><?= guvenli(
                        $favori["ilan_basligi"]
                            ?: ceviri(
                                "Hayvan Sahiplendirme İlanı",
                                "Pet Adoption Listing"
                            )
                    ) ?></h3>

                    <p>
                        <strong><?= guvenli(ceviri("Hayvan Adı:", "Animal Name:")) ?></strong>
                        <?= guvenli($favori["hayvan_adi"]) ?>
                    </p>

                    <p>
                        <strong><?= guvenli(ceviri("Tür:", "Type:")) ?></strong>
                        <?= guvenli(tur_adi($favori["tur"])) ?>
                        ·
                        <strong><?= guvenli(ceviri("Yaş:", "Age:")) ?></strong>
                        <?= guvenli(yas_yazisi($favori["yas"])) ?>
                    </p>

                    <p>
                        <strong><?= guvenli(ceviri("Şehir:", "City:")) ?></strong>
                        <?= guvenli($favori["sehir"]) ?>
                    </p>

                    <div class="kart-islemler">
                        <a
                            href="ilan-detay.php?id=<?= (int)$favori["id"] ?>"
                            class="buton"
                        ><?= guvenli(ceviri("İlanı İncele", "View Listing")) ?></a>

                        <form action="favori-sil.php" method="POST">
                            <input
                                type="hidden"
                                name="ilan_id"
                                value="<?= (int)$favori["id"] ?>"
                            >

                            <button type="submit" class="buton acik">
                                ♡ <?= guvenli(ceviri(
                                    "Favorilerden Çıkar",
                                    "Remove from Favourites"
                                )) ?>
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p>
                <?= guvenli(ceviri(
                    "Henüz favorilerinize eklediğiniz onaylı bir ilan bulunmuyor.",
                    "You have no approved listings saved to your favourites yet."
                )) ?>
            </p>
        <?php endif; ?>
    </section>
</main>

<footer>
    <p>
        © <?= date("Y") ?> Aris Pati Yuvası ·
        <?= guvenli(ceviri(
            "Hayvan Sahiplendirme Platformu",
            "Pet Adoption Platform"
        )) ?>
    </p>
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