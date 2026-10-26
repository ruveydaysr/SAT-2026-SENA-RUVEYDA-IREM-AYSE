<?php
session_start();

if (!isset($_SESSION["uye_id"])) {
    header("Location: giris.php");
    exit;
}

require_once __DIR__ . "/baglanti.php";

function guvenli($deger)
{
    return htmlspecialchars((string) $deger, ENT_QUOTES, "UTF-8");
}

function sorgula($baglanti, $sql, $tipler, ...$degerler)
{
    $sorgu = mysqli_prepare($baglanti, $sql);

    if (!$sorgu) {
        throw new RuntimeException("Sorgu hazırlanamadı.");
    }

    try {
        mysqli_stmt_bind_param($sorgu, $tipler, ...$degerler);

        if (!mysqli_stmt_execute($sorgu)) {
            throw new RuntimeException("Sorgu çalıştırılamadı.");
        }

        return mysqli_stmt_get_result($sorgu);
    } finally {
        mysqli_stmt_close($sorgu);
    }
}

$ben = (int) $_SESSION["uye_id"];
$ilan_id = filter_input(INPUT_GET, "ilan_id", FILTER_VALIDATE_INT);
$kisi = filter_input(INPUT_GET, "kisi", FILTER_VALIDATE_INT);

$hata = "";
$sohbet = null;
$mesajlar = [];
$metin = "";

if (empty($_SESSION["sohbet_token"])) {
    $_SESSION["sohbet_token"] = bin2hex(random_bytes(32));
}

/* Sohbet yetkisi */

if (
    $ilan_id && $ilan_id > 0
    && $kisi && $kisi > 0
    && $kisi !== $ben
) {
    $sonuc = sorgula(
        $baglanti,
        "SELECT i.id, i.uye_id, i.ilan_basligi,
                i.hayvan_adi, i.durum,
                u.ad_soyad AS kisi_adi
         FROM ilanlar i
         JOIN uyeler u ON u.id = ?
         WHERE i.id = ?
         LIMIT 1",
        "ii",
        $kisi,
        $ilan_id
    );

    $aday = mysqli_fetch_assoc($sonuc);

    if ($aday) {
        $sahip = (int) $aday["uye_id"];

        $ilgili_uye = $kisi === $sahip && $ben !== $sahip;
        $sahip_yaniti = false;

        if ($ben === $sahip) {
            $kontrol = sorgula(
                $baglanti,
                "SELECT id
                 FROM ilan_mesajlari
                 WHERE ilan_id = ?
                   AND gonderen_id = ?
                   AND alici_id = ?
                 LIMIT 1",
                "iii",
                $ilan_id,
                $kisi,
                $ben
            );

            $sahip_yaniti = mysqli_num_rows($kontrol) > 0;
        }

        if ($ilgili_uye || $sahip_yaniti) {
            $sohbet = $aday;
        }
    }

    if (!$sohbet) {
        $hata = "Bu sohbet bulunamadı veya bu kişiye mesaj gönderemezsiniz.";
    }
}

/* Mesaj gönderme */

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["token"] ?? "";

    $metin = is_string($_POST["mesaj"] ?? null)
        ? trim($_POST["mesaj"])
        : "";

    if (!$sohbet) {
        $hata = "Geçerli bir sohbet seçiniz.";
    } elseif (
        !is_string($token)
        || !hash_equals($_SESSION["sohbet_token"], $token)
    ) {
        $hata = "Sayfayı yenileyip tekrar deneyiniz.";
    } elseif ($sohbet["durum"] !== "Onaylandı") {
        $hata = "İlan yayında olmadığı için yeni mesaj gönderilemiyor.";
    } elseif (
        $metin === ""
        || mb_strlen($metin, "UTF-8") > 5000
    ) {
        $hata = "Mesajınız 1 ile 5000 karakter arasında olmalıdır.";
    } else {
        try {
            sorgula(
                $baglanti,
                "INSERT INTO ilan_mesajlari
                    (ilan_id, gonderen_id, alici_id, mesaj)
                 VALUES (?, ?, ?, ?)",
                "iiis",
                $ilan_id,
                $ben,
                $kisi,
                $metin
            );

            $_SESSION["sohbet_token"] = bin2hex(random_bytes(32));

            header(
                "Location: mesajlar.php?ilan_id=" . $ilan_id
                . "&kisi=" . $kisi . "#son"
            );
            exit;
        } catch (mysqli_sql_exception | RuntimeException $e) {
            $hata = "Mesaj gönderilemedi. Lütfen tekrar deneyiniz.";
        }
    }
}

