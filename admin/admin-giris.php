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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Girişi | Aris Pati Yuvası</title>
    <link rel="stylesheet" href="admin-style.css?v=5">
</head>

<body class="admin-giris-sayfasi">
    <header class="giris-ust">
    <a href="../index.php" class="admin-logo">
        <span class="logo-pati">🐾</span>
        <span>
            <strong>Aris Pati Yuvası</strong>
            <small>Her can dostuna bir yuva</small>
        </span>
    </a>

    <nav aria-label="Site menüsü">
        <a href="../index.php">Ana Sayfa</a>
        <a href="../ilanlar.php">İlanlar</a>
        <a href="../hakkimizda.php">Hakkımızda</a>
        <a href="../iletisim.php">İletişim</a>
    </nav>
</header>

<div class="giris">
    <img
    src="../images/aris-logo.png"
    alt="Aris Pati Yuvası"
    class="giris-logo-resim"
>
    <h2>Yönetici Girişi</h2>

    <p>Aris Pati Yuvası</p>
    <p class="giris-aciklama">Her can dostuna bir yuva</p>

    <?php if ($hata !== ""): ?>
        <p class="hata">
            <?php echo htmlspecialchars($hata, ENT_QUOTES, "UTF-8"); ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <input
            type="text"
            name="kullanici_adi"
            placeholder="Kullanıcı Adı"
            aria-label="Kullanıcı Adı"
            autocomplete="username"
            required
        >

        <input
            type="password"
            name="sifre"
            placeholder="Şifre"
            aria-label="Şifre"
            autocomplete="current-password"
            required
        >

        <button type="submit" name="giris">
            Giriş Yap
        </button>
    </form>
</div>

</body>
</html>