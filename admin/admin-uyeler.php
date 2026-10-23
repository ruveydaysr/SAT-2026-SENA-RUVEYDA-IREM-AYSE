<?php
session_start();

if (!isset($_SESSION["admin"])) {
    header("Location: admin-giris.php");
    exit();
}

require_once __DIR__ . "/../baglanti.php";

function guvenli($deger)
{
    return htmlspecialchars(
        (string)$deger,
        ENT_QUOTES,
        "UTF-8"
    );
}

$arama = is_string($_GET["arama"] ?? null)
    ? trim($_GET["arama"])
    : "";

$sql = "SELECT id, ad_soyad, email, telefon
        FROM uyeler
        WHERE (? = ''
            OR LOCATE(?, ad_soyad) > 0
            OR LOCATE(?, email) > 0)
        ORDER BY id DESC";

$stmt = mysqli_prepare($baglanti, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "sss",
    $arama,
    $arama,
    $arama
);

mysqli_stmt_execute($stmt);
$uyeler = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Üye Yönetimi | Admin Paneli</title>

    <link rel="stylesheet" href="admin-style.css?v=14">

    <style>
        .uye-arama {
            display: flex;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 14px;
        }

        .uye-arama-alani {
            flex: 1 1 260px;
            min-width: 0;
        }

        .uye-arama label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .uye-arama input {
            width: 100%;
            padding: 13px;
            border: 1px solid #ded6c6;
            border-radius: 8px;
            background: #fffdf8;
            color: #284b32;
            font: inherit;
        }
    </style>
</head>

<body>

<?php require __DIR__ . "/admin-menu.php"; ?>

<main class="icerik">

    <h2>Kayıtlı Üyeler</h2>

    <section class="kart">

        <form
            action="admin-uyeler.php"
            method="GET"
            class="uye-arama"
        >
            <div class="uye-arama-alani">
                <label for="arama">Üye Ara</label>

                <input
                    type="search"
                    id="arama"
                    name="arama"
                    placeholder="Ad soyad veya e-posta yazınız..."
                    value="<?php echo guvenli($arama); ?>"
                >
            </div>

            <button type="submit">Ara</button>

            <a href="admin-uyeler.php" class="filtre-temizle">
                Temizle
            </a>
        </form>

        <p>
            Bulunan üye sayısı:
            <strong><?php echo mysqli_num_rows($uyeler); ?></strong>
        </p>

    </section>

    <div class="tablo-alani">
        <table>
            <thead>
                <tr>
                    <th scope="col">Üye ID</th>
                    <th scope="col">Ad Soyad</th>
                    <th scope="col">E-posta</th>
                    <th scope="col">Telefon</th>
                </tr>
            </thead>

            <tbody>

                <?php if (mysqli_num_rows($uyeler) === 0): ?>

                    <tr>
                        <td colspan="4">
                            <?php echo $arama !== ""
                                ? "Aramanıza uygun üye bulunamadı."
                                : "Henüz kayıtlı üye bulunmuyor."; ?>
                        </td>
                    </tr>

                <?php else: ?>

                    <?php while ($uye = mysqli_fetch_assoc($uyeler)): ?>
                        <tr>
                            <td>
                                <?php echo (int)$uye["id"]; ?>
                            </td>

                            <td>
                                <?php echo guvenli($uye["ad_soyad"] ?? ""); ?>
                            </td>

                            <td>
                                <?php echo guvenli($uye["email"] ?? ""); ?>
                            </td>

                            <td>
                                <?php echo guvenli($uye["telefon"] ?? ""); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php endif; ?>

            </tbody>
        </table>
    </div>

</main>

</body>
</html>

<?php mysqli_stmt_close($stmt); ?>