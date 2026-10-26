<?php
session_start();
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hakkımızda - Hayvan Sahiplendirme</title>
    <link rel="stylesheet" href="css/style.css">
    
<style>
    body {
        background-color: #f5f8f5;
    }

    .tanitim {
        max-width: 800px;
        margin: 60px auto;
        padding: 40px;
        background-color: white;
        border: 1px solid #dce8df;
        border-radius: 15px;
    }

    .tanitim h2 {
        color: #184f3c;
        text-align: center;
        font-size: 28px;
        margin-bottom: 30px;
    }

    .tanitim p {
        color: #526b5a;
        font-size: 16px;
        line-height: 1.9;
        margin-bottom: 20px;
    }

    .tanitim p:last-child {
        text-align: center;
        color: #347a53;
        font-weight: bold;
    }
    /* SAYFALAR ARASINDA SABİT ÜST MENÜ HİZASI */

html {
    scrollbar-gutter: stable;
}

html body {
    margin: 0;
}

/* Bütün sayfalardaki üst bölüm */
html body > header,
html body > header.ilan-ust,
html body > header.ust,
html body.ay-sayfa > header#anasayfa-ust {
    box-sizing: border-box;
    display: grid;
    grid-template-columns: 250px minmax(0, 1fr);
    align-items: center;
    gap: 16px;
    width: 100%;
    min-height: 88px;
    margin: 0;
    padding: 16px max(24px, calc((100% - 1240px) / 2));
    border-bottom: 1px solid #ded6c6;
    background: #fffdf8;
    text-align: left;
    overflow: visible;
}

/* Logo */
html body > header > a.logo,
html body > header > a.ilan-logo,
html body.ay-sayfa #anasayfa-ust > a.ilan-logo {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 10px;
    width: 250px;
    margin: 0;
    padding: 0;
    background: transparent;
    color: #284b32;
    text-decoration: none;
}

html body > header > a.logo img,
html body > header > a.ilan-logo img,
html body.ay-sayfa #anasayfa-ust .ilan-logo img {
    display: block;
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    margin: 0;
    object-fit: contain;
}

html body > header > a strong {
    display: block;
    margin: 0;
    color: #284b32;
    font: bold 20px/1.3 Arial, sans-serif;
}

html body > header > a small {
    display: block;
    margin: 5px 0 0;
    color: #687361;
    font: normal 11px/1.4 Arial, sans-serif;
}

/* Menü */
html body > header > nav,
html body > header > nav.ilan-menu,
html body.ay-sayfa #anasayfa-ust > nav.ilan-menu {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    flex-wrap: wrap;
    gap: 4px;
    width: 100%;
    margin: 0;
    padding: 0;
    background: transparent;
}

/* Menü bağlantıları ve kategori başlığı */
html body > header > nav > a,
html body > header > nav.ilan-menu > a,
html body.ay-sayfa #anasayfa-ust .ilan-menu > a,
html body.ay-sayfa #anasayfa-ust .kategori-baslik {
    box-sizing: border-box;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    flex: 0 0 auto;
    width: auto;
    min-height: 40px;
    margin: 0;
    padding: 10px;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: #284b32;
    font: normal 14px/20px Arial, sans-serif;
    text-align: center;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
}

html body > header > nav > a:hover,
html body > header > nav > a[aria-current="page"],
html body > header > nav > a.menu-aktif {
    background: #e6eddf;
}

/* Tablet ve telefon */
@media (max-width: 1100px) {
    html body > header,
    html body > header.ilan-ust,
    html body > header.ust,
    html body.ay-sayfa > header#anasayfa-ust {
        grid-template-columns: 1fr;
        justify-items: center;
        gap: 12px;
        padding: 14px 20px;
    }

    html body > header > a.logo,
    html body > header > a.ilan-logo,
    html body.ay-sayfa #anasayfa-ust > a.ilan-logo {
        width: auto;
    }

    html body > header > nav,
    html body > header > nav.ilan-menu,
    html body.ay-sayfa #anasayfa-ust > nav.ilan-menu {
        justify-content: center;
    }
}

