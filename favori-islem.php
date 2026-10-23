<?php
session_start();
require_once "baglanti.php";

if (!isset($_SESSION["uye_id"])) {
    header("Location: giris.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ilanlar.php");
    exit;
}

$ilan_id = filter_input(INPUT_POST, "ilan_id", FILTER_VALIDATE_INT);
$uye_id = (int) $_SESSION["uye_id"];

if (!$ilan_id || $ilan_id < 1) {
    header("Location: ilanlar.php");
    exit;
}

$kontrol = mysqli_prepare(
    $baglanti,
    "SELECT id FROM ilanlar WHERE id = ? AND durum = 'Onaylandı'"
);

mysqli_stmt_bind_param($kontrol, "i", $ilan_id);
mysqli_stmt_execute($kontrol);
mysqli_stmt_store_result($kontrol);

if (mysqli_stmt_num_rows($kontrol) === 0) {
    mysqli_stmt_close($kontrol);
    header("Location: ilanlar.php");
    exit;
}

mysqli_stmt_close($kontrol);

$sorgu = mysqli_prepare(
    $baglanti,
    "INSERT IGNORE INTO favoriler (uye_id, ilan_id) VALUES (?, ?)"
);

mysqli_stmt_bind_param($sorgu, "ii", $uye_id, $ilan_id);
mysqli_stmt_execute($sorgu);
mysqli_stmt_close($sorgu);

header("Location: ilanlar.php");
exit;