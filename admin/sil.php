
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

if ($id && $id > 0) {

    $sql = "DELETE FROM ilanlar WHERE id = ?";

    $sorgu = mysqli_prepare($baglanti, $sql);

    mysqli_stmt_bind_param($sorgu, "i", $id);

    mysqli_stmt_execute($sorgu);

    mysqli_stmt_close($sorgu);
}

header("Location: admin-panel.php");
exit();
?>

