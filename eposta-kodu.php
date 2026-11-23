<?php

function eposta_ayarlari(): array
{
    // Ayar dosyası proje klasörünün ve XAMPP htdocs dizininin dışında.
    $dosya = dirname(__DIR__, 2) . '/aris-eposta-ayarlari.php';
    return is_file($dosya) ? require $dosya : [];
}

function eposta_kodu_baslat(string $kullanici_adi, bool $ingilizce): bool
{
    $simdi = time();
    if (($simdi - ($_SESSION['aris_kod_gonderim']['admin'] ?? 0)) < 60) {
        return false;
    }
    $_SESSION['aris_kod_gonderim']['admin'] = $simdi;

    try {
        $ayar = eposta_ayarlari();
        $gonderen = $ayar['gmail'] ?? '';
        $sifre = str_replace(' ', '', $ayar['uygulama_sifresi'] ?? '');

        if (!filter_var($gonderen, FILTER_VALIDATE_EMAIL) || $sifre === '') {
            return false;
        }
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            return false;
        }
        require_once $autoload;

        $kod = (string) random_int(100000, 999999);
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 587;
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Username = $gonderen;
        $mail->Password = $sifre;
        $mail->Timeout = 10;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($gonderen, 'Aris Pati Yuvası');
        $mail->addAddress($gonderen);
        $mail->Subject = $ingilizce ? 'Your verification code' : 'Doğrulama kodunuz';
        $mail->Body = $ingilizce
            ? "Your verification code is $kod. It expires in 10 minutes."
            : "Doğrulama kodunuz $kod. Kod 10 dakika geçerlidir.";
        $mail->send();

        $_SESSION['aris_kod']['admin'] = [
            'hash' => password_hash($kod, PASSWORD_DEFAULT),
            'token' => bin2hex(random_bytes(32)),
            'bitis' => $simdi + 600,
            'deneme' => 0,
            'kullanici_adi' => $kullanici_adi,
            'en' => $ingilizce,
        ];
        return true;
    } catch (Throwable $hata) {
        error_log('Aris: doğrulama e-postası gönderilemedi (' . get_class($hata) . ').');
        return false;
    }
}
