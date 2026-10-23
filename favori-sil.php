<?php
session_start();
require_once "baglanti.php";

// Giriş yapılmış mı?
if (!isset($_SESSION["uye_id"])) {
    header("Location: giris.php");
    exit;
}

// Sadece POST isteğine izin ver
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: profil.php");
    exit;
}

$uye_id = (int) $_SESSION["uye_id"];
$ilan_id = filter_input(INPUT_POST, "ilan_id", FILTER_VALIDATE_INT);

if (!$ilan_id || $ilan_id < 1) {
    header("Location: profil.php");
    exit;
}

// Yalnızca kullanıcının kendi favorisini sil
$sql = "DELETE FROM favoriler
        WHERE uye_id = ? AND ilan_id = ?";

$sorgu = mysqli_prepare($baglanti, $sql);
mysqli_stmt_bind_param($sorgu, "ii", $uye_id, $ilan_id);
mysqli_stmt_execute($sorgu);
mysqli_stmt_close($sorgu);

// Profil sayfasına geri dön
header("Location: profil.php");
exit;