
<?php
session_start();

if (!isset($_SESSION["uye_id"])) {
    header("Location: giris.php");
    exit();
}

include("baglanti.php");

$uye_id = (int) $_SESSION["uye_id"];
$ilan_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$ilan_id || $ilan_id < 1) {
    header("Location: profil.php");
    exit();
}

// İlanın giriş yapan kullanıcıya ait olup olmadığını kontrol et
$sql = "SELECT * FROM ilanlar WHERE id = ? AND uye_id = ?";
$sorgu = mysqli_prepare($baglanti, $sql);

mysqli_stmt_bind_param($sorgu, "ii", $ilan_id, $uye_id);
mysqli_stmt_execute($sorgu);

$sonuc = mysqli_stmt_get_result($sorgu);
$ilan = mysqli_fetch_assoc($sonuc);

mysqli_stmt_close($sorgu);

if (!$ilan) {
    http_response_code(403);
    exit("Bu ilanı düzenleme yetkiniz yok veya ilan bulunamadı.");
}


$ozellik_secenekleri = [
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

$kayitli_aciklama = $ilan["aciklama"] ?? "";
$parcalar = explode("\n\nEk Açıklama: ", $kayitli_aciklama, 2);

$kayitli_ozellikler = array_map("trim", explode(",", $parcalar[0]));
$kayitli_ek_aciklama = $parcalar[1] ?? "";

$tum_ozellikler = array_merge(...array_values($ozellik_secenekleri));
$eski_aciklama = array_diff($kayitli_ozellikler, $tum_ozellikler);

if (!empty($eski_aciklama)) {
    $kayitli_ek_aciklama = trim(
        implode(", ", $eski_aciklama) . "\n" . $kayitli_ek_aciklama
    );
}

$hata = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["guncelle"])) {
    $ilan_basligi = trim($_POST["ilan_basligi"] ?? "");
    $hayvan_adi = trim($_POST["hayvan_adi"] ?? "");
    $tur = trim($_POST["tur"] ?? "");
    $yas = filter_var($_POST["yas"] ?? "", FILTER_VALIDATE_INT);
    $cinsiyet = $_POST["cinsiyet"] ?? "";
    $sehir = trim($_POST["sehir"] ?? "");
    
$ozellikler = $_POST["ozellikler"] ?? [];

if (!is_array($ozellikler)) {
    $ozellikler = [];
}

$tum_ozellikler = array_merge(...array_values($ozellik_secenekleri));
$ozellikler = array_values(array_intersect($tum_ozellikler, $ozellikler));

$ek_aciklama = trim($_POST["ek_aciklama"] ?? "");

$aciklama = implode(", ", $ozellikler);

if ($ek_aciklama !== "") {
    $aciklama .= "\n\nEk Açıklama: " . $ek_aciklama;
}


    if (
        $hayvan_adi === "" ||
        $tur === "" ||
        $yas === false || $yas < 0 ||
        !in_array($cinsiyet, ["Dişi", "Erkek"], true) ||
        $sehir === "" ||
        
empty($ozellikler)

    ) {
        $hata = "Lütfen tüm alanları doğru doldurunuz.";
    } else {

       
$sql = "UPDATE ilanlar
                SET ilan_basligi = ?,
                    hayvan_adi = ?,
                    tur = ?,
                    yas = ?,
                    cinsiyet = ?,
                    sehir = ?,
                    aciklama = ?
                WHERE id = ? AND uye_id = ?";


        $guncelle = mysqli_prepare($baglanti, $sql);

       
mysqli_stmt_bind_param(
    $guncelle,
    "sssisssii",
    $ilan_basligi,
    $hayvan_adi,
    $tur,
    $yas,
    $cinsiyet,
    $sehir,
    $aciklama,
    $ilan_id,
    $uye_id
);


        if (mysqli_stmt_execute($guncelle)) {
            mysqli_stmt_close($guncelle);

            header("Location: profil.php");
            exit();
        } else {
            $hata = "İlan güncellenirken bir hata oluştu.";
        }

        mysqli_stmt_close($guncelle);
    }
}

