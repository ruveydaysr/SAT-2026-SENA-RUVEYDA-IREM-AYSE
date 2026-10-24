<?php
session_start();

if (!isset($_SESSION["admin"])) {
    header("Location: admin-giris.php");
    exit();
}

require_once __DIR__ . "/../baglanti.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin-panel.php");
    exit();
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

$red_nedeni = is_string($_POST["red_nedeni"] ?? null)
    ? trim($_POST["red_nedeni"])
    : "";

$bildirim = "Geçerli bir ilan ve reddetme nedeni gereklidir.";

if ($id && $id > 0 && $red_nedeni !== "") {
    $sql = "UPDATE ilanlar
            SET durum = 'Reddedildi',
                red_nedeni = ?
            WHERE id = ? AND durum = 'Bekliyor'";

    $sorgu = mysqli_prepare($baglanti, $sql);

    mysqli_stmt_bind_param(
        $sorgu,
        "si",
        $red_nedeni,
        $id
    );

    if (mysqli_stmt_execute($sorgu)) {
        $bildirim = mysqli_stmt_affected_rows($sorgu) > 0
            ? "İlan reddedildi ve reddetme nedeni kaydedildi."
            : "İlan bulunamadı veya zaten değerlendirilmiş.";
    } else {
        $bildirim = "İlan reddedilemedi.";
    }

    mysqli_stmt_close($sorgu);
}

$_SESSION["ilan_bildirimi"] = $bildirim;

header("Location: admin-panel.php", true, 303);
exit();