
<?php
session_start();

if (!isset($_SESSION["admin"])) {
    header("Location: admin-giris.php");
    exit();
}

include("baglanti.php");

$uyeler = mysqli_query(
    $baglanti,
    "SELECT id, ad_soyad, email, telefon
     FROM uyeler ORDER BY id DESC"
);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Üye Yönetimi</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            margin: 0;
        }

        header {
            background-color: #4CAF50;
            color: white;
            text-align: center;
            padding: 20px;
        }

        nav {
            background-color: #333;
            padding: 15px;
            text-align: center;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin: 15px;
        }

        .icerik {
            width: 85%;
            max-width: 1100px;
            margin: 30px auto;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #4CAF50;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>

<body>

<header>
    <h1>🐾 Hayvan Sahiplendirme</h1>
    <h2>Üye Yönetimi</h2>
</header>

<nav>
    <a href="admin-panel.php">İlan Yönetimi</a>
    <a href="admin-uyeler.php">Üye Yönetimi</a>
    <a href="index.php">Siteye Git</a>
    <a href="admin-cikis.php">Çıkış Yap</a>
</nav>

<div class="icerik">

    <h2>Kayıtlı Üyeler</h2>

    <table>
        <tr>
            <th>Üye ID</th>
            <th>Ad Soyad</th>
            <th>E-posta</th>
            <th>Telefon</th>
        </tr>

        <?php while ($uye = mysqli_fetch_assoc($uyeler)) { ?>
        <tr>
            <td><?php echo (int)$uye["id"]; ?></td>
            <td><?php echo htmlspecialchars($uye["ad_soyad"], ENT_QUOTES, "UTF-8"); ?></td>
            <td><?php echo htmlspecialchars($uye["email"], ENT_QUOTES, "UTF-8"); ?></td>
            <td><?php echo htmlspecialchars($uye["telefon"], ENT_QUOTES, "UTF-8"); ?></td>
        </tr>
        <?php } ?>

    </table>

</div>

</body>
</html>
