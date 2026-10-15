
<?php
session_start();

if (!isset($_SESSION["admin"])) {
    header("Location: admin-giris.php");
    exit();
}

include("../baglanti.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin-panel.php");
    exit();
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
$red_nedeni = trim($_POST["red_nedeni"] ?? "");

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

    mysqli_stmt_execute($sorgu);
    mysqli_stmt_close($sorgu);
}

header("Location: admin-panel.php");
exit();
?>
