const adAlani = document.querySelector('#adsoyad, #ad_soyad');
adAlani?.addEventListener('input', () => {
    const temizAd = adAlani.value.replace(/\p{N}/gu, '');
    if (adAlani.value !== temizAd) {
        adAlani.value = temizAd;
    }
});

const telefonAlani = document.getElementById('telefon');
const ulkeKodu = document.getElementById('telefon-kodu');

const kodGorunumu = document.getElementById('telefon-kodu-gorunum');

if (telefonAlani && ulkeKodu && kodGorunumu) {
    function telefonIpucunuGuncelle() {
        kodGorunumu.textContent = ulkeKodu.value === 'diger' ? '+' : ulkeKodu.value;
        telefonAlani.placeholder = {
            '+90': '05XXXXXXXXX',
            '+1': '202 555 0123',
            diger: '+61 412 345 678',
        }[ulkeKodu.value] || '';
    }

    ulkeKodu.addEventListener('change', () => {
        if (telefonAlani.value === '' || telefonAlani.value === '0') {
            telefonAlani.value = ulkeKodu.value === '+90' ? '0' : '';
        }
        telefonIpucunuGuncelle();
    });

    telefonAlani.addEventListener('input', () => {
        if (ulkeKodu.value === '+90' && telefonAlani.value.startsWith('5')) {
            telefonAlani.value = '0' + telefonAlani.value;
        }
    });

    telefonIpucunuGuncelle();
}
