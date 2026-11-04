<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (
    is_string($_GET["admin_lang"] ?? null) &&
    in_array($_GET["admin_lang"], ["tr", "en"], true)
) {
    $_SESSION["admin_dil"] = $_GET["admin_lang"];
}

$admin_dil = in_array(
    $_SESSION["admin_dil"] ?? "",
    ["tr", "en"],
    true
)
    ? $_SESSION["admin_dil"]
    : "tr";

function admin_t($metin)
{
    global $admin_dil;

    $ceviriler = [
        'Tümü' => 'All',
        'Filtrele' => 'Filter',
        'Silinmiş ilan' => 'Deleted listing',
        'İlan sahibi:' => 'Listing owner:',
        'Sayfalama' => 'Pagination',
        'Önceki' => 'Previous',
        'Sonraki' => 'Next',
        'Eski üye' => 'Former member',

        "Genel Bakış" => "Overview",
        "Admin Paneli" => "Admin Panel",
        "Ana Sayfa Yönetimi" => "Homepage Management",
        "İlan Yönetimi" => "Listing Management",
        "Sahiplenme Başvuruları" => "Adoption Applications",
        "Üye Yönetimi" => "Member Management",
        "Gelen Mesajlar" => "Incoming Messages",
        "Profilim" => "My Profile",
        "Her can dostuna bir yuva" => "A home for every animal",
        "Aris Pati Yuvası logosu" => "Aris Pati Yuvası logo",
        "Yönetim menüsü" => "Admin navigation",
        "Site menüsü" => "Site navigation",
        "Siteye Git" => "Visit Website",
        "Çıkış Yap" => "Log Out",
        "bekleyen kayıt" => "pending records",

        "Ana Sayfa" => "Home",
        "İlanlar" => "Listings",
        "İlan Ver" => "Create Listing",
        "Hakkımızda" => "About Us",
        "İletişim" => "Contact",

        "Yönetici Girişi" => "Admin Login",
        "Aris Pati Yuvası yönetim paneline giriş yapın."
            => "Log in to the Aris Pati Yuvası admin panel.",
        "Kullanıcı Adı" => "Username",
        "Kullanıcı adınızı yazınız" => "Enter your username",
        "Şifre" => "Password",
        "Şifrenizi yazınız" => "Enter your password",
        "Giriş Yap" => "Log In",
        "Kullanıcı adı veya şifre yanlış."
            => "Incorrect username or password.",

        "Yönetim paneline hoş geldiniz. Sistemdeki kayıt sayılarını buradan takip edebilirsiniz."
            => "Welcome to the admin panel. You can track record counts here.",
        "Toplam Üye" => "Total Members",
        "Toplam İlan" => "Total Listings",
        "Bekleyen İlan" => "Pending Listings",
        "Onaylanan İlan" => "Approved Listings",
        "Reddedilen İlan" => "Rejected Listings",
        "Toplam Sahiplenme Başvurusu" => "Total Adoption Applications",
        "Bekleyen Başvuru" => "Pending Applications",
        "Toplam Mesaj" => "Total Messages",

        "Üyeleri görüntüle →" => "View members →",
        "Tüm ilanları görüntüle →" => "View all listings →",
        "Bekleyen ilanları incele →" => "Review pending listings →",
        "Onaylanan ilanları görüntüle →" => "View approved listings →",
        "Reddedilen ilanları görüntüle →" => "View rejected listings →",
        "Tüm başvuruları görüntüle →" => "View all applications →",
        "Bekleyen başvuruları incele →" => "Review pending applications →",
        "Gelen mesajları görüntüle →" => "View incoming messages →",

        "Kayıtlı Üyeler" => "Registered Members",
        "Üye Ara" => "Search Members",
        "Ad soyad veya e-posta yazınız..."
            => "Enter a name or email address...",
        "Ara" => "Search",
        "Temizle" => "Clear",
        "Bulunan üye sayısı:" => "Members found:",
        "Üye ID" => "Member ID",
        "Ad Soyad" => "Full Name",
        "E-posta" => "Email",
        "Telefon" => "Phone",
        "İşlem" => "Action",
        "Detayları Gör" => "View Details",
        "Aramanıza uygun üye bulunamadı."
            => "No members match your search.",
        "Henüz kayıtlı üye bulunmuyor."
            => "No members have registered yet.",

        "Üye Detayları" => "Member Details",
        "← Üye Listesine Dön" => "← Back to Members",
        "Üye bulunamadı" => "Member not found",
        "Bu numaraya ait bir üye bulunmuyor."
            => "No member exists with this ID.",
        "— Üye Detayları" => "— Member Details",
        "Üye Bilgileri" => "Member Information",
        "Verdiği İlanlar" => "Member Listings",
        "Favorileri" => "Favorites",
        "Bu üyenin verdiği ilan bulunmuyor."
            => "This member has no listings.",
        "Bu üyenin sahiplenme başvurusu bulunmuyor."
            => "This member has no adoption applications.",
        "Bu üyenin favori kaydı bulunmuyor."
            => "This member has no favorites.",

        "Başlık" => "Title",
        "Tür" => "Species",
        "Şehir" => "City",
        "Yaş" => "Age",
        "Durum" => "Status",
        "Hayvan" => "Animal",
        "Başvuran" => "Applicant",
        "Yaşadığı Yer" => "Housing Type",
        "Başvuru Tarihi" => "Application Date",
        "İlan ID" => "Listing ID",
        "İlan Durumu" => "Listing Status",
        "İlan Tarihi" => "Listing Date",
        "Başvuru #" => "Application #",
        "İlan silinmiş" => "Listing deleted",
        "İlanı İncele" => "Review Listing",

        "Başvuru Durumu" => "Application Status",
        "Tüm Başvurular" => "All Applications",
        "Filtrele" => "Filter",
        "Bulunan başvuru sayısı:" => "Applications found:",
        "Seçilen duruma uygun başvuru bulunamadı."
            => "No applications match the selected status.",
        "Henüz sahiplenme başvurusu bulunmuyor."
            => "No adoption applications yet.",
        "Ek Bilgiyi Gör" => "View Additional Information",
        "Ek Bilgi" => "Additional Information",
        "— Ek Bilgi" => "— Additional Information",
        "Kapat ✕" => "Close ✕",
        "Onayla" => "Approve",
        "Reddet" => "Reject",

        "Bekliyor" => "Pending",
        "Onaylandı" => "Approved",
        "Reddedildi" => "Rejected",
        "Apartman" => "Apartment",
        "Müstakil Ev" => "Detached House",

        "Kedi" => "Cat",
        "Köpek" => "Dog",
        "Kuş" => "Bird",
        "Balık" => "Fish",
        "Tavşan" => "Rabbit",
        "Kemirgen" => "Rodent",
        "Sürüngen" => "Reptile",
        "Diğer" => "Other",
        "Erkek" => "Male",
        "Dişi" => "Female",

        "Kategori" => "Category",
        "Tüm Kategoriler" => "All Categories",
        "Tüm Durumlar" => "All Statuses",
        "Tüm tarihler" => "All Dates",
        "Bugün" => "Today",
        "Son 7 gün" => "Last 7 Days",
        "Son 30 gün" => "Last 30 Days",
        "Bulunan ilan sayısı:" => "Listings found:",
        "arası gösteriliyor." => "range displayed.",
        "Sayfa" => "Page",
        "Seçtiğiniz filtrelere uygun ilan bulunamadı."
            => "No listings match your filters.",
        "Tarih belirtilmemiş" => "Date not specified",
        "Hayvan fotoğrafı" => "Animal photo",
        "🐾 Fotoğraf Yok" => "🐾 No Photo",
        "Reddedilme Nedenini Gör" => "View Rejection Reason",
        "Sil" => "Delete",
        "İlanı Reddet" => "Reject Listing",
        "Reddetme Nedeni" => "Rejection Reason",
        "Reddetme nedenini yazınız..." => "Enter the rejection reason...",
        "İlan sayfaları" => "Listing pages",
        "‹ Önceki" => "‹ Previous",
        "Sonraki ›" => "Next ›",

        "Siteye gitmek istediğinize emin misiniz?"
            => "Are you sure you want to visit the website?",
        "Çıkış yapmak istediğinize emin misiniz?"
            => "Are you sure you want to log out?",
        "Bu ilanı onaylamak istediğinize emin misiniz?"
            => "Are you sure you want to approve this listing?",
        "Bu ilanı silmek istediğinize emin misiniz? Bu işlem geri alınamaz."
            => "Are you sure you want to delete this listing? This action cannot be undone.",
        "Bu ilanı reddetmek istediğinize emin misiniz?"
            => "Are you sure you want to reject this listing?",
        "Başvuruyu onaylamak istediğinize emin misiniz?"
            => "Are you sure you want to approve this application?",
        "Başvuruyu reddetmek istediğinize emin misiniz?"
            => "Are you sure you want to reject this application?",

        "Sayfayı yenileyip tekrar deneyiniz."
            => "Refresh the page and try again.",
        "Geçersiz işlem." => "Invalid action.",
        "Başvuru durumu güncellendi." => "Application status updated.",
        "Başvuru bulunamadı veya zaten değerlendirilmiş."
            => "Application not found or already reviewed.",
        "İşlem sırasında hata oluştu."
            => "An error occurred during the operation.",
        "Geçersiz ilan numarası." => "Invalid listing ID.",
        "Silme işlemi hazırlanamadı." => "Could not prepare the deletion.",
        "İlan başarıyla silindi." => "Listing deleted successfully.",
        "Silinecek ilan bulunamadı." => "Listing to delete was not found.",
        "İlan silinemedi." => "Could not delete the listing.",
        "İlan silinemedi. İlişkili kayıtlar bulunuyor olabilir."
            => "Could not delete the listing. Related records may exist.",
        "Geçerli bir ilan ve reddetme nedeni gereklidir."
            => "A valid listing and rejection reason are required.",
        "İlan reddedildi ve reddetme nedeni kaydedildi."
            => "Listing rejected and rejection reason saved.",
        "İlan bulunamadı veya zaten değerlendirilmiş."
            => "Listing not found or already reviewed.",
        "İlan reddedilemedi." => "Could not reject the listing.",
        "İlan başarıyla onaylandı." => "Listing approved successfully.",
        "İlan onaylanamadı." => "Could not approve the listing.",

        "Gelen Kutusu" => "Inbox",
        "İletişim formundan gönderilen soru ve mesajlar."
            => "Questions and messages sent through the contact form.",
        "mesaj" => "messages",
        "Mesajlar yüklenemedi. Lütfen tekrar deneyiniz."
            => "Could not load messages. Please try again.",
        "Gelen kutusu henüz boş" => "Your inbox is empty",
        "Gönderilen mesajlar burada listelenecek."
            => "Incoming messages will appear here.",
        "Ziyaretçi" => "Visitor",
        "Aris Pati Yuvası - Mesajınıza Yanıt #"
            => "Aris Pati Yuvası - Reply to Your Message #",
        "İletişim Mesajı" => "Contact Message",
        "E-posta ile Cevapla" => "Reply by Email",
        "E-posta uygulamanız açılır. Yanıtınızı oradan gönderebilirsiniz."
            => "Your email application will open. You can send your reply there.",
        "Geçerli bir e-posta adresi bulunmuyor."
            => "No valid email address is available.",
        "Mesajı aç" => "Open message",
        "Mesajı kapat" => "Close message",

        "Kendi yönetici bilgilerinizi ve şifrenizi buradan değiştirebilirsiniz."
            => "Update your administrator details and password here.",
        "Yönetici Bilgilerim" => "My Administrator Details",
        "Henüz eklenmedi" => "Not added yet",
        "Bilgilerimi Düzenle" => "Edit My Details",
        "Değişiklikleri kaydetmek için mevcut şifrenizi giriniz."
            => "Enter your current password to save changes.",
        "Değiştirirseniz sonraki girişinizde yeni kullanıcı adınızı kullanınız."
            => "If changed, use your new username the next time you log in.",
        "E-posta Adresi" => "Email Address",
        "Telefon Numarası" => "Phone Number",
        "05 ile başlayan 11 haneli numaranızı boşluksuz yazınız."
            => "Enter your 11-digit number starting with 05, without spaces.",
        "Mevcut Şifreniz" => "Your Current Password",
        "Mevcut Şifre" => "Current Password",
        "Bilgilerimi Kaydet" => "Save My Details",
        "Şifremi Değiştir" => "Change My Password",
        "Yeni şifreniz en az 8 karakter olmalıdır."
            => "Your new password must contain at least 8 characters.",
        "Yeni Şifre" => "New Password",
        "Yeni Şifre Tekrarı" => "Confirm New Password",
        "İşlem doğrulanamadı. Sayfayı yenileyip tekrar deneyiniz."
            => "Could not verify the request. Refresh the page and try again.",
        "Lütfen tüm alanları doldurunuz." => "Please fill in all fields.",
        "Ad soyad en fazla 100, kullanıcı adı en fazla 50 karakter olabilir."
            => "Full name may contain up to 100 characters and username up to 50.",
        "Geçerli bir e-posta adresi giriniz." => "Enter a valid email address.",
        "Telefon numarası 05 ile başlamalı ve 11 haneli olmalıdır."
            => "Phone number must start with 05 and contain 11 digits.",
        "Mevcut şifreniz yanlış." => "Your current password is incorrect.",
        "Bu kullanıcı adı veya e-posta başka bir admin tarafından kullanılıyor."
            => "This username or email is already used by another administrator.",
        "Bilgileriniz başarıyla güncellendi."
            => "Your details have been updated successfully.",
        "Bilgiler kaydedilemedi. Girdiğiniz bilgileri kontrol ediniz."
            => "Could not save your details. Check your entries.",
        "Lütfen tüm şifre alanlarını doldurunuz."
            => "Please fill in all password fields.",
        "Yeni şifreniz çok uzun. Daha kısa bir şifre giriniz."
            => "Your new password is too long. Enter a shorter password.",
        "Yeni şifreler birbiriyle eşleşmiyor."
            => "The new passwords do not match.",
        "Yeni şifreniz mevcut şifrenizden farklı olmalıdır."
            => "Your new password must differ from your current password.",
        "Şifreniz başarıyla değiştirildi."
            => "Your password has been changed successfully.",
        "Şifre değiştirilemedi. Sayfayı yenileyip tekrar deneyiniz."
            => "Could not change the password. Refresh the page and try again.",
        "Şifre değiştirilemedi. Tekrar deneyiniz."
            => "Could not change the password. Try again.",

        "Başlıkları, görselleri ve bölümleri bu ekrandan yönetin."
            => "Manage homepage headings, images and sections here.",
        "Ana Sayfayı Aç ↗" => "Open Homepage ↗",
        "Dosya seçtiyseniz tekrar seçiniz."
            => "If you selected a file, please select it again.",
        "Karşılama Alanı" => "Welcome Section",
        "Karşılama alanını göster" => "Show welcome section",
        "Açıklama" => "Description",
        "Buton yazısı" => "Button text",
        "Butonun açacağı sayfa" => "Button destination",
        "Kapak Görseli" => "Cover Image",
        "Mevcut kapak" => "Current cover",
        "Görsel ana sayfada yazının sağında gösterilir. Her görsel en fazla 5 MB olabilir. Yeni görsel seçerseniz yüklediğiniz görsel kullanılır."
            => "The image appears to the right of the homepage text. Each image may be up to 5 MB. Selecting a new image replaces the current one.",
        "Yüklenen kapağı kaldır, images/anasayfa.png görseline dön"
            => "Remove uploaded cover and restore images/anasayfa.png",
        "Duyuru Bandı" => "Announcement Banner",
        "Duyuru bandını göster" => "Show announcement banner",
        "Duyuru metni" => "Announcement text",
        "Bu bölümü ana sayfada göster" => "Show this section on the homepage",
        "Kısa Tanıtım" => "Introduction",
        "Kategoriler" => "Categories",
        "Hayvan Kategorileri" => "Animal Categories",
        "Kategorileri göster" => "Show categories",
        "Vitrin ilanlarını göster" => "Show featured listings",
        "Vitrin Başlıkları" => "Featured Listing Headings",
        "Sahiplenme Adımları" => "Adoption Steps",
        "Şehirlere Göre İlanlar" => "Listings by City",
        "Sık Sorulan Sorular" => "Frequently Asked Questions",
        "Alt Davet Alanı" => "Call to Action",
        "Alt Bilgi" => "Footer",
        "Alt Bilgi Metni" => "Footer Text",
        "Küçük üst yazı" => "Eyebrow text",
        "Şehirler (her satıra bir şehir)" => "Cities (one per line)",
        "Her satıra bir şehir yazınız. En fazla 24 şehir ekleyebilirsiniz. Şehir düğmeleri ilgili şehrin ilanlarını açar."
            => "Enter one city per line, up to 24 cities. City buttons open listings for the selected city.",
        "Yeni görsel seçmezseniz mevcut görsel korunur. Varsayılan görseller images klasöründeki hayvan resimleridir."
            => "The current image is kept unless you select a new one. Default animal images are in the images folder.",
        "Yeni görsel" => "New image",
        "Varsayılan görsele dön" => "Restore default image",
        "Bir soruyu gizlemek için soru ve cevap alanlarını birlikte boş bırakınız."
            => "Leave both the question and answer empty to hide a question.",
        "Soru" => "Question",
        "Cevap" => "Answer",
        "Soru " => "Question ",
        "Adım " => "Step ",
        "Tanıtım " => "Introduction ",
        "En fazla 12 onaylı ilan seçebilirsiniz. İlanlar en yeniden eskiye sıralanır. Onaydan kaldırılan ilan ana sayfada gösterilmez."
            => "Select up to 12 approved listings, shown newest first. Listings that lose approval are hidden from the homepage.",
        "Henüz onaylanmış ilan yok." => "No approved listings yet.",
        "Tüm Değişiklikleri Kaydet" => "Save All Changes",
        "Değişiklikler kaydedildikten sonra ziyaretçilere yansır."
            => "Changes become visible to visitors after saving.",
        "Kaydedilmiş Ana Sayfa Önizlemesi" => "Saved Homepage Preview",
        "Kaydedilmiş ana sayfa önizlemesi" => "Saved homepage preview",
        "Bu alan gerçek ana sayfayı gösterir. Değişiklikleri kaydedince önizleme yenilenir."
            => "This area shows the actual homepage. The preview refreshes after you save changes.",
        " / 12 ilan seçildi" => " / 12 listings selected",
        "En fazla 12 vitrin ilanı seçebilirsiniz."
            => "You can select up to 12 featured listings.",

        "Geçersiz form alanı." => "Invalid form field.",
        "Metin alanlarının karakter sınırlarını aşmayınız."
            => "Do not exceed the text field character limits.",
        "Geçersiz görsel yüklemesi." => "Invalid image upload.",
        "Görsel yüklenemedi. Dosya boyutu veya sunucu yükleme sınırını kontrol ediniz."
            => "Image upload failed. Check the file size and server upload limit.",
        "Her görsel en fazla 5 MB olabilir." => "Each image may be up to 5 MB.",
        "JPG, PNG veya WEBP seçiniz. Görsel en fazla 8000 × 8000 piksel olabilir."
            => "Select JPG, PNG or WEBP. Images may be up to 8000 × 8000 pixels.",
        "Görsel klasörü oluşturulamadı." => "Could not create the image folder.",
        "Görsel kaydedilemedi." => "Could not save the image.",
        "Önce verilen SQL sorgusunu phpMyAdmin üzerinden çalıştırınız."
            => "First run the provided SQL query in phpMyAdmin.",
        "Ana sayfa kayıtları bulunamadı. anasayfa_icerik ve anasayfa_ayarlar tablolarında id=1 kaydını kontrol ediniz."
            => "Homepage records were not found. Check the id=1 records in anasayfa_icerik and anasayfa_ayarlar.",
        "İşlem doğrulanamadı. Sayfayı yenileyiniz. Büyük dosya yüklediyseniz sunucu yükleme sınırını kontrol ediniz."
            => "Could not verify the request. Refresh the page. If you uploaded a large file, check the server upload limit.",
        "Karşılama başlığını, açıklamasını ve buton yazısını doldurunuz."
            => "Fill in the welcome title, description and button text.",
        "Karakter sınırlarını aşmayınız." => "Do not exceed the character limits.",
        "Geçerli bir buton bağlantısı seçiniz." => "Select a valid button link.",
        "Duyuru bandı açıkken duyuru metnini doldurunuz."
            => "Enter announcement text when the banner is enabled.",
        "Geçersiz vitrin seçimi." => "Invalid featured listing selection.",
        "Davet butonu için geçerli bir bağlantı seçiniz."
            => "Select a valid call-to-action button link.",
        "Sahiplenme adımları başlığını doldurunuz."
            => "Enter the adoption steps heading.",
        " içindeki başlık ve açıklamaları doldurunuz."
            => ": fill in the titles and descriptions.",
        " başlığını doldurunuz." => ": enter the heading.",
        "SSS satırlarında soru ve cevabı birlikte doldurunuz veya ikisini de boş bırakınız."
            => "Fill in both question and answer in FAQ rows, or leave both empty.",
        "SSS bölümü için en az bir soru ve cevap giriniz."
            => "Enter at least one FAQ question and answer.",
        "Kategori başlığını doldurunuz." => "Enter the category heading.",
        "Vitrin başlığını doldurunuz." => "Enter the featured listings heading.",
        "Ana sayfada en fazla 24 şehir gösterilebilir."
            => "The homepage can display up to 24 cities.",
        "Şehir adları en fazla 100 karakter olabilir."
            => "City names may contain up to 100 characters.",
        "Şehir bölümü için en az bir şehir giriniz."
            => "Enter at least one city for the city section.",
        "Davet butonunun yazısını doldurunuz."
            => "Enter the call-to-action button text.",
        "Ana sayfa ayarları başarıyla güncellendi."
            => "Homepage settings updated successfully.",
        "Değişiklikler kaydedilemedi. Veritabanı bağlantısını kontrol ediniz."
            => "Could not save changes. Check the database connection."
    ];

    $metin = (string) $metin;

    if ($admin_dil !== "en") {
        return $metin;
    }

    if (isset($ceviriler[$metin])) {
        return $ceviriler[$metin];
    }

    // Örnek: "Telefon:" gibi iki nokta ile biten etiketler.
    if (substr($metin, -1) === ":") {
        $etiket = substr($metin, 0, -1);

        if (isset($ceviriler[$etiket])) {
            return $ceviriler[$etiket] . ":";
        }
    }

    // Sayfa başlıkları: "Genel Bakış | Admin Paneli"
    if (strpos($metin, " | ") !== false) {
        $parcalar = explode(" | ", $metin);

        foreach ($parcalar as &$parca) {
            $parca = $ceviriler[$parca] ?? $parca;
        }

        unset($parca);

        return implode(" | ", $parcalar);
    }

    return $metin;
}

