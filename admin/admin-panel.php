<?php
session_start();

if (!isset($_SESSION["admin"])) {
    header("Location: admin-giris.php");
    exit();
}

require_once("../baglanti.php");

function guvenli($deger)
{
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

$kategoriler = [
    "Kedi", "Köpek", "Kuş", "Balık",
    "Tavşan", "Kemirgen", "Sürüngen", "Diğer"
];

$izinli_durumlar = ["Bekliyor", "Onaylandı", "Reddedildi"];

$kategori = isset($_GET["kategori"]) && is_string($_GET["kategori"])
    ? trim($_GET["kategori"])
    : "";

$durum = isset($_GET["durum"]) && is_string($_GET["durum"])
    ? $_GET["durum"]
    : "";

if (!in_array($kategori, $kategoriler, true)) {
    $kategori = "";
}

if (!in_array($durum, $izinli_durumlar, true)) {
    $durum = "";
}

$sql = "SELECT * FROM ilanlar
        WHERE (? = '' OR tur = ?)
        AND (? = '' OR durum = ?)
        ORDER BY id DESC";

$stmt = mysqli_prepare($baglanti, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ssss",
    $kategori,
    $kategori,
    $durum,
    $durum
);

mysqli_stmt_execute($stmt);
$sorgu = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli</title>

    <link rel="stylesheet" href="admin-style.css?v=9">

    <style>
        .admin-ilan-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: start;
            gap: 22px;
        }

        .admin-ilan-grid > .kart {
            min-width: 0;
            margin: 0;
            padding: 20px;
        }

        .admin-ilan-grid .ilan-fotograf,
        .admin-foto-yok {
            display: block;
            width: 100%;
            height: 200px;
            margin: 0 0 16px;
            border-radius: 10px;
            object-fit: cover;
            background: #eaf1eb;
        }

        .admin-foto-yok {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #587362;
            font-size: 18px;
        }

        .admin-ilan-grid .kart h3 {
            margin: 0 0 12px;
            font-size: 23px;
        }

        .admin-ilan-bilgileri {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 18px;
            margin-bottom: 12px;
        }

        .admin-ilan-grid .admin-ilan-bilgileri p {
            margin: 0;
            line-height: 1.5;
        }

        .admin-ilan-grid .islem-alani {
            gap: 10px;
            margin: 16px 0 0;
        }

        .incele-buton {
            display: inline-block;
            padding: 11px 20px;
            border-radius: 7px;
            background: #e7f2e9;
            color: #205843;
            text-decoration: none;
            font-size: 14px;
        }

        .incele-buton:hover {
            background: #d6e8da;
        }

        .admin-ret {
            margin-top: 16px;
            border-top: 1px solid #dce6dc;
            padding-top: 14px;
        }

        .admin-ret summary {
            color: #a63c35;
            font-weight: bold;
            cursor: pointer;
        }

        .admin-ret .ret-formu {
            border-top: 0;
            margin-top: 12px;
            padding-top: 0;
        }

        .admin-ret .ret-formu textarea {
            min-height: 75px;
        }

        .admin-red-nedeni {
            margin-top: 14px;
            padding: 12px;
            border-radius: 8px;
            background: #f9ecea;
            color: #a63c35;
        }

        .admin-red-nedeni summary {
            cursor: pointer;
        }

        .admin-red-nedeni p {
            white-space: pre-wrap;
        }

        @media (max-width: 700px) {
            .admin-ilan-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<header>
    <a
        href="../index.php"
        class="admin-logo"
        onclick="return confirm('Siteye gitmek istediğinize emin misiniz?');"
    >
        <span class="logo-pati">🐾</span>
        <span>
            <strong>Aris Pati Yuvası</strong>
            <small>Her can dostuna bir yuva</small>
        </span>
    </a>

    <h2>Admin Paneli</h2>
</header>

<nav class="admin-menu">
    <a href="admin-anasayfa.php">Ana Sayfa Yönetimi</a>
    <a href="admin-genel-bakis.php">Genel Bakış</a>
    <a href="admin-panel.php" aria-current="page">İlan Yönetimi</a>
    <a href="admin-basvurular.php">Sahiplenme Başvuruları</a>
    <a href="admin-uyeler.php">Üye Yönetimi</a>
    <a href="admin-mesajlar.php">Gelen Mesajlar</a>

    <a
        href="../index.php"
        onclick="return confirm('Siteye gitmek istediğinize emin misiniz?');"
    >
        Siteye Git
    </a>

    <a
        href="admin-cikis.php"
        class="cikis-buton"
        onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');"
    >
        Çıkış Yap
    </a>
</nav>

<main class="icerik">

    <h2>İlan Yönetimi</h2>

    <div class="kart">
        <form method="GET" action="admin-panel.php" class="ilan-filtre">

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
            <strong><?php echo mysqli_num_rows($sorgu); ?></strong>
        </p>
    </div>

    <?php if (mysqli_num_rows($sorgu) === 0): ?>
        <div class="kart">
            <p>Seçtiğiniz filtrelere uygun ilan bulunamadı.</p>
        </div>
    <?php endif; ?>

    <div class="admin-ilan-grid">

        <?php while ($ilan = mysqli_fetch_assoc($sorgu)): ?>

            <?php
            $ilan_id = (int)$ilan["id"];
            $ilan_durumu = $ilan["durum"] ?? "";

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
                        <?php echo (int)($ilan["yas"] ?? 0); ?>
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
                            <input
                                type="hidden"
                                name="id"
                                value="<?php echo $ilan_id; ?>"
                            >

                            <button type="submit" class="onayla">
                                Onayla
                            </button>
                        </form>
                    <?php endif; ?>

                    <form
                        action="sil.php"
                        method="POST"
                        onsubmit="return confirm('Bu ilanı silmek istediğinize emin misiniz? Bu işlem geri alınamaz.');"
                    >
                        <input
                            type="hidden"
                            name="id"
                            value="<?php echo $ilan_id; ?>"
                        >

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
                            <input
                                type="hidden"
                                name="id"
                                value="<?php echo $ilan_id; ?>"
                            >

                            <label for="red-<?php echo $ilan_id; ?>">
                                Reddetme Nedeni:
                            </label>

                            <textarea
                                id="red-<?php echo $ilan_id; ?>"
                                name="red_nedeni"
                                placeholder="Reddetme nedenini yazınız..."
                                required
                            ></textarea>

                            <button type="submit" class="reddet">
                                Reddet
                            </button>
                        </form>
                    </details>
                <?php endif; ?>

            </article>

        <?php endwhile; ?>

    </div>

</main>

</body>
</html>

<?php mysqli_stmt_close($stmt); ?>