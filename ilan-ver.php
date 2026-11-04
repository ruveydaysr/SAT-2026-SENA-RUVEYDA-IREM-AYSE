<?php
session_start();
require_once __DIR__ . "/site-ust-ayar.php";

/* Dil tercihi */
$istenen_dil = $_SERVER["REQUEST_METHOD"] === "POST"
    ? ($_POST["dil_secimi"] ?? null)
    : ($_GET["lang"] ?? null);

$dil_degisti = is_string($istenen_dil)
    && in_array($istenen_dil, ["tr", "en"], true);

if ($dil_degisti) {
    $_SESSION["dil"] = $istenen_dil;

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        header("Location: ilan-ver.php");
        exit();
    }
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

function form_degeri($alan)
{
    return isset($_POST[$alan]) && is_string($_POST[$alan])
        ? trim($_POST[$alan])
        : "";
}

function tur_adi($tur)
{
    global $dil;

    $adlar = [
        "Kedi" => "Cat",
        "Köpek" => "Dog",
        "Kuş" => "Bird",
        "Tavşan" => "Rabbit",
        "Balık" => "Fish",
        "Kemirgen" => "Rodent",
        "Sürüngen" => "Reptile",
        "Diğer" => "Other"
    ];

    return $dil === "en" ? ($adlar[$tur] ?? $tur) : $tur;
}

function ozellik_adi($deger)
{
    global $dil;

    $adlar = [
        "Karakter Özellikleri" => "Personality",
        "Sosyal Özellikleri" => "Social Traits",
        "Bakım ve Sağlık" => "Care and Health",
        "Yaşam Ortamı" => "Living Environment",
        "Oyuncu" => "Playful",
        "Sakin" => "Calm",
        "Sevecen" => "Affectionate",
        "Hareketli" => "Energetic",
        "Uysal" => "Gentle",
        "Çekingen" => "Shy",
        "Çocuklarla anlaşır" => "Gets along with children",
        "Diğer hayvanlarla anlaşır" => "Gets along with other animals",
        "İnsanlara alışkın" => "Used to people",
        "Tuvalet eğitimi var" => "Toilet trained",
        "Aşıları yapıldı" => "Vaccinated",
        "Kısırlaştırıldı" => "Spayed / neutered",
        "Özel bakım gerektiriyor" => "Needs special care",
        "Ev ortamına alışkın" => "Used to living indoors",
        "Apartman yaşamına uygun" => "Suitable for apartment living",
        "Bahçeli ev için uygun" => "Suitable for a home with a garden"
    ];

    return $dil === "en" ? ($adlar[$deger] ?? $deger) : $deger;
}

$uye_girisi = (int)($_SESSION["uye_id"] ?? 0) > 0;
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

$gelen_ozellikler = $_POST["ozellikler"] ?? [];

if (!is_array($gelen_ozellikler)) {
    $gelen_ozellikler = [];
}

