<?php
session_start();

if (
    !isset($_SESSION["admin"])
    || !is_string($_SESSION["admin"])
    || $_SESSION["admin"] === ""
) {
    header("Location: admin-giris.php");
    exit;
}

require_once __DIR__ . "/../baglanti.php";

function ap_guvenli($deger)
{
    return htmlspecialchars((string) $deger, ENT_QUOTES, "UTF-8");
}

function ap_post($alan)
{
    return is_string($_POST[$alan] ?? null) ? $_POST[$alan] : "";
}

function ap_getir($baglanti, $sql, $tipler, ...$degerler)
{
    $sorgu = mysqli_prepare($baglanti, $sql);
    mysqli_stmt_bind_param($sorgu, $tipler, ...$degerler);
    mysqli_stmt_execute($sorgu);

    $sonuc = mysqli_stmt_get_result($sorgu);
    $veriler = mysqli_fetch_all($sonuc, MYSQLI_ASSOC);

    mysqli_stmt_close($sorgu);

    return $veriler;
}

/* Giriş yapan adminin kaydını getir. */

$kayitlar = ap_getir(
    $baglanti,
    "SELECT id, kullanici_adi, ad_soyad, email, telefon, sifre
     FROM admin
     WHERE kullanici_adi = ?
     LIMIT 2",
    "s",
    $_SESSION["admin"]
);

if (count($kayitlar) !== 1) {
    unset($_SESSION["admin"], $_SESSION["admin_profil_csrf"]);
    header("Location: admin-giris.php");
    exit;
}

$yonetici = $kayitlar[0];
$admin_id = (int) $yonetici["id"];

if (!is_string($_SESSION["admin_profil_csrf"] ?? null)) {
    $_SESSION["admin_profil_csrf"] = bin2hex(random_bytes(32));
}

$bilgi_hata = "";
$sifre_hata = "";

$basari = is_string($_SESSION["admin_profil_basari"] ?? null)
    ? $_SESSION["admin_profil_basari"]
    : "";

unset($_SESSION["admin_profil_basari"]);

$form_ad = (string) ($yonetici["ad_soyad"] ?? "");
$form_kullanici = (string) $yonetici["kullanici_adi"];
$form_email = (string) ($yonetici["email"] ?? "");
$form_telefon = (string) ($yonetici["telefon"] ?? "");

