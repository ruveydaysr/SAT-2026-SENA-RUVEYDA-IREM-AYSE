
<?php
session_start();

if (!isset($_SESSION["uye_id"])) {
    ?>
    
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <title>Giriş Gerekli</title>
    </head>

    <body>

        <h1>İlan Vermek İçin Giriş Yapmalısınız</h1>

        <p>İlan vermek için giriş yapmalısınız.</p>

        <a href="giris.php">
            <button>Giriş Yap</button>
        </a>

    </body>
    </html>

    <?php
    exit();
}

include("baglanti.php");

$mesaj = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $hayvan_adi = trim($_POST["hayvan_adi"] ?? "");
    $tur = trim($_POST["tur"] ?? "");
    $yas = filter_input(INPUT_POST, "yas", FILTER_VALIDATE_INT);
    $cinsiyet = trim($_POST["cinsiyet"] ?? "");
    $sehir = trim($_POST["sehir"] ?? "");
    $aciklama = trim($_POST["aciklama"] ?? "");
    $uye_id = (int) $_SESSION["uye_id"];

    $fotograf = null;
    $kaydedilen_dosya = null;

    if (
        $hayvan_adi === "" ||
        $tur === "" ||
        $yas === false ||
        $yas === null ||
        $yas < 0 ||
        $cinsiyet === "" ||
        $sehir === "" ||
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
                    $mesaj = "Yalnızca JPG, PNG veya WEBP fotoğraf yükleyebilirsiniz.";
                } else {

                    $klasor = __DIR__ . "/uploads";

                    if (!is_dir($klasor) || !is_writable($klasor)) {
                        $mesaj = "Fotoğraf klasörü bulunamadı veya yazılabilir değil.";
                    } else {

                        $dosya_adi = bin2hex(random_bytes(16)) . "." . $izin_verilen[$mime];
                        $kaydedilen_dosya = $klasor . "/" . $dosya_adi;

                        if (move_uploaded_file($dosya["tmp_name"], $kaydedilen_dosya)) {
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
                (hayvan_adi, tur, yas, cinsiyet, sehir, aciklama, uye_id, durum, fotograf)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Bekliyor', ?)";

            $sorgu = mysqli_prepare($baglanti, $sql);

            if ($sorgu) {

                mysqli_stmt_bind_param(
                    $sorgu,
                    "ssisssis",
                    $hayvan_adi,
                    $tur,
                    $yas,
                    $cinsiyet,
                    $sehir,
                    $aciklama,
                    $uye_id,
                    $fotograf
                );

                if (mysqli_stmt_execute($sorgu)) {
                    $mesaj = "İlanınız başarıyla kaydedildi. Yönetici onayı bekleniyor.";
                } else {
                    $mesaj = "İlan kaydedilirken bir hata oluştu.";

                    if ($kaydedilen_dosya !== null) {
                        unlink($kaydedilen_dosya);
                    }
                }

                mysqli_stmt_close($sorgu);

            } else {

                $mesaj = "İlan hazırlanırken bir hata oluştu.";

                if ($kaydedilen_dosya !== null) {
                    unlink($kaydedilen_dosya);
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
    <title>İlan Ver</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="ilan-sayfa">

    <div class="ilan-baslik">
        <h1>🐾 Hayvan İlanı Ver</h1>
        <p>Yeni bir ilan oluşturarak bir hayvanın yuva bulmasına yardımcı olun.</p>
    </div>

    <?php if ($mesaj !== ""): ?>
        <div class="ilan-mesaj">
            <?php echo htmlspecialchars($mesaj, ENT_QUOTES, "UTF-8"); ?>
        </div>
    <?php endif; ?>

    <form class="ilan-form" action="ilan-ver.php" method="POST" enctype="multipart/form-data">

    <label for="tur">Hayvan Türü:</label>
    <select id="tur" name="tur" required>
        <option value="">Hayvan türünü seçiniz</option>
        <option value="Kedi">Kedi</option>
        <option value="Köpek">Köpek</option>
        <option value="Kuş">Kuş</option>
        <option value="Tavşan">Tavşan</option>
        <option value="Balık">Balık</option>
        <option value="Diğer">Diğer</option>
    </select>

    <br><br>

    <label for="hayvan_adi">Hayvan Adı:</label>
    <input
        type="text"
        id="hayvan_adi"
        name="hayvan_adi"
        placeholder="Hayvanın adını giriniz"
        required
    >

    <br><br>

    <label for="yas">Hayvanın Yaşı:</label>
    <input
        type="number"
        id="yas"
        name="yas"
        placeholder="Yaşını giriniz"
        min="0"
        required
    >

    <br><br>

    <label for="cinsiyet">Cinsiyet:</label>
    <select id="cinsiyet" name="cinsiyet" required>
        <option value="">Seçiniz</option>
        <option value="Dişi">Dişi</option>
        <option value="Erkek">Erkek</option>
    </select>

    <br><br>

    <label for="sehir">Şehir:</label>
    <input
        type="text"
        id="sehir"
        name="sehir"
        placeholder="Şehir giriniz"
        required
    >

    <br><br>

    <label for="aciklama">Açıklama:</label>
    <textarea
        id="aciklama"
        name="aciklama"
        rows="5"
        placeholder="Hayvan hakkında bilgi veriniz"
        required
    ></textarea>

    <br><br>

    <label for="fotograf">Hayvan Fotoğrafı:</label>
    <input
        type="file"
        id="fotograf"
        name="fotograf"
        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
    >

    <p>JPG, PNG veya WEBP formatında, en fazla 5 MB fotoğraf yükleyebilirsiniz.</p>

    <br><br>

          <button type="submit" class="ilan-gonder">🐾 İlanı Yayınla</button>

    </form>

</div>

</body>
</html>
