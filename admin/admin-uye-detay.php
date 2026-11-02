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
    return htmlspecialchars(
        (string) $deger,
        ENT_QUOTES,
        "UTF-8"
    );
}

function uye_ilan_fotografi($fotograf)
{
    $foto = trim((string) $fotograf);
    $dosya = basename($foto);
    $kok = dirname(__DIR__);

    if (
        preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$/i', $dosya)
        && is_file($kok . "/uploads/" . $dosya)
    ) {
        return "../uploads/" . $dosya;
    }

    if (
        preg_match('~^images/hayvanlar/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$~i', $foto)
        && is_file($kok . "/" . $foto)
    ) {
        return "../" . $foto;
    }

    return "";
}

function kayitlari_getir($baglanti, $sql, $uye_id)
{
    $sorgu = mysqli_prepare($baglanti, $sql);

    mysqli_stmt_bind_param($sorgu, "i", $uye_id);
    mysqli_stmt_execute($sorgu);

    $sonuc = mysqli_stmt_get_result($sorgu);
    $kayitlar = mysqli_fetch_all($sonuc, MYSQLI_ASSOC);

    mysqli_stmt_close($sorgu);

    return $kayitlar;
}

$uye_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$uye_id || $uye_id < 1) {
    header("Location: admin-uyeler.php");
    exit();
}

$uyeler = kayitlari_getir(
    $baglanti,
    "SELECT id, ad_soyad, email, telefon
     FROM uyeler
     WHERE id = ?
     LIMIT 1",
    $uye_id
);

$uye = $uyeler[0] ?? null;

if (!$uye) {
    http_response_code(404);
}

$ilanlar = [];
$basvurular = [];
$favoriler = [];

if ($uye) {
    $ilanlar = kayitlari_getir(
        $baglanti,
        "SELECT id, hayvan_adi, ilan_basligi, tur, sehir, durum, fotograf
         FROM ilanlar
         WHERE uye_id = ?
         ORDER BY id DESC",
        $uye_id
    );

    $basvurular = kayitlari_getir(
        $baglanti,
        "SELECT
            b.id,
            b.durum,
            b.basvuru_tarihi,
            b.ev,
            b.mesaj,
            i.hayvan_adi,
            i.tur
         FROM sahiplenme_basvurulari AS b
         LEFT JOIN ilanlar AS i ON b.ilan_id = i.id
         WHERE b.uye_id = ?
         ORDER BY b.id DESC",
        $uye_id
    );

    $favoriler = kayitlari_getir(
        $baglanti,
        "SELECT
            f.id AS favori_id,
            i.id AS ilan_id,
            i.hayvan_adi,
            i.tur,
            i.sehir,
            i.durum
         FROM favoriler AS f
         LEFT JOIN ilanlar AS i ON f.ilan_id = i.id
         WHERE f.uye_id = ?
         ORDER BY f.id DESC",
        $uye_id
    );
}

$durum_siniflari = [
    "Bekliyor" => "bekliyor",
    "Onaylandı" => "onaylandi",
    "Reddedildi" => "reddedildi"
];

$evler = [
    "apartman" => "Apartman",
    "mustakil" => "Müstakil Ev"
];
?>

