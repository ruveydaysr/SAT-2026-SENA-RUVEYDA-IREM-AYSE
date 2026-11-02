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

if (empty($_SESSION["basvuru_token"])) {
    $_SESSION["basvuru_token"] = bin2hex(random_bytes(32));
}

$izinli_durumlar = [
    "Bekliyor",
    "Onaylandı",
    "Reddedildi"
];

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
        !is_string($token) ||
        !hash_equals($_SESSION["basvuru_token"], $token)
    ) {
        $mesaj = "Sayfayı yenileyip tekrar deneyiniz.";

    } elseif (
        !$id ||
        $id < 1 ||
        !in_array($islem, ["onayla", "reddet"], true)
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

        mysqli_stmt_bind_param(
            $guncelle,
            "si",
            $yeni_durum,
            $id
        );

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
<html lang="<?= admin_h($admin_dil) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= admin_h(admin_t("Sahiplenme Başvuruları | Admin Paneli")) ?>
    </title>

    <link rel="stylesheet" href="admin-style.css?v=20261102">
<link rel="stylesheet" href="../site-ust.css?v=20261102-1">
<link rel="stylesheet" href="admin-ust.css?v=20261102-1">
</head>

<body>

<?php require __DIR__ . "/admin-menu.php"; ?>

<main class="icerik basvurular-sayfasi">

    <h2>
        <?= admin_h(admin_t("Sahiplenme Başvuruları")) ?>
    </h2>

    <?php if ($mesaj !== ""): ?>
        <p class="basvuru-mesaj" role="status">
            <?= admin_h(admin_t($mesaj)) ?>
        </p>
    <?php endif; ?>

    <section class="kart">

        <form
            action="admin-basvurular.php"
            method="GET"
            class="ilan-filtre"
        >
            <div>
                <label for="durum">
                    <?= admin_h(admin_t("Başvuru Durumu")) ?>
                </label>

                <select id="durum" name="durum">
                    <option value="">
                        <?= admin_h(admin_t("Tüm Başvurular")) ?>
                    </option>

                    <?php foreach ($izinli_durumlar as $secenek): ?>
                        <option
                            value="<?= guvenli($secenek) ?>"
                            <?= $filtre === $secenek ? "selected" : "" ?>
                        >
                            <?= admin_h(admin_t($secenek)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit">
                <?= admin_h(admin_t("Filtrele")) ?>
            </button>

            <a href="admin-basvurular.php" class="filtre-temizle">
                <?= admin_h(admin_t("Temizle")) ?>
            </a>
        </form>

        <p>
            <?= admin_h(admin_t("Bulunan başvuru sayısı:")) ?>
            <strong><?= $toplam_basvuru ?></strong>
        </p>

    </section>

    <?php if ($toplam_basvuru === 0): ?>

        <div class="kart">
            <p>
                <?= admin_h(admin_t(
                    $filtre !== ""
                        ? "Seçilen duruma uygun başvuru bulunamadı."
                        : "Henüz sahiplenme başvurusu bulunmuyor."
                )) ?>
            </p>
        </div>

    <?php else: ?>

        <div class="basvuru-grid">

            <?php while ($basvuru = mysqli_fetch_assoc($sonuc)): ?>
                <?php
                $basvuru_id = (int) $basvuru["id"];
                $durum = (string) ($basvuru["durum"] ?? "");
                $sinif = $durum_siniflari[$durum] ?? "";
                $ev = $evler[$basvuru["ev"] ?? ""] ?? "-";
                ?>

                <article class="basvuru-karti">

                    <h3>
                        <?= admin_h(admin_t("Başvuru #")) ?><?= $basvuru_id ?>
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

                        <?= admin_h(admin_t($basvuru["tur"] ?? "-")) ?>
                    </p>

                    <p>
                        <strong>
                            <?= admin_h(admin_t("Başvuran:")) ?>
                        </strong>

                        <?= guvenli($basvuru["adsoyad"] ?? "") ?>
                    </p>

                    <p>
                        <strong>
                            <?= admin_h(admin_t("Telefon:")) ?>
                        </strong>

                        <?= guvenli($basvuru["telefon"] ?? "") ?>
                    </p>

                    <p>
                        <strong>
                            <?= admin_h(admin_t("E-posta:")) ?>
                        </strong>

                        <?= guvenli($basvuru["email"] ?? "") ?>
                    </p>

                    <p>
                        <strong>
                            <?= admin_h(admin_t("Yaşadığı Yer:")) ?>
                        </strong>

                        <?= admin_h(admin_t($ev)) ?>
                    </p>

                    <button
                        type="button"
                        class="ek-bilgi-buton"
                        onclick="document.getElementById('ek-bilgi-<?= $basvuru_id ?>').showModal();"
                    >
                        <?= admin_h(admin_t("Ek Bilgiyi Gör")) ?>
                    </button>

                    <dialog
                        id="ek-bilgi-<?= $basvuru_id ?>"
                        class="ek-bilgi-pencere"
                        aria-labelledby="ek-bilgi-baslik-<?= $basvuru_id ?>"
                    >
                        <div class="ek-bilgi-ust">

                            <h3 id="ek-bilgi-baslik-<?= $basvuru_id ?>">
                                <?= admin_h(admin_t("Başvuru #")) ?><?= $basvuru_id ?>
                                <?= admin_h(admin_t("— Ek Bilgi")) ?>
                            </h3>

                            <form method="dialog">
                                <button
                                    type="submit"
                                    class="ek-bilgi-kapat"
                                >
                                    <?= admin_h(admin_t("Kapat ✕")) ?>
                                </button>
                            </form>

                        </div>

                        <p class="ek-bilgi-kisi">
                            <?= guvenli($basvuru["adsoyad"] ?? "") ?>
                        </p>

                        <div class="ek-bilgi-yazi"><?= guvenli($basvuru["mesaj"] ?? "") ?></div>
                    </dialog>

                    <p>
                        <strong>
                            <?= admin_h(admin_t("Başvuru Tarihi:")) ?>
                        </strong>

                        <?= guvenli($basvuru["basvuru_tarihi"] ?? "") ?>
                    </p>

                    <p>
                        <strong>
                            <?= admin_h(admin_t("Durum:")) ?>
                        </strong>

                        <span class="basvuru-durum <?= guvenli($sinif) ?>">
                            <?= admin_h(admin_t($durum)) ?>
                        </span>
                    </p>

                    <?php if ($durum === "Bekliyor"): ?>

                        <div class="basvuru-islemleri">

                            <form
                                action="<?= guvenli($form_adresi) ?>"
                                method="POST"
                                onsubmit="return confirm(<?= admin_h(admin_json('Başvuruyu onaylamak istediğinize emin misiniz?')) ?>);"
                            >
                                <input
                                    type="hidden"
                                    name="token"
                                    value="<?= guvenli($_SESSION['basvuru_token']) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= $basvuru_id ?>"
                                >

                                <input
                                    type="hidden"
                                    name="islem"
                                    value="onayla"
                                >

                                <button
                                    type="submit"
                                    class="basvuru-onayla"
                                >
                                    <?= admin_h(admin_t("Onayla")) ?>
                                </button>
                            </form>

                            <form
                                action="<?= guvenli($form_adresi) ?>"
                                method="POST"
                                onsubmit="return confirm(<?= admin_h(admin_json('Başvuruyu reddetmek istediğinize emin misiniz?')) ?>);"
                            >
                                <input
                                    type="hidden"
                                    name="token"
                                    value="<?= guvenli($_SESSION['basvuru_token']) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= $basvuru_id ?>"
                                >

                                <input
                                    type="hidden"
                                    name="islem"
                                    value="reddet"
                                >

                                <button
                                    type="submit"
                                    class="basvuru-reddet"
                                >
                                    <?= admin_h(admin_t("Reddet")) ?>
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