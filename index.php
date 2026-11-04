<?php
session_start();
require_once __DIR__ . "/site-ust-ayar.php";

require_once __DIR__ . "/baglanti.php";

/* Dil tercihi */
if (
    is_string($_GET["lang"] ?? null)
    && in_array($_GET["lang"], ["tr", "en"], true)
) {
    $_SESSION["dil"] = $_GET["lang"];

    header("Location: index.php");
    exit();
}

$dil = in_array($_SESSION["dil"] ?? "", ["tr", "en"], true)
    ? $_SESSION["dil"]
    : "tr";

function aa_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function guvenli($value)
{
    return aa_h($value);
}

function aa_t($tr, $en)
{
    global $dil;

    return $dil === "en" ? $en : $tr;
}

function aa_category_name($name)
{
    global $dil;

    $names = [
        "Kedi" => "Cat",
        "Köpek" => "Dog",
        "Kuş" => "Bird",
        "Balık" => "Fish",
        "Tavşan" => "Rabbit",
        "Kemirgen" => "Rodent",
        "Sürüngen" => "Reptile",
        "Diğer" => "Other"
    ];

    return $dil === "en" ? ($names[$name] ?? $name) : $name;
}

function aa_links()
{
    return [
        "ilanlar.php" => aa_t("İlanlar", "Listings"),
        "ilan-ver.php" => aa_t("İlan Ver", "Post a Listing"),
        "blog.php" => "Blog",
        "hakkimizda.php" => aa_t("Hakkımızda", "About Us"),
        "iletisim.php" => aa_t("İletişim", "Contact")
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
        "SHOW TABLES LIKE 'anasayfa_bolumler'"
    );

    if (!mysqli_num_rows($result)) {
        return $defaults;
    }

    $result = mysqli_query(
        $db,
        "SELECT icerik_json
         FROM anasayfa_bolumler
         WHERE id = 1"
    );

    $row = mysqli_fetch_assoc($result);

    $data = json_decode(
        (string)($row["icerik_json"] ?? ""),
        true
    );

    return aa_merge(
        $defaults,
        is_array($data) ? $data : []
    );
}

/*
 * İngilizce metinler.
 * Bölümlerin aktiflik, görsel, şehir ve bağlantı ayarları korunur.
 */
