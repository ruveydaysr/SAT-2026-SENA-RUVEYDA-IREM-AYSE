
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
// Kullanıcının favorilerine eklediği ilanlar
$sql3 = "SELECT i.*
         FROM favoriler AS f
         INNER JOIN ilanlar AS i ON f.ilan_id = i.id
         WHERE f.uye_id = ?
         AND i.durum = 'Onaylandı'
         ORDER BY f.id DESC";

$sorgu3 = mysqli_prepare($baglanti, $sql3);
mysqli_stmt_bind_param($sorgu3, "i", $uye_id);
mysqli_stmt_execute($sorgu3);

$favoriler = mysqli_stmt_get_result($sorgu3);
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
    max-width: 950px;
    margin: 35px auto;
    padding: 30px;
    background-color: white;
    border: 1px solid #dce8df;
    border-radius: 16px;
}

.profil-bolum h2 {
    color: #184f3c;
    margin-bottom: 25px;
}

.profil-bolum > p {
    margin: 12px 0;
    color: #425b50;
}

.profil-kart {
    background-color: #f6faf7;
    border: 1px solid #dce8df;
    border-radius: 12px;
    padding: 22px;
    margin-bottom: 18px;
}

.profil-kart h3 {
    color: #1c6045;
    margin-top: 0;
}

.profil-kart p {
    margin: 12px 0;
}

.profil-fotograf {
    width: 180px;
    height: 140px;
    object-fit: cover;
    border-radius: 10px;
    margin-bottom: 12px;
}

.profil-kart a {
    display: inline-block;
    background-color: #347a53;
    color: white;
    padding: 9px 16px;
    border-radius: 7px;
    text-decoration: none;
    margin-top: 8px;
}

.profil-kart a:hover {
    background-color: #245c3d;
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

.profil-durum.reddedildi,
.red-nedeni {
    color: #d32f2f;
}

body {
    background-color: #f5f8f5;
}

main {
    padding: 25px;
}

@media (max-width: 600px) {
    .profil-bolum {
        padding: 18px;
    }

    main {
        padding: 10px;
    }
}
hr {
    display: none;
}
    /* Favorilerden çıkar butonu */
.favori-sil-buton {
    background-color: #347a53;
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 7px;
    font-size: 14px;
    cursor: pointer;
    margin-top: 10px;
}

.favori-sil-buton:hover {
    background-color: #245c3d;
}
    </style>
</head>

<body>

<header class="ilan-ust">
    <a href="index.php" class="ilan-logo">
        <span class="logo-pati">🐾</span>
        <span>
            <strong>Aris Pati Yuvası</strong>
            <small>Her can dostuna bir yuva</small>
        </span>
    </a>

    <nav class="ilan-menu">
        <a href="index.php">Ana Sayfa</a>
        <a href="ilanlar.php">İlanlar</a>
        <a href="ilan-ver.php">İlan Ver</a>
        <a href="hakkimizda.php">Hakkımızda</a>
        <a href="iletisim.php">İletişim</a>
        <a href="profil.php" class="aktif">Profilim</a>
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
<section class="profil-bolum">

    <h2>❤️ Favorilerim</h2>

    <?php if (mysqli_num_rows($favoriler) > 0): ?>

        <?php while ($favori = mysqli_fetch_assoc($favoriler)): ?>

            <div class="profil-kart">

                <h3>
                    <?php echo guvenli($favori["ilan_basligi"] ?: "Hayvan Sahiplendirme İlanı"); ?>
                </h3>

                <p>
                    <strong>Hayvan Adı:</strong>
                    <?php echo guvenli($favori["hayvan_adi"]); ?>
                </p>

                <p>
                    <strong>Tür:</strong>
                    <?php echo guvenli($favori["tur"]); ?>
                </p>

                <p>
                    <strong>Yaş:</strong>
                    <?php echo (int)$favori["yas"]; ?>
                </p>

                <p>
                    <strong>Şehir:</strong>
                    <?php echo guvenli($favori["sehir"]); ?>
                </p>

                <a href="ilan-detay.php?id=<?php echo (int)$favori["id"]; ?>">
                    İlanı İncele
                </a>
                <form action="favori-sil.php" method="POST">
    <input
        type="hidden"
        name="ilan_id"
        value="<?php echo (int)$favori["id"]; ?>"
    >
    <button type="submit" class="favori-sil-buton">
    ♡ Favorilerden Çıkar
</button>
</form>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <p>Henüz favorilerine eklediğin onaylı bir ilan bulunmuyor.</p>

    <?php endif; ?>

</section>
</main>

</body>
</html>

<?php
mysqli_stmt_close($sorgu);
mysqli_stmt_close($sorgu2);
mysqli_stmt_close($sorgu3);
?>
