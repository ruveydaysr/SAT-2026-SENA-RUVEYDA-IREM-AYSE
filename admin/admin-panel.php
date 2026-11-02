<?php
session_start();

require_once __DIR__ . "/admin-dil.php";

if (!isset($_SESSION["admin"])) {
    header("Location: admin-giris.php");
    exit();
}

require_once __DIR__ . "/../baglanti.php";

function guvenli($deger)
{
    return htmlspecialchars((string) $deger, ENT_QUOTES, "UTF-8");
}

function sayfa_linki($numara, $filtreler)
{
    $filtreler["sayfa"] = $numara;

    return "admin-panel.php?" . http_build_query(
        $filtreler,
        "",
        "&",
        PHP_QUERY_RFC3986
    );
}

$kategoriler = [
    "Kedi", "Köpek", "Kuş", "Balık",
    "Tavşan", "Kemirgen", "Sürüngen", "Diğer"
];

$izinli_durumlar = ["Bekliyor", "Onaylandı", "Reddedildi"];

$tarih_secenekleri = [
    "" => "Tüm tarihler",
    "bugun" => "Bugün",
    "7" => "Son 7 gün",
    "30" => "Son 30 gün"
];

$kategori = is_string($_GET["kategori"] ?? null)
    ? trim($_GET["kategori"]) : "";

$durum = is_string($_GET["durum"] ?? null)
    ? trim($_GET["durum"]) : "Onaylandı";

$tarih = is_string($_GET["tarih"] ?? null)
    ? $_GET["tarih"] : "";

if (!in_array($kategori, $kategoriler, true)) {
    $kategori = "";
}

if ($durum !== "" && !in_array($durum, $izinli_durumlar, true)) {
    $durum = "Onaylandı";
}

if (!array_key_exists($tarih, $tarih_secenekleri)) {
    $tarih = "";
}

$kosul = "WHERE (? = '' OR tur = ?)
          AND (? = '' OR durum = ?)";

if ($tarih === "bugun") {
    $kosul .= " AND ilan_tarihi >= CURDATE()
                AND ilan_tarihi < CURDATE() + INTERVAL 1 DAY";
} elseif ($tarih === "7") {
    $kosul .= " AND ilan_tarihi >= NOW() - INTERVAL 7 DAY
                AND ilan_tarihi <= NOW()";
} elseif ($tarih === "30") {
    $kosul .= " AND ilan_tarihi >= NOW() - INTERVAL 30 DAY
                AND ilan_tarihi <= NOW()";
}

$sayim = mysqli_prepare(
    $baglanti,
    "SELECT COUNT(*) AS toplam FROM ilanlar " . $kosul
);

mysqli_stmt_bind_param(
    $sayim,
    "ssss",
    $kategori,
    $kategori,
    $durum,
    $durum
);

mysqli_stmt_execute($sayim);

$sayim_sonucu = mysqli_stmt_get_result($sayim);
$toplam_ilan = (int) mysqli_fetch_assoc($sayim_sonucu)["toplam"];

mysqli_stmt_close($sayim);

$sayfa_basi = 10;
$toplam_sayfa = max(1, (int) ceil($toplam_ilan / $sayfa_basi));

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
    "ssssii",
    $kategori,
    $kategori,
    $durum,
    $durum,
    $sayfa_basi,
    $baslangic
);

mysqli_stmt_execute($stmt);
$ilanlar = mysqli_stmt_get_result($stmt);

$filtreler = [
    "kategori" => $kategori,
    "durum" => $durum,
    "tarih" => $tarih
];

$ilk_kayit = $toplam_ilan > 0 ? $baslangic + 1 : 0;
$son_kayit = min($baslangic + $sayfa_basi, $toplam_ilan);

$durum_siniflari = [
    "Bekliyor" => "durum-bekliyor",
    "Onaylandı" => "durum-onaylandi",
    "Reddedildi" => "durum-reddedildi"
];
?>

