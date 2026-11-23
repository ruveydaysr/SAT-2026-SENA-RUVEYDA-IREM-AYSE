<?php
session_start();
header("Cache-Control: no-store, private");
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
if ((int)($_SESSION["uye_id"] ?? 0) < 1) {
    header("Location: giris.php");
    exit();
}
require_once __DIR__ . "/baglanti.php";
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
function sorgula($baglanti, $sql, $tipler, ...$degerler)
{
    $sorgu = mysqli_prepare($baglanti, $sql);
    if (!$sorgu) {
        throw new RuntimeException("Sorgu hazırlanamadı.");
    }
    try {
        mysqli_stmt_bind_param($sorgu, $tipler, ...$degerler);
        if (!mysqli_stmt_execute($sorgu)) {
            throw new RuntimeException("Sorgu çalıştırılamadı.");
        }
        return mysqli_stmt_get_result($sorgu);
    } finally {
        mysqli_stmt_close($sorgu);
    }
}

function sohbet_fotografi($fotograf)
{
    $fotograf = (string)$fotograf;
    $dosya = basename($fotograf);
    if (preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$/i', $dosya)
        && is_file(__DIR__ . "/uploads/" . $dosya)) {
        return "uploads/" . $dosya;
    }
    if (preg_match('~^images/hayvanlar/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$~i', $fotograf)
        && is_file(__DIR__ . "/" . $fotograf)) {
        return $fotograf;
    }
    return "";
}

$profil_gorunumu = ($_GET["gorunum"] ?? null) === "profil";

function sohbet_adresi($ilan_id, $kisi, $yeni_dil = null)
{
    global $profil_gorunumu;

    $parametreler = $profil_gorunumu ? ["gorunum" => "profil"] : [];
    if ($yeni_dil !== null) {
        $parametreler["lang"] = $yeni_dil;
    }
    if ($ilan_id && $ilan_id > 0 && $kisi && $kisi > 0) {
        $parametreler["ilan_id"] = (int)$ilan_id;
        $parametreler["kisi"] = (int)$kisi;
    }

    return "mesajlar.php" . ($parametreler ? "?" . http_build_query($parametreler) : "");
}
function dil_linki($yeni_dil, $ilan_id, $kisi)
{
    return sohbet_adresi($ilan_id, $kisi, $yeni_dil);
}
$ben = (int)$_SESSION["uye_id"];
$ilan_id = filter_input(INPUT_GET, "ilan_id", FILTER_VALIDATE_INT);
$kisi = filter_input(INPUT_GET, "kisi", FILTER_VALIDATE_INT);
if ($dil_degisti && !$post_istegi) {
    header("Location: " . sohbet_adresi($ilan_id, $kisi));
    exit();
}
$hata = "";
$sohbet = null;
$mesajlar = [];
$metin = is_string($_POST["mesaj"] ?? null)
    ? trim($_POST["mesaj"])
    : "";
