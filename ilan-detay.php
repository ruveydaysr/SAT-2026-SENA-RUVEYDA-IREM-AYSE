<?php
session_start();
include("baglanti.php");

function guvenli($deger) {
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
$ilan = null;

if ($id !== false && $id !== null && $id > 0) {

    $sql = "SELECT * FROM ilanlar
            WHERE id = ? AND durum = 'Onaylandı'
            LIMIT 1";

    $sorgu = mysqli_prepare($baglanti, $sql);

    if ($sorgu) {
        mysqli_stmt_bind_param($sorgu, "i", $id);
        mysqli_stmt_execute($sorgu);

        $sonuc = mysqli_stmt_get_result($sorgu);
        $ilan = mysqli_fetch_assoc($sonuc);

        mysqli_stmt_close($sorgu);
    }
}

if ($ilan) {
    $fotograf = $ilan["fotograf"] ?? "";
    $dosya_adi = basename($fotograf);

    $gecerli_fotograf =
        $fotograf !== "" &&
        preg_match(
            '/^[a-f0-9]{32}\.(jpg|png|webp)$/',
            $dosya_adi
        ) &&
        is_file(__DIR__ . "/uploads/" . $dosya_adi);
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo $ilan ? guvenli($ilan["hayvan_adi"]) . " - İlan Detayı" : "İlan Bulunamadı"; ?>
    </title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        .hayvan-detay {
            display: flex;
            gap: 30px;
            align-items: flex-start;
            flex-wrap: wrap;
            margin: 30px 0;
        }

        .hayvan-fotograf img {
            width: 350px;
            max-width: 100%;
            height: 280px;
            object-fit: cover;
            border-radius: 12px;
        }

        .detay-foto-yok {
            width: 350px;
            max-width: 100%;
            height: 280px;
            background: #eeeeee;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777;
        }

        .hayvan-bilgileri {
            flex: 1;
            min-width: 220px;
        }

        .hayvan-bilgileri p {
            margin: 12px 0;
        }

        .karakter,
        .sahiplenme {
            margin: 25px 0;
        }

        .basvuru-buton {
            display: inline-block;
            margin-top: 12px;
        }

        .aciklama-metni {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }
    </style>
</head>
<body class="ilan-sayfasi detay-sayfasi">

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

        <?php if (isset($_SESSION["uye_id"])): ?>
            <a href="profil.php">
                <?php echo guvenli($_SESSION["uye_adsoyad"] ?? "Profilim"); ?>
            </a>
            <a href="cikis.php">Çıkış Yap</a>
        <?php else: ?>
            <a href="giris.php">Giriş Yap</a>
        <?php endif; ?>
    </nav>

</header>


<main>

<?php if ($ilan): ?>

    <section class="hayvan-detay">

        <div class="hayvan-fotograf">

            <?php if ($gecerli_fotograf): ?>

                <img
                    src="uploads/<?php echo guvenli($dosya_adi); ?>"
                    alt="<?php echo guvenli($ilan["hayvan_adi"]); ?>"
                >

            <?php else: ?>

                <div class="detay-foto-yok">
                    Fotoğraf Yok
                </div>

            <?php endif; ?>

        </div>

        <div class="hayvan-bilgileri">

            <h2>
                <?php echo guvenli($ilan["hayvan_adi"]); ?>
            </h2>

            <p>
                <strong>Tür:</strong>
                <?php echo guvenli($ilan["tur"]); ?>
            </p>

            <p>
                <strong>Yaş:</strong>
                <?php echo (int)$ilan["yas"]; ?>
            </p>

            <p>
                <strong>Cinsiyet:</strong>
                <?php echo guvenli($ilan["cinsiyet"]); ?>
            </p>

            <p>
                <strong>Şehir:</strong>
                <?php echo guvenli($ilan["sehir"]); ?>
            </p>

        </div>

    </section>

    <section class="karakter">

        <h2>Hayvan Hakkında</h2>

        <p class="aciklama-metni"><?php echo guvenli($ilan["aciklama"]); ?></p>

    </section>

    <section class="sahiplenme">

        <h2>
            <?php echo guvenli($ilan["hayvan_adi"]); ?>'a
            Yuva Olmak İster misiniz?
        </h2>

        <p>
            Bu hayvanı sahiplenmek için sahiplenme
            başvuru formunu doldurabilirsiniz.
        </p>

        <a
            href="sahiplen.php?ilan_id=<?php echo (int)$ilan["id"]; ?>"
            class="basvuru-buton"
        >
            Sahiplenme Başvurusu Yap
        </a>

    </section>

<?php else: ?>

    <section class="ilan-baslik">

        <h2>İlan Bulunamadı</h2>

        <p>
            Aradığınız ilan bulunamadı veya henüz
            yönetici tarafından onaylanmadı.
        </p>

    </section>

<?php endif; ?>

    <p>
        <a href="ilanlar.php">← İlanlara Geri Dön</a>
    </p>

</main>

<footer>
    <p>Hayvan Sahiplendirme Sistemi - 2026</p>
</footer>

</body>
</html>
