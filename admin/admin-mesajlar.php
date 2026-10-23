<?php
session_start();

if (!isset($_SESSION["admin"])) {
    header("Location: admin-giris.php");
    exit();
}

require_once __DIR__ . "/../baglanti.php";

function guvenli($deger)
{
    return htmlspecialchars(
        (string)$deger,
        ENT_QUOTES,
        "UTF-8"
    );
}

$mesajlar = mysqli_query(
    $baglanti,
    "SELECT id, ad_soyad, email, tarih, mesaj
     FROM mesajlar
     ORDER BY tarih DESC, id DESC"
);

$toplam = $mesajlar
    ? mysqli_num_rows($mesajlar)
    : 0;
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gelen Kutusu | Aris Pati Yuvası</title>

    <link rel="stylesheet" href="admin-style.css?v=14">

    <style>
        .gelen-kutusu-baslik {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 26px;
        }

        .gelen-kutusu-baslik h2 {
            margin: 0 0 8px;
            color: #284b32;
            font-size: 28px;
        }

        .gelen-kutusu-baslik p {
            margin: 0;
            color: #687361;
            line-height: 1.6;
        }

        .mesaj-sayaci {
            padding: 10px 18px;
            border: 1px solid #ded6c6;
            border-radius: 25px;
            background: #fffdf8;
            color: #284b32;
            font-weight: bold;
        }

        .mesaj-listesi {
            display: grid;
            gap: 22px;
        }

        .mesaj-karti {
            min-width: 0;
            overflow: hidden;
            border: 1px solid #ded6c6;
            border-radius: 16px;
            background: #fffdf8;
            box-shadow: 0 6px 22px rgba(40, 75, 50, 0.08);
        }

        .mesaj-karti-ust {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 22px;
            border-bottom: 1px solid #ded6c6;
            background: #eee7d8;
        }

        .mesaj-avatar {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-shrink: 0;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #284b32;
            color: #fff;
            font-size: 22px;
            font-weight: bold;
        }

        .mesaj-kisi {
            flex: 1;
            min-width: 0;
        }

        .mesaj-kisi h3 {
            margin: 0 0 7px;
            color: #284b32;
            font-size: 18px;
            overflow-wrap: anywhere;
        }

        .mesaj-kisi a,
        .mesaj-kisi span {
            color: #58664f;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .mesaj-kisi a:hover {
            text-decoration: underline;
        }

        .mesaj-tarih {
            color: #687361;
            font-size: 13px;
        }

        .mesaj-karti-icerik {
            padding: 26px;
        }

        .mesaj-etiket {
            display: inline-block;
            margin-bottom: 15px;
            padding: 7px 12px;
            border-radius: 7px;
            background: #e6eddf;
            color: #284b32;
            font-size: 12px;
            font-weight: bold;
        }

        .mesaj-metni {
            margin: 0;
            padding: 18px;
            border: 1px solid #e4ddcf;
            border-radius: 10px;
            background: #f7f3ea;
            color: #354638;
            font-size: 15px;
            line-height: 1.8;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .mesaj-alt {
            margin-top: 18px;
        }

        .cevapla-buton {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border-radius: 8px;
            background: #426b46;
            color: #fff;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
        }

        .cevapla-buton:hover {
            background: #284b32;
        }

        .cevapla-buton:focus-visible {
            outline: 3px solid #92bc83;
            outline-offset: 3px;
        }

        .cevap-aciklama {
            margin: 10px 0 0;
            color: #687361;
            font-size: 12px;
            line-height: 1.6;
        }

        .mesaj-bos {
            padding: 40px 22px;
            border: 1px dashed #b7b09f;
            border-radius: 14px;
            background: #fffdf8;
            text-align: center;
        }

        @media (max-width: 700px) {
            .mesaj-karti-ust {
                flex-wrap: wrap;
                padding: 18px;
            }

            .mesaj-tarih {
                width: 100%;
                padding-left: 65px;
            }

            .mesaj-karti-icerik {
                padding: 18px;
            }

            .cevapla-buton {
                width: 100%;
            }
        }
        .mesaj-listesi {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    align-items: start;
    gap: 18px;
}

.mesaj-karti > summary {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding: 18px;
    background: #eee7d8;
    cursor: pointer;
    list-style: none;
}

.mesaj-karti > summary::-webkit-details-marker {
    display: none;
}

.mesaj-karti > summary:hover {
    background: #e5ddcb;
}

.mesaj-karti > summary:focus-visible {
    outline: 3px solid #426b46;
    outline-offset: -3px;
}

.mesaj-karti .mesaj-kisi h3 {
    font-size: 16px;
}

.mesaj-karti .mesaj-tarih {
    width: 100%;
    padding-left: 0;
    font-size: 12px;
}

.mesaj-ac-isaret {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    padding-top: 12px;
    border-top: 1px solid #d6cdbb;
    color: #426b46;
    font-size: 13px;
    font-weight: bold;
}

.mesaj-ac-isaret::before {
    content: "Mesajı aç";
}

.mesaj-ac-isaret::after {
    content: "+";
    font-size: 20px;
}

.mesaj-karti[open] .mesaj-ac-isaret::before {
    content: "Mesajı kapat";
}

.mesaj-karti[open] .mesaj-ac-isaret::after {
    content: "−";
}

.mesaj-karti[open] > summary {
    border-bottom: 1px solid #ded6c6;
}

.mesaj-karti .mesaj-karti-icerik {
    padding: 18px;
}

.mesaj-karti .cevapla-buton {
    width: 100%;
    text-align: center;
}

@media (max-width: 1000px) {
    .mesaj-listesi {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 650px) {
    .mesaj-listesi {
        grid-template-columns: 1fr;
    }
}
    </style>
</head>

<body>

<?php require __DIR__ . "/admin-menu.php"; ?>

<main class="icerik">

    <div class="gelen-kutusu-baslik">
        <div>
            <h2>Gelen Kutusu</h2>
            <p>İletişim formundan gönderilen soru ve mesajlar.</p>
        </div>

        <?php if ($mesajlar): ?>
            <span class="mesaj-sayaci">
                <?php echo $toplam; ?> mesaj
            </span>
        <?php endif; ?>
    </div>

    <?php if (!$mesajlar): ?>

        <div class="mesaj-bos" role="alert">
            <p>Mesajlar yüklenemedi. Lütfen tekrar deneyiniz.</p>
        </div>

    <?php elseif ($toplam === 0): ?>

        <div class="mesaj-bos">
            <h3>Gelen kutusu henüz boş</h3>
            <p>Gönderilen mesajlar burada listelenecek.</p>
        </div>

    <?php else: ?>

        <div class="mesaj-listesi">

            <?php while ($mesaj = mysqli_fetch_assoc($mesajlar)): ?>

                <?php
                $ad = trim((string)($mesaj["ad_soyad"] ?? ""));

                if ($ad === "") {
                    $ad = "Ziyaretçi";
                }

                $harf = mb_strtoupper(
                    mb_substr($ad, 0, 1, "UTF-8"),
                    "UTF-8"
                );

                $email = trim((string)($mesaj["email"] ?? ""));

                $gecerli_email = filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                );

                $tarih = (string)($mesaj["tarih"] ?? "");
                $zaman = $tarih !== "" ? strtotime($tarih) : false;

                $gorunen_tarih = $zaman !== false
                    ? date("d.m.Y H:i", $zaman)
                    : $tarih;

                $cevap_linki = "";

                if ($gecerli_email) {
                    $konu = "Aris Pati Yuvası - Mesajınıza Yanıt #"
                        . (int)$mesaj["id"];

                    $cevap_linki = "mailto:" . $email
                        . "?subject=" . rawurlencode($konu);
                }
                ?>

                <details class="mesaj-karti">

    <summary>
        <span class="mesaj-avatar" aria-hidden="true">
            <?php echo guvenli($harf); ?>
        </span>

        <div class="mesaj-kisi">
            <h3><?php echo guvenli($ad); ?></h3>
            <span><?php echo guvenli($email); ?></span>
        </div>

        <span class="mesaj-tarih">
            <?php echo guvenli($gorunen_tarih); ?>
        </span>

        <span class="mesaj-ac-isaret" aria-hidden="true"></span>
    </summary>

    <div class="mesaj-karti-icerik">

        <span class="mesaj-etiket">
            İletişim Mesajı
        </span>

        <p class="mesaj-metni"><?php
            echo guvenli($mesaj["mesaj"] ?? "");
        ?></p>

        <div class="mesaj-alt">

            <?php if ($gecerli_email): ?>

                <a
                    href="<?php echo guvenli($cevap_linki); ?>"
                    class="cevapla-buton"
                >
                    E-posta ile Cevapla
                </a>

                <p class="cevap-aciklama">
                    E-posta uygulamanız açılır.
                    Yanıtınızı oradan gönderebilirsiniz.
                </p>

            <?php else: ?>

                <p class="cevap-aciklama">
                    Geçerli bir e-posta adresi bulunmuyor.
                </p>

            <?php endif; ?>

        </div>

    </div>

</details>

            <?php endwhile; ?>

        </div>

    <?php endif; ?>

</main>

</body>
</html>