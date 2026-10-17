
<?php
session_start();

if (!isset($_SESSION["uye_id"])) {
    header("Location: giris.php");
    exit();
}

include("baglanti.php");

$uye_id = (int) $_SESSION["uye_id"];

function guvenli($deger) {
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

// Kullanıcının verdiği ilanlar
$sql = "SELECT * FROM ilanlar
        WHERE uye_id = ?
        ORDER BY id DESC";

$sorgu = mysqli_prepare($baglanti, $sql);
mysqli_stmt_bind_param($sorgu, "i", $uye_id);
mysqli_stmt_execute($sorgu);

$ilanlar = mysqli_stmt_get_result($sorgu);

// Kullanıcının sahiplenme başvuruları
$sql2 = "SELECT
            b.id,
            b.ilan_id,
            b.durum,
            b.basvuru_tarihi,
            i.hayvan_adi,
            i.tur,
            i.sehir
         FROM sahiplenme_basvurulari AS b
         LEFT JOIN ilanlar AS i
            ON b.ilan_id = i.id
         WHERE b.uye_id = ?
         ORDER BY b.id DESC";

$sorgu2 = mysqli_prepare($baglanti, $sql2);
mysqli_stmt_bind_param($sorgu2, "i", $uye_id);
mysqli_stmt_execute($sorgu2);

$basvurular = mysqli_stmt_get_result($sorgu2);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
        .profil-bolum {
            margin: 30px 0;
        }

        .profil-kart {
            background: white;
            padding: 20px;
            margin-bottom: 18px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .profil-kart h3 {
            margin-top: 0;
        }

        .profil-kart p {
            margin: 10px 0;
        }

        .profil-fotograf {
            width: 180px;
            height: 140px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .profil-durum {
            font-weight: bold;
        }

        .profil-durum.bekliyor {
            color: #e69500;
        }

        .profil-durum.onaylandi {
            color: #2e7d32;
        }

        .profil-durum.reddedildi {
            color: #d32f2f;
        }

        .red-nedeni {
            color: #d32f2f;
        }
    </style>
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

    <section class="profil-bolum">

        <h2>Profil Bilgilerim</h2>

        <p>
            <strong>Ad Soyad:</strong>
            <?php echo guvenli($_SESSION["uye_adsoyad"] ?? ""); ?>
        </p>

        <p>
            <strong>E-posta:</strong>
            <?php echo guvenli($_SESSION["uye_email"] ?? ""); ?>
        </p>

    </section>

    <hr>

    <section class="profil-bolum">

        <h2>İlanlarım</h2>

        <?php if (mysqli_num_rows($ilanlar) > 0): ?>

            <?php while ($ilan = mysqli_fetch_assoc($ilanlar)): ?>

                <div class="profil-kart">

                    <?php
                    $fotograf = $ilan["fotograf"] ?? "";
                    $dosya_adi = basename($fotograf);

                    $gecerli_fotograf =
                        $fotograf !== "" &&
                        preg_match(
                            '/^[a-f0-9]{32}\.(jpg|png|webp)$/',
                            $dosya_adi
                        ) &&
                        is_file(__DIR__ . "/uploads/" . $dosya_adi);
                    ?>

                    <?php if ($gecerli_fotograf): ?>

                        <img
                            src="uploads/<?php echo guvenli($dosya_adi); ?>"
                            alt="<?php echo guvenli($ilan["hayvan_adi"]); ?>"
                            class="profil-fotograf"
                        >

                    <?php endif; ?>

                    <h3>
                        <?php echo guvenli($ilan["hayvan_adi"]); ?>
                    </h3>

                    <p>
                        <strong>Tür:</strong>
                        <?php echo guvenli($ilan["tur"]); ?>
                    </p>

                    <p>
                        <strong>Yaş:</strong>
                        <?php echo (int)$ilan["yas"]; ?>
                    </p>

                    <p>
                        <strong>Şehir:</strong>
                        <?php echo guvenli($ilan["sehir"]); ?>
                    </p>

                    <p>
                        <strong>Durum:</strong>
                        <?php echo guvenli($ilan["durum"]); ?>
                    </p>

                    <?php if ($ilan["durum"] === "Reddedildi"): ?>

                        <p class="red-nedeni">
                            <strong>Reddedilme Nedeni:</strong>
                            <?php echo guvenli($ilan["red_nedeni"] ?? ""); ?>
                        </p>

                    <?php endif; ?>

<p>
    <a href="ilan-duzenle.php?id=<?php echo (int)$ilan['id']; ?>">
        Düzenle
    </a>

    &nbsp; | &nbsp;

    <a href="ilan-sil.php?id=<?php echo (int)$ilan['id']; ?>">
        Sil
    </a>
</p>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <p>Henüz ilan vermediniz.</p>

        <?php endif; ?>

    </section>

    <hr>

    <section class="profil-bolum">

        <h2>Sahiplenme Başvurularım</h2>

        <?php if (mysqli_num_rows($basvurular) > 0): ?>

            <?php while ($basvuru = mysqli_fetch_assoc($basvurular)): ?>

                <div class="profil-kart">

                    <h3>
                        <?php echo guvenli(
                            $basvuru["hayvan_adi"] ?? "İlan silinmiş"
                        ); ?>
                    </h3>

                    <p>
                        <strong>Başvuru No:</strong>
                        <?php echo (int)$basvuru["id"]; ?>
                    </p>

                    <p>
                        <strong>Hayvan Türü:</strong>
                        <?php echo guvenli($basvuru["tur"] ?? "-"); ?>
                    </p>

                    <p>
                        <strong>Şehir:</strong>
                        <?php echo guvenli($basvuru["sehir"] ?? "-"); ?>
                    </p>

                    <p>
                        <strong>Başvuru Tarihi:</strong>
                        <?php echo guvenli($basvuru["basvuru_tarihi"]); ?>
                    </p>

                    <p>
                        <strong>Başvuru Durumu:</strong>

                        <?php
                        $durum = $basvuru["durum"];

                        $durum_sinifi = "";

                        if ($durum === "Bekliyor") {
                            $durum_sinifi = "bekliyor";
                        } elseif ($durum === "Onaylandı") {
                            $durum_sinifi = "onaylandi";
                        } elseif ($durum === "Reddedildi") {
                            $durum_sinifi = "reddedildi";
                        }
                        ?>

                        <span class="profil-durum <?php echo $durum_sinifi; ?>">
                            <?php echo guvenli($durum); ?>
                        </span>
                    </p>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <p>Henüz sahiplenme başvurusu yapmadınız.</p>

        <?php endif; ?>

    </section>

</main>

</body>
</html>

<?php
mysqli_stmt_close($sorgu);
mysqli_stmt_close($sorgu2);
?>
