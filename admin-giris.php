<?php

session_start();
include("baglanti.php");

$hata = "";

if (isset($_POST["giris"])) {

    $kullanici_adi = $_POST["kullanici_adi"];
    $sifre = $_POST["sifre"];

    $sorgu = mysqli_query(
        $baglanti,
        "SELECT * FROM admin 
         WHERE kullanici_adi='$kullanici_adi' 
         AND sifre='$sifre'"
    );

    if (mysqli_num_rows($sorgu) > 0) {

        $_SESSION["admin"] = $kullanici_adi;

        header("Location: admin-panel.php");
        exit();

    } else {

        $hata = "Kullanıcı adı veya şifre yanlış.";

    }
}

?>

<!DOCTYPE html>
<html lang="tr">

<head>

    <meta charset="UTF-8">
    <title>Admin Girişi</title>

    <style>

        body {
            font-family: Arial;
            background-color: #f2f2f2;
        }

        .giris {
            width: 350px;
            background-color: white;
            margin: 100px auto;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
        }

        input {
            width: 90%;
            padding: 10px;
            margin: 10px;
        }

        button {
            width: 95%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }

        .hata {
            color: red;
        }

    </style>

</head>

<body>

<div class="giris">

    <h1>🐾</h1>

    <h2>Yönetici Girişi</h2>

    <p>Hayvan Sahiplendirme Sistemi</p>

    <?php
    if ($hata != "") {
        echo "<p class='hata'>$hata</p>";
    }
    ?>

    <form method="POST">

        <input
            type="text"
            name="kullanici_adi"
            placeholder="Kullanıcı Adı"
            required>

        <input
            type="password"
            name="sifre"
            placeholder="Şifre"
            required>

        <button type="submit" name="giris">
            Giriş Yap
        </button>

    </form>

</div>

</body>

</html>