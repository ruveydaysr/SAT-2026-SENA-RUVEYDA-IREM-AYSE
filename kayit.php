<?php

include("baglanti.php");

if (isset($_POST["kayit"])) {

    $adsoyad = $_POST["adsoyad"];
    $email = $_POST["email"];
    $telefon = $_POST["telefon"];
    $sifre = $_POST["sifre"];

    $ekle = mysqli_query($baglanti, "INSERT INTO uyeler 
    (ad_soyad, email, telefon, sifre) 
    VALUES ('$adsoyad', '$email', '$telefon', '$sifre')");

    if ($ekle) {
        echo "Üyelik başarıyla oluşturuldu.";
    }
}

?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Kayıt Ol</title>
</head>

<body>

    <h1>Üyelik Kayıt Formu</h1>

    <form method="POST">

        <label>Ad Soyad:</label>
        <input type="text" name="adsoyad">

        <br><br>

        <label>E-posta:</label>
        <input type="email" name="email">

        <br><br>

        <label>Telefon:</label>
        <input type="tel" name="telefon">

        <br><br>

        <label>Şifre:</label>
        <input type="password" name="sifre">

        <br><br>

        <input type="submit" name="kayit" value="Kayıt Ol">

    </form>

    <p>Zaten üye misiniz?</p>
    <a href="giris.html">Giriş Yap</a>

</body>

</html>