<?php
session_start();

require_once __DIR__ . "/baglanti.php";

function guvenli($deger)
{
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

$bildirim = "";
$basarili = false;

$ad = trim((string)($_SESSION["uye_adsoyad"] ?? ""));
$eposta = trim((string)($_SESSION["uye_email"] ?? ""));
$mesaj = "";

if (empty($_SESSION["iletisim_token"])) {
    $_SESSION["iletisim_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $ad = is_string($_POST["ad"] ?? null)
        ? trim($_POST["ad"])
        : "";

    $eposta = is_string($_POST["eposta"] ?? null)
        ? trim($_POST["eposta"])
        : "";

    $mesaj = is_string($_POST["mesaj"] ?? null)
        ? trim($_POST["mesaj"])
        : "";

    $token = $_POST["token"] ?? "";

    if (!isset($_SESSION["uye_id"])) {
        $bildirim = "Mesaj göndermek için giriş yapmalısınız.";
    } elseif (
        !is_string($token)
        || !hash_equals($_SESSION["iletisim_token"], $token)
    ) {
        $bildirim = "Sayfayı yenileyip tekrar deneyiniz.";
    } elseif ($ad === "" || $eposta === "" || $mesaj === "") {
        $bildirim = "Lütfen ad soyad, e-posta ve mesaj alanlarını doldurunuz.";
    } elseif (mb_strlen($ad, "UTF-8") > 100) {
        $bildirim = "Ad soyad en fazla 100 karakter olabilir.";
    } elseif (
        strlen($eposta) > 150
        || !filter_var($eposta, FILTER_VALIDATE_EMAIL)
    ) {
        $bildirim = "Lütfen geçerli bir e-posta adresi yazınız.";
    } elseif (mb_strlen($mesaj, "UTF-8") > 5000) {
        $bildirim = "Mesajınız en fazla 5000 karakter olabilir.";
    } else {
        $uye_id = (int)$_SESSION["uye_id"];

        try {
            $sorgu = mysqli_prepare(
                $baglanti,
                "INSERT INTO mesajlar
                    (uye_id, ad_soyad, email, mesaj)
                 VALUES (?, ?, ?, ?)"
            );

            if ($sorgu) {
                mysqli_stmt_bind_param(
                    $sorgu,
                    "isss",
                    $uye_id,
                    $ad,
                    $eposta,
                    $mesaj
                );

                if (mysqli_stmt_execute($sorgu)) {
                    $bildirim = "Mesajınız yöneticiye gönderildi.";
                    $basarili = true;
                    $mesaj = "";
                } else {
                    $bildirim = "Mesaj gönderilemedi. Lütfen tekrar deneyiniz.";
                }

                mysqli_stmt_close($sorgu);
            } else {
                $bildirim = "Mesaj gönderilemedi. Lütfen tekrar deneyiniz.";
            }
        } catch (mysqli_sql_exception $e) {
            $bildirim = "Mesaj gönderilemedi. Lütfen tekrar deneyiniz.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>İletişim | Aris Pati Yuvası</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        body {
            background-color: #f5f8f5;
        }

        .iletisim {
            box-sizing: border-box;
            width: min(650px, 100%);
            margin: 40px auto;
            padding: 40px;
            background-color: white;
            border: 1px solid #dce8df;
            border-radius: 15px;
        }

        .iletisim h2 {
            color: #184f3c;
            text-align: center;
            font-size: 28px;
            margin-bottom: 25px;
        }

        .iletisim p {
            color: #526b5a;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .iletisim-formu {
            margin-top: 30px;
        }

        .iletisim .form-grup {
            margin-bottom: 20px;
        }

        .iletisim .form-grup label {
            display: block;
            color: #184f3c;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .iletisim .form-grup input,
        .iletisim .form-grup textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            border: 1px solid #cbded0;
            border-radius: 8px;
            background: #fff;
            color: #284b32;
            font-family: Arial, sans-serif;
            font-size: 15px;
        }

        .iletisim .form-grup textarea {
            resize: vertical;
        }

        .iletisim .form-grup small {
            display: block;
            margin-top: 8px;
            color: #687361;
            font-size: 12px;
            line-height: 1.6;
        }

        .iletisim input:focus-visible,
        .iletisim textarea:focus-visible,
        .iletisim button:focus-visible {
            outline: 2px solid #347a53;
            outline-offset: 3px;
        }

        .iletisim .btn-gonder {
            background-color: #347a53;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
        }

        .iletisim .btn-gonder:hover {
            background-color: #245c3d;
        }

        .iletisim .bildirim {
            padding: 14px;
            border-radius: 8px;
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .iletisim .bildirim.basarili {
            background: #e6eddf;
            color: #284b32;
        }

        .iletisim .bildirim.hata {
            background: #f9ecea;
            color: #a63c35;
        }

        @media (max-width: 600px) {
            .iletisim {
                padding: 24px 18px;
                margin: 20px auto;
            }

            .iletisim h2 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

<header class="ilan-ust">
    <a href="index.php" class="ilan-logo">
        <span class="logo-pati">🐾</span>

        <span>
            <strong>Aris Pati Yuvası</strong>
            <small>Her can dostuna bir yuva</small>
        </span>
    </a>

    <nav class="ilan-menu" aria-label="Site menüsü">
        <a href="index.php">Ana Sayfa</a>
        <a href="ilanlar.php">İlanlar</a>
        <a href="ilan-ver.php">İlan Ver</a>
        <a href="hakkimizda.php">Hakkımızda</a>
        <a href="iletisim.php" aria-current="page">İletişim</a>

        <?php if (isset($_SESSION["uye_id"])): ?>
            <a href="profil.php">Profilim</a>

            <a
                href="cikis.php"
                onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');"
            >
                Çıkış Yap
            </a>
        <?php else: ?>
            <a href="giris.php">Giriş Yap</a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <section class="iletisim">

        <h2>Bir Mesajınız mı Var? 🐾</h2>

        <p>
            Aris Pati Yuvası olarak sizlerden haber almak bizi mutlu eder!
            Minik dostlarımızla ilgili merak ettikleriniz,
            önerileriniz veya bizimle paylaşmak istediğiniz
            düşünceleriniz varsa buradayız.
        </p>

        <p>
            Aşağıdaki formu doldurarak bize kolayca ulaşabilirsiniz.
            Her mesajınız bizim için değerli. 💚
        </p>

        <?php if ($bildirim !== ""): ?>
            <p
                class="bildirim <?php echo $basarili ? 'basarili' : 'hata'; ?>"
                role="<?php echo $basarili ? 'status' : 'alert'; ?>"
            >
                <?php echo guvenli($bildirim); ?>
            </p>
        <?php endif; ?>

        <?php if (isset($_SESSION["uye_id"])): ?>

            <form
                action="iletisim.php"
                method="POST"
                class="iletisim-formu"
            >
                <input
                    type="hidden"
                    name="token"
                    value="<?php echo guvenli($_SESSION["iletisim_token"]); ?>"
                >

                <div class="form-grup">
                    <label for="ad">Adınız Soyadınız:</label>

                    <input
                        type="text"
                        id="ad"
                        name="ad"
                        value="<?php echo guvenli($ad); ?>"
                        autocomplete="name"
                        maxlength="100"
                        aria-describedby="ad-aciklama"
                        required
                    >

                    <small id="ad-aciklama">
                        Bu mesajda görünecek ad soyadı değiştirebilirsiniz.
                        Üyelik bilgileriniz değişmez.
                    </small>
                </div>

                <div class="form-grup">
                    <label for="eposta">E-posta Adresiniz:</label>

                    <input
                        type="email"
                        id="eposta"
                        name="eposta"
                        value="<?php echo guvenli($eposta); ?>"
                        autocomplete="email"
                        maxlength="150"
                        aria-describedby="eposta-aciklama"
                        required
                    >

                    <small id="eposta-aciklama">
                        Yanıtın gelmesini istediğiniz e-posta adresini yazınız.
                        Üyelik e-posta adresiniz değişmez.
                    </small>
                </div>

                <div class="form-grup">
                    <label for="mesaj">Sorunuz:</label>

                    <textarea
                        id="mesaj"
                        name="mesaj"
                        rows="5"
                        maxlength="5000"
                        required
                        placeholder="Yöneticiye sorunuzu yazınız..."
                    ><?php echo guvenli($mesaj); ?></textarea>
                </div>

                <button type="submit" class="btn-gonder">
                    Soruyu Gönder
                </button>
            </form>

        <?php else: ?>

            <p>
                Yöneticiye soru göndermek için
                üye girişi yapmalısınız.
            </p>

            <a href="giris.php">Giriş Yap</a>

        <?php endif; ?>

    </section>
</main>

</body>
</html>