@media (max-width: 600px) {
    html body > header > nav > a,
    html body > header > nav.ilan-menu > a,
    html body.ay-sayfa #anasayfa-ust .ilan-menu > a,
    html body.ay-sayfa #anasayfa-ust .kategori-baslik {
        padding: 8px;
        font-size: 13px;
    }
}
html body > header,
html body > header.ilan-ust,
html body > header.ust {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 24px;
    padding: 16px 24px;
}

html body > header > a.logo,
html body > header > a.ilan-logo {
    width: auto;
    flex-shrink: 0;
}

html body > header > nav,
html body > header > nav.ilan-menu {
    width: auto;
    margin-left: auto;
    justify-content: flex-end;
}

@media (max-width: 1100px) {
    html body > header > nav,
    html body > header > nav.ilan-menu {
        width: 100%;
        justify-content: flex-end;
    }
}
/* Üst menüde imlecin geldiği bağlantı */
html body > header > nav > a:hover,
html body > header > nav.ilan-menu > a:hover,
html body > header > nav > a:focus-visible,
html body > header > nav.ilan-menu > a:focus-visible {
    background-color: #426b46;
    color: #ffffff;
    border-radius: 8px;
}
/* Üst şeridin tamamı yeşil */
html body > header,
html body > header.ilan-ust,
html body > header.ust,
html body.ay-sayfa > header#anasayfa-ust {
    background: #426b46 !important;
    border-bottom: 1px solid #35583b !important;
}

/* Logo yanındaki yazılar */
html body > header > a,
html body > header > a strong,
html body > header > a small {
    color: #ffffff !important;
}

/* Menü yazıları */
html body > header nav > a,
html body.ay-sayfa #anasayfa-ust .ilan-menu > a,
html body.ay-sayfa #anasayfa-ust .kategori-baslik {
    background: transparent !important;
    color: #ffffff !important;
}

/* Üzerine gelince koyu yeşil */
html body > header nav > a:hover,
html body > header nav > a:focus-visible,
html body.ay-sayfa #anasayfa-ust .ilan-menu > a:hover,
html body.ay-sayfa #anasayfa-ust .ilan-menu > a:focus-visible,
html body.ay-sayfa #anasayfa-ust .kategori-menu:hover .kategori-baslik,
html body.ay-sayfa #anasayfa-ust .kategori-menu:focus-within .kategori-baslik {
    background: #284b32 !important;
    color: #ffffff !important;
    border-radius: 8px;
}

/* Açık olan sayfanın menü bağlantısı */
html body > header nav > a[aria-current="page"],
html body > header nav > a.menu-aktif,
html body.ay-sayfa #anasayfa-ust .ilan-menu > a[aria-current="page"],
html body.ay-sayfa #anasayfa-ust .ilan-menu > a.menu-aktif {
    background: #eee5d3 !important;
    color: #284b32 !important;
    border-radius: 8px;
    font-weight: bold;
}

