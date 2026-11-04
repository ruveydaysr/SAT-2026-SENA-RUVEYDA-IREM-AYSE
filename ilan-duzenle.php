<?php
session_start();
require_once __DIR__ . "/site-ust-ayar.php";

if (!isset($_SESSION["uye_id"])) {
    header("Location: giris.php");
    exit();
}

require_once __DIR__ . "/baglanti.php";

function id_guvenli($deger)
{
    return htmlspecialchars(
        (string) $deger,
        ENT_QUOTES,
        "UTF-8"
    );
}

function id_post($alan)
{
    return is_string($_POST[$alan] ?? null)
        ? trim($_POST[$alan])
        : "";
}

$uye_id = (int) $_SESSION["uye_id"];
$ilan_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$ilan_id || $ilan_id < 1) {
    header("Location: profil.php");
    exit();
}

$sorgu = mysqli_prepare(
    $baglanti,
    "SELECT * FROM ilanlar WHERE id = ? AND uye_id = ?"
);

mysqli_stmt_bind_param($sorgu, "ii", $ilan_id, $uye_id);
mysqli_stmt_execute($sorgu);

$sonuc = mysqli_stmt_get_result($sorgu);
$ilan = mysqli_fetch_assoc($sonuc);

mysqli_stmt_close($sorgu);

if (!$ilan) {
    http_response_code(403);
    exit("Bu ilanı düzenleme yetkiniz yok veya ilan bulunamadı.");
}

$iller = [
    "Adana", "Adıyaman", "Afyonkarahisar", "Ağrı",
    "Aksaray", "Amasya", "Ankara", "Antalya", "Ardahan",
    "Artvin", "Aydın", "Balıkesir", "Bartın", "Batman",
    "Bayburt", "Bilecik", "Bingöl", "Bitlis", "Bolu",
    "Burdur", "Bursa", "Çanakkale", "Çankırı", "Çorum",
    "Denizli", "Diyarbakır", "Düzce", "Edirne", "Elazığ",
    "Erzincan", "Erzurum", "Eskişehir", "Gaziantep",
    "Giresun", "Gümüşhane", "Hakkâri", "Hatay", "Iğdır",
    "Isparta", "İstanbul", "İzmir", "Kahramanmaraş",
    "Karabük", "Karaman", "Kars", "Kastamonu", "Kayseri",
    "Kırıkkale", "Kırklareli", "Kırşehir", "Kilis",
    "Kocaeli", "Konya", "Kütahya", "Malatya", "Manisa",
    "Mardin", "Mersin", "Muğla", "Muş", "Nevşehir",
    "Niğde", "Ordu", "Osmaniye", "Rize", "Sakarya",
    "Samsun", "Siirt", "Sinop", "Sivas", "Şanlıurfa",
    "Şırnak", "Tekirdağ", "Tokat", "Trabzon", "Tunceli",
    "Uşak", "Van", "Yalova", "Yozgat", "Zonguldak"
];

$turler = [
    "Kedi", "Köpek", "Kuş", "Tavşan",
    "Balık", "Kemirgen", "Sürüngen", "Diğer"
];

$ozellik_secenekleri = [
    "Karakter Özellikleri" => [
        "Oyuncu", "Sakin", "Sevecen",
        "Hareketli", "Uysal", "Çekingen"
    ],
    "Sosyal Özellikleri" => [
        "Çocuklarla anlaşır",
        "Diğer hayvanlarla anlaşır",
        "İnsanlara alışkın"
    ],
    "Bakım ve Sağlık" => [
        "Tuvalet eğitimi var",
        "Aşıları yapıldı",
        "Kısırlaştırıldı",
        "Özel bakım gerektiriyor"
    ],
    "Yaşam Ortamı" => [
        "Ev ortamına alışkın",
        "Apartman yaşamına uygun",
        "Bahçeli ev için uygun"
    ]
];

$tum_ozellikler = array_merge(
    ...array_values($ozellik_secenekleri)
);

$kayitli_aciklama = (string) ($ilan["aciklama"] ?? "");

$parcalar = explode(
    "\n\nEk Açıklama: ",
    $kayitli_aciklama,
    2
);

