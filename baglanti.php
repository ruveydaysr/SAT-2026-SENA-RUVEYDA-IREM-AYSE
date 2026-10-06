<?php

$baglanti = mysqli_connect(
    "localhost",
    "root",
    "",
    "hayvan_sahiplendirme"
);

if (!$baglanti) {
    die("Veritabanına bağlanılamadı.");
}

?>