/* Mesajları getir ve gösterilen gelen mesajları okundu yap */

if ($sohbet) {
    $sonuc = sorgula(
        $baglanti,
        "SELECT id, gonderen_id, mesaj, tarih
         FROM ilan_mesajlari
         WHERE ilan_id = ?
           AND (
               (gonderen_id = ? AND alici_id = ?)
               OR
               (gonderen_id = ? AND alici_id = ?)
           )
         ORDER BY id DESC
         LIMIT 200",
        "iiiii",
        $ilan_id,
        $ben,
        $kisi,
        $kisi,
        $ben
    );

    $mesajlar = array_reverse(
        mysqli_fetch_all($sonuc, MYSQLI_ASSOC)
    );

    if ($mesajlar) {
        $ilk_gosterilen = (int) $mesajlar[0]["id"];
        $son_gosterilen = (int) $mesajlar[count($mesajlar) - 1]["id"];

        sorgula(
            $baglanti,
            "UPDATE ilan_mesajlari
             SET okundu = 1
             WHERE ilan_id = ?
               AND gonderen_id = ?
               AND alici_id = ?
               AND id BETWEEN ? AND ?",
            "iiiii",
            $ilan_id,
            $kisi,
            $ben,
            $ilk_gosterilen,
            $son_gosterilen
        );
    }
}

/* Okundu işlemi bittikten sonra bildirim durumunu hesapla */

$bildirim_sonuc = sorgula(
    $baglanti,
    "SELECT id
     FROM ilan_mesajlari
     WHERE alici_id = ? AND okundu = 0
     LIMIT 1",
    "i",
    $ben
);

$okunmamis_mesaj_var = mysqli_num_rows($bildirim_sonuc) > 0;

/* Sohbet listesi */

$liste_sonuc = sorgula(
    $baglanti,
    "SELECT m.*, i.ilan_basligi, i.hayvan_adi,
            u.id AS kisi_id, u.ad_soyad AS kisi_adi,
            (
                SELECT COUNT(*)
                FROM ilan_mesajlari yeni
                WHERE yeni.ilan_id = m.ilan_id
                  AND yeni.gonderen_id = u.id
                  AND yeni.alici_id = ?
                  AND yeni.okundu = 0
            ) AS okunmamis
     FROM ilan_mesajlari m
     JOIN (
         SELECT MAX(id) AS son_id
         FROM ilan_mesajlari
         WHERE gonderen_id = ? OR alici_id = ?
         GROUP BY ilan_id,
                  LEAST(gonderen_id, alici_id),
                  GREATEST(gonderen_id, alici_id)
     ) son ON son.son_id = m.id
     JOIN ilanlar i ON i.id = m.ilan_id
     JOIN uyeler u
       ON u.id = CASE
           WHEN m.gonderen_id = ?
           THEN m.alici_id
           ELSE m.gonderen_id
       END
     ORDER BY m.id DESC",
    "iiii",
    $ben,
    $ben,
    $ben,
    $ben
);