$ilk_bolum = array_values(array_filter(
    array_map("trim", explode(",", $parcalar[0])),
    function ($deger) {
        return $deger !== "";
    }
));

$kayitli_ozellikler = array_values(
    array_intersect($tum_ozellikler, $ilk_bolum)
);

$kayitli_ek_aciklama = $parcalar[1] ?? "";

$eski_aciklama = array_diff(
    $ilk_bolum,
    $tum_ozellikler
);

if ($eski_aciklama) {
    $kayitli_ek_aciklama = trim(
        implode(", ", $eski_aciklama)
        . "\n" . $kayitli_ek_aciklama
    );
}

$form = [
    "ilan_basligi" => (string) ($ilan["ilan_basligi"] ?? ""),
    "hayvan_adi" => (string) ($ilan["hayvan_adi"] ?? ""),
    "tur" => (string) ($ilan["tur"] ?? ""),
    "yas" => (string) ($ilan["yas"] ?? ""),
    "cinsiyet" => (string) ($ilan["cinsiyet"] ?? ""),
    "sehir" => (string) ($ilan["sehir"] ?? "")
];

$eski_sehir = $form["sehir"];
$hata = "";

if (!is_string($_SESSION["ilan_duzenle_token"] ?? null)) {
    $_SESSION["ilan_duzenle_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach (array_keys($form) as $alan) {
        $form[$alan] = id_post($alan);
    }

    $gelen_ozellikler = $_POST["ozellikler"] ?? [];

    if (!is_array($gelen_ozellikler)) {
        $gelen_ozellikler = [];
    }

    $gelen_ozellikler = array_filter(
        $gelen_ozellikler,
        "is_string"
    );

    $kayitli_ozellikler = array_values(
        array_intersect($tum_ozellikler, $gelen_ozellikler)
    );

    $kayitli_ek_aciklama = id_post("ek_aciklama");

    $yas = filter_var($form["yas"], FILTER_VALIDATE_INT);

    $sehir_gecerli = in_array($form["sehir"], $iller, true)
        || (
            $eski_sehir !== ""
            && $form["sehir"] === $eski_sehir
        );

    if (!hash_equals(
        $_SESSION["ilan_duzenle_token"],
        id_post("token")
    )) {
        $hata = "Sayfayı yenileyip tekrar deneyiniz.";

    } elseif (
        $form["ilan_basligi"] === "" ||
        $form["hayvan_adi"] === "" ||
        !in_array($form["tur"], $turler, true) ||
        $yas === false ||
        $yas < 0 ||
        !in_array($form["cinsiyet"], ["Dişi", "Erkek"], true) ||
        !$sehir_gecerli ||
        !$kayitli_ozellikler
    ) {
        $hata = "Lütfen tüm alanları doğru doldurunuz ve en az bir özellik seçiniz.";

    } elseif (
        preg_match_all('/./us', $form["ilan_basligi"]) > 150
    ) {
        $hata = "İlan başlığı en fazla 150 karakter olabilir.";

    } else {
        $aciklama = implode(", ", $kayitli_ozellikler);

        if ($kayitli_ek_aciklama !== "") {
            $aciklama .= "\n\nEk Açıklama: "
                . $kayitli_ek_aciklama;
        }

        $guncelle = mysqli_prepare(
            $baglanti,
            "UPDATE ilanlar
             SET ilan_basligi = ?,
                 hayvan_adi = ?,
                 tur = ?,
                 yas = ?,
                 cinsiyet = ?,
                 sehir = ?,
                 aciklama = ?
             WHERE id = ? AND uye_id = ?"
        );

        mysqli_stmt_bind_param(
            $guncelle,
            "sssisssii",
            $form["ilan_basligi"],
            $form["hayvan_adi"],
            $form["tur"],
            $yas,
            $form["cinsiyet"],
            $form["sehir"],
            $aciklama,
            $ilan_id,
            $uye_id
        );

        $kaydedildi = mysqli_stmt_execute($guncelle);

        mysqli_stmt_close($guncelle);

        if ($kaydedildi) {
            header("Location: profil.php");
            exit();
        }

        $hata = "İlan güncellenirken bir hata oluştu.";
    }
}

/* Mevcut CSS dosyasını bul ve önbellek sürümünü güncelle. */
$css_dosyasi = "";