<!DOCTYPE html>
<html lang="<?= admin_h($admin_dil) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= admin_h(admin_t("Üye Detayları | Admin Paneli")) ?>
    </title>

    <link rel="stylesheet" href="admin-style.css?v=20261102">

    <style>
        .uye-geri,
        .uye-incele {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 8px;
            background: #426b46;
            color: white;
            text-decoration: none;
        }

        .uye-geri {
            margin-bottom: 20px;
        }

        .uye-bilgi {
            margin-bottom: 24px;
        }

        .uye-bilgi p {
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .uye-ozet {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin: 24px 0;
        }

        .uye-ozet a {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 24px;
            border: 1px solid #ded6c6;
            border-top: 4px solid #80a86d;
            border-radius: 12px;
            background: #fffdf8;
            color: #284b32;
            text-align: center;
            text-decoration: none;
        }

        .uye-ozet strong {
            font-size: 30px;
        }

        .uye-bolum {
            margin-top: 32px;
        }

        .uye-detay-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            align-items: start;
            gap: 18px;
        }

        .uye-detay-kart {
            min-width: 0;
            padding: 22px;
            border: 1px solid #ded6c6;
            border-top: 4px solid #80a86d;
            border-radius: 14px;
            background: #fffdf8;
            color: #284b32;
            overflow-wrap: anywhere;
            box-shadow: 0 6px 20px rgba(40, 75, 50, 0.05);
        }

        .uye-ilan-fotografi {
            display: block;
            width: 100%;
            height: 190px;
            object-fit: contain;
            background: #f3eee3;
            border-radius: 10px;
            margin-bottom: 16px;
        }

        .uye-ilan-foto-yok {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #5c6d5b;
            font-size: 14px;
        }

        .uye-detay-kart h3 {
            margin: 0 0 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5dfd2;
            font-size: 19px;
        }

        .uye-detay-kart p {
            margin: 10px 0;
            font-size: 14px;
            line-height: 1.6;
        }

        .uye-durum {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 8px;
            background: #e6eddf;
            font-size: 13px;
            font-weight: bold;
        }

        .uye-durum.bekliyor {
            background: #fff0c8;
            color: #795a12;
        }

        .uye-durum.onaylandi {
            background: #e0efdf;
            color: #285b30;
        }

        .uye-durum.reddedildi {
            background: #f8dddd;
            color: #922f2f;
        }

        .uye-ek-bilgi {
            max-height: 180px;
            overflow-y: auto;
            padding: 12px;
            border-radius: 8px;
            background: #f5f0e6;
            white-space: pre-wrap;
            font-size: 14px;
            line-height: 1.6;
        }

        .uye-incele {
            margin-top: 14px;
            font-size: 13px;
        }

        .uye-ozet a:hover,
        .uye-geri:hover,
        .uye-incele:hover {
            filter: brightness(0.95);
        }

        .icerik a:focus-visible {
            outline: 2px solid #426b46;
            outline-offset: 3px;
        }

        @media (max-width: 1100px) {
            .uye-detay-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 650px) {
            .uye-detay-grid,
            .uye-ozet {
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

    <a href="admin-uyeler.php" class="uye-geri">
        <?= admin_h(admin_t("← Üye Listesine Dön")) ?>
    </a>

    <?php if (!$uye): ?>

        <section class="kart">
            <h2><?= admin_h(admin_t("Üye bulunamadı")) ?></h2>

            <p>
                <?= admin_h(admin_t(
                    "Bu numaraya ait bir üye bulunmuyor."
                )) ?>
            </p>
        </section>

    <?php else: ?>

        <h2>
            <?= guvenli($uye["ad_soyad"]) ?>
            <?= admin_h(admin_t("— Üye Detayları")) ?>
        </h2>

        <section class="kart uye-bilgi">
            <h3><?= admin_h(admin_t("Üye Bilgileri")) ?></h3>

            <p>
                <strong><?= admin_h(admin_t("Üye ID:")) ?></strong>
                <?= (int) $uye["id"] ?>
            </p>

            <p>
                <strong><?= admin_h(admin_t("Ad Soyad:")) ?></strong>
                <?= guvenli($uye["ad_soyad"]) ?>
            </p>

            <p>
                <strong><?= admin_h(admin_t("E-posta:")) ?></strong>
                <?= guvenli($uye["email"]) ?>
            </p>

            <p>
                <strong><?= admin_h(admin_t("Telefon:")) ?></strong>
                <?= guvenli($uye["telefon"]) ?>
            </p>
        </section>

        <div class="uye-ozet">
            <a href="#uye-ilanlar">
                <span>
                    <?= admin_h(admin_t("Verdiği İlanlar")) ?>
                </span>
                <strong><?= count($ilanlar) ?></strong>
            </a>

            <a href="#uye-basvurular">
                <span>
                    <?= admin_h(admin_t("Sahiplenme Başvuruları")) ?>
                </span>
                <strong><?= count($basvurular) ?></strong>
            </a>

            <a href="#uye-favoriler">
                <span>
                    <?= admin_h(admin_t("Favorileri")) ?>
                </span>
                <strong><?= count($favoriler) ?></strong>
            </a>
        </div>

        <section id="uye-ilanlar" class="uye-bolum">
            <h2><?= admin_h(admin_t("Verdiği İlanlar")) ?></h2>

            <?php if (!$ilanlar): ?>

                <div class="kart">
                    <p>
                        <?= admin_h(admin_t(
                            "Bu üyenin verdiği ilan bulunmuyor."
                        )) ?>
                    </p>
                </div>

            <?php else: ?>

                <div class="uye-detay-grid">

                    <?php foreach ($ilanlar as $ilan): ?>
                        <?php
                        $durum = (string) ($ilan["durum"] ?? "");
                        $sinif = $durum_siniflari[$durum] ?? "";
                        $ilan_fotografi = uye_ilan_fotografi($ilan["fotograf"] ?? "");
                        ?>

                        <article class="uye-detay-kart">
                            <?php if ($ilan_fotografi !== ""): ?>
                                <a
                                    href="../ilan-detay.php?id=<?= (int) $ilan['id'] ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <img
                                        class="uye-ilan-fotografi"
                                        src="<?= guvenli($ilan_fotografi) ?>"
                                        alt="<?= guvenli($ilan["hayvan_adi"] ?? "") ?>"
                                        loading="lazy"
                                        decoding="async"
                                        width="320"
                                        height="190"
                                    >
                                </a>
                            <?php else: ?>
                                <div class="uye-ilan-fotografi uye-ilan-foto-yok">
                                    <?= guvenli($admin_dil === "en" ? "No photo added" : "Fotoğraf eklenmemiş") ?>
                                </div>
                            <?php endif; ?>
                            <h3>
                                <?= guvenli($ilan["hayvan_adi"] ?? "") ?>
                            </h3>

                            <p>
                                <strong>
                                    <?= admin_h(admin_t("İlan ID:")) ?>
                                </strong>
                                <?= (int) $ilan["id"] ?>
                            </p>

                            <p>
                                <strong>
                                    <?= admin_h(admin_t("Başlık:")) ?>
                                </strong>
                                <?= guvenli($ilan["ilan_basligi"] ?? "") ?>
                            </p>

                            <p>
                                <strong>
                                    <?= admin_h(admin_t("Tür:")) ?>
                                </strong>
                                <?= admin_h(admin_t($ilan["tur"] ?? "-")) ?>
                            </p>

                            <p>
                                <strong>
                                    <?= admin_h(admin_t("Şehir:")) ?>
                                </strong>
                                <?= guvenli($ilan["sehir"] ?? "-") ?>
                            </p>

                            <p>
                                <strong>
                                    <?= admin_h(admin_t("Durum:")) ?>
                                </strong>

                                <span class="uye-durum <?= guvenli($sinif) ?>">
                                    <?= admin_h(admin_t($durum)) ?>
                                </span>
                            </p>

                            <a
                                href="../ilan-detay.php?id=<?= (int) $ilan['id'] ?>"
                                class="uye-incele"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <?= admin_h(admin_t("İlanı İncele")) ?>
                            </a>
                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>
        </section>

        <section id="uye-basvurular" class="uye-bolum">
            <h2>
                <?= admin_h(admin_t("Sahiplenme Başvuruları")) ?>
            </h2>

            <?php if (!$basvurular): ?>

                <div class="kart">
                    <p>
                        <?= admin_h(admin_t(
                            "Bu üyenin sahiplenme başvurusu bulunmuyor."
                        )) ?>
                    </p>
                </div>

            <?php else: ?>

                <div class="uye-detay-grid">

                    <?php foreach ($basvurular as $basvuru): ?>
                        <?php
                        $durum = (string) ($basvuru["durum"] ?? "");
                        $sinif = $durum_siniflari[$durum] ?? "";
                        $ev = $evler[$basvuru["ev"] ?? ""] ?? "-";
                        ?>

                        <article class="uye-detay-kart">
                            <h3>
                                <?= admin_h(admin_t("Başvuru #")) ?><?= (int) $basvuru["id"] ?>
                            </h3>

                            <p>
                                <strong>
                                    <?= admin_h(admin_t("Hayvan:")) ?>
                                </strong>

                                <?= guvenli(
                                    $basvuru["hayvan_adi"]
                                    ?? admin_t("İlan silinmiş")
                                ) ?>
                            </p>

                            <p>
                                <strong>
                                    <?= admin_h(admin_t("Tür:")) ?>
                                </strong>

                                <?= admin_h(admin_t(
                                    $basvuru["tur"] ?? "-"
                                )) ?>
                            </p>

                            <p>
                                <strong>
                                    <?= admin_h(admin_t("Başvuru Tarihi:")) ?>
                                </strong>

                                <?= guvenli(
                                    $basvuru["basvuru_tarihi"] ?? ""
                                ) ?>
                            </p>

                            <p>
                                <strong>
                                    <?= admin_h(admin_t("Yaşadığı Yer:")) ?>
                                </strong>
                                <?= admin_h(admin_t($ev)) ?>
                            </p>

                            <p>
                                <strong>
                                    <?= admin_h(admin_t("Durum:")) ?>
                                </strong>

                                <span class="uye-durum <?= guvenli($sinif) ?>">
                                    <?= admin_h(admin_t($durum)) ?>
                                </span>
                            </p>

                            <p>
                                <strong>
                                    <?= admin_h(admin_t("Ek Bilgi:")) ?>
                                </strong>
                            </p>

                            <div class="uye-ek-bilgi"><?= guvenli($basvuru["mesaj"] ?? "") ?></div>
                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>
        </section>

        <section id="uye-favoriler" class="uye-bolum">
            <h2><?= admin_h(admin_t("Favorileri")) ?></h2>

            <?php if (!$favoriler): ?>

                <div class="kart">
                    <p>
                        <?= admin_h(admin_t(
                            "Bu üyenin favori kaydı bulunmuyor."
                        )) ?>
                    </p>
                </div>

            <?php else: ?>

                <div class="uye-detay-grid">

                    <?php foreach ($favoriler as $favori): ?>
                        <?php
                        $durum = (string) ($favori["durum"] ?? "");
                        $sinif = $durum_siniflari[$durum] ?? "";
                        ?>

                        <article class="uye-detay-kart">
                            <h3>
                                <?= guvenli(
                                    $favori["hayvan_adi"]
                                    ?? admin_t("İlan silinmiş")
                                ) ?>
                            </h3>

                            <p>
                                <strong>
                                    <?= admin_h(admin_t("Tür:")) ?>
                                </strong>

                                <?= admin_h(admin_t(
                                    $favori["tur"] ?? "-"
                                )) ?>
                            </p>

                            <p>
                                <strong>
                                    <?= admin_h(admin_t("Şehir:")) ?>
                                </strong>
                                <?= guvenli($favori["sehir"] ?? "-") ?>
                            </p>

                            <?php if ($favori["ilan_id"] !== null): ?>

                                <p>
                                    <strong>
                                        <?= admin_h(admin_t("İlan Durumu:")) ?>
                                    </strong>

                                    <span class="uye-durum <?= guvenli($sinif) ?>">
                                        <?= admin_h(admin_t($durum)) ?>
                                    </span>
                                </p>

                                <a
                                    href="../ilan-detay.php?id=<?= (int) $favori['ilan_id'] ?>"
                                    class="uye-incele"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <?= admin_h(admin_t("İlanı İncele")) ?>
                                </a>

                            <?php endif; ?>
                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>
        </section>

    <?php endif; ?>

</main>

</body>
</html>