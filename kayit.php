<?php

include("baglanti.php");

$mesaj = "";

if (isset($_POST["kayit"])) {

    $adsoyad = trim($_POST["adsoyad"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $telefon = trim($_POST["telefon"] ?? "");
    $sifre = $_POST["sifre"] ?? "";

    if ($adsoyad === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || $telefon === "" || $sifre === "") {

        $mesaj = "Lütfen tüm alanları doğru doldurunuz.";

    } else {

        $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);

        $sql = "INSERT INTO uyeler (ad_soyad, email, telefon, sifre)
                VALUES (?, ?, ?, ?)";

        $sorgu = mysqli_prepare($baglanti, $sql);

        mysqli_stmt_bind_param(
            $sorgu, "ssss",
            $adsoyad, $email, $telefon, $sifre_hash
        );

        if (mysqli_stmt_execute($sorgu)) {
            $mesaj = "Üyelik başarıyla oluşturuldu.";
            
header("Refresh: 2; url=giris.php");
$mesaj = "Kaydınız başarıyla tamamlandı. Giriş sayfasına yönlendiriliyorsunuz...";

        } else {
            $mesaj = "Kayıt yapılamadı. E-posta adresi zaten kullanılıyor olabilir.";
        }

        mysqli_stmt_close($sorgu);
    }
}
?>




<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="kayit-kutusu">
    <h1>Üyelik Kayıt Formu</h1>
    
<?php if ($mesaj != "") { ?>

    <p style="color: #333; font-weight: bold;">
        <?php echo htmlspecialchars($mesaj, ENT_QUOTES, "UTF-8"); ?>
    </p>

<?php } ?>


    <form method="POST">

        <label>Ad Soyad:</label>
        <input type="text" name="adsoyad" required>

        <br><br>

        <label>E-posta:</label>
        <input type="email" name="email" required>

        <br><br>

        <label>Telefon:</label>
        <input type="tel" name="telefon" required>

        <br><br>

        <label>Şifre:</label>
        <input type="password" name="sifre" required>

        <br><br>

        <input type="submit" name="kayit" value="Kayıt Ol">

    </form>

    <p>Zaten üye misiniz?</p>
    <a href="giris.php">Giriş Yap</a>
    </div>

</body>

</html>