function aa_english_sections($sections)
{
    $sections["degerler"]["ogeler"] = [
        [
            "baslik" => "Let a friendship begin",
            "aciklama" => "Welcome a loving companion into your life."
        ],
        [
            "baslik" => "Give hope a home",
            "aciklama" => "Change the story of a pet looking for a family."
        ],
        [
            "baslik" => "Adopt responsibly",
            "aciklama" => "Take the first step towards a lifelong bond."
        ]
    ];

    $sections["kategoriler"]["etiket"] = "Your new friend is waiting";
    $sections["kategoriler"]["baslik"] = "Who would you like to meet?";

    $sections["vitrin"]["etiket"] = "Your next companion could be here";
    $sections["vitrin"]["baslik"] = "Friends looking for a home";
    $sections["vitrin"]["aciklama"] =
        "Different stories, one shared dream: a loving home.";

    $sections["surec"]["etiket"] = "The path to friendship";
    $sections["surec"]["baslik"] = "Three steps to a new beginning";
    $sections["surec"]["aciklama"] =
        "Find a companion who suits your life, get to know them and send your adoption application.";

    $sections["surec"]["ogeler"] = [
        [
            "baslik" => "Find your companion",
            "aciklama" => "Browse the listings. Filter by animal type and city to discover a suitable companion."
        ],
        [
            "baslik" => "Get to know them",
            "aciklama" => "Read the listing details and contact the person caring for the animal to learn about their needs."
        ],
        [
            "baslik" => "Send your application",
            "aciklama" => "Sign in and submit an adoption application. Follow its status from your profile."
        ]
    ];

    $sections["sehir"]["etiket"] = "A wonderful meeting could be nearby";
    $sections["sehir"]["baslik"] = "Find a friend near you.";
    $sections["sehir"]["aciklama"] =
        "Explore adoption listings in your city. Your new companion may be closer than you think.";

    $sections["sss"]["etiket"] = "Your questions answered";
    $sections["sss"]["baslik"] = "Before you adopt.";
    $sections["sss"]["aciklama"] =
        "Welcoming a companion is a wonderful beginning. Consider their needs and your living arrangements before making a decision.";

    $sections["sss"]["ogeler"] = [
        [
            "baslik" => "Do I need an account to adopt?",
            "aciklama" => "You can browse listings without an account. You need to sign in to send an adoption application."
        ],
        [
            "baslik" => "How can I contact the listing owner?",
            "aciklama" => "Use the messaging option on the listing details page. You need to sign in to send a message."
        ],
        [
            "baslik" => "Where can I follow my application?",
            "aciklama" => "You can check its status in the My Adoption Applications section of your profile."
        ],
        [
            "baslik" => "What should I consider before adopting?",
            "aciklama" => "Think about your available time, living space, veterinary care and feeding needs. Discuss these responsibilities with the people you live with."
        ]
    ];

    $sections["davet"]["baslik"] =
        "A friend for you,\na whole world for them.";

    $sections["davet"]["aciklama"] =
        "Create a listing for a pet looking for a home and help them meet their new family.";

    $sections["davet"]["buton_yazisi"] = "Post an Adoption Listing";

    $sections["footer"]["aciklama"] =
        "We bring together animals looking for a home and people ready to care for them. A home for every companion.";

    return $sections;
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

    return is_file(__DIR__ . "/" . $path) ? $path : "";
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

function aa_listing_image($fotograf)
{
    $fotograf = (string)$fotograf;
    $base = basename($fotograf);

    if (
        preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$/i', $base)
        && is_file(__DIR__ . "/uploads/" . $base)
    ) {
        return "uploads/" . $base;
    }

    if (
        preg_match(
            '~^images/hayvanlar/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$~i',
            $fotograf
        )
        && is_file(__DIR__ . "/" . $fotograf)
    ) {
        return $fotograf;
    }

    return "";
}

function aa_cities($text)
{
    $lines = preg_split('/\R/u', (string)$text);

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

/* Okunmamış mesaj bildirimi */
$uye_id = (int)($_SESSION["uye_id"] ?? 0);
$okunmamis_mesaj_var = false;

if ($uye_id > 0) {
    $query = mysqli_prepare(
        $baglanti,
        "SELECT id
         FROM ilan_mesajlari
         WHERE alici_id = ? AND okundu = 0
         LIMIT 1"
    );

    mysqli_stmt_bind_param($query, "i", $uye_id);
    mysqli_stmt_execute($query);

    $okunmamis_mesaj_var = mysqli_num_rows(
        mysqli_stmt_get_result($query)
    ) > 0;

    mysqli_stmt_close($query);
}

/* Yönetim panelindeki ana sayfa içeriği */
$result = mysqli_query(
    $baglanti,
    "SELECT baslik, aciklama, buton_yazisi
     FROM anasayfa_icerik
     WHERE id = 1"
);

$icerik = mysqli_fetch_assoc($result) ?: [
    "baslik" => "Bir dost edin, bir hayat değiştir.",
    "aciklama" => "Yuva arayan dostlarımızla tanış.",
    "buton_yazisi" => "Dostunu Bul"
];

$result = mysqli_query(
    $baglanti,
    "SELECT * FROM anasayfa_ayarlar WHERE id = 1"
);

$ayar = array_merge([
    "buton_linki" => "ilanlar.php",
    "kapak" => null,
    "duyuru" => "",
    "duyuru_aktif" => 0,
    "tanitim_aktif" => 1,
    "kategoriler_aktif" => 1,
    "vitrin_aktif" => 1,
    "vitrin_idler" => "[]"
], mysqli_fetch_assoc($result) ?: []);

$bolumler = aa_read($baglanti);
$kategoriler = aa_categories();

$buton_linki = isset(aa_links()[$ayar["buton_linki"]])
    ? $ayar["buton_linki"]
    : "ilanlar.php";

$kapak_yolu = aa_cover($ayar);

if ($dil === "en") {
    $bolumler = aa_english_sections($bolumler);

    $icerik = [
        "baslik" => "Find a friend, change a life.",
        "aciklama" => "Meet our companions looking for a loving home.",
        "buton_yazisi" => aa_links()[$buton_linki]
    ];
}

/* Vitrin ilanları */
$ids = json_decode((string)$ayar["vitrin_idler"], true);
$temiz_idler = [];

foreach (is_array($ids) ? $ids : [] as $value) {
    if (!is_string($value) && !is_int($value)) {
        continue;
    }

    $id = filter_var($value, FILTER_VALIDATE_INT);

    if ($id && $id > 0) {
        $temiz_idler[] = $id;
    }
}

$temiz_idler = array_slice(
    array_values(array_unique($temiz_idler)),
    0,
    12
);

$vitrin = [];

if ($ayar["vitrin_aktif"] && $temiz_idler) {
    $id_listesi = implode(",", $temiz_idler);

    $result = mysqli_query(
        $baglanti,
        "SELECT id, hayvan_adi, tur, sehir, fotograf
         FROM ilanlar
         WHERE durum = 'Onaylandı'
           AND id IN ($id_listesi)
         ORDER BY id DESC
         LIMIT 12"
    );

    while ($ilan = mysqli_fetch_assoc($result)) {
        $vitrin[] = $ilan;
    }
}
?>

<!DOCTYPE html>
<html lang="<?= aa_h($dil) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="<?= aa_h(aa_t(
            "Aris Pati Yuvası ile yuva arayan dostlarımızla tanış. Sahiplendirme ilanlarını incele, şehrindeki yeni dostunu bul.",
            "Meet pets looking for a loving home with Aris Pati Yuvası. Browse adoption listings and find a companion in your city."
        )) ?>"
    >

    <meta name="theme-color" content="#426b46">

    <title><?= aa_h(aa_t(
        "Aris Pati Yuvası | Bir Dost, Yeni Bir Başlangıç",
        "Aris Pati Yuvası | A Friend, A New Beginning"
    )) ?></title>

    <style>
        :root {
            --yesil: #426b46;
            --koyu: #284b32;
            --krem: #f5f0e6;
            --beyaz: #fffdf8;
            --acik: #e6eddf;
            --cizgi: #dddccc;
            --metin: #65715f;
        }

        * { box-sizing: border-box; }

        html {
            scrollbar-gutter: stable;
            scroll-behavior: smooth;
            scroll-padding-top: 120px;
        }

        body {
            margin: 0;
            background: var(--krem);
            color: var(--koyu);
            font-family: Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        img { max-width: 100%; }
        a { color: inherit; }
        button { font: inherit; }

        a:focus-visible,
        button:focus-visible,
        summary:focus-visible {
            outline: 3px solid #8eac73;
            outline-offset: 4px;
        }

        .kapsayici {
            width: min(1200px, calc(100% - 48px));
            margin-inline: auto;
        }

        .atla {
            position: fixed;
            top: -100px;
            left: 20px;
            z-index: 999;
            padding: 12px 18px;
            background: white;
            border-radius: 8px;
        }

        .atla:focus { top: 12px; }

        /* Üst menü */

        .ilan-ust {
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 18px;
            min-height: 88px;
            padding: 16px 24px;
            border-bottom: 1px solid #35583b;
            background: var(--yesil);
        }

        .ilan-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            color: white;
            text-decoration: none;
        }

        .ilan-logo img {
            display: block;
            width: 54px;
            height: 54px;
            padding: 5px;
            border: 1px solid #eee5d3;
            border-radius: 12px;
            background: var(--beyaz);
            object-fit: contain;
        }

        .ilan-logo strong {
            display: block;
            font-size: 20px;
            line-height: 1.3;
        }

        .ilan-logo small {
            display: block;
            margin-top: 5px;
            color: #e3ebdd;
            font-size: 11px;
        }

        .ust-sag {
            display: flex;
            flex: 1;
            min-width: 0;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
        }

        .dil-secici {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px;
            border: 1px solid rgba(255, 255, 255, .35);
            border-radius: 8px;
        }

        .dil-secici a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 37px;
            min-height: 30px;
            padding: 5px 9px;
            border-radius: 5px;
            color: white;
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
        }

        .dil-secici a:hover {
            background: var(--koyu);
        }

        .dil-secici a[aria-current="true"] {
            background: var(--krem);
            color: var(--koyu);
        }

        .ilan-menu {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 4px;
            margin-left: auto;
        }

        .ilan-menu > a,
        .kategori-baslik {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 40px;
            padding: 10px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: white;
            font: normal 14px/20px Arial, sans-serif;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
        }

        .ilan-menu > a:hover,
        .kategori-menu:hover .kategori-baslik,
        .kategori-menu:focus-within .kategori-baslik {
            background: var(--koyu);
        }

        .ilan-menu > a[aria-current="page"] {
            background: #eee5d3;
            color: var(--koyu);
            font-weight: bold;
        }

        .mesaj-bildirim {
            width: 9px;
            height: 9px;
            border: 1px solid white;
            border-radius: 50%;
            background: #ef5350;
            flex-shrink: 0;
        }

        .kategori-menu { position: relative; }

        .kategori-acilir {
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 200;
            display: none;
            width: 210px;
            max-width: calc(100vw - 36px);
            padding: 8px;
            border: 1px solid var(--cizgi);
            border-radius: 12px;
            background: var(--beyaz);
            box-shadow: 0 14px 35px rgba(40, 75, 50, .16);
        }

        .kategori-menu:hover .kategori-acilir,
        .kategori-menu:focus-within .kategori-acilir {
            display: block;
        }

        .kategori-acilir a {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 44px;
            padding: 7px 10px;
            border-radius: 7px;
            font-size: 14px;
            text-decoration: none;
        }

        .kategori-acilir a:hover { background: var(--acik); }

        .kategori-acilir img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            background: var(--acik);
        }

        /* Karşılama alanı */

        .duyuru {
            padding: 13px 24px;
            background: var(--acik);
            font-size: 14px;
            line-height: 1.6;
            text-align: center;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .hero { padding-top: 34px; }

        .hero-kutu {
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            gap: 34px;
            min-height: 460px;
            padding: 42px;
            border: 1px solid #d9dfcd;
            border-radius: 26px;
            background: var(--acik);
        }

        .hero-yazi { min-width: 0; }

        .etiket {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            color: var(--yesil);
            font-size: 12px;
            font-weight: bold;
            line-height: 1.6;
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        .etiket::before {
            content: "";
            width: 22px;
            height: 2px;
            flex-shrink: 0;
            background: currentColor;
        }

        .hero h1 {
            margin: 0 0 20px;
            font-size: clamp(34px, 4.1vw, 56px);
            line-height: 1.1;
            letter-spacing: -1.8px;
            overflow-wrap: anywhere;
        }

        .hero-aciklama {
            max-width: 490px;
            margin: 0 0 26px;
            color: #53664e;
            font-size: 16px;
            line-height: 1.8;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .butonlar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .buton {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            min-height: 48px;
            max-width: 100%;
            padding: 14px 22px;
            border: 1px solid var(--yesil);
            border-radius: 10px;
            background: var(--yesil);
            color: white;
            font-size: 14px;
            font-weight: bold;
            line-height: 1.5;
            text-decoration: none;
            text-align: center;
            transition: background .2s, transform .2s;
        }

        .buton:hover {
            transform: translateY(-2px);
            background: var(--koyu);
        }

        .buton.ikincil {
            border-color: #bac8b1;
            background: transparent;
            color: var(--koyu);
        }

        .buton.ikincil:hover { background: var(--beyaz); }

        .hero-not {
            margin: 22px 0 0;
            color: #53664e;
            font-size: 12px;
            line-height: 1.6;
        }

        .hero-gorsel { min-width: 0; }

        .hero-gorsel img {
            display: block;
            width: 100%;
            height: auto;
            border: 1px solid #d3ddc7;
            border-radius: 20px;
            background: #d6dfc9;
        }

        .hero-gorsel .kapak-yok {
            min-height: 250px;
            padding: 50px;
            object-fit: contain;
        }

        /* Kısa tanıtım */

        .degerler {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 28px;
            padding: 28px 10px;
            border-bottom: 1px solid var(--cizgi);
        }

        .deger {
            padding-left: 15px;
            border-left: 3px solid #a8bc97;
        }

        .deger strong {
            display: block;
            font-size: 14px;
        }

        .deger p {
            margin: 6px 0 0;
            color: var(--metin);
            font-size: 12px;
            line-height: 1.6;
        }

        /* Bölümler */

        .bolum { padding-block: 60px; }

        .bolum-baslik {
            display: flex;
            align-items: end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 26px;
        }

        .bolum-baslik .etiket { margin-bottom: 12px; }

        .bolum h2,
        .sehir-alani h2,
        .davet h2 {
            margin: 0;
            font-size: clamp(25px, 3vw, 36px);
            line-height: 1.2;
            letter-spacing: -1px;
        }

        .bolum-baslik p {
            max-width: 580px;
            margin: 12px 0 0;
            color: var(--metin);
            font-size: 14px;
            line-height: 1.7;
        }

        .metin-link {
            display: inline-block;
            padding-block: 8px;
            color: var(--yesil);
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
        }

        .metin-link:hover {
            text-decoration: underline;
            text-underline-offset: 5px;
        }

        .kategori-grid {
            display: grid;
            grid-template-columns: repeat(8, minmax(0, 1fr));
            gap: 14px;
        }

        .kategori-kart {
            display: flex;
            align-items: center;
            flex-direction: column;
            gap: 15px;
            min-width: 0;
            padding: 20px 8px;
            border: 1px solid #dedfce;
            border-radius: 18px;
            background: var(--beyaz);
            text-decoration: none;
            transition: transform .2s, box-shadow .2s, border-color .2s;
        }

        .kategori-fotograf {
            display: block;
            width: 90px;
            height: 90px;
            padding: 4px;
            border: 1px solid #d6dfcd;
            border-radius: 50%;
            background: #f5f0e6;
            object-fit: cover;
        }

        .kategori-kart strong {
            font-size: 14px;
            font-weight: 600;
        }

        .kategori-kart:hover {
            transform: translateY(-5px);
            border-color: #91aa80;
            box-shadow: 0 12px 24px rgba(40, 75, 50, .1);
        }

        /* Vitrin ilanları */

        .vitrin-bolumu { padding-top: 10px; }

        .vitrin-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 22px;
        }

        .ilan-kart {
            display: block;
            min-width: 0;
            overflow: hidden;
            border: 1px solid var(--cizgi);
            border-radius: 18px;
            background: var(--beyaz);
            text-decoration: none;
            transition: transform .2s, box-shadow .2s;
        }

        .ilan-kart:hover {
            transform: translateY(-5px);
            box-shadow: 0 14px 30px rgba(40, 75, 50, .1);
        }

        .ilan-gorsel {
            position: relative;
            overflow: hidden;
            aspect-ratio: 1.15;
            background: var(--acik);
        }

        .ilan-gorsel img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ilan-gorsel img.logo-yedek {
            padding: 45px;
            object-fit: contain;
        }

        .ilan-rozet {
            position: absolute;
            bottom: 12px;
            left: 12px;
            padding: 6px 10px;
            border-radius: 7px;
            background: var(--beyaz);
            font-size: 11px;
            font-weight: bold;
        }

        .ilan-bilgi {
            padding: 19px;
            overflow-wrap: anywhere;
        }

        .ilan-bilgi h3 {
            margin: 0 0 9px;
            font-size: 21px;
        }

        .ilan-bilgi p {
            margin: 0;
            color: var(--metin);
            font-size: 13px;
            line-height: 1.6;
        }

        .ilan-alt {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-top: 17px;
            padding-top: 13px;
            border-top: 1px solid #e7e7dc;
            color: var(--yesil);
            font-size: 13px;
            font-weight: bold;
        }

        /* Sahiplenme adımları */

        .surec {
            border-block: 1px solid #dbe1d3;
            background: #edf2e8;
        }

        .surec-baslik {
            max-width: 650px;
            margin: 0 auto 32px;
            text-align: center;
        }

        .surec-baslik p {
            margin: 14px 0 0;
            color: var(--metin);
            font-size: 14px;
            line-height: 1.7;
        }

        .adim-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .adim {
            padding: 28px;
            border: 1px solid #d9e0cf;
            border-radius: 16px;
            background: var(--beyaz);
        }

        .adim-no {
            display: inline-grid;
            place-items: center;
            width: 42px;
            height: 42px;
            margin-bottom: 20px;
            border-radius: 12px;
            background: var(--acik);
            color: var(--yesil);
            font-size: 15px;
            font-weight: bold;
        }

        .adim h3 {
            margin: 0 0 12px;
            font-size: 19px;
        }

        .adim p {
            margin: 0;
            color: var(--metin);
            font-size: 14px;
            line-height: 1.8;
        }

        /* Şehirler */

        .sehir-alani {
            padding-block: 54px;
            background: var(--yesil);
            color: var(--beyaz);
        }

        .sehir-icerik {
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            gap: 40px;
        }

        .sehir-alani .etiket {
            margin-bottom: 15px;
            color: #e0e9d6;
        }

        .sehir-alani p {
            max-width: 450px;
            margin: 16px 0 0;
            color: #e1e9db;
            font-size: 14px;
            line-height: 1.8;
        }

        .sehirler {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .sehirler a {
            padding: 14px 10px;
            border: 1px solid #d6dfca;
            border-radius: 9px;
            background: var(--beyaz);
            color: var(--koyu);
            font-size: 14px;
            text-align: center;
            text-decoration: none;
        }

        .sehirler a:hover { background: #dce8cf; }

        .sehir-link {
            display: inline-block;
            margin-top: 18px;
            color: white;
            font-size: 13px;
            text-underline-offset: 5px;
        }

        /* Sık sorulan sorular */

        .sss-duzen {
            display: grid;
            grid-template-columns: .85fr 1.15fr;
            align-items: start;
            gap: 60px;
        }

        .sss-giris p {
            margin: 17px 0 18px;
            color: var(--metin);
            font-size: 14px;
            line-height: 1.8;
        }

        .sss-listesi details {
            border-bottom: 1px solid var(--cizgi);
        }

        .sss-listesi details:first-child {
            border-top: 1px solid var(--cizgi);
        }

        .sss-listesi summary {
            position: relative;
            padding: 21px 32px 21px 0;
            font-size: 15px;
            font-weight: bold;
            line-height: 1.5;
            list-style: none;
            cursor: pointer;
        }

        .sss-listesi summary::-webkit-details-marker { display: none; }

        .sss-listesi summary::after {
            content: "+";
            position: absolute;
            top: 17px;
            right: 3px;
            color: var(--yesil);
            font-size: 24px;
            font-weight: normal;
        }

        .sss-listesi details[open] summary::after { content: "−"; }

        .sss-listesi details p {
            margin: 0;
            padding: 0 20px 21px 0;
            color: var(--metin);
            font-size: 14px;
            line-height: 1.8;
        }

        /* Son davet */

        .davet {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
            margin-bottom: 60px;
            padding: 38px 42px;
            border: 1px solid #d7dfcb;
            border-radius: 20px;
            background: var(--acik);
        }

        .davet h2 {
            max-width: 650px;
            font-size: clamp(25px, 2.8vw, 33px);
        }

        .davet p {
            margin: 14px 0 0;
            color: #53664e;
            font-size: 14px;
            line-height: 1.7;
        }

        .davet .buton { flex-shrink: 0; }

        /* Alt menü */

        .pati-footer {
            background: var(--koyu);
            color: var(--beyaz);
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1fr;
            gap: 40px;
            padding-block: 50px;
        }

        .footer-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .footer-logo img {
            width: 48px;
            height: 48px;
            padding: 5px;
            border-radius: 10px;
            background: var(--beyaz);
            object-fit: contain;
        }

        .footer-logo strong { font-size: 19px; }

        .footer-tanitim {
            max-width: 310px;
            margin: 18px 0 0;
            color: #cedbc6;
            font-size: 13px;
            line-height: 1.9;
        }

        .pati-footer h3 {
            margin: 7px 0 20px;
            font-size: 14px;
        }

        .footer-linkler {
            display: flex;
            align-items: flex-start;
            flex-direction: column;
            gap: 13px;
        }

        .footer-linkler a {
            color: #d4dfcc;
            font-size: 13px;
            line-height: 1.5;
            text-decoration: none;
        }

        .footer-linkler a:hover {
            color: white;
            text-decoration: underline;
            text-underline-offset: 4px;
        }

        .footer-taban { border-top: 1px solid #48664b; }

        .footer-taban > div {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            padding-block: 21px;
            color: #d4dfcc;
            font-size: 12px;
        }

        .footer-taban a { text-decoration: none; }

        @media (max-width: 1100px) {
            .ust-sag { flex-basis: 100%; }
            .ilan-menu { width: 100%; }
            .hero-kutu { padding: 32px; gap: 25px; }
            .hero h1 { font-size: 43px; }

            .kategori-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .sss-duzen { gap: 35px; }
        }

        @media (max-width: 900px) {
            .hero-kutu { grid-template-columns: 1fr; }
            .hero-yazi { max-width: 650px; }

            .vitrin-grid,
            .footer-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .sehir-icerik,
            .sss-duzen {
                grid-template-columns: 1fr;
                gap: 28px;
            }

            .adim { padding: 22px; }

            .davet {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 700px) {
            html { scroll-padding-top: 24px; }

            .ilan-ust {
                position: relative;
                gap: 15px;
                padding: 15px 18px;
            }

            .ilan-menu > a,
            .kategori-baslik {
                padding: 8px;
                font-size: 13px;
            }

            .kapsayici { width: calc(100% - 32px); }
            .hero { padding-top: 20px; }
            .hero-kutu { padding: 24px; border-radius: 20px; }

            .hero h1 {
                font-size: clamp(34px, 7vw, 44px);
                letter-spacing: -1.3px;
            }

            .hero-aciklama { font-size: 15px; }

            .degerler,
            .adim-grid {
                grid-template-columns: 1fr;
            }

            .degerler { gap: 20px; }
            .bolum { padding-block: 42px; }
            .vitrin-bolumu { padding-top: 8px; }
            .kategori-grid { gap: 10px; }

            .kategori-kart {
                padding: 16px 5px;
                gap: 12px;
            }

            .kategori-fotograf {
                width: 64px;
                height: 64px;
                padding: 3px;
            }

            .kategori-kart strong { font-size: 12px; }
            .vitrin-grid { gap: 12px; }
            .ilan-bilgi { padding: 14px; }
            .ilan-bilgi h3 { font-size: 18px; }
            .davet { padding: 26px; margin-bottom: 42px; }
        }

        @media (max-width: 440px) {
            .hero .butonlar {
                align-items: stretch;
                flex-direction: column;
            }

            .sehirler {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 28px;
            }
        }

        @media (max-width: 360px) {
            .kategori-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .vitrin-grid { grid-template-columns: 1fr; }
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }

            *, *::before, *::after {
                transition: none !important;
            }

            .ilan-kart:hover,
            .kategori-kart:hover,
            .buton:hover {
                transform: none;
            }
        }

        .pati-footer .footer-grid {
            grid-template-columns: 1.35fr .9fr .9fr 1.05fr 1.3fr;
            gap: 30px;
            padding-block: 62px;
        }
        .pati-footer .footer-grid > div { min-width: 0; }
        .pati-footer .footer-logo { flex-wrap: wrap; }
        .pati-footer .footer-logo img { width: 64px; height: 64px; }
        .pati-footer h3 { font-size: 17px; margin-bottom: 24px; }
        .pati-footer .footer-linkler { gap: 14px; }
        .pati-footer .footer-linkler a { font-size: 14px; }
        .footer-yuva-band { background: #355c3d; border-bottom: 1px solid #557558; }
        .footer-yuva-icerik { display: flex; align-items: center; justify-content: space-between; gap: 35px; padding-block: 34px; }
        .footer-yuva-icerik h2 { margin: 10px 0; font-size: 28px; color: #fffdf8; }
        .footer-yuva-icerik p { margin: 0; max-width: 700px; color: #dce6d5; line-height: 1.8; font-size: 14px; }
        .footer-kucuk-baslik { color: #dce6d5; font-size: 11px; letter-spacing: 1.5px; font-weight: bold; }
        .footer-ana-buton, .footer-iletisim-buton { display: inline-flex; align-items: center; justify-content: center; gap: 12px; padding: 14px 18px; border-radius: 10px; background: #eee6d2; color: #284b32; font-size: 14px; font-weight: bold; text-decoration: none; }
        .footer-ana-buton { flex-shrink: 0; }
        .footer-marka-sozu { margin: 22px 0 12px; font-size: 14px; font-weight: bold; color: #f1ead8; }
        .footer-hikaye { color: #d4dfcc; font-size: 13px; text-decoration: none; }
        .footer-iletisim-yazi, .footer-rehber-not { color: #d4dfcc; font-size: 13px; line-height: 1.9; margin: 0 0 18px; }
        .footer-rehber-not { margin-top: 24px; padding-top: 16px; border-top: 1px solid #48664b; }
        .footer-bilgi-kutu { background: #35573c; border: 1px solid #527258; border-radius: 14px; padding: 18px; margin-top: 22px; }
        .footer-bilgi-kutu strong { display: block; font-size: 13px; line-height: 1.7; color: #f1ead8; }
        .footer-bilgi-kutu p { color: #d4dfcc; font-size: 12px; line-height: 1.8; margin: 10px 0 0; }
        .footer-sehirler { border-top: 1px solid #48664b; }
        .footer-sehir-icerik { padding-block: 26px; }
        .footer-sehir-icerik > strong { display: block; margin-bottom: 16px; font-size: 14px; }
        .footer-sehir-icerik nav { display: flex; flex-wrap: wrap; gap: 10px; }
        .footer-sehir-icerik a { border: 1px solid #557558; border-radius: 7px; color: #dce6d5; padding: 7px 11px; font-size: 12px; text-decoration: none; }
        .pati-footer a:focus-visible { outline: 2px solid #f1ead8; outline-offset: 4px; }
        .footer-ana-buton:hover, .footer-iletisim-buton:hover { background: #fffdf8; }
        .footer-sehir-icerik a:hover { background: #355c3d; color: white; }
        @media (max-width: 1100px) {
            .pati-footer .footer-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 36px; }
            .footer-yuva-icerik { align-items: flex-start; }
        }
        @media (max-width: 700px) {
            .pati-footer .footer-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 30px 22px; padding-block: 38px; }
            .footer-marka-alani, .footer-iletisim-alani { grid-column: 1 / -1; }
            .footer-yuva-icerik { flex-direction: column; gap: 22px; }
            .footer-yuva-icerik h2 { font-size: 24px; }
        }
        @media (max-width: 440px) {
            .pati-footer .footer-grid { grid-template-columns: 1fr; }
        }
    </style>
<link rel="stylesheet" href="site-ust.css?v=20261102-1">
<link rel="stylesheet" href="site-alt.css?v=20261104-1">
</head>

<body>

<a href="#ana-icerik" class="atla">
    <?= aa_h(aa_t("Ana içeriğe geç", "Skip to main content")) ?>
</a>

<?php require __DIR__ . "/site-ust.php"; ?>

<main id="ana-icerik">

    <?php if (
        $ayar["duyuru_aktif"]
        && trim((string)$ayar["duyuru"]) !== ""
    ): ?>
        <div class="duyuru" lang="tr"><?= aa_h($ayar["duyuru"]) ?></div>
    <?php endif; ?>

    <?php if ($ayar["tanitim_aktif"]): ?>
        <section class="hero kapsayici">
            <div class="hero-kutu">
                <div class="hero-yazi">
                    <span class="etiket">
                        <?= aa_h(aa_t(
                            "Bir yuva, yeni bir başlangıç",
                            "A home, a new beginning"
                        )) ?>
                    </span>

                    <h1><?= aa_h($icerik["baslik"]) ?></h1>

                    <p class="hero-aciklama"><?= aa_h($icerik["aciklama"]) ?></p>

                    <div class="butonlar">
                        <a href="<?= aa_h($buton_linki) ?>" class="buton">
                            <?= aa_h($icerik["buton_yazisi"]) ?>
                            <span aria-hidden="true">↗</span>
                        </a>

                        <?php if ($bolumler["surec"]["aktif"]): ?>
                            <a href="#nasil-sahiplenirim" class="buton ikincil">
                                <?= aa_h(aa_t(
                                    "Nasıl Sahiplenirim?",
                                    "How Do I Adopt?"
                                )) ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <p class="hero-not">
                        <?= aa_h(aa_t(
                            "Küçük bir adım, ömür boyu sürecek bir dostluk.",
                            "One small step towards a lifelong friendship."
                        )) ?>
                    </p>
                </div>

                <div class="hero-gorsel">
                    <img
                        src="<?= aa_h(
                            $kapak_yolu ?: "images/aris-logoo.png"
                        ) ?>"
                        alt="<?= aa_h(aa_t(
                            "Aris Pati Yuvası: kedi ve köpek",
                            "Aris Pati Yuvası: a cat and a dog"
                        )) ?>"
                        class="<?= $kapak_yolu === "" ? "kapak-yok" : "" ?>"
                        fetchpriority="high"
                    >
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($bolumler["degerler"]["aktif"]): ?>
        <div class="kapsayici">
            <div class="degerler">
                <?php foreach ($bolumler["degerler"]["ogeler"] as $oge): ?>
                    <div class="deger">
                        <strong><?= aa_h($oge["baslik"]) ?></strong>
                        <p><?= aa_h($oge["aciklama"]) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($ayar["kategoriler_aktif"]): ?>
        <?php $bolum = $bolumler["kategoriler"]; ?>

        <section class="bolum kapsayici">
            <div class="bolum-baslik">
                <div>
                    <span class="etiket"><?= aa_h($bolum["etiket"]) ?></span>
                    <h2><?= aa_h($bolum["baslik"]) ?></h2>
                </div>
            </div>

            <div class="kategori-grid">
                <?php foreach ($kategoriler as $ad => $dosya): ?>
                    <?php
                    $gorsel = aa_image(
                        $bolum["ogeler"][$ad]["gorsel"]
                    ) ?: aa_image("images/" . $dosya);
                    ?>

                    <a
                        href="ilanlar.php?tur=<?= rawurlencode($ad) ?>"
                        class="kategori-kart"
                    >
                        <img
                            src="<?= aa_h(
                                $gorsel ?: "images/aris-logoo.png"
                            ) ?>"
                            alt=""
                            class="kategori-fotograf"
                            width="90"
                            height="90"
                            loading="lazy"
                        >

                        <strong><?= aa_h(aa_category_name($ad)) ?></strong>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($ayar["vitrin_aktif"] && $vitrin): ?>
        <?php $bolum = $bolumler["vitrin"]; ?>

        <section class="bolum vitrin-bolumu kapsayici">
            <div class="bolum-baslik">
                <div>
                    <span class="etiket"><?= aa_h($bolum["etiket"]) ?></span>
                    <h2><?= aa_h($bolum["baslik"]) ?></h2>
                    <p><?= aa_h($bolum["aciklama"]) ?></p>
                </div>

                <a href="ilanlar.php" class="metin-link">
                    <?= aa_h(aa_t(
                        "Tüm ilanları gör",
                        "View all listings"
                    )) ?> →
                </a>
            </div>

            <div class="vitrin-grid">
                <?php foreach ($vitrin as $ilan): ?>
                    <?php
                    $foto = aa_listing_image($ilan["fotograf"] ?? "")
                        ?: "images/aris-logoo.png";
                    ?>

                    <a
                        href="ilan-detay.php?id=<?= (int)$ilan["id"] ?>"
                        class="ilan-kart"
                    >
                        <div class="ilan-gorsel">
                            <img
                                src="<?= aa_h($foto) ?>"
                                alt="<?= aa_h($ilan["hayvan_adi"]) ?>"
                                class="<?= $foto === "images/aris-logoo.png"
                                    ? "logo-yedek" : "" ?>"
                                loading="lazy"
                                width="400"
                                height="350"
                            >

                            <span class="ilan-rozet">
                                <?= aa_h(aa_t(
                                    "Yuva arıyor",
                                    "Looking for a home"
                                )) ?>
                            </span>
                        </div>

                        <div class="ilan-bilgi">
                            <h3><?= aa_h($ilan["hayvan_adi"]) ?></h3>

                            <p>
                                <?= aa_h(aa_category_name($ilan["tur"])) ?>
                                ·
                                <?= aa_h($ilan["sehir"]) ?>
                            </p>

                            <div class="ilan-alt">
                                <span>
                                    <?= aa_h(aa_t("Benimle tanış", "Meet me")) ?>
                                </span>
                                <span aria-hidden="true">↗</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($bolumler["surec"]["aktif"]): ?>
        <?php $bolum = $bolumler["surec"]; ?>

        <section class="bolum surec" id="nasil-sahiplenirim">
            <div class="kapsayici">
                <div class="surec-baslik">
                    <span class="etiket"><?= aa_h($bolum["etiket"]) ?></span>
                    <h2><?= aa_h($bolum["baslik"]) ?></h2>
                    <p><?= aa_h($bolum["aciklama"]) ?></p>
                </div>

                <div class="adim-grid">
                    <?php foreach ($bolum["ogeler"] as $sira => $oge): ?>
                        <article class="adim">
                            <span class="adim-no">
                                <?= sprintf("%02d", $sira + 1) ?>
                            </span>

                            <h3><?= aa_h($oge["baslik"]) ?></h3>
                            <p><?= aa_h($oge["aciklama"]) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($bolumler["sehir"]["aktif"]): ?>
        <?php $bolum = $bolumler["sehir"]; ?>

        <section class="sehir-alani">
            <div class="kapsayici sehir-icerik">
                <div>
                    <span class="etiket"><?= aa_h($bolum["etiket"]) ?></span>
                    <h2><?= aa_h($bolum["baslik"]) ?></h2>
                    <p><?= aa_h($bolum["aciklama"]) ?></p>
                </div>

                <div>
                    <div class="sehirler">
                        <?php foreach (aa_cities($bolum["liste"]) as $sehir): ?>
                            <a href="ilanlar.php?sehir=<?= rawurlencode($sehir) ?>">
                                <?= aa_h($sehir) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <a href="ilanlar.php" class="sehir-link">
                        <?= aa_h(aa_t(
                            "Tüm şehirlerdeki ilanları incele",
                            "Browse listings in all cities"
                        )) ?> →
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($bolumler["sss"]["aktif"]): ?>
        <?php $bolum = $bolumler["sss"]; ?>

        <section class="bolum kapsayici">
            <div class="sss-duzen">
                <div class="sss-giris">
                    <span class="etiket"><?= aa_h($bolum["etiket"]) ?></span>
                    <h2><?= aa_h($bolum["baslik"]) ?></h2>
                    <p><?= aa_h($bolum["aciklama"]) ?></p>

                    <a href="iletisim.php" class="metin-link">
                        <?= aa_h(aa_t("Bize ulaş", "Contact us")) ?> →
                    </a>
                </div>

                <div class="sss-listesi">
                    <?php foreach ($bolum["ogeler"] as $oge): ?>
                        <?php
                        if (
                            trim($oge["baslik"]) === ""
                            || trim($oge["aciklama"]) === ""
                        ) {
                            continue;
                        }
                        ?>

                        <details>
                            <summary><?= aa_h($oge["baslik"]) ?></summary>
                            <p><?= aa_h($oge["aciklama"]) ?></p>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($bolumler["davet"]["aktif"]): ?>
        <?php
        $bolum = $bolumler["davet"];

        $davet_linki = isset(aa_links()[$bolum["buton_linki"]])
            ? $bolum["buton_linki"]
            : "ilan-ver.php";
        ?>

        <section class="davet kapsayici">
            <div>
                <h2 style="white-space: pre-line"><?= aa_h($bolum["baslik"]) ?></h2>
                <p><?= aa_h($bolum["aciklama"]) ?></p>
            </div>

            <a href="<?= aa_h($davet_linki) ?>" class="buton">
                <?= aa_h($bolum["buton_yazisi"]) ?>
                <span aria-hidden="true">↗</span>
            </a>
        </section>
    <?php endif; ?>

</main>

<?php require __DIR__ . "/site-alt.php"; ?>

</body>
</html>