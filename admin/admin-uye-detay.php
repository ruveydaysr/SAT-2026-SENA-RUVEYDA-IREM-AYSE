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
        "SELECT id, hayvan_adi, ilan_basligi, tur, sehir, durum
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
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Üye Detayları | Admin Paneli</title>

    <link rel="stylesheet" href="admin-style.css?v=15">

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
</head>

<body>

<?php require __DIR__ . "/admin-menu.php"; ?>

<main class="icerik">

    <a href="admin-uyeler.php" class="uye-geri">
        ← Üye Listesine Dön
    </a>

    <?php if (!$uye): ?>

        <section class="kart">
            <h2>Üye bulunamadı</h2>
            <p>Bu numaraya ait bir üye bulunmuyor.</p>
        </section>

    <?php else: ?>

        <h2>
            <?php echo guvenli($uye["ad_soyad"]); ?>
            — Üye Detayları
        </h2>

        <section class="kart uye-bilgi">
            <h3>Üye Bilgileri</h3>

            <p>
                <strong>Üye ID:</strong>
                <?php echo (int)$uye["id"]; ?>
            </p>

            <p>
                <strong>Ad Soyad:</strong>
                <?php echo guvenli($uye["ad_soyad"]); ?>
            </p>

            <p>
                <strong>E-posta:</strong>
                <?php echo guvenli($uye["email"]); ?>
            </p>

            <p>
                <strong>Telefon:</strong>
                <?php echo guvenli($uye["telefon"]); ?>
            </p>
        </section>

        <div class="uye-ozet">
            <a href="#uye-ilanlar">
                <span>Verdiği İlanlar</span>
                <strong><?php echo count($ilanlar); ?></strong>
            </a>

            <a href="#uye-basvurular">
                <span>Sahiplenme Başvuruları</span>
                <strong><?php echo count($basvurular); ?></strong>
            </a>

            <a href="#uye-favoriler">
                <span>Favorileri</span>
                <strong><?php echo count($favoriler); ?></strong>
            </a>
        </div>

        <section id="uye-ilanlar" class="uye-bolum">

            <h2>Verdiği İlanlar</h2>

            <?php if (!$ilanlar): ?>

                <div class="kart">
                    <p>Bu üyenin verdiği ilan bulunmuyor.</p>
                </div>

            <?php else: ?>

                <div class="uye-detay-grid">

                    <?php foreach ($ilanlar as $ilan): ?>
                        <?php
                        $durum = (string)($ilan["durum"] ?? "");
                        $sinif = $durum_siniflari[$durum] ?? "";
                        ?>

                        <article class="uye-detay-kart">

                            <h3>
                                <?php echo guvenli($ilan["hayvan_adi"] ?? ""); ?>
                            </h3>

                            <p>
                                <strong>İlan ID:</strong>
                                <?php echo (int)$ilan["id"]; ?>
                            </p>

                            <p>
                                <strong>Başlık:</strong>
                                <?php echo guvenli($ilan["ilan_basligi"] ?? ""); ?>
                            </p>

                            <p>
                                <strong>Tür:</strong>
                                <?php echo guvenli($ilan["tur"] ?? "-"); ?>
                            </p>

                            <p>
                                <strong>Şehir:</strong>
                                <?php echo guvenli($ilan["sehir"] ?? "-"); ?>
                            </p>

                            <p>
                                <strong>Durum:</strong>
                                <span class="uye-durum <?php echo guvenli($sinif); ?>">
                                    <?php echo guvenli($durum); ?>
                                </span>
                            </p>

                            <a
                                href="../ilan-detay.php?id=<?php echo (int)$ilan['id']; ?>"
                                class="uye-incele"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                İlanı İncele
                            </a>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

        <section id="uye-basvurular" class="uye-bolum">

            <h2>Sahiplenme Başvuruları</h2>

            <?php if (!$basvurular): ?>

                <div class="kart">
                    <p>Bu üyenin sahiplenme başvurusu bulunmuyor.</p>
                </div>

            <?php else: ?>

                <div class="uye-detay-grid">

                    <?php foreach ($basvurular as $basvuru): ?>
                        <?php
                        $durum = (string)($basvuru["durum"] ?? "");
                        $sinif = $durum_siniflari[$durum] ?? "";
                        $ev = $evler[$basvuru["ev"] ?? ""] ?? "-";
                        ?>

                        <article class="uye-detay-kart">

                            <h3>
                                Başvuru #<?php echo (int)$basvuru["id"]; ?>
                            </h3>

                            <p>
                                <strong>Hayvan:</strong>
                                <?php echo guvenli($basvuru["hayvan_adi"] ?? "İlan silinmiş"); ?>
                            </p>

                            <p>
                                <strong>Tür:</strong>
                                <?php echo guvenli($basvuru["tur"] ?? "-"); ?>
                            </p>

                            <p>
                                <strong>Başvuru Tarihi:</strong>
                                <?php echo guvenli($basvuru["basvuru_tarihi"] ?? ""); ?>
                            </p>

                            <p>
                                <strong>Yaşadığı Yer:</strong>
                                <?php echo guvenli($ev); ?>
                            </p>

                            <p>
                                <strong>Durum:</strong>
                                <span class="uye-durum <?php echo guvenli($sinif); ?>">
                                    <?php echo guvenli($durum); ?>
                                </span>
                            </p>

                            <p><strong>Ek Bilgi:</strong></p>

                            <div class="uye-ek-bilgi"><?php echo guvenli($basvuru["mesaj"] ?? ""); ?></div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

        <section id="uye-favoriler" class="uye-bolum">

            <h2>Favorileri</h2>

            <?php if (!$favoriler): ?>

                <div class="kart">
                    <p>Bu üyenin favori kaydı bulunmuyor.</p>
                </div>

            <?php else: ?>

                <div class="uye-detay-grid">

                    <?php foreach ($favoriler as $favori): ?>
                        <?php
                        $durum = (string)($favori["durum"] ?? "");
                        $sinif = $durum_siniflari[$durum] ?? "";
                        ?>

                        <article class="uye-detay-kart">

                            <h3>
                                <?php echo guvenli($favori["hayvan_adi"] ?? "İlan silinmiş"); ?>
                            </h3>

                            <p>
                                <strong>Tür:</strong>
                                <?php echo guvenli($favori["tur"] ?? "-"); ?>
                            </p>

                            <p>
                                <strong>Şehir:</strong>
                                <?php echo guvenli($favori["sehir"] ?? "-"); ?>
                            </p>

                            <?php if ($favori["ilan_id"] !== null): ?>

                                <p>
                                    <strong>İlan Durumu:</strong>
                                    <span class="uye-durum <?php echo guvenli($sinif); ?>">
                                        <?php echo guvenli($durum); ?>
                                    </span>
                                </p>

                                <a
                                    href="../ilan-detay.php?id=<?php echo (int)$favori['ilan_id']; ?>"
                                    class="uye-incele"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    İlanı İncele
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