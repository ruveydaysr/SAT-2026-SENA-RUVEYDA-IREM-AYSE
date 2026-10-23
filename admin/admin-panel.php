<?php
session_start();

if (!isset($_SESSION["admin"])) {
    header("Location: admin-giris.php");
    exit();
}

require_once __DIR__ . "/../baglanti.php";

function guvenli($deger)
{
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

$kategoriler = [
    "Kedi", "Köpek", "Kuş", "Balık",
    "Tavşan", "Kemirgen", "Sürüngen", "Diğer"
];

$izinli_durumlar = ["Bekliyor", "Onaylandı", "Reddedildi"];

$kategori = is_string($_GET["kategori"] ?? null)
    ? trim($_GET["kategori"]) : "";

$durum = is_string($_GET["durum"] ?? null)
    ? trim($_GET["durum"]) : "";

$arama = is_string($_GET["arama"] ?? null)
    ? trim($_GET["arama"]) : "";

if (!in_array($kategori, $kategoriler, true)) {
    $kategori = "";
}

if (!in_array($durum, $izinli_durumlar, true)) {
    $durum = "";
}

// Filtreye uygun toplam ilan sayısı
$kosul = "WHERE (? = '' OR tur = ?)
          AND (? = '' OR durum = ?)
          AND (? = '' OR LOCATE(?, hayvan_adi) > 0)";

$sayim = mysqli_prepare(
    $baglanti,
    "SELECT COUNT(*) AS toplam FROM ilanlar " . $kosul
);

mysqli_stmt_bind_param(
    $sayim,
    "ssssss",
    $kategori,
    $kategori,
    $durum,
    $durum,
    $arama,
    $arama
);

mysqli_stmt_execute($sayim);
$sayim_sonucu = mysqli_stmt_get_result($sayim);
$toplam_ilan = (int)mysqli_fetch_assoc($sayim_sonucu)["toplam"];
mysqli_stmt_close($sayim);

// Sayfalama
$sayfa_basi = 12;
$toplam_sayfa = max(1, (int)ceil($toplam_ilan / $sayfa_basi));

$sayfa = filter_input(INPUT_GET, "sayfa", FILTER_VALIDATE_INT);
$sayfa = min($toplam_sayfa, max(1, $sayfa ?: 1));

$baslangic = ($sayfa - 1) * $sayfa_basi;

$stmt = mysqli_prepare(
    $baglanti,
    "SELECT * FROM ilanlar " . $kosul . "
     ORDER BY id DESC
     LIMIT ? OFFSET ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "ssssssii",
    $kategori,
    $kategori,
    $durum,
    $durum,
    $arama,
    $arama,
    $sayfa_basi,
    $baslangic
);

mysqli_stmt_execute($stmt);
$ilanlar = mysqli_stmt_get_result($stmt);

// Sayfa bağlantılarında filtreleri koru
function sayfa_linki($numara, $kategori, $durum, $arama)
{
    return "admin-panel.php?" . http_build_query([
        "kategori" => $kategori,
        "durum" => $durum,
        "arama" => $arama,
        "sayfa" => $numara
    ], "", "&", PHP_QUERY_RFC3986);
}

$ilk_kayit = $toplam_ilan > 0 ? $baslangic + 1 : 0;
$son_kayit = min($baslangic + $sayfa_basi, $toplam_ilan);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>İlan Yönetimi | Aris Pati Yuvası</title>

    <link rel="stylesheet" href="admin-style.css?v=14">

    <style>
        .ilan-filtre input[type="search"] {
            width: 100%;
            min-width: 0;
            padding: 13px;
            border: 1px solid #ded6c6;
            border-radius: 8px;
            background: #fffdf8;
            color: #284b32;
            font: inherit;
        }

        .admin-ilan-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            align-items: start;
            gap: 16px;
        }

        .admin-ilan-grid > .kart {
            min-width: 0;
            margin: 0;
            padding: 12px;
            border: 1px solid #ded6c6;
            border-radius: 12px;
            background: #fffdf8;
            overflow: hidden;
        }

        .admin-ilan-grid .ilan-fotograf,
        .admin-ilan-grid .admin-foto-yok {
            width: 100%;
            height: auto;
            aspect-ratio: 1 / 1;
            margin: 0 0 12px;
            border-radius: 8px;
            object-fit: cover;
        }

        .admin-ilan-grid .admin-foto-yok {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            text-align: center;
        }

        .admin-ilan-grid .kart h3 {
            margin: 0 0 10px;
            font-size: 18px;
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .admin-ilan-grid .admin-ilan-bilgileri {
            display: grid;
            gap: 5px;
            margin-bottom: 10px;
        }

        .admin-ilan-grid .admin-ilan-bilgileri p {
            margin: 0;
            font-size: 13px;
            line-height: 1.5;
        }

        .admin-ilan-grid .durum {
            max-width: 100%;
            padding: 6px 9px;
            font-size: 11px;
        }

        .admin-ilan-grid .islem-alani {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 7px;
            margin-top: 12px;
        }

        .admin-ilan-grid .islem-alani > a {
            grid-column: 1 / -1;
        }

        .admin-ilan-grid .islem-alani form {
            min-width: 0;
            margin: 0;
        }

        .admin-ilan-grid .islem-alani button,
        .admin-ilan-grid .incele-buton,
        .admin-ilan-grid .ret-formu button {
            display: block;
            width: 100%;
            padding: 9px 5px;
            border-radius: 6px;
            font-size: 12px;
            line-height: 1.4;
            text-align: center;
            white-space: normal;
        }

        .admin-ilan-grid .admin-ret,
        .admin-ilan-grid .admin-red-nedeni {
            margin-top: 12px;
            font-size: 12px;
        }

        .admin-ilan-grid .ret-formu label {
            font-size: 12px;
        }

        .admin-ilan-grid .ret-formu textarea {
            min-height: 90px;
            padding: 8px;
            font-size: 13px;
        }

        nav.ilan-sayfalama {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin: 28px 0 0;
            padding: 0;
            border: 0;
            background: transparent;
        }

        .ilan-sayfalama a,
        .ilan-sayfalama span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            min-height: 42px;
            padding: 9px 12px;
            border: 1px solid #ded6c6;
            border-radius: 8px;
            background: #fffdf8;
            color: #284b32;
            font-size: 14px;
            text-decoration: none;
        }

        .ilan-sayfalama a:hover {
            background: #e6eddf;
        }

        .ilan-sayfalama [aria-current="page"] {
            border-color: #426b46;
            background: #426b46;
            color: #fff;
            font-weight: bold;
        }

        .ilan-sayfalama .sayfa-nokta {
            min-width: 20px;
            padding: 0;
            border: 0;
            background: transparent;
        }

        @media (max-width: 1000px) {
            .admin-ilan-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .admin-ilan-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .admin-ilan-grid > .kart {
                padding: 9px;
            }

            .admin-ilan-grid .kart h3 {
                font-size: 15px;
            }

            .admin-ilan-grid .admin-ilan-bilgileri p {
                font-size: 12px;
            }

            .admin-ilan-grid .islem-alani button,
            .admin-ilan-grid .incele-buton {
                font-size: 11px;
            }
        }
    </style>
