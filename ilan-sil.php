
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

// İlanın giriş yapan kullanıcıya ait olduğunu kontrol et
$sql = "SELECT id, hayvan_adi FROM ilanlar
        WHERE id = ? AND uye_id = ?";

$sorgu = mysqli_prepare($baglanti, $sql);
mysqli_stmt_bind_param($sorgu, "ii", $ilan_id, $uye_id);
mysqli_stmt_execute($sorgu);

$sonuc = mysqli_stmt_get_result($sorgu);
$ilan = mysqli_fetch_assoc($sonuc);

mysqli_stmt_close($sorgu);

if (!$ilan) {
    http_response_code(403);
    exit("Bu ilanı silme yetkiniz yok veya ilan bulunamadı.");
}

// Silme formunun güvenlik anahtarı
if (empty($_SESSION["ilan_sil_token"])) {
    $_SESSION["ilan_sil_token"] = bin2hex(random_bytes(32));
}


$hata = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $gelen_token = $_POST["token"] ?? "";
    $gelen_id = filter_input(INPUT_POST, "ilan_id", FILTER_VALIDATE_INT);

    if (
        !hash_equals($_SESSION["ilan_sil_token"], $gelen_token)
        || $gelen_id !== $ilan_id
    ) {
        $hata = "Geçersiz silme isteği.";
    } else {

        $sql = "DELETE FROM ilanlar WHERE id = ? AND uye_id = ?";
        $sil = mysqli_prepare($baglanti, $sql);

        mysqli_stmt_bind_param($sil, "ii", $ilan_id, $uye_id);

        if (mysqli_stmt_execute($sil)) {
            mysqli_stmt_close($sil);

            unset($_SESSION["ilan_sil_token"]);

            header("Location: profil.php");
            exit();
        } else {
            $hata = "İlan silinemedi. İlanla ilişkili başvurular olabilir.";
        }

        mysqli_stmt_close($sil);
    }
}

?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlan Sil</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<header>
    <h1>Hayvan Sahiplendirme</h1>

    <nav>
        <a href="index.php">Ana Sayfa</a>
        <a href="profil.php">Profilim</a>
        <a href="cikis.php">Çıkış Yap</a>
    </nav>
</header>

<main>

    <h2>İlan Silme Onayı</h2>

    <p>
        <strong>
            <?php echo htmlspecialchars($ilan["hayvan_adi"], ENT_QUOTES, "UTF-8"); ?>
        </strong>
        isimli ilanı silmek istediğinize emin misiniz?
    </p>

    <?php if ($hata !== ""): ?>
        <p style="color: red;">
            <?php echo htmlspecialchars($hata, ENT_QUOTES, "UTF-8"); ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <input type="hidden" name="ilan_id"
               value="<?php echo (int)$ilan_id; ?>">

        <input type="hidden" name="token"
               value="<?php echo htmlspecialchars(
                   $_SESSION["ilan_sil_token"],
                   ENT_QUOTES,
                   "UTF-8"
               ); ?>">

      <div class="sil-butonlari">
    <button type="submit" class="btn-sil">Evet, İlanı Sil</button>
    <a href="profil.php" class="btn-vazgec">Vazgeç</a>
</div>

    </form>

</main>

</body>
</html>

