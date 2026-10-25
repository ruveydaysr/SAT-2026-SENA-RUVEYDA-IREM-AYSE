$hata = "";
$ozellikler = [
    "Oyuncu",
    "Sakin",
    "Çocuklarla uyumlu",
    "Diğer hayvanlarla uyumlu",
    "Eğitilebilir"
];

// Veritabanındaki virgülle ayrılmış özellikleri seçili kutulara dönüştür.
$secili_ozellikler = [];
if (!empty($ilan["karakteristik_ozellikler"])) {
    $secili_ozellikler = array_map("trim", explode(",", $ilan["karakteristik_ozellikler"]));
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["guncelle"])) {
    $ilan_basligi = trim($_POST["ilan_basligi"] ?? "");
    $hayvan_adi = trim($_POST["hayvan_adi"] ?? "");
    $tur = trim($_POST["tur"] ?? "");
    $yas = filter_var($_POST["yas"] ?? "", FILTER_VALIDATE_INT);
    $cinsiyet = $_POST["cinsiyet"] ?? "";
    $sehir = trim($_POST["sehir"] ?? "");
    $aciklama = trim($_POST["aciklama"] ?? "");

    // Yalnızca izin verilen özellikleri kaydet.
    $gelen_ozellikler = $_POST["karakteristik_ozellikler"] ?? [];
    if (!is_array($gelen_ozellikler)) {
        $gelen_ozellikler = [];
    }
    $secili_ozellikler = array_values(array_intersect($ozellikler, $gelen_ozellikler));
    $karakteristik_ozellikler = implode(", ", $secili_ozellikler);

    if (
        $ilan_basligi === "" ||
        $hayvan_adi === "" ||
        $tur === "" ||
        $yas === false || $yas < 0 ||
        !in_array($cinsiyet, ["Dişi", "Erkek"], true) ||
        $sehir === "" ||
        $aciklama === ""
    ) {
        $hata = "Lütfen tüm alanları doğru doldurunuz.";
    } else {
        $sql = "UPDATE ilanlar
                SET ilan_basligi = ?,
                    hayvan_adi = ?,
                    tur = ?,
                    yas = ?,
                    cinsiyet = ?,
                    sehir = ?,
                    aciklama = ?,
                    karakteristik_ozellikler = ?
                WHERE id = ? AND uye_id = ?";

        $guncelle = mysqli_prepare($baglanti, $sql);
        mysqli_stmt_bind_param(
            $guncelle,
            "sssissssii",
            $ilan_basligi,
            $hayvan_adi,
            $tur,
            $yas,
            $cinsiyet,
            $sehir,
            $aciklama,
            $karakteristik_ozellikler,
            $ilan_id,
            $uye_id
        );

        if (mysqli_stmt_execute($guncelle)) {
            mysqli_stmt_close($guncelle);
            header("Location: profil.php");
            exit();
        } else {
            $hata = "İlan güncellenirken bir hata oluştu. Veritabanında karakteristik_ozellikler sütununun bulunduğunu kontrol edin.";
            mysqli_stmt_close($guncelle);
        }
    }

    // Hata olursa formda kullanıcının yazdıkları kaybolmasın.
    $ilan["ilan_basligi"] = $ilan_basligi;
    $ilan["hayvan_adi"] = $hayvan_adi;
    $ilan["tur"] = $tur;
    $ilan["yas"] = $yas === false ? ($_POST["yas"] ?? "") : $yas;
    $ilan["cinsiyet"] = $cinsiyet;
    $ilan["sehir"] = $sehir;
    $ilan["aciklama"] = $aciklama;
}