if (
    !isset($_SESSION["sohbet_token"])
    || !is_string($_SESSION["sohbet_token"])
    || $_SESSION["sohbet_token"] === ""
) {
    $_SESSION["sohbet_token"] = bin2hex(random_bytes(32));
}
/* Sohbet yetkisi */
if (
    $ilan_id && $ilan_id > 0
    && $kisi && $kisi > 0
    && $kisi !== $ben
) {
    $sonuc = sorgula(
        $baglanti,
        "SELECT i.id, i.uye_id, i.ilan_basligi, i.fotograf,
                i.hayvan_adi, i.durum,
                u.ad_soyad AS kisi_adi
         FROM ilanlar i
         JOIN uyeler u ON u.id = ?
         WHERE i.id = ?
         LIMIT 1",
        "ii",
        $kisi,
        $ilan_id
    );
    $aday = mysqli_fetch_assoc($sonuc);
    if ($aday) {
        $sahip = (int)$aday["uye_id"];
        $ilgili_uye = $kisi === $sahip && $ben !== $sahip;
        $sahip_yaniti = false;
        if ($ben === $sahip) {
            $kontrol = sorgula(
                $baglanti,
                "SELECT id
                 FROM ilan_mesajlari
                 WHERE ilan_id = ?
                   AND gonderen_id = ?
                   AND alici_id = ?
                 LIMIT 1",
                "iii",
                $ilan_id,
                $kisi,
                $ben
            );
            $sahip_yaniti = mysqli_num_rows($kontrol) > 0;
        }
        if ($ilgili_uye || $sahip_yaniti) {
            $sohbet = $aday;
        }
    }
    if (!$sohbet) {
        $hata = ceviri(
            "Bu sohbet bulunamadı veya bu kişiye mesaj gönderemezsiniz.",
            "This conversation could not be found, or you cannot message this person."
        );
    }
}
/* Dil değiştirme isteği mesaj göndermez. */
if ($post_istegi && !$dil_degisti) {
    $token = $_POST["token"] ?? "";
    if (!$sohbet) {
        $hata = ceviri(
            "Geçerli bir sohbet seçiniz.",
            "Please select a valid conversation."
        );
    } elseif (
        !is_string($token)
        || !hash_equals($_SESSION["sohbet_token"], $token)
    ) {
        $hata = ceviri(
            "Sayfayı yenileyip tekrar deneyiniz.",
            "Please refresh the page and try again."
        );
    } elseif ($sohbet["durum"] !== "Onaylandı") {
        $hata = ceviri(
            "İlan yayında olmadığı için yeni mesaj gönderilemiyor.",
            "New messages cannot be sent because this listing is not active."
        );
    } elseif (
        $metin === ""
        || mb_strlen($metin, "UTF-8") > 5000
    ) {
        $hata = ceviri(
            "Mesajınız 1 ile 5000 karakter arasında olmalıdır.",
            "Your message must contain between 1 and 5000 characters."
        );
    } else {
        try {
            sorgula(
                $baglanti,
                "INSERT INTO ilan_mesajlari
                    (ilan_id, gonderen_id, alici_id, mesaj)
                 VALUES (?, ?, ?, ?)",
                "iiis",
                $ilan_id,
                $ben,
                $kisi,
                $metin
            );
            $_SESSION["sohbet_token"] = bin2hex(random_bytes(32));
            header(
                "Location: " . sohbet_adresi($ilan_id, $kisi) . "#son"
            );
            exit();
        } catch (mysqli_sql_exception | RuntimeException $e) {
            $hata = ceviri(
                "Mesaj gönderilemedi. Lütfen tekrar deneyiniz.",
                "Your message could not be sent. Please try again."
            );
        }
    }
}
/* Mesajları getir ve gösterilen gelen mesajları okundu yap */
if ($sohbet) {
    $sonuc = sorgula(
        $baglanti,
        "SELECT id, gonderen_id, mesaj, tarih, okundu
         FROM ilan_mesajlari
         WHERE ilan_id = ?
           AND (
               (gonderen_id = ? AND alici_id = ?)
               OR
               (gonderen_id = ? AND alici_id = ?)
           )
         ORDER BY id DESC
         LIMIT 200",
        "iiiii",
        $ilan_id,
        $ben,
        $kisi,
        $kisi,
        $ben
    );
    $mesajlar = array_reverse(
        mysqli_fetch_all($sonuc, MYSQLI_ASSOC)
    );
    if ($mesajlar) {
        $ilk_gosterilen = (int)$mesajlar[0]["id"];
        $son_gosterilen = (int)$mesajlar[
            count($mesajlar) - 1
        ]["id"];
        sorgula(
            $baglanti,
            "UPDATE ilan_mesajlari
             SET okundu = 1
             WHERE ilan_id = ?
               AND gonderen_id = ?
               AND alici_id = ?
               AND id BETWEEN ? AND ?",
            "iiiii",
            $ilan_id,
            $kisi,
            $ben,
            $ilk_gosterilen,
            $son_gosterilen
        );
    }
}
/* Okunmamış mesaj bildirimi */
$bildirim_sonuc = sorgula(
    $baglanti,
    "SELECT id
     FROM ilan_mesajlari
     WHERE alici_id = ? AND okundu = 0
     LIMIT 1",
    "i",
    $ben
);
$okunmamis_mesaj_var = mysqli_num_rows($bildirim_sonuc) > 0;
/* Sohbet listesi */
$liste_sonuc = sorgula(
    $baglanti,
    "SELECT m.*, i.ilan_basligi, i.hayvan_adi, i.fotograf,
            u.id AS kisi_id, u.ad_soyad AS kisi_adi,
            (
                SELECT COUNT(*)
                FROM ilan_mesajlari yeni
                WHERE yeni.ilan_id = m.ilan_id
                  AND yeni.gonderen_id = u.id
                  AND yeni.alici_id = ?
                  AND yeni.okundu = 0
            ) AS okunmamis
     FROM ilan_mesajlari m
     JOIN (
         SELECT MAX(id) AS son_id
         FROM ilan_mesajlari
         WHERE gonderen_id = ? OR alici_id = ?
         GROUP BY ilan_id,
                  LEAST(gonderen_id, alici_id),
                  GREATEST(gonderen_id, alici_id)
     ) son ON son.son_id = m.id
     JOIN ilanlar i ON i.id = m.ilan_id
     JOIN uyeler u
       ON u.id = CASE
           WHEN m.gonderen_id = ?
           THEN m.alici_id
           ELSE m.gonderen_id
       END
     ORDER BY m.id DESC",
    "iiii",
    $ben,
    $ben,
    $ben,
    $ben
);
$sohbetler = mysqli_fetch_all($liste_sonuc, MYSQLI_ASSOC);
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
        "Mesajlarım | Aris Pati Yuvası",
        "My Messages | Aris Pati Yuvası"
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
            background: #f5f0e6;
            color: #284b32;
            font-family: Arial, sans-serif;
        }
        a {
            color: #284b32;
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
            font: bold 12px Arial, sans-serif;
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
            gap: 7px;
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
        .mesaj-bildirim {
            display: inline-block;
            flex-shrink: 0;
            width: 9px;
            height: 9px;
            border: 1px solid white;
            border-radius: 50%;
            background: #ef5350;
            vertical-align: middle;
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
            color: white;
        }
        main {
            width: min(960px,92%);
            margin: 28px auto;
        }
        h1 {
            margin: 0 0 18px;
            font-size: 25px;
        }
        .duzen {
            display: grid;
            grid-template-columns: 260px minmax(0,1fr);
            align-items: start;
            gap: 16px;
        }
        .liste,
        .sohbet {
            min-width: 0;
            overflow: hidden;
            border: 1px solid #ded6c6;
            border-radius: 12px;
            background: #fffdf8;
        }
        .liste {
            max-height: 520px;
            overflow-y: auto;
        }
        .liste-baslik {
            margin: 0;
            padding: 15px 16px;
            border-bottom: 1px solid #ded6c6;
            font-size: 16px;
        }
        .sohbet-link {
            display: block;
            padding: 14px 16px;
            border-bottom: 1px solid #e6e0d4;
            font-size: 14px;
            text-decoration: none;
            overflow-wrap: anywhere;
        }
        .sohbet-link:last-child {
            border-bottom: 0;
        }
        .sohbet-link:hover,
        .sohbet-link.aktif {
            background: #e6eddf;
        }
        .sohbet-kisi {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .sohbet-link small {
            display: block;
            margin-top: 6px;
            color: #687361;
            font-size: 12px;
            line-height: 1.5;
        }
        .sohbet-baslik {
            padding: 15px 18px;
            border-bottom: 1px solid #ded6c6;
            overflow-wrap: anywhere;
        }
        .sohbet-baslik h2 {
            margin: 0 0 7px;
            font-size: 18px;
        }
        .sohbet-baslik a {
            font-size: 13px;
            line-height: 1.5;
        }
        .akis {
            display: flex;
            flex-direction: column;
            gap: 10px;
            height: 320px;
            padding: 16px;
            overflow-y: auto;
        }
        .balon {
            align-self: flex-start;
            flex-shrink: 0;
            max-width: 85%;
            padding: 10px 13px;
            border-radius: 12px;
            background: #eee5d3;
            font-size: 14px;
            overflow-wrap: anywhere;
        }
        .balon.ben {
            align-self: flex-end;
            background: #dcebd8;
        }
        .balon p {
            margin: 0;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        .balon time {
            display: block;
            margin-top: 7px;
            color: #687361;
            font-size: 11px;
        }
        .yaz {
            padding: 14px 16px;
            border-top: 1px solid #ded6c6;
        }
        .yaz label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: bold;
        }
        .yaz textarea {
            display: block;
            width: 100%;
            min-height: 75px;
            padding: 10px;
            border: 1px solid #ded6c6;
            border-radius: 8px;
            background: white;
            color: #284b32;
            font: inherit;
            font-size: 14px;
            resize: vertical;
        }
        .yaz button {
            display: block;
            margin: 10px 0 0 auto;
            padding: 11px 20px;
            border: 0;
            border-radius: 8px;
            background: #426b46;
            color: white;
            font: inherit;
            font-size: 14px;
            cursor: pointer;
        }
        .yaz button:hover {
            background: #284b32;
        }
        .bos {
            margin: 0;
            padding: 20px;
            color: #687361;
            font-size: 14px;
            line-height: 1.65;
        }
        .hata {
            margin-bottom: 18px;
            padding: 14px;
            border-radius: 8px;
            background: #fae5df;
            color: #8f3029;
            font-size: 14px;
            line-height: 1.6;
        }
        .alt-not {
            margin-top: 14px;
            color: #687361;
            font-size: 12px;
            line-height: 1.6;
        }
        footer {
            padding: 10px 20px 24px;
            color: #687361;
            font-size: 12px;
            line-height: 1.6;
            text-align: center;
        }
        a:focus-visible,
        button:focus-visible,
        textarea:focus-visible {
            outline: 2px solid #284b32;
            outline-offset: 3px;
        }
        .ilan-menu > a:focus-visible,
        .kategori-baslik:focus-visible,
        .dil-secici a:focus-visible,
        .dil-secici button:focus-visible {
            outline-color: #eee5d3;
        }
        @media (max-width: 1100px) {
            .ust-sag {
                flex-basis: 100%;
            }
            .ilan-menu {
                width: 100%;
            }
        }
        @media (max-width: 750px) {
            .duzen {
                grid-template-columns: 1fr;
            }
            .liste {
                max-height: 220px;
            }
            .akis {
                height: 300px;
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
    
/* Mesaj sayfası */
main { width: min(1180px, calc(100% - 40px)); margin: 30px auto 40px; }
.sayfa-ust { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:22px; }
.sayfa-ust h1 { margin:0 0 7px; }
.sayfa-ust p { margin:0; color:#687361; font-size:14px; line-height:1.6; }
.kesfet { display:inline-block; padding:11px 16px; border-radius:9px; background:#426b46; color:white; text-decoration:none; font-size:13px; white-space:nowrap; }
.duzen { grid-template-columns:320px minmax(0,1fr); gap:0; align-items:stretch; border:1px solid #ded6c6; border-radius:16px; overflow:hidden; background:#fffdf8; box-shadow:0 8px 30px #284b3208; }
.liste,.sohbet { border:0; border-radius:0; }
.liste { max-height:none; height:640px; border-right:1px solid #ded6c6; display:flex; flex-direction:column; overflow:hidden; }
.liste-baslik { display:flex; align-items:center; justify-content:space-between; padding:20px; }
.liste-baslik span { font-size:12px; padding:4px 9px; border-radius:20px; background:#e6eddf; }
.arama-kutu { padding:14px; border-bottom:1px solid #ebe6dc; }
.arama-kutu input { width:100%; padding:11px 12px; border:1px solid #ded6c6; border-radius:9px; background:#f9f7f1; font:inherit; font-size:13px; }
#sohbet-listesi { overflow:auto; flex:1; min-height:0; }
.sohbet-link { display:flex; align-items:center; gap:12px; padding:16px 14px; border-left:3px solid transparent; }
.sohbet-link.aktif { border-left-color:#426b46; }
.avatar { display:grid; place-items:center; width:46px; height:46px; flex-shrink:0; border-radius:13px; background:#e6eddf; overflow:hidden; color:#426b46; font-size:20px; font-weight:bold; }
.avatar img { width:100%; height:100%; object-fit:cover; }
.sohbet-ozet { flex:1; min-width:0; }
.sohbet-kisi strong { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.sohbet-kisi time { font-size:10px; white-space:nowrap; color:#687361; }
.sohbet-link small { overflow:hidden; white-space:nowrap; text-overflow:ellipsis; margin-top:4px; }
.rozet { background:#426b46; color:white; border-radius:12px; padding:3px 7px; font-size:11px; margin-left:auto; }
.onizleme { display:flex; align-items:center; gap:6px; margin-top:5px; }
.onizleme small { flex:1; min-width:0; }
.sohbet { height:640px; display:flex; flex-direction:column; }
.sohbet-baslik { display:flex; align-items:center; gap:12px; min-height:88px; padding:16px 22px; }
.sohbet-baslik h2 { font-size:17px; margin:0 0 5px; }
.sohbet-baslik .baslik-metin { flex:1; min-width:0; }
.sohbet-baslik small { color:#687361; font-size:12px; }
.ilan-link { font-size:12px; text-decoration:none; border:1px solid #d6dfcf; border-radius:8px; padding:9px 12px; }
.akis { flex:1; height:auto; min-height:0; padding:22px; background:#f7f6ef; gap:12px; }
.balon { max-width:78%; border-radius:14px 14px 14px 3px; background:white; border:1px solid #e7e6dd; padding:11px 14px; }
.balon.ben { background:#426b46; color:white; border-color:#426b46; border-radius:14px 14px 3px 14px; }
.balon.ben time { color:#e4eddf; }
.balon time { text-align:right; font-size:10px; }
.gun { align-self:center; color:#687361; background:#e9eddf; border-radius:20px; padding:6px 12px; font-size:11px; margin:4px 0 8px; }
.yaz { padding:15px 20px; background:#fffdf8; }
.yaz label { font-size:14px; }
.yaz textarea { min-height:64px; max-height:160px; font-size:16px; }
.yaz-alt { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:10px; }
.yaz-alt small { color:#687361; font-size:11px; }
.yaz button { margin:0; }
.bos-alan { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:35px; }
.bos-simge { display:grid; place-items:center; width:76px; height:76px; border-radius:24px; background:#e6eddf; color:#426b46; font-size:34px; margin-bottom:20px; }
.bos-alan h2 { font-size:22px; margin:0 0 12px; }
.bos-alan p { color:#687361; font-size:14px; line-height:1.8; max-width:360px; margin:0 0 20px; }
.bos-liste { padding:32px 22px; text-align:center; color:#687361; font-size:13px; line-height:1.8; }
#yenile-durum { color:#687361; font-size:12px; }
.yeni-mesaj { align-self:center; border:1px solid #426b46; border-radius:18px; padding:8px 13px; background:white; color:#284b32; cursor:pointer; }
[hidden] { display:none !important; }
@media(max-width:800px) {
    .duzen { grid-template-columns:260px minmax(0,1fr); }
    .sohbet-baslik { flex-wrap:wrap; padding:14px; }
    .balon { max-width:90%; }
}
.sohbetlere-don, .mesaj-menu-dugmesi { display:none; }
@media (max-width:800px) {
    .mesaj-menu-dugmesi:not([hidden]) {
        display:inline-flex; align-items:center; min-height:44px; padding:0 14px;
        border:0; border-radius:8px; background:#f5f0e6; color:#284b32;
        font:bold 14px Arial, sans-serif; cursor:pointer;
    }
    .mesaj-menu-dugmesi:not([hidden])[aria-expanded="false"] + .ilan-menu { display:none; }
}
.sohbet-ozet small, .arama-kutu input { font-size:14px; }
.balon { font-size:15px; }
@media(max-width:650px) {
    main { width:calc(100% - 24px); margin:20px auto; }
    .sayfa-ust { align-items:flex-start; flex-direction:column; }
    .duzen { grid-template-columns:1fr; }
    .liste { height: min(480px, 65dvh); min-height: 260px; border:0; }
    .duzen:not(.sohbet-acik) .sohbet, .sohbet-acik .liste { display:none; }
    .sohbet { height: clamp(360px, 70dvh, 580px); }
    .sohbetlere-don {
        display:flex; align-items:center; min-height:44px; padding:0 14px;
        color:#284b32; font-weight:bold; border-bottom:1px solid #ded6c6;
    }
    .akis { padding:15px; }
    .yaz { padding:14px; }
}

    </style>
<?php if ($profil_gorunumu): ?>
<link rel="stylesheet" href="profil-hesap.css?v=20261104-1">
<?php endif; ?>
</head>
<body>
<header class="ilan-ust" id="aris-ortak-ust">
    <a href="index.php" class="ilan-logo">
        <img
            src="images/aris-logoo.png"
            alt="<?= guvenli(ceviri(
                "Aris Pati Yuvası logosu",
                "Aris Pati Yuvası logo"
            )) ?>"
            width="54"
            height="54"
        >
        <span>
            <strong>Aris Pati Yuvası</strong>
            <small>
                <?= guvenli(ceviri(
                    "Her can dostuna bir yuva",
                    "A home for every companion"
                )) ?>
            </small>
        </span>
    </a>
    <div class="ust-sag">
        <nav
            class="dil-secici"
            aria-label="<?= guvenli(ceviri(
                "Dil seçimi",
                "Language selection"
            )) ?>"
        >
            <?php if ($sohbet && $sohbet["durum"] === "Onaylandı"): ?>
                <button
                    type="submit"
                    form="mesaj-form"
                    name="dil_secimi"
                    value="tr"
                    formnovalidate
                    lang="tr"
                    aria-label="Türkçe"
                    <?= $dil === "tr" ? 'aria-current="true"' : "" ?>
                >TR</button>
                <button
                    type="submit"
                    form="mesaj-form"
                    name="dil_secimi"
                    value="en"
                    formnovalidate
                    lang="en"
                    aria-label="English"
                    <?= $dil === "en" ? 'aria-current="true"' : "" ?>
                >EN</button>
            <?php else: ?>
                <a
                    href="<?= guvenli(dil_linki("tr", $ilan_id, $kisi)) ?>"
                    lang="tr"
                    hreflang="tr"
                    aria-label="Türkçe"
                    <?= $dil === "tr" ? 'aria-current="true"' : "" ?>
                >TR</a>
                <a
                    href="<?= guvenli(dil_linki("en", $ilan_id, $kisi)) ?>"
                    lang="en"
                    hreflang="en"
                    aria-label="English"
                    <?= $dil === "en" ? 'aria-current="true"' : "" ?>
                >EN</a>
            <?php endif; ?>
        </nav>
        <button class="mesaj-menu-dugmesi" type="button" aria-expanded="false"
                aria-controls="mesaj-ana-menu" hidden><?= guvenli(ceviri("Menü", "Menu")) ?></button>
        <nav
            id="mesaj-ana-menu"
            class="ilan-menu"
            aria-label="<?= guvenli(ceviri(
                "Ana menü",
                "Main navigation"
            )) ?>"
        >
            <a href="index.php">
                <?= guvenli(ceviri("Ana Sayfa", "Home")) ?>
            </a>
            <a href="ilanlar.php">
                <?= guvenli(ceviri("İlanlar", "Listings")) ?>
            </a>
            <div class="kategori-menu">
                <button
                    type="button"
                    class="kategori-baslik"
                    aria-controls="aris-kategori-listesi"
                >
                    <?= guvenli(ceviri(
                        "Hayvan Kategorileri",
                        "Animal Categories"
                    )) ?>
                    <span aria-hidden="true">▾</span>
                </button>
                <div class="kategori-acilir" id="aris-kategori-listesi">
                    <?php foreach ($menu_kategorileri as $ad => $simge): ?>
                        <a href="ilanlar.php?tur=<?= rawurlencode($ad) ?>">
                            <span aria-hidden="true"><?= $simge ?></span>
                            <span><?= guvenli(tur_adi($ad)) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <a href="ilan-ver.php">
                <?= guvenli(ceviri("İlan Ver", "Post a Listing")) ?>
            </a>
            <a href="blog.php">Blog</a>
            <a href="hakkimizda.php">
                <?= guvenli(ceviri("Hakkımızda", "About Us")) ?>
            </a>
            <a href="iletisim.php">
                <?= guvenli(ceviri("İletişim", "Contact")) ?>
            </a>
            <a href="mesajlar.php" aria-current="page">
                <?= guvenli(ceviri("Mesajlarım", "Messages")) ?>
                <?php if ($okunmamis_mesaj_var): ?>
                    <span
                        class="mesaj-bildirim"
                        role="img"
                        aria-label="<?= guvenli(ceviri(
                            "Okunmamış mesajınız var",
                            "You have unread messages"
                        )) ?>"
                    ></span>
                <?php endif; ?>
            </a>
            <a href="profil.php">
                <?= guvenli(ceviri("Profilim", "My Profile")) ?>
            </a>
            <a
                href="cikis.php"
                onclick="return confirm(<?= guvenli(json_encode(
                    ceviri(
                        "Çıkış yapmak istediğinize emin misiniz?",
                        "Are you sure you want to sign out?"
                    ),
                    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                )) ?>);"
            >
                <?= guvenli(ceviri("Çıkış Yap", "Sign Out")) ?>
            </a>
        </nav>
    </div>
</header>
<main<?= $profil_gorunumu ? ' class="hesap-duzen"' : "" ?>>
<?php if ($profil_gorunumu): ?>
<?php
$hesap_sonuc = sorgula($baglanti, "SELECT * FROM uyeler WHERE id = ? LIMIT 1", "i", $ben);
$hesap_uye = mysqli_fetch_assoc($hesap_sonuc) ?? [
    "ad_soyad" => $_SESSION["uye_adsoyad"] ?? ceviri("Üye", "Member")
];
?>
<aside class="hesap-yan">
    <div class="hesap-kimlik">
        <?php if (!empty($hesap_uye["profil_fotografi"])): ?>
            <img class="hesap-avatar" src="<?= guvenli($hesap_uye["profil_fotografi"]) ?>" alt="" width="44" height="44" style="object-fit:cover">
        <?php else: ?>
            <span class="hesap-avatar" aria-hidden="true"><?= guvenli(function_exists('mb_substr') ? mb_substr($hesap_uye['ad_soyad'], 0, 1, 'UTF-8') : substr($hesap_uye['ad_soyad'], 0, 1)) ?></span>
        <?php endif; ?>
        <strong><?= guvenli($hesap_uye['ad_soyad']) ?></strong>
        <small><?= guvenli(ceviri('Hesabım', 'My Account')) ?></small>
    </div>
    <nav class="hesap-menu" aria-label="<?= guvenli(ceviri('Hesap menüsü', 'Account navigation')) ?>">
        <h2><?= guvenli(ceviri('İlan ve Başvurularım', 'Listings and Applications')) ?></h2>
        <?php foreach ([
            'ilanlar' => ['İlanlarım', 'My Listings'],
            'basvurular' => ['Sahiplenme Başvurularım', 'Adoption Applications'],
            'favoriler' => ['Favorilerim', 'My Favourites']
        ] as $anahtar => $etiket): ?>
            <a href="profil.php?bolum=<?= $anahtar ?>"><?= guvenli(ceviri($etiket[0], $etiket[1])) ?></a>
        <?php endforeach; ?>
        <a href="mesajlar.php?gorunum=profil" aria-current="page"><?= guvenli(ceviri('Mesajlarım', 'My Messages')) ?></a>
        <h2><?= guvenli(ceviri('Hesap Ayarlarım', 'Account Settings')) ?></h2>
        <a href="profil.php?bolum=hesap"><?= guvenli(ceviri('Kullanıcı Bilgilerim', 'My Details')) ?></a>
        <a href="profil.php?bolum=sifre"><?= guvenli(ceviri('Şifremi Değiştir', 'Change Password')) ?></a>
    </nav>
    <a class="hesap-yeni" href="ilan-ver.php">+ <?= guvenli(ceviri('Yeni İlan Ver', 'Post a New Listing')) ?></a>
</aside>
<div class="hesap-icerik">
<?php endif; ?>
    <div class="sayfa-ust">
        <div>
            <h1><?= guvenli(ceviri("Mesajlarım", "My Messages")) ?></h1>
            <p><?= guvenli(ceviri("Yeni bir yuvaya uzanan sohbetler burada başlar.", "Conversations about a new home start here.")) ?></p>
        </div>
        <a class="kesfet" href="ilanlar.php"><?= guvenli(ceviri("İlanları Keşfet", "Explore Listings")) ?> →</a>
    </div>
    <?php if ($hata !== ""): ?>
        <div class="hata" role="alert"><?= guvenli($hata) ?></div>
    <?php endif; ?>
    <div class="duzen<?= $sohbet ? ' sohbet-acik' : '' ?>">
        <aside class="liste" aria-label="<?= guvenli(ceviri("Sohbetler", "Conversations")) ?>">
            <h2 class="liste-baslik"><?= guvenli(ceviri("Sohbetler", "Conversations")) ?> <span id="sohbet-adet"><?= count($sohbetler) ?></span></h2>
            <div class="arama-kutu">
                <input type="search" id="sohbet-ara" aria-label="<?= guvenli(ceviri("Sohbet ara", "Search conversations")) ?>" placeholder="<?= guvenli(ceviri("Kişi veya ilan ara...", "Search person or listing...")) ?>">
            </div>
            <div id="sohbet-listesi">
                <?php if (!$sohbetler): ?>
                    <p class="bos-liste"><?= guvenli(ceviri("Henüz sohbetin yok. Bir ilanın detayından ilan sahibine mesaj gönderebilirsin.", "No conversations yet. Message an owner from a listing's details page.")) ?></p>
                <?php endif; ?>
                <?php foreach ($sohbetler as $satir): ?>
                    <?php
                    $secili = $ilan_id === (int)$satir["ilan_id"] && $kisi === (int)$satir["kisi_id"];
                    $foto = sohbet_fotografi($satir["fotograf"] ?? "");
                    ?>
                    <a class="sohbet-link <?= $secili ? "aktif" : "" ?>"
                       data-arama="<?= guvenli($satir["kisi_adi"] . " " . $satir["ilan_basligi"] . " " . $satir["hayvan_adi"]) ?>"
                       href="<?= guvenli(sohbet_adresi((int)$satir["ilan_id"], (int)$satir["kisi_id"])) ?>"
                       <?= $secili ? 'aria-current="true"' : "" ?>>
                        <span class="avatar" aria-hidden="true">
                            <?php if ($foto): ?><img src="<?= guvenli($foto) ?>" alt=""><?php else: ?><?= guvenli(mb_substr($satir["kisi_adi"], 0, 1, "UTF-8")) ?><?php endif; ?>
                        </span>
                        <span class="sohbet-ozet">
                            <span class="sohbet-kisi">
                                <strong><?= guvenli($satir["kisi_adi"]) ?></strong>
                                <time><?= guvenli(date("d.m H:i", strtotime($satir["tarih"]))) ?></time>
                            </span>
                            <small><?= guvenli($satir["ilan_basligi"] ?: $satir["hayvan_adi"]) ?></small>
                            <span class="onizleme">
                                <small><?= guvenli(((int)$satir["gonderen_id"] === $ben ? ceviri("Sen: ", "You: ") : "") . mb_strimwidth($satir["mesaj"], 0, 65, "…", "UTF-8")) ?></small>
                                <?php if ((int)$satir["okunmamis"] > 0): ?>
                                    <span class="rozet" aria-label="<?= guvenli(ceviri("Okunmamış mesaj sayısı", "Unread message count")) ?>"><?= (int)$satir["okunmamis"] ?></span>
                                <?php endif; ?>
                            </span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
            <p class="bos-liste" id="arama-bos" hidden><?= guvenli(ceviri("Eşleşen sohbet bulunamadı.", "No matching conversations.")) ?></p>
        </aside>
        <section class="sohbet" aria-label="<?= guvenli(ceviri("Mesaj alanı", "Message area")) ?>">
            <?php if ($sohbet): ?>
                <?php $foto = sohbet_fotografi($sohbet["fotograf"] ?? ""); ?>
                <a class="sohbetlere-don" href="<?= guvenli(sohbet_adresi(null, null)) ?>">
                    &larr; <?= guvenli(ceviri("Sohbetlere dön", "Back to conversations")) ?>
                </a>
                <div class="sohbet-baslik">
                    <span class="avatar" aria-hidden="true">
                        <?php if ($foto): ?><img src="<?= guvenli($foto) ?>" alt=""><?php else: ?><?= guvenli(mb_substr($sohbet["kisi_adi"], 0, 1, "UTF-8")) ?><?php endif; ?>
                    </span>
                    <div class="baslik-metin">
                        <h2><?= guvenli($sohbet["kisi_adi"]) ?></h2>
                        <small><?= guvenli($sohbet["ilan_basligi"] ?: $sohbet["hayvan_adi"]) ?></small>
                    </div>
                    <a class="ilan-link" href="ilan-detay.php?id=<?= (int)$ilan_id ?>"><?= guvenli(ceviri("İlanı Gör", "View Listing")) ?> ↗</a>
                </div>
                <div class="akis" id="mesaj-akisi" aria-label="<?= guvenli(ceviri("Sohbet mesajları", "Conversation messages")) ?>">
                    <?php if (!$mesajlar): ?>
                        <div class="bos-alan"><p><?= guvenli(ceviri("İlk adımı sen at. İlan hakkında merak ettiklerini sorabilirsin.", "Start the conversation by asking about this listing.")) ?></p></div>
                    <?php endif; ?>
                    <?php $onceki_gun = ""; ?>
                    <?php foreach ($mesajlar as $satir): ?>
                        <?php $gun = date("d.m.Y", strtotime($satir["tarih"])); $benim = (int)$satir["gonderen_id"] === $ben; ?>
                        <?php if ($gun !== $onceki_gun): ?>
                            <span class="gun"><?= guvenli($gun) ?></span>
                            <?php $onceki_gun = $gun; ?>
                        <?php endif; ?>
                        <div class="balon <?= $benim ? "ben" : "" ?>" data-id="<?= (int)$satir["id"] ?>">
                            <p><?= guvenli($satir["mesaj"]) ?></p>
                            <time><?= guvenli(date("H:i", strtotime($satir["tarih"]))) ?><?php if ($benim): ?> · <?= guvenli((int)$satir["okundu"] === 1 ? ceviri("Okundu", "Read") : ceviri("Gönderildi", "Sent")) ?><?php endif; ?></time>
                        </div>
                    <?php endforeach; ?>
                    <div id="son"></div>
                </div>
                <button class="yeni-mesaj" id="yeni-mesaj" type="button" hidden><?= guvenli(ceviri("Yeni mesajlar ↓", "New messages ↓")) ?></button>
                <?php if ($sohbet["durum"] === "Onaylandı"): ?>
                    <form id="mesaj-form" class="yaz" method="POST" action="<?= guvenli(sohbet_adresi($ilan_id, $kisi)) ?>">
                        <input type="hidden" name="token" value="<?= guvenli($_SESSION["sohbet_token"]) ?>">
                        <label for="mesaj"><?= guvenli(ceviri("Mesajınız", "Your Message")) ?></label>
                        <textarea id="mesaj" name="mesaj" maxlength="5000" rows="2" required placeholder="<?= guvenli(ceviri("Mesajını yaz...", "Write your message...")) ?>"><?= guvenli($metin) ?></textarea>
                        <div class="yaz-alt">
                            <small><?= guvenli(ceviri("Göndermek için Ctrl + Enter", "Press Ctrl + Enter to send")) ?></small>
                            <button type="submit" id="gonder-buton"><?= guvenli(ceviri("Gönder", "Send")) ?> →</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="bos"><?= guvenli(ceviri("İlan yayında olmadığı için yeni mesaj gönderilemiyor.", "New messages cannot be sent because this listing is not active.")) ?></p>
                <?php endif; ?>
            <?php else: ?>
                <div class="bos-alan">
                    <span class="bos-simge" aria-hidden="true">✉</span>
                    <h2><?= guvenli(ceviri("Bir sohbetle başlar", "It starts with a conversation")) ?></h2>
                    <p><?= guvenli(ceviri("Mesajlarını okumak için soldan bir sohbet seç. Henüz sohbetin yoksa ilanları keşfedip bir ilan sahibine mesaj gönderebilirsin.", "Select a conversation to read your messages, or explore listings to message an owner.")) ?></p>
                    <a class="kesfet" href="ilanlar.php"><?= guvenli(ceviri("İlanları Keşfet", "Explore Listings")) ?> →</a>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <p class="alt-not"><span id="yenile-durum" role="status"><?= guvenli(ceviri("Yeni mesajlar 8 saniyede bir kontrol edilir.", "New messages are checked every 8 seconds.")) ?></span> · <?= guvenli(ceviri("Seçili sohbette son 200 mesaj gösterilir.", "The latest 200 messages are shown.")) ?></p>
<?php if ($profil_gorunumu): ?>
</div>
<?php endif; ?>
</main>
<footer>
    © <?= date("Y") ?> Aris Pati Yuvası ·
    <?= guvenli(ceviri(
        "Hayvan Sahiplendirme Platformu",
        "Pet Adoption Platform"
    )) ?>
</footer>
<script>
const menuDugmesi = document.querySelector(".mesaj-menu-dugmesi");
menuDugmesi.hidden = false;
menuDugmesi.addEventListener("click", function () {
    const acik = this.getAttribute("aria-expanded") === "true";
    this.setAttribute("aria-expanded", String(!acik));
});

const akis = document.getElementById("mesaj-akisi");
const arama = document.getElementById("sohbet-ara");
const form = document.getElementById("mesaj-form");
const yeniButon = document.getElementById("yeni-mesaj");
let gonderiliyor = false;
let kontrolEdiliyor = false;
const durum = document.getElementById("yenile-durum");
const normalMetin = durum.textContent;
const hataMetin = <?= json_encode(ceviri("Bağlantı kurulamadı. Yeniden denenecek.", "Unable to connect. Retrying shortly."), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
function filtrele() {
    const metin = arama.value.trim().toLocaleLowerCase("tr-TR");
    let adet = 0;
    document.querySelectorAll(".sohbet-link").forEach(link => {
        const uygun = link.dataset.arama.toLocaleLowerCase("tr-TR").includes(metin);
        link.hidden = !uygun;
        if (uygun) adet++;
    });
    document.getElementById("arama-bos").hidden = !metin || adet > 0;
}
arama.addEventListener("input", filtrele);
if (akis) akis.scrollTop = akis.scrollHeight;
if (yeniButon) yeniButon.addEventListener("click", () => {
    akis.scrollTop = akis.scrollHeight;
    yeniButon.hidden = true;
});
if (akis) akis.addEventListener("scroll", () => {
    if (akis.scrollHeight - akis.scrollTop - akis.clientHeight < 70) yeniButon.hidden = true;
});
if (form) {
    form.addEventListener("submit", event => {
        // Language buttons preserve the draft and use the original POST flow.
        if (gonderiliyor) { event.preventDefault(); return; }
        gonderiliyor = true;
        document.getElementById("gonder-buton").disabled = true;
    });
    document.getElementById("mesaj").addEventListener("keydown", event => {
        if ((event.ctrlKey || event.metaKey) && event.key === "Enter") {
            event.preventDefault();
            form.requestSubmit(document.getElementById("gonder-buton"));
        }
    });
}
async function yenile() {
    if (document.hidden || gonderiliyor || kontrolEdiliyor) return;
    kontrolEdiliyor = true;
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 12000);
    try {
        const cevap = await fetch(window.location.href, {
            cache: "no-store", credentials: "same-origin", signal: controller.signal
        });
        if (document.hidden || gonderiliyor) return;
        if (cevap.redirected && new URL(cevap.url).pathname.endsWith("/giris.php")) {
            window.location.href = cevap.url;
            return;
        }
        if (!cevap.ok) throw new Error("HTTP");
        const belge = new DOMParser().parseFromString(await cevap.text(), "text/html");
        if (document.hidden || gonderiliyor) return;
        const yeniListe = belge.getElementById("sohbet-listesi");
        if (!yeniListe) throw new Error("Response");
        const liste = document.getElementById("sohbet-listesi");
        const konum = liste.scrollTop;
        if (liste.innerHTML !== yeniListe.innerHTML) {
            liste.innerHTML = yeniListe.innerHTML;
            filtrele();
            liste.scrollTop = konum;
        }
        document.getElementById("sohbet-adet").textContent = belge.getElementById("sohbet-adet").textContent;
        const yeniAkis = belge.getElementById("mesaj-akisi");
        if (akis && yeniAkis && akis.innerHTML !== yeniAkis.innerHTML) {
            const altta = akis.scrollHeight - akis.scrollTop - akis.clientHeight < 70;
            const eskiKonum = akis.scrollTop;
            const eskiId = akis.querySelectorAll(".balon");
            const sonId = eskiId.length ? eskiId[eskiId.length - 1].dataset.id : "0";
            akis.innerHTML = yeniAkis.innerHTML;
            if (altta) akis.scrollTop = akis.scrollHeight;
            else {
                akis.scrollTop = eskiKonum;
                const balonlar = akis.querySelectorAll(".balon");
                if (balonlar.length && balonlar[balonlar.length - 1].dataset.id !== sonId) yeniButon.hidden = false;
            }
        }
        const yeniForm = belge.getElementById("mesaj-form");
        if (form && yeniForm) form.elements.token.value = yeniForm.elements.token.value;
        if (form && !yeniForm) {
            document.getElementById("gonder-buton").disabled = true;
            document.getElementById("mesaj").disabled = true;
            durum.textContent = <?= json_encode(ceviri("İlan artık yayında değil. Yeni mesaj gönderilemiyor.", "This listing is no longer active. Messaging is disabled."), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        } else durum.textContent = normalMetin;
    } catch (hata) {
        durum.textContent = hataMetin;
    } finally {
        clearTimeout(timeout);
        kontrolEdiliyor = false;
    }
}
setInterval(yenile, 8000);
document.addEventListener("visibilitychange", () => { if (!document.hidden) yenile(); });
</script>
</body>
</html>
