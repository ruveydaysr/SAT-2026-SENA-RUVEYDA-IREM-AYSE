<?php
session_start();

if (!isset($_SESSION["admin"])) {
    header("Location: admin-giris.php");
    exit();
}

require_once __DIR__ . "/../baglanti.php";

function guvenli($deger)
{
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

function metin($alan)
{
    return is_string($_POST[$alan] ?? null)
        ? trim($_POST[$alan])
        : "";
}

if (empty($_SESSION["anasayfa_token"])) {
    $_SESSION["anasayfa_token"] = bin2hex(random_bytes(32));
}

$sonuc = mysqli_query(
    $baglanti,
    "SELECT baslik, aciklama, buton_yazisi
     FROM anasayfa_icerik WHERE id = 1"
);

$icerik = mysqli_fetch_assoc($sonuc);

$ayar_sonuc = mysqli_query(
    $baglanti,
    "SELECT * FROM anasayfa_ayarlar WHERE id = 1"
);

$ayar = mysqli_fetch_assoc($ayar_sonuc);

if (!$icerik || !$ayar) {
    exit("Ana sayfa kayıtları bulunamadı. Önce verilen SQL kodunu çalıştırınız.");
}

$linkler = [
    "ilanlar.php" => "İlanlar",
    "ilan-ver.php" => "İlan Ver",
    "hakkimizda.php" => "Hakkımızda",
    "iletisim.php" => "İletişim"
];

$bolumler = [
    "tanitim_aktif" => "Karşılama alanı",
    "kategoriler_aktif" => "Hayvan kategorileri",
    "vitrin_aktif" => "Vitrin ilanları",
    "duyuru_aktif" => "Duyuru bandı"
];

$kategoriler = [
    "🐱 Kedi", "🐶 Köpek", "🐦 Kuş", "🐠 Balık",
    "🐰 Tavşan", "🐹 Kemirgen", "🦎 Sürüngen", "🐾 Diğer"
];

$onayli_ilanlar = [];

$ilan_sonuc = mysqli_query(
    $baglanti,
    "SELECT id, hayvan_adi, tur, sehir, fotograf
     FROM ilanlar
     WHERE durum = 'Onaylandı'
     ORDER BY id DESC"
);

while ($satir = mysqli_fetch_assoc($ilan_sonuc)) {
    $onayli_ilanlar[(int)$satir["id"]] = $satir;
}

$secili = json_decode($ayar["vitrin_idler"], true);
$secili = is_array($secili) ? array_map("intval", $secili) : [];

$hata = "";
$basari = $_SESSION["anasayfa_bildirim"] ?? "";
unset($_SESSION["anasayfa_bildirim"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["token"] ?? "";

    $icerik["baslik"] = metin("baslik");
    $icerik["aciklama"] = metin("aciklama");
    $icerik["buton_yazisi"] = metin("buton_yazisi");

    $ayar["buton_linki"] = metin("buton_linki");
    $ayar["duyuru"] = metin("duyuru");

    foreach ($bolumler as $alan => $etiket) {
        $ayar[$alan] = isset($_POST[$alan]) ? 1 : 0;
    }

    $secili = [];
    $gelen_idler = $_POST["vitrin"] ?? [];

    if (is_array($gelen_idler)) {
        foreach ($gelen_idler as $deger) {
            if (!is_string($deger)) {
                continue;
            }

            $id = filter_var($deger, FILTER_VALIDATE_INT);

            if ($id && isset($onayli_ilanlar[$id])) {
                $secili[] = $id;
            }
        }
    }

    $secili = array_values(array_unique($secili));

    if (
        !is_string($token)
        || !hash_equals($_SESSION["anasayfa_token"], $token)
    ) {
        $hata = "Sayfayı yenileyip tekrar deneyiniz.";
    } elseif (
        $icerik["baslik"] === ""
        || $icerik["aciklama"] === ""
        || $icerik["buton_yazisi"] === ""
    ) {
        $hata = "Başlık, açıklama ve buton yazısını doldurunuz.";
    } elseif (
        mb_strlen($icerik["baslik"], "UTF-8") > 150
        || mb_strlen($icerik["aciklama"], "UTF-8") > 3000
        || mb_strlen($icerik["buton_yazisi"], "UTF-8") > 80
        || mb_strlen($ayar["duyuru"], "UTF-8") > 300
    ) {
        $hata = "Alanların karakter sınırlarını aşmayınız.";
    } elseif (!isset($linkler[$ayar["buton_linki"]])) {
        $hata = "Geçerli bir buton bağlantısı seçiniz.";
    } elseif (
        $ayar["duyuru_aktif"]
        && $ayar["duyuru"] === ""
    ) {
        $hata = "Duyuru bandını açmak için duyuru yazınız.";
    } elseif (count($secili) > 12) {
        $hata = "En fazla 12 vitrin ilanı seçebilirsiniz.";
    }

    $yeni_dosya = null;

    if ($hata === "") {
        $yukleme = $_FILES["kapak"] ?? null;

        if ($yukleme) {
            $yukleme_hatasi = $yukleme["error"] ?? null;

            if (!is_int($yukleme_hatasi)) {
                $hata = "Geçersiz görsel yüklemesi.";
            } elseif ($yukleme_hatasi !== UPLOAD_ERR_NO_FILE) {
                if ($yukleme_hatasi !== UPLOAD_ERR_OK) {
                    $hata = "Görsel yüklenemedi. En fazla 5 MB görsel seçiniz.";
                } elseif (
                    $yukleme["size"] > 5 * 1024 * 1024
                    || !is_uploaded_file($yukleme["tmp_name"])
                ) {
                    $hata = "Görsel en fazla 5 MB olabilir.";
                } else {
                    $mime = (new finfo(FILEINFO_MIME_TYPE))
                        ->file($yukleme["tmp_name"]);

                    $uzantilar = [
                        "image/jpeg" => "jpg",
                        "image/png" => "png",
                        "image/webp" => "webp"
                    ];

                    $boyut = @getimagesize($yukleme["tmp_name"]);

                    if (
                        !isset($uzantilar[$mime])
                        || !$boyut
                        || $boyut[0] > 8000
                        || $boyut[1] > 8000
                    ) {
                        $hata = "JPG, PNG veya WEBP seçiniz. Görsel en fazla 8000 piksel olabilir.";
                    } else {
                        $klasor = __DIR__ . "/../uploads";

                        if (
                            !is_dir($klasor)
                            && !mkdir($klasor, 0755, true)
                            && !is_dir($klasor)
                        ) {
                            $hata = "Görsel klasörü oluşturulamadı.";
                        } else {
                            $yeni_dosya = "kapak_"
                                . bin2hex(random_bytes(16))
                                . "." . $uzantilar[$mime];

                            if (!move_uploaded_file(
                                $yukleme["tmp_name"],
                                $klasor . "/" . $yeni_dosya
                            )) {
                                $hata = "Görsel kaydedilemedi.";
                                $yeni_dosya = null;
                            }
                        }
                    }
                }
            }
        }
    }

    if ($hata === "") {
        $kapak = isset($_POST["kapak_kaldir"])
            ? null
            : $ayar["kapak"];

        if ($yeni_dosya !== null) {
            $kapak = $yeni_dosya;
        }

        $vitrin_json = json_encode($secili);

        mysqli_begin_transaction($baglanti);

        try {
            $stmt = mysqli_prepare(
                $baglanti,
                "UPDATE anasayfa_icerik
                 SET baslik = ?, aciklama = ?, buton_yazisi = ?
                 WHERE id = 1"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $icerik["baslik"],
                $icerik["aciklama"],
                $icerik["buton_yazisi"]
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException("Kayıt başarısız.");
            }

            mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare(
                $baglanti,
                "UPDATE anasayfa_ayarlar
                 SET buton_linki = ?, kapak = ?, duyuru = ?,
                     duyuru_aktif = ?, tanitim_aktif = ?,
                     kategoriler_aktif = ?, vitrin_aktif = ?,
                     vitrin_idler = ?
                 WHERE id = 1"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sssiiiis",
                $ayar["buton_linki"],
                $kapak,
                $ayar["duyuru"],
                $ayar["duyuru_aktif"],
                $ayar["tanitim_aktif"],
                $ayar["kategoriler_aktif"],
                $ayar["vitrin_aktif"],
                $vitrin_json
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException("Kayıt başarısız.");
            }

            mysqli_stmt_close($stmt);
            mysqli_commit($baglanti);

            $_SESSION["anasayfa_bildirim"] = "Ana sayfa ayarları kaydedildi.";

            header("Location: admin-anasayfa.php", true, 303);
            exit();
        } catch (Throwable $e) {
            mysqli_rollback($baglanti);

            if ($yeni_dosya !== null) {
                @unlink(__DIR__ . "/../uploads/" . $yeni_dosya);
            }

            $hata = "Değişiklikler kaydedilemedi. Lütfen tekrar deneyiniz.";
        }
    }
}

$kapak_yolu = "";

if (
    !empty($ayar["kapak"])
    && preg_match(
        '/^kapak_[a-f0-9]{32}\.(jpg|png|webp)$/',
        $ayar["kapak"]
    )
    && is_file(__DIR__ . "/../uploads/" . $ayar["kapak"])
) {
    $kapak_yolu = "../uploads/" . $ayar["kapak"];
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ana Sayfa Yönetimi | Aris Pati Yuvası</title>

    <link rel="stylesheet" href="admin-style.css?v=14">
    <link rel="stylesheet" href="../css/anasayfa.css?v=2">

    <style>
        /* Admin ekranındaki kapak önizlemesi */

        .ay-onizleme .ay-hero {
            position: relative;
            isolation: isolate;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 380px;
            padding: 45px 24px;
            overflow: hidden;
            border: 0;
            border-radius: 0;
            background: #426b46;
            text-align: center;
        }

        .ay-onizleme .ay-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -1;
            background: rgba(22, 49, 30, 0.55);
        }

        .ay-onizleme .ay-hero.ay-gorselsiz::before {
            background: transparent;
        }

        .ay-onizleme .ay-hero > div {
            width: min(680px, 100%);
        }

        .ay-onizleme .ay-hero h2 {
            margin: 0 0 18px;
            color: #fffdf8;
            font-size: 34px;
            line-height: 1.25;
        }

        .ay-onizleme .ay-hero p {
            margin: 0 0 24px;
            color: #fffdf8;
            font-size: 15px;
            line-height: 1.8;
        }

        .ay-onizleme .ay-hero .ay-kapak {
            position: absolute;
            inset: 0;
            z-index: -2;
            width: 100%;
            height: 100%;
            border-radius: 0;
            object-fit: cover;
        }

        .ay-onizleme .ay-hero .ay-buton {
            display: inline-block;
            min-width: 220px;
            max-width: 100%;
            padding: 14px 24px;
            border-radius: 8px;
            background: #eee5d3;
            color: #284b32;
            font-weight: bold;
        }

        .ay-onizleme #on-link {
            margin: 12px 0 0;
            font-size: 12px;
        }

        @media (max-width: 600px) {
            .ay-onizleme .ay-hero {
                min-height: 330px;
                padding: 30px 18px;
            }

            .ay-onizleme .ay-hero h2 {
                font-size: 27px;
            }

            .ay-onizleme .ay-hero .ay-buton {
                min-width: 0;
                width: 100%;
            }
        }
    </style>
</head>

<body>

<?php require __DIR__ . "/admin-menu.php"; ?>

<main class="icerik">

    <h2>Ana Sayfa Yönetimi</h2>

    <?php if ($basari !== ""): ?>
        <div class="kayit-bildirimi" role="status">
            <?php echo guvenli($basari); ?>
        </div>
    <?php endif; ?>

    <?php if ($hata !== ""): ?>
        <div class="kayit-bildirimi hata" role="alert">
            <?php echo guvenli($hata); ?>
        </div>
    <?php endif; ?>

    <form
        id="ayar-form"
        action="admin-anasayfa.php"
        method="POST"
        enctype="multipart/form-data"
        class="ay-yonetim-form"
    >
        <input
            type="hidden"
            name="token"
            value="<?php echo guvenli($_SESSION["anasayfa_token"]); ?>"
        >

        <section class="kart">
            <h3>Karşılama Alanı</h3>

            <div class="ay-alanlar">

                <div class="ay-alan ay-tam">
                    <label for="baslik">Başlık</label>
                    <input
                        type="text"
                        id="baslik"
                        name="baslik"
                        maxlength="150"
                        value="<?php echo guvenli($icerik["baslik"]); ?>"
                        required
                    >
                </div>

                <div class="ay-alan ay-tam">
                    <label for="aciklama">Açıklama</label>
                    <textarea
                        id="aciklama"
                        name="aciklama"
                        maxlength="3000"
                        required
                    ><?php echo guvenli($icerik["aciklama"]); ?></textarea>
                </div>

                <div class="ay-alan">
                    <label for="buton_yazisi">Buton Yazısı</label>
                    <input
                        type="text"
                        id="buton_yazisi"
                        name="buton_yazisi"
                        maxlength="80"
                        value="<?php echo guvenli($icerik["buton_yazisi"]); ?>"
                        required
                    >
                </div>

                <div class="ay-alan">
                    <label for="buton_linki">Butonun Açacağı Sayfa</label>

                    <select id="buton_linki" name="buton_linki">
                        <?php foreach ($linkler as $adres => $etiket): ?>
                            <option
                                value="<?php echo guvenli($adres); ?>"
                                <?php echo $ayar["buton_linki"] === $adres ? "selected" : ""; ?>
                            >
                                <?php echo guvenli($etiket); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>
        </section>

        <section class="kart">
            <h3>Kapak Görseli</h3>

            <label for="kapak">Görsel Seç</label>

            <input
                type="file"
                id="kapak"
                name="kapak"
                accept="image/jpeg,image/png,image/webp"
            >

            <p class="ay-not">
                Yatay bir görsel seçiniz. JPG, PNG veya WEBP;
                en fazla 5 MB. Yazılar görselin ortasında gösterilir.
            </p>

            <label class="ay-secim">
                <input
                    type="checkbox"
                    id="kapak_kaldir"
                    name="kapak_kaldir"
                >
                Mevcut kapak görselini kaldır
            </label>
        </section>

        <section class="kart">
            <h3>Duyuru Bandı</h3>

            <label for="duyuru">Duyuru Metni</label>

            <textarea
                id="duyuru"
                name="duyuru"
                maxlength="300"
                placeholder="Örneğin: Satın alma, sahiplen!"
            ><?php echo guvenli($ayar["duyuru"]); ?></textarea>
        </section>

        <section class="kart">
            <h3>Vitrin İlanları</h3>

            <p class="ay-not">
                En fazla 12 onaylı ilan seçiniz.
                İlanlar en yeniden eskiye sıralanır.
            </p>

            <?php if (!$onayli_ilanlar): ?>
                <p>Henüz onaylanmış ilan bulunmuyor.</p>
            <?php else: ?>

                <div class="ay-vitrin-secimleri">
                    <?php foreach ($onayli_ilanlar as $id => $ilan): ?>
                        <label class="ay-secim">
                            <input
                                type="checkbox"
                                name="vitrin[]"
                                value="<?php echo $id; ?>"
                                <?php echo in_array($id, $secili, true) ? "checked" : ""; ?>
                            >

                            <span>
                                #<?php echo $id; ?>
                                — <?php echo guvenli($ilan["hayvan_adi"]); ?>
                                <br>
                                <?php echo guvenli($ilan["tur"]); ?>
                                · <?php echo guvenli($ilan["sehir"]); ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </section>

        <section class="kart">
            <h3>Bölümleri Göster / Gizle</h3>

            <div class="ay-secimler">
                <?php foreach ($bolumler as $alan => $etiket): ?>
                    <label class="ay-secim">
                        <input
                            type="checkbox"
                            id="<?php echo $alan; ?>"
                            name="<?php echo $alan; ?>"
                            <?php echo $ayar[$alan] ? "checked" : ""; ?>
                        >
                        <?php echo guvenli($etiket); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="kart">
            <h3>Canlı Önizleme</h3>

            <p class="ay-not">
                Değişiklikleri burada görebilirsiniz.
                Ziyaretçiler kaydettiğiniz hâlini görür.
            </p>

            <div class="ay-onizleme ay-kapsam">

                <div id="on-duyuru" class="ay-duyuru"></div>

                <section id="on-tanitim" class="ay-bolum">
                    <div id="on-hero" class="ay-hero">

                        <div>
                            <h2 id="on-baslik"></h2>
                            <p id="on-aciklama"></p>

                            <span id="on-buton" class="ay-buton"></span>

                            <p id="on-link"></p>
                        </div>

                        <img
                            id="on-kapak"
                            class="ay-kapak"
                            alt="Kapak önizlemesi"
                            <?php if ($kapak_yolu !== ""): ?>
                                src="<?php echo guvenli($kapak_yolu); ?>"
                            <?php else: ?>
                                hidden
                            <?php endif; ?>
                        >

                    </div>
                </section>

                <section id="on-kategoriler" class="ay-bolum">
                    <h2>Hayvan Kategorileri</h2>

                    <div class="ay-kategoriler">
                        <?php foreach ($kategoriler as $kategori): ?>
                            <div class="ay-kategori">
                                <?php echo guvenli($kategori); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section id="on-vitrin" class="ay-bolum">
                    <h2>Yuva Arayan Dostlarımız</h2>

                    <div class="ay-vitrin">
                        <?php foreach ($onayli_ilanlar as $id => $ilan): ?>

                            <?php
                            $foto = basename((string)($ilan["fotograf"] ?? ""));

                            $foto_var = preg_match(
                                '/^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$/i',
                                $foto
                            ) === 1
                                && is_file(__DIR__ . "/../uploads/" . $foto);
                            ?>

                            <article
                                class="ay-ilan"
                                data-on-ilan="<?php echo $id; ?>"
                                hidden
                            >
                                <?php if ($foto_var): ?>
                                    <img
                                        src="../uploads/<?php echo guvenli($foto); ?>"
                                        alt="<?php echo guvenli($ilan["hayvan_adi"]); ?>"
                                        loading="lazy"
                                    >
                                <?php else: ?>
                                    <div class="ay-foto-yok">🐾</div>
                                <?php endif; ?>

                                <div class="ay-ilan-bilgi">
                                    <h3>
                                        <?php echo guvenli($ilan["hayvan_adi"]); ?>
                                    </h3>

                                    <p>
                                        <?php echo guvenli($ilan["tur"]); ?>
                                        · <?php echo guvenli($ilan["sehir"]); ?>
                                    </p>
                                </div>
                            </article>

                        <?php endforeach; ?>
                    </div>

                    <p id="on-vitrin-bos" class="ay-not">
                        Vitrin ilanı seçilmedi.
                    </p>
                </section>

            </div>
        </section>

        <div class="ay-kaydet">
            <button type="submit">Tüm Değişiklikleri Kaydet</button>

            <a
                href="../index.php"
                class="filtre-temizle"
                target="_blank"
                rel="noopener noreferrer"
            >
                Kaydedilmiş Ana Sayfayı Aç
            </a>
        </div>

    </form>
</main>

<script>
const form = document.getElementById("ayar-form");
const kapakInput = document.getElementById("kapak");
const kapakResim = document.getElementById("on-kapak");
const eskiKapak = kapakResim.getAttribute("src") || "";

let geciciKapak = "";

function onizle() {
    document.getElementById("on-baslik").textContent =
        document.getElementById("baslik").value;

    document.getElementById("on-aciklama").textContent =
        document.getElementById("aciklama").value;

    document.getElementById("on-buton").textContent =
        document.getElementById("buton_yazisi").value;

    const link = document.getElementById("buton_linki");

    document.getElementById("on-link").textContent =
        "Butonun açacağı sayfa: "
        + link.options[link.selectedIndex].text;

    const duyuru = document.getElementById("duyuru").value;

    document.getElementById("on-duyuru").textContent = duyuru;

    document.getElementById("on-duyuru").hidden =
        !document.getElementById("duyuru_aktif").checked
        || !duyuru.trim();

    document.getElementById("on-tanitim").hidden =
        !document.getElementById("tanitim_aktif").checked;

    document.getElementById("on-kategoriler").hidden =
        !document.getElementById("kategoriler_aktif").checked;

    document.getElementById("on-vitrin").hidden =
        !document.getElementById("vitrin_aktif").checked;

    const secilenler = new Set(
        Array.from(
            form.querySelectorAll('input[name="vitrin[]"]:checked')
        ).map(input => input.value)
    );

    document.querySelectorAll("[data-on-ilan]").forEach(kart => {
        kart.hidden = !secilenler.has(kart.dataset.onIlan);
    });

    document.getElementById("on-vitrin-bos").hidden =
        secilenler.size > 0;

    const kaldir = document.getElementById("kapak_kaldir").checked;
    const kaynak = geciciKapak || (kaldir ? "" : eskiKapak);

    kapakResim.hidden = !kaynak;

    if (kaynak) {
        kapakResim.src = kaynak;
    } else {
        kapakResim.removeAttribute("src");
    }

    document.getElementById("on-hero")
        .classList.toggle("ay-gorselsiz", !kaynak);
}

kapakInput.addEventListener("change", () => {
    if (geciciKapak) {
        URL.revokeObjectURL(geciciKapak);
        geciciKapak = "";
    }

    kapakInput.setCustomValidity("");

    const dosya = kapakInput.files[0];

    if (dosya) {
        const turler = ["image/jpeg", "image/png", "image/webp"];

        if (
            !turler.includes(dosya.type)
            || dosya.size > 5 * 1024 * 1024
        ) {
            kapakInput.setCustomValidity(
                "En fazla 5 MB JPG, PNG veya WEBP seçiniz."
            );

            kapakInput.reportValidity();
        } else {
            geciciKapak = URL.createObjectURL(dosya);
        }
    }

    onizle();
});

form.addEventListener("input", onizle);
form.addEventListener("change", onizle);

form.addEventListener("submit", event => {
    const sayi = form.querySelectorAll(
        'input[name="vitrin[]"]:checked'
    ).length;

    if (sayi > 12) {
        event.preventDefault();
        alert("En fazla 12 vitrin ilanı seçebilirsiniz.");
        return;
    }

    if (!confirm(
        "Ana sayfa değişikliklerini kaydetmek istediğinize emin misiniz?"
    )) {
        event.preventDefault();
    }
});

onizle();
</script>

</body>
</html>