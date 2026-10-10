<?php
session_start();

if (!isset($_SESSION["uye_id"])) {
    header("Location: giris.php");
    exit();
}

include("baglanti.php");

$mesaj = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $hayvan_adi = trim($_POST["hayvan_adi"] ?? "");
    $tur = trim($_POST["tur"] ?? "");
    $yas = filter_input(INPUT_POST, "yas", FILTER_VALIDATE_INT);
    $cinsiyet = trim($_POST["cinsiyet"] ?? "");
    $sehir = trim($_POST["sehir"] ?? "");
    $aciklama = trim($_POST["aciklama"] ?? "");

    if (
        $hayvan_adi === "" ||
        $tur === "" ||
        $yas === false ||
        $yas === null ||
        $yas < 0 ||
        $cinsiyet === "" ||
        $sehir === "" ||
        $aciklama === ""
    ) {
        $mesaj = "Lütfen tüm alanları doğru şekilde doldurunuz.";
    } else {

        $sql = "INSERT INTO ilanlar
                (hayvan_adi, tur, yas, cinsiyet, sehir, aciklama, durum)
                VALUES (?, ?, ?, ?, ?, ?, 'Bekliyor')";

        $sorgu = mysqli_prepare($baglanti, $sql);

        mysqli_stmt_bind_param(
            $sorgu,
            "ssisss",
            $hayvan_adi,
            $tur,
            $yas,
            $cinsiyet,
            $sehir,
            $aciklama
        );

        if (mysqli_stmt_execute($sorgu)) {
            $mesaj = "İlanınız başarıyla kaydedildi. Yönetici onayı bekleniyor.";
        } else {
            $mesaj = "İlan kaydedilirken bir hata oluştu.";
        }

        mysqli_stmt_close($sorgu);
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İlan Ver</title>
</head>
<body>

    <h1>Hayvan İlanı Ver</h1>
    <?php if ($mesaj !== ""): ?>
    <p><?php echo htmlspecialchars($mesaj, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

    <form action="ilan-ver.php" method="POST">

        <label for="tur">Hayvan Türü:</label>
<select id="tur" name="tur" required>
    <option value="">Hayvan türünü seçiniz</option>
    <option value="Kedi">Kedi</option>
    <option value="Köpek">Köpek</option>
    <option value="Kuş">Kuş</option>
    <option value="Tavşan">Tavşan</option>
    <option value="Balık">Balık</option>
    <option value="Diğer">Diğer</option>
</select>
        <br><br>
        <label for="hayvan_adi">Hayvan Adı:</label>
<input type="text" id="hayvan_adi" name="hayvan_adi"
       placeholder="Hayvanın adını giriniz" required> <br><br>
<br><br>

<label for="yas">Hayvanın Yaşı:</label>
<input type="number" id="yas" name="yas"
       placeholder="Yaşını giriniz" min="0" required>
<br><br>
<br><br>

<label for="cinsiyet">Cinsiyet:</label>
<select id="cinsiyet" name="cinsiyet" required>
    <option value="">Seçiniz</option>
    <option value="Dişi">Dişi</option>
    <option value="Erkek">Erkek</option>
</select>

<br><br>

<label for="sehir">Şehir:</label>
<input type="text" id="sehir" name="sehir"
       placeholder="Şehir giriniz" required>

<br><br>

<label for="aciklama">Açıklama:</label>
<textarea id="aciklama" name="aciklama"
          rows="5" placeholder="Hayvan hakkında bilgi veriniz"
          required></textarea>

<br><br>

<button type="submit">İlan Ver</button>
    </form>

</body>
</html>