/* Logoyu yeşil zeminden ayır */
html body > header > a.ilan-logo img,
html body > header > a.logo img,
html body.ay-sayfa #anasayfa-ust .ilan-logo img {
    box-sizing: border-box;
    width: 54px;
    height: 54px;
    padding: 5px;
    background: #fffdf8;
    border: 1px solid #eee5d3;
    border-radius: 12px;
    object-fit: contain;
}
</style>
<style>
    html {
        scrollbar-gutter: stable;
    }

    #aris-ortak-ust,
    #aris-ortak-ust * {
        box-sizing: border-box;
    }

    html body > header#aris-ortak-ust {
        position: relative;
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 24px;
        width: 100%;
        min-height: 88px;
        margin: 0;
        padding: 16px 24px;
        border-bottom: 1px solid #35583b !important;
        background: #426b46 !important;
        text-align: left;
        overflow: visible;
    }

    #aris-ortak-ust > a.ilan-logo {
        display: inline-flex;
        align-items: center;
        justify-content: flex-start;
        gap: 12px;
        flex-shrink: 0;
        width: auto;
        margin: 0;
        padding: 0;
        background: transparent;
        color: #fff !important;
        text-decoration: none;
    }

    #aris-ortak-ust .ilan-logo img {
        display: block;
        flex-shrink: 0;
        width: 54px;
        height: 54px;
        margin: 0;
        padding: 5px;
        border: 1px solid #eee5d3;
        border-radius: 12px;
        background: #fffdf8;
        object-fit: contain;
    }

    #aris-ortak-ust .ilan-logo strong {
        display: block;
        margin: 0;
        color: #fff !important;
        font: bold 20px/1.3 Arial, sans-serif;
    }

    #aris-ortak-ust .ilan-logo small {
        display: block;
        margin: 5px 0 0;
        color: #fff !important;
        font: normal 11px/1.4 Arial, sans-serif;
    }

    #aris-ortak-ust > nav.ilan-menu {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 4px;
        width: auto;
        margin: 0 0 0 auto;
        padding: 0;
        background: transparent;
    }

    #aris-ortak-ust .ilan-menu > a,
    #aris-ortak-ust .kategori-baslik {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        flex: 0 0 auto;
        width: auto;
        min-height: 40px;
        margin: 0;
        padding: 10px;
        border: 0;
        border-radius: 8px;
        background: transparent !important;
        color: #fff !important;
        font: normal 14px/20px Arial, sans-serif;
        text-align: center;
        text-decoration: none;
        white-space: nowrap;
        cursor: pointer;
    }

    #aris-ortak-ust .ilan-menu > a:hover,
    #aris-ortak-ust .ilan-menu > a:focus-visible,
    #aris-ortak-ust .kategori-menu:hover .kategori-baslik,
    #aris-ortak-ust .kategori-menu:focus-within .kategori-baslik {
        background: #284b32 !important;
        color: #fff !important;
    }

    #aris-ortak-ust .ilan-menu > a[aria-current="page"],
    #aris-ortak-ust .ilan-menu > a.menu-aktif {
        background: #eee5d3 !important;
        color: #284b32 !important;
        font-weight: bold;
    }

    #aris-ortak-ust .ilan-menu > a:focus-visible,
    #aris-ortak-ust .kategori-baslik:focus-visible {
        outline: 2px solid #eee5d3;
        outline-offset: 3px;
    }

    #aris-ortak-ust .kategori-menu {
        position: relative;
        flex-shrink: 0;
        margin: 0;
        padding: 0;
    }

    #aris-ortak-ust .kategori-acilir {
        position: absolute;
        top: 100%;
        right: 0;
        z-index: 200;
        display: none;
        width: 205px;
        max-width: calc(100vw - 36px);
        margin: 0;
        padding: 8px;
        border: 1px solid #ded6c6;
        border-radius: 12px;
        background: #fffdf8;
        box-shadow: 0 12px 28px rgba(40, 75, 50, .16);
    }

    #aris-ortak-ust .kategori-menu:hover .kategori-acilir,
    #aris-ortak-ust .kategori-menu:focus-within .kategori-acilir {
        display: block;
    }

    #aris-ortak-ust .kategori-acilir a {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 10px;
        width: 100%;
        min-height: 40px;
        margin: 0;
        padding: 10px 12px;
        border: 0;
        border-radius: 7px;
        background: transparent !important;
        color: #284b32 !important;
        font: normal 14px/20px Arial, sans-serif;
        text-align: left;
        text-decoration: none;
        white-space: nowrap;
    }

    #aris-ortak-ust .kategori-acilir a:hover,
    #aris-ortak-ust .kategori-acilir a:focus-visible {
        background: #426b46 !important;
        color: #fff !important;
    }

    @media (max-width: 1100px) {
        #aris-ortak-ust > nav.ilan-menu {
            width: 100%;
            justify-content: flex-end;
        }
    }

    @media (max-width: 700px) {
        html body > header#aris-ortak-ust {
            gap: 16px;
            padding: 15px 18px;
        }

        #aris-ortak-ust .ilan-menu > a,
        #aris-ortak-ust .kategori-baslik {
            padding: 8px;
            font-size: 13px;
        }
    }
</style>
</head>

<body>

<?php
$aktif_sayfa = basename($_SERVER["SCRIPT_NAME"] ?? "");

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

