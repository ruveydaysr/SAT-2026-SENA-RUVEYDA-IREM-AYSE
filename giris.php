<?php

include("baglanti.php");

if (isset($_POST["giris"])) {

    $email = $_POST["email"];
    $sifre = $_POST["sifre"];

    $sorgu = mysqli_query(
        $baglanti,
        "SELECT * FROM uyeler WHERE email='$email' AND sifre='$sifre'"
    );

    if (mysqli_num_rows($sorgu) > 0) {
        echo "Giriş başarılı.";
    } else {
        echo "E-posta veya şifre yanlış.";
    }
}

?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Giriş Yap</title>
</head>

<body>

    <h1>Üye Girişi</h1>

    <form method="POST">

        <label>E-posta:</label>
        <input type="email" name="email">

        <br><br>

        <label>Şifre:</label>
        <input type="password" name="sifre">

        <br><br>

        <input type="submit" name="giris" value="Giriş Yap">

    </form>

    <p>Üye değil misiniz?</p>
    <a href="kayit.php">Kayıt Ol</a>

</body>

</html>