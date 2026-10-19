
<?php
session_start();

if (!isset($_SESSION["admin"])) {
    header("Location: admin-giris.php");
    exit();
}

include("../baglanti.php");

$sorgu = mysqli_query(
    $baglanti,
    "SELECT * FROM ilanlar ORDER BY id DESC"
);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli</title>
    <link rel="stylesheet" href="admin-style.css?v=3">
</head>

<body>

<header>
    <a href="../index.php" class="admin-logo">
        <span class="logo-pati">🐾</span>

        <span>
            <strong>Aris Pati Yuvası</strong>
            <small>Her can dostuna bir yuva</small>
        </span>
    </a>

    <h2>Admin Paneli</h2>
</header>

<nav class="admin-menu">

    <a href="admin-panel.php">İlan Yönetimi</a>

    <a href="admin-basvurular.php">
        Sahiplenme Başvuruları
    </a>

    <a href="admin-uyeler.php">Üye Yönetimi</a>

    <a href="admin-mesajlar.php">Gelen Mesajlar</a>

  <a href="../index.php"
   onclick="return confirm('Siteye gitmek istediğinize emin misiniz?');">
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

    <h2>İlan Yönetimi</h2>

    <?php if (mysqli_num_rows($sorgu) == 0) { ?>

        <div class="kart">
            <p>Henüz ilan bulunmuyor.</p>
        </div>

    <?php } ?>

    <?php while ($ilan = mysqli_fetch_assoc($sorgu)) { ?>

        <div class="kart">

            <?php
            $fotograf = $ilan["fotograf"] ?? "";
            $dosya_adi = basename($fotograf);

            $gecerli_fotograf =
                $fotograf !== "" &&
                preg_match(
                    '/^[a-f0-9]{32}\.(jpg|png|webp)$/',
                    $dosya_adi
                ) &&
                is_file(
                    __DIR__ . "/../uploads/" . $dosya_adi
                );
            ?>

            <?php if ($gecerli_fotograf): ?>

                <img
                    src="../uploads/<?php
                        echo htmlspecialchars(
                            $dosya_adi,
                            ENT_QUOTES,
                            "UTF-8"
                        );
                    ?>"
                    alt="Hayvan Fotoğrafı"
                    class="ilan-fotograf"
                >

            <?php else: ?>

                <p>Fotoğraf Yok</p>

            <?php endif; ?>

            <h3>
                <?php
                echo htmlspecialchars(
                    $ilan["hayvan_adi"],
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>
            </h3>

            <p>
                <strong>Tür:</strong>
                <?php
                echo htmlspecialchars(
                    $ilan["tur"],
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>
            </p>

            <p>
                <strong>Yaş:</strong>
                <?php echo (int)$ilan["yas"]; ?>
            </p>

            <p>
                <strong>Durum:</strong>

                <span class="durum <?php
                    echo htmlspecialchars(
                        $ilan["durum"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    );
                ?>">
                    <?php
                    echo htmlspecialchars(
                        $ilan["durum"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>
                </span>
            </p>

            <?php if (
                $ilan["durum"] == "Reddedildi" &&
                !empty($ilan["red_nedeni"])
            ) { ?>

                <p>
                    <strong>Reddedilme Nedeni:</strong>
                    <?php
                    echo htmlspecialchars(
                        $ilan["red_nedeni"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>
                </p>

            <?php } ?>

            <?php if ($ilan["durum"] == "Bekliyor") { ?>

                <div class="islem-alani">

                    <form action="onayla.php" method="POST">

                        <input
                            type="hidden"
                            name="id"
                            value="<?php echo (int)$ilan["id"]; ?>"
                        >

                        <button type="submit" class="onayla">
                            Onayla
                        </button>

                    </form>

                    <form
                        action="sil.php"
                        method="POST"
                        onsubmit="return confirm('Bu ilanı silmek istediğinize emin misiniz? Bu işlem geri alınamaz.');"
                    >

                        <input
                            type="hidden"
                            name="id"
                            value="<?php echo (int)$ilan["id"]; ?>"
                        >

                        <button type="submit" class="sil">
                            Sil
                        </button>

                    </form>

                </div>

                <form
                    action="reddet.php"
                    method="POST"
                    class="ret-formu"
                >

                    <input
                        type="hidden"
                        name="id"
                        value="<?php echo (int)$ilan["id"]; ?>"
                    >

                    <label for="red-<?php echo (int)$ilan["id"]; ?>">
                        Reddetme Nedeni:
                    </label>

                    <textarea
                        id="red-<?php echo (int)$ilan["id"]; ?>"
                        name="red_nedeni"
                        placeholder="Reddetme nedenini yazınız..."
                        required
                    ></textarea>

                    <button type="submit" class="reddet">
                        Reddet
                    </button>

                </form>

            <?php } else { ?>

                <div class="islem-alani">

                    <form
                        action="sil.php"
                        method="POST"
                        onsubmit="return confirm('Bu ilanı silmek istediğinize emin misiniz? Bu işlem geri alınamaz.');"
                    >

                        <input
                            type="hidden"
                            name="id"
                            value="<?php echo (int)$ilan["id"]; ?>"
                        >

                        <button type="submit" class="sil">
                            Sil
                        </button>

                    </form>

                </div>

            <?php } ?>

        </div>

    <?php } ?>

</div>

</body>
</html>
