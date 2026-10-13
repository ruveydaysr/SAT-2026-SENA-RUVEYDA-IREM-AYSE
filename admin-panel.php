
<?php
session_start();

if (!isset($_SESSION["admin"])) {
    header("Location: admin-giris.php");
    exit();
}

include("baglanti.php");

$sorgu = mysqli_query($baglanti, "SELECT * FROM ilanlar");

?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli</title>

    <style>
        body {
            font-family: Arial;
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
            text-align: center;
            padding: 15px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin: 15px;
        }

        .icerik {
            width: 80%;
            margin: 30px auto;
        }

        .kart {
            background-color: white;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .onayla {
            background-color: green;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
        }

        .sil {
            background-color: red;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
        }
    </style>
</head>

<body>

<header>
    <h1>🐾 Hayvan Sahiplendirme</h1>
    <h2>Admin Paneli</h2>
</header>


<nav>
    <a href="admin-panel.php">İlan Yönetimi</a>
    <a href="admin-uyeler.php">Üye Yönetimi</a>
    <a href="admin-mesajlar.php">Gelen Mesajlar</a>
    <a href="index.php">Siteye Git</a>
    <a href="admin-cikis.php">Çıkış Yap</a>
</nav>


<div class="icerik">

    <h2>İlan Yönetimi</h2>

    <?php while ($ilan = mysqli_fetch_assoc($sorgu)) { ?>

        <div class="kart">

            <h3>
                <?php echo htmlspecialchars($ilan["hayvan_adi"], ENT_QUOTES, "UTF-8"); ?>
            </h3>

            <p>
                Tür:
                <?php echo htmlspecialchars($ilan["tur"], ENT_QUOTES, "UTF-8"); ?>
            </p>

            <p>
                Yaş:
                <?php echo (int)$ilan["yas"]; ?>
            </p>

            <p>
                Durum:
                <?php echo htmlspecialchars($ilan["durum"], ENT_QUOTES, "UTF-8"); ?>
            </p>

            <?php if ($ilan["durum"] == "Bekliyor") { ?>

                <a class="onayla"
                   href="onayla.php?id=<?php echo (int)$ilan["id"]; ?>">
                    Onayla
                </a>

            <?php } ?>

            <a class="sil"
               href="sil.php?id=<?php echo (int)$ilan["id"]; ?>">
                Sil
            </a>

        </div>

    <?php } ?>

</div>

</body>
</html>
