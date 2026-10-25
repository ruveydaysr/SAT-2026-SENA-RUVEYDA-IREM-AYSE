
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
$arama = trim($_GET["arama"] ?? "");
// İlan sıralama seçeneği
$siralama = $_GET["siralama"] ?? "yeni";

$izinli_siralamalar = ["yeni", "eski", "yas_artan", "yas_azalan"];

if (!is_string($siralama) || !in_array($siralama, $izinli_siralamalar, true)) {
    $siralama = "yeni";
}
//sayfalama ayarları
$ilan_sayisi = 8;

$sayfa = filter_input(INPUT_GET, "sayfa", FILTER_VALIDATE_INT);

if (!$sayfa || $sayfa < 1) {
    $sayfa = 1;
}

$baslangic = ($sayfa - 1) * $ilan_sayisi;


$kosullar = ["durum = 'Onaylandı'"];
$parametreler = [];
$tipler = "";
if ($arama !== "") {
    $kosullar[] = "ilan_basligi LIKE ?";
    $parametreler[] = "%" . $arama . "%";
    $tipler .= "s";
}

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

// Filtrelere uygun toplam ilan sayısını bul
$sayma_sql = "SELECT COUNT(*) AS toplam FROM ilanlar WHERE "
    . implode(" AND ", $kosullar);

$sayma_sorgu = mysqli_prepare($baglanti, $sayma_sql);

$toplam_ilan = 0;

if ($sayma_sorgu) {
    if (!empty($parametreler)) {
        mysqli_stmt_bind_param(
            $sayma_sorgu,
            $tipler,
            ...$parametreler
        );
    }

    mysqli_stmt_execute($sayma_sorgu);
    $sayma_sonuc = mysqli_stmt_get_result($sayma_sorgu);
    $toplam_ilan = (int) mysqli_fetch_assoc($sayma_sonuc)["toplam"];

    mysqli_stmt_close($sayma_sorgu);
}

$toplam_sayfa = (int) ceil($toplam_ilan / $ilan_sayisi);
// Seçilen sıralama türüne göre ilanları sırala
switch ($siralama) {
    case "eski":
        $siralama_sql = "id ASC";
        break;

    case "yas_artan":
        $siralama_sql = "yas ASC, id DESC";
        break;

    case "yas_azalan":
        $siralama_sql = "yas DESC, id DESC";
        break;

    default:
        $siralama_sql = "id DESC";
}
$sql = "SELECT * FROM ilanlar WHERE "
    . implode(" AND ", $kosullar)
    . " ORDER BY " . $siralama_sql . " LIMIT ? OFFSET ?";

$parametreler[] = $ilan_sayisi;
$parametreler[] = $baslangic;
$tipler .= "ii";

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
        object-fit: contain;
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
    
/* SAYFALAMA TASARIMI */

.sayfalama {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 10px;
    margin: 30px 0 50px;
}

.sayfalama a {
    display: inline-block;
    padding: 10px 16px;
    background-color: white;
    color: #347a53;
    border: 1px solid #cbded0;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
}

.sayfalama a:hover {
    background-color: #e6f1e8;
}

.sayfalama a.aktif {
    background-color: #347a53;
    color: white;
    border-color: #347a53;
}
/* Hayvan adı arama kutusu */
.filtreler input[type="text"] {
    padding: 10px;
    border: 1px solid #cbded0;
    border-radius: 8px;
    background-color: white;
    color: #205843;
    font-size: 14px;
    width: 100%;
    box-sizing: border-box;
}