<!DOCTYPE html>
<html lang="<?= admin_h($admin_dil) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= admin_h(admin_t("İlan Yönetimi | Aris Pati Yuvası")) ?>
    </title>

    <link rel="stylesheet" href="admin-style.css?v=20261102">

    <style>
        .ilan-filtre {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: 12px;
        }

        .ilan-filtre > div {
            flex: 1 1 160px;
        }

        .ilan-filtre label {
            display: block;
            margin-bottom: 6px;
        }

        .ilan-filtre select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ded6c6;
            border-radius: 8px;
            background: #fffdf8;
            color: #284b32;
        }

        .ilan-filtre button,
        .ilan-filtre .filtre-temizle {
            padding: 10px 16px;
            border: 1px solid #426b46;
            border-radius: 8px;
            background: #426b46;
            color: white;
            font: inherit;
            text-decoration: none;
            cursor: pointer;
        }

        .ilan-filtre .filtre-temizle {
            background: #fffdf8;
            color: #284b32;
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
            background: #eeeadd;
            font-size: 14px;
            text-align: center;
        }

        .admin-ilan-grid .kart h3 {
            margin: 0 0 10px;
            font-size: 18px;
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .admin-ilan-bilgileri {
            display: grid;
            gap: 5px;
            margin-bottom: 10px;
        }

        .admin-ilan-bilgileri p {
            margin: 0;
            font-size: 13px;
            line-height: 1.5;
        }

        .admin-ilan-grid .durum {
            display: inline-block;
            max-width: 100%;
            padding: 6px 9px;
            border-radius: 6px;
            font-size: 11px;
        }

        .durum-onaylandi {
            background: #e0efdf;
            color: #285b30;
        }

        .durum-bekliyor {
            background: #fff0c8;
            color: #795a12;
        }

        .durum-reddedildi {
            background: #f8dddd;
            color: #922f2f;
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
            box-sizing: border-box;
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
            overflow-wrap: anywhere;
        }

        .admin-ilan-grid summary {
            cursor: pointer;
        }

        .admin-ilan-grid .ret-formu label {
            display: block;
            margin: 10px 0 6px;
            font-size: 12px;
        }

        .admin-ilan-grid .ret-formu textarea {
            box-sizing: border-box;
            width: 100%;
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
            box-sizing: border-box;
            margin: 0;
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
            color: white;
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
                gap: 10px;
            }

            .admin-ilan-grid > .kart {
                padding: 9px;
            }

            .admin-ilan-grid .kart h3 {
                font-size: 15px;
            }

            .admin-ilan-bilgileri p {
                font-size: 12px;
            }

            .admin-ilan-grid .islem-alani button,
            .admin-ilan-grid .incele-buton {
                font-size: 11px;
            }
        }

        @media (max-width: 380px) {
            .admin-ilan-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
<link rel="stylesheet" href="../site-ust.css?v=20261102-1">
<link rel="stylesheet" href="admin-ust.css?v=20261102-1">
</head>

<body>

<?php require __DIR__ . "/admin-menu.php"; ?>

<main class="icerik">

    <h2><?= admin_h(admin_t("İlan Yönetimi")) ?></h2>

    <?php if (isset($_SESSION["ilan_bildirimi"])): ?>
        <div class="kayit-bildirimi" role="status">
            <?php
            echo admin_h(admin_t($_SESSION["ilan_bildirimi"]));
            unset($_SESSION["ilan_bildirimi"]);
            ?>
        </div>
    <?php endif; ?>

    <section class="kart">
        <form action="admin-panel.php" method="GET" class="ilan-filtre">

            <div>
                <label for="kategori">
                    <?= admin_h(admin_t("Kategori")) ?>
                </label>

                <select id="kategori" name="kategori">
                    <option value="">
                        <?= admin_h(admin_t("Tüm Kategoriler")) ?>
                    </option>

                    <?php foreach ($kategoriler as $secenek): ?>
                        <option
                            value="<?= guvenli($secenek) ?>"
                            <?= $kategori === $secenek ? "selected" : "" ?>
                        >
                            <?= admin_h(admin_t($secenek)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="durum">
                    <?= admin_h(admin_t("İlan Durumu")) ?>
                </label>

                <select id="durum" name="durum">
                    <option
                        value=""
                        <?= $durum === "" ? "selected" : "" ?>
                    >
                        <?= admin_h(admin_t("Tüm Durumlar")) ?>
                    </option>

                    <?php foreach ($izinli_durumlar as $secenek): ?>
                        <option
                            value="<?= guvenli($secenek) ?>"
                            <?= $durum === $secenek ? "selected" : "" ?>
                        >
                            <?= admin_h(admin_t($secenek)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="tarih">
                    <?= admin_h(admin_t("İlan Tarihi")) ?>
                </label>

                <select id="tarih" name="tarih">
                    <?php foreach ($tarih_secenekleri as $deger => $yazi): ?>
                        <option
                            value="<?= guvenli($deger) ?>"
                            <?= $tarih === (string) $deger ? "selected" : "" ?>
                        >
                            <?= admin_h(admin_t($yazi)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit">
                <?= admin_h(admin_t("Filtrele")) ?>
            </button>

            <a href="admin-panel.php" class="filtre-temizle">
                <?= admin_h(admin_t("Temizle")) ?>
            </a>
        </form>

        <p>
            <?= admin_h(admin_t("Bulunan ilan sayısı:")) ?>
            <strong><?= $toplam_ilan ?></strong>

            <?php if ($toplam_ilan > 0): ?>
                — <?= $ilk_kayit ?>–<?= $son_kayit ?>
                <?= admin_h(admin_t("arası gösteriliyor.")) ?>
            <?php endif; ?>
        </p>

        <p>
            <?= admin_h(admin_t("Sayfa")) ?>
            <?= $sayfa ?> / <?= $toplam_sayfa ?>
        </p>
    </section>

    <?php if ($toplam_ilan === 0): ?>
        <div class="kart">
            <p>
                <?= admin_h(admin_t(
                    "Seçtiğiniz filtrelere uygun ilan bulunamadı."
                )) ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="admin-ilan-grid">

        <?php while ($ilan = mysqli_fetch_assoc($ilanlar)): ?>
            <?php
            $ilan_id = (int) $ilan["id"];
            $ilan_durumu = (string) ($ilan["durum"] ?? "");

            $durum_sinifi = $durum_siniflari[$ilan_durumu] ?? "";

            $dosya_adi = basename(
                (string) ($ilan["fotograf"] ?? "")
            );

            $gecerli_fotograf =
                preg_match(
                    '/^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$/i',
                    $dosya_adi
                ) === 1
                && is_file(__DIR__ . "/../uploads/" . $dosya_adi);

            $ilan_tarihi = (string) ($ilan["ilan_tarihi"] ?? "");

            $tarih_nesnesi = DateTimeImmutable::createFromFormat(
                "!Y-m-d H:i:s",
                $ilan_tarihi
            );

            $tarih_yazisi = (
                $tarih_nesnesi
                && $tarih_nesnesi->format("Y-m-d H:i:s") === $ilan_tarihi
            )
                ? $tarih_nesnesi->format("d.m.Y H:i")
                : admin_t("Tarih belirtilmemiş");
            ?>

            <article class="kart">

                <?php if ($gecerli_fotograf): ?>
                    <img
                        src="../uploads/<?= guvenli($dosya_adi) ?>"
                        alt="<?= guvenli($ilan['hayvan_adi'] ?? admin_t('Hayvan fotoğrafı')) ?>"
                        class="ilan-fotograf"
                        loading="lazy"
                    >
                <?php else: ?>
                    <div class="admin-foto-yok">
                        <?= admin_h(admin_t("🐾 Fotoğraf Yok")) ?>
                    </div>
                <?php endif; ?>

                <h3><?= guvenli($ilan["hayvan_adi"] ?? "") ?></h3>

                <div class="admin-ilan-bilgileri">
                    <p>
                        <strong><?= admin_h(admin_t("Tür:")) ?></strong>
                        <?= admin_h(admin_t($ilan["tur"] ?? "")) ?>
                    </p>

                    <p>
                        <strong><?= admin_h(admin_t("Yaş:")) ?></strong>
                        <?= guvenli($ilan["yas"] ?? "") ?>
                    </p>

                    <p>
                        <strong><?= admin_h(admin_t("Şehir:")) ?></strong>
                        <?= guvenli($ilan["sehir"] ?? "") ?>
                    </p>

                    <p>
                        <strong>
                            <?= admin_h(admin_t("İlan Tarihi:")) ?>
                        </strong>
                        <?= guvenli($tarih_yazisi) ?>
                    </p>
                </div>

                <span class="durum <?= guvenli($durum_sinifi) ?>">
                    <?= admin_h(admin_t($ilan_durumu)) ?>
                </span>

                <?php if (
                    $ilan_durumu === "Reddedildi"
                    && !empty($ilan["red_nedeni"])
                ): ?>
                    <details class="admin-red-nedeni">
                        <summary>
                            <?= admin_h(admin_t("Reddedilme Nedenini Gör")) ?>
                        </summary>

                        <p><?= guvenli($ilan["red_nedeni"]) ?></p>
                    </details>
                <?php endif; ?>

                <div class="islem-alani">
                    <a
                        href="../ilan-detay.php?id=<?= $ilan_id ?>"
                        class="incele-buton"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <?= admin_h(admin_t("İlanı İncele")) ?>
                    </a>

                    <?php if ($ilan_durumu === "Bekliyor"): ?>
                        <form
                            action="onayla.php"
                            method="POST"
                            onsubmit="return confirm(<?= admin_h(admin_json('Bu ilanı onaylamak istediğinize emin misiniz?')) ?>);"
                        >
                            <input
                                type="hidden"
                                name="id"
                                value="<?= $ilan_id ?>"
                            >

                            <button type="submit" class="onayla">
                                <?= admin_h(admin_t("Onayla")) ?>
                            </button>
                        </form>
                    <?php endif; ?>

                    <form
                        action="sil.php"
                        method="POST"
                        onsubmit="return confirm(<?= admin_h(admin_json('Bu ilanı silmek istediğinize emin misiniz? Bu işlem geri alınamaz.')) ?>);"
                    >
                        <input
                            type="hidden"
                            name="id"
                            value="<?= $ilan_id ?>"
                        >

                        <button type="submit" class="sil">
                            <?= admin_h(admin_t("Sil")) ?>
                        </button>
                    </form>
                </div>

                <?php if ($ilan_durumu === "Bekliyor"): ?>
                    <details class="admin-ret">
                        <summary>
                            <?= admin_h(admin_t("İlanı Reddet")) ?>
                        </summary>

                        <form
                            action="reddet.php"
                            method="POST"
                            class="ret-formu"
                            onsubmit="return confirm(<?= admin_h(admin_json('Bu ilanı reddetmek istediğinize emin misiniz?')) ?>);"
                        >
                            <input
                                type="hidden"
                                name="id"
                                value="<?= $ilan_id ?>"
                            >

                            <label for="red-<?= $ilan_id ?>">
                                <?= admin_h(admin_t("Reddetme Nedeni")) ?>
                            </label>

                            <textarea
                                id="red-<?= $ilan_id ?>"
                                name="red_nedeni"
                                placeholder="<?= admin_h(admin_t('Reddetme nedenini yazınız...')) ?>"
                                required
                            ></textarea>

                            <button type="submit" class="reddet">
                                <?= admin_h(admin_t("Reddet")) ?>
                            </button>
                        </form>
                    </details>
                <?php endif; ?>

            </article>

        <?php endwhile; ?>

    </div>

    <?php if ($toplam_sayfa > 1): ?>
        <nav
            class="ilan-sayfalama"
            aria-label="<?= admin_h(admin_t('İlan sayfaları')) ?>"
        >
            <?php if ($sayfa > 1): ?>
                <a href="<?= guvenli(sayfa_linki($sayfa - 1, $filtreler)) ?>">
                    <?= admin_h(admin_t("‹ Önceki")) ?>
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
                    <span aria-current="page"><?= $numara ?></span>
                <?php else: ?>
                    <a href="<?= guvenli(sayfa_linki($numara, $filtreler)) ?>">
                        <?= $numara ?>
                    </a>
                <?php endif; ?>

                <?php $onceki = $numara; ?>

            <?php endforeach; ?>

            <?php if ($sayfa < $toplam_sayfa): ?>
                <a href="<?= guvenli(sayfa_linki($sayfa + 1, $filtreler)) ?>">
                    <?= admin_h(admin_t("Sonraki ›")) ?>
                </a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

</main>

</body>
</html>

<?php mysqli_stmt_close($stmt); ?>