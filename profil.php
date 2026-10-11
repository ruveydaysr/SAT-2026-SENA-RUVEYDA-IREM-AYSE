
<?php
session_start();

if (!isset($_SESSION["uye_id"])) {
    header("Location: giris.php");
    exit();
}

include("baglanti.php");

$uye_id = $_SESSION["uye_id"];

$sql = "SELECT * FROM ilanlar WHERE uye_id = ? ORDER BY id DESC";

$sorgu = mysqli_prepare($baglanti, $sql);
mysqli_stmt_bind_param($sorgu, "i", $uye_id);
mysqli_stmt_execute($sorgu);

$ilanlar = mysqli_stmt_get_result($sorgu);

?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<header>
    <h1>Hayvan Sahiplendirme</h1>

    <nav>
        <a href="index.php">Ana Sayfa</a>
        <a href="ilanlar.php">İlanlar</a>
        <a href="ilan-ver.php">İlan Ver</a>
        <a href="profil.php">Profilim</a>
        <a href="cikis.php">Çıkış Yap</a>
    </nav>
</header>

<main>

    <h2>Profil Bilgilerim</h2>

    <p>
        <strong>Ad Soyad:</strong>
        <?php echo htmlspecialchars($_SESSION["uye_adsoyad"] ?? "", ENT_QUOTES, "UTF-8"); ?>
    </p>

    <p>
        <strong>E-posta:</strong>
        <?php echo htmlspecialchars($_SESSION["uye_email"] ?? "", ENT_QUOTES, "UTF-8"); ?>
    </p>

    <hr>


<h2>İlanlarım</h2>

<?php if (mysqli_num_rows($ilanlar) > 0) { ?>

    <?php while ($ilan = mysqli_fetch_assoc($ilanlar)) { ?>

        <div class="kart">
            <h3>
                <?php echo htmlspecialchars($ilan["hayvan_adi"] ?? "", ENT_QUOTES, "UTF-8"); ?>
            </h3>

            <p>Tür: <?php echo htmlspecialchars($ilan["tur"] ?? "", ENT_QUOTES, "UTF-8"); ?></p>

            <p>Yaş: <?php echo (int)$ilan["yas"]; ?></p>

            <p>Şehir: <?php echo htmlspecialchars($ilan["sehir"] ?? "", ENT_QUOTES, "UTF-8"); ?></p>

            <p>Durum: <?php echo htmlspecialchars($ilan["durum"] ?? "", ENT_QUOTES, "UTF-8"); ?></p>
        </div>

    <?php } ?>

<?php } else { ?>

    <p>Henüz ilan vermediniz.</p>

<?php } ?>


</main>

</body>
</html>
