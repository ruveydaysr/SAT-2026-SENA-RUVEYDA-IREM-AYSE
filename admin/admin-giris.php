<?php

session_start();
include("../baglanti.php");

$hata = "";

if (isset($_POST["giris"])) {

    $kullanici_adi = trim($_POST["kullanici_adi"] ?? "");
    $sifre = $_POST["sifre"] ?? "";

    $sorgu = mysqli_prepare(
        $baglanti,
        "SELECT kullanici_adi, sifre
         FROM admin
         WHERE kullanici_adi = ?
         LIMIT 1"
    );

    mysqli_stmt_bind_param($sorgu, "s", $kullanici_adi);
    mysqli_stmt_execute($sorgu);

    $sonuc = mysqli_stmt_get_result($sorgu);
    $admin = mysqli_fetch_assoc($sonuc);

    mysqli_stmt_close($sorgu);

    $dogru = false;

    if ($admin) {

        $kayitli_sifre = $admin["sifre"];
        $bilgi = password_get_info($kayitli_sifre);

        if (!empty($bilgi["algo"])) {

            $dogru = password_verify($sifre, $kayitli_sifre);

        } else {

            // Eski açık şifreyi doğrula ve hash olarak güncelle.
            $dogru = hash_equals($kayitli_sifre, $sifre);

            if ($dogru) {

                $hash = password_hash($sifre, PASSWORD_DEFAULT);

                $guncelle = mysqli_prepare(
                    $baglanti,
                    "UPDATE admin
                     SET sifre = ?
                     WHERE kullanici_adi = ? AND sifre = ?
                     LIMIT 1"
                );

                mysqli_stmt_bind_param(
                    $guncelle,
                    "sss",
                    $hash,
                    $admin["kullanici_adi"],
                    $kayitli_sifre
                );

                mysqli_stmt_execute($guncelle);
                mysqli_stmt_close($guncelle);
            }
        }
    }

    if ($dogru) {

        session_regenerate_id(true);
        $_SESSION["admin"] = $admin["kullanici_adi"];

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