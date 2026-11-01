<?php
session_start();

if (!isset($_SESSION["admin"])) {
    header("Location: admin-giris.php");
    exit();
}

require_once __DIR__ . "/../baglanti.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function aa_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function aa_links()
{
    return [
        "ilanlar.php" => "İlanlar",
        "ilan-ver.php" => "İlan Ver",
        "hakkimizda.php" => "Hakkımızda",
        "iletisim.php" => "İletişim"
    ];
}

function aa_categories()
{
    return [
        "Kedi" => "kedi.png",
        "Köpek" => "kopek.png",
        "Kuş" => "kus.png",
        "Balık" => "balik.png",
        "Tavşan" => "tavsan.png",
        "Kemirgen" => "kemirgen.png",
        "Sürüngen" => "surungen.png",
        "Diğer" => "diger.png"
    ];
}

function aa_defaults()
{
    $cats = [];

    foreach (aa_categories() as $name => $file) {
        $cats[$name] = ["gorsel" => "images/" . $file];
    }

    return [
        "degerler" => [
            "aktif" => 1,
            "ogeler" => [
                [
                    "baslik" => "Bir dostluk başlasın",
                    "aciklama" => "Hayatına sevgi dolu bir arkadaş kat."
                ],
                [
                    "baslik" => "Bir yuva umut olsun",
                    "aciklama" => "Yuva arayan bir dostun hikâyesini değiştir."
                ],
                [
                    "baslik" => "Sorumlulukla sahiplen",
                    "aciklama" => "Ömür boyu sürecek bir bağ için adım at."
                ]
            ]
        ],

        "kategoriler" => [
            "etiket" => "Yeni arkadaşın seni bekliyor",
            "baslik" => "Hangi dostumuzla tanışmak istersin?",
            "ogeler" => $cats
        ],

        "vitrin" => [
            "etiket" => "Belki de aradığın dost burada",
            "baslik" => "Yuva arayan dostlarımız",
            "aciklama" => "Her birinin farklı bir hikâyesi, aynı güzel hayali var: sevgi dolu bir yuva."
        ],

        "surec" => [
            "aktif" => 1,
            "etiket" => "Dostluğa giden yol",
            "baslik" => "Yeni bir başlangıç için üç adım",
            "aciklama" => "Sana ve yaşamına uygun dostunu bul, onu tanı ve sahiplenme başvurunu oluştur.",
            "ogeler" => [
                [
                    "baslik" => "Dostunu keşfet",
                    "aciklama" => "İlanları incele. Tür ve şehir seçeneklerini kullanarak sana uygun dostlarımızı keşfet."
                ],
                [
                    "baslik" => "Onu daha yakından tanı",
                    "aciklama" => "İlan detaylarını oku. İlan sahibiyle iletişime geçerek dostumuzun ihtiyaçları hakkında bilgi al."
                ],
                [
                    "baslik" => "Başvurunu oluştur",
                    "aciklama" => "Hesabına giriş yapıp sahiplenme başvurunu gönder. Başvurunun durumunu profilinden takip et."
                ]
            ]
        ],

        "sehir" => [
            "aktif" => 1,
            "etiket" => "Güzel bir karşılaşma çok yakında",
            "baslik" => "Yakınındaki dostunu bul.",
            "aciklama" => "Şehrindeki sahiplendirme ilanlarını incele. Belki de yeni arkadaşın düşündüğünden daha yakındadır.",
            "liste" => "İstanbul\nAnkara\nİzmir\nBursa\nAntalya\nEskişehir"
        ],

        "sss" => [
            "aktif" => 1,
            "etiket" => "Aklındaki sorular",
            "baslik" => "Sahiplenmeden önce merak ettiklerin.",
            "aciklama" => "Yeni bir dost edinmek güzel bir başlangıçtır. Bu kararı onun ihtiyaçlarını ve kendi yaşam koşullarını düşünerek vermelisin.",
            "ogeler" => [
                [
                    "baslik" => "Sahiplenmek için üye olmam gerekiyor mu?",
                    "aciklama" => "İlanları üye olmadan inceleyebilirsin. Sahiplenme başvurusu göndermek için hesabına giriş yapmalısın."
                ],
                [
                    "baslik" => "İlan sahibiyle nasıl iletişim kurabilirim?",
                    "aciklama" => "İlgilendiğin ilanın detay sayfasındaki mesajlaşma alanını kullanabilirsin. Mesaj göndermek için hesabına giriş yapmalısın."
                ],
                [
                    "baslik" => "Başvurumu nereden takip edebilirim?",
                    "aciklama" => "Profilim sayfasındaki Sahiplenme Başvurularım bölümünden başvurunun durumunu görebilirsin."
                ],
                [
                    "baslik" => "Sahiplenmeden önce neleri düşünmeliyim?",
                    "aciklama" => "Dostumuzun bakımına ayırabileceğin zamanı, yaşam alanını, veteriner ve beslenme ihtiyaçlarını değerlendir. Birlikte yaşayacağın kişilerle de bu sorumluluğu konuş."
                ]
            ]
        ],

        "davet" => [
            "aktif" => 1,
            "baslik" => "Senin için bir dost,\nonun için bütün bir dünya.",
            "aciklama" => "Yuva arayan bir dostumuz için ilan oluştur, onun yeni ailesiyle tanışmasına yardımcı ol.",
            "buton_yazisi" => "Sahiplendirme İlanı Ver",
            "buton_linki" => "ilan-ver.php"
        ],

        "footer" => [
            "aciklama" => "Yuva arayan hayvanlarla onlara sevgiyle bakmak isteyen insanları bir araya getiriyoruz. Her can dostuna bir yuva."
        ]
    ];
}

