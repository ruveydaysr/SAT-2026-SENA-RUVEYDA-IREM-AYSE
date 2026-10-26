<?php
session_start();

function guvenli($deger)
{
    return htmlspecialchars((string) $deger, ENT_QUOTES, "UTF-8");
}

function form_degeri($alan)
{
    return isset($_POST[$alan]) && is_string($_POST[$alan])
        ? trim($_POST[$alan])
        : "";
}

$uye_girisi = (int) ($_SESSION["uye_id"] ?? 0) > 0;
$mesaj = "";
$basarili = false;
$secilen_ozellikler = [];

$ozellik_gruplari = [
    "Karakter Özellikleri" => [
        "Oyuncu", "Sakin", "Sevecen",
        "Hareketli", "Uysal", "Çekingen"
    ],
    "Sosyal Özellikleri" => [
        "Çocuklarla anlaşır",
        "Diğer hayvanlarla anlaşır",
        "İnsanlara alışkın"
    ],
    "Bakım ve Sağlık" => [
        "Tuvalet eğitimi var",
        "Aşıları yapıldı",
        "Kısırlaştırıldı",
        "Özel bakım gerektiriyor"
    ],
    "Yaşam Ortamı" => [
        "Ev ortamına alışkın",
        "Apartman yaşamına uygun",
        "Bahçeli ev için uygun"
    ]
];

$izinli_ozellikler = [];

foreach ($ozellik_gruplari as $grup) {
    $izinli_ozellikler = array_merge($izinli_ozellikler, $grup);
}

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

$turler = [
    "Kedi", "Köpek", "Kuş", "Tavşan",
    "Balık", "Kemirgen", "Sürüngen", "Diğer"
];

if (empty($_SESSION["ilan_token"])) {
    $_SESSION["ilan_token"] = bin2hex(random_bytes(32));
}

