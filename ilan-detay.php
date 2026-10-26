<?php
session_start();
require_once __DIR__ . "/baglanti.php";

function guvenli($deger)
{
    return htmlspecialchars((string) $deger, ENT_QUOTES, "UTF-8");
}

$admin_mi = isset($_SESSION["admin"]);
$uye_id = (int) ($_SESSION["uye_id"] ?? 0);
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
$ilan = null;

if ($id && $id > 0) {
    $sql = "SELECT i.*, u.ad_soyad AS sahip_adi,
                   u.telefon AS sahip_telefon
            FROM ilanlar i
            LEFT JOIN uyeler u ON u.id = i.uye_id
            WHERE i.id = ?";

    if (!$admin_mi) {
        $sql .= " AND i.durum = 'Onaylandı'";
    }

    $sql .= " LIMIT 1";

    $sorgu = mysqli_prepare($baglanti, $sql);
    mysqli_stmt_bind_param($sorgu, "i", $id);
    mysqli_stmt_execute($sorgu);

    $ilan = mysqli_fetch_assoc(mysqli_stmt_get_result($sorgu));

    mysqli_stmt_close($sorgu);
}

if (!$ilan) {
    http_response_code(404);
}

$baslik = $ilan
    ? (trim((string) ($ilan["ilan_basligi"] ?? "")) ?: $ilan["hayvan_adi"])
    : "İlan Bulunamadı";

$foto_yolu = "";
$temsili = false;
$telefon = "";
$whatsapp = "";
$ozellikler = [];

