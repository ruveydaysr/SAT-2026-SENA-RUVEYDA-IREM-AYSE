<?php

function uye_telefon_duzenle(string $telefon): ?string
{
    $telefon = preg_replace('/[\s().-]+/u', '', trim($telefon));

    if ($telefon === null) {
        return null;
    }
    if (preg_match('/\A0?5[0-9]{9}\z/', $telefon)) {
        return "0" . substr($telefon, -10);
    }
    if (strpos($telefon, "+90") === 0) {
        return preg_match('/\A\+90(5[0-9]{9})\z/', $telefon, $eslesme)
            ? "0" . $eslesme[1]
            : null;
    }
    if (preg_match('/\A\+[1-9][0-9]{6,14}\z/', $telefon)) {
        return $telefon;
    }

    return null;
}

function uye_ulke_kodlari(): array
{
    return [
        "+90" => ["Türkiye", "Türkiye"],
        "+1" => ["ABD / Kanada", "United States / Canada"],
        "+44" => ["Birleşik Krallık", "United Kingdom"],
        "+49" => ["Almanya", "Germany"],
        "+33" => ["Fransa", "France"],
        "+31" => ["Hollanda", "Netherlands"],
        "+994" => ["Azerbaycan", "Azerbaijan"]
    ];
}

function uye_telefon_secimini_duzenle(string $telefon, string $kod): ?string
{
    $telefon = preg_replace('/[\s().-]+/u', '', $telefon);
    if ($telefon === null) {
        return null;
    }

    if ($kod === "+90" || $kod === "") {
        if ($kod === "+90" && $telefon !== "" && $telefon[0] === "+" && substr($telefon, 0, 3) !== "+90") {
            return null;
        }
        return uye_telefon_duzenle($telefon);
    }
    if ($kod === "diger") {
        return substr($telefon, 0, 1) === "+" ? uye_telefon_duzenle($telefon) : null;
    }
    if (!isset(uye_ulke_kodlari()[$kod])) {
        return null;
    }

    if (substr($telefon, 0, strlen($kod)) === $kod) {
        $telefon = substr($telefon, strlen($kod));
    }

    if ($kod === "+1" && !preg_match('/\A[0-9]{10}\z/', $telefon)) {
        return null;
    }

    return preg_match('/\A[1-9][0-9]{5,13}\z/', $telefon)
        ? uye_telefon_duzenle($kod . $telefon)
        : null;
}

function uye_telefon_form_degerleri(string $telefon, string $kod): array
{
    if ($kod !== "") {
        return [$kod, $telefon];
    }

    foreach (uye_ulke_kodlari() as $ulke_kodu => $adlar) {
        if (substr($telefon, 0, strlen($ulke_kodu)) === $ulke_kodu) {
            return [$ulke_kodu, substr($telefon, strlen($ulke_kodu))];
        }
    }

    return [substr($telefon, 0, 1) === "+" ? "diger" : "+90", $telefon];
}

function uye_adi_gecerli(string $ad): bool
{
    return preg_match('/\A\p{L}[\p{L}\p{M} .\x{0027}\x{2019}-]*\z/u', $ad) === 1;
}