function admin_h($metin)
{
    return htmlspecialchars(
        (string) $metin,
        ENT_QUOTES,
        "UTF-8"
    );
}

function admin_json($metin)
{
    return json_encode(
        admin_t($metin),
        JSON_HEX_TAG |
        JSON_HEX_AMP |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_UNESCAPED_UNICODE
    );
}

function admin_dil_linki($dil)
{
    $parametreler = $_GET;
    $parametreler["admin_lang"] = $dil;

    return "?" . http_build_query($parametreler);
}

function admin_dil_secici()
{
    global $admin_dil;

    echo '<nav class="admin-dil-secimi"
               aria-label="Language / Dil"
               style="display:flex;gap:6px;align-items:center;
                      margin-left:auto;padding:10px;flex-shrink:0">';

    foreach (["tr" => "TR", "en" => "EN"] as $kod => $yazi) {
        $aktif = $admin_dil === $kod;

        echo '<a href="' . admin_h(admin_dil_linki($kod)) . '"
                 lang="' . $kod . '"
                 hreflang="' . $kod . '"'
                 . ($aktif ? ' aria-current="true"' : '') . '
                 style="display:inline-block;padding:7px 10px;
                        border:1px solid #78916e;border-radius:6px;
                        text-decoration:none;'
                        . ($aktif
                            ? 'background:#284b32;color:#fff;'
                            : 'background:#fffdf8;color:#284b32;')
                        . '">'
                 . $yazi .
             '</a>';
    }

    echo '</nav>';
}