function aa_merge($defaults, $data)
{
    foreach ($defaults as $key => $value) {
        if (is_array($value)) {
            $defaults[$key] = aa_merge(
                $value,
                is_array($data[$key] ?? null) ? $data[$key] : []
            );
        } elseif (is_int($value)) {
            $defaults[$key] = !empty($data[$key] ?? $value) ? 1 : 0;
        } elseif (is_string($data[$key] ?? null)) {
            $defaults[$key] = $data[$key];
        }
    }

    return $defaults;
}

function aa_read($db)
{
    $defaults = aa_defaults();

    $result = mysqli_query(
        $db,
        "SELECT icerik_json FROM anasayfa_bolumler WHERE id = 1"
    );

    $row = mysqli_fetch_assoc($result);

    $data = json_decode((string) ($row["icerik_json"] ?? ""), true);

    return aa_merge($defaults, is_array($data) ? $data : []);
}

function aa_image($path)
{
    if (!is_string($path)) {
        return "";
    }

    $pattern = '~^(images/[a-zA-Z0-9_-]+\.(?:jpg|jpeg|png|webp)|uploads/(?:kapak|kategori)_[a-f0-9]{32}\.(?:jpg|png|webp))$~i';

    if (!preg_match($pattern, $path)) {
        return "";
    }

    return is_file(dirname(__DIR__) . "/" . $path) ? $path : "";
}

function aa_cover($ayar)
{
    $default = aa_image("images/anasayfa.png")
        ?: aa_image("images/anasayfa-kapak.png");

    if (is_string($ayar["kapak"] ?? null)) {
        $upload = aa_image("uploads/" . $ayar["kapak"]);

        if ($upload !== "") {
            return $upload;
        }
    }

    return $default;
}

function aa_cities($text)
{
    $lines = preg_split('/\R/u', (string) $text);

    return array_values(
        array_unique(
            array_filter(
                array_map("trim", $lines ?: []),
                function ($value) {
                    return $value !== "";
                }
            )
        )
    );
}

function ay_post($key)
{
    return is_string($_POST[$key] ?? null)
        ? trim($_POST[$key])
        : "";
}

function ay_len($text)
{
    return function_exists("mb_strlen")
        ? mb_strlen($text, "UTF-8")
        : strlen($text);
}

function ay_field($name, $label, $value, $multi = false, $max = 3000)
{
    $id = "alan-" . substr(hash("sha256", $name), 0, 12);

    echo '<div class="ay-alan">';
    echo '<label for="' . aa_h($id) . '">' . aa_h($label) . '</label>';

    if ($multi) {
        echo '<textarea id="' . $id . '" name="' . aa_h($name)
            . '" maxlength="' . $max . '">'
            . aa_h($value) . '</textarea>';
    } else {
        echo '<input type="text" id="' . $id
            . '" name="' . aa_h($name)
            . '" value="' . aa_h($value)
            . '" maxlength="' . $max . '">';
    }

    echo '</div>';
}

function ay_clean($defaults, $posted, $current)
{
    $output = [];

    foreach ($defaults as $key => $value) {
        if ($key === "gorsel") {
            $output[$key] = $current[$key] ?? $value;
            continue;
        }

        if (is_array($value)) {
            $output[$key] = ay_clean(
                $value,
                is_array($posted[$key] ?? null) ? $posted[$key] : [],
                is_array($current[$key] ?? null) ? $current[$key] : []
            );
        } elseif (is_int($value)) {
            $output[$key] = isset($posted[$key]) ? 1 : 0;
        } else {
            if (
                isset($posted[$key])
                && !is_string($posted[$key])
            ) {
                throw new RuntimeException("Geçersiz form alanı.");
            }

            $text = trim($posted[$key] ?? "");

            $max = in_array($key, ["aciklama", "liste"], true)
                ? 3000
                : 150;

            if (ay_len($text) > $max) {
                throw new RuntimeException(
                    "Metin alanlarının karakter sınırlarını aşmayınız."
                );
            }

            $output[$key] = $text;
        }
    }

    return $output;
}

