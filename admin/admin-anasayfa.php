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
    <link rel="stylesheet" href="admin-style.css?v=10">

    <style>
        .anasayfa-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .anasayfa-form input,
        .anasayfa-form textarea {
            box-sizing: border-box;
            width: 100%;
            margin-bottom: 22px;
            padding: 13px;
            border: 1px solid #d1dfd1;
            border-radius: 8px;
            background: #f8fbf8;
            color: #205843;
            font: inherit;
        }

        .anasayfa-form textarea {
            min-height: 150px;
            resize: vertical;
        }

        .kayit-bildirimi {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background: #e7f2e9;
            color: #205843;
        }

        .kayit-bildirimi.hata {
            background: #f9ecea;
            color: #a63c35;
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
    <a href="admin-genel-bakis.php">Genel Bakış</a>
    <a href="admin-panel.php">İlan Yönetimi</a>
    <a href="admin-anasayfa.php" aria-current="page">
        Ana Sayfa Yönetimi
    </a>
    <a href="admin-basvurular.php">Sahiplenme Başvuruları</a>
    <a href="admin-uyeler.php">Üye Yönetimi</a>
    <a href="admin-mesajlar.php">Gelen Mesajlar</a>

    <a
        href="../index.php"
        target="_blank"
        rel="noopener noreferrer"
    >
        Ana Sayfayı Gör
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