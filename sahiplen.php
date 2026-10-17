
<?php
session_start();

if (!isset($_SESSION["uye_id"])) {
    $hedef = "sahiplen.php";

    if (isset($_GET["ilan_id"])) {
        $hedef .= "?ilan_id=" . urlencode($_GET["ilan_id"]);
    }

    header("Location: giris.php?redirect=" . urlencode($hedef));
    exit();
}

include("baglanti.php");

function guvenli($deger) {
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

$mesaj = "";
$basarili = false;

$ilan_id = filter_input(INPUT_GET, "ilan_id", FILTER_VALIDATE_INT);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $ilan_id = filter_input(INPUT_POST, "ilan_id", FILTER_VALIDATE_INT);
}

$ilan = null;

if ($ilan_id !== false && $ilan_id !== null && $ilan_id > 0) {

    $sql = "SELECT id, hayvan_adi, tur, yas, sehir
            FROM ilanlar
            WHERE id = ? AND durum = 'Onaylandı'
            LIMIT 1";

    $sorgu = mysqli_prepare($baglanti, $sql);

    mysqli_stmt_bind_param($sorgu, "i", $ilan_id);
    mysqli_stmt_execute($sorgu);

    $sonuc = mysqli_stmt_get_result($sorgu);
    $ilan = mysqli_fetch_assoc($sonuc);

    mysqli_stmt_close($sorgu);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $ilan) {

    $uye_id = (int) $_SESSION["uye_id"];
    $adsoyad = trim($_POST["adsoyad"] ?? "");
    $telefon = trim($_POST["telefon"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $ev = trim($_POST["ev"] ?? "");
    $ek_bilgi = trim($_POST["mesaj"] ?? "");

    if (
        $adsoyad === "" ||
        $telefon === "" ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !in_array($ev, ["apartman", "mustakil"], true)
    ) {
        $mesaj = "Lütfen bilgilerinizi doğru şekilde doldurunuz.";
    } else {

        $sql = "INSERT INTO sahiplenme_basvurulari
                (ilan_id, uye_id, adsoyad, telefon, email, ev, mesaj, durum)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Bekliyor')";

        $sorgu = mysqli_prepare($baglanti, $sql);

        if ($sorgu) {

            mysqli_stmt_bind_param(
                $sorgu,
                "iisssss",
                $ilan_id,
                $uye_id,
                $adsoyad,
                $telefon,
                $email,
                $ev,
                $ek_bilgi
            );

            if (mysqli_stmt_execute($sorgu)) {
                $mesaj = "Sahiplenme başvurunuz başarıyla gönderildi.";
                $basarili = true;
            } else {
                $mesaj = "Başvuru kaydedilirken bir hata oluştu.";
            }

            mysqli_stmt_close($sorgu);

        } else {
            $mesaj = "Başvuru hazırlanırken bir hata oluştu.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hayvan Sahiplen</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
        .basvuru-alani {
            max-width: 650px;
            margin: 35px auto;
            padding: 25px;
            background: white;
            border-radius: 12px;
        }

        .basvuru-alani label {
            display: block;
            margin-top: 15px;
            margin-bottom: 6px;
            font-weight: bold;
        }

        .basvuru-alani input,
        .basvuru-alani select,
        .basvuru-alani textarea {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .basvuru-alani button {
            margin-top: 20px;
            padding: 12px 20px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .basvuru-alani button:hover {
            background: #388e3c;
        }

        .hayvan-ozet {
            padding: 15px;
            background: #f1f8f1;
            border-radius: 8px;
            margin: 20px 0;
        }

        .basvuru-mesaj {
            padding: 12px;
            background: #eef5ee;
            border-radius: 6px;
        }
    </style>
</head>

<body>

<header>
    <h1>Hayvan Sahiplendirme Sistemi</h1>

    <nav>
        <a href="index.php">Ana Sayfa</a>
        <a href="ilanlar.php">Hayvan Ara</a>
        <a href="kayip.html">Kayıp Hayvanlar</a>
        <a href="ilan-ver.php">İlan Ver</a>
        <a href="giris.php">Giriş Yap</a>
    </nav>
</header>

<main>

<section class="basvuru-alani">

    <h1>Sahiplendirme Başvuru Formu</h1>

    <?php if (!$ilan): ?>

        <p>Başvuru yapılacak ilan bulunamadı veya onaylanmamış.</p>

        <a href="ilanlar.php">İlanlara Geri Dön</a>

    <?php else: ?>

        <div class="hayvan-ozet">
            <h2><?php echo guvenli($ilan["hayvan_adi"]); ?></h2>

            <p><strong>Tür:</strong>
                <?php echo guvenli($ilan["tur"]); ?>
            </p>

            <p><strong>Yaş:</strong>
                <?php echo (int)$ilan["yas"]; ?>
            </p>

            <p><strong>Şehir:</strong>
                <?php echo guvenli($ilan["sehir"]); ?>
            </p>
        </div>

        <?php if ($mesaj !== ""): ?>
            <p class="basvuru-mesaj">
                <?php echo guvenli($mesaj); ?>
            </p>
        <?php endif; ?>

        <?php if (!$basarili): ?>

        <form action="sahiplen.php" method="POST">

            <input
                type="hidden"
                name="ilan_id"
                value="<?php echo (int)$ilan["id"]; ?>"
            >

            <label for="adsoyad">Ad Soyad:</label>
            <input
                type="text"
                id="adsoyad"
                name="adsoyad"
                required
            >

            <label for="telefon">Telefon:</label>
            <input
                type="tel"
                id="telefon"
                name="telefon"
                required
            >

            <label for="email">E-posta:</label>
            <input
                type="email"
                id="email"
                name="email"
                required
            >

            <label for="ev">Yaşadığınız Yer:</label>
            <select id="ev" name="ev" required>
                <option value="">Seçiniz</option>
                <option value="apartman">Apartman</option>
                <option value="mustakil">Müstakil Ev</option>
            </select>

            <label for="mesaj">Ek Bilgi:</label>
            <textarea
                id="mesaj"
                name="mesaj"
                rows="5"
                placeholder="Kendiniz ve hayvana sağlayacağınız ortam hakkında bilgi verebilirsiniz."
            ></textarea>

            <button type="submit">
                Başvuru Gönder
            </button>

        </form>

        <?php endif; ?>

    <?php endif; ?>

    <p>
        <a href="ilanlar.php">← İlanlara Geri Dön</a>
    </p>

</section>

</main>

<footer>
    <p>Hayvan Sahiplendirme Sistemi - 2026</p>
</footer>

</body>
</html>
