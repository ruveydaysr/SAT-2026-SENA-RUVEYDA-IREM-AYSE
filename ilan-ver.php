<?php
session_start();

if (!isset($_SESSION["uye_id"])) {
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlan Ver | Pati Yuvası</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
        .giris-gerekli-alani {
            max-width: 850px;
            margin: 60px auto;
            padding: 40px 25px;
            text-align: center;
            background-color: white;
            border: 1px solid #dce6d9;
            border-radius: 16px;
        }

        .giris-gerekli-alani h1 {
            color: #35583b;
            font-size: clamp(24px, 5vw, 34px);
        }

        .giris-gerekli-alani p {
            color: #536354;
            line-height: 1.7;
        }

        .giris-gerekli-buton {
            display: inline-block;
            margin-top: 15px;
            padding: 14px 28px;
            background-color: #4a7652;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }

        .giris-gerekli-buton:hover {
            background-color: #35583b;
        }

        .giris-gerekli-buton:focus-visible {
            outline: 3px solid #35583b;
            outline-offset: 4px;
        }
    </style>
</head>

<body class="ilan-sayfasi">

    <header class="ilan-ust">
        <a href="index.php" class="ilan-logo">
            <span class="logo-pati">🐾</span>
            <span>
                <strong>Aris Pati Yuvası</strong>
                <small>Her can dostuna bir yuva</small>
            </span>
        </a>

        <nav class="ilan-menu">
            <a href="index.php">Ana Sayfa</a>
            <a href="ilanlar.php">İlanlar</a>
            <a href="ilan-ver.php" class="menu-aktif">İlan Ver</a>
            <a href="hakkimizda.php">Hakkımızda</a>
            <a href="iletisim.php">İletişim</a>
            <a href="giris.php">Giriş Yap</a>
        </nav>
    </header>

    <main>
        <section class="tanitim giris-gerekli-alani">
            <span aria-hidden="true">🐾</span>

            <h1>İlan Vermek İçin Giriş Yapmalısınız</h1>

            <p>
                Bir can dostuna yuva bulmak için
                önce hesabınıza giriş yapın.
            </p>

            <a href="giris.php" class="giris-gerekli-buton">
                Giriş Yap
            </a>
        </section>
    </main>

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
    <title>İlan Ver | Pati Yuvası</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="ilan-sayfasi">

    <header class="ilan-ust">
        <a href="index.php" class="ilan-logo">
            <span class="logo-pati">🐾</span>
            <span>
                <strong>Aris Pati Yuvası</strong>
                <small>Her can dostuna bir yuva</small>
            </span>
        </a>

        <nav class="ilan-menu">
            <a href="index.php">Ana Sayfa</a>
            <a href="ilanlar.php">İlanlar</a>
            <a href="ilan-ver.php" class="menu-aktif">İlan Ver</a>
            <a href="hakkimizda.php">Hakkımızda</a>
            <a href="iletisim.php">İletişim</a>
            <a href="cikis.php">Çıkış Yap</a>
        </nav>
    </header>

    <main class="ilan-icerik">

        <section class="ilan-tanitim">
            <span class="ilan-etiket">ARİS PATİ YUVASI · YENİ İLAN</span>
            <h1>Ona yeni bir <span>yuva</span> bulalım.</h1>
            <p>Dostunuzun bilgilerini paylaşın ve onun sevgi dolu bir aileyle buluşmasına yardımcı olun.</p>
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
                <div class="ilan-mesaj">
                    <?php echo htmlspecialchars($mesaj, ENT_QUOTES, "UTF-8"); ?>
                </div>
            <?php endif; ?>

            <form class="ilan-form" action="ilan-ver.php" method="POST" enctype="multipart/form-data">

                <div class="ilan-form-grid">

                    <div class="ilan-grup">
                        <label for="hayvan_adi">Hayvanın Adı *</label>
                        <input type="text" id="hayvan_adi" name="hayvan_adi" placeholder="Örn. Boncuk" required>
                    </div>

                    <div class="ilan-grup">
                        <label for="tur">Hayvan Türü *</label>
                        <select id="tur" name="tur" required>
                            <option value="">Tür seçiniz</option>
                            <option value="Kedi">Kedi</option>
                            <option value="Köpek">Köpek</option>
                            <option value="Kuş">Kuş</option>
                            <option value="Tavşan">Tavşan</option>
                            <option value="Balık">Balık</option>
                            <option value="Diğer">Diğer</option>
                        </select>
                    </div>

                    <div class="ilan-grup">
                        <label for="yas">Hayvanın Yaşı *</label>
                        <input type="number" id="yas" name="yas" min="0" placeholder="Yaşını giriniz" required>
                    </div>

                    <div class="ilan-grup">
                        <label for="cinsiyet">Cinsiyet *</label>
                        <select id="cinsiyet" name="cinsiyet" required>
                            <option value="">Cinsiyet seçiniz</option>
                            <option value="Dişi">Dişi</option>
                            <option value="Erkek">Erkek</option>
                        </select>
                    </div>

                    <div class="ilan-grup ilan-tam">
                        <label for="sehir">Bulunduğu Şehir *</label>
                        <input type="text" id="sehir" name="sehir" placeholder="Örn. Ankara" required>
                    </div>

                    <div class="ilan-grup ilan-tam">
                        <label for="aciklama">Açıklama *</label>
                        <textarea id="aciklama" name="aciklama" rows="5" placeholder="Hayvanın özelliklerini ve karakterini anlatın." required></textarea>
                    </div>

                    <div class="ilan-grup ilan-tam">
                        <label for="fotograf">Hayvan Fotoğrafı</label>
                        <input type="file" id="fotograf" name="fotograf" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <small>JPG, PNG veya WEBP formatı · En fazla 5 MB</small>
                    </div>

                </div>

                <div class="ilan-form-alt">
                    <p>Bilgileri kontrol ettikten sonra ilanınızı gönderebilirsiniz.</p>
                    <button type="submit" class="ilan-gonder">🐾 İlanı Gönder →</button>
                </div>

            </form>
        </section>

        <p class="ilan-alt-yazi">♥ Her can dostu sevgi dolu bir yuvayı hak eder.</p>

    </main>

    <footer class="ilan-footer">
        <p>© 2026 Pati Yuvası · Hayvan Sahiplendirme Platformu</p>
    </footer>

</body>
</html>