?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlan Düzenle</title>
    <link rel="stylesheet" href="css/style.css">
    
<style>
    body {
        background-color: #f5f8f5;
    }

    .duzenle-baslik {
        text-align: center;
        margin: 45px auto 30px;
    }

    .duzenle-baslik h2 {
        color: #184f3c;
        font-size: 32px;
        margin-bottom: 12px;
    }

    .duzenle-baslik p {
        color: #71867a;
    }

    .duzenle-kutusu {
        max-width: 800px;
        margin: 0 auto 50px;
        padding: 35px;
        background-color: white;
        border: 1px solid #dce8df;
        border-radius: 16px;
    }

    .duzenle-kutusu label {
        display: block;
        color: #184f3c;
        font-weight: bold;
        margin-bottom: 8px;
    }

    .duzenle-kutusu input,
    .duzenle-kutusu select,
    .duzenle-kutusu textarea {
        width: 100%;
        padding: 13px;
        border: 1px solid #d3e0d6;
        border-radius: 8px;
        background-color: #f9fbf9;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
        font-size: 14px;
    }

    .duzenle-kutusu input:focus,
    .duzenle-kutusu select:focus,
    .duzenle-kutusu textarea:focus {
        outline: none;
        border-color: #347a53;
    }

    .duzenle-kutusu button {
        background-color: #347a53;
        color: white;
        padding: 13px 22px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
    }

    .duzenle-kutusu button:hover {
        background-color: #245c3d;
    }

    .duzenle-kutusu .iptal-buton {
        display: inline-block;
        margin-left: 12px;
        padding: 12px 20px;
        color: #347a53;
        text-decoration: none;
    }

    .duzenle-hata {
        color: #c62828;
        margin-bottom: 20px;
    }
    
/* Hayvan özellikleri */
.ozellik-listesi {
    margin-top: 15px;
}

.ozellik-listesi h4 {
    color: #245c3d;
    margin-top: 22px;
    margin-bottom: 12px;
}

.ozellik-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
}

.duzenle-kutusu .ozellik-secimi {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    font-weight: normal;
    font-size: 14px;
    cursor: pointer;
}

.duzenle-kutusu .ozellik-secimi input[type="checkbox"] {
    width: 16px;
    height: 16px;
    padding: 0;
    margin: 0;
    flex-shrink: 0;
    accent-color: #347a53;
}

