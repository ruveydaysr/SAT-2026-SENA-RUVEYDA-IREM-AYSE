
<?php
session_start();

if (!isset($_SESSION["admin"])) {
    header("Location: admin-giris.php");
    exit();
}

include("baglanti.php");

$mesajlar = mysqli_query(
    $baglanti,
    "SELECT * FROM mesajlar ORDER BY tarih DESC, id DESC"
);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Gelen Mesajlar</title>

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
            text-align: center;
            padding: 15px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin: 15px;
        }

        .icerik {
            width: 85%;
            max-width: 1000px;
            margin: 30px auto;
        }

        .kart {
            background-color: white;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
        }

        .kart h3 {
            color: #4CAF50;
        }

        .mesaj {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }
    </style>
</head>

<body>

<header>
    <h1>🐾 Hayvan Sahiplendirme</h1>
    <h2>Gelen Mesajlar</h2>
</header>

<nav>
    <a href="admin-panel.php">İlan Yönetimi</a>
    <a href="admin-uyeler.php">Üye Yönetimi</a>
    <a href="admin-mesajlar.php">Gelen Mesajlar</a>
    <a href="index.php">Siteye Git</a>
    <a href="admin-cikis.php">Çıkış Yap</a>
</nav>

<div class="icerik">

    <h2>Üyelerden Gelen Sorular</h2>

    <?php if (mysqli_num_rows($mesajlar) > 0) { ?>

        <?php while ($mesaj = mysqli_fetch_assoc($mesajlar)) { ?>

            <div class="kart">

                <h3>
                    <?php
                    echo htmlspecialchars(
                        $mesaj["ad_soyad"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>
                </h3>

                <p>
                    <strong>E-posta:</strong>
                    <?php
                    echo htmlspecialchars(
                        $mesaj["email"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>
                </p>

                <p>
                    <strong>Gönderilme Tarihi:</strong>
                    <?php
                    echo htmlspecialchars(
                        $mesaj["tarih"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>
                </p>

                <p><strong>Üyenin Sorusu:</strong></p>

                <div class="mesaj"><?php
                    echo htmlspecialchars(
                        $mesaj["mesaj"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                ?></div>

            </div>

        <?php } ?>

    <?php } else { ?>

        <p>Henüz gönderilmiş mesaj bulunmuyor.</p>

    <?php } ?>

</div>

</body>
</html>
