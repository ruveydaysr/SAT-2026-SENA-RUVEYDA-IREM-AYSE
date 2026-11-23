<?php
[$secili_kod, $telefon_degeri] = uye_telefon_form_degerleri($telefon, $telefon_kodu);
if ($secili_kod !== "+90" && $telefon_degeri === "0") {
    $telefon_degeri = "";
}
?>
<label for="telefon"><?= guvenli(ceviri("Telefon Numarası", "Phone Number")) ?></label>
<div class="uye-telefon">
    <div class="uye-telefon-kod">
        <span id="telefon-kodu-gorunum" aria-hidden="true"><?= guvenli($secili_kod === "diger" ? "+" : $secili_kod) ?></span>
        <span class="uye-telefon-ok" aria-hidden="true"></span>
        <select id="telefon-kodu" name="telefon_kodu" aria-label="<?= guvenli(ceviri("Ülke Kodu", "Country Code")) ?>">
            <?php foreach (uye_ulke_kodlari() as $kod => [$tr, $en]): ?>
                <option value="<?= guvenli($kod) ?>" <?= $secili_kod === $kod ? "selected" : "" ?>>
                    <?= guvenli(ceviri($tr, $en) . " (" . $kod . ")") ?>
                </option>
            <?php endforeach; ?>
            <option value="diger" <?= $secili_kod === "diger" ? "selected" : "" ?>>
                <?= guvenli(ceviri("Diğer ülke", "Other country")) ?>
            </option>
        </select>
    </div>
    <input type="tel" id="telefon" name="telefon" autocomplete="tel-national"
           maxlength="32" value="<?= guvenli($telefon_degeri) ?>"
           aria-describedby="telefon-aciklama" required>
</div>
<small class="alan-notu" id="telefon-aciklama">
    <?= guvenli(ceviri(
        "Diğer ülke seçiliyse numarayı + ve ülke koduyla yazın.",
        "For Other country, enter the full number starting with + and the country code."
    )) ?>
</small>
