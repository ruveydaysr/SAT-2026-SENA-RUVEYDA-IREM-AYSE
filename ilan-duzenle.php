
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


$hata = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["guncelle"])) {

    $hayvan_adi = trim($_POST["hayvan_adi"] ?? "");
    $tur = trim($_POST["tur"] ?? "");
    $yas = filter_var($_POST["yas"] ?? "", FILTER_VALIDATE_INT);
    $cinsiyet = $_POST["cinsiyet"] ?? "";
    $sehir = trim($_POST["sehir"] ?? "");
    $aciklama = trim($_POST["aciklama"] ?? "");

    if (
        $hayvan_adi === "" ||
        $tur === "" ||
        $yas === false || $yas < 0 ||
        !in_array($cinsiyet, ["Dişi", "Erkek"], true) ||
        $sehir === "" ||
        $aciklama === ""
    ) {
        $hata = "Lütfen tüm alanları doğru doldurunuz.";
    } else {

        $sql = "UPDATE ilanlar
                SET hayvan_adi = ?,
                    tur = ?,
                    yas = ?,
                    cinsiyet = ?,
                    sehir = ?,
                    aciklama = ?
                WHERE id = ? AND uye_id = ?";

        $guncelle = mysqli_prepare($baglanti, $sql);

        mysqli_stmt_bind_param(
            $guncelle,
            "ssisssii",
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
</head>

<body>

<header>
    <h1>Hayvan Sahiplendirme</h1>

    <nav>
        <a href="index.php">Ana Sayfa</a>
        <a href="ilanlar.php">İlanlar</a>
        <a href="profil.php">Profilim</a>
        <a href="cikis.php">Çıkış Yap</a>
    </nav>
</header>

<main>

    <h2>İlanımı Düzenle</h2>
    
<?php if ($hata !== ""): ?>
    <p style="color: red;">
        <?php echo htmlspecialchars($hata, ENT_QUOTES, "UTF-8"); ?>
    </p>
<?php endif; ?>

    <form method="POST">

        <label>Hayvan Adı:</label>
        <input type="text" name="hayvan_adi"
               value="<?php echo htmlspecialchars($ilan['hayvan_adi'], ENT_QUOTES, 'UTF-8'); ?>"
               required>

        <br><br>

        <label>Tür:</label>
        <input type="text" name="tur"
               value="<?php echo htmlspecialchars($ilan['tur'], ENT_QUOTES, 'UTF-8'); ?>"
               required>

        <br><br>

        <label>Yaş:</label>
        <input type="number" name="yas" min="0"
               value="<?php echo (int)$ilan['yas']; ?>"
               required>

        <br><br>

        <label>Cinsiyet:</label>
        <select name="cinsiyet">
            <option value="Dişi" <?php if ($ilan['cinsiyet'] === 'Dişi') echo 'selected'; ?>>
                Dişi
            </option>
            <option value="Erkek" <?php if ($ilan['cinsiyet'] === 'Erkek') echo 'selected'; ?>>
                Erkek
            </option>
        </select>

        <br><br>

        <label>Şehir:</label>
        <input type="text" name="sehir"
               value="<?php echo htmlspecialchars($ilan['sehir'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
               required>

        <br><br>

        <label>Açıklama:</label>
        <textarea name="aciklama" rows="5" required><?php
            echo htmlspecialchars($ilan['aciklama'] ?? '', ENT_QUOTES, 'UTF-8');
        ?></textarea>

        <br><br>

        <button type="submit" name="guncelle">
            Değişiklikleri Kaydet
        </button>

        <a href="profil.php">İptal</a>

    </form>

</main>

</body>
</html>