foreach ($izinli_ozellikler as $ozellik) {
    if (in_array($ozellik, $gelen_ozellikler, true)) {
        $secilen_ozellikler[] = $ozellik;
    }
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

if (
    !isset($_SESSION["ilan_token"])
    || !is_string($_SESSION["ilan_token"])
    || $_SESSION["ilan_token"] === ""
) {
    $_SESSION["ilan_token"] = bin2hex(random_bytes(32));
}

if ($uye_girisi) {
    require_once __DIR__ . "/baglanti.php";

    /*
     * Dil düğmesine basıldığında form bilgileri korunur.
     * İlan kaydı ve fotoğraf yüklemesi yapılmaz.
     */
    if (
        $_SERVER["REQUEST_METHOD"] === "POST"
        && !$dil_degisti
    ) {
        $ilan_basligi = form_degeri("ilan_basligi");
        $hayvan_adi = form_degeri("hayvan_adi");
        $tur = form_degeri("tur");
        $yas = filter_input(INPUT_POST, "yas", FILTER_VALIDATE_INT);
        $cinsiyet = form_degeri("cinsiyet");
        $sehir = form_degeri("sehir");
        $ek_aciklama = form_degeri("ek_aciklama");
        $uye_id = (int)$_SESSION["uye_id"];
        $token = $_POST["token"] ?? "";

        $karakteristik_ozellikler = implode(", ", $secilen_ozellikler);

        /*
         * Mevcut veritabanı düzenini korumak için
         * özellikler Türkçe değerleriyle saklanır.
         */
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
            $mesaj = ceviri(
                "Sayfayı yenileyip tekrar deneyiniz.",
                "Please refresh the page and try again."
            );
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
            $mesaj = ceviri(
                "Lütfen tüm alanları doğru doldurunuz ve en az bir özellik seçiniz.",
                "Please complete all fields correctly and select at least one trait."
            );
        } else {
            $dosya = $_FILES["fotograf"] ?? null;

            if ($dosya !== null) {
                if (
                    !is_array($dosya)
                    || !isset($dosya["error"])
                    || !is_int($dosya["error"])
                ) {
                    $mesaj = ceviri(
                        "Geçersiz fotoğraf yüklemesi.",
                        "Invalid photo upload."
                    );
                } elseif ($dosya["error"] !== UPLOAD_ERR_NO_FILE) {
                    if ($dosya["error"] !== UPLOAD_ERR_OK) {
                        $mesaj = ceviri(
                            "Fotoğraf yüklenirken hata oluştu.",
                            "An error occurred while uploading the photo."
                        );
                    } elseif (
                        !isset($dosya["size"], $dosya["tmp_name"])
                        || !is_numeric($dosya["size"])
                        || !is_string($dosya["tmp_name"])
                    ) {
                        $mesaj = ceviri(
                            "Geçersiz fotoğraf yüklemesi.",
                            "Invalid photo upload."
                        );
                    } elseif ($dosya["size"] > 5 * 1024 * 1024) {
                        $mesaj = ceviri(
                            "Fotoğraf en fazla 5 MB olabilir.",
                            "The photo must not exceed 5 MB."
                        );
                    } elseif (!is_uploaded_file($dosya["tmp_name"])) {
                        $mesaj = ceviri(
                            "Geçersiz fotoğraf yüklemesi.",
                            "Invalid photo upload."
                        );
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
                            $mesaj = ceviri(
                                "Yalnızca JPG, PNG veya WEBP fotoğraf yükleyebilirsiniz.",
                                "Only JPG, PNG or WEBP photos are allowed."
                            );
                        } else {
                            $klasor = __DIR__ . "/uploads";

                            if (!is_dir($klasor) || !is_writable($klasor)) {
                                $mesaj = ceviri(
                                    "uploads klasörü bulunamadı veya yazılabilir değil.",
                                    "The uploads folder is missing or is not writable."
                                );
                            } else {
                                $dosya_adi = bin2hex(random_bytes(16))
                                    . "." . $izin_verilen[$mime];

                                $hedef = $klasor . "/" . $dosya_adi;

                                if (
                                    move_uploaded_file(
                                        $dosya["tmp_name"],
                                        $hedef
                                    )
                                ) {
                                    $kaydedilen_dosya = $hedef;
                                    $fotograf = "uploads/" . $dosya_adi;
                                } else {
                                    $mesaj = ceviri(
                                        "Fotoğraf kaydedilemedi.",
                                        "The photo could not be saved."
                                    );
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

                    $mesaj = ceviri(
                        "İlanınız oluşturuldu. Yönetici onayından sonra yayınlanacaktır.",
                        "Your listing has been created. It will be published after administrator approval."
                    );

                    $_POST = [];
                    $secilen_ozellikler = [];

                    $_SESSION["ilan_token"] = bin2hex(random_bytes(32));
                } else {
                    $mesaj = ceviri(
                        "İlan kaydedilemedi. Lütfen tekrar deneyiniz.",
                        "The listing could not be saved. Please try again."
                    );

                    if ($kaydedilen_dosya !== null) {
                        @unlink($kaydedilen_dosya);
                    }
                }
            }
        }
    }
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

$ornekler = [
    "Kedi" => ceviri(
        "Örneğin: Kedim alışınca çok sevecendir ve oyun oynamayı sever.",
        "For example: My cat is affectionate once settled in and enjoys playing."
    ),
    "Köpek" => ceviri(
        "Örneğin: Köpeğim yürüyüş yapmayı çok sever ve temel komutları bilir.",
        "For example: My dog enjoys walks and knows basic commands."
    ),
    "Kuş" => ceviri(
        "Örneğin: Kuşum insan sesine alışkındır ve zaman zaman sesleri taklit eder.",
        "For example: My bird is used to human voices and sometimes imitates sounds."
    ),
    "Tavşan" => ceviri(
        "Örneğin: Tavşanım sakin bir ortamda yaşamayı sever.",
        "For example: My rabbit enjoys a quiet living environment."
    ),
    "Balık" => ceviri(
        "Örneğin: Balığım diğer balıklarla uyumludur.",
        "For example: My fish gets along with other fish."
    ),
    "Kemirgen" => ceviri(
        "Örneğin: Küçük dostum hareketli ve meraklıdır.",
        "For example: My little companion is active and curious."
    ),
    "Sürüngen" => ceviri(
        "Örneğin: Sürüngenimin özel sıcaklık ve bakım ihtiyaçları vardır.",
        "For example: My reptile has specific temperature and care requirements."
    ),
    "Diğer" => ceviri(
        "Hayvanınızın alışkanlıklarını ve bakım ihtiyaçlarını yazabilirsiniz.",
        "Describe your animal's habits and care needs."
    )
];

$varsayilan_ornek = ceviri(
    "Hayvanınız hakkında eklemek istediğiniz bilgileri yazabilirsiniz.",
    "Write any additional information about your animal."
);

$dosya_sec_yazisi = ceviri("Fotoğraf Seç", "Choose Photo");
$dosya_yok_yazisi = ceviri("Dosya seçilmedi", "No file selected");
?>

<!DOCTYPE html>
<html lang="<?= guvenli($dil) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= guvenli(ceviri(
        "İlan Ver | Aris Pati Yuvası",
        "Post a Listing | Aris Pati Yuvası"
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

        input, select, textarea, button { font: inherit; }

        a:focus-visible, button:focus-visible {
            outline: 3px solid #9eb995;
            outline-offset: 3px;
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
            margin: 0;
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
            padding: 5px 9px;
            border: 0;
            border-radius: 5px;
            background: transparent;
            color: white;
            font-size: 12px;
            font-weight: bold;
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
            gap: 6px;
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

        .ilan-icerik {
            width: min(850px, 92%);
            margin: 28px auto 35px;
        }

        .ilan-tanitim { margin-bottom: 22px; }

        .ilan-etiket {
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #727b69;
        }

        .ilan-tanitim h1 {
            margin: 12px 0;
            font-size: clamp(25px,4vw,33px);
            line-height: 1.3;
        }

        .ilan-tanitim h1 span { color: #62824f; }

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
            grid-template-columns: repeat(2,minmax(0,1fr));
            gap: 18px;
        }

        .ilan-grup { min-width: 0; }
        .ilan-tam { grid-column: 1 / -1; }

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

        .ozellik-grubu { margin-top: 18px; }

        .ozellik-grubu h3 {
            margin: 0 0 12px;
            font-size: 13px;
        }

        .ozellik-listesi {
            display: grid;
            grid-template-columns: repeat(3,minmax(0,1fr));
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

        .ilan-alt-yazi {
            margin: 20px 0 0;
            color: #727b69;
            font-size: 13px;
            text-align: center;
            line-height: 1.7;
        }

        .giris-gerekli-alani {
            width: min(650px,92%);
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
            font-size: clamp(23px,4vw,28px);
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
            .ust-sag { flex-basis: 100%; }
            .ilan-menu { width: 100%; }
        }

        @media (max-width: 700px) {
            .ilan-ust { padding: 15px 18px; gap: 16px; }

            .ilan-menu > a,
            .kategori-baslik {
                padding: 8px;
                font-size: 13px;
            }

            .ilan-kart { padding: 20px; }

            .ozellik-listesi {
                grid-template-columns: repeat(2,minmax(0,1fr));
            }
        }

        @media (max-width: 500px) {
            .ilan-form-grid { grid-template-columns: minmax(0,1fr); }
            .ilan-gonder { width: 100%; }
            .ozellik-alani { padding: 14px; }
        }

        @media (max-width: 380px) {
            .ozellik-listesi { grid-template-columns: minmax(0,1fr); }
        }
    </style>
<link rel="stylesheet" href="site-ust.css?v=20261102-1">
<link rel="stylesheet" href="site-alt.css?v=20261104-1">
</head>

<body>

<?php require __DIR__ . "/site-ust.php"; ?>

<?php if (!$uye_girisi): ?>

    <main>
        <section class="giris-gerekli-alani">
            <img
                src="images/aris-logoo.png"
                alt="<?= guvenli(ceviri(
                    "Aris Pati Yuvası logosu",
                    "Aris Pati Yuvası logo"
                )) ?>"
            >

            <h1>
                <?= guvenli(ceviri(
                    "İlan Vermek İçin Giriş Yapmalısınız",
                    "Sign In to Post a Listing"
                )) ?>
            </h1>

            <p>
                <?= guvenli(ceviri(
                    "Bir can dostuna yuva bulmak için önce hesabınıza giriş yapın.",
                    "Sign in to your account to help a companion find a home."
                )) ?>
            </p>

            <a href="giris.php" class="giris-gerekli-buton">
                <?= guvenli(ceviri("Giriş Yap", "Sign In")) ?>
            </a>
        </section>
    </main>

<?php else: ?>

    <main class="ilan-icerik">
        <section class="ilan-tanitim">
            <span class="ilan-etiket">
                <?= guvenli(ceviri(
                    "ARİS PATİ YUVASI · YENİ İLAN",
                    "ARİS PATİ YUVASI · NEW LISTING"
                )) ?>
            </span>

            <h1>
                <?php if ($dil === "en"): ?>
                    Let's find them a new <span>home</span>.
                <?php else: ?>
                    Ona yeni bir <span>yuva</span> bulalım.
                <?php endif; ?>
            </h1>

            <p>
                <?= guvenli(ceviri(
                    "Dostunuzun bilgilerini paylaşın ve onun sevgi dolu bir aileyle buluşmasına yardımcı olun.",
                    "Share your companion's information and help them meet a loving family."
                )) ?>
            </p>
        </section>

        <section class="ilan-kart">
            <div class="ilan-kart-baslik">
                <div>
                    <h2>
                        <?= guvenli(ceviri(
                            "Hayvan Bilgileri",
                            "Animal Information"
                        )) ?>
                    </h2>

                    <p>
                        <?= guvenli(ceviri(
                            "İlan oluşturmak için aşağıdaki alanları doldurun.",
                            "Complete the fields below to create a listing."
                        )) ?>
                    </p>
                </div>

                <span class="ilan-zorunlu">
                    <?= guvenli(ceviri(
                        "* Zorunlu alanlar",
                        "* Required fields"
                    )) ?>
                </span>
            </div>

            <?php if ($mesaj !== ""): ?>
                <div
                    class="ilan-mesaj<?= $basarili ? " basarili" : "" ?>"
                    role="status"
                >
                    <?= guvenli($mesaj) ?>
                </div>
            <?php endif; ?>

            <?php if ($dil_degisti && $_SERVER["REQUEST_METHOD"] === "POST"): ?>
                <div class="ilan-mesaj basarili" role="status">
                    <?= guvenli(ceviri(
                        "Dil değiştirildi. Form bilgileriniz korundu. Fotoğraf seçtiyseniz yeniden seçiniz.",
                        "Language changed. Your form details have been kept. If you selected a photo, please select it again."
                    )) ?>
                </div>
            <?php endif; ?>

            <form
                id="ilan-form"
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
                        <label for="ilan_basligi">
                            <?= guvenli(ceviri(
                                "İlan Başlığı *",
                                "Listing Title *"
                            )) ?>
                        </label>

                        <input
                            type="text"
                            id="ilan_basligi"
                            name="ilan_basligi"
                            placeholder="<?= guvenli(ceviri(
                                "Örn. Minik dostumuza sıcak yuva aranıyor",
                                "e.g. A loving home wanted for our little companion"
                            )) ?>"
                            maxlength="150"
                            value="<?= guvenli(form_degeri("ilan_basligi")) ?>"
                            required
                        >
                    </div>

                    <div class="ilan-grup">
                        <label for="hayvan_adi">
                            <?= guvenli(ceviri(
                                "Hayvanın Adı *",
                                "Animal's Name *"
                            )) ?>
                        </label>

                        <input
                            type="text"
                            id="hayvan_adi"
                            name="hayvan_adi"
                            placeholder="<?= guvenli(ceviri(
                                "Örn. Boncuk",
                                "e.g. Boncuk"
                            )) ?>"
                            maxlength="50"
                            value="<?= guvenli(form_degeri("hayvan_adi")) ?>"
                            required
                        >
                    </div>

                    <div class="ilan-grup">
                        <label for="tur">
                            <?= guvenli(ceviri(
                                "Hayvan Türü *",
                                "Animal Type *"
                            )) ?>
                        </label>

                        <select id="tur" name="tur" required>
                            <option value="">
                                <?= guvenli(ceviri(
                                    "Tür seçiniz",
                                    "Select an animal type"
                                )) ?>
                            </option>

                            <?php foreach ($turler as $secenek): ?>
                                <option
                                    value="<?= guvenli($secenek) ?>"
                                    <?= form_degeri("tur") === $secenek ? "selected" : "" ?>
                                ><?= guvenli(tur_adi($secenek)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="ilan-grup">
                        <label for="yas">
                            <?= guvenli(ceviri(
                                "Hayvanın Yaşı *",
                                "Animal's Age *"
                            )) ?>
                        </label>

                        <input
                            type="number"
                            id="yas"
                            name="yas"
                            min="0"
                            step="1"
                            placeholder="<?= guvenli(ceviri(
                                "Yaşını giriniz",
                                "Enter age in years"
                            )) ?>"
                            value="<?= guvenli(form_degeri("yas")) ?>"
                            aria-describedby="yas-yardim"
                            required
                        >

                        <small id="yas-yardim">
                            <?= guvenli(ceviri(
                                "Yaşı yıl olarak yazınız. Bir yaşından küçükse 0 giriniz.",
                                "Enter age in years. Enter 0 if under one year old."
                            )) ?>
                        </small>
                    </div>

                    <div class="ilan-grup">
                        <label for="cinsiyet">
                            <?= guvenli(ceviri("Cinsiyet *", "Sex *")) ?>
                        </label>

                        <select id="cinsiyet" name="cinsiyet" required>
                            <option value="">
                                <?= guvenli(ceviri(
                                    "Cinsiyet seçiniz",
                                    "Select sex"
                                )) ?>
                            </option>

                            <?php foreach ([
                                "Dişi" => ceviri("Dişi", "Female"),
                                "Erkek" => ceviri("Erkek", "Male")
                            ] as $deger => $yazi): ?>
                                <option
                                    value="<?= guvenli($deger) ?>"
                                    <?= form_degeri("cinsiyet") === $deger ? "selected" : "" ?>
                                ><?= guvenli($yazi) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="ilan-grup ilan-tam">
                        <label for="sehir">
                            <?= guvenli(ceviri(
                                "Bulunduğu Şehir *",
                                "City *"
                            )) ?>
                        </label>

                        <select id="sehir" name="sehir" required>
                            <option value="">
                                <?= guvenli(ceviri(
                                    "Şehir seçiniz",
                                    "Select a city"
                                )) ?>
                            </option>

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
                        <label id="ozellik-baslik">
                            <?= guvenli(ceviri(
                                "Hayvanın Özellikleri *",
                                "Animal's Traits *"
                            )) ?>
                        </label>

                        <p id="ozellik-yardim">
                            <?= guvenli(ceviri(
                                "Hayvanınıza uygun en az bir özellik işaretleyiniz.",
                                "Select at least one trait that applies to your animal."
                            )) ?>
                        </p>

                        <?php foreach ($ozellik_gruplari as $baslik => $ozellikler): ?>
                            <div class="ozellik-grubu">
                                <h3><?= guvenli(ozellik_adi($baslik)) ?></h3>

                                <div class="ozellik-listesi">
                                    <?php foreach ($ozellikler as $ozellik): ?>
                                        <label>
                                            <input
                                                type="checkbox"
                                                name="ozellikler[]"
                                                value="<?= guvenli($ozellik) ?>"
                                                <?= in_array(
                                                    $ozellik,
                                                    $secilen_ozellikler,
                                                    true
                                                ) ? "checked" : "" ?>
                                            >

                                            <span>
                                                <?= guvenli(ozellik_adi($ozellik)) ?>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="ilan-grup ilan-tam">
                        <label for="ek_aciklama">
                            <?= guvenli(ceviri(
                                "Ek Açıklama (İsteğe Bağlı)",
                                "Additional Description (Optional)"
                            )) ?>
                        </label>

                        <textarea
                            id="ek_aciklama"
                            name="ek_aciklama"
                            rows="3"
                            maxlength="500"
                            placeholder="<?= guvenli($varsayilan_ornek) ?>"
                        ><?= guvenli(form_degeri("ek_aciklama")) ?></textarea>
                    </div>

                    <div class="ilan-grup ilan-tam">
                        <label for="fotograf">
                            <?= guvenli(ceviri(
                                "Hayvan Fotoğrafı",
                                "Animal Photo"
                            )) ?>
                        </label>

                        <input
                            type="file"
                            id="fotograf"
                            name="fotograf"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            aria-describedby="foto-yardim"
                        >

                        <small id="foto-yardim">
                            <?= guvenli(ceviri(
                                "JPG, PNG veya WEBP formatı · En fazla 5 MB",
                                "JPG, PNG or WEBP format · Maximum 5 MB"
                            )) ?>
                        </small>
                    </div>
                </div>

                <div class="ilan-form-alt">
                    <p>
                        <?= guvenli(ceviri(
                            "Bilgileri kontrol ettikten sonra ilanınızı gönderebilirsiniz.",
                            "Check the information before submitting your listing."
                        )) ?>
                    </p>

                    <button type="submit" class="ilan-gonder">
                        🐾 <?= guvenli(ceviri(
                            "İlanı Gönder",
                            "Submit Listing"
                        )) ?> →
                    </button>
                </div>
            </form>
        </section>

        <p class="ilan-alt-yazi">
            ♥ <?= guvenli(ceviri(
                "Her can dostu sevgi dolu bir yuvayı hak eder.",
                "Every companion deserves a loving home."
            )) ?>
        </p>
    </main>

<?php endif; ?>

<?php require __DIR__ . "/site-alt.php"; ?>

<script>
const turSecimi = document.getElementById("tur");
const aciklama = document.getElementById("ek_aciklama");

const ornekler = <?= json_encode(
    $ornekler,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?>;

const varsayilanOrnek = <?= json_encode(
    $varsayilan_ornek,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?>;

if (turSecimi && aciklama) {
    function ornegiGuncelle() {
        aciklama.placeholder = ornekler[turSecimi.value]
            || varsayilanOrnek;
    }

    turSecimi.addEventListener("change", ornegiGuncelle);
    ornegiGuncelle();
}
</script>

</body>
</html>