<?php
// SQL kurulmadan da sitenin mevcut mesajları çalışmaya devam eder.
function qa_gelenler($db, $uye, $sadece_yeni = false) {
    $sql = "SELECT q.id, q.ilan_id, q.soru, q.cevap, q.soru_tarihi, q.cevap_tarihi,
        i.ilan_basligi, i.hayvan_adi,
        CASE WHEN i.uye_id = ? THEN 'soru' WHEN q.cevap IS NULL THEN 'gonderilen' ELSE 'cevap' END AS tur,
        CASE WHEN i.uye_id = ? THEN q.soru_okundu WHEN q.cevap IS NULL THEN 1 ELSE q.cevap_okundu END AS okundu,
        (SELECT COUNT(*) FROM ilan_sorulari yeni WHERE yeni.ilan_id = q.ilan_id AND yeni.id > q.id) AS onceki
        FROM ilan_sorulari q JOIN ilanlar i ON i.id = q.ilan_id
        WHERE i.durum = 'Onaylandı' AND
        ((i.uye_id = ? AND q.uye_id <> ?) OR (q.uye_id = ? AND i.uye_id <> ?))";
    if ($sadece_yeni) $sql .= " AND ((i.uye_id = ? AND q.soru_okundu = 0) OR (q.uye_id = ? AND q.cevap IS NOT NULL AND q.cevap_okundu = 0))";
    $sql .= " ORDER BY COALESCE(q.cevap_tarihi, q.soru_tarihi) DESC, q.id DESC";
    if ($sadece_yeni) $sql .= " LIMIT 1";
    try {
        $s = mysqli_prepare($db, $sql);
        if (!$s) return [];
        if ($sadece_yeni) mysqli_stmt_bind_param($s, 'iiiiiiii', $uye, $uye, $uye, $uye, $uye, $uye, $uye, $uye);
        else mysqli_stmt_bind_param($s, 'iiiiii', $uye, $uye, $uye, $uye, $uye, $uye);
        if (!mysqli_stmt_execute($s)) { mysqli_stmt_close($s); return []; }
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
        mysqli_stmt_close($s);
        return $rows;
    } catch (Throwable $e) { error_log('Soru bildirimleri: ' . $e->getMessage()); return []; }
}
