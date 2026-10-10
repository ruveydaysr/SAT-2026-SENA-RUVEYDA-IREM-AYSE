<?php
session_start();

if (!isset($_SESSION["uye_id"])) {
    header("Location: giris.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Profilim</title>
</head>
<body>

    <h1>Profilim</h1>
    <h2>İlanlarım</h2>
    <p>
        Verdiğim hayvan ilanlarını burada görebilirim.
    </p>
    
</body>
</html>

