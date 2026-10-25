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

if (empty($_SESSION["basvuru_token"])) {
    $_SESSION["basvuru_token"] = bin2hex(random_bytes(32));
}

$izinli_durumlar = ["Bekliyor", "Onaylandı", "Reddedildi"];

$filtre = is_string($_GET["durum"] ?? null)
    ? trim($_GET["durum"])
    : "";

if (!in_array($filtre, $izinli_durumlar, true)) {
    $filtre = "";
}

$form_adresi = "admin-basvurular.php";

if ($filtre !== "") {
    $form_adresi .= "?durum=" . rawurlencode($filtre);
}

$mesaj = $_SESSION["basvuru_bildirimi"] ?? "";
unset($_SESSION["basvuru_bildirimi"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
    $islem = $_POST["islem"] ?? "";
    $token = $_POST["token"] ?? "";

    if (
        !is_string($token)
        || !hash_equals($_SESSION["basvuru_token"], $token)
    ) {
        $mesaj = "Sayfayı yenileyip tekrar deneyiniz.";

    } elseif (
        !$id
        || $id < 1
        || !in_array($islem, ["onayla", "reddet"], true)
    ) {
        $mesaj = "Geçersiz işlem.";

    } else {

        $yeni_durum = $islem === "onayla"
            ? "Onaylandı"
            : "Reddedildi";

        $guncelle = mysqli_prepare(
            $baglanti,
            "UPDATE sahiplenme_basvurulari
             SET durum = ?
             WHERE id = ? AND durum = 'Bekliyor'"
        );

        mysqli_stmt_bind_param($guncelle, "si", $yeni_durum, $id);

        if (mysqli_stmt_execute($guncelle)) {
            $mesaj = mysqli_stmt_affected_rows($guncelle) > 0
                ? "Başvuru durumu güncellendi."
                : "Başvuru bulunamadı veya zaten değerlendirilmiş.";
        } else {
            $mesaj = "İşlem sırasında hata oluştu.";
        }

        mysqli_stmt_close($guncelle);
    }

    $_SESSION["basvuru_bildirimi"] = $mesaj;

    header("Location: " . $form_adresi, true, 303);
    exit();
}

$sql = "SELECT
            b.id,
            b.adsoyad,
            b.telefon,
            b.email,
            b.ev,
            b.mesaj,
            b.durum,
            b.basvuru_tarihi,
            i.hayvan_adi,
            i.tur
        FROM sahiplenme_basvurulari AS b
        LEFT JOIN ilanlar AS i ON b.ilan_id = i.id
        WHERE (? = '' OR b.durum = ?)
        ORDER BY b.id DESC";

$stmt = mysqli_prepare($baglanti, $sql);
mysqli_stmt_bind_param($stmt, "ss", $filtre, $filtre);
mysqli_stmt_execute($stmt);

$sonuc = mysqli_stmt_get_result($stmt);
$toplam_basvuru = mysqli_num_rows($sonuc);

$evler = [
    "apartman" => "Apartman",
    "mustakil" => "Müstakil Ev"
];

$durum_siniflari = [
    "Bekliyor" => "bekliyor",
    "Onaylandı" => "onaylandi",
    "Reddedildi" => "reddedildi"
];
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sahiplenme Başvuruları | Admin Paneli</title>

    <link rel="stylesheet" href="admin-style.css?v=18">
</head>

<body>

<?php require __DIR__ . "/admin-menu.php"; ?>

<main class="icerik basvurular-sayfasi">

    <h2>Sahiplenme Başvuruları</h2>

    <?php if ($mesaj !== ""): ?>
        <p class="basvuru-mesaj" role="status">
            <?php echo guvenli($mesaj); ?>
        </p>
    <?php endif; ?>

    <section class="kart">

        <form
            action="admin-basvurular.php"
            method="GET"
            class="ilan-filtre"
        >
            <div>
                <label for="durum">Başvuru Durumu</label>

                <select id="durum" name="durum">
                    <option value="">Tüm Başvurular</option>

                    <?php foreach ($izinli_durumlar as $secenek): ?>
                        <option
                            value="<?php echo guvenli($secenek); ?>"
                            <?php echo $filtre === $secenek ? "selected" : ""; ?>
                        >
                            <?php echo guvenli($secenek); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit">Filtrele</button>

            <a href="admin-basvurular.php" class="filtre-temizle">
                Temizle
            </a>
        </form>

        <p>
            Bulunan başvuru sayısı:
            <strong><?php echo $toplam_basvuru; ?></strong>
        </p>

    </section>

    <?php if ($toplam_basvuru === 0): ?>

        <div class="kart">
            <p>
                <?php
                echo $filtre !== ""
                    ? "Seçilen duruma uygun başvuru bulunamadı."
                    : "Henüz sahiplenme başvurusu bulunmuyor.";
                ?>
            </p>
        </div>

    <?php else: ?>

        <div class="basvuru-grid">

            <?php while ($basvuru = mysqli_fetch_assoc($sonuc)): ?>
                <?php
                $basvuru_id = (int)$basvuru["id"];
                $durum = (string)($basvuru["durum"] ?? "");
                $sinif = $durum_siniflari[$durum] ?? "";
                $ev = $evler[$basvuru["ev"] ?? ""] ?? "-";
                ?>

                <article class="basvuru-karti">

                    <h3>Başvuru #<?php echo $basvuru_id; ?></h3>

                    <p>
                        <strong>Hayvan:</strong>
                        <?php echo guvenli($basvuru["hayvan_adi"] ?? "İlan silinmiş"); ?>
                    </p>

                    <p>
                        <strong>Tür:</strong>
                        <?php echo guvenli($basvuru["tur"] ?? "-"); ?>
                    </p>

                    <p>
                        <strong>Başvuran:</strong>
                        <?php echo guvenli($basvuru["adsoyad"] ?? ""); ?>
                    </p>

                    <p>
                        <strong>Telefon:</strong>
                        <?php echo guvenli($basvuru["telefon"] ?? ""); ?>
                    </p>

                    <p>
                        <strong>E-posta:</strong>
                        <?php echo guvenli($basvuru["email"] ?? ""); ?>
                    </p>

                    <p>
                        <strong>Yaşadığı Yer:</strong>
                        <?php echo guvenli($ev); ?>
                    </p>

                    <button
                        type="button"
                        class="ek-bilgi-buton"
                        onclick="document.getElementById('ek-bilgi-<?php echo $basvuru_id; ?>').showModal();"
                    >
                        Ek Bilgiyi Gör
                    </button>

                    <dialog
                        id="ek-bilgi-<?php echo $basvuru_id; ?>"
                        class="ek-bilgi-pencere"
                        aria-labelledby="ek-bilgi-baslik-<?php echo $basvuru_id; ?>"
                    >
                        <div class="ek-bilgi-ust">

                            <h3 id="ek-bilgi-baslik-<?php echo $basvuru_id; ?>">
                                Başvuru #<?php echo $basvuru_id; ?> — Ek Bilgi
                            </h3>

                            <form method="dialog">
                                <button type="submit" class="ek-bilgi-kapat">
                                    Kapat ✕
                                </button>
                            </form>

                        </div>

                        <p class="ek-bilgi-kisi">
                            <?php echo guvenli($basvuru["adsoyad"] ?? ""); ?>
                        </p>

                        <div class="ek-bilgi-yazi"><?php
                            echo guvenli($basvuru["mesaj"] ?? "");
                        ?></div>
                    </dialog>

                    <p>
                        <strong>Başvuru Tarihi:</strong>
                        <?php echo guvenli($basvuru["basvuru_tarihi"] ?? ""); ?>
                    </p>

                    <p>
                        <strong>Durum:</strong>
                        <span class="basvuru-durum <?php echo guvenli($sinif); ?>">
                            <?php echo guvenli($durum); ?>
                        </span>
                    </p>

                    <?php if ($durum === "Bekliyor"): ?>

                        <div class="basvuru-islemleri">

                            <form
                                action="<?php echo guvenli($form_adresi); ?>"
                                method="POST"
                                onsubmit="return confirm('Başvuruyu onaylamak istediğinize emin misiniz?');"
                            >
                                <input
                                    type="hidden"
                                    name="token"
                                    value="<?php echo guvenli($_SESSION['basvuru_token']); ?>"
                                >
                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?php echo $basvuru_id; ?>"
                                >
                                <input type="hidden" name="islem" value="onayla">

                                <button type="submit" class="basvuru-onayla">
                                    Onayla
                                </button>
                            </form>

                            <form
                                action="<?php echo guvenli($form_adresi); ?>"
                                method="POST"
                                onsubmit="return confirm('Başvuruyu reddetmek istediğinize emin misiniz?');"
                            >
                                <input
                                    type="hidden"
                                    name="token"
                                    value="<?php echo guvenli($_SESSION['basvuru_token']); ?>"
                                >
                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?php echo $basvuru_id; ?>"
                                >
                                <input type="hidden" name="islem" value="reddet">

                                <button type="submit" class="basvuru-reddet">
                                    Reddet
                                </button>
                            </form>

                        </div>

                    <?php endif; ?>

                </article>

            <?php endwhile; ?>

        </div>

    <?php endif; ?>

</main>

</body>
</html>

<?php mysqli_stmt_close($stmt); ?>