function ay_upload($field, $prefix, &$created)
{
    $file = $_FILES[$field] ?? null;

    if ($file === null) {
        return null;
    }

    if (
        !is_array($file)
        || !is_int($file["error"] ?? null)
    ) {
        throw new RuntimeException("Geçersiz görsel yüklemesi.");
    }

    if ($file["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(
            "Görsel yüklenemedi. Dosya boyutu veya sunucu yükleme sınırını kontrol ediniz."
        );
    }

    if (
        !is_string($file["tmp_name"] ?? null)
        || !is_uploaded_file($file["tmp_name"])
        || ($file["size"] ?? 0) > 5 * 1024 * 1024
    ) {
        throw new RuntimeException(
            "Her görsel en fazla 5 MB olabilir."
        );
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file(
        $file["tmp_name"]
    );

    $extensions = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    $dimensions = @getimagesize($file["tmp_name"]);

    if (
        !isset($extensions[$mime])
        || !$dimensions
        || $dimensions[0] > 8000
        || $dimensions[1] > 8000
    ) {
        throw new RuntimeException(
            "JPG, PNG veya WEBP seçiniz. Görsel en fazla 8000 × 8000 piksel olabilir."
        );
    }

    $directory = __DIR__ . "/../uploads";

    if (
        !is_dir($directory)
        && !mkdir($directory, 0755, true)
        && !is_dir($directory)
    ) {
        throw new RuntimeException(
            "Görsel klasörü oluşturulamadı."
        );
    }

    $name = $prefix . "_"
        . bin2hex(random_bytes(16))
        . "." . $extensions[$mime];

    if (
        !move_uploaded_file(
            $file["tmp_name"],
            $directory . "/" . $name
        )
    ) {
        throw new RuntimeException("Görsel kaydedilemedi.");
    }

    $created[] = $directory . "/" . $name;

    return $name;
}

/* Mevcut kayıtları getir. */

$result = mysqli_query(
    $baglanti,
    "SHOW TABLES LIKE 'anasayfa_bolumler'"
);

if (!mysqli_num_rows($result)) {
    exit("Önce verilen SQL sorgusunu phpMyAdmin üzerinden çalıştırınız.");
}

$result = mysqli_query(
    $baglanti,
    "SELECT baslik, aciklama, buton_yazisi
     FROM anasayfa_icerik
     WHERE id = 1"
);

$icerik = mysqli_fetch_assoc($result);

$result = mysqli_query(
    $baglanti,
    "SELECT * FROM anasayfa_ayarlar WHERE id = 1"
);

$ayar = mysqli_fetch_assoc($result);

if (!$icerik || !$ayar) {
    exit(
        "Ana sayfa kayıtları bulunamadı. anasayfa_icerik ve "
        . "anasayfa_ayarlar tablolarında id=1 kaydını kontrol ediniz."
    );
}

$b = aa_read($baglanti);

if (!is_string($_SESSION["anasayfa_token"] ?? null)) {
    $_SESSION["anasayfa_token"] = bin2hex(random_bytes(32));
}

$onayli = [];

$result = mysqli_query(
    $baglanti,
    "SELECT id, hayvan_adi, tur, sehir, fotograf
     FROM ilanlar
     WHERE durum = 'Onaylandı'
     ORDER BY id DESC"
);

while ($row = mysqli_fetch_assoc($result)) {
    $onayli[(int) $row["id"]] = $row;
}

$ids = json_decode((string) $ayar["vitrin_idler"], true);

$secili = [];

foreach (is_array($ids) ? $ids : [] as $id) {
    if (
        (is_string($id) || is_int($id))
        && isset($onayli[(int) $id])
    ) {
        $secili[] = (int) $id;
    }
}

$legacy = [
    "tanitim_aktif" => "Karşılama alanını göster",
    "kategoriler_aktif" => "Kategorileri göster",
    "vitrin_aktif" => "Vitrin ilanlarını göster",
    "duyuru_aktif" => "Duyuru bandını göster"
];

$titles = [
    "degerler" => "Kısa Tanıtım",
    "kategoriler" => "Hayvan Kategorileri",
    "vitrin" => "Vitrin Başlıkları",
    "surec" => "Sahiplenme Adımları",
    "sehir" => "Şehirlere Göre İlanlar",
    "sss" => "Sık Sorulan Sorular",
    "davet" => "Alt Davet Alanı",
    "footer" => "Alt Bilgi Metni"
];

$labels = [
    "etiket" => "Küçük üst yazı",
    "baslik" => "Başlık",
    "aciklama" => "Açıklama",
    "liste" => "Şehirler (her satıra bir şehir)",
    "buton_yazisi" => "Buton yazısı"
];

$hata = "";

$basari = is_string($_SESSION["anasayfa_bildirim"] ?? null)
    ? $_SESSION["anasayfa_bildirim"]
    : "";

unset($_SESSION["anasayfa_bildirim"]);

/* Kaydetme işlemi. */

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $created = [];
    $transaction = false;

    try {
        if (
            !hash_equals(
                $_SESSION["anasayfa_token"],
                ay_post("token")
            )
        ) {
            throw new RuntimeException(
                "İşlem doğrulanamadı. Sayfayı yenileyiniz. "
                . "Büyük dosya yüklediyseniz sunucu yükleme sınırını kontrol ediniz."
            );
        }

        foreach (["baslik", "aciklama", "buton_yazisi"] as $key) {
            $icerik[$key] = ay_post($key);
        }

        $ayar["buton_linki"] = ay_post("buton_linki");
        $ayar["duyuru"] = ay_post("duyuru");

        foreach ($legacy as $key => $label) {
            $ayar[$key] = isset($_POST[$key]) ? 1 : 0;
        }

        if (
            $icerik["baslik"] === ""
            || $icerik["aciklama"] === ""
            || $icerik["buton_yazisi"] === ""
        ) {
            throw new RuntimeException(
                "Karşılama başlığını, açıklamasını ve buton yazısını doldurunuz."
            );
        }

        if (
            ay_len($icerik["baslik"]) > 150
            || ay_len($icerik["aciklama"]) > 3000
            || ay_len($icerik["buton_yazisi"]) > 80
            || ay_len($ayar["duyuru"]) > 300
        ) {
            throw new RuntimeException("Karakter sınırlarını aşmayınız.");
        }

        if (!isset(aa_links()[$ayar["buton_linki"]])) {
            throw new RuntimeException(
                "Geçerli bir buton bağlantısı seçiniz."
            );
        }

        if ($ayar["duyuru_aktif"] && $ayar["duyuru"] === "") {
            throw new RuntimeException(
                "Duyuru bandı açıkken duyuru metnini doldurunuz."
            );
        }

        $secili = [];
        $posted = $_POST["vitrin"] ?? [];

        if (!is_array($posted)) {
            throw new RuntimeException("Geçersiz vitrin seçimi.");
        }

        foreach ($posted as $value) {
            if (!is_string($value)) {
                continue;
            }

            $id = filter_var($value, FILTER_VALIDATE_INT);

            if ($id && isset($onayli[$id])) {
                $secili[] = $id;
            }
        }

        $secili = array_values(array_unique($secili));

        if (count($secili) > 12) {
            throw new RuntimeException(
                "En fazla 12 vitrin ilanı seçebilirsiniz."
            );
        }

        $b = ay_clean(
            aa_defaults(),
            is_array($_POST["bolum"] ?? null) ? $_POST["bolum"] : [],
            $b
        );

        if (!isset(aa_links()[$b["davet"]["buton_linki"]])) {
            throw new RuntimeException(
                "Davet butonu için geçerli bir bağlantı seçiniz."
            );
        }

        foreach (["degerler", "surec"] as $key) {
            if (!$b[$key]["aktif"]) {
                continue;
            }

            if ($key === "surec" && $b[$key]["baslik"] === "") {
                throw new RuntimeException(
                    "Sahiplenme adımları başlığını doldurunuz."
                );
            }

            foreach ($b[$key]["ogeler"] as $row) {
                if ($row["baslik"] === "" || $row["aciklama"] === "") {
                    throw new RuntimeException(
                        $titles[$key]
                        . " içindeki başlık ve açıklamaları doldurunuz."
                    );
                }
            }
        }

        foreach (["sehir", "sss", "davet"] as $key) {
            if ($b[$key]["aktif"] && $b[$key]["baslik"] === "") {
                throw new RuntimeException(
                    $titles[$key] . " başlığını doldurunuz."
                );
            }
        }

        foreach ($b["sss"]["ogeler"] as $row) {
            if (
                ($row["baslik"] === "")
                !== ($row["aciklama"] === "")
            ) {
                throw new RuntimeException(
                    "SSS satırlarında soru ve cevabı birlikte "
                    . "doldurunuz veya ikisini de boş bırakınız."
                );
            }
        }

        $dolu_sorular = array_filter(
            $b["sss"]["ogeler"],
            function ($row) {
                return $row["baslik"] !== "";
            }
        );

        if ($b["sss"]["aktif"] && !$dolu_sorular) {
            throw new RuntimeException(
                "SSS bölümü için en az bir soru ve cevap giriniz."
            );
        }

        if (
            $ayar["kategoriler_aktif"]
            && $b["kategoriler"]["baslik"] === ""
        ) {
            throw new RuntimeException("Kategori başlığını doldurunuz.");
        }

        if ($ayar["vitrin_aktif"] && $b["vitrin"]["baslik"] === "") {
            throw new RuntimeException("Vitrin başlığını doldurunuz.");
        }

        $cities = aa_cities($b["sehir"]["liste"]);

        if (count($cities) > 24) {
            throw new RuntimeException(
                "Ana sayfada en fazla 24 şehir gösterilebilir."
            );
        }

        foreach ($cities as $city) {
            if (ay_len($city) > 100) {
                throw new RuntimeException(
                    "Şehir adları en fazla 100 karakter olabilir."
                );
            }
        }

        if ($b["sehir"]["aktif"] && !$cities) {
            throw new RuntimeException(
                "Şehir bölümü için en az bir şehir giriniz."
            );
        }

        if (
            $b["davet"]["aktif"]
            && $b["davet"]["buton_yazisi"] === ""
        ) {
            throw new RuntimeException(
                "Davet butonunun yazısını doldurunuz."
            );
        }

        $kapak = isset($_POST["kapak_kaldir"])
            ? null
            : $ayar["kapak"];

        $upload = ay_upload("kapak", "kapak", $created);

        if ($upload !== null) {
            $kapak = $upload;
        }

        $i = 0;

        foreach (aa_categories() as $name => $file) {
            if (isset($_POST["kat_sifirla_" . $i])) {
                $b["kategoriler"]["ogeler"][$name]["gorsel"]
                    = "images/" . $file;
            }

            $upload = ay_upload("kat_" . $i, "kategori", $created);

            if ($upload !== null) {
                $b["kategoriler"]["ogeler"][$name]["gorsel"]
                    = "uploads/" . $upload;
            }

            $i++;
        }

        $json = json_encode(
            $b,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        $vitrin_json = json_encode(
            $secili,
            JSON_THROW_ON_ERROR
        );

        mysqli_begin_transaction($baglanti);
        $transaction = true;

        $query = mysqli_prepare(
            $baglanti,
            "UPDATE anasayfa_icerik
             SET baslik = ?, aciklama = ?, buton_yazisi = ?
             WHERE id = 1"
        );

        mysqli_stmt_bind_param(
            $query,
            "sss",
            $icerik["baslik"],
            $icerik["aciklama"],
            $icerik["buton_yazisi"]
        );

        mysqli_stmt_execute($query);
        mysqli_stmt_close($query);

        $query = mysqli_prepare(
            $baglanti,
            "UPDATE anasayfa_ayarlar
             SET buton_linki = ?,
                 kapak = ?,
                 duyuru = ?,
                 duyuru_aktif = ?,
                 tanitim_aktif = ?,
                 kategoriler_aktif = ?,
                 vitrin_aktif = ?,
                 vitrin_idler = ?
             WHERE id = 1"
        );

        mysqli_stmt_bind_param(
            $query,
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

        mysqli_stmt_execute($query);
        mysqli_stmt_close($query);

        $query = mysqli_prepare(
            $baglanti,
            "INSERT INTO anasayfa_bolumler (id, icerik_json)
             VALUES (1, ?)
             ON DUPLICATE KEY UPDATE
             icerik_json = VALUES(icerik_json)"
        );

        mysqli_stmt_bind_param($query, "s", $json);
        mysqli_stmt_execute($query);
        mysqli_stmt_close($query);

        mysqli_commit($baglanti);
        $transaction = false;

        $_SESSION["anasayfa_bildirim"]
            = "Ana sayfa ayarları başarıyla güncellendi.";

        header("Location: admin-anasayfa.php", true, 303);
        exit();

    } catch (Throwable $exception) {
        if ($transaction) {
            mysqli_rollback($baglanti);
        }

        foreach ($created as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        $hata = $exception instanceof mysqli_sql_exception
            ? "Değişiklikler kaydedilemedi. Veritabanı bağlantısını kontrol ediniz."
            : $exception->getMessage();
    }
}

$cover = aa_cover($ayar);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ana Sayfa Yönetimi | Aris Pati Yuvası</title>

    <link rel="stylesheet" href="admin-style.css?v=15">

    <style>
        .ay-panel {
            min-width: 0;
            color: #284b32;
        }

        .ay-panel * {
            box-sizing: border-box;
        }

        .ay-ust {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 18px;
            margin-bottom: 22px;
        }

        .ay-ust h2 {
            margin: 0 0 8px;
        }

        .ay-ust p,
        .ay-not {
            color: #687361;
            font-size: 13px;
            line-height: 1.7;
        }

        .ay-panel .ay-form {
            display: block;
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 0;
            border: 0;
            background: transparent;
            box-shadow: none;
        }

        .ay-kart {
            margin-bottom: 20px;
            padding: 22px;
            border: 1px solid #ded6c6;
            border-radius: 13px;
            background: #fffdf8;
        }

        .ay-kart h3 {
            margin: 0 0 16px;
            font-size: 19px;
        }

        .ay-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .ay-alan {
            min-width: 0;
        }

        .ay-alan label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: bold;
        }

        .ay-panel .ay-alan input,
        .ay-panel .ay-alan textarea,
        .ay-panel .ay-alan select {
            display: block;
            width: 100%;
            margin: 0;
            padding: 11px;
            border: 1px solid #ded6c6;
            border-radius: 8px;
            background: white;
            color: #284b32;
            font: normal 14px Arial, sans-serif;
        }

        .ay-alan textarea {
            min-height: 95px;
            resize: vertical;
        }

        .ay-secim {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 13px;
            line-height: 1.6;
            cursor: pointer;
        }

        .ay-panel .ay-secim input {
            width: 16px;
            height: 16px;
            margin: 0;
            flex-shrink: 0;
            accent-color: #426b46;
        }

        .ay-toggle {
            margin-bottom: 18px;
            padding: 12px;
            border-radius: 8px;
            background: #edf2e6;
        }

        .ay-alt-kart {
            margin-top: 16px;
            padding: 16px;
            border: 1px solid #e2dfd2;
            border-radius: 10px;
            background: #f7f5ee;
        }

        .ay-alt-kart h4 {
            margin: 0 0 13px;
        }

        .ay-kategoriler {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .ay-kategori {
            min-width: 0;
            padding: 15px;
            border: 1px solid #e1dece;
            border-radius: 10px;
            background: #f7f5ee;
        }

        .ay-kategori img {
            display: block;
            width: 80px;
            height: 80px;
            margin-bottom: 12px;
            border-radius: 50%;
            object-fit: cover;
        }

        .ay-kategori strong {
            display: block;
            margin-bottom: 12px;
        }

        .ay-panel input[type="file"] {
            width: 100%;
            margin-bottom: 10px;
            font-size: 12px;
        }

        .ay-kapak {
            display: block;
            width: min(440px, 100%);
            max-height: 260px;
            margin-bottom: 15px;
            border-radius: 12px;
            background: #e6eddf;
            object-fit: contain;
        }

        .ay-vitrin {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            max-height: 330px;
            overflow: auto;
            padding: 4px;
        }

        .ay-vitrin label {
            align-items: flex-start;
            padding: 12px;
            border: 1px solid #ded6c6;
            border-radius: 8px;
        }

        .ay-vitrin small {
            display: block;
            color: #687361;
        }

        .ay-islemler {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .ay-panel .ay-buton {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: auto;
            margin: 0;
            padding: 12px 18px;
            border: 0;
            border-radius: 8px;
            background: #426b46;
            color: white;
            font: normal 14px Arial, sans-serif;
            text-decoration: none;
            cursor: pointer;
        }

        .ay-panel .ay-buton.acik {
            background: #eee5d3;
            color: #284b32;
        }

        .ay-panel .ay-buton:hover {
            background: #284b32;
            color: white;
        }

        .ay-hata {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #deb4a9;
            border-radius: 10px;
            background: #f9e6e0;
            color: #923e30;
            line-height: 1.6;
        }

        .ay-basari {
            position: fixed;
            top: 50%;
            left: 50%;
            z-index: 9999;
            transform: translate(-50%, -50%);
            width: min(380px, calc(100% - 30px));
            padding: 25px;
            border: 1px solid #bdd1b2;
            border-radius: 14px;
            background: #fffdf8;
            box-shadow: 0 12px 40px #284b3233;
            text-align: center;
            pointer-events: none;
            animation: ay-kaybol 2s forwards;
        }

        .ay-basari b {
            display: block;
            margin-bottom: 10px;
            color: #426b46;
            font-size: 30px;
        }

        .ay-onizleme {
            display: block;
            width: 100%;
            height: 650px;
            border: 1px solid #ded6c6;
            border-radius: 10px;
            background: #f5f0e6;
        }

        .ay-panel :focus-visible {
            outline: 2px solid #426b46;
            outline-offset: 3px;
        }

        @keyframes ay-kaybol {
            0%, 85% {
                opacity: 1;
                visibility: visible;
            }

            100% {
                opacity: 0;
                visibility: hidden;
            }
        }

        @media (max-width: 1000px) {
            .ay-kategoriler,
            .ay-vitrin {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 650px) {
            .ay-grid,
            .ay-vitrin,
            .ay-kategoriler {
                grid-template-columns: 1fr;
            }

            .ay-kart {
                padding: 17px;
            }

            .ay-onizleme {
                height: 500px;
            }
        }
    </style>
</head>
<body>

<?php require __DIR__ . "/admin-menu.php"; ?>

<main class="icerik ay-panel">

    <div class="ay-ust">
        <div>
            <h2>Ana Sayfa Yönetimi</h2>
            <p>Başlıkları, görselleri ve bölümleri bu ekrandan yönetin.</p>
        </div>

        <a
            class="ay-buton acik"
            href="../index.php"
            target="_blank"
            rel="noopener noreferrer"
        >
            Ana Sayfayı Aç ↗
        </a>
    </div>

    <?php if ($basari !== ""): ?>
        <div class="ay-basari" id="ay-basari" role="status">
            <b aria-hidden="true">✓</b>
            <?= aa_h($basari) ?>
        </div>
    <?php endif; ?>

    <?php if ($hata !== ""): ?>
        <div class="ay-hata" role="alert">
            <?= aa_h($hata) ?>
            Dosya seçtiyseniz tekrar seçiniz.
        </div>
    <?php endif; ?>

    <form
        method="POST"
        action="admin-anasayfa.php"
        enctype="multipart/form-data"
        class="ay-form"
        id="ayar-form"
    >
        <input
            type="hidden"
            name="token"
            value="<?= aa_h($_SESSION['anasayfa_token']) ?>"
        >

        <section class="ay-kart">
            <h3>Karşılama Alanı</h3>

            <label class="ay-secim ay-toggle">
                <input
                    type="checkbox"
                    name="tanitim_aktif"
                    <?= $ayar["tanitim_aktif"] ? "checked" : "" ?>
                >
                Karşılama alanını göster
            </label>

            <div class="ay-grid">
                <?php
                ay_field("baslik", "Başlık", $icerik["baslik"], false, 150);
                ay_field(
                    "buton_yazisi",
                    "Buton yazısı",
                    $icerik["buton_yazisi"],
                    false,
                    80
                );
                ay_field("aciklama", "Açıklama", $icerik["aciklama"], true);
                ?>

                <div class="ay-alan">
                    <label for="buton-linki">Butonun açacağı sayfa</label>

                    <select name="buton_linki" id="buton-linki">
                        <?php foreach (aa_links() as $url => $name): ?>
                            <option
                                value="<?= aa_h($url) ?>"
                                <?= $ayar["buton_linki"] === $url ? "selected" : "" ?>
                            >
                                <?= aa_h($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h4>Kapak Görseli</h4>

            <?php if ($cover !== ""): ?>
                <img
                    class="ay-kapak"
                    src="../<?= aa_h($cover) ?>"
                    alt="Mevcut kapak"
                >
            <?php endif; ?>

            <input
                type="file"
                name="kapak"
                accept="image/jpeg,image/png,image/webp"
            >

            <p class="ay-not">
                Görsel ana sayfada yazının sağında gösterilir.
                Her görsel en fazla 5 MB olabilir.
                Yeni görsel seçerseniz yüklediğiniz görsel kullanılır.
            </p>

            <label class="ay-secim">
                <input type="checkbox" name="kapak_kaldir">
                Yüklenen kapağı kaldır, images/anasayfa.png görseline dön
            </label>
        </section>

        <section class="ay-kart">
            <h3>Duyuru Bandı</h3>

            <label class="ay-secim ay-toggle">
                <input
                    type="checkbox"
                    name="duyuru_aktif"
                    <?= $ayar["duyuru_aktif"] ? "checked" : "" ?>
                >
                Duyuru bandını göster
            </label>

            <?php
            ay_field("duyuru", "Duyuru metni", $ayar["duyuru"], true, 300);
            ?>
        </section>

        <?php foreach ($titles as $key => $title): ?>
            <?php $section = $b[$key]; ?>

            <section class="ay-kart">
                <h3><?= aa_h($title) ?></h3>

                <?php if (array_key_exists("aktif", $section)): ?>
                    <label class="ay-secim ay-toggle">
                        <input
                            type="checkbox"
                            name="bolum[<?= aa_h($key) ?>][aktif]"
                            <?= $section["aktif"] ? "checked" : "" ?>
                        >
                        Bu bölümü ana sayfada göster
                    </label>
                <?php endif; ?>

                <?php if ($key === "kategoriler" || $key === "vitrin"): ?>
                    <?php
                    $flag = $key === "kategoriler"
                        ? "kategoriler_aktif"
                        : "vitrin_aktif";
                    ?>

                    <label class="ay-secim ay-toggle">
                        <input
                            type="checkbox"
                            name="<?= aa_h($flag) ?>"
                            <?= $ayar[$flag] ? "checked" : "" ?>
                        >
                        Bu bölümü ana sayfada göster
                    </label>
                <?php endif; ?>

                <div class="ay-grid">
                    <?php
                    foreach ($section as $field => $value) {
                        if (
                            is_array($value)
                            || is_int($value)
                            || $field === "buton_linki"
                        ) {
                            continue;
                        }

                        $multi = in_array(
                            $field,
                            ["aciklama", "liste"],
                            true
                        ) || ($key === "davet" && $field === "baslik");

                        $max = in_array(
                            $field,
                            ["aciklama", "liste"],
                            true
                        ) ? 3000 : 150;

                        ay_field(
                            "bolum[" . $key . "][" . $field . "]",
                            $labels[$field] ?? $field,
                            $value,
                            $multi,
                            $max
                        );
                    }
                    ?>

                    <?php if ($key === "davet"): ?>
                        <div class="ay-alan">
                            <label for="davet-link">
                                Butonun açacağı sayfa
                            </label>

                            <select
                                name="bolum[davet][buton_linki]"
                                id="davet-link"
                            >
                                <?php foreach (aa_links() as $url => $name): ?>
                                    <option
                                        value="<?= aa_h($url) ?>"
                                        <?= $section["buton_linki"] === $url ? "selected" : "" ?>
                                    >
                                        <?= aa_h($name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($key === "sehir"): ?>
                    <p class="ay-not">
                        Her satıra bir şehir yazınız. En fazla 24 şehir
                        ekleyebilirsiniz. Şehir düğmeleri ilgili şehrin
                        ilanlarını açar.
                    </p>
                <?php endif; ?>

                <?php if ($key === "kategoriler"): ?>
                    <p class="ay-not">
                        Yeni görsel seçmezseniz mevcut görsel korunur.
                        Varsayılan görseller images klasöründeki
                        hayvan resimleridir.
                    </p>

                    <div class="ay-kategoriler">
                        <?php $i = 0; ?>

                        <?php foreach (aa_categories() as $name => $file): ?>
                            <?php
                            $img = aa_image(
                                $section["ogeler"][$name]["gorsel"]
                            ) ?: aa_image("images/" . $file);
                            ?>

                            <div class="ay-kategori">
                                <img
                                    src="../<?= aa_h($img ?: 'images/aris-logoo.png') ?>"
                                    alt="<?= aa_h($name) ?>"
                                >

                                <strong><?= aa_h($name) ?></strong>

                                <label for="kat-<?= $i ?>" class="ay-not">
                                    Yeni görsel
                                </label>

                                <input
                                    id="kat-<?= $i ?>"
                                    type="file"
                                    name="kat_<?= $i ?>"
                                    accept="image/jpeg,image/png,image/webp"
                                >

                                <label class="ay-secim">
                                    <input
                                        type="checkbox"
                                        name="kat_sifirla_<?= $i ?>"
                                    >
                                    Varsayılan görsele dön
                                </label>
                            </div>

                            <?php $i++; ?>
                        <?php endforeach; ?>
                    </div>

                <?php elseif (isset($section["ogeler"])): ?>

                    <?php if ($key === "sss"): ?>
                        <p class="ay-not">
                            Bir soruyu gizlemek için soru ve cevap
                            alanlarını birlikte boş bırakınız.
                        </p>
                    <?php endif; ?>

                    <?php foreach ($section["ogeler"] as $i => $row): ?>
                        <div class="ay-alt-kart">
                            <h4>
                                <?php
                                echo ($key === "sss"
                                    ? "Soru "
                                    : ($key === "surec" ? "Adım " : "Tanıtım "))
                                    . ($i + 1);
                                ?>
                            </h4>

                            <div class="ay-grid">
                                <?php
                                ay_field(
                                    "bolum[" . $key . "][ogeler][" . $i . "][baslik]",
                                    $key === "sss" ? "Soru" : "Başlık",
                                    $row["baslik"],
                                    false,
                                    150
                                );

                                ay_field(
                                    "bolum[" . $key . "][ogeler][" . $i . "][aciklama]",
                                    $key === "sss" ? "Cevap" : "Açıklama",
                                    $row["aciklama"],
                                    true
                                );
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php endif; ?>

                <?php if ($key === "vitrin"): ?>
                    <p class="ay-not">
                        En fazla 12 onaylı ilan seçebilirsiniz.
                        İlanlar en yeniden eskiye sıralanır.
                        Onaydan kaldırılan ilan ana sayfada gösterilmez.
                    </p>

                    <p id="vitrin-sayac" class="ay-not"></p>

                    <div class="ay-vitrin">
                        <?php foreach ($onayli as $id => $ilan): ?>
                            <label class="ay-secim">
                                <input
                                    type="checkbox"
                                    name="vitrin[]"
                                    value="<?= $id ?>"
                                    <?= in_array($id, $secili, true) ? "checked" : "" ?>
                                >

                                <span>
                                    #<?= $id ?> —
                                    <?= aa_h($ilan["hayvan_adi"]) ?>

                                    <small>
                                        <?= aa_h($ilan["tur"]) ?>
                                        ·
                                        <?= aa_h($ilan["sehir"]) ?>
                                    </small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!$onayli): ?>
                        <p>Henüz onaylanmış ilan yok.</p>
                    <?php endif; ?>
                <?php endif; ?>

            </section>
        <?php endforeach; ?>

        <div class="ay-kart ay-islemler">
            <button type="submit" class="ay-buton">
                Tüm Değişiklikleri Kaydet
            </button>

            <a
                href="../index.php"
                target="_blank"
                rel="noopener noreferrer"
                class="ay-buton acik"
            >
                Ana Sayfayı Aç ↗
            </a>

            <span class="ay-not">
                Değişiklikler kaydedildikten sonra ziyaretçilere yansır.
            </span>
        </div>
    </form>

    <section class="ay-kart">
        <h3>Kaydedilmiş Ana Sayfa Önizlemesi</h3>

        <p class="ay-not">
            Bu alan gerçek ana sayfayı gösterir.
            Değişiklikleri kaydedince önizleme yenilenir.
        </p>

        <iframe
            class="ay-onizleme"
            src="../index.php?onizleme=<?= time() ?>"
            title="Kaydedilmiş ana sayfa önizlemesi"
            loading="lazy"
        ></iframe>
    </section>

</main>

<script>
    const form = document.getElementById("ayar-form");
    const count = document.getElementById("vitrin-sayac");

    function secilenleriSay() {
        const adet = form.querySelectorAll(
            'input[name="vitrin[]"]:checked'
        ).length;

        count.textContent = adet + " / 12 ilan seçildi";

        return adet;
    }

    form.addEventListener("change", secilenleriSay);

    form.addEventListener("submit", function (event) {
        if (secilenleriSay() > 12) {
            event.preventDefault();
            alert("En fazla 12 vitrin ilanı seçebilirsiniz.");
        }
    });

    secilenleriSay();

    const bildirim = document.getElementById("ay-basari");

    if (bildirim) {
        setTimeout(function () {
            bildirim.remove();
        }, 2000);
    }
</script>

</body>
</html>