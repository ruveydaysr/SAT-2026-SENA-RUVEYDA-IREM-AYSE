<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hayvan İlanları</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>Hayvan Sahiplendirme Sistemi</h1>

    <nav>
        <a href="index.php">Ana Sayfa</a>
        <a href="ilanlar.php">Hayvan Ara</a>
        <a href="kayip.html">Kayıp Hayvanlar</a>
        <a href="ilan-ekle.html">İlan Ver</a>
        <a href="giris.php">Giriş Yap</a>
    </nav>
</header>

<main>

    <section class="ilan-baslik">
        <h2>Yuva Arayan Hayvanlar</h2>
        <p>Sana uygun dostunu bulmak için filtreleri kullanabilirsin.</p>
    </section>

    <!-- FİLTRELEME ALANI -->
    <section class="filtre-alani">

        <h3>Hayvan Ara</h3>

        <div class="filtreler">

            <div>
                <label for="tur">Hayvan Türü</label>
                <select id="tur">
                    <option value="">Tümü</option>
                    <option value="kedi">Kedi</option>
                    <option value="kopek">Köpek</option>
                    <option value="kus">Kuş</option>
                    <option value="tavsan">Tavşan</option>
                    <option value="balik">Balık</option>
                </select>
            </div>

            <div>
                <label for="sehir">Şehir</label>
                <select id="sehir">
                    <option value="">Tümü</option>
                    <option value="yozgat">Yozgat</option>
                    <option value="ankara">Ankara</option>
                    <option value="istanbul">İstanbul</option>
                    <option value="kayseri">Kayseri</option>
                </select>
            </div>

            <div>
                <label for="yas">Yaş</label>
                <select id="yas">
                    <option value="">Tümü</option>
                    <option value="yavru">1 yaşından küçük</option>
                    <option value="genc">1 - 3 yaş</option>
                    <option value="yetiskin">3 yaş ve üzeri</option>
                </select>
            </div>

            <div>
                <label for="cinsiyet">Cinsiyet</label>
                <select id="cinsiyet">
                    <option value="">Tümü</option>
                    <option value="disi">Dişi</option>
                    <option value="erkek">Erkek</option>
                </select>
            </div>

            <button type="button">Filtrele</button>

        </div>

    </section>

    <!-- İLANLAR -->
    <section class="ilanlar">

        <div class="ilan-karti">
            <div class="ilan-bilgi">
                <h3>Pamuk</h3>
                <p><strong>Tür:</strong> Kedi</p>
                <p><strong>Yaş:</strong> 8 Aylık</p>
                <p><strong>Cinsiyet:</strong> Dişi</p>
                <p><strong>Şehir:</strong> Yozgat</p>
                <a href="ilan-detay.php" class="detay-buton">İlanı İncele</a>
            </div>
        </div>

        <div class="ilan-karti">
            <div class="ilan-bilgi">
                <h3>Karabaş</h3>
                <p><strong>Tür:</strong> Köpek</p>
                <p><strong>Yaş:</strong> 2 Yaş</p>
                <p><strong>Cinsiyet:</strong> Erkek</p>
                <p><strong>Şehir:</strong> Ankara</p>
                <a href="ilan-detay.html" class="detay-buton">İlanı İncele</a>
            </div>
        </div>

        <div class="ilan-karti">
            <div class="ilan-bilgi">
                <h3>Limon</h3>
                <p><strong>Tür:</strong> Kuş</p>
                <p><strong>Yaş:</strong> 1 Yaş</p>
                <p><strong>Cinsiyet:</strong> Erkek</p>
                <p><strong>Şehir:</strong> İstanbul</p>
                <a href="ilan-detay.html" class="detay-buton">İlanı İncele</a>
            </div>
        </div>

        <div class="ilan-karti">
            <div class="ilan-bilgi">
                <h3>Minnoş</h3>
                <p><strong>Tür:</strong> Tavşan</p>
                <p><strong>Yaş:</strong> 6 Aylık</p>
                <p><strong>Cinsiyet:</strong> Dişi</p>
                <p><strong>Şehir:</strong> Kayseri</p>
                <a href="ilan-detay.html" class="detay-buton">İlanı İncele</a>
            </div>
        </div>

        <div class="ilan-karti">
            <div class="ilan-bilgi">
                <h3>Mavi</h3>
                <p><strong>Tür:</strong> Balık</p>
                <p><strong>Yaş:</strong> 5 Aylık</p>
                <p><strong>Cinsiyet:</strong> Erkek</p>
                <p><strong>Şehir:</strong> Yozgat</p>
                <a href="ilan-detay.html" class="detay-buton">İlanı İncele</a>
            </div>
        </div>

    </section>

</main>

<footer>
    <p>Hayvan Sahiplendirme Sistemi - 2026</p>
</footer>

</body>
</html>