if ($ilan) {
    $foto = trim((string) ($ilan["fotograf"] ?? ""));
    $dosya = basename($foto);

    if (
        preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$/i', $dosya)
        && is_file(__DIR__ . "/uploads/" . $dosya)
    ) {
        $foto_yolu = "uploads/" . $dosya;
    } elseif (
        preg_match(
            '~^images/hayvanlar/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$~i',
            $foto
        )
        && is_file(__DIR__ . "/" . $foto)
    ) {
        $foto_yolu = $foto;
        $temsili = true;
    } else {
        $temsili_resimler = [
            "Kedi" => "kedi.jpg",
            "Köpek" => "kopek.jpg",
            "Kuş" => "kus.jpg",
            "Balık" => "balik.jpg",
            "Tavşan" => "tavsan.jpg",
            "Kemirgen" => "kemirgen.jpg",
            "Sürüngen" => "surungen.jpg",
            "Diğer" => "diger.jpg"
        ];

        $resim = $temsili_resimler[$ilan["tur"] ?? ""] ?? "";

        if ($resim && is_file(__DIR__ . "/images/hayvanlar/" . $resim)) {
            $foto_yolu = "images/hayvanlar/" . $resim;
            $temsili = true;
        }
    }

    $numara = preg_replace(
        '/\D/',
        '',
        (string) ($ilan["sahip_telefon"] ?? "")
    );

    if (substr($numara, 0, 2) === "00") {
        $numara = substr($numara, 2);
    }

    if (strlen($numara) === 11 && $numara[0] === "0") {
        $numara = "90" . substr($numara, 1);
    } elseif (strlen($numara) === 10 && $numara[0] === "5") {
        $numara = "90" . $numara;
    }

    if (preg_match('/^[1-9][0-9]{7,14}$/', $numara)) {
        $telefon = "+" . $numara;

        $whatsapp = "https://wa.me/" . $numara . "?text=" . rawurlencode(
            "Merhaba, Aris Pati Yuvası sitesindeki "
            . $baslik
            . " başlıklı ilanınız hakkında bilgi almak istiyorum. İlan no: "
            . $ilan["id"]
        );
    }

    $ozellikler = array_filter(
        array_map(
            "trim",
            explode(",", (string) ($ilan["karakteristik_ozellikler"] ?? ""))
        )
    );
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
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= guvenli($baslik) ?> | Aris Pati Yuvası</title>

    <style>
        * {
            box-sizing: border-box;
        }

        html {
            scrollbar-gutter: stable;
        }

        body {
            margin: 0;
            background: #f5f0e6;
            color: #284b32;
            font-family: Arial, sans-serif;
        }

        a {
            color: inherit;
        }

        button,
        textarea {
            font: inherit;
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
            background: #426b46;
            border-bottom: 1px solid #35583b;
        }

        .ilan-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            color: #fff;
            text-decoration: none;
        }

        .ilan-logo img {
            display: block;
            width: 54px;
            height: 54px;
            padding: 5px;
            object-fit: contain;
            background: #fffdf8;
            border: 1px solid #eee5d3;
            border-radius: 12px;
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
            color: #fff;
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
            color: #fff;
        }

        .ilan-menu > a.menu-aktif {
            background: #eee5d3;
            color: #284b32;
            font-weight: bold;
        }

        .ilan-menu > a:focus-visible,
        .kategori-baslik:focus-visible {
            outline: 2px solid #eee5d3;
            outline-offset: 3px;
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
            box-shadow: 0 12px 28px rgba(40, 75, 50, .16);
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
            color: #fff;
        }

        .detay-alani {
            width: min(1180px, 92%);
            margin: 26px auto 45px;
        }

        .yol {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
            color: #727b69;
            font-size: 13px;
            line-height: 1.6;
        }

        .yol a {
            text-decoration: none;
        }

        .yol a:hover {
            text-decoration: underline;
        }

        h1 {
            margin: 0 0 22px;
            font-size: clamp(23px, 3vw, 30px);
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        h2 {
            margin: 0 0 18px;
            font-size: 20px;
            line-height: 1.4;
        }

        .detay-grid {
            display: grid;
            grid-template-columns:
                minmax(0, 1.4fr)
                minmax(0, 1fr)
                minmax(0, 1fr);
            gap: 20px;
            align-items: start;
        }

        .kart {
            min-width: 0;
            overflow: hidden;
            background: #fffdf8;
            border: 1px solid #ded6c6;
            border-radius: 14px;
        }

        .foto-kart img {
            display: block;
            width: 100%;
            height: 380px;
            object-fit: contain;
            background: #eee9df;
        }

        .foto-yok {
            display: grid;
            place-items: center;
            height: 380px;
            background: #eee9df;
            font-size: 65px;
        }

        .foto-not {
            margin: 0;
            padding: 12px;
            color: #727b69;
            font-size: 13px;
            line-height: 1.5;
            text-align: center;
        }

        .bilgi-kart,
        .sahip-kart {
            padding: 20px;
        }

        .bilgi-listesi {
            margin: 0;
        }

        .bilgi-satiri {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            padding: 13px 0;
            border-bottom: 1px dashed #ded6c6;
            font-size: 14px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .bilgi-satiri:first-child {
            padding-top: 0;
        }

        .bilgi-satiri:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .bilgi-satiri dt {
            color: #727b69;
        }

        .bilgi-satiri dd {
            margin: 0;
            font-weight: bold;
        }

        .sahip-simge {
            display: grid;
            place-items: center;
            width: 56px;
            height: 56px;
            margin-bottom: 12px;
            border-radius: 50%;
            background: #e6eddf;
            font-size: 26px;
        }

        .sahip-adi {
            margin: 0 0 10px;
            font-size: 19px;
            overflow-wrap: anywhere;
        }

        .kucuk-metin {
            margin: 10px 0;
            color: #727b69;
            font-size: 13px;
            line-height: 1.7;
        }

        .buton {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 43px;
            margin-top: 12px;
            padding: 11px 14px;
            border: 1px solid #ded6c6;
            border-radius: 8px;
            background: #eee5d3;
            color: #284b32;
            font-size: 14px;
            font-weight: bold;
            line-height: 1.5;
            text-align: center;
            text-decoration: none;
            overflow-wrap: anywhere;
            cursor: pointer;
        }

        .buton:hover {
            filter: brightness(.95);
        }

        .buton:focus-visible {
            outline: 3px solid #9eb995;
            outline-offset: 3px;
        }

        .yesil {
            background: #426b46;
            border-color: #426b46;
            color: #fff;
        }

        .whatsapp {
            background: #168673;
            border-color: #168673;
            color: #fff;
        }

        .kirmizi {
            background: #ae4640;
            border-color: #ae4640;
            color: #fff;
        }

        .aciklama-kart,
        .admin-kart,
        .bulunamadi {
            margin-top: 22px;
            padding: 24px;
        }

        .ozellikler {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
        }

        .ozellik {
            padding: 7px 12px;
            border-radius: 20px;
            background: #e6eddf;
            font-size: 13px;
            line-height: 1.4;
        }

        .metin {
            margin: 0;
            line-height: 1.8;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .admin-kart form {
            margin-top: 18px;
        }

        .admin-kart label {
            display: block;
            font-size: 14px;
            font-weight: bold;
        }

        .admin-kart textarea {
            display: block;
            width: 100%;
            min-height: 100px;
            margin-top: 10px;
            padding: 12px;
            border: 1px solid #ded6c6;
            border-radius: 8px;
            background: #fff;
            color: #284b32;
            resize: vertical;
        }

        .admin-kart textarea:focus {
            outline: 2px solid #9eb995;
            border-color: #426b46;
        }

        .red-nedeni {
            color: #ae4640;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .geri-link {
            display: inline-block;
            margin-top: 24px;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
        }

        .geri-link:hover {
            text-decoration: underline;
        }

        .bulunamadi h1 {
            margin-bottom: 12px;
        }

        .bulunamadi p {
            margin: 0;
            line-height: 1.7;
        }

        .alt-bilgi {
            padding: 20px;
            color: #727b69;
            font-size: 12px;
            line-height: 1.6;
            text-align: center;
        }

        @media (max-width: 1100px) {
            .ilan-menu {
                width: 100%;
                justify-content: flex-end;
            }

            .detay-grid {
                grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr);
            }

            .sahip-kart {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 700px) {
            .ilan-ust {
                padding: 15px 18px;
                gap: 16px;
            }

            .ilan-menu > a,
            .kategori-baslik {
                padding: 8px;
                font-size: 13px;
            }

            .detay-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .sahip-kart {
                grid-column: auto;
            }

            .foto-kart img,
            .foto-yok {
                height: 320px;
            }

            .aciklama-kart,
            .admin-kart,
            .bulunamadi {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<header class="ilan-ust" id="aris-ortak-ust">
    <a class="ilan-logo" href="index.php">
        <img
            src="images/aris-logoo.png"
            alt="Aris Pati Yuvası logosu"
            width="54"
            height="54"
        >
        <span>
            <strong>Aris Pati Yuvası</strong>
            <small>Her can dostuna bir yuva</small>
        </span>
    </a>

    <nav class="ilan-menu" aria-label="Ana menü">
        <a href="index.php">Ana Sayfa</a>
        <a href="ilanlar.php" class="menu-aktif">İlanlar</a>

        <div class="kategori-menu">
            <button
                type="button"
                class="kategori-baslik"
                aria-controls="aris-kategori-listesi"
            >
                Hayvan Kategorileri
                <span aria-hidden="true">▾</span>
            </button>

            <div class="kategori-acilir" id="aris-kategori-listesi">
                <?php foreach ($menu_kategorileri as $kategori_adi => $simge): ?>
                    <a href="ilanlar.php?tur=<?= rawurlencode($kategori_adi) ?>">
                        <span aria-hidden="true"><?= $simge ?></span>
                        <span><?= guvenli($kategori_adi) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <a href="ilan-ver.php">İlan Ver</a>
        <a href="hakkimizda.php">Hakkımızda</a>
        <a href="iletisim.php">İletişim</a>

        <?php if ($uye_id > 0): ?>
            <a href="mesajlar.php">Mesajlarım</a>
            <a href="profil.php">Profilim</a>
            <a
                href="cikis.php"
                onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');"
            >Çıkış Yap</a>
        <?php else: ?>
            <a href="giris.php">Giriş Yap</a>
        <?php endif; ?>

        <?php if ($admin_mi): ?>
            <a href="admin/admin-panel.php">İlan Yönetimi</a>
        <?php endif; ?>
    </nav>
</header>

<main class="detay-alani">
    <?php if ($ilan): ?>
        <nav class="yol" aria-label="Sayfa yolu">
            <a href="index.php">Ana Sayfa</a>
            <span>/</span>
            <a href="ilanlar.php">İlanlar</a>
            <span>/</span>
            <span><?= guvenli($ilan["tur"] ?? "") ?></span>
        </nav>

        <h1><?= guvenli($baslik) ?></h1>

        <div class="detay-grid">
            <section class="kart foto-kart" aria-label="Hayvan fotoğrafı">
                <?php if ($foto_yolu): ?>
                    <img
                        src="<?= guvenli($foto_yolu) ?>"
                        alt="<?= guvenli($ilan["hayvan_adi"] ?? $baslik) ?>"
                    >
                <?php else: ?>
                    <div class="foto-yok" aria-label="Fotoğraf bulunmuyor">🐾</div>
                <?php endif; ?>

                <p class="foto-not">
                    <?php if ($temsili): ?>
                        Bu fotoğraf temsili olarak kullanılmıştır.
                    <?php elseif ($foto_yolu): ?>
                        <?= guvenli($ilan["hayvan_adi"] ?? "") ?>
                    <?php else: ?>
                        Bu ilan için fotoğraf eklenmemiş.
                    <?php endif; ?>
                </p>
            </section>

            <section class="kart bilgi-kart">
                <h2>Hayvan Bilgileri</h2>

                <?php
                $bilgiler = [
                    "İlan No" => $ilan["id"],
                    "Hayvanın Adı" => $ilan["hayvan_adi"] ?? "",
                    "Türü" => $ilan["tur"] ?? "",
                    "Yaşı" => isset($ilan["yas"]) && $ilan["yas"] !== ""
                        ? (int) $ilan["yas"] . " yaş"
                        : "Belirtilmemiş",
                    "Cinsiyeti" => $ilan["cinsiyet"] ?? "",
                    "Şehir" => $ilan["sehir"] ?? "",
                    "İlan Durumu" => $ilan["durum"] ?? ""
                ];
                ?>

                <dl class="bilgi-listesi">
                    <?php foreach ($bilgiler as $etiket => $deger): ?>
                        <div class="bilgi-satiri">
                            <dt><?= guvenli($etiket) ?></dt>
                            <dd>
                                <?= guvenli(
                                    trim((string) $deger) !== ""
                                        ? $deger
                                        : "Belirtilmemiş"
                                ) ?>
                            </dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </section>

            <section class="kart sahip-kart">
                <h2>İlan Sahibi</h2>

                <div class="sahip-simge" aria-hidden="true">👤</div>

                <h3 class="sahip-adi">
                    <?= guvenli(
                        trim((string) ($ilan["sahip_adi"] ?? "")) ?: "İlan sahibi"
                    ) ?>
                </h3>

                <p class="kucuk-metin">
                    Hayvan hakkında bilgi almak için ilan sahibiyle
                    iletişime geçebilirsiniz.
                </p>

                <?php if ($telefon): ?>
                    <a class="buton" href="tel:<?= guvenli($telefon) ?>">
                        ☎ <?= guvenli($telefon) ?>
                    </a>

                    <a
                        class="buton whatsapp"
                        href="<?= guvenli($whatsapp) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >WhatsApp ile İletişim</a>
                <?php else: ?>
                    <p class="kucuk-metin">
                        Geçerli telefon numarası eklenmemiş.
                    </p>
                <?php endif; ?>

                <?php if ($uye_id > 0 && $uye_id === (int) $ilan["uye_id"]): ?>
                    <a class="buton yesil" href="mesajlar.php">
                        ✉ İlan Mesajlarımı Gör
                    </a>
                <?php elseif ($uye_id > 0 && !empty($ilan["sahip_adi"])): ?>
                    <a
                        class="buton yesil"
                        href="mesajlar.php?ilan_id=<?= (int) $ilan["id"] ?>&amp;kisi=<?= (int) $ilan["uye_id"] ?>"
                    >✉ Mesaj Gönder</a>
                <?php elseif (!$uye_id): ?>
                    <a class="buton yesil" href="giris.php">
                        Mesaj Göndermek İçin Giriş Yap
                    </a>
                <?php endif; ?>

                <?php if (!$admin_mi && $uye_id !== (int) $ilan["uye_id"]): ?>
                    <a
                        class="buton"
                        href="sahiplen.php?ilan_id=<?= (int) $ilan["id"] ?>"
                    >🐾 Sahiplenme Başvurusu Yap</a>
                <?php endif; ?>
            </section>
        </div>

        <section class="kart aciklama-kart">
            <h2>İlan Açıklaması</h2>

            <?php if ($ozellikler): ?>
                <div class="ozellikler">
                    <?php foreach ($ozellikler as $ozellik): ?>
                        <span class="ozellik"><?= guvenli($ozellik) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <p class="metin"><?= guvenli(
                trim((string) ($ilan["aciklama"] ?? "")) ?: "Açıklama eklenmemiş."
            ) ?></p>
        </section>

        <?php if ($admin_mi): ?>
            <section class="kart admin-kart">
                <h2>İlan Yönetim İşlemleri</h2>

                <p>
                    İlan durumu:
                    <strong><?= guvenli($ilan["durum"]) ?></strong>
                </p>

                <?php if (!empty($ilan["red_nedeni"])): ?>
                    <p class="red-nedeni">
                        <strong>Red nedeni:</strong>
                        <?= guvenli($ilan["red_nedeni"]) ?>
                    </p>
                <?php endif; ?>

                <?php if ($ilan["durum"] === "Bekliyor"): ?>
                    <form
                        action="admin/onayla.php"
                        method="post"
                        onsubmit="return confirm('Bu ilanı onaylamak istiyor musunuz?');"
                    >
                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int) $ilan["id"] ?>"
                        >
                        <button type="submit" class="buton yesil">
                            İlanı Onayla
                        </button>
                    </form>

                    <form
                        action="admin/reddet.php"
                        method="post"
                        onsubmit="return confirm('Bu ilanı reddetmek istiyor musunuz?');"
                    >
                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int) $ilan["id"] ?>"
                        >

                        <label for="red-nedeni">Red nedeni</label>
                        <textarea
                            id="red-nedeni"
                            name="red_nedeni"
                            rows="3"
                            placeholder="İlanın reddedilme nedenini yazın."
                            required
                        ></textarea>

                        <button type="submit" class="buton kirmizi">
                            İlanı Reddet
                        </button>
                    </form>
                <?php endif; ?>

                <form
                    action="admin/sil.php"
                    method="post"
                    onsubmit="return confirm('Bu ilan kalıcı olarak silinecek. Devam etmek istiyor musunuz?');"
                >
                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $ilan["id"] ?>"
                    >
                    <button type="submit" class="buton kirmizi">
                        İlanı Sil
                    </button>
                </form>
            </section>
        <?php endif; ?>

    <?php else: ?>
        <section class="kart bulunamadi">
            <h1>İlan Bulunamadı</h1>
            <p>Aradığınız ilan silinmiş veya henüz onaylanmamış olabilir.</p>
        </section>
    <?php endif; ?>

    <a
        class="geri-link"
        href="<?= $admin_mi ? 'admin/admin-panel.php' : 'ilanlar.php' ?>"
    >
        ← <?= $admin_mi ? "İlan Yönetimine Dön" : "İlanlara Dön" ?>
    </a>
</main>

<footer class="alt-bilgi">
    © 2026 Aris Pati Yuvası · Hayvan Sahiplendirme Platformu
</footer>

</body>
</html>