function guvenli($deger) {
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlan Düzenle</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background-color: #f5f8f5; }
        .duzenle-baslik { text-align: center; margin: 45px auto 30px; }
        .duzenle-baslik h2 { color: #184f3c; font-size: 32px; margin-bottom: 12px; }
        .duzenle-baslik p { color: #71867a; }
        .duzenle-kutusu { max-width: 800px; margin: 0 auto 50px; padding: 35px; background-color: white; border: 1px solid #dce8df; border-radius: 16px; }
        .duzenle-kutusu label { display: block; color: #184f3c; font-weight: bold; margin-bottom: 8px; }
        .duzenle-kutusu input[type="text"], .duzenle-kutusu input[type="number"], .duzenle-kutusu select, .duzenle-kutusu textarea { width: 100%; padding: 13px; border: 1px solid #d3e0d6; border-radius: 8px; background-color: #f9fbf9; box-sizing: border-box; font-family: Arial, sans-serif; font-size: 14px; }
        .duzenle-kutusu input:focus, .duzenle-kutusu select:focus, .duzenle-kutusu textarea:focus { outline: none; border-color: #347a53; }
        .ozellik-alani { padding: 16px; margin: 8px 0 20px; border: 1px solid #dce8df; border-radius: 10px; background: #f9fbf9; }
        .ozellik-alani .ozellik-secim { display: flex; align-items: center; gap: 10px; margin: 12px 0; color: #35583b; font-weight: normal; }
        .ozellik-alani .ozellik-secim input { width: auto; accent-color: #347a53; }
        .ozellik-notu { color: #71867a; font-size: 13px; margin-top: 0; }
        .duzenle-kutusu button { background-color: #347a53; color: white; padding: 13px 22px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
        .duzenle-kutusu button:hover { background-color: #245c3d; }
        .duzenle-kutusu .iptal-buton { display: inline-block; margin-left: 12px; padding: 12px 20px; color: #347a53; text-decoration: none; }
        .duzenle-hata { color: #c62828; margin-bottom: 20px; }
        @media (max-width: 600px) { .duzenle-kutusu { margin: 0 12px 35px; padding: 20px; } .duzenle-baslik h2 { font-size: 26px; } }
    </style>
</head>
<body>
<header class="ilan-ust">
    <a href="index.php" class="ilan-logo">
        <span class="logo-pati">🐾</span>
        <span><strong>Aris Pati Yuvası</strong><small>Her can dostuna bir yuva</small></span>
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
    <div class="duzenle-baslik">
        <h2>İlanımı Düzenle</h2>
        <p>Dostumuzun bilgilerini buradan güncelleyebilirsiniz.</p>
    </div>

    <div class="duzenle-kutusu">
        <?php if ($hata !== ""): ?>
            <p class="duzenle-hata"><?php echo guvenli($hata); ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="ilan_basligi">İlan Başlığı:</label>
            <input id="ilan_basligi" type="text" name="ilan_basligi" value="<?php echo guvenli($ilan['ilan_basligi'] ?? ''); ?>" maxlength="150" required>
            <br><br>

            <label for="hayvan_adi">Hayvan Adı:</label>
            <input id="hayvan_adi" type="text" name="hayvan_adi" value="<?php echo guvenli($ilan['hayvan_adi'] ?? ''); ?>" required>
            <br><br>

            <label for="tur">Tür:</label>
            <select id="tur" name="tur" required>
                <option value="">Tür seçiniz</option>
                <?php foreach (["Kedi", "Köpek", "Kuş", "Tavşan", "Balık", "Kemirgen", "Sürüngen", "Diğer"] as $turSecenegi): ?>
                    <option value="<?php echo guvenli($turSecenegi); ?>" <?php echo (($ilan['tur'] ?? '') === $turSecenegi) ? 'selected' : ''; ?>><?php echo guvenli($turSecenegi); ?></option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label for="yas">Yaş:</label>
            <input id="yas" type="number" name="yas" min="0" value="<?php echo guvenli($ilan['yas'] ?? ''); ?>" required>
            <br><br>

            <label for="cinsiyet">Cinsiyet:</label>
            <select id="cinsiyet" name="cinsiyet" required>
                <option value="Dişi" <?php echo (($ilan['cinsiyet'] ?? '') === 'Dişi') ? 'selected' : ''; ?>>Dişi</option>
                <option value="Erkek" <?php echo (($ilan['cinsiyet'] ?? '') === 'Erkek') ? 'selected' : ''; ?>>Erkek</option>
            </select>
            <br><br>

            <label for="sehir">Şehir:</label>
            <select id="sehir" name="sehir" required>
                <option value="">Şehir seçiniz</option>
                <?php foreach (["Adana", "Ankara", "Antalya", "Bursa", "İstanbul", "İzmir", "Kayseri", "Konya", "Yozgat"] as $sehirSecenegi): ?>
                    <option value="<?php echo guvenli($sehirSecenegi); ?>" <?php echo (($ilan['sehir'] ?? '') === $sehirSecenegi) ? 'selected' : ''; ?>><?php echo guvenli($sehirSecenegi); ?></option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label for="aciklama">Açıklama:</label>
            <textarea id="aciklama" name="aciklama" rows="5" required><?php echo guvenli($ilan['aciklama'] ?? ''); ?></textarea>
            <br><br>

            <label>Hayvanın Karakteristik Özellikleri:</label>
            <p class="ozellik-notu">Hayvanına uygun olan özellikleri seçebilirsin.</p>