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

$bildirim = "Geçersiz ilan numarası.";

if ($id && $id > 0) {
    try {
        $sorgu = mysqli_prepare(
            $baglanti,
            "DELETE FROM ilanlar WHERE id = ?"
        );

        if (!$sorgu) {
            $bildirim = "Silme işlemi hazırlanamadı.";
        } else {
            mysqli_stmt_bind_param($sorgu, "i", $id);

            if (mysqli_stmt_execute($sorgu)) {
                $bildirim = mysqli_stmt_affected_rows($sorgu) > 0
                    ? "İlan başarıyla silindi."
                    : "Silinecek ilan bulunamadı.";
            } else {
                $bildirim = "İlan silinemedi.";
            }

            mysqli_stmt_close($sorgu);
        }
    } catch (mysqli_sql_exception $hata) {
        $bildirim = "İlan silinemedi. İlişkili kayıtlar bulunuyor olabilir.";
    }
}

$_SESSION["ilan_bildirimi"] = $bildirim;

header("Location: admin-panel.php", true, 303);
exit();