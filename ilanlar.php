
<?php
session_start();
include("baglanti.php");

function guvenli($deger) {
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

$tur = trim($_GET["tur"] ?? "");
$sehir = trim($_GET["sehir"] ?? "");
$yas = trim($_GET["yas"] ?? "");
$cinsiyet = trim($_GET["cinsiyet"] ?? "");

$kosullar = ["durum = 'Onaylandı'"];
$parametreler = [];
$tipler = "";

if ($tur !== "") {
    $kosullar[] = "tur = ?";
    $parametreler[] = $tur;
    $tipler .= "s";
}

if ($sehir !== "") {
    $kosullar[] = "sehir = ?";
    $parametreler[] = $sehir;
    $tipler .= "s";
}

if ($cinsiyet !== "") {
    $kosullar[] = "cinsiyet = ?";
    $parametreler[] = $cinsiyet;
    $tipler .= "s";
}

if ($yas === "yavru") {
    $kosullar[] = "yas < 1";
} elseif ($yas === "genc") {
    $kosullar[] = "yas BETWEEN 1 AND 3";
} elseif ($yas === "yetiskin") {
    $kosullar[] = "yas > 3";
}

$sql = "SELECT * FROM ilanlar WHERE "
    . implode(" AND ", $kosullar)
    . " ORDER BY id DESC";

$sorgu = mysqli_prepare($baglanti, $sql);

if ($sorgu) {
    if (!empty($parametreler)) {
        mysqli_stmt_bind_param(
            $sorgu,
            $tipler,
            ...$parametreler
        );
    }

    mysqli_stmt_execute($sorgu);
    $sonuc = mysqli_stmt_get_result($sorgu);
} else {
    $sonuc = false;
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Hayvan İlanları</title>

    <link rel="stylesheet" href="css/style.css">
<style>
    /* YAN YANA İKİ İLAN */

    body.ilan-sayfasi .ilanlar {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        align-items: stretch;
        gap: 24px;
        width: 100%;
        margin: 28px 0;
    }

    body.ilan-sayfasi .ilanlar .ilan-karti {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 0;
        min-width: 0;
        width: auto;
        margin: 0;
        padding: 0;
        overflow: hidden;
        border: 1px solid #dce6dc;
        border-radius: 14px;
        background: white;
        box-shadow: 0 4px 16px rgba(30, 70, 45, 0.04);
    }

    body.ilan-sayfasi .ilan-karti .ilan-fotograf,
    body.ilan-sayfasi .ilan-karti .foto-yok {
        box-sizing: border-box;
        display: block;
        width: 100%;
        max-width: 100%;
        height: 220px;
        margin: 0;
        border-radius: 0;
        object-fit: cover;
        background: #eaf1eb;
    }

    body.ilan-sayfasi .ilan-karti .foto-yok {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #587362;
        font-size: 18px;
    }

    body.ilan-sayfasi .ilan-karti .ilan-bilgi {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-width: 0;
        padding: 22px;
        overflow-wrap: anywhere;
    }

    body.ilan-sayfasi .ilan-bilgi h3 {
        margin: 0 0 14px;
        color: #205843;
        font-size: 24px;
    }

    body.ilan-sayfasi .ilan-bilgi p {
        margin: 5px 0;
        color: #205843;
        line-height: 1.5;
    }

    body.ilan-sayfasi .ilan-bilgi .detay-buton {
        display: block;
        margin: auto 0 0;
        position: static;
        width: auto;
        padding: 12px 18px;
        border-radius: 8px;
        background: #34794b;
        color: white;
        text-align: center;
        text-decoration: none;
        font-size: 15px;
        font-weight: bold;
    }

    body.ilan-sayfasi .ilan-bilgi p:last-of-type {
        margin-bottom: 20px;
    }

    body.ilan-sayfasi .ilan-bilgi .detay-buton:hover {
        background: #285f3a;
    }

    body.ilan-sayfasi .ilanlar > p {
        grid-column: 1 / -1;
        padding: 24px;
        border: 1px solid #dce6dc;
        border-radius: 12px;
        background: white;
        color: #205843;
        text-align: center;
    }

    @media (max-width: 700px) {
        body.ilan-sayfasi .ilanlar {
            grid-template-columns: 1fr;
            gap: 18px;
        }

        body.ilan-sayfasi .ilan-karti .ilan-bilgi {
            padding: 18px;
        }
    }
</style>
</head>

<body class="ilan-sayfasi">


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

    <section class="ilan-baslik">
        <h2>Yuva Arayan Hayvanlar</h2>
        <p>Sana uygun dostunu bulmak için filtreleri kullanabilirsin.</p>
    </section>

    <!-- FİLTRELEME ALANI -->

    <section class="filtre-alani">

        <h3>Hayvan Ara</h3>

        <form action="ilanlar.php" method="GET">

            <div class="filtreler">

                <div>
                    <label for="tur">Hayvan Türü</label>

                    <select id="tur" name="tur">
                        <option value="">Tümü</option>

                        <?php
                        $turler = [
                            "Kedi", "Köpek", "Kuş",
                            "Tavşan", "Balık", "Diğer"
                        ];

                        foreach ($turler as $secenek):
                        ?>
                            <option
                                value="<?php echo guvenli($secenek); ?>"
                                <?php echo $tur === $secenek ? "selected" : ""; ?>
                            >
                                <?php echo guvenli($secenek); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="sehir">Şehir</label>

                    <select id="sehir" name="sehir">
                        <option value="">Tümü</option>

                        <?php
                        $sehirler = [
                            "Yozgat", "Ankara",
                            "İstanbul", "Kayseri"
                        ];

                        foreach ($sehirler as $secenek):
                        ?>
                            <option
                                value="<?php echo guvenli($secenek); ?>"
                                <?php echo $sehir === $secenek ? "selected" : ""; ?>
                            >
                                <?php echo guvenli($secenek); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="yas">Yaş</label>

                    <select id="yas" name="yas">
                        <option value="">Tümü</option>

                        <option value="yavru"
                            <?php echo $yas === "yavru" ? "selected" : ""; ?>>
                            1 yaşından küçük
                        </option>

                        <option value="genc"
                            <?php echo $yas === "genc" ? "selected" : ""; ?>>
                            1 - 3 yaş
                        </option>

                        <option value="yetiskin"
                            <?php echo $yas === "yetiskin" ? "selected" : ""; ?>>
                            3 yaşından büyük
                        </option>
                    </select>
                </div>

                <div>
                    <label for="cinsiyet">Cinsiyet</label>

                    <select id="cinsiyet" name="cinsiyet">
                        <option value="">Tümü</option>

                        <option value="Dişi"
                            <?php echo $cinsiyet === "Dişi" ? "selected" : ""; ?>>
                            Dişi
                        </option>

                        <option value="Erkek"
                            <?php echo $cinsiyet === "Erkek" ? "selected" : ""; ?>>
                            Erkek
                        </option>
                    </select>
                </div>

                <button type="submit">Filtrele</button>

            </div>

        </form>

    </section>

    <!-- İLANLAR -->

    <section class="ilanlar">

        <?php if ($sonuc && mysqli_num_rows($sonuc) > 0): ?>

            <?php while ($ilan = mysqli_fetch_assoc($sonuc)): ?>

                <div class="ilan-karti">

                    <?php
                    $fotograf = $ilan["fotograf"] ?? "";
                    $dosya_adi = basename($fotograf);

                    $gecerli_fotograf =
                        $fotograf !== "" &&
                        preg_match(
                            '/^[a-f0-9]{32}\.(jpg|png|webp)$/',
                            $dosya_adi
                        ) &&
                        is_file(
                            __DIR__ . "/uploads/" . $dosya_adi
                        );
                    ?>

                    <?php if ($gecerli_fotograf): ?>

                        <img
                            src="uploads/<?php echo guvenli($dosya_adi); ?>"
                            alt="<?php echo guvenli($ilan["hayvan_adi"]); ?>"
                            class="ilan-fotograf"
                        >

                    <?php else: ?>

                        <div class="foto-yok">
                            Fotoğraf Yok
                        </div>

                    <?php endif; ?>

                    <div class="ilan-bilgi">

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
                            <strong>Cinsiyet:</strong>
                            <?php echo guvenli($ilan["cinsiyet"]); ?>
                        </p>

                        <p>
                            <strong>Şehir:</strong>
                            <?php echo guvenli($ilan["sehir"]); ?>
                        </p>

                        <a
                            href="ilan-detay.php?id=<?php echo (int)$ilan["id"]; ?>"
                            class="detay-buton"
                        >
                            İlanı İncele
                        </a>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <p>Aradığınız kriterlere uygun onaylanmış ilan bulunmuyor.</p>

        <?php endif; ?>

    </section>

</main>

<footer>
    <p>Hayvan Sahiplendirme Sistemi - 2026</p>
</footer>

</body>
</html>

<?php
if ($sorgu) {
    mysqli_stmt_close($sorgu);
}
?>
