<?php
session_start();

require_once __DIR__ . "/../baglanti.php";

$hata = "";
$kullanici_adi = "";

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

        $kayitli_sifre = (string)$admin["sifre"];
        $bilgi = password_get_info($kayitli_sifre);

        if (!empty($bilgi["algo"])) {

            $dogru = password_verify($sifre, $kayitli_sifre);

        } else {

            // Eski açık şifreyi doğrula ve hash olarak güncelle.
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

        header("Location: admin-panel.php");
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

    <link rel="stylesheet" href="admin-style.css?v=15">

    <style>
        body.admin-giris-sayfasi {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            background: #f5f0e6;
            color: #284b32;
            font-family: Arial, sans-serif;
        }

        .admin-giris-sayfasi,
        .admin-giris-sayfasi * {
            box-sizing: border-box;
        }

        body.admin-giris-sayfasi > header.giris-ust {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            width: 100%;
            padding: 16px 5%;
            border-bottom: 1px solid #ded6c6;
            background: #fffdf8;
            text-align: left;
        }

        .admin-giris-sayfasi .admin-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: #284b32;
            text-decoration: none;
        }

        .admin-giris-sayfasi .admin-logo img {
            display: block;
            flex-shrink: 0;
            width: 48px;
            height: 48px;
            object-fit: contain;
        }

        .admin-giris-sayfasi .admin-logo strong {
            display: block;
            font-size: 19px;
        }

        .admin-giris-sayfasi .admin-logo small {
            display: block;
            margin-top: 5px;
            color: #687361;
            font-size: 11px;
        }

        .admin-giris-sayfasi .giris-ust nav {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 6px;
        }

        .admin-giris-sayfasi .giris-ust nav a {
            display: inline-block;
            padding: 10px 13px;
            border-radius: 8px;
            background: transparent;
            color: #284b32;
            font-size: 14px;
            text-decoration: none;
        }

        .admin-giris-sayfasi .giris-ust nav a:hover {
            background: #e6eddf;
        }

        .admin-giris-sayfasi .giris-ana {
            display: flex;
            flex: 1;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 40px 20px;
        }

        .admin-giris-sayfasi .giris {
            width: 100%;
            max-width: 420px;
            margin: 0;
            padding: 34px;
            border: 1px solid #ded6c6;
            border-radius: 18px;
            background: #fffdf8;
            text-align: center;
            box-shadow: 0 10px 30px rgba(40, 75, 50, 0.08);
        }

        .admin-giris-sayfasi .giris-logo-resim {
            display: block;
            width: 80px;
            height: 80px;
            max-width: 100%;
            margin: 0 auto 20px;
            object-fit: contain;
        }

        .admin-giris-sayfasi .giris h2 {
            margin: 0 0 12px;
            color: #284b32;
            font-size: 27px;
        }

        .admin-giris-sayfasi .giris p {
            margin: 8px 0;
            color: #687361;
            line-height: 1.6;
        }

        .admin-giris-sayfasi .giris .giris-aciklama {
            margin-bottom: 26px;
            font-size: 13px;
        }

        .admin-giris-sayfasi .giris form {
            display: grid;
            gap: 16px;
            text-align: left;
        }

        .admin-giris-sayfasi .giris label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: bold;
        }

        .admin-giris-sayfasi .giris input {
            display: block;
            width: 100%;
            margin: 0;
            padding: 13px 14px;
            border: 1px solid #d6d9c9;
            border-radius: 9px;
            background: #fff;
            color: #284b32;
            font: inherit;
        }

        .admin-giris-sayfasi .giris input:focus-visible {
            outline: 2px solid #426b46;
            outline-offset: 2px;
        }

        .admin-giris-sayfasi .giris button {
            width: 100%;
            margin-top: 4px;
            padding: 14px;
            border: 0;
            border-radius: 9px;
            background: #426b46;
            color: #fff;
            font: inherit;
            font-weight: bold;
            cursor: pointer;
        }

        .admin-giris-sayfasi .giris button:hover {
            background: #284b32;
        }

        .admin-giris-sayfasi .giris button:focus-visible,
        .admin-giris-sayfasi a:focus-visible {
            outline: 2px solid #426b46;
            outline-offset: 3px;
        }

        .admin-giris-sayfasi .giris .hata {
            margin: 0 0 20px;
            padding: 12px;
            border: 1px solid #edccc7;
            border-radius: 8px;
            background: #f9ecea;
            color: #a63c35;
            font-size: 14px;
        }

        @media (max-width: 650px) {
            body.admin-giris-sayfasi > header.giris-ust {
                justify-content: center;
                padding: 16px;
            }

            .admin-giris-sayfasi .giris-ust nav {
                width: 100%;
            }

            .admin-giris-sayfasi .giris-ust nav a {
                padding: 8px 10px;
                font-size: 13px;
            }

            .admin-giris-sayfasi .giris {
                padding: 28px 22px;
            }

            .admin-giris-sayfasi .giris h2 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body class="admin-giris-sayfasi">

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
            width="80"
            height="80"
        >

        <h2 id="giris-baslik">Yönetici Girişi</h2>

        <p>Aris Pati Yuvası</p>
        <p class="giris-aciklama">Her can dostuna bir yuva</p>

        <?php if ($hata !== ""): ?>
            <p class="hata" role="alert">
                <?php
                echo htmlspecialchars(
                    $hata,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>
            </p>
        <?php endif; ?>

        <form action="admin-giris.php" method="POST">

            <div>
                <label for="kullanici_adi">Kullanıcı Adı</label>

                <input
                    type="text"
                    id="kullanici_adi"
                    name="kullanici_adi"
                    value="<?php
                    echo htmlspecialchars(
                        $kullanici_adi,
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>"
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