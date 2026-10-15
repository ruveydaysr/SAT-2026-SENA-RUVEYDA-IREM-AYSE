
<?php
session_start();

if (!isset($_SESSION["admin"])) {
    header("Location: admin-giris.php");
    exit();
}

include("../baglanti.php");

$uyeler = mysqli_query(
    $baglanti,
    "SELECT id, ad_soyad, email, telefon
     FROM uyeler
     ORDER BY id DESC"
);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Üye Yönetimi</title>

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

        .tablo-alani {
            overflow-x: auto;
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

    <h2>Kayıtlı Üyeler</h2>

    <div class="tablo-alani">

        <table>

            <tr>
                <th>Üye ID</th>
                <th>Ad Soyad</th>
                <th>E-posta</th>
                <th>Telefon</th>
            </tr>

            <?php if ($uyeler && mysqli_num_rows($uyeler) > 0): ?>

                <?php while ($uye = mysqli_fetch_assoc($uyeler)): ?>

                    <tr>

                        <td>
                            <?php echo (int)$uye["id"]; ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $uye["ad_soyad"],
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $uye["email"],
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $uye["telefon"] ?? "",
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>
                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="4">
                        Henüz kayıtlı üye bulunmuyor.
                    </td>
                </tr>

            <?php endif; ?>

        </table>

    </div>

</div>

</body>
</html>
