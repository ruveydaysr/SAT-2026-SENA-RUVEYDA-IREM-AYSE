
<?php
session_start();

if (!isset($_SESSION["admin"])) {
    header("Location: admin-giris.php");
    exit();
}

include("../baglanti.php");

$mesajlar = mysqli_query(
    $baglanti,
    "SELECT * FROM mesajlar ORDER BY tarih DESC, id DESC"
);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gelen Mesajlar</title>

    <link rel="stylesheet" href="admin-style.css">

    <style>
        .admin-menu {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            padding: 12px 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .admin-menu a {
            text-decoration: none;
            white-space: nowrap;
        }

        .admin-menu .cikis-buton {
            margin-left: auto;
            background-color: #c62828;
            color: white;
            padding: 10px 15px;
            border-radius: 6px;
        }

        .admin-menu .cikis-buton:hover {
            background-color: #a61c1c;
        }

        .mesaj {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        @media (max-width: 600px) {
            .admin-menu .cikis-buton {
                margin-left: 0;
            }
        }
    </style>

</head>

<body>

<header>
    <h1>🐾 Hayvan Sahiplendirme</h1>
    <h2>Admin Paneli</h2>
</header>

<nav class="admin-menu">

    <a href="admin-panel.php">
        İlan Yönetimi
    </a>

    <a href="admin-basvurular.php">
        Sahiplenme Başvuruları
    </a>

    <a href="admin-uyeler.php">
        Üye Yönetimi
    </a>

    <a href="admin-mesajlar.php">
        Gelen Mesajlar
    </a>

    <a href="../index.php">
        Siteye Git
    </a>

    <a
        href="admin-cikis.php"
        class="cikis-buton"
        onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');"
    >
        Çıkış Yap
    </a>

</nav>

<div class="icerik">

    <h2>Üyelerden Gelen Sorular</h2>

    <?php if ($mesajlar && mysqli_num_rows($mesajlar) > 0): ?>

        <?php while ($mesaj = mysqli_fetch_assoc($mesajlar)): ?>

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

                <p>
                    <strong>Üyenin Sorusu:</strong>
                </p>

                <div class="mesaj"><?php
                    echo htmlspecialchars(
                        $mesaj["mesaj"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                ?></div>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="kart">
            <p>Henüz gönderilmiş mesaj bulunmuyor.</p>
        </div>

    <?php endif; ?>

</div>

</body>
</html>