if ($uye_girisi) {
    require_once __DIR__ . "/baglanti.php";

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $ilan_basligi = form_degeri("ilan_basligi");
        $hayvan_adi = form_degeri("hayvan_adi");
        $tur = form_degeri("tur");
        $yas = filter_input(INPUT_POST, "yas", FILTER_VALIDATE_INT);
        $cinsiyet = form_degeri("cinsiyet");
        $sehir = form_degeri("sehir");
        $ek_aciklama = form_degeri("ek_aciklama");
        $uye_id = (int) $_SESSION["uye_id"];
        $token = $_POST["token"] ?? "";

        $gelen_ozellikler = $_POST["ozellikler"] ?? [];

        if (!is_array($gelen_ozellikler)) {
            $gelen_ozellikler = [];
        }

        foreach ($izinli_ozellikler as $ozellik) {
            if (in_array($ozellik, $gelen_ozellikler, true)) {
                $secilen_ozellikler[] = $ozellik;
            }
        }

        $karakteristik_ozellikler = implode(", ", $secilen_ozellikler);
        $aciklama = $karakteristik_ozellikler;

        if ($ek_aciklama !== "") {
            $aciklama .= "\n\nEk Açıklama: " . $ek_aciklama;
        }

        $fotograf = null;
        $kaydedilen_dosya = null;

        if (
            !is_string($token)
            || !hash_equals($_SESSION["ilan_token"], $token)
        ) {
            $mesaj = "Sayfayı yenileyip tekrar deneyiniz.";
        } elseif (
            $ilan_basligi === ""
            || mb_strlen($ilan_basligi, "UTF-8") > 150
            || $hayvan_adi === ""
            || mb_strlen($hayvan_adi, "UTF-8") > 50
            || !in_array($tur, $turler, true)
            || $yas === false
            || $yas === null
            || $yas < 0
            || !in_array($cinsiyet, ["Dişi", "Erkek"], true)
            || !in_array($sehir, $sehirler, true)
            || empty($secilen_ozellikler)
            || mb_strlen($ek_aciklama, "UTF-8") > 500
        ) {
            $mesaj = "Lütfen tüm alanları doğru doldurunuz ve en az bir özellik seçiniz.";
        } else {
            $dosya = $_FILES["fotograf"] ?? null;

            if ($dosya !== null) {
                if (
                    !is_array($dosya)
                    || !isset($dosya["error"])
                    || !is_int($dosya["error"])
                ) {
                    $mesaj = "Geçersiz fotoğraf yüklemesi.";
                } elseif ($dosya["error"] !== UPLOAD_ERR_NO_FILE) {
                    if ($dosya["error"] !== UPLOAD_ERR_OK) {
                        $mesaj = "Fotoğraf yüklenirken hata oluştu.";
                    } elseif (
                        !isset($dosya["size"], $dosya["tmp_name"])
                        || !is_numeric($dosya["size"])
                        || !is_string($dosya["tmp_name"])
                    ) {
                        $mesaj = "Geçersiz fotoğraf yüklemesi.";
                    } elseif ($dosya["size"] > 5 * 1024 * 1024) {
                        $mesaj = "Fotoğraf en fazla 5 MB olabilir.";
                    } elseif (!is_uploaded_file($dosya["tmp_name"])) {
                        $mesaj = "Geçersiz fotoğraf yüklemesi.";
                    } else {
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mime = $finfo->file($dosya["tmp_name"]);

                        $izin_verilen = [
                            "image/jpeg" => "jpg",
                            "image/png" => "png",
                            "image/webp" => "webp"
                        ];

                        if (
                            !isset($izin_verilen[$mime])
                            || @getimagesize($dosya["tmp_name"]) === false
                        ) {
                            $mesaj = "Yalnızca JPG, PNG veya WEBP fotoğraf yükleyebilirsiniz.";
                        } else {
                            $klasor = __DIR__ . "/uploads";

                            if (!is_dir($klasor) || !is_writable($klasor)) {
                                $mesaj = "uploads klasörü bulunamadı veya yazılabilir değil.";
                            } else {
                                $dosya_adi = bin2hex(random_bytes(16))
                                    . "." . $izin_verilen[$mime];

                                $hedef = $klasor . "/" . $dosya_adi;

                                if (move_uploaded_file($dosya["tmp_name"], $hedef)) {
                                    $kaydedilen_dosya = $hedef;
                                    $fotograf = "uploads/" . $dosya_adi;
                                } else {
                                    $mesaj = "Fotoğraf kaydedilemedi.";
                                }
                            }
                        }
                    }
                }
            }

            if ($mesaj === "") {
                $kayit_basarili = false;
                $sorgu = null;

                try {
                    $sql = "INSERT INTO ilanlar
                            (
                                ilan_basligi, hayvan_adi, tur, yas,
                                cinsiyet, sehir, aciklama,
                                karakteristik_ozellikler,
                                uye_id, durum, fotograf
                            )
                            VALUES (
                                ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Bekliyor', ?
                            )";

                    $sorgu = mysqli_prepare($baglanti, $sql);

                    if ($sorgu) {
                        mysqli_stmt_bind_param(
                            $sorgu,
                            "sssissssis",
                            $ilan_basligi,
                            $hayvan_adi,
                            $tur,
                            $yas,
                            $cinsiyet,
                            $sehir,
                            $aciklama,
                            $karakteristik_ozellikler,
                            $uye_id,
                            $fotograf
                        );

                        $kayit_basarili = mysqli_stmt_execute($sorgu);
                    }
                } catch (mysqli_sql_exception $e) {
                    $kayit_basarili = false;
                } finally {
                    if ($sorgu instanceof mysqli_stmt) {
                        mysqli_stmt_close($sorgu);
                    }
                }

                if ($kayit_basarili) {
                    $basarili = true;
                    $mesaj = "İlanınız oluşturuldu. Yönetici onayından sonra yayınlanacaktır.";
                    $_POST = [];
                    $secilen_ozellikler = [];
                    $_SESSION["ilan_token"] = bin2hex(random_bytes(32));
                } else {
                    $mesaj = "İlan kaydedilemedi. Lütfen tekrar deneyiniz.";

                    if ($kaydedilen_dosya !== null) {
                        @unlink($kaydedilen_dosya);
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlan Ver | Aris Pati Yuvası</title>

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

        input,
        select,
        textarea,
        button {
            font: inherit;
        }

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
            color: #fff;
            font-size: 14px;
            line-height: 20px;
            text-decoration: none;
            white-space: nowrap;
            transition: background-color .15s, color .15s;
        }

        .ilan-menu a:hover,
        .ilan-menu a:focus-visible {
            background: #284b32;
            color: #fff;
        }

        .ilan-menu a[aria-current="page"] {
            background: #eee5d3;
            color: #284b32;
            font-weight: bold;
        }

        .ilan-icerik {
            width: min(850px, 92%);
            margin: 28px auto 35px;
        }

        .ilan-tanitim {
            margin-bottom: 22px;
        }

        .ilan-etiket {
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #727b69;
        }

        .ilan-tanitim h1 {
            margin: 12px 0;
            font-size: clamp(25px, 4vw, 33px);
            line-height: 1.3;
        }

        .ilan-tanitim h1 span {
            color: #62824f;
        }

        .ilan-tanitim p {
            margin: 0;
            color: #727b69;
            font-size: 14px;
            line-height: 1.7;
        }

        .ilan-kart,
        .giris-gerekli-alani {
            padding: 25px;
            background: #fffdf8;
            border: 1px solid #ded6c6;
            border-radius: 14px;
        }

        .ilan-kart-baslik {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding-bottom: 18px;
            margin-bottom: 22px;
            border-bottom: 1px solid #e8e1d3;
        }

        .ilan-kart-baslik h2 {
            margin: 0 0 8px;
            font-size: 21px;
        }

        .ilan-kart-baslik p {
            margin: 0;
            color: #727b69;
            font-size: 13px;
            line-height: 1.6;
        }

        .ilan-zorunlu {
            color: #727b69;
            font-size: 12px;
        }

        .ilan-mesaj {
            margin-bottom: 20px;
            padding: 13px 15px;
            border: 1px solid #e1bdb5;
            border-radius: 8px;
            background: #fff0ec;
            color: #8c3931;
            font-size: 14px;
            line-height: 1.6;
        }

        .ilan-mesaj.basarili {
            border-color: #c8d9bc;
            background: #eaf2e4;
            color: #35583b;
        }

        .ilan-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .ilan-grup {
            min-width: 0;
        }

        .ilan-tam {
            grid-column: 1 / -1;
        }

        .ilan-grup > label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: bold;
        }

        .ilan-grup > input,
        .ilan-grup > select,
        .ilan-grup > textarea {
            display: block;
            width: 100%;
            min-height: 43px;
            padding: 11px 12px;
            border: 1px solid #d6d0c2;
            border-radius: 8px;
            background: #fff;
            color: #284b32;
            font-size: 14px;
        }

        .ilan-grup > textarea {
            min-height: 100px;
            resize: vertical;
            line-height: 1.6;
        }

        .ilan-grup > input:focus,
        .ilan-grup > select:focus,
        .ilan-grup > textarea:focus {
            border-color: #426b46;
            outline: 2px solid #d5e2cd;
            outline-offset: 1px;
        }

        .ilan-grup > input[type="file"] {
            padding: 9px;
            font-size: 13px;
        }

        input[type="file"]::file-selector-button {
            margin-right: 10px;
            padding: 7px 10px;
            border: 0;
            border-radius: 5px;
            background: #e6eddf;
            color: #284b32;
            cursor: pointer;
        }

        .ilan-grup small,
        .ozellik-alani > p {
            display: block;
            margin: 7px 0 0;
            color: #727b69;
            font-size: 12px;
            line-height: 1.6;
        }

        .ozellik-alani {
            padding: 18px;
            border: 1px solid #e5dece;
            border-radius: 10px;
            background: #f8f5ee;
        }

        .ozellik-grubu {
            margin-top: 18px;
        }

        .ozellik-grubu h3 {
            margin: 0 0 12px;
            font-size: 13px;
        }

        .ozellik-listesi {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px 16px;
        }

        .ozellik-listesi label {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 13px;
            line-height: 1.5;
            cursor: pointer;
        }

        .ozellik-listesi input {
            width: 16px;
            height: 16px;
            margin: 2px 0 0;
            flex-shrink: 0;
            accent-color: #426b46;
        }

        .ilan-form-alt {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e8e1d3;
        }

        .ilan-form-alt p {
            flex: 1 1 250px;
            margin: 0;
            color: #727b69;
            font-size: 12px;
            line-height: 1.6;
        }

        .ilan-gonder,
        .giris-gerekli-buton {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 43px;
            padding: 12px 20px;
            border: 0;
            border-radius: 8px;
            background: #426b46;
            color: #fff;
            font-size: 14px;
            font-weight: bold;
            line-height: 1.4;
            text-decoration: none;
            cursor: pointer;
        }

        .ilan-gonder:hover,
        .giris-gerekli-buton:hover {
            background: #284b32;
        }

        .ilan-gonder:focus-visible,
        .giris-gerekli-buton:focus-visible {
            outline: 3px solid #9eb995;
            outline-offset: 3px;
        }

        .ilan-alt-yazi {
            margin: 20px 0 0;
            color: #727b69;
            font-size: 13px;
            text-align: center;
            line-height: 1.7;
        }

        .giris-gerekli-alani {
            width: min(650px, 92%);
            margin: 45px auto;
            padding: 35px 25px;
            text-align: center;
        }

        .giris-gerekli-alani img {
            width: 75px;
            height: 75px;
            object-fit: contain;
        }

        .giris-gerekli-alani h1 {
            margin: 20px 0 12px;
            font-size: clamp(23px, 4vw, 28px);
            line-height: 1.4;
        }

        .giris-gerekli-alani p {
            margin: 0 0 22px;
            color: #727b69;
            font-size: 14px;
            line-height: 1.7;
        }

        .ilan-footer {
            padding: 20px;
            color: #727b69;
            font-size: 12px;
            line-height: 1.6;
            text-align: center;
        }

        @media (max-width: 1100px) {
            .ilan-menu {
                width: 100%;
                justify-content: flex-end;
            }
        }

        @media (max-width: 700px) {
            .ilan-ust {
                padding: 15px 18px;
                gap: 16px;
            }

            .ilan-menu a {
                padding: 8px;
                font-size: 13px;
            }

            .ilan-kart {
                padding: 20px;
            }

            .ozellik-listesi {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 500px) {
            .ilan-form-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .ilan-gonder {
                width: 100%;
            }

            .ozellik-alani {
                padding: 14px;
            }
        }

        @media (max-width: 380px) {
            .ozellik-listesi {
                grid-template-columns: minmax(0, 1fr);
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

<?php if (!$uye_girisi): ?>
    <main>
        <section class="giris-gerekli-alani">
            <img src="images/aris-logoo.png" alt="Aris Pati Yuvası logosu">

            <h1>İlan Vermek İçin Giriş Yapmalısınız</h1>

            <p>
                Bir can dostuna yuva bulmak için önce hesabınıza giriş yapın.
            </p>

            <a href="giris.php" class="giris-gerekli-buton">Giriş Yap</a>
        </section>
    </main>
<?php else: ?>
    <main class="ilan-icerik">
        <section class="ilan-tanitim">
            <span class="ilan-etiket">ARİS PATİ YUVASI · YENİ İLAN</span>

            <h1>Ona yeni bir <span>yuva</span> bulalım.</h1>

            <p>
                Dostunuzun bilgilerini paylaşın ve onun sevgi dolu
                bir aileyle buluşmasına yardımcı olun.
            </p>
        </section>

        <section class="ilan-kart">
            <div class="ilan-kart-baslik">
                <div>
                    <h2>Hayvan Bilgileri</h2>
                    <p>İlan oluşturmak için aşağıdaki alanları doldurun.</p>
                </div>

                <span class="ilan-zorunlu">* Zorunlu alanlar</span>
            </div>

            <?php if ($mesaj !== ""): ?>
                <div
                    class="ilan-mesaj<?= $basarili ? ' basarili' : '' ?>"
                    role="status"
                >
                    <?= guvenli($mesaj) ?>
                </div>
            <?php endif; ?>

            <form
                class="ilan-form"
                action="ilan-ver.php"
                method="post"
                enctype="multipart/form-data"
            >
                <input
                    type="hidden"
                    name="token"
                    value="<?= guvenli($_SESSION["ilan_token"]) ?>"
                >

                <div class="ilan-form-grid">
                    <div class="ilan-grup ilan-tam">
                        <label for="ilan_basligi">İlan Başlığı *</label>
                        <input
                            type="text"
                            id="ilan_basligi"
                            name="ilan_basligi"
                            placeholder="Örn. Minik dostumuza sıcak yuva aranıyor"
                            maxlength="150"
                            value="<?= guvenli(form_degeri("ilan_basligi")) ?>"
                            required
                        >
                    </div>

                    <div class="ilan-grup">
                        <label for="hayvan_adi">Hayvanın Adı *</label>
                        <input
                            type="text"
                            id="hayvan_adi"
                            name="hayvan_adi"
                            placeholder="Örn. Boncuk"
                            maxlength="50"
                            value="<?= guvenli(form_degeri("hayvan_adi")) ?>"
                            required
                        >
                    </div>

                    <div class="ilan-grup">
                        <label for="tur">Hayvan Türü *</label>
                        <select id="tur" name="tur" required>
                            <option value="">Tür seçiniz</option>

                            <?php foreach ($turler as $secenek): ?>
                                <option
                                    value="<?= guvenli($secenek) ?>"
                                    <?= form_degeri("tur") === $secenek ? "selected" : "" ?>
                                ><?= guvenli($secenek) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="ilan-grup">
                        <label for="yas">Hayvanın Yaşı *</label>
                        <input
                            type="number"
                            id="yas"
                            name="yas"
                            min="0"
                            step="1"
                            placeholder="Yaşını giriniz"
                            value="<?= guvenli(form_degeri("yas")) ?>"
                            required
                        >
                    </div>

                    <div class="ilan-grup">
                        <label for="cinsiyet">Cinsiyet *</label>
                        <select id="cinsiyet" name="cinsiyet" required>
                            <option value="">Cinsiyet seçiniz</option>

                            <?php foreach (["Dişi", "Erkek"] as $secenek): ?>
                                <option
                                    value="<?= guvenli($secenek) ?>"
                                    <?= form_degeri("cinsiyet") === $secenek ? "selected" : "" ?>
                                ><?= guvenli($secenek) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="ilan-grup ilan-tam">
                        <label for="sehir">Bulunduğu Şehir *</label>
                        <select id="sehir" name="sehir" required>
                            <option value="">Şehir seçiniz</option>

                            <?php foreach ($sehirler as $il): ?>
                                <option
                                    value="<?= guvenli($il) ?>"
                                    <?= form_degeri("sehir") === $il ? "selected" : "" ?>
                                ><?= guvenli($il) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div
                        class="ilan-grup ilan-tam ozellik-alani"
                        role="group"
                        aria-labelledby="ozellik-baslik"
                        aria-describedby="ozellik-yardim"
                    >
                        <label id="ozellik-baslik">Hayvanın Özellikleri *</label>
                        <p id="ozellik-yardim">
                            Hayvanınıza uygun en az bir özellik işaretleyiniz.
                        </p>

                        <?php foreach ($ozellik_gruplari as $baslik => $ozellikler): ?>
                            <div class="ozellik-grubu">
                                <h3><?= guvenli($baslik) ?></h3>

                                <div class="ozellik-listesi">
                                    <?php foreach ($ozellikler as $ozellik): ?>
                                        <label>
                                            <input
                                                type="checkbox"
                                                name="ozellikler[]"
                                                value="<?= guvenli($ozellik) ?>"
                                                <?= in_array($ozellik, $secilen_ozellikler, true) ? "checked" : "" ?>
                                            >
                                            <span><?= guvenli($ozellik) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="ilan-grup ilan-tam">
                        <label for="ek_aciklama">
                            Ek Açıklama (İsteğe Bağlı)
                        </label>
                        <textarea
                            id="ek_aciklama"
                            name="ek_aciklama"
                            rows="3"
                            maxlength="500"
                            placeholder="Hayvanınız hakkında eklemek istediğiniz bilgileri yazabilirsiniz."
                        ><?= guvenli(form_degeri("ek_aciklama")) ?></textarea>
                    </div>

                    <div class="ilan-grup ilan-tam">
                        <label for="fotograf">Hayvan Fotoğrafı</label>
                        <input
                            type="file"
                            id="fotograf"
                            name="fotograf"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            aria-describedby="foto-yardim"
                        >
                        <small id="foto-yardim">
                            JPG, PNG veya WEBP formatı · En fazla 5 MB
                        </small>
                    </div>
                </div>

                <div class="ilan-form-alt">
                    <p>
                        Bilgileri kontrol ettikten sonra ilanınızı gönderebilirsiniz.
                    </p>

                    <button type="submit" class="ilan-gonder">
                        🐾 İlanı Gönder →
                    </button>
                </div>
            </form>
        </section>

        <p class="ilan-alt-yazi">
            ♥ Her can dostu sevgi dolu bir yuvayı hak eder.
        </p>
    </main>
<?php endif; ?>

<footer class="ilan-footer">
    © 2026 Aris Pati Yuvası · Hayvan Sahiplendirme Platformu
</footer>

<script>
const turSecimi = document.getElementById("tur");
const aciklama = document.getElementById("ek_aciklama");

if (turSecimi && aciklama) {
    const ornekler = {
        "Kedi": "Örneğin: Kedim alışınca çok sevecendir ve oyun oynamayı sever.",
        "Köpek": "Örneğin: Köpeğim yürüyüş yapmayı çok sever ve temel komutları bilir.",
        "Kuş": "Örneğin: Kuşum insan sesine alışkındır ve zaman zaman sesleri taklit eder.",
        "Tavşan": "Örneğin: Tavşanım sakin bir ortamda yaşamayı sever.",
        "Balık": "Örneğin: Balığım diğer balıklarla uyumludur.",
        "Kemirgen": "Örneğin: Küçük dostum hareketli ve meraklıdır.",
        "Sürüngen": "Örneğin: Sürüngenimin özel sıcaklık ve bakım ihtiyaçları vardır.",
        "Diğer": "Hayvanınızın alışkanlıklarını ve bakım ihtiyaçlarını yazabilirsiniz."
    };

    function ornegiGuncelle() {
        aciklama.placeholder = ornekler[turSecimi.value]
            || "Hayvanınız hakkında eklemek istediğiniz bilgileri yazabilirsiniz.";
    }

    turSecimi.addEventListener("change", ornegiGuncelle);
    ornegiGuncelle();
}
</script>

</body>
</html>