/* Profil işlemleri */

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $islem = ap_post("islem");
    $token = ap_post("csrf_token");

    if ($islem === "bilgi_guncelle") {
        $form_ad = trim(ap_post("ad_soyad"));
        $form_kullanici = trim(ap_post("kullanici_adi"));
        $form_email = trim(ap_post("email"));
        $form_telefon = trim(ap_post("telefon"));
    }

    if (!hash_equals($_SESSION["admin_profil_csrf"], $token)) {
        $mesaj = "İşlem doğrulanamadı. Sayfayı yenileyip tekrar deneyiniz.";

        if ($islem === "sifre_degistir") {
            $sifre_hata = $mesaj;
        } else {
            $bilgi_hata = $mesaj;
        }
    } elseif ($islem === "bilgi_guncelle") {
        $mevcut_sifre = ap_post("bilgi_sifre");

        if (
            $form_ad === ""
            || $form_kullanici === ""
            || $form_email === ""
            || $form_telefon === ""
            || $mevcut_sifre === ""
        ) {
            $bilgi_hata = "Lütfen tüm alanları doldurunuz.";
        } elseif (
            preg_match_all('/./us', $form_ad) > 100
            || preg_match_all('/./us', $form_kullanici) > 50
        ) {
            $bilgi_hata = "Ad soyad en fazla 100, kullanıcı adı en fazla 50 karakter olabilir.";
        } elseif (
            strlen($form_email) > 254
            || !filter_var($form_email, FILTER_VALIDATE_EMAIL)
        ) {
            $bilgi_hata = "Geçerli bir e-posta adresi giriniz.";
        } elseif (!preg_match('/^05[0-9]{9}$/', $form_telefon)) {
            $bilgi_hata = "Telefon numarası 05 ile başlamalı ve 11 haneli olmalıdır.";
        } elseif (!password_verify($mevcut_sifre, (string) $yonetici["sifre"])) {
            $bilgi_hata = "Mevcut şifreniz yanlış.";
        } else {
            try {
                $ayni_kayit = ap_getir(
                    $baglanti,
                    "SELECT id FROM admin
                     WHERE id <> ?
                       AND (kullanici_adi = ? OR email = ?)
                     LIMIT 1",
                    "iss",
                    $admin_id,
                    $form_kullanici,
                    $form_email
                );

                if ($ayni_kayit) {
                    $bilgi_hata = "Bu kullanıcı adı veya e-posta başka bir admin tarafından kullanılıyor.";
                } else {
                    $sorgu = mysqli_prepare(
                        $baglanti,
                        "UPDATE admin
                         SET ad_soyad = ?, kullanici_adi = ?,
                             email = ?, telefon = ?
                         WHERE id = ?"
                    );

                    mysqli_stmt_bind_param(
                        $sorgu,
                        "ssssi",
                        $form_ad,
                        $form_kullanici,
                        $form_email,
                        $form_telefon,
                        $admin_id
                    );

                    $kaydedildi = mysqli_stmt_execute($sorgu);
                    mysqli_stmt_close($sorgu);

                    if ($kaydedildi) {
                        $_SESSION["admin"] = $form_kullanici;
                        $_SESSION["admin_profil_basari"] =
                            "Bilgileriniz başarıyla güncellendi.";

                        header("Location: admin-profil.php");
                        exit;
                    }

                    $bilgi_hata = "Bilgiler kaydedilemedi. Tekrar deneyiniz.";
                }
            } catch (mysqli_sql_exception $e) {
                $bilgi_hata = "Bilgiler kaydedilemedi. Girdiğiniz bilgileri kontrol ediniz.";
            }
        }
    } elseif ($islem === "sifre_degistir") {
        $mevcut_sifre = ap_post("mevcut_sifre");
        $yeni_sifre = ap_post("yeni_sifre");
        $sifre_tekrar = ap_post("sifre_tekrar");

        if (
            $mevcut_sifre === ""
            || $yeni_sifre === ""
            || $sifre_tekrar === ""
        ) {
            $sifre_hata = "Lütfen tüm şifre alanlarını doldurunuz.";
        } elseif (!password_verify($mevcut_sifre, (string) $yonetici["sifre"])) {
            $sifre_hata = "Mevcut şifreniz yanlış.";
        } elseif (preg_match_all('/./us', $yeni_sifre) < 8) {
            $sifre_hata = "Yeni şifreniz en az 8 karakter olmalıdır.";
        } elseif (strlen($yeni_sifre) > 72) {
            $sifre_hata = "Yeni şifreniz çok uzun. Daha kısa bir şifre giriniz.";
        } elseif ($yeni_sifre !== $sifre_tekrar) {
            $sifre_hata = "Yeni şifreler birbiriyle eşleşmiyor.";
        } elseif (password_verify($yeni_sifre, (string) $yonetici["sifre"])) {
            $sifre_hata = "Yeni şifreniz mevcut şifrenizden farklı olmalıdır.";
        } else {
            $yeni_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);

            try {
                $sorgu = mysqli_prepare(
                    $baglanti,
                    "UPDATE admin
                     SET sifre = ?
                     WHERE id = ? AND sifre = ?"
                );

                mysqli_stmt_bind_param(
                    $sorgu,
                    "sis",
                    $yeni_hash,
                    $admin_id,
                    $yonetici["sifre"]
                );

                $kaydedildi = mysqli_stmt_execute($sorgu);
                $degisen = mysqli_stmt_affected_rows($sorgu);

                mysqli_stmt_close($sorgu);

                if ($kaydedildi && $degisen === 1) {
                    session_regenerate_id(true);

                    $_SESSION["admin_profil_csrf"] = bin2hex(random_bytes(32));
                    $_SESSION["admin_profil_basari"] =
                        "Şifreniz başarıyla değiştirildi.";

                    header("Location: admin-profil.php");
                    exit;
                }

                $sifre_hata = "Şifre değiştirilemedi. Sayfayı yenileyip tekrar deneyiniz.";
            } catch (mysqli_sql_exception $e) {
                $sifre_hata = "Şifre değiştirilemedi. Tekrar deneyiniz.";
            }
        }
    } else {
        $bilgi_hata = "Geçersiz işlem.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Profilim | Admin Paneli</title>

    <link rel="stylesheet" href="admin-style.css?v=15">

    <style>
        .ap-profil,
        .ap-profil *,
        .ap-basari {
            box-sizing: border-box;
        }

        .ap-profil {
            min-width: 0;
            color: #284b32;
        }

        .ap-profil > h2 {
            margin-bottom: 10px;
        }

        .ap-aciklama {
            margin: 0 0 20px;
            color: #687361;
            font-size: 14px;
            line-height: 1.6;
        }

        .ap-ozet,
        .ap-kutu {
            padding: 20px;
            border: 1px solid #ded6c6;
            border-radius: 12px;
            background: #fffdf8;
        }

        .ap-ozet {
            margin-bottom: 18px;
        }

        .ap-ozet h3,
        .ap-kutu h3 {
            margin: 0 0 14px;
            color: #284b32;
            font-size: 19px;
        }

        .ap-bilgiler {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 15px;
        }

        .ap-bilgiler p {
            margin: 0;
            color: #284b32;
            font-size: 14px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .ap-bilgiler strong {
            display: block;
            margin-bottom: 4px;
            color: #687361;
            font-size: 12px;
        }

        .ap-kutular {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: start;
            gap: 18px;
        }

        .ap-kutu {
            min-width: 0;
        }

        .ap-kutu > p {
            margin: 0 0 16px;
            color: #687361;
            font-size: 13px;
            line-height: 1.5;
        }

        .ap-profil .ap-form {
            display: grid;
            gap: 12px;
            width: 100%;
            margin: 0;
            padding: 0;
            border: 0;
            background: transparent;
            box-shadow: none;
        }

        .ap-form label {
            display: block;
            margin-bottom: 6px;
            color: #284b32;
            font-size: 13px;
            font-weight: bold;
        }

        .ap-profil .ap-form input {
            display: block;
            width: 100%;
            min-height: 40px;
            margin: 0;
            padding: 9px 11px;
            border: 1px solid #ded6c6;
            border-radius: 8px;
            background: white;
            color: #284b32;
            font: normal 14px Arial, sans-serif;
        }

        .ap-form small {
            display: block;
            margin-top: 5px;
            color: #687361;
            font-size: 12px;
            line-height: 1.4;
        }

        .ap-profil .ap-buton {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: auto;
            min-height: 37px;
            margin: 3px 0 0;
            padding: 10px 15px;
            border: 0;
            border-radius: 8px;
            background: #426b46;
            color: white;
            font: normal 13px Arial, sans-serif;
            cursor: pointer;
        }

        .ap-profil .ap-buton:hover {
            background: #284b32;
        }

        .ap-form input:focus-visible,
        .ap-buton:focus-visible {
            outline: 2px solid #426b46;
            outline-offset: 3px;
        }

        .ap-hata {
            margin-bottom: 15px;
            padding: 12px;
            border: 1px solid #e1b4ad;
            border-radius: 8px;
            background: #f8e1dd;
            color: #9b3b33;
            font-size: 13px;
            line-height: 1.6;
        }

        .ap-basari {
            position: fixed;
            top: 50%;
            left: 50%;
            z-index: 99999;
            transform: translate(-50%, -50%);
            width: min(380px, calc(100% - 40px));
            padding: 25px;
            border: 1px solid #b7d2ad;
            border-radius: 14px;
            background: #fffdf8;
            color: #284b32;
            box-shadow: 0 12px 45px rgba(0, 0, 0, .22);
            font-family: Arial, sans-serif;
            text-align: center;
            pointer-events: none;
            animation: ap-kaybol 2s forwards;
        }

        .ap-basari span {
            display: block;
            width: 44px;
            height: 44px;
            margin: 0 auto 12px;
            border-radius: 50%;
            background: #e0edda;
            color: #426b46;
            font-size: 28px;
            font-weight: bold;
            line-height: 44px;
        }

        .ap-basari p {
            margin: 0;
            font-size: 15px;
            line-height: 1.6;
        }

        @keyframes ap-kaybol {
            0%, 85% {
                opacity: 1;
                visibility: visible;
            }

            100% {
                opacity: 0;
                visibility: hidden;
            }
        }

        @media (max-width: 1100px) {
            .ap-bilgiler {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 800px) {
            .ap-kutular {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 500px) {
            .ap-bilgiler {
                grid-template-columns: 1fr;
            }

            .ap-ozet,
            .ap-kutu {
                padding: 16px;
            }
        }
    </style>
</head>

<body>

<?php require __DIR__ . "/admin-menu.php"; ?>

<main class="icerik ap-profil">
    <h2>Profilim</h2>

    <p class="ap-aciklama">
        Kendi yönetici bilgilerinizi ve şifrenizi buradan değiştirebilirsiniz.
    </p>

    <section class="ap-ozet">
        <h3>Yönetici Bilgilerim</h3>

        <div class="ap-bilgiler">
            <p>
                <strong>Ad Soyad</strong>
                <?= ap_guvenli($yonetici["ad_soyad"] ?: "Henüz eklenmedi") ?>
            </p>

            <p>
                <strong>Kullanıcı Adı</strong>
                <?= ap_guvenli($yonetici["kullanici_adi"]) ?>
            </p>

            <p>
                <strong>E-posta</strong>
                <?= ap_guvenli($yonetici["email"] ?: "Henüz eklenmedi") ?>
            </p>

            <p>
                <strong>Telefon</strong>
                <?= ap_guvenli($yonetici["telefon"] ?: "Henüz eklenmedi") ?>
            </p>
        </div>
    </section>

    <div class="ap-kutular">
        <section class="ap-kutu">
            <h3>Bilgilerimi Düzenle</h3>

            <p>Değişiklikleri kaydetmek için mevcut şifrenizi giriniz.</p>

            <?php if ($bilgi_hata !== ""): ?>
                <div class="ap-hata" role="alert">
                    <?= ap_guvenli($bilgi_hata) ?>
                </div>
            <?php endif; ?>

            <form action="admin-profil.php" method="POST" class="ap-form">
                <input type="hidden" name="islem" value="bilgi_guncelle">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= ap_guvenli($_SESSION["admin_profil_csrf"]) ?>"
                >

                <div>
                    <label for="ad_soyad">Ad Soyad</label>
                    <input
                        type="text"
                        id="ad_soyad"
                        name="ad_soyad"
                        value="<?= ap_guvenli($form_ad) ?>"
                        maxlength="100"
                        autocomplete="name"
                        required
                    >
                </div>

                <div>
                    <label for="kullanici_adi">Kullanıcı Adı</label>
                    <input
                        type="text"
                        id="kullanici_adi"
                        name="kullanici_adi"
                        value="<?= ap_guvenli($form_kullanici) ?>"
                        maxlength="50"
                        autocomplete="username"
                        aria-describedby="kullanici-notu"
                        required
                    >
                    <small id="kullanici-notu">
                        Değiştirirseniz sonraki girişinizde yeni kullanıcı adınızı kullanınız.
                    </small>
                </div>

                <div>
                    <label for="email">E-posta Adresi</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= ap_guvenli($form_email) ?>"
                        maxlength="254"
                        autocomplete="email"
                        required
                    >
                </div>

                <div>
                    <label for="telefon">Telefon Numarası</label>
                    <input
                        type="tel"
                        id="telefon"
                        name="telefon"
                        value="<?= ap_guvenli($form_telefon) ?>"
                        placeholder="05XXXXXXXXX"
                        pattern="05[0-9]{9}"
                        maxlength="11"
                        inputmode="numeric"
                        autocomplete="tel"
                        aria-describedby="telefon-notu"
                        required
                    >
                    <small id="telefon-notu">
                        05 ile başlayan 11 haneli numaranızı boşluksuz yazınız.
                    </small>
                </div>

                <div>
                    <label for="bilgi_sifre">Mevcut Şifreniz</label>
                    <input
                        type="password"
                        id="bilgi_sifre"
                        name="bilgi_sifre"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div>
                    <button type="submit" class="ap-buton">
                        Bilgilerimi Kaydet
                    </button>
                </div>
            </form>
        </section>

        <section class="ap-kutu">
            <h3>Şifremi Değiştir</h3>

            <p>Yeni şifreniz en az 8 karakter olmalıdır.</p>

            <?php if ($sifre_hata !== ""): ?>
                <div class="ap-hata" role="alert">
                    <?= ap_guvenli($sifre_hata) ?>
                </div>
            <?php endif; ?>

            <form action="admin-profil.php" method="POST" class="ap-form">
                <input type="hidden" name="islem" value="sifre_degistir">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= ap_guvenli($_SESSION["admin_profil_csrf"]) ?>"
                >

                <div>
                    <label for="mevcut_sifre">Mevcut Şifre</label>
                    <input
                        type="password"
                        id="mevcut_sifre"
                        name="mevcut_sifre"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div>
                    <label for="yeni_sifre">Yeni Şifre</label>
                    <input
                        type="password"
                        id="yeni_sifre"
                        name="yeni_sifre"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <div>
                    <label for="sifre_tekrar">Yeni Şifre Tekrarı</label>
                    <input
                        type="password"
                        id="sifre_tekrar"
                        name="sifre_tekrar"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <div>
                    <button type="submit" class="ap-buton">
                        Şifremi Değiştir
                    </button>
                </div>
            </form>
        </section>
    </div>
</main>

<?php if ($basari !== ""): ?>
    <div class="ap-basari" id="ap-basari" role="status">
        <span aria-hidden="true">✓</span>
        <p><?= ap_guvenli($basari) ?></p>
    </div>
<?php endif; ?>

<script>
    const basariKutusu = document.getElementById("ap-basari");

    if (basariKutusu) {
        setTimeout(function () {
            basariKutusu.remove();
        }, 2000);
    }
</script>

</body>
</html>