</head>

<body>

<?php require __DIR__ . "/admin-menu.php"; ?>

<main class="icerik">

    <h2>İlan Yönetimi</h2>

    <?php if (isset($_SESSION["ilan_bildirimi"])): ?>
        <div class="kayit-bildirimi" role="status">
            <?php
            echo guvenli($_SESSION["ilan_bildirimi"]);
            unset($_SESSION["ilan_bildirimi"]);
            ?>
        </div>
    <?php endif; ?>

    <section class="kart">

        <form action="admin-panel.php" method="GET" class="ilan-filtre">
            <div>
                <label for="arama">Hayvan Adı</label>
                <input
                    type="search"
                    id="arama"
                    name="arama"
                    placeholder="Hayvan adı yazınız..."
                    value="<?php echo guvenli($arama); ?>"
                >
            </div>

            <div>
                <label for="kategori">Kategori</label>
                <select id="kategori" name="kategori">
                    <option value="">Tüm Kategoriler</option>

                    <?php foreach ($kategoriler as $secenek): ?>
                        <option
                            value="<?php echo guvenli($secenek); ?>"
                            <?php echo $kategori === $secenek ? "selected" : ""; ?>
                        >
                            <?php echo guvenli($secenek); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="durum">İlan Durumu</label>
                <select id="durum" name="durum">
                    <option value="">Tüm Durumlar</option>

                    <?php foreach ($izinli_durumlar as $secenek): ?>
                        <option
                            value="<?php echo guvenli($secenek); ?>"
                            <?php echo $durum === $secenek ? "selected" : ""; ?>
                        >
                            <?php echo guvenli($secenek); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit">Filtrele</button>
            <a href="admin-panel.php" class="filtre-temizle">Temizle</a>
        </form>

        <p>
            Bulunan ilan sayısı:
            <strong><?php echo $toplam_ilan; ?></strong>

            <?php if ($toplam_ilan > 0): ?>
                — <?php echo $ilk_kayit; ?>–<?php echo $son_kayit; ?>
                arası gösteriliyor.
            <?php endif; ?>
        </p>

    </section>

    <?php if ($toplam_ilan === 0): ?>
        <div class="kart">
            <p>Aramanıza ve seçtiğiniz filtrelere uygun ilan bulunamadı.</p>
        </div>
    <?php endif; ?>

    <div class="admin-ilan-grid">

        <?php while ($ilan = mysqli_fetch_assoc($ilanlar)): ?>

            <?php
            $ilan_id = (int)$ilan["id"];
            $ilan_durumu = (string)($ilan["durum"] ?? "");

            $dosya_adi = basename(
                (string)($ilan["fotograf"] ?? "")
            );

            $gecerli_fotograf =
                preg_match(
                    '/^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$/i',
                    $dosya_adi
                ) === 1
                && is_file(__DIR__ . "/../uploads/" . $dosya_adi);
            ?>

            <article class="kart">

                <?php if ($gecerli_fotograf): ?>
                    <img
                        src="../uploads/<?php echo guvenli($dosya_adi); ?>"
                        alt="<?php echo guvenli($ilan["hayvan_adi"] ?? "Hayvan fotoğrafı"); ?>"
                        class="ilan-fotograf"
                        loading="lazy"
                    >
                <?php else: ?>
                    <div class="admin-foto-yok">🐾 Fotoğraf Yok</div>
                <?php endif; ?>

                <h3><?php echo guvenli($ilan["hayvan_adi"] ?? ""); ?></h3>

                <div class="admin-ilan-bilgileri">
                    <p>
                        <strong>Tür:</strong>
                        <?php echo guvenli($ilan["tur"] ?? ""); ?>
                    </p>
                    <p>
                        <strong>Yaş:</strong>
                        <?php echo guvenli($ilan["yas"] ?? ""); ?>
                    </p>
                    <p>
                        <strong>Şehir:</strong>
                        <?php echo guvenli($ilan["sehir"] ?? ""); ?>
                    </p>
                </div>

                <span class="durum <?php echo guvenli($ilan_durumu); ?>">
                    <?php echo guvenli($ilan_durumu); ?>
                </span>

                <?php if (
                    $ilan_durumu === "Reddedildi"
                    && !empty($ilan["red_nedeni"])
                ): ?>
                    <details class="admin-red-nedeni">
                        <summary>Reddedilme Nedenini Gör</summary>
                        <p><?php echo guvenli($ilan["red_nedeni"]); ?></p>
                    </details>
                <?php endif; ?>

                <div class="islem-alani">
                    <a
                        href="../ilan-detay.php?id=<?php echo $ilan_id; ?>"
                        class="incele-buton"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        İlanı İncele
                    </a>

                    <?php if ($ilan_durumu === "Bekliyor"): ?>
                        <form
                            action="onayla.php"
                            method="POST"
                            onsubmit="return confirm('Bu ilanı onaylamak istediğinize emin misiniz?');"
                        >
                            <input type="hidden" name="id" value="<?php echo $ilan_id; ?>">
                            <button type="submit" class="onayla">Onayla</button>
                        </form>
                    <?php endif; ?>

                    <form
                        action="sil.php"
                        method="POST"
                        onsubmit="return confirm('Bu ilanı silmek istediğinize emin misiniz? Bu işlem geri alınamaz.');"
                    >
                        <input type="hidden" name="id" value="<?php echo $ilan_id; ?>">
                        <button type="submit" class="sil">Sil</button>
                    </form>
                </div>

                <?php if ($ilan_durumu === "Bekliyor"): ?>
                    <details class="admin-ret">
                        <summary>İlanı Reddet</summary>

                        <form
                            action="reddet.php"
                            method="POST"
                            class="ret-formu"
                            onsubmit="return confirm('Bu ilanı reddetmek istediğinize emin misiniz?');"
                        >
                            <input type="hidden" name="id" value="<?php echo $ilan_id; ?>">

                            <label for="red-<?php echo $ilan_id; ?>">
                                Reddetme Nedeni
                            </label>

                            <textarea
                                id="red-<?php echo $ilan_id; ?>"
                                name="red_nedeni"
                                placeholder="Reddetme nedenini yazınız..."
                                required
                            ></textarea>

                            <button type="submit" class="reddet">Reddet</button>
                        </form>
                    </details>
                <?php endif; ?>

            </article>

        <?php endwhile; ?>

    </div>

    <?php if ($toplam_sayfa > 1): ?>
        <nav class="ilan-sayfalama" aria-label="İlan sayfaları">

            <?php if ($sayfa > 1): ?>
                <a href="<?php echo guvenli(sayfa_linki($sayfa - 1, $kategori, $durum, $arama)); ?>">
                    ‹ Önceki
                </a>
            <?php endif; ?>

            <?php
            $numaralar = [1, $toplam_sayfa];

            for (
                $i = max(1, $sayfa - 2);
                $i <= min($toplam_sayfa, $sayfa + 2);
                $i++
            ) {
                $numaralar[] = $i;
            }

            $numaralar = array_unique($numaralar);
            sort($numaralar);
            $onceki = 0;
            ?>

            <?php foreach ($numaralar as $numara): ?>

                <?php if ($onceki > 0 && $numara > $onceki + 1): ?>
                    <span class="sayfa-nokta" aria-hidden="true">…</span>
                <?php endif; ?>

                <?php if ($numara === $sayfa): ?>
                    <span aria-current="page">
                        <?php echo $numara; ?>
                    </span>
                <?php else: ?>
                    <a href="<?php echo guvenli(sayfa_linki($numara, $kategori, $durum, $arama)); ?>">
                        <?php echo $numara; ?>
                    </a>
                <?php endif; ?>

                <?php $onceki = $numara; ?>

            <?php endforeach; ?>

            <?php if ($sayfa < $toplam_sayfa): ?>
                <a href="<?php echo guvenli(sayfa_linki($sayfa + 1, $kategori, $durum, $arama)); ?>">
                    Sonraki ›
                </a>
            <?php endif; ?>

        </nav>
    <?php endif; ?>

</main>

</body>
</html>

<?php mysqli_stmt_close($stmt); ?>