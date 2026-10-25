<?php
session_start();

function guvenli($deger)
{
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

function form_degeri($alan)
{
    return isset($_POST[$alan]) && is_string($_POST[$alan])
        ? trim($_POST[$alan])
        : "";
}

$uye_girisi = isset($_SESSION["uye_id"]);
$mesaj = "";
$ozellik_secenekleri = [
    "Uysal",
    "Oyuncu",
    "Sakin",
    "Enerjik",
    "İnsan canlısı",
    "Çocuklarla anlaşır",
    "Diğer hayvanlarla anlaşır"
];

$secilen_ozellikler = [];
$karakteristik_ozellikler = "";

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

if ($uye_girisi) {
    require_once("baglanti.php");

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $ilan_basligi = form_degeri("ilan_basligi");
        $hayvan_adi = form_degeri("hayvan_adi");
        $tur = form_degeri("tur");
        $yas = filter_input(INPUT_POST, "yas", FILTER_VALIDATE_INT);
        $cinsiyet = form_degeri("cinsiyet");
        $sehir = form_degeri("sehir");
        $aciklama = form_degeri("aciklama");
        $uye_id = (int)$_SESSION["uye_id"];

        $fotograf = null;
        $kaydedilen_dosya = null;

        if (
            $ilan_basligi === ""
            || mb_strlen($ilan_basligi, "UTF-8") > 150
            || $hayvan_adi === ""
            || !in_array($tur, $turler, true)
            || $yas === false
            || $yas === null
            || $yas < 0
            || !in_array($cinsiyet, ["Dişi", "Erkek"], true)
            || !in_array($sehir, $sehirler, true)
            || $aciklama === ""
        ) {
            $mesaj = "Lütfen tüm alanları doğru şekilde doldurunuz.";
        } else {
            $dosya = $_FILES["fotograf"] ?? null;
            $dosya_hatasi = $dosya["error"] ?? UPLOAD_ERR_NO_FILE;

            if ($dosya_hatasi !== UPLOAD_ERR_NO_FILE) {
                if ($dosya_hatasi !== UPLOAD_ERR_OK) {
                    $mesaj = "Fotoğraf yüklenirken hata oluştu.";
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
                            $mesaj = "Fotoğraf klasörü bulunamadı veya yazılabilir değil.";
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

            if ($mesaj === "") {
                $kayit_basarili = false;

                try {
                    $sql = "INSERT INTO ilanlar
        (
            ilan_basligi, hayvan_adi, tur, yas,
            cinsiyet, sehir, aciklama,
            karakteristik_ozellikler,
            uye_id, durum, fotograf
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Bekliyor', ?)";

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
    mysqli_stmt_close($sorgu);
}

<!DOCTYPE html>

if ($uye_girisi) {
    require_once("baglanti.php");

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $ilan_basligi = form_degeri("ilan_basligi");
        $hayvan_adi = form_degeri("hayvan_adi");
        $tur = form_degeri("tur");
        $yas = filter_input(INPUT_POST, "yas", FILTER_VALIDATE_INT);
        $cinsiyet = form_degeri("cinsiyet");
        $sehir = form_degeri("sehir");
        $aciklama = form_degeri("aciklama");
        $uye_id = (int)$_SESSION["uye_id"];

        $gelen_ozellikler = $_POST["karakteristik_ozellikler"] ?? [];
        if (!is_array($gelen_ozellikler)) {
            $gelen_ozellikler = [];
        }

        $secilen_ozellikler = array_values(
            array_intersect($ozellik_secenekleri, $gelen_ozellikler)
        );

        $karakteristik_ozellikler = implode(", ", $secilen_ozellikler);
        $fotograf = null;
        $kaydedilen_dosya = null;

        if (
            $ilan_basligi === "" ||
            mb_strlen($ilan_basligi, "UTF-8") > 150 ||
            $hayvan_adi === "" ||
            !in_array($tur, $turler, true) ||
            $yas === false || $yas === null || $yas < 0 ||
            !in_array($cinsiyet, ["Dişi", "Erkek"], true) ||
            !in_array($sehir, $sehirler, true) ||
            $aciklama === ""
        ) {
            $mesaj = "Lütfen tüm alanları doğru şekilde doldurunuz.";
        } else {
            $dosya = $_FILES["fotograf"] ?? null;
            $dosya_hatasi = $dosya["error"] ?? UPLOAD_ERR_NO_FILE;

            if ($dosya_hatasi !== UPLOAD_ERR_NO_FILE) {
                if ($dosya_hatasi !== UPLOAD_ERR_OK) {
                    $mesaj = "Fotoğraf yüklenirken hata oluştu.";
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
                        !isset($izin_verilen[$mime]) ||
                        @getimagesize($dosya["tmp_name"]) === false
                    ) {
                        $mesaj = "Yalnızca JPG, PNG veya WEBP yükleyebilirsiniz.";
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

            if ($mesaj === "") {
                $sql = "INSERT INTO ilanlar
                    (ilan_basligi, hayvan_adi, tur, yas, cinsiyet,
                     sehir, aciklama, karakteristik_ozellikler,
                     uye_id, durum, fotograf)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Bekliyor', ?)";

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

                    if (mysqli_stmt_execute($sorgu)) {
                        $mesaj = "İlanınız başarıyla oluşturuldu.";
                        $_POST = [];
                        $secilen_ozellikler = [];
                    } else {
                        $mesaj = "İlan kaydedilemedi. Veritabanı alanlarını kontrol edin.";
                        if ($kaydedilen_dosya !== null) {
                            @unlink($kaydedilen_dosya);
                        }
                    }

                    mysqli_stmt_close($sorgu);
                } else {
                    $mesaj = "Kayıt sorgusu oluşturulamadı. Veritabanı tablosunu kontrol edin.";
                    if ($kaydedilen_dosya !== null) {
                        @unlink($kaydedilen_dosya);
                    }
                }
            }
        }
    }
}
?>