@media (max-width: 600px) {
    .ozellik-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

</style>

</head>

<body>

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
        <a href="ilan-ver.php">İlan Ver</a>
        <a href="hakkimizda.php">Hakkımızda</a>
        <a href="iletisim.php">İletişim</a>
        <a href="profil.php">Profilim</a>
        <a href="cikis.php">Çıkış Yap</a>
    </nav>
</header>

<main>
<div class="duzenle-baslik">
    <h2>İlanımı Düzenle</h2>
    <p>Dostumuzun bilgilerini buradan güncelleyebilirsiniz.</p>
</div>

<div class="duzenle-kutusu">
    
<?php if ($hata !== ""): ?>
    <p style="color: red;">
        <?php echo htmlspecialchars($hata, ENT_QUOTES, "UTF-8"); ?>
    </p>
<?php endif; ?>

    <form method="POST">

        <label>Hayvan Adı:</label>
        
<label>İlan Başlığı:</label>
<input type="text" name="ilan_basligi"
       value="<?php echo htmlspecialchars($ilan['ilan_basligi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
       maxlength="150" required>

<br><br>

        <input type="text" name="hayvan_adi"
               value="<?php echo htmlspecialchars($ilan['hayvan_adi'], ENT_QUOTES, 'UTF-8'); ?>"
               required>

        <br><br>

        <label>Tür:</label>
<select name="tur" required>
    <option value="">Tür seçiniz</option>
    <option value="Kedi" <?php if ($ilan['tur'] === 'Kedi') echo 'selected'; ?>>Kedi</option>
    <option value="Köpek" <?php if ($ilan['tur'] === 'Köpek') echo 'selected'; ?>>Köpek</option>
    <option value="Kuş" <?php if ($ilan['tur'] === 'Kuş') echo 'selected'; ?>>Kuş</option>
    <option value="Tavşan" <?php if ($ilan['tur'] === 'Tavşan') echo 'selected'; ?>>Tavşan</option>
<option value="Balık" <?php if ($ilan['tur'] === 'Balık') echo 'selected'; ?>>Balık</option>
<option value="Kemirgen" <?php if ($ilan['tur'] === 'Kemirgen') echo 'selected'; ?>>Kemirgen</option>
<option value="Sürüngen" <?php if ($ilan['tur'] === 'Sürüngen') echo 'selected'; ?>>Sürüngen</option>
<option value="Diğer" <?php if ($ilan['tur'] === 'Diğer') echo 'selected'; ?>>Diğer</option>

</select>


        <br><br>

        <label>Yaş:</label>
        <input type="number" name="yas" min="0"
               value="<?php echo (int)$ilan['yas']; ?>"
               required>

        <br><br>

        <label>Cinsiyet:</label>
        <select name="cinsiyet" required>
            <option value="Dişi" <?php if ($ilan['cinsiyet'] === 'Dişi') echo 'selected'; ?>>
                Dişi
            </option>
            <option value="Erkek" <?php if ($ilan['cinsiyet'] === 'Erkek') echo 'selected'; ?>>
                Erkek
            </option>
        </select>

        <br><br>

        <label>Şehir:</label>
       
<select name="sehir" required>
    <option value="">Şehir seçiniz</option>

    <option value="Yozgat" <?php if (($ilan['sehir'] ?? '') === 'Yozgat') echo 'selected'; ?>>Yozgat</option>

    <option value="Ankara" <?php if (($ilan['sehir'] ?? '') === 'Ankara') echo 'selected'; ?>>Ankara</option>

    <option value="Konya" <?php if (($ilan['sehir'] ?? '') === 'Konya') echo 'selected'; ?>>Konya</option>

    <option value="Kayseri" <?php if (($ilan['sehir'] ?? '') === 'Kayseri') echo 'selected'; ?>>Kayseri</option>
</select>
        <br><br>

      
<label>Hayvanın Özellikleri:</label>
<p>Hayvanınıza uygun özellikleri işaretleyiniz.</p>

<div class="ozellik-listesi">
    <?php foreach ($ozellik_secenekleri as $kategori => $secenekler): ?>
        <h4><?php echo htmlspecialchars($kategori, ENT_QUOTES, "UTF-8"); ?></h4>

        <div class="ozellik-grid">
            <?php foreach ($secenekler as $secenek): ?>
                <label class="ozellik-secimi">
                    <input
                        type="checkbox"
                        name="ozellikler[]"
                        value="<?php echo htmlspecialchars($secenek, ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo in_array($secenek, $kayitli_ozellikler, true) ? 'checked' : ''; ?>
                    >
                    <?php echo htmlspecialchars($secenek, ENT_QUOTES, "UTF-8"); ?>
                </label>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>

<br>

<label for="ek_aciklama">Ek Açıklama (İsteğe Bağlı):</label>
<textarea
    id="ek_aciklama"
    name="ek_aciklama"
    rows="3"
    maxlength="500"
    placeholder="Hayvanınızın alışkanlıkları veya özel ihtiyaçları hakkında bilgi verebilirsiniz."
><?php echo htmlspecialchars($kayitli_ek_aciklama, ENT_QUOTES, "UTF-8"); ?></textarea>

<br><br>


        <button type="submit" name="guncelle">
            Değişiklikleri Kaydet
        </button>

       <a href="profil.php" class="iptal-buton">İptal</a>

    </form>
</div>
</main>

</body>
</html>
