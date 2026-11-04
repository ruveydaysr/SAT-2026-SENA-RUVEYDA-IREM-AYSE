<?php
session_start();
require_once __DIR__ . "/site-ust-ayar.php";

/* Dil tercihi */
if (
    is_string($_GET["lang"] ?? null)
    && in_array($_GET["lang"], ["tr", "en"], true)
) {
    $_SESSION["dil"] = $_GET["lang"];

    header("Location: hakkimizda.php");
    exit();
}

$dil = in_array($_SESSION["dil"] ?? "", ["tr", "en"], true)
    ? $_SESSION["dil"]
    : "tr";

function guvenli($deger)
{
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

function ceviri($tr, $en)
{
    global $dil;

    return $dil === "en" ? $en : $tr;
}

function tur_adi($tur)
{
    global $dil;

    $adlar = [
        "Kedi" => "Cat",
        "Köpek" => "Dog",
        "Kuş" => "Bird",
        "Balık" => "Fish",
        "Tavşan" => "Rabbit",
        "Kemirgen" => "Rodent",
        "Sürüngen" => "Reptile",
        "Diğer" => "Other"
    ];

    return $dil === "en" ? ($adlar[$tur] ?? $tur) : $tur;
}

$menu_kategorileri = [
    "Kedi" => "🐱",
    "Köpek" => "🐶",
    "Kuş" => "🐦",
    "Balık" => "🐠",
    "Tavşan" => "🐰",
    "Kemirgen" => "🐹",
    "Sürüngen" => "🦎",
    "Diğer" => "🐾"
];

$paragraflar = [
    [
        "Her canlının sevilmeye, korunmaya ve güvenle uyuyabileceği bir yuvaya ihtiyacı olduğuna inanıyoruz.",
        "We believe every living creature needs love, protection and a home where they can sleep safely."
    ],
    [
        "Aris Pati Yuvası, sokakta ya da geçici yuvalarda yaşayan dostlarımızın kendilerini gerçekten sevecek insanlarla buluşabilmesi için oluşturuldu.",
        "Aris Pati Yuvası was created to connect animals living on the streets or in temporary homes with people who will truly love them."
    ],
    [
        "Bazen bir çift meraklı göz, bazen sallanan bir kuyruk, bazen de küçücük bir pati izi hayatımızı güzelleştirmeye yeter. Biz de bu küçük dostlarımızın hayatlarında güzel bir değişime vesile olmak istiyoruz.",
        "Sometimes a pair of curious eyes, a wagging tail or a tiny paw print is enough to brighten our lives. We want to help bring a positive change to the lives of these little companions, too."
    ],
    [
        "Platformumuzda yuva arayan dostlarımızı tanıyabilir, hikâyelerini öğrenebilir ve onlara sıcak bir yuva olmak için ilk adımı atabilirsiniz.",
        "On our platform, you can meet companions looking for a home, discover their stories and take the first step towards giving them a warm home."
    ],
    [
        "Çünkü biliyoruz ki bir hayvanı sahiplenmek sadece ona kapımızı açmak değil; sevgimizi, zamanımızı ve hayatımızı da paylaşmaktır.",
        "We know that adopting an animal means more than opening our door. It means sharing our love, our time and our lives."
    ]
];
?>

<!DOCTYPE html>
<html lang="<?= guvenli($dil) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= guvenli(ceviri(
        "Hakkımızda | Aris Pati Yuvası",
        "About Us | Aris Pati Yuvası"
    )) ?></title>

    <style>
        * {
            box-sizing: border-box;
        }

        html {
            scrollbar-gutter: stable;
        }

        body {
            margin: 0;
            background: #f5f8f5;
            color: #284b32;
            font-family: Arial, sans-serif;
        }

        a:focus-visible,
        button:focus-visible {
            outline: 3px solid #9eb995;
            outline-offset: 3px;
        }

        .ilan-ust {
            position: relative;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 24px;
            min-height: 88px;
            padding: 16px 24px;
            border-bottom: 1px solid #35583b;
            background: #426b46;
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
            background: #fffdf8;
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
            font-size: 11px;
            line-height: 1.4;
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
            gap: 4px;
            padding: 3px;
            border: 1px solid rgba(255,255,255,.35);
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
            background: #284b32;
        }

        .dil-secici a[aria-current="true"] {
            background: #f5f0e6;
            color: #284b32;
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
            gap: 6px;
            min-height: 40px;
            margin: 0;
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
        .ilan-menu > a:focus-visible,
        .kategori-menu:hover .kategori-baslik,
        .kategori-menu:focus-within .kategori-baslik {
            background: #284b32;
        }

        .ilan-menu > a[aria-current="page"] {
            background: #eee5d3;
            color: #284b32;
            font-weight: bold;
        }

        .kategori-menu {
            position: relative;
            flex-shrink: 0;
        }

        .kategori-acilir {
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 200;
            display: none;
            width: 205px;
            max-width: calc(100vw - 36px);
            padding: 8px;
            border: 1px solid #ded6c6;
            border-radius: 12px;
            background: #fffdf8;
            box-shadow: 0 12px 28px rgba(40,75,50,.16);
        }

        .kategori-menu:hover .kategori-acilir,
        .kategori-menu:focus-within .kategori-acilir {
            display: block;
        }

        .kategori-acilir a {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 40px;
            padding: 10px 12px;
            border-radius: 7px;
            color: #284b32;
            font-size: 14px;
            line-height: 20px;
            text-decoration: none;
        }

        .kategori-acilir a:hover,
        .kategori-acilir a:focus-visible {
            background: #426b46;
            color: white;
        }

        .tanitim {
            width: min(800px, 92%);
            margin: 60px auto;
            padding: 40px;
            background: white;
            border: 1px solid #dce8df;
            border-radius: 15px;
        }

        .tanitim h1 {
            margin: 0 0 30px;
            color: #184f3c;
            text-align: center;
            font-size: 28px;
            line-height: 1.4;
        }

        .tanitim p {
            margin: 0 0 20px;
            color: #526b5a;
            font-size: 16px;
            line-height: 1.9;
        }

        .tanitim p:last-child {
            margin-bottom: 0;
            text-align: center;
            color: #347a53;
            font-weight: bold;
        }

        footer {
            padding: 20px;
            color: #687361;
            font-size: 12px;
            line-height: 1.7;
            text-align: center;
        }

        @media (max-width: 1100px) {
            .ust-sag {
                flex-basis: 100%;
            }

            .ilan-menu {
                width: 100%;
            }
        }

        @media (max-width: 700px) {
            .ilan-ust {
                gap: 16px;
                padding: 15px 18px;
            }

            .ilan-menu > a,
            .kategori-baslik {
                padding: 8px;
                font-size: 13px;
            }

            .tanitim {
                margin: 30px auto;
                padding: 25px;
            }

            .tanitim h1 {
                font-size: 25px;
            }

            .tanitim p {
                font-size: 15px;
            }
        }
    </style>
<link rel="stylesheet" href="site-ust.css?v=20261102-1">
<link rel="stylesheet" href="site-alt.css?v=20261104-1">
</head>

<body>

<?php require __DIR__ . "/site-ust.php"; ?>

<main>
    <section class="tanitim">
        <h1>
            <?= guvenli(ceviri(
                "Küçük Patiler, Büyük Umutlar",
                "Little Paws, Big Hopes"
            )) ?> 🐾
        </h1>

        <?php foreach ($paragraflar as [$tr, $en]): ?>
            <p><?= guvenli(ceviri($tr, $en)) ?></p>
        <?php endforeach; ?>

        <p>
            <strong>
                <?= guvenli(ceviri(
                    "Unutmayın, siz bir canın hayatını değiştirirken o da sizin hayatınıza tarifsiz bir sevgi katabilir.",
                    "Remember, as you change an animal's life, they can bring immeasurable love into yours."
                )) ?>
            </strong>
        </p>

        <p>
            <em>
                <?= guvenli(ceviri(
                    "Bir pati, bir umut, bir ömürlük dostluk...",
                    "One paw, one hope, a lifelong friendship..."
                )) ?> 💚
            </em>
        </p>
    </section>
</main>

<?php require __DIR__ . "/site-alt.php"; ?>

</body>
</html>