
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
    body {
        background-color: #f5f8f5;
    }

    .basvuru-alani {
        max-width: 650px;
        margin: 60px auto;
        padding: 40px;
        background-color: white;
        border: 1px solid #dce8df;
        border-radius: 15px;
    }

    .basvuru-alani h1 {
        color: #184f3c;
        text-align: center;
        font-size: 28px;
        margin-bottom: 25px;
    }

    .basvuru-alani label {
        display: block;
        margin-top: 18px;
        margin-bottom: 8px;
        color: #184f3c;
        font-weight: bold;
    }

    .basvuru-alani input,
    .basvuru-alani select,
    .basvuru-alani textarea {
        width: 100%;
        box-sizing: border-box;
        padding: 12px;
        border: 1px solid #cbded0;
        border-radius: 8px;
        font-family: Arial, sans-serif;
        font-size: 15px;
    }

    .basvuru-alani button {
        margin-top: 25px;
        padding: 12px 25px;
        background-color: #347a53;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 15px;
    }

    .basvuru-alani button:hover {
        background-color: #245c3d;
    }

    .hayvan-ozet {
        padding: 20px;
        background-color: #f5f8f5;
        border: 1px solid #dce8df;
        border-radius: 10px;
        margin: 25px 0;
    }

    .hayvan-ozet h2 {
        color: #184f3c;
        margin-bottom: 15px;
    }

    .hayvan-ozet p {
        color: #526b5a;
        margin-bottom: 10px;
    }

    .basvuru-mesaj {
        padding: 15px;
        background-color: #eef5ee;
        color: #184f3c;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .basvuru-alani a {
        color: #347a53;
        text-decoration: none;
    }

    .basvuru-alani a:hover {
        text-decoration: underline;
    }
</style>

</head>

<body>

<header class="ilan-ust">
    <a href="index.php" class="ilan-logo">
        <span class="logo-pati">🐾</span>
        <span>
            <strong>Aris Pati Yuvası</strong>
            <small>Her can dostuna bir yuva</small>
        </span>
    </a>

    <nav class="ilan-menu">
        <a href="index.php">Ana Sayfa</a>
        <a href="ilanlar.php">İlanlar</a>
        <a href="ilan-ver.php">İlan Ver</a>
        <a href="hakkimizda.php">Hakkımızda</a>
        <a href="iletisim.php">İletişim</a>
        <a href="profil.php">Profilim</a>
        <a href="cikis.php">Çıkış Yap</a>
    </nav>
</header>

<main>

<section class="basvuru-alani">
<h1>Bir Cana Yuva Olmaya Hazır mısınız? 🐾</h1>

<p style="color: #526b5a; text-align: center; line-height: 1.8;">
    Minik dostlarımızdan birine sevgi dolu bir yuva açmak
    istemeniz bizi çok mutlu ediyor!
    Başvurunuzu tamamlamak için aşağıdaki bilgileri
    doldurmanız yeterli. 💚
</p>


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
