<?php

function uye_hesabini_sil(mysqli $baglanti, int $uye_id, string $sifre_hash): void
{
    $sorgular = [
        ["DELETE f FROM favoriler f LEFT JOIN ilanlar i ON i.id = f.ilan_id
          WHERE f.uye_id = ? OR i.uye_id = ?", "ii", [$uye_id, $uye_id]],
        ["DELETE b FROM sahiplenme_basvurulari b LEFT JOIN ilanlar i ON i.id = b.ilan_id
          WHERE b.uye_id = ? OR i.uye_id = ?", "ii", [$uye_id, $uye_id]],
        ["DELETE m FROM ilan_mesajlari m LEFT JOIN ilanlar i ON i.id = m.ilan_id
          WHERE m.gonderen_id = ? OR m.alici_id = ? OR i.uye_id = ?", "iii", [$uye_id, $uye_id, $uye_id]],
        ["DELETE FROM ilanlar WHERE uye_id = ?", "i", [$uye_id]],
        ["DELETE FROM uyeler WHERE id = ? AND sifre = ?", "is", [$uye_id, $sifre_hash]]
    ];

    if (!$baglanti->begin_transaction()) {
        throw new RuntimeException("Hesap silme işlemi başlatılamadı.");
    }

    try {
        foreach ($sorgular as [$sql, $tipler, $degerler]) {
            $sorgu = $baglanti->prepare($sql);
            if (!$sorgu) {
                throw new RuntimeException("Silme sorgusu hazırlanamadı.");
            }

            try {
                $sorgu->bind_param($tipler, ...$degerler);

                if (!$sorgu->execute() || ($tipler === "is" && $sorgu->affected_rows !== 1)) {
                    throw new RuntimeException("Silme sorgusu tamamlanamadı.");
                }
            } finally {
                $sorgu->close();
            }
        }

        if (!$baglanti->commit()) {
            throw new RuntimeException("Hesap silme işlemi kaydedilemedi.");
        }
    } catch (RuntimeException $hata) {
        $baglanti->rollback();
        throw $hata;
    }
}
