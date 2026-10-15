
<?php
session_start();

if (!isset($_SESSION["admin"])) {
    header("Location: admin-giris.php");
    exit();
}

include("../baglanti.php");

$mesaj = "";

// Başvuru onaylama ve reddetme
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
    $islem = $_POST["islem"] ?? "";

    if (
        $id !== false &&
        $id !== null &&
        $id > 0 &&
        in_array($islem, ["onayla", "reddet"], true)
    ) {

        $durum = $islem === "onayla"
            ? "Onaylandı"
            : "Reddedildi";

        $sql = "UPDATE sahiplenme_basvurulari
                SET durum = ?
                WHERE id = ? AND durum = 'Bekliyor'";

        $sorgu = mysqli_prepare($baglanti, $sql);

        if ($sorgu) {

            mysqli_stmt_bind_param(
                $sorgu,
                "si",
                $durum,
                $id
            );

            if (mysqli_stmt_execute($sorgu)) {

                if (mysqli_stmt_affected_rows($sorgu) > 0) {
                    $mesaj = "Başvuru durumu güncellendi.";
                } else {
                    $mesaj = "Başvuru zaten değerlendirilmiş olabilir.";
                }

            } else {
                $mesaj = "İşlem sırasında hata oluştu.";
            }

            mysqli_stmt_close($sorgu);
        }

    } else {
        $mesaj = "Geçersiz işlem.";
    }
}

// Başvuruları listele
$sql = "SELECT
            b.id,
            b.ilan_id,
            b.adsoyad,
            b.telefon,
            b.email,
            b.ev,
            b.mesaj,
            b.durum,
            b.basvuru_tarihi,
            i.hayvan_adi,
            i.tur
        FROM sahiplenme_basvurulari AS b
        LEFT JOIN ilanlar AS i
            ON b.ilan_id = i.id
        ORDER BY b.id DESC";

$sonuc = mysqli_query($baglanti, $sql);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sahiplenme Başvuruları - Admin</title>
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

        .basvuru-karti {
            background: white;
            padding: 22px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .basvuru-karti h3 {
            margin-top: 0;
        }

        .basvuru-karti p {
            margin: 9px 0;
        }

        .basvuru-islemleri {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .basvuru-islemleri button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            color: white;
            cursor: pointer;
        }

        .basvuru-onayla {
            background-color: #4CAF50;
        }

        .basvuru-reddet {
            background-color: #e53935;
        }

        .basvuru-durum {
            font-weight: bold;
        }

        .basvuru-mesaj {
            background: #e8f5e9;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
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

    <a href="admin-panel.php">İlan Yönetimi</a>

    <a href="admin-basvurular.php">
        Sahiplenme Başvuruları
    </a>

    <a href="admin-uyeler.php">Üye Yönetimi</a>

    <a href="admin-mesajlar.php">Gelen Mesajlar</a>

    <a href="../index.php">Siteye Git</a>

    <a
        href="admin-cikis.php"
        class="cikis-buton"
        onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');"
    >
        Çıkış Yap
    </a>

</nav>

<div class="icerik">

    <h2>Sahiplenme Başvuruları</h2>

    <?php if ($mesaj !== ""): ?>

        <p class="basvuru-mesaj">
            <?php
            echo htmlspecialchars(
                $mesaj,
                ENT_QUOTES,
                "UTF-8"
            );
            ?>
        </p>

    <?php endif; ?>

    <?php if ($sonuc && mysqli_num_rows($sonuc) > 0): ?>

        <?php while ($basvuru = mysqli_fetch_assoc($sonuc)): ?>

            <div class="basvuru-karti">

                <h3>
                    Başvuru #<?php echo (int)$basvuru["id"]; ?>
                </h3>

                <p>
                    <strong>Hayvan:</strong>
                    <?php
                    echo htmlspecialchars(
                        $basvuru["hayvan_adi"] ?? "İlan silinmiş",
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>
                </p>

                <p>
                    <strong>Tür:</strong>
                    <?php
                    echo htmlspecialchars(
                        $basvuru["tur"] ?? "-",
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>
                </p>

                <p>
                    <strong>Başvuran:</strong>
                    <?php
                    echo htmlspecialchars(
                        $basvuru["adsoyad"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>
                </p>

                <p>
                    <strong>Telefon:</strong>
                    <?php
                    echo htmlspecialchars(
                        $basvuru["telefon"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>
                </p>

                <p>
                    <strong>E-posta:</strong>
                    <?php
                    echo htmlspecialchars(
                        $basvuru["email"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>
                </p>

                <p>
                    <strong>Yaşadığı Yer:</strong>
                    <?php
                    echo $basvuru["ev"] === "apartman"
                        ? "Apartman"
                        : (
                            $basvuru["ev"] === "mustakil"
                            ? "Müstakil Ev"
                            : "-"
                        );
                    ?>
                </p>

                <p>
                    <strong>Ek Bilgi:</strong>
                    <?php
                    echo nl2br(
                        htmlspecialchars(
                            $basvuru["mesaj"] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        )
                    );
                    ?>
                </p>

                <p>
                    <strong>Başvuru Tarihi:</strong>
                    <?php
                    echo htmlspecialchars(
                        $basvuru["basvuru_tarihi"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>
                </p>

                <p>
                    <strong>Durum:</strong>
                    <span class="basvuru-durum">
                        <?php
                        echo htmlspecialchars(
                            $basvuru["durum"],
                            ENT_QUOTES,
                            "UTF-8"
                        );
                        ?>
                    </span>
                </p>

                <?php if ($basvuru["durum"] === "Bekliyor"): ?>

                    <div class="basvuru-islemleri">

                        <form action="admin-basvurular.php" method="POST">

                            <input
                                type="hidden"
                                name="id"
                                value="<?php echo (int)$basvuru["id"]; ?>"
                            >

                            <input
                                type="hidden"
                                name="islem"
                                value="onayla"
                            >

                            <button
                                type="submit"
                                class="basvuru-onayla"
                                onclick="return confirm('Başvuruyu onaylamak istiyor musunuz?');"
                            >
                                Onayla
                            </button>

                        </form>

                        <form action="admin-basvurular.php" method="POST">

                            <input
                                type="hidden"
                                name="id"
                                value="<?php echo (int)$basvuru["id"]; ?>"
                            >

                            <input
                                type="hidden"
                                name="islem"
                                value="reddet"
                            >

                            <button
                                type="submit"
                                class="basvuru-reddet"
                                onclick="return confirm('Başvuruyu reddetmek istediğinize emin misiniz?');"
                            >
                                Reddet
                            </button>

                        </form>

                    </div>

                <?php endif; ?>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="basvuru-karti">
            <p>Henüz sahiplenme başvurusu bulunmuyor.</p>
        </div>

    <?php endif; ?>

</div>

</body>
</html>