foreach (["style.css", "css/style.css"] as $aday) {
    if (is_file(__DIR__ . "/" . $aday)) {
        $css_dosyasi = $aday;
        break;
    }
}

$css_surumu = $css_dosyasi !== ""
    ? filemtime(__DIR__ . "/" . $css_dosyasi)
    : "";
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>İlanımı Düzenle | Aris Pati Yuvası</title>

    <?php if ($css_dosyasi !== ""): ?>
        <link
            rel="stylesheet"
            href="<?= id_guvenli($css_dosyasi) ?>?v=<?= id_guvenli($css_surumu) ?>"
        >
    <?php endif; ?>

    <style>
        body.ilan-duzenle-sayfasi {
            margin: 0;
            background: #f5f0e6;
            color: #284b32;
            font-family: Arial, sans-serif;
        }

        .ilan-duzenle-sayfasi .duzenle-ust {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            padding: 18px 5%;
            border-bottom: 1px solid #ded6c6;
            background: #fffdf8;
        }

        .ilan-duzenle-sayfasi .duzenle-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: #284b32;
            text-decoration: none;
        }

        .ilan-duzenle-sayfasi .duzenle-logo img {
            display: block;
            width: 58px;
            height: 58px;
            max-width: 58px;
            flex-shrink: 0;
            object-fit: contain;
        }

        .duzenle-logo strong {
            display: block;
            font-size: 22px;
        }

        .duzenle-logo small {
            display: block;
            margin-top: 5px;
            color: #687361;
            font-size: 12px;
        }

        .ilan-duzenle-sayfasi .duzenle-menu {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 6px;
            margin: 0;
            padding: 0;
            border: 0;
            background: transparent;
        }

        .ilan-duzenle-sayfasi .duzenle-menu a {
            margin: 0;
            padding: 10px 12px;
            border-radius: 8px;
            color: #284b32;
            font-size: 14px;
            text-decoration: none;
        }

        .duzenle-menu a:hover,
        .duzenle-menu a[aria-current="page"] {
            background: #e6eddf;
        }

        .duzenle-ana {
            width: min(900px, calc(100% - 32px));
            margin: 0 auto;
        }

        .duzenle-baslik {
            margin: 38px 0 24px;
            text-align: center;
        }

        .duzenle-baslik h1 {
            margin: 0 0 12px;
            color: #284b32;
            font-size: 30px;
        }

        .duzenle-baslik p {
            color: #687361;
            line-height: 1.6;
        }

        .duzenle-kutusu {
            margin-bottom: 45px;
            padding: 30px;
            border: 1px solid #ded6c6;
            border-top: 4px solid #80a86d;
            border-radius: 16px;
            background: #fffdf8;
            box-shadow: 0 6px 20px rgba(40, 75, 50, .06);
        }

        .ilan-duzenle-sayfasi .duzenle-form {
            display: block;
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 0;
            border: 0;
            background: transparent;
            box-shadow: none;
        }

        .duzenle-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .duzenle-alan {
            min-width: 0;
        }

        .duzenle-alan label,
        .duzenle-ek-etiket {
            display: block;
            margin-bottom: 8px;
            color: #284b32;
            font-size: 14px;
            font-weight: bold;
        }

        .ilan-duzenle-sayfasi .duzenle-alan input,
        .ilan-duzenle-sayfasi .duzenle-alan select,
        .ilan-duzenle-sayfasi .duzenle-form textarea {
            display: block;
            box-sizing: border-box;
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 13px;
            border: 1px solid #d3ddce;
            border-radius: 8px;
            background: white;
            color: #284b32;
            font: normal 14px Arial, sans-serif;
        }

        .duzenle-form textarea {
            min-height: 110px;
            resize: vertical;
        }

        .duzenle-ozellikler {
            min-width: 0;
            margin: 28px 0 24px;
            padding: 0;
            border: 0;
        }

        .duzenle-ozellikler legend {
            padding: 0;
            color: #284b32;
            font-size: 17px;
            font-weight: bold;
        }

        .duzenle-ozellikler > p {
            color: #687361;
            font-size: 13px;
            line-height: 1.6;
        }

        .duzenle-ozellikler h3 {
            margin: 22px 0 12px;
            color: #426b46;
            font-size: 15px;
        }

        .duzenle-ozellik-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .ilan-duzenle-sayfasi .duzenle-ozellik-secimi {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0;
            color: #284b32;
            font-size: 13px;
            font-weight: normal;
            line-height: 1.5;
            cursor: pointer;
        }

        .ilan-duzenle-sayfasi .duzenle-ozellik-secimi input {
            display: inline-block;
            width: 16px;
            height: 16px;
            margin: 0;
            padding: 0;
            flex-shrink: 0;
            accent-color: #426b46;
        }

        .duzenle-islemler {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        .ilan-duzenle-sayfasi .duzenle-kaydet,
        .ilan-duzenle-sayfasi .duzenle-iptal {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: auto;
            margin: 0;
            padding: 13px 20px;
            border: 0;
            border-radius: 8px;
            background: #426b46;
            color: white;
            font: bold 14px Arial, sans-serif;
            text-decoration: none;
            cursor: pointer;
        }

        .ilan-duzenle-sayfasi .duzenle-iptal {
            background: #eee5d3;
            color: #284b32;
        }

        .duzenle-kaydet:hover {
            background: #284b32;
        }

        .duzenle-hata {
            margin-bottom: 22px;
            padding: 14px;
            border: 1px solid #e2bbb3;
            border-radius: 8px;
            background: #f9e8e3;
            color: #923e30;
            line-height: 1.6;
        }

        .ilan-duzenle-sayfasi :focus-visible {
            outline: 2px solid #426b46;
            outline-offset: 3px;
        }

        @media (max-width: 800px) {
            .ilan-duzenle-sayfasi .duzenle-ust {
                justify-content: center;
            }

            .ilan-duzenle-sayfasi .duzenle-menu {
                justify-content: center;
            }
        }

        @media (max-width: 600px) {
            .duzenle-form-grid {
                grid-template-columns: 1fr;
            }

            .duzenle-ozellik-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .duzenle-kutusu {
                padding: 20px;
            }

            .duzenle-logo strong {
                font-size: 19px;
            }

            .duzenle-baslik h1 {
                font-size: 25px;
            }
        }
        .ilan-duzenle-sayfasi .duzenle-ust {
    background: #284b32;
    border-bottom-color: #426b46;
}

.ilan-duzenle-sayfasi .duzenle-logo,
.ilan-duzenle-sayfasi .duzenle-logo strong,
.ilan-duzenle-sayfasi .duzenle-menu a {
    color: #fffdf8;
}

.ilan-duzenle-sayfasi .duzenle-logo small {
    color: #e6eddf;
}

.ilan-duzenle-sayfasi .duzenle-menu a:hover,
.ilan-duzenle-sayfasi .duzenle-menu a[aria-current="page"] {
    background: #426b46;
    color: #fffdf8;
}

.ilan-duzenle-sayfasi .duzenle-logo img {
    background: #fffdf8;
    border-radius: 50%;
    padding: 5px;
    box-sizing: border-box;
}
    </style>
<link rel="stylesheet" href="site-ust.css?v=20261102-1">
<link rel="stylesheet" href="site-alt.css?v=20261104-1">
</head>

<body class="ilan-duzenle-sayfasi">

<?php require __DIR__ . "/site-ust.php"; ?>

<main class="duzenle-ana">

    <div class="duzenle-baslik">
        <h1>İlanımı Düzenle</h1>
        <p>Dostumuzun bilgilerini buradan güncelleyebilirsiniz.</p>
    </div>

    <div class="duzenle-kutusu">

        <?php if ($hata !== ""): ?>
            <div class="duzenle-hata" role="alert">
                <?= id_guvenli($hata) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="duzenle-form">

            <input
                type="hidden"
                name="token"
                value="<?= id_guvenli($_SESSION['ilan_duzenle_token']) ?>"
            >

            <div class="duzenle-form-grid">

                <div class="duzenle-alan">
                    <label for="ilan_basligi">İlan Başlığı</label>

                    <input
                        type="text"
                        id="ilan_basligi"
                        name="ilan_basligi"
                        value="<?= id_guvenli($form['ilan_basligi']) ?>"
                        maxlength="150"
                        required
                    >
                </div>

                <div class="duzenle-alan">
                    <label for="hayvan_adi">Hayvan Adı</label>

                    <input
                        type="text"
                        id="hayvan_adi"
                        name="hayvan_adi"
                        value="<?= id_guvenli($form['hayvan_adi']) ?>"
                        required
                    >
                </div>

                <div class="duzenle-alan">
                    <label for="tur">Tür</label>

                    <select id="tur" name="tur" required>
                        <option value="">Tür seçiniz</option>

                        <?php foreach ($turler as $tur): ?>
                            <option
                                value="<?= id_guvenli($tur) ?>"
                                <?= $form["tur"] === $tur ? "selected" : "" ?>
                            >
                                <?= id_guvenli($tur) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="duzenle-alan">
                    <label for="yas">Yaş</label>

                    <input
                        type="number"
                        id="yas"
                        name="yas"
                        min="0"
                        step="1"
                        value="<?= id_guvenli($form['yas']) ?>"
                        required
                    >
                </div>

                <div class="duzenle-alan">
                    <label for="cinsiyet">Cinsiyet</label>

                    <select id="cinsiyet" name="cinsiyet" required>
                        <option value="">Cinsiyet seçiniz</option>

                        <?php foreach (["Dişi", "Erkek"] as $cinsiyet): ?>
                            <option
                                value="<?= id_guvenli($cinsiyet) ?>"
                                <?= $form["cinsiyet"] === $cinsiyet ? "selected" : "" ?>
                            >
                                <?= id_guvenli($cinsiyet) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="duzenle-alan">
                    <label for="sehir">Şehir</label>

                    <select id="sehir" name="sehir" required>
                        <option value="">Şehir seçiniz</option>

                        <?php if (
                            $eski_sehir !== "" &&
                            !in_array($eski_sehir, $iller, true)
                        ): ?>
                            <option
                                value="<?= id_guvenli($eski_sehir) ?>"
                                <?= $form["sehir"] === $eski_sehir ? "selected" : "" ?>
                            >
                                <?= id_guvenli($eski_sehir) ?>
                            </option>
                        <?php endif; ?>

                        <?php foreach ($iller as $il): ?>
                            <option
                                value="<?= id_guvenli($il) ?>"
                                <?= $form["sehir"] === $il ? "selected" : "" ?>
                            >
                                <?= id_guvenli($il) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <fieldset class="duzenle-ozellikler">
                <legend>Hayvanın Özellikleri</legend>
                <p>Hayvanınıza uygun özellikleri işaretleyiniz.</p>

                <?php foreach ($ozellik_secenekleri as $kategori => $secenekler): ?>

                    <h3><?= id_guvenli($kategori) ?></h3>

                    <div class="duzenle-ozellik-grid">

                        <?php foreach ($secenekler as $secenek): ?>
                            <label class="duzenle-ozellik-secimi">
                                <input
                                    type="checkbox"
                                    name="ozellikler[]"
                                    value="<?= id_guvenli($secenek) ?>"
                                    <?= in_array($secenek, $kayitli_ozellikler, true) ? "checked" : "" ?>
                                >
                                <?= id_guvenli($secenek) ?>
                            </label>
                        <?php endforeach; ?>

                    </div>

                <?php endforeach; ?>
            </fieldset>

            <label for="ek_aciklama" class="duzenle-ek-etiket">
                Ek Açıklama (İsteğe Bağlı)
            </label>

            <textarea
                id="ek_aciklama"
                name="ek_aciklama"
                rows="4"
                placeholder="Hayvanınızın alışkanlıkları veya özel ihtiyaçları hakkında bilgi verebilirsiniz."
            ><?= id_guvenli($kayitli_ek_aciklama) ?></textarea>

            <div class="duzenle-islemler">
                <button
                    type="submit"
                    name="guncelle"
                    value="1"
                    class="duzenle-kaydet"
                >
                    Değişiklikleri Kaydet
                </button>

                <a href="profil.php" class="duzenle-iptal">
                    İptal
                </a>
            </div>

        </form>

    </div>

</main>

<?php require __DIR__ . "/site-alt.php"; ?>
</body>
</html>