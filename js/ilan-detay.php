<?php
session_start();
require_once("baglanti.php");

function guvenli($deger)
{
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

$admin_mi = isset($_SESSION["admin"]);
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

$ilan = null;
$gecerli_fotograf = false;
$dosya_adi = "";

if ($id !== false && $id !== null && $id > 0) {
    if ($admin_mi) {
        $sql = "SELECT * FROM ilanlar WHERE id = ? LIMIT 1";
    } else {
        $sql = "SELECT * FROM ilanlar
                WHERE id = ? AND durum = 'Onaylandı'
                LIMIT 1";
    }

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
    $dosya_adi = basename((string)($ilan["fotograf"] ?? ""));

    $gecerli_fotograf =
        preg_match(
            '/^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$/i',
            $dosya_adi
        ) === 1
        && is_file(__DIR__ . "/uploads/" . $dosya_adi);
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
    <?php
    echo $ilan
        ? guvenli(
            !empty($ilan["ilan_basligi"])
                ? $ilan["ilan_basligi"]
                : $ilan["hayvan_adi"]
        ) . " - İlan Detayı"
        : "İlan Bulunamadı";
    ?>
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

        .hayvan-fotograf {
            max-width: 100%;
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
            overflow-wrap: anywhere;
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

        .admin-inceleme {
            margin: 25px 0;
            padding: 24px;
            border: 1px solid #dce6dc;
            border-radius: 12px;
            background: #f4f8f5;
            color: #205843;
            overflow-wrap: anywhere;
        }

        .admin-inceleme h2 {
            margin-top: 0;
        }

        .admin-islemler {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin: 20px 0;
        }

        .admin-islemler form {
            margin: 0;
        }

        .admin-inceleme button,
        .admin-geri {
            display: inline-block;
            padding: 11px 20px;
            border: 0;
            border-radius: 7px;
            background: #34794b;
            color: white;
            text-decoration: none;
            font: inherit;
            cursor: pointer;
        }

        .admin-inceleme button:hover,
        .admin-geri:hover {
            background: #285f3a;
        }

        .admin-inceleme .sil-buton,
        .admin-inceleme .reddet-buton {
            background: #ae4640;
        }

        .admin-inceleme .sil-buton:hover,
        .admin-inceleme .reddet-buton:hover {
            background: #913831;
        }

        .admin-ret-formu label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .admin-ret-formu textarea {
            box-sizing: border-box;
            display: block;
            width: 100%;
            min-height: 100px;
            margin-bottom: 14px;
            padding: 12px;
            border: 1px solid #d1dfd1;
            border-radius: 7px;
            background: white;
            color: #205843;
            font: inherit;
            resize: vertical;
        }

        body.detay-sayfasi a.admin-geri,
        body.detay-sayfasi a.admin-geri:visited {
            background: #34794b;
            color: #ffffff;
            text-decoration: none;
        }

        body.detay-sayfasi a.admin-geri:hover {
            background: #285f3a;
            color: #ffffff;
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
        <?php if ($admin_mi): ?>

            <a href="admin/admin-genel-bakis.php">Genel Bakış</a>
            <a href="admin/admin-panel.php">İlan Yönetimi</a>

            <a
                href="index.php"
                onclick="return confirm('Siteye gitmek istediğinize emin misiniz?');"
            >
                Siteye Git
            </a>

            <a
                href="admin/admin-cikis.php"
                onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');"
            >
                Çıkış Yap
            </a>

        <?php else: ?>

            <a href="index.php">Ana Sayfa</a>
            <a href="ilanlar.php">İlanlar</a>
            <a href="ilan-ver.php">İlan Ver</a>
            <a href="hakkimizda.php">Hakkımızda</a>
            <a href="iletisim.php">İletişim</a>

            <?php if (isset($_SESSION["uye_id"])): ?>
                <a href="profil.php">
                    <?php echo guvenli($_SESSION["uye_adsoyad"] ?? "Profilim"); ?>
                </a>

                <a
                    href="cikis.php"
                    onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');"
                >
                    Çıkış Yap
                </a>
            <?php else: ?>
                <a href="giris.php">Giriş Yap</a>
            <?php endif; ?>

        <?php endif; ?>
    </nav>
</header>

<main>

<?php if ($ilan): ?>

    <?php if ($admin_mi): ?>
        <div class="admin-inceleme">
            <strong>Admin İlan İncelemesi</strong>

            <p>
                İlanın fotoğrafını, bilgilerini ve açıklamasını
                kontrol ederek aşağıdaki yönetim işlemlerini
                kullanabilirsiniz.
            </p>

            <p>
                <strong>İlan Numarası:</strong>
                <?php echo (int)$ilan["id"]; ?>
            </p>

            <p>
                <strong>İlan Durumu:</strong>
                <?php echo guvenli($ilan["durum"] ?? ""); ?>
            </p>
        </div>
    <?php endif; ?>

    <section class="hayvan-detay">
        <div class="hayvan-fotograf">
            <?php if ($gecerli_fotograf): ?>
                <img
                    src="uploads/<?php echo guvenli($dosya_adi); ?>"
                    alt="<?php echo guvenli($ilan["hayvan_adi"]); ?>"
                >
            <?php else: ?>
                <div class="detay-foto-yok">Fotoğraf Yok</div>
            <?php endif; ?>
        </div>

        <div class="hayvan-bilgileri">

    <h2>
        <?php
        echo guvenli(
            !empty($ilan["ilan_basligi"])
                ? $ilan["ilan_basligi"]
                : $ilan["hayvan_adi"]
        );
        ?>
    </h2>

    <p>
        <strong>Hayvanın Adı:</strong>
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
        </div>
    </section>

    <section class="karakter">
        <h2>Hayvan Hakkında</h2>
        <p class="aciklama-metni"><?php echo guvenli($ilan["aciklama"]); ?></p>
    </section>

    <?php if ($admin_mi): ?>

        <section class="admin-inceleme">
            <h2>İlan Yönetim İşlemleri</h2>

            <?php if (
                ($ilan["durum"] ?? "") === "Reddedildi"
                && !empty($ilan["red_nedeni"])
            ): ?>
                <p>
                    <strong>Reddedilme Nedeni:</strong>
                    <?php echo guvenli($ilan["red_nedeni"]); ?>
                </p>
            <?php endif; ?>

            <div class="admin-islemler">

                <?php if (($ilan["durum"] ?? "") === "Bekliyor"): ?>
                    <form
                        action="admin/onayla.php"
                        method="POST"
                        onsubmit="return confirm('Bu ilanı onaylamak istediğinize emin misiniz?');"
                    >
                        <input
                            type="hidden"
                            name="id"
                            value="<?php echo (int)$ilan["id"]; ?>"
                        >

                        <button type="submit">İlanı Onayla</button>
                    </form>
                <?php endif; ?>

                <form
                    action="admin/sil.php"
                    method="POST"
                    onsubmit="return confirm('Bu ilanı silmek istediğinize emin misiniz? Bu işlem geri alınamaz.');"
                >
                    <input
                        type="hidden"
                        name="id"
                        value="<?php echo (int)$ilan["id"]; ?>"
                    >

                    <button type="submit" class="sil-buton">
                        İlanı Sil
                    </button>
                </form>

            </div>

            <?php if (($ilan["durum"] ?? "") === "Bekliyor"): ?>
                <form
                    action="admin/reddet.php"
                    method="POST"
                    class="admin-ret-formu"
                    onsubmit="return confirm('Bu ilanı reddetmek istediğinize emin misiniz?');"
                >
                    <input
                        type="hidden"
                        name="id"
                        value="<?php echo (int)$ilan["id"]; ?>"
                    >

                    <label for="red_nedeni">Reddetme Nedeni:</label>

                    <textarea
                        id="red_nedeni"
                        name="red_nedeni"
                        placeholder="İlanı neden uygun bulmadığınızı yazınız..."
                        required
                    ></textarea>

                    <button type="submit" class="reddet-buton">
                        İlanı Reddet
                    </button>
                </form>
            <?php endif; ?>

        </section>

    <?php elseif (($ilan["durum"] ?? "") === "Onaylandı"): ?>

        <section class="sahiplenme">
            <h2>
                <?php echo guvenli($ilan["hayvan_adi"]); ?>
                için Yuva Olmak İster misiniz?
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

    <?php endif; ?>

<?php else: ?>

    <section class="ilan-baslik">
        <h2>İlan Bulunamadı</h2>

        <p>
            <?php if ($admin_mi): ?>
                Aradığınız ilan bulunamadı veya silinmiş.
            <?php else: ?>
                Aradığınız ilan bulunamadı veya henüz
                yönetici tarafından onaylanmadı.
            <?php endif; ?>
        </p>
    </section>

<?php endif; ?>

    <p>
        <?php if ($admin_mi): ?>
            <a href="admin/admin-panel.php" class="admin-geri">
                ← İlan Yönetimine Dön
            </a>
        <?php else: ?>
            <a href="ilanlar.php">← İlanlara Geri Dön</a>
        <?php endif; ?>
    </p>

</main>

<footer>
    <p>Hayvan Sahiplendirme Sistemi - 2026</p>
</footer>

</body>
</html>