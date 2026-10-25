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
        
$ozellikler = $_POST["ozellikler"] ?? [];

if (!is_array($ozellikler)) {
    $ozellikler = [];
}

$ek_aciklama = trim($_POST["ek_aciklama"] ?? "");

$aciklama = implode(", ", $ozellikler);

if ($ek_aciklama !== "") {
    $aciklama .= "\n\nEk Açıklama: " . $ek_aciklama;
}

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
            || empty($ozellikler)
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
                                uye_id, durum, fotograf
                            )
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Bekliyor', ?)";

                    $sorgu = mysqli_prepare($baglanti, $sql);

                    if ($sorgu) {
                        mysqli_stmt_bind_param(
                            $sorgu,
                            "sssisssis",
                            $ilan_basligi,
                            $hayvan_adi,
                            $tur,
                            $yas,
                            $cinsiyet,
                            $sehir,
                            $aciklama,
                            $uye_id,
                            $fotograf
                        );

                        $kayit_basarili = mysqli_stmt_execute($sorgu);
                        mysqli_stmt_close($sorgu);
                    }
                } catch (mysqli_sql_exception $hata) {
                    error_log($hata->getMessage());
                }

                if ($kayit_basarili) {
                    $mesaj = "İlanınız başarıyla kaydedildi. Yönetici onayı bekleniyor.";
                } else {
                    $mesaj = "İlan kaydedilemedi. Veritabanı alanlarını ve bağlantıyı kontrol ediniz.";

                    if (
                        $kaydedilen_dosya !== null
                        && is_file($kaydedilen_dosya)
                    ) {
                        unlink($kaydedilen_dosya);
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
    <title>İlan Ver | Pati Yuvası</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
        .giris-gerekli-alani {
            max-width: 850px;
            margin: 60px auto;
            padding: 40px 25px;
            text-align: center;
            background: white;
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
            background: #4a7652;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }

        .giris-gerekli-buton:hover {
            background: #35583b;
        }

        .giris-gerekli-buton:focus-visible {
            outline: 3px solid #35583b;
            outline-offset: 4px;
        }
        .ilan-grup input[type="checkbox"] {
    accent-color: #347a53;
    width: 16px;
    height: 16px;
    margin-right: 6px;
}

.ilan-grup h4 {
    color: #245c3d;
    margin-top: 18px;
    margin-bottom: 10px;
}
.ilan-grup label:has(input[type="checkbox"]) {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    font-weight: normal;
    cursor: pointer;
}

.ilan-grup input[type="checkbox"] {
    accent-color: #347a53;
    width: 16px;
    height: 16px;
    margin: 0;
    flex-shrink: 0;
}

/* Hayvan özelliklerini üç sütunda göster */
.ilan-grup.ilan-tam:has(input[name="ozellikler[]"]) {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    column-gap: 20px;
    row-gap: 10px;
}

.ilan-grup.ilan-tam:has(input[name="ozellikler[]"]) > h4,
.ilan-grup.ilan-tam:has(input[name="ozellikler[]"]) > p,
.ilan-grup.ilan-tam:has(input[name="ozellikler[]"]) > label:first-child {
    grid-column: 1 / -1;
}

.ilan-grup.ilan-tam:has(input[name="ozellikler[]"]) label:has(input[type="checkbox"]) {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

@media (max-width: 600px) {
    .ilan-grup.ilan-tam:has(input[name="ozellikler[]"]) {
        grid-template-columns: repeat(2, 1fr);
    }
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

        <?php if ($uye_girisi): ?>
            <a
                href="cikis.php"
                onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');"
            >
                Çıkış Yap
            </a>
        <?php else: ?>
            <a href="giris.php">Giriş Yap</a>
        <?php endif; ?>
    </nav>
</header>

<?php if (!$uye_girisi): ?>

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
                <div class="ilan-mesaj" role="status">
                    <?php echo guvenli($mesaj); ?>
                </div>
            <?php endif; ?>

            <form
                class="ilan-form"
                action="ilan-ver.php"
                method="POST"
                enctype="multipart/form-data"
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
                            value="<?php echo guvenli(form_degeri('ilan_basligi')); ?>"
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
                            value="<?php echo guvenli(form_degeri('hayvan_adi')); ?>"
                            required
                        >
                    </div>

                    <div class="ilan-grup">
                        <label for="tur">Hayvan Türü *</label>
                        <select id="tur" name="tur" required>
                            <option value="">Tür seçiniz</option>

                            <?php foreach ($turler as $secenek): ?>
                                <option
                                    value="<?php echo guvenli($secenek); ?>"
                                    <?php echo form_degeri("tur") === $secenek ? "selected" : ""; ?>
                                >
                                    <?php echo guvenli($secenek); ?>
                                </option>
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
                            value="<?php echo guvenli(form_degeri('yas')); ?>"
                            required
                        >
                    </div>

                    <div class="ilan-grup">
                        <label for="cinsiyet">Cinsiyet *</label>
                        <select id="cinsiyet" name="cinsiyet" required>
                            <option value="">Cinsiyet seçiniz</option>

                            <?php foreach (["Dişi", "Erkek"] as $secenek): ?>
                                <option
                                    value="<?php echo guvenli($secenek); ?>"
                                    <?php echo form_degeri("cinsiyet") === $secenek ? "selected" : ""; ?>
                                >
                                    <?php echo guvenli($secenek); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="ilan-grup ilan-tam">
                        <label for="sehir">Bulunduğu Şehir *</label>

                        <select id="sehir" name="sehir" required>
                            <option value="">Şehir seçiniz</option>

                            <?php foreach ($sehirler as $il): ?>
                                <option
                                    value="<?php echo guvenli($il); ?>"
                                    <?php echo form_degeri("sehir") === $il ? "selected" : ""; ?>
                                >
                                    <?php echo guvenli($il); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
<div class="ilan-grup ilan-tam">
    <label>Hayvanın Özellikleri *</label>
    <p>Hayvanınıza uygun özellikleri işaretleyiniz.</p>

    <h4>Karakter Özellikleri</h4>
    <label><input type="checkbox" name="ozellikler[]" value="Oyuncu"> Oyuncu</label>
    <label><input type="checkbox" name="ozellikler[]" value="Sakin"> Sakin</label>
    <label><input type="checkbox" name="ozellikler[]" value="Sevecen"> Sevecen</label>
    <label><input type="checkbox" name="ozellikler[]" value="Hareketli"> Hareketli</label>
    <label><input type="checkbox" name="ozellikler[]" value="Uysal"> Uysal</label>
    <label><input type="checkbox" name="ozellikler[]" value="Çekingen"> Çekingen</label>

    <h4>Sosyal Özellikleri</h4>
    <label><input type="checkbox" name="ozellikler[]" value="Çocuklarla anlaşır"> Çocuklarla anlaşır</label>
    <label><input type="checkbox" name="ozellikler[]" value="Diğer hayvanlarla anlaşır"> Diğer hayvanlarla anlaşır</label>
    <label><input type="checkbox" name="ozellikler[]" value="İnsanlara alışkın"> İnsanlara alışkın</label>

    <h4>Bakım ve Sağlık</h4>
    <label><input type="checkbox" name="ozellikler[]" value="Tuvalet eğitimi var"> Tuvalet eğitimi var</label>
    <label><input type="checkbox" name="ozellikler[]" value="Aşıları yapıldı"> Aşıları yapıldı</label>
    <label><input type="checkbox" name="ozellikler[]" value="Kısırlaştırıldı"> Kısırlaştırıldı</label>
    <label><input type="checkbox" name="ozellikler[]" value="Özel bakım gerektiriyor"> Özel bakım gerektiriyor</label>

    <h4>Yaşam Ortamı</h4>
    <label><input type="checkbox" name="ozellikler[]" value="Ev ortamına alışkın"> Ev ortamına alışkın</label>
    <label><input type="checkbox" name="ozellikler[]" value="Apartman yaşamına uygun"> Apartman yaşamına uygun</label>
    <label><input type="checkbox" name="ozellikler[]" value="Bahçeli ev için uygun"> Bahçeli ev için uygun</label>
</div>

<div class="ilan-grup ilan-tam">
    <label for="ek_aciklama">Ek Açıklama (İsteğe Bağlı)</label>
    <textarea
        id="ek_aciklama"
        name="ek_aciklama"
        rows="3"
        maxlength="500"
        placeholder="Hayvanınızın alışkanlıkları veya özel ihtiyaçları hakkında bilgi verebilirsiniz."
    ></textarea>
</div>


                    <div class="ilan-grup ilan-tam">
                        <label for="fotograf">Hayvan Fotoğrafı</label>
                        <input
                            type="file"
                            id="fotograf"
                            name="fotograf"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >
                        <small>JPG, PNG veya WEBP formatı · En fazla 5 MB</small>
                    </div>

                </div>

                <div class="ilan-form-alt">
                    <p>Bilgileri kontrol ettikten sonra ilanınızı gönderebilirsiniz.</p>
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

    <footer class="ilan-footer">
        <p>© 2026 Pati Yuvası · Hayvan Sahiplendirme Platformu</p>
    </footer>

<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const turSecimi = document.querySelector('select[name="tur"]');
    const aciklama = document.getElementById("ek_aciklama");

    if (!turSecimi || !aciklama) return;

    const ornekler = {
        "Kedi": "Örneğin: Kedim yabancılara karşı ilk başta çekingen davranır ancak alışınca çok sevecendir.",
        "Köpek": "Örneğin: Köpeğim yürüyüş yapmayı çok sever ve temel komutları bilir.",
        "Kuş": "Örneğin: Kuşum insan sesine alışkındır ve zaman zaman sesleri taklit eder.",
        "Tavşan": "Örneğin: Tavşanım sakin bir ortamda yaşamayı sever ve elde beslenmeye alışkındır.",
        "Balık": "Örneğin: Balığım diğer balıklarla uyumludur ve düzenli beslenme saatlerine alışkındır.",
        "Kemirgen": "Örneğin: Küçük dostum hareketli ve meraklıdır, oyuncaklarla vakit geçirmeyi sever.",
        "Sürüngen": "Örneğin: Sürüngenimin özel sıcaklık ve bakım ihtiyaçları vardır.",
        "Diğer": "Örneğin: Hayvanımın özel alışkanlıkları ve bakım ihtiyaçları bulunmaktadır."
    };

    function ornegiGuncelle() {
        aciklama.placeholder = ornekler[turSecimi.value] ||
            "Hayvanınız hakkında eklemek istediğiniz bilgileri yazabilirsiniz.";
    }

    turSecimi.addEventListener("change", ornegiGuncelle);
    ornegiGuncelle();
});
</script>

</body>
</html>