$menu_baglantilari = [
    "ilan-ver.php" => "İlan Ver",
    "hakkimizda.php" => "Hakkımızda",
    "iletisim.php" => "İletişim"
];
?>

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
        <a
            href="index.php"
            <?= $aktif_sayfa === "index.php" ? 'aria-current="page"' : "" ?>
        >Ana Sayfa</a>

        <a
            href="ilanlar.php"
            <?= $aktif_sayfa === "ilanlar.php" ? 'aria-current="page"' : "" ?>
            <?= $aktif_sayfa === "ilan-detay.php" ? 'class="menu-aktif"' : "" ?>
        >İlanlar</a>

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
                <?php foreach ($menu_kategorileri as $kategori_adi => $kategori_simge): ?>
                    <a href="ilanlar.php?tur=<?= rawurlencode($kategori_adi) ?>">
                        <span aria-hidden="true"><?= $kategori_simge ?></span>
                        <span><?= htmlspecialchars(
                            $kategori_adi,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php foreach ($menu_baglantilari as $menu_dosyasi => $menu_yazisi): ?>
            <a
                href="<?= htmlspecialchars($menu_dosyasi, ENT_QUOTES, "UTF-8") ?>"
                <?= $aktif_sayfa === $menu_dosyasi ? 'aria-current="page"' : "" ?>
            ><?= htmlspecialchars($menu_yazisi, ENT_QUOTES, "UTF-8") ?></a>
        <?php endforeach; ?>

        <?php if ((int) ($_SESSION["uye_id"] ?? 0) > 0): ?>
            <a
                href="mesajlar.php"
                <?= $aktif_sayfa === "mesajlar.php" ? 'aria-current="page"' : "" ?>
            >Mesajlarım</a>

            <a
                href="profil.php"
                <?= $aktif_sayfa === "profil.php" ? 'aria-current="page"' : "" ?>
            >Profilim</a>

            <a
                href="cikis.php"
                onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');"
            >Çıkış Yap</a>
        <?php else: ?>
            <a
                href="giris.php"
                <?= $aktif_sayfa === "giris.php" ? 'aria-current="page"' : "" ?>
            >Giriş Yap</a>
        <?php endif; ?>

        <?php if (
            isset($_SESSION["admin"])
            && $aktif_sayfa === "ilan-detay.php"
        ): ?>
            <a href="admin/admin-panel.php">İlan Yönetimi</a>
        <?php endif; ?>
    </nav>
</header>

    <main>
        
<section class="tanitim">
    <h2>Küçük Patiler, Büyük Umutlar 🐾</h2>

    <p>Her canlının sevilmeye, korunmaya ve güvenle uyuyabileceği bir yuvaya ihtiyacı olduğuna inanıyoruz.</p>

    <p>Aris Pati Yuvası, sokakta ya da geçici yuvalarda yaşayan dostlarımızın kendilerini gerçekten sevecek insanlarla buluşabilmesi için oluşturuldu.</p>

    <p>Bazen bir çift meraklı göz, bazen sallanan bir kuyruk, bazen de küçücük bir pati izi hayatımızı güzelleştirmeye yeter. Biz de bu küçük dostlarımızın hayatlarında güzel bir değişime vesile olmak istiyoruz.</p>

    <p>Platformumuzda yuva arayan dostlarımızı tanıyabilir, hikâyelerini öğrenebilir ve onlara sıcak bir yuva olmak için ilk adımı atabilirsiniz.</p>

    <p>Çünkü biliyoruz ki bir hayvanı sahiplenmek sadece ona kapımızı açmak değil; sevgimizi, zamanımızı ve hayatımızı da paylaşmaktır.</p>

    <p><strong>Unutmayın, siz bir canın hayatını değiştirirken o da sizin hayatınıza tarifsiz bir sevgi katabilir.</strong></p>

    <p><em>Bir pati, bir umut, bir ömürlük dostluk... 💚</em></p>
</section>

    </main>

    <footer>
        <p>Hayvan Sahiplendirme Projesi</p>
    </footer>

    <script src="js/script.js"></script>

</body>
</html>