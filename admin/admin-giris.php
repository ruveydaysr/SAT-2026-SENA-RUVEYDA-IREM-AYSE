<?php
session_start();

require_once __DIR__ . "/../baglanti.php";

$hata = "";
$kullanici_adi = "";

if (isset($_SESSION["admin"])) {
    header("Location: admin-genel-bakis.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $kullanici_adi = is_string($_POST["kullanici_adi"] ?? null)
        ? trim($_POST["kullanici_adi"])
        : "";

    $sifre = is_string($_POST["sifre"] ?? null)
        ? $_POST["sifre"]
        : "";

    $sorgu = mysqli_prepare(
        $baglanti,
        "SELECT kullanici_adi, sifre
         FROM admin
         WHERE kullanici_adi = ?
         LIMIT 1"
    );

    mysqli_stmt_bind_param($sorgu, "s", $kullanici_adi);
    mysqli_stmt_execute($sorgu);

    $sonuc = mysqli_stmt_get_result($sorgu);
    $admin = mysqli_fetch_assoc($sonuc);

    mysqli_stmt_close($sorgu);

    $dogru = false;

    if ($admin && $kullanici_adi !== "" && $sifre !== "") {

        $kayitli_sifre = (string) $admin["sifre"];
        $bilgi = password_get_info($kayitli_sifre);

        if (!empty($bilgi["algo"])) {

            $dogru = password_verify($sifre, $kayitli_sifre);

        } else {

            // Eski açık şifreleri başarılı girişte hash'e dönüştür.
            $dogru = hash_equals($kayitli_sifre, $sifre);

            if ($dogru) {

                $hash = password_hash($sifre, PASSWORD_DEFAULT);

                $guncelle = mysqli_prepare(
                    $baglanti,
                    "UPDATE admin
                     SET sifre = ?
                     WHERE kullanici_adi = ? AND sifre = ?
                     LIMIT 1"
                );

                mysqli_stmt_bind_param(
                    $guncelle,
                    "sss",
                    $hash,
                    $admin["kullanici_adi"],
                    $kayitli_sifre
                );

                mysqli_stmt_execute($guncelle);
                mysqli_stmt_close($guncelle);
            }
        }
    }

    if ($dogru) {

        session_regenerate_id(true);
        $_SESSION["admin"] = $admin["kullanici_adi"];

        header("Location: admin-genel-bakis.php");
        exit();

    } else {

        $hata = "Kullanıcı adı veya şifre yanlış.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Yönetici Girişi | Aris Pati Yuvası</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            background: #f5f0e6;
            color: #284b32;
            font-family: Arial, sans-serif;
        }

        .giris-ust {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            padding: 16px 5%;
            border-bottom: 1px solid #ded6c6;
            background: #fffdf8;
        }

        .admin-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: #284b32;
            text-decoration: none;
        }

        .admin-logo img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .admin-logo strong {
            display: block;
            font-size: 19px;
        }

        .admin-logo small {
            display: block;
            margin-top: 5px;
            color: #687361;
            font-size: 11px;
        }

        .giris-ust nav {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 6px;
        }

        .giris-ust nav a {
            padding: 10px 13px;
            border-radius: 8px;
            color: #284b32;
            font-size: 14px;
            text-decoration: none;
        }

        .giris-ust nav a:hover {
            background: #e6eddf;
        }

        /* Görselin tamamı görünür, kenarlardan kesilmez. */
        .giris-ana {
            display: flex;
            flex: 1;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
            background-color: #879273;
            background-image: url("../images/anasayfa-kapak.png");
            background-position: center;
            background-size: contain;
            background-repeat: no-repeat;
        }

        .giris {
            width: 100%;
            max-width: 380px;
            padding: 26px;
            border: 1px solid #ded6c6;
            border-radius: 18px;
            background: rgba(255, 253, 248, 0.97);
            text-align: center;
            box-shadow: 0 15px 45px rgba(20, 40, 25, 0.22);
        }

        .giris-logo-resim {
            display: block;
            width: 65px;
            height: 65px;
            margin: 0 auto 14px;
            object-fit: contain;
        }

        .giris h1 {
            margin: 0 0 10px;
            font-size: 25px;
        }

        .giris-aciklama {
            margin: 8px 0 22px;
            color: #687361;
            font-size: 13px;
            line-height: 1.6;
        }

        .giris form {
            display: grid;
            gap: 16px;
            text-align: left;
        }

        .giris label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: bold;
        }

        .giris input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d6d9c9;
            border-radius: 9px;
            background: white;
            color: #284b32;
            font: inherit;
        }

        .giris button {
            width: 100%;
            margin-top: 4px;
            padding: 13px;
            border: 0;
            border-radius: 9px;
            background: #426b46;
            color: white;
            font: inherit;
            font-weight: bold;
            cursor: pointer;
        }

        .giris button:hover {
            background: #284b32;
        }

        input:focus-visible,
        button:focus-visible,
        a:focus-visible {
            outline: 2px solid #426b46;
            outline-offset: 3px;
        }

        .hata {
            margin: 0 0 20px;
            padding: 12px;
            border: 1px solid #edccc7;
            border-radius: 8px;
            background: #f9ecea;
            color: #a63c35;
            font-size: 14px;
        }

        @media (max-width: 650px) {
            .giris-ust {
                justify-content: center;
                padding: 16px;
            }

            .giris-ust nav {
                width: 100%;
            }

            .giris-ust nav a {
                padding: 8px 10px;
                font-size: 13px;
            }

            .giris-ana {
                padding: 30px 16px;
            }

            .giris {
                padding: 24px 20px;
            }
        }
    </style>
</head>

<body>

<header class="giris-ust">

    <a href="../index.php" class="admin-logo">
        <img
            src="../images/aris-logoo.png"
            alt="Aris Pati Yuvası logosu"
            width="48"
            height="48"
        >

        <span>
            <strong>Aris Pati Yuvası</strong>
            <small>Her can dostuna bir yuva</small>
        </span>
    </a>

    <nav aria-label="Site menüsü">
        <a href="../index.php">Ana Sayfa</a>
        <a href="../ilanlar.php">İlanlar</a>
        <a href="../hakkimizda.php">Hakkımızda</a>
        <a href="../iletisim.php">İletişim</a>
    </nav>

</header>

<main class="giris-ana">

    <section class="giris" aria-labelledby="giris-baslik">

        <img
            src="../images/aris-logoo.png"
            alt=""
            class="giris-logo-resim"
            width="65"
            height="65"
        >

        <h1 id="giris-baslik">Yönetici Girişi</h1>

        <p class="giris-aciklama">
            Aris Pati Yuvası yönetim paneline giriş yapın.
        </p>

        <?php if ($hata !== ""): ?>
            <p class="hata" role="alert">
                <?php echo htmlspecialchars($hata, ENT_QUOTES, "UTF-8"); ?>
            </p>
        <?php endif; ?>

        <form action="admin-giris.php" method="POST">

            <div>
                <label for="kullanici_adi">Kullanıcı Adı</label>

                <input
                    type="text"
                    id="kullanici_adi"
                    name="kullanici_adi"
                    value="<?php echo htmlspecialchars($kullanici_adi, ENT_QUOTES, 'UTF-8'); ?>"
                    placeholder="Kullanıcı adınızı yazınız"
                    autocomplete="username"
                    required
                >
            </div>

            <div>
                <label for="sifre">Şifre</label>

                <input
                    type="password"
                    id="sifre"
                    name="sifre"
                    placeholder="Şifrenizi yazınız"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" name="giris" value="1">
                Giriş Yap
            </button>

        </form>

    </section>

</main>

</body>
</html>