.filtreler input[type="text"]:focus {
    outline: none;
    border-color: #347a53;
}
/* Filtreleme alanı */
body.ilan-sayfasi .filtreler {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

body.ilan-sayfasi .filtreler > div {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

body.ilan-sayfasi .filtreler select,
body.ilan-sayfasi .filtreler input {
    width: 100%;
    padding: 10px;
    border: 1px solid #c8d9cc;
    border-radius: 6px;
    box-sizing: border-box;
}

@media (max-width: 600px) {
    body.ilan-sayfasi .filtreler {
        grid-template-columns: 1fr;
    }
}
/* Favorilere ekle butonu */
.favori-buton {
    display: inline-block;
    background-color: #347a53;
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 7px;
    font-family: Arial, sans-serif;
    font-size: 14px;
    text-decoration: none;
    cursor: pointer;
    margin-top: 10px;
}

.favori-buton:hover {
    background-color: #245c3d;
}
/* Bilgisayarda yan yana 4 ilan */
body.ilan-sayfasi .ilanlar {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 24px;
}

/* Tablette yan yana 2 ilan */
@media (max-width: 1100px) {
    body.ilan-sayfasi .ilanlar {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

/* Telefonda tek ilan */
@media (max-width: 700px) {
    body.ilan-sayfasi .ilanlar {
        grid-template-columns: 1fr;
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
        <label for="arama">İlan Başlığı</label>
        <input
            type="text"
            id="arama"
            name="arama"
            placeholder="Örn: Yuva arayan yavru kedi"
            value="<?php echo guvenli($arama); ?>"
        >
    </div>

    <div>
        <label for="siralama">Sıralama</label>
    <select id="siralama" name="siralama">
        <option value="yeni" <?php if ($siralama == "yeni") echo "selected"; ?>>
            En Yeni
        </option>
        <option value="eski" <?php if ($siralama == "eski") echo "selected"; ?>>
            En Eski
        </option>
        <option value="yas_artan" <?php if ($siralama == "yas_artan") echo "selected"; ?>>
            Yaşı Küçükten Büyüğe
        </option>
        <option value="yas_azalan" <?php if ($siralama == "yas_azalan") echo "selected"; ?>>
            Yaşı Büyükten Küçüğe
        </option>
    </select>
</div>
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
                        
<p>
    <strong>Karakteristik Özellikler:</strong>
    <?php
    echo guvenli(
        $ilan["karakteristik_ozellikler"] ?? "Belirtilmemiş"
    );
    ?>
</p>


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
                <?php if ($ilan["tur"] === "Kedi"): ?>
    <img src="images/hayvanlar/kedi.jpg"
         alt="Temsili kedi fotoğrafı"
         class="ilan-fotograf">

<?php elseif ($ilan["tur"] === "Köpek"): ?>
    <img src="images/hayvanlar/kopek.jpg"
         alt="Temsili köpek fotoğrafı"
         class="ilan-fotograf">

<?php elseif ($ilan["tur"] === "Kuş"): ?>
    <img src="images/hayvanlar/kus.jpg"
         alt="Temsili kuş fotoğrafı"
         class="ilan-fotograf">

<?php else: ?>
    <div class="foto-yok">Fotoğraf Yok</div>
<?php endif; ?>
                    

                    <?php endif; ?>

                   <div class="ilan-bilgi"> 

    <h3>
        <?php echo guvenli($ilan["ilan_basligi"] ?: "Hayvan Sahiplendirme İlanı"); ?>
    </h3>
        <p>
                <strong>Hayvan Adı:</strong>
                <?php echo guvenli($ilan["hayvan_adi"]); ?>
        </p>

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
                    <?php if (isset($_SESSION["uye_id"])): ?>

    <form action="favori-islem.php" method="POST">
        <input
            type="hidden"
            name="ilan_id"
            value="<?php echo (int)$ilan["id"]; ?>"
        >

        <button type="submit" class="favori-buton">
            ♡ Favorilere Ekle
        </button>
    </form>

<?php else: ?>

    <a href="giris.php" class="favori-buton">
        ♡ Favorilere Ekle
    </a>

<?php endif; ?>
                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <p>Aradığınız kriterlere uygun onaylanmış ilan bulunmuyor.</p>

        <?php endif; ?>

    </section>

<?php if ($toplam_sayfa > 1): ?>

     <div class="sayfalama">

    <!-- Önceki sayfa -->
    <?php if ($sayfa > 1): ?>
        <?php
        $filtreler = $_GET;
        $filtreler["sayfa"] = $sayfa - 1;
        $onceki_link = "ilanlar.php?" . http_build_query($filtreler);
        ?>

        <a href="<?php echo guvenli($onceki_link); ?>">
            « Önceki
        </a>
    <?php endif; ?>


    <!-- Sayfa numaraları -->
    <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>

        <?php
        $filtreler = $_GET;
        $filtreler["sayfa"] = $i;
        $link = "ilanlar.php?" . http_build_query($filtreler);
        ?>

        <a href="<?php echo guvenli($link); ?>"
           class="<?php echo $sayfa == $i ? 'aktif' : ''; ?>">
            <?php echo $i; ?>
        </a>

    <?php endfor; ?>


    <!-- Sonraki sayfa -->
    <?php if ($sayfa < $toplam_sayfa): ?>
        <?php
        $filtreler = $_GET;
        $filtreler["sayfa"] = $sayfa + 1;
        $sonraki_link = "ilanlar.php?" . http_build_query($filtreler);
        ?>

        <a href="<?php echo guvenli($sonraki_link); ?>">
            Sonraki »
        </a>
    <?php endif; ?>

</div>
<?php endif; ?>

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