$sohbetler = mysqli_fetch_all($liste_sonuc, MYSQLI_ASSOC);

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
    <title>Mesajlarım | Aris Pati Yuvası</title>

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
            color: #284b32;
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

        .ilan-menu > a[aria-current="page"] {
            background: #eee5d3;
            color: #284b32;
            font-weight: bold;
        }

        .mesaj-bildirim {
            display: inline-block;
            flex-shrink: 0;
            width: 9px;
            height: 9px;
            border: 1px solid #fff;
            border-radius: 50%;
            background: #ef5350;
            vertical-align: middle;
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

        main {
            width: min(960px, 92%);
            margin: 28px auto;
        }

        h1 {
            margin: 0 0 18px;
            font-size: 25px;
        }

        .duzen {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            align-items: start;
            gap: 16px;
        }

        .liste,
        .sohbet {
            min-width: 0;
            overflow: hidden;
            border: 1px solid #ded6c6;
            border-radius: 12px;
            background: #fffdf8;
        }

        .liste {
            max-height: 520px;
            overflow-y: auto;
        }

        .liste-baslik {
            margin: 0;
            padding: 15px 16px;
            border-bottom: 1px solid #ded6c6;
            font-size: 16px;
        }

        .sohbet-link {
            display: block;
            padding: 14px 16px;
            border-bottom: 1px solid #e6e0d4;
            font-size: 14px;
            text-decoration: none;
            overflow-wrap: anywhere;
        }

        .sohbet-link:last-child {
            border-bottom: 0;
        }

        .sohbet-link:hover,
        .sohbet-link.aktif {
            background: #e6eddf;
        }

        .sohbet-kisi {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .sohbet-link small {
            display: block;
            margin-top: 6px;
            color: #687361;
            font-size: 12px;
            line-height: 1.5;
        }

        .sohbet-baslik {
            padding: 15px 18px;
            border-bottom: 1px solid #ded6c6;
            overflow-wrap: anywhere;
        }

        .sohbet-baslik h2 {
            margin: 0 0 7px;
            font-size: 18px;
        }

        .sohbet-baslik a {
            font-size: 13px;
            line-height: 1.5;
        }

        .akis {
            display: flex;
            flex-direction: column;
            gap: 10px;
            height: 320px;
            padding: 16px;
            overflow-y: auto;
        }

        .balon {
            align-self: flex-start;
            flex-shrink: 0;
            max-width: 85%;
            padding: 10px 13px;
            border-radius: 12px;
            background: #eee5d3;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .balon.ben {
            align-self: flex-end;
            background: #dcebd8;
        }

        .balon p {
            margin: 0;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .balon time {
            display: block;
            margin-top: 7px;
            color: #687361;
            font-size: 11px;
        }

        .yaz {
            padding: 14px 16px;
            border-top: 1px solid #ded6c6;
        }

        .yaz label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: bold;
        }

        .yaz textarea {
            display: block;
            width: 100%;
            min-height: 75px;
            padding: 10px;
            border: 1px solid #ded6c6;
            border-radius: 8px;
            background: #fff;
            color: #284b32;
            font: inherit;
            font-size: 14px;
            resize: vertical;
        }

        .yaz button {
            display: block;
            margin: 10px 0 0 auto;
            padding: 11px 20px;
            border: 0;
            border-radius: 8px;
            background: #426b46;
            color: #fff;
            font: inherit;
            font-size: 14px;
            cursor: pointer;
        }

        .yaz button:hover {
            background: #284b32;
        }

        .bos {
            margin: 0;
            padding: 20px;
            color: #687361;
            font-size: 14px;
            line-height: 1.65;
        }

        .hata {
            margin-bottom: 18px;
            padding: 14px;
            border-radius: 8px;
            background: #fae5df;
            color: #8f3029;
            font-size: 14px;
            line-height: 1.6;
        }

        .alt-not {
            margin-top: 14px;
            color: #687361;
            font-size: 12px;
            line-height: 1.6;
        }

        footer {
            padding: 10px 20px 24px;
            color: #687361;
            font-size: 12px;
            line-height: 1.6;
            text-align: center;
        }

        a:focus-visible,
        button:focus-visible,
        textarea:focus-visible {
            outline: 2px solid #284b32;
            outline-offset: 3px;
        }

        .ilan-menu > a:focus-visible,
        .kategori-baslik:focus-visible {
            outline-color: #eee5d3;
        }

        @media (max-width: 1100px) {
            .ilan-menu {
                width: 100%;
            }
        }

        @media (max-width: 750px) {
            .duzen {
                grid-template-columns: 1fr;
            }

            .liste {
                max-height: 220px;
            }

            .akis {
                height: 300px;
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
        }
    </style>
</head>
<body>

<header class="ilan-ust" id="aris-ortak-ust">
    <a href="index.php" class="ilan-logo">
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
        <a href="ilanlar.php">İlanlar</a>

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

        <a href="mesajlar.php" aria-current="page">
            Mesajlarım

            <?php if ($okunmamis_mesaj_var): ?>
                <span
                    class="mesaj-bildirim"
                    role="img"
                    aria-label="Okunmamış mesajınız var"
                ></span>
            <?php endif; ?>
        </a>

        <a href="profil.php">Profilim</a>

        <a
            href="cikis.php"
            onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');"
        >Çıkış Yap</a>
    </nav>
</header>

<main>
    <h1>Mesajlarım</h1>

    <?php if ($hata !== ""): ?>
        <div class="hata" role="alert">
            <?= guvenli($hata) ?>
        </div>
    <?php endif; ?>

    <div class="duzen">
        <aside class="liste" aria-label="Sohbetler">
            <h2 class="liste-baslik">Sohbetler</h2>

            <?php if (!$sohbetler): ?>
                <p class="bos">
                    Henüz mesajınız yok. İlan detayındaki
                    “Mesaj Gönder” seçeneğiyle sohbet başlatabilirsiniz.
                </p>
            <?php endif; ?>

            <?php foreach ($sohbetler as $satir): ?>
                <?php
                $secili = $ilan_id === (int) $satir["ilan_id"]
                    && $kisi === (int) $satir["kisi_id"];
                ?>

                <a
                    class="sohbet-link <?= $secili ? 'aktif' : '' ?>"
                    href="mesajlar.php?ilan_id=<?= (int) $satir["ilan_id"] ?>&amp;kisi=<?= (int) $satir["kisi_id"] ?>"
                    <?= $secili ? 'aria-current="true"' : "" ?>
                >
                    <span class="sohbet-kisi">
                        <strong><?= guvenli($satir["kisi_adi"]) ?></strong>

                        <?php if ((int) $satir["okunmamis"] > 0): ?>
                            <span
                                class="mesaj-bildirim"
                                role="img"
                                aria-label="Bu sohbette okunmamış mesaj var"
                            ></span>
                        <?php endif; ?>
                    </span>

                    <small><?= guvenli(
                        $satir["ilan_basligi"] ?: $satir["hayvan_adi"]
                    ) ?></small>

                    <small><?= guvenli(
                        mb_strimwidth(
                            $satir["mesaj"],
                            0,
                            65,
                            "...",
                            "UTF-8"
                        )
                    ) ?></small>
                </a>
            <?php endforeach; ?>
        </aside>

        <section class="sohbet" aria-label="Mesaj alanı">
            <?php if ($sohbet): ?>
                <div class="sohbet-baslik">
                    <h2><?= guvenli($sohbet["kisi_adi"]) ?></h2>

                    <a href="ilan-detay.php?id=<?= (int) $ilan_id ?>">
                        <?= guvenli(
                            $sohbet["ilan_basligi"] ?: $sohbet["hayvan_adi"]
                        ) ?>
                    </a>
                </div>

                <div class="akis" id="mesaj-akisi" aria-label="Sohbet mesajları">
                    <?php if (!$mesajlar): ?>
                        <p class="bos">
                            İlan hakkında ilk mesajınızı yazabilirsiniz.
                        </p>
                    <?php endif; ?>

                    <?php foreach ($mesajlar as $satir): ?>
                        <div class="balon <?= (int) $satir["gonderen_id"] === $ben ? 'ben' : '' ?>">
                            <p><?= guvenli($satir["mesaj"]) ?></p>

                            <time><?= guvenli(
                                date("d.m.Y H:i", strtotime($satir["tarih"]))
                            ) ?></time>
                        </div>
                    <?php endforeach; ?>

                    <div id="son"></div>
                </div>

                <?php if ($sohbet["durum"] === "Onaylandı"): ?>
                    <form
                        class="yaz"
                        method="post"
                        action="mesajlar.php?ilan_id=<?= (int) $ilan_id ?>&amp;kisi=<?= (int) $kisi ?>"
                    >
                        <input
                            type="hidden"
                            name="token"
                            value="<?= guvenli($_SESSION["sohbet_token"]) ?>"
                        >

                        <label for="mesaj">Mesajınız</label>

                        <textarea
                            id="mesaj"
                            name="mesaj"
                            maxlength="5000"
                            rows="3"
                            placeholder="Mesajınızı yazınız..."
                            required
                        ><?= guvenli($metin) ?></textarea>

                        <button type="submit">Mesajı Gönder</button>
                    </form>
                <?php else: ?>
                    <p class="bos">
                        İlan yayında olmadığı için yeni mesaj gönderilemiyor.
                    </p>
                <?php endif; ?>

            <?php else: ?>
                <p class="bos">
                    Mesajları okumak için soldan bir sohbet seçiniz.
                </p>
            <?php endif; ?>
        </section>
    </div>

    <p class="alt-not">
        Yeni mesajları görmek için sayfayı yenileyebilirsiniz.
        Seçili sohbette son 200 mesaj gösterilir.
    </p>
</main>

<footer>
    © 2026 Aris Pati Yuvası · Hayvan Sahiplendirme Platformu
</footer>

<script>
const mesajAkisi = document.getElementById("mesaj-akisi");

if (mesajAkisi) {
    mesajAkisi.scrollTop = mesajAkisi.scrollHeight;
}
</script>

</body>
</html>