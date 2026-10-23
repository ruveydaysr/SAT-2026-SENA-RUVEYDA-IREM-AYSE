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

if (empty($_SESSION["anasayfa_token"])) {
    $_SESSION["anasayfa_token"] = bin2hex(random_bytes(32));
}

$sonuc = mysqli_query(
    $baglanti,
    "SELECT baslik, aciklama, buton_yazisi
     FROM anasayfa_icerik WHERE id = 1"
);

$icerik = mysqli_fetch_assoc($sonuc);

if (!$icerik) {
    exit("Ana sayfa kaydı bulunamadı. Önce verilen SQL kodunu çalıştırınız.");
}

$hata = "";
$basarili = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["token"] ?? "";

    $icerik["baslik"] = is_string($_POST["baslik"] ?? null)
        ? trim($_POST["baslik"])
        : "";

    $icerik["aciklama"] = is_string($_POST["aciklama"] ?? null)
        ? trim($_POST["aciklama"])
        : "";

    $icerik["buton_yazisi"] = is_string($_POST["buton_yazisi"] ?? null)
        ? trim($_POST["buton_yazisi"])
        : "";

    if (
        !is_string($token)
        || !hash_equals($_SESSION["anasayfa_token"], $token)
    ) {
        $hata = "Sayfayı yenileyip tekrar deneyiniz.";
    } elseif (
        $icerik["baslik"] === ""
        || $icerik["aciklama"] === ""
        || $icerik["buton_yazisi"] === ""
    ) {
        $hata = "Lütfen tüm alanları doldurunuz.";
    } elseif (
        mb_strlen($icerik["baslik"], "UTF-8") > 150
        || mb_strlen($icerik["buton_yazisi"], "UTF-8") > 80
        || mb_strlen($icerik["aciklama"], "UTF-8") > 3000
    ) {
        $hata = "Başlık en fazla 150, buton yazısı 80, açıklama 3000 karakter olabilir.";
    } else {
        $stmt = mysqli_prepare(
            $baglanti,
            "UPDATE anasayfa_icerik
             SET baslik = ?, aciklama = ?, buton_yazisi = ?
             WHERE id = 1"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "sss",
            $icerik["baslik"],
            $icerik["aciklama"],
            $icerik["buton_yazisi"]
        );

        $basarili = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if (!$basarili) {
            $hata = "Değişiklikler kaydedilemedi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ana Sayfa Yönetimi</title>
    <link rel="stylesheet" href="admin-style.css?v=13">
</head>

<body>

<?php require __DIR__ . "/admin-menu.php"; ?>
<main class="icerik">
    <h2>Ana Sayfa Yönetimi</h2>

    <?php if ($basarili): ?>
        <div class="kayit-bildirimi" role="status">
            Ana sayfa içeriği kaydedildi.
        </div>
    <?php endif; ?>

    <?php if ($hata !== ""): ?>
        <div class="kayit-bildirimi hata" role="alert">
            <?php echo guvenli($hata); ?>
        </div>
    <?php endif; ?>

    <section class="kart">
        <h3>Tanıtım Yazıları</h3>
        <p>Burada kaydettiğiniz yazılar ana sayfada gösterilir.</p>

        <form
            action="admin-anasayfa.php"
            method="POST"
            class="anasayfa-form"
            onsubmit="return confirm('Ana sayfa değişikliklerini kaydetmek istediğinize emin misiniz?');"
        >
            <input
                type="hidden"
                name="token"
                value="<?php echo guvenli($_SESSION["anasayfa_token"]); ?>"
            >

            <label for="baslik">Başlık</label>
            <input
                type="text"
                id="baslik"
                name="baslik"
                maxlength="150"
                value="<?php echo guvenli($icerik["baslik"]); ?>"
                required
            >

            <label for="aciklama">Açıklama</label>
            <textarea
                id="aciklama"
                name="aciklama"
                maxlength="3000"
                required
            ><?php echo guvenli($icerik["aciklama"]); ?></textarea>

            <label for="buton_yazisi">Buton Yazısı</label>
            <input
                type="text"
                id="buton_yazisi"
                name="buton_yazisi"
                maxlength="80"
                value="<?php echo guvenli($icerik["buton_yazisi"]); ?>"
                required
            >

            <button type="submit">Değişiklikleri Kaydet</button>
        </form>
    </section>
</main>

</body>
</html>