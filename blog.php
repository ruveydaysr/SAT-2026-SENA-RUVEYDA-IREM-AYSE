<?php
session_start();
require_once __DIR__ . "/site-ust-ayar.php";

/* Dil tercihi bütün iki dilli sayfalarda aynı oturumdan okunur. */
$istenen_dil = $_GET["lang"] ?? null;

if (
    is_string($istenen_dil)
    && in_array($istenen_dil, ["tr", "en"], true)
) {
    $_SESSION["dil"] = $istenen_dil;
}

$dil = $_SESSION["dil"] ?? "tr";

if (!is_string($dil) || !in_array($dil, ["tr", "en"], true)) {
    $dil = "tr";
}

$_SESSION["dil"] = $dil;

function blog_guvenli($deger)
{
    return htmlspecialchars((string)$deger, ENT_QUOTES, "UTF-8");
}

function blog_ceviri($tr, $en)
{
    global $dil;
    return $dil === "en" ? $en : $tr;
}

/* Dil bağlantılarında yalnızca bu sayfanın bilinen parametreleri taşınır. */
function blog_dil_linki($yeni_dil)
{
    $parametreler = [];

    foreach (["id", "kategori", "arama"] as $anahtar) {
        if (isset($_GET[$anahtar]) && is_string($_GET[$anahtar])) {
            $parametreler[$anahtar] = $_GET[$anahtar];
        }
    }

    $parametreler["lang"] = $yeni_dil;

    return "blog.php?" . http_build_query($parametreler);
}

function blog_arama_metni($metin)
{
    return strtolower(strtr($metin, [
        "İ" => "i",
        "I" => "i",
        "ı" => "i",
        "Ş" => "s",
        "ş" => "s",
        "Ğ" => "g",
        "ğ" => "g",
        "Ü" => "u",
        "ü" => "u",
        "Ö" => "o",
        "ö" => "o",
        "Ç" => "c",
        "ç" => "c"
    ]));
}

$kategori_adlari = [
    "Tümü" => blog_ceviri("Tümü", "All"),
    "Sahiplenme" => blog_ceviri("Sahiplenme", "Adoption"),
    "Kedi Bakımı" => blog_ceviri("Kedi Bakımı", "Cat Care"),
    "Köpek Bakımı" => blog_ceviri("Köpek Bakımı", "Dog Care"),
    "Kuş Bakımı" => blog_ceviri("Kuş Bakımı", "Bird Care"),
    "Pet Dünyası" => blog_ceviri("Pet Dünyası", "Pet Life")
];

$menu_kategorileri = [
    ["Kedi", "Cat", "🐱"],
    ["Köpek", "Dog", "🐶"],
    ["Kuş", "Bird", "🐦"],
    ["Balık", "Fish", "🐠"],
    ["Tavşan", "Rabbit", "🐰"],
    ["Kemirgen", "Rodent", "🐹"],
    ["Sürüngen", "Reptile", "🦎"],
    ["Diğer", "Other", "🐾"]
];

/*
 * Her bölüm:
 * [Türkçe başlık, İngilizce başlık, Türkçe metin, İngilizce metin]
 */
$yazilar = [
    1 => [
        "baslik" => blog_ceviri(
            "Hayvan Sahiplenmeden Önce Nelere Dikkat Etmeliyiz?",
            "What Should You Consider Before Adopting a Pet?"
        ),
        "kategori" => "Sahiplenme",
        "resim" => "images/blog/sahiplenme.png",
        "ozet" => blog_ceviri(
            "Bir hayvana yuva açmadan önce yaşam düzenini, bakım sorumluluklarını ve evinin hazırlığını birlikte değerlendir.",
            "Before welcoming a pet, consider your daily routine, care responsibilities and preparations at home."
        ),
        "bolumler" => [
            [
                "Sahiplenme kararını aceleye getirme",
                "Take your time with the adoption decision",
                "Bir hayvan sahiplenmek, yalnızca onunla keyifli zaman geçirmek anlamına gelmez. Beslenmesi, temizliği, güvenliği ve günlük ihtiyaçları için düzenli sorumluluk almak gerekir. Bu sorumluluk, tatillerde veya yoğun olduğun dönemlerde de devam eder. Karar vermeden önce bugünkü yaşamını ve ileride oluşabilecek değişiklikleri düşün. Taşınma, okul, iş veya seyahat planlarında yeni dostunun bakımını nasıl sürdüreceğini değerlendirmek iyi bir başlangıçtır.",
                "Adopting a pet involves more than enjoying time together. You take ongoing responsibility for feeding, cleaning, safety and daily needs. This responsibility continues during holidays and busy periods. Before deciding, consider your current lifestyle and possible future changes. Think about how you would provide care when moving home, studying, working or travelling."
            ],
            [
                "Yaşam düzenine uygun bir dost seç",
                "Choose a companion whose needs fit your lifestyle",
                "Hayvanın yalnızca görünüşüne göre karar vermek yerine yaşı, hareketliliği, karakteri ve alışkanlıkları hakkında bilgi edin. Sakin bir hayvanla hareketli bir hayvanın günlük ihtiyaçları farklı olabilir. Gün içinde evde ne kadar bulunduğunu, birlikte zaman geçirmek için ne kadar fırsatın olduğunu ve evinin koşullarını düşün. Amaç, hayvanı kendi düzenine zorla uydurmak yerine onun ihtiyaçlarını da karşılayabileceğin bir yaşam oluşturmaktır.",
                "Learn about the animal’s age, activity level, personality and habits instead of deciding based only on appearance. A quiet animal and an energetic one may have different daily needs. Consider how much time you spend at home, how much time you can share and what your home can accommodate. Aim to build a life that meets your companion’s needs as well as your own."
            ],
            [
                "Evdeki herkesle sorumlulukları paylaş",
                "Share responsibilities with everyone at home",
                "Aynı evi paylaştığın kişilerin sahiplenme kararına katılması önemlidir. Mama ve su kontrolü, yaşam alanının temizliği, oyun ve dışarı çıkma gibi görevleri önceden konuşabilirsiniz. Çocukların da yaşlarına uygun görevleri olabilir; ancak bakımın asıl sorumluluğu bir yetişkinde olmalıdır. Evde başka bir hayvan varsa yeni gelecek dost için ayrı bir başlangıç alanı hazırlamak ve tanışmayı aceleye getirmemek gerekir.",
                "The people you live with should be involved in the adoption decision. Discuss feeding, checking water, cleaning, play and walks in advance. Children can help with age-appropriate tasks, while an adult remains responsible for overall care. If another animal already lives at home, prepare a separate starting area for the newcomer and allow introductions to happen gradually."
            ],
            [
                "Düzenli giderleri ve beklenmedik ihtiyaçları düşün",
                "Plan for regular expenses and unexpected needs",
                "Mama, temizlik malzemeleri, taşıma çantası, yatak ve oyuncak gibi ihtiyaçlar için bütçe ayırmalısın. Bazı eşyalar ilk hazırlık sırasında alınırken bazı giderler düzenli olarak devam eder. Veteriner kontrolleri ve beklenmedik bakım ihtiyaçlarını da planına kat. Kullanılacak ürünleri yalnızca fiyatına veya görünüşüne göre seçmek yerine hayvanın türüne, boyutuna ve kullanımına uygun olup olmadığını değerlendir.",
                "Set aside a budget for food, cleaning supplies, a carrier, bedding and toys. Some purchases are part of the initial preparation, while other expenses continue regularly. Include veterinary visits and unexpected care needs in your planning. When choosing products, consider whether they suit the animal’s species, size and intended use, alongside their price and appearance."
            ],
            [
                "Evi ve temel eşyaları önceden hazırla",
                "Prepare your home and essential supplies",
                "Yeni dostun eve gelmeden önce sakin bir dinlenme alanı, uygun mama ve su kapları ve güvenli taşıma ekipmanı hazır olsun. Kedi için kum kabı ve tırmalama alanı, köpek için uygun tasma ve gezdirme ekipmanı gibi türe özgü ihtiyaçları da düşün. Açık pencereleri, balkonları, elektrik kablolarını ve ulaşılabilir küçük eşyaları kontrol et. İlaçları ve temizlik ürünlerini kapalı dolaplarda sakla.",
                "Before your companion arrives, prepare a quiet resting place, suitable food and water bowls and secure transport equipment. Remember species-specific supplies, such as a litter tray and scratching surface for a cat or suitable walking equipment for a dog. Check windows, balconies, electrical cables and small objects within reach. Keep medicines and cleaning products in closed cupboards."
            ],
            [
                "İlk günler için sabırlı bir başlangıç planla",
                "Plan a patient start for the first few days",
                "Yeni bir ev, hayvan için farklı sesler, kokular ve insanlarla karşılaşmak demektir. İlk günlerde sürekli sevmeye çalışmak veya çok sayıda ziyaretçi çağırmak yerine çevresini tanımasına fırsat ver. Bazı hayvanlar hemen yaklaşırken bazıları daha fazla zamana ihtiyaç duyabilir. Önceki bakımını üstlenen kişiden günlük düzeni hakkında bilgi almak, alıştığı bazı uygulamaları sürdürmene yardımcı olur. Uyum sürecini başka hayvanlarla karşılaştırmadan kendi dostunun davranışlarını gözlemle.",
                "A new home brings unfamiliar sounds, smells and people. Give your companion time to explore instead of offering constant attention or inviting many visitors. Some animals approach immediately, while others need more time. Ask the previous caregiver about daily routines so you can keep some familiar habits. Observe your own companion’s behaviour without comparing their adjustment with that of other animals."
            ]
        ]
    ],

    2 => [
        "baslik" => blog_ceviri(
            "Yeni Sahiplenilen Kedinin Eve Alışması",
            "Helping a Newly Adopted Cat Settle In"
        ),
        "kategori" => "Kedi Bakımı",
        "resim" => "images/blog/kedi-eve-alisma.png",
        "ozet" => blog_ceviri(
            "Sakin bir başlangıç alanı, güvenli bir ev ve sabırlı iletişimle kedinin yeni yuvasını kendi hızında keşfetmesine yardımcı ol.",
            "Help your cat explore their new home at their own pace with a quiet starting area, a safe environment and patient interaction."
        ),
        "bolumler" => [
            [
                "İlk gün için sakin bir oda hazırla",
                "Prepare a quiet room for the first day",
                "Kedini ilk anda evin bütün odalarıyla tanıştırmak yerine sakin bir başlangıç alanı hazırlayabilirsin. Bu alanda dinlenebileceği bir yatak, saklanabileceği güvenli bir kutu, mama ve su kapları ile kum kabı bulunabilir. Kum kabını mama ve su kaplarından ayrı bir noktaya yerleştir. İhtiyaçlarını bulabileceği sade bir düzen kurmak, çevresini daha rahat tanımasına yardımcı olur.",
                "Start with a quiet area rather than introducing every room at once. Include a comfortable bed, a safe hiding box, food and water bowls and a litter tray. Place the litter tray away from food and water. A simple layout makes it easier for your cat to find essentials and become familiar with the surroundings."
            ],
            [
                "Taşıma çantasından kendi isteğiyle çıkmasını bekle",
                "Let your cat leave the carrier voluntarily",
                "Taşıma çantasını hazırladığın odaya koyup kapısını aç. Kediyi çekerek çıkarmak veya çantayı sallamak yerine kendi isteğiyle dışarı çıkmasını bekle. Çevreyi bir süre çantanın içinden izleyebilir. İlk anda saklanmak istemesi de mümkündür. Saklandığı alan güvenliyse peşinden gitmeden ve çıkması için zorlamadan zaman tanı.",
                "Place the carrier in the prepared room and open the door. Wait for your cat to come out voluntarily instead of pulling them out or shaking the carrier. They may watch from inside for a while or choose to hide. If the hiding place is safe, allow time without following or forcing them to leave."
            ],
            [
                "İletişimi kedinin davranışlarına göre kur",
                "Let your cat’s behaviour guide interaction",
                "Kedinin yakınında sakin hareket et ve yumuşak bir sesle konuş. Sana yaklaştığında hemen kucağına almak yerine önce seni koklamasına izin ver. Uzaklaşıyorsa veya etkileşimi sürdürmek istemiyorsa ara ver. Her kedinin sevilmekten hoşlandığı biçim ve süre farklıdır. Günlük kısa ve sakin karşılaşmalar, onu sürekli ilgiye maruz bırakmaktan daha rahat bir başlangıç sağlayabilir.",
                "Move calmly and speak softly around your cat. When they approach, allow them to sniff you before trying to pick them up. Pause if they move away or seem unwilling to continue. Cats differ in how and for how long they enjoy attention. Short, calm interactions can offer a more comfortable start than constant attention."
            ],
            [
                "Evi aşamalı olarak keşfetmesini sağla",
                "Introduce the rest of the home gradually",
                "Başlangıç alanında rahat hareket etmeye başladığında diğer odaları kontrollü biçimde keşfetmesine fırsat verebilirsin. Bu sırada pencere ve balkonların güvenli olduğundan emin ol. Çamaşır makinesi, dolap içleri ve açılır kapanır koltuklar gibi saklanabileceği yerleri kullanmadan önce kontrol et. Kedinin geri dönebileceği sakin alanı erişilebilir tut.",
                "Once your cat moves comfortably around the starting area, let them explore other rooms gradually. Make sure windows and balconies are secure. Check potential hiding places, including washing machines, cupboards and reclining furniture, before using them. Keep the original quiet area accessible so your cat can return whenever they wish."
            ],
            [
                "Tırmalama, oyun ve dinlenme alanları oluştur",
                "Provide places to scratch, play and rest",
                "Tırmalama yüzeyi, güvenli oyuncaklar ve rahat bir yatak kedinin günlük yaşamının parçalarıdır. Oyuncakları onun ilgisine göre seç; küçük, kopabilir parçaları olan ürünleri kontrol et. İp ve kurdele gibi eşyaları oyun sonrasında ulaşamayacağı yerde sakla. Oyun oynamadığı zamanlarda da rahatsız edilmeden dinlenebileceği bir alanı olsun.",
                "A scratching surface, safe toys and a comfortable bed are part of everyday life for a cat. Choose toys according to their interests and check for small or detachable parts. Put string and ribbons out of reach after play. Provide a place where your cat can rest undisturbed when they are not playing."
            ],
            [
                "Evdeki diğer hayvanlarla tanışmayı aceleye getirme",
                "Take time when introducing other animals",
                "Evde başka bir hayvan varsa ilk karşılaşmayı doğrudan aynı alana bırakarak yapmak yerine ayrı alanlardan başlayan kontrollü bir süreç planla. Her hayvanın kendi dinlenme ve ihtiyaç alanına erişimi olsun. Yakınlaşmayı zorlamak yerine davranışlarını izle ve gerektiğinde araya mesafe koy. Tanışma süresinin her evde aynı olmayacağını unutma.",
                "If another animal lives at home, plan a gradual introduction beginning with separate areas instead of placing them together immediately. Each animal should have access to their own resting place and essential resources. Observe their behaviour, avoid forcing contact and create distance when needed. The time required for introductions varies between households."
            ]
        ]
    ],

    3 => [
        "baslik" => blog_ceviri(
            "Köpekle İlk Gün İçin Hazırlık",
            "Preparing for Your First Day with a Dog"
        ),
        "kategori" => "Köpek Bakımı",
        "resim" => "images/blog/kopek-ilk-gun.png",
        "ozet" => blog_ceviri(
            "Yeni köpeğinin ilk gününü sakin geçirmesi için dinlenme alanını, temel eşyalarını ve günlük düzenini önceden hazırla.",
            "Prepare a resting area, essential supplies and a daily routine to give your new dog a calm first day."
        ),
        "bolumler" => [
            [
                "Eve gelmeden önce ihtiyaçlarını tamamla",
                "Gather essential supplies before arrival",
                "Uygun boyutta bir yatak, mama ve su kapları, güvenli gezdirme ekipmanı ve birkaç dayanıklı oyuncak hazırlayabilirsin. Eşyaların köpeğinin boyutuna ve kullanımına uygun olmasına dikkat et. Önceki bakımını üstlenen kişiden kullandığı mama, günlük alışkanlıkları ve tanıdığı komutlar hakkında bilgi al. Böylece ilk gün aynı anda çok fazla değişiklik yapmak zorunda kalmazsın.",
                "Prepare a suitably sized bed, food and water bowls, secure walking equipment and a few durable toys. Make sure the items suit your dog’s size and intended use. Ask the previous caregiver about food, daily habits and familiar cues. This helps you avoid introducing too many changes on the first day."
            ],
            [
                "Sessiz bir dinlenme alanı belirle",
                "Choose a quiet resting place",
                "Yatağını evin sürekli geçiş yapılan veya çok gürültülü bir köşesine koymak yerine daha sakin bir noktaya yerleştir. Bu alanı tanımasına izin ver ve dinlenirken rahatsız edilmemesini sağla. İlk günlerde evin bütün odalarını aynı anda kullanması gerekmeyebilir. Güvenli ve sade bir başlangıç alanı, hem çevreyi tanımasını hem de senin onu gözlemlemeni kolaylaştırır.",
                "Place the bed somewhere quiet, away from busy walkways and loud activity. Let your dog become familiar with the space and rest without disturbance. They may not need access to every room immediately. A safe, simple starting area helps your dog explore and makes it easier for you to observe their behaviour."
            ],
            [
                "İlk karşılaşmaları sakin tut",
                "Keep the first meetings calm",
                "Yeni dostunu görmek isteyen çok kişi olabilir; ancak ilk günü kalabalık bir karşılama yerine sakin geçirmek daha uygun bir başlangıçtır. Evdeki kişiler sırayla ve yavaşça yaklaşabilir. Köpeği sürekli kucağa almak, sarılmak veya yüzüne çok yaklaşmak yerine kendi isteğiyle iletişim kurmasına fırsat ver. Çocuklarla etkileşim sırasında bir yetişkin yanında bulunsun.",
                "Many people may want to meet your new companion, but a quiet first day is a gentler beginning than a crowded welcome. Household members can approach slowly, one at a time. Let your dog choose to interact rather than repeatedly picking them up, hugging them or moving close to their face. An adult should supervise interactions with children."
            ],
            [
                "Günlük düzeni anlaşılır hâle getir",
                "Build a clear daily routine",
                "Dinlenme, yemek, oyun ve dışarı çıkma zamanlarında uygulanabilir bir düzen kur. Evde hangi alanları kullanabileceğini ve hangi davranışları destekleyeceğini birlikte yaşadığın kişilerle konuş. Aynı davranışa bir gün izin verip başka bir gün tepki göstermek yerine tutarlı ol. Yeni bir ortamda her şeyi hemen anlamasını bekleme; uygun davranışları sakin biçimde ödüllendir.",
                "Create a manageable routine for rest, meals, play and walks. Agree with the people you live with on accessible areas and the behaviours you want to encourage. Be consistent rather than allowing something one day and objecting the next. Do not expect your dog to understand everything immediately; calmly reward appropriate behaviour."
            ],
            [
                "İlk gezilerde ekipmanını kontrol et",
                "Check equipment before the first walks",
                "Dışarı çıkmadan önce tasma veya göğüs tasmasının uygun takıldığını ve bağlantıların sağlam olduğunu kontrol et. Köpeğin seni ve çevresini henüz tanımadığı için ilk gezilerde güvenli ekipman kullanmak önemlidir. Kalabalık ve yoğun uyaranlı yerlere gitmek yerine yönetebileceğin sakin bir başlangıç yap. Tanımadığı insanlarla veya hayvanlarla yaklaşmayı zorunlu hâle getirme.",
                "Before going outside, check that the collar or harness fits properly and all connections are secure. Reliable equipment matters while your dog is still getting to know you and the surroundings. Begin in a quiet, manageable location instead of a crowded or highly stimulating place. Do not require your dog to approach unfamiliar people or animals."
            ],
            [
                "Uyum için zaman tanı",
                "Allow time to adjust",
                "Köpeğinin ilk günkü davranışları, evde ileride göstereceği bütün davranışları temsil etmeyebilir. Yeni ses ve kokuları tanıdıkça daha farklı tepkiler gösterebilir. Nerede rahat ettiğini, hangi oyunlardan hoşlandığını ve hangi durumlarda uzaklaşmak istediğini gözlemle. Günlük düzenini bu gözlemlerle geliştirmek, birlikte yaşamı daha anlaşılır ve rahat hâle getirir.",
                "Your dog’s first-day behaviour may not represent how they will behave once settled. Their responses can change as sounds and smells become familiar. Notice where they feel comfortable, which games they enjoy and when they want more space. Use these observations to develop a routine that makes living together clearer and more comfortable."
            ]
        ]
    ],

    4 => [
        "baslik" => blog_ceviri(
            "Muhabbet Kuşu İçin Evde Güvenli Ortam",
            "Creating a Safe Home for a Budgie"
        ),
        "kategori" => "Kuş Bakımı",
        "resim" => "images/blog/kus-bakimi.png",
        "ozet" => blog_ceviri(
            "Kafesin yerinden oda düzenine kadar küçük hazırlıklarla muhabbet kuşun için daha güvenli bir yaşam alanı oluştur.",
            "Create a safer living space for your budgie through thoughtful cage placement and room preparation."
        ),
        "bolumler" => [
            [
                "Kafes için uygun bir yer seç",
                "Choose a suitable place for the cage",
                "Kafesi devrilmeyecek sağlam bir yüzeye yerleştir. Kapıların sürekli açılıp kapandığı, doğrudan hava akımı alan veya yoğun gürültülü bir yer yerine daha sakin bir alan seç. Gün boyunca çevresini görebilsin; ancak dinlenmek istediğinde sürekli hareketin ortasında kalmasın. Güneş alan bir noktadaysa gölgeye geçebileceği alanın da bulunmasına dikkat et.",
                "Place the cage on a stable surface where it cannot tip over. Choose a calm area away from frequently opening doors, direct draughts and loud noise. Your bird should be able to observe the surroundings without being surrounded by constant activity when resting. If sunlight reaches the cage, make sure a shaded area is also available."
            ],
            [
                "Kafes içindeki düzeni sade ve kullanışlı tut",
                "Keep the cage layout practical and uncluttered",
                "Tünek, yemlik, suluk ve oyuncakların yerleşimi kuşun hareketini engellememelidir. Kafesi çok sayıda eşyayla doldurmak yerine rahatça hareket edebileceği alan bırak. Kırılmış, keskin kenarlı veya iplikleri ayrılmış eşyaları kontrol et. Kapların kolay erişilebilir ve temizlenebilir olması günlük bakımını da kolaylaştırır.",
                "Arrange perches, food containers, water containers and toys without obstructing movement. Leave room to move comfortably instead of filling the cage with objects. Check for broken items, sharp edges and loose threads. Containers that are easy to reach and clean also make everyday care more straightforward."
            ],
            [
                "Kafes dışına çıkmadan önce odayı kontrol et",
                "Check the room before out-of-cage time",
                "Pencere ve kapıları kapat, çalışan vantilatörleri durdur ve odadaki diğer hayvanların erişimini kontrol et. Açık su kapları, sıcak içecekler ve sıcak yüzeyler gibi tehlikeleri ortadan kaldır. Kuşun girebileceği dar boşlukları gözden geçir. Kafes dışındaki zamanı gözetim altında geçir ve geri dönmesi için kapısını ulaşılabilir tut.",
                "Close windows and doors, switch off fans and control access by other animals. Remove hazards such as open water containers, hot drinks and hot surfaces. Check narrow gaps your bird could enter. Supervise time outside the cage and keep the cage entrance accessible so your bird can return."
            ],
            [
                "Duman ve yoğun kokulardan uzak bir alan oluştur",
                "Keep the living area away from smoke and strong fumes",
                "Kuşun yaşam alanını sigara dumanından, oda spreylerinden ve temizlik sırasında oluşan yoğun kokulardan uzak tut. Mutfak gibi sıcak yüzeylerin ve pişirme dumanlarının bulunduğu alanlar yerine ayrı bir yaşam alanı planla. Temizlik yaparken kuşu ve kullandığı eşyaları koruyacak bir düzen oluştur; ürünleri doğrudan yakınında püskürtme.",
                "Keep your bird’s living area away from cigarette smoke, room sprays and strong cleaning fumes. Plan a separate space away from kitchens, hot surfaces and cooking fumes. When cleaning, protect both your bird and their belongings, and avoid spraying products nearby."
            ],
            [
                "Günlük temizlik için küçük bir rutin kur",
                "Build a simple cleaning routine",
                "Yem ve su kaplarını düzenli olarak temizle ve yeniden kullanmadan önce uygun biçimde durula. Kafes tabanını, tünekleri ve oyuncakları da kontrol listene ekle. Temizlik sırasında bağlantıların gevşeyip gevşemediğini ve kapıların sağlam kapanıp kapanmadığını incele. Günlük kısa kontroller, biriken işleri fark etmeyi kolaylaştırır.",
                "Clean food and water containers regularly and rinse them properly before reuse. Include the cage base, perches and toys in your checklist. During cleaning, check for loose fittings and make sure the doors close securely. Brief daily checks help you notice tasks before they build up."
            ],
            [
                "İletişimde sabırlı ve sakin davran",
                "Interact calmly and patiently",
                "Yeni gelen kuşunun seni ve evdeki sesleri tanıması zaman alabilir. Kafesin yanında ani hareketler yapmak veya onu yakalamaya çalışmak yerine sakin bir sesle konuşarak başlayabilirsin. Uzaklaşmak istediğinde iletişime ara ver. Dinlenme zamanlarında daha sessiz bir ortam sağlayarak günlük düzeninin oluşmasına yardımcı ol.",
                "A newly arrived bird may need time to become familiar with you and household sounds. Begin by speaking calmly rather than making sudden movements or trying to catch them. Pause interaction when your bird wants to move away. A quieter environment during resting times can help establish a daily routine."
            ]
        ]
    ],

    5 => [
        "baslik" => blog_ceviri(
            "Sahiplenme Görüşmesinde Neler Sorulabilir?",
            "What Questions Should You Ask Before Adoption?"
        ),
        "kategori" => "Sahiplenme",
        "resim" => "images/blog/beslenme.png",
        "ozet" => blog_ceviri(
            "Günlük alışkanlıklar, bakım geçmişi ve ihtiyaçlar hakkında doğru sorular sorarak sahiplenme sürecine hazırlan.",
            "Prepare for adoption by asking useful questions about daily habits, care history and individual needs."
        ),
        "bolumler" => [
            [
                "Günlük düzeni hakkında bilgi iste",
                "Ask about the daily routine",
                "Hayvanın gün içinde ne zaman yemek yediğini, nerede dinlendiğini ve hangi etkinliklerden hoşlandığını sorabilirsin. Köpek için dışarı çıkma düzeni, kedi için kum kabı alışkanlıkları gibi bilgiler ilk günlerini planlamana yardımcı olur. Verilen bilgilerin önceki ev koşullarına ait olduğunu ve yeni ortamda bazı değişiklikler görülebileceğini de aklında tut.",
                "Ask when the animal eats, where they rest and which activities they enjoy. Information such as a dog’s walking routine or a cat’s litter tray habits can help you plan the first few days. Remember that these observations come from the previous living environment, and some habits may change in a new home."
            ],
            [
                "Beslenme alışkanlıklarını öğren",
                "Learn about feeding habits",
                "Kullandığı mamanın adını, günlük beslenme düzenini ve mama kabına yaklaşımını öğren. Birden fazla hayvanla yaşıyorsa yemek sırasında ayrı alan kullanıp kullanmadığını sor. Böylece yeni evde ihtiyaçlarını hazırlarken daha somut bilgilere sahip olursun. Özel bir beslenme planı varsa bunun kimin yönlendirmesiyle uygulandığını ve varsa ilgili kayıtları iste.",
                "Find out which food is used, the feeding routine and how the animal behaves around the food bowl. If they live with other animals, ask whether they eat in separate areas. These details help you prepare at home. If there is a special feeding plan, ask who recommended it and request any relevant records."
            ],
            [
                "Bakım ve veteriner kayıtlarını sor",
                "Ask for care and veterinary records",
                "Varsa veteriner kayıtlarını, kimlik bilgilerini ve devam eden bakım planını öğren. Daha önce kullanılan ilaçlar veya özel bir bakım ihtiyacı hakkında bilgi veriliyorsa yazılı kayıtları da görmek isteyebilirsin. Amaç kendi başına bir değerlendirme yapmak yerine bilgileri eksiksiz devralmaktır. Kayıtların bulunmaması durumunda da hangi bilgilerin bilindiğini ve hangilerinin bilinmediğini açıkça konuş.",
                "Ask for available veterinary records, identification details and any ongoing care plan. If previous medication or special care needs are mentioned, request written information where available. The aim is to receive complete information rather than make your own medical assessment. If records are missing, clarify what is known and what remains unknown."
            ],
            [
                "İnsanlarla ve diğer hayvanlarla ilişkisini öğren",
                "Ask about interactions with people and other animals",
                "Çocuklarla, misafirlerle veya diğer hayvanlarla daha önce aynı ortamda bulunup bulunmadığını sor. Kalabalık yerlerde, yalnız kaldığında veya yüksek sesler duyduğunda nasıl davrandığı hakkında örnekler iste. Genel ifadeler yerine yaşanmış durumları konuşmak daha açıklayıcı olabilir. Bununla birlikte hiçbir görüşme, her ortamda nasıl davranacağını kesin olarak göstermeyecektir.",
                "Ask whether the animal has lived or spent time with children, visitors or other animals. Request examples of behaviour in crowds, when alone or around loud sounds. Real situations can be more informative than general descriptions. However, no conversation can predict exactly how an animal will behave in every environment."
            ],
            [
                "Kendi ev koşullarını açıkça anlat",
                "Explain your own home environment openly",
                "Gün içinde evde bulunma süreni, birlikte yaşadığın kişileri, varsa diğer hayvanları ve bakım için ayırabileceğin zamanı paylaş. Seyahat ettiğinde veya yoğun olduğunda nasıl bir bakım planın olduğunu anlat. Sadece hayvan hakkında soru sormak yerine kendi koşullarını da açıklamak, iki tarafın beklentilerini daha iyi anlamasına yardımcı olur.",
                "Explain how much time you spend at home, who you live with, whether there are other animals and how much time you can devote to care. Describe your plans for travel and busy periods. Sharing your circumstances as well as asking questions helps both sides understand expectations."
            ],
            [
                "Teslim ve iletişim sürecini netleştir",
                "Clarify the handover and future contact",
                "Hayvanın hangi gün, nerede ve nasıl teslim edileceğini önceden konuş. Birlikte gelecek eşyaları, paylaşılacak kayıtları ve ilk günlerde soru sormak için kullanabileceğin iletişim yolunu belirle. Uygulanacak bir sahiplenme belgesi veya takip süreci varsa içeriğini okuyup anlamadığın noktaları sor. Karar vermek için zamana ihtiyacın olduğunda bunu açıkça söyle.",
                "Discuss when, where and how the handover will take place. Agree on which belongings and records will come with the animal and how you can ask questions during the first few days. Read any adoption agreement or follow-up arrangements and ask about anything unclear. Say openly if you need more time to decide."
            ]
        ]
    ],

    6 => [
        "baslik" => blog_ceviri(
            "Evcil Dostunun Bakımını Birlikte Planlamak",
            "Planning Your Pet’s Care Together"
        ),
        "kategori" => "Pet Dünyası",
        "resim" => "images/blog/oyun.png",
        "ozet" => blog_ceviri(
            "Beslenme, temizlik, oyun ve dinlenme ihtiyaçlarını paylaşarak sürdürülebilir bir bakım düzeni oluştur.",
            "Create a sustainable care routine by sharing responsibility for feeding, cleaning, play and rest."
        ),
        "bolumler" => [
            [
                "Günlük bakım görevlerini listele",
                "List daily care tasks",
                "İlk adım, gün içinde yapılması gereken işleri görünür hâle getirmektir. Su kontrolü, beslenme, yaşam alanının temizliği, oyun ve gerekiyorsa dışarı çıkma gibi görevleri yazabilirsin. Her hayvanın ihtiyaçları aynı olmadığı için listeyi kendi dostunun düzenine göre oluştur. Gereksiz ayrıntılarla zorlaştırmak yerine günlük hayatta uygulanabilecek kısa bir plan hazırla.",
                "Begin by making everyday tasks visible. List water checks, feeding, cleaning, play and walks where needed. Since animals have different needs, build the list around your own companion’s routine. Keep the plan brief and practical enough to use in everyday life."
            ],
            [
                "Her görevin sorumlusunu belirle",
                "Agree on who handles each task",
                "Bir işin herkes tarafından yapılacağını düşünmek bazen kimsenin üstlenmemesine yol açabilir. Bu yüzden hangi görevi kimin yapacağını açıkça konuş. Aynı görevi sırayla üstleniyorsanız günleri belirleyebilirsiniz. Çocuklar yaşlarına uygun işlere katılabilir; ancak bakımın tamamlandığını takip edecek bir yetişkin bulunmalıdır. Bir kişi evde olmadığında görevi devralacak kişiyi de belirle.",
                "Assuming everyone will handle a task can sometimes mean nobody does it. Agree clearly on responsibilities. If you take turns, assign particular days. Children can help with age-appropriate tasks, while an adult checks that care is completed. Decide who will take over when the usual person is away."
            ],
            [
                "Yapılan işleri birbirinize bildirin",
                "Let each other know when tasks are completed",
                "Mama verildiğinde veya dışarı çıkıldığında diğer kişilere haber vermek karışıklıkları azaltır. Buzdolabına asılan küçük bir çizelge ya da ortak bir not sayfası yeterli olabilir. Böylece aynı işin gereksiz yere tekrarlanmasını veya unutulmasını önleyebilirsiniz. Takip yönteminin herkes tarafından kolayca kullanılabilir olması, ayrıntılı görünmesinden daha önemlidir.",
                "Telling others when feeding or a walk is finished reduces confusion. A small chart on the fridge or a shared note may be enough. This helps prevent tasks from being repeated unnecessarily or forgotten. Choose a tracking method that everyone can use easily."
            ],
            [
                "Oyun ve dinlenmeyi de plana kat",
                "Include play and rest in the plan",
                "Bakım planı yalnızca mama ve temizlikten oluşmaz. Birlikte oyun oynamak, çevreyi keşfetmek ve sakin zaman geçirmek de günlük yaşamın parçalarıdır. Hayvanın ilgisine ve isteğine göre kısa etkinlikler planlayabilirsiniz. Oyunu sürdürmek istemediğinde ara verin ve dinlenme alanında rahatsız edilmemesini sağlayın. Kullanılan oyuncakları düzenli olarak kontrol ederek hasarlı olanları ayırın.",
                "Care includes more than feeding and cleaning. Playing together, exploring and spending quiet time are also parts of daily life. Plan short activities based on your companion’s interests and willingness. Stop when they no longer want to play and protect their resting space from disturbance. Check toys regularly and remove damaged items."
            ],
            [
                "Seyahat ve yoğun günler için yedek plan hazırla",
                "Prepare a backup plan for travel and busy days",
                "Tatil, sınav veya yoğun iş dönemlerinde bakımı kimin üstleneceğini önceden belirlemek son dakika karışıklığını azaltır. Bakımı devralacak kişiye günlük düzeni, kullanılan eşyaların yerini ve iletişim bilgilerini açıkça anlat. Mama miktarı veya özel uygulamalar için kendi tahminini yapmak yerine mevcut bakım planını paylaş. Gerekli malzemeleri kolay bulunabilecek bir yerde hazır tut.",
                "Decide in advance who will provide care during holidays, exams or busy working periods. Explain the daily routine, where supplies are stored and how to contact you. Share the existing care plan rather than leaving the caregiver to guess food amounts or special arrangements. Keep essential supplies in an accessible place."
            ],
            [
                "Planı birlikte gözden geçir",
                "Review the plan together",
                "Hazırladığınız bakım düzeninin günlük yaşamda işe yarayıp yaramadığını zaman zaman konuşun. Unutulan görevler, çakışan saatler veya bir kişiye fazla yüklenen sorumluluklar varsa planı yeniden düzenleyin. Hayvanın alışkanlıkları ve evdeki yaşam koşulları değiştikçe ihtiyaçlar da değişebilir. Herkesin anlayabildiği ve sürdürebildiği bir düzen oluşturmak, bakımın devamlılığını kolaylaştırır.",
                "Regularly discuss whether the care routine works in practice. Adjust it if tasks are missed, times clash or one person carries too much responsibility. Needs may change as your companion’s habits or household circumstances change. A routine everyone understands and can maintain supports consistent care."
            ]
        ]
    ]
];

$kategori = is_string($_GET["kategori"] ?? null)
    ? trim($_GET["kategori"])
    : "Tümü";

if (!array_key_exists($kategori, $kategori_adlari)) {
    $kategori = "Tümü";
}

$arama = is_string($_GET["arama"] ?? null)
    ? trim($_GET["arama"])
    : "";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

$secili_yazi = $id && isset($yazilar[$id])
    ? $yazilar[$id]
    : null;

$bulunamadi = isset($_GET["id"]) && !$secili_yazi;

if ($bulunamadi) {
    http_response_code(404);
}

$liste = [];

foreach ($yazilar as $yazi_id => $yazi) {
    if ($kategori !== "Tümü" && $yazi["kategori"] !== $kategori) {
        continue;
    }

    $aranacak = $yazi["baslik"] . " " . $yazi["ozet"]
        . " " . $kategori_adlari[$yazi["kategori"]];

    /* Bölüm metinleri her iki dilde de aranabilir. */
    foreach ($yazi["bolumler"] as $bolum) {
        $aranacak .= " " . implode(" ", $bolum);
    }

    if (
        $arama !== ""
        && strpos(
            blog_arama_metni($aranacak),
            blog_arama_metni($arama)
        ) === false
    ) {
        continue;
    }

    $liste[$yazi_id] = $yazi;
}
?>
<!DOCTYPE html>
<html lang="<?= blog_guvenli($dil) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= blog_guvenli(
        $secili_yazi
            ? $secili_yazi["baslik"] . " | Aris Blog"
            : "Blog | Aris Pati Yuvası"
    ) ?></title>

    <style>
        * {
            box-sizing: border-box;
        }

        html {
            scrollbar-gutter: stable;
        }

        body {
            margin: 0;
            background: #f5f0e6;
            color: #284b32;
            font-family: Arial, sans-serif;
        }

        a {
            color: inherit;
        }

        a:focus-visible,
        button:focus-visible,
        input:focus-visible {
            outline: 3px solid #8fa97d;
            outline-offset: 3px;
        }

        .ilan-ust {
            position: relative;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 24px;
            min-height: 88px;
            padding: 16px 24px;
            border-bottom: 1px solid #35583b;
            background: #426b46;
        }

        .ilan-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            color: white;
            text-decoration: none;
        }

        .ilan-logo img {
            width: 54px;
            height: 54px;
            padding: 5px;
            border: 1px solid #eee5d3;
            border-radius: 12px;
            background: #fffdf8;
            object-fit: contain;
        }

        .ilan-logo strong {
            display: block;
            font-size: 20px;
            line-height: 1.3;
        }

        .ilan-logo small {
            display: block;
            margin-top: 5px;
            font-size: 11px;
            line-height: 1.4;
        }

        .ust-sag {
            display: flex;
            flex: 1;
            min-width: 0;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
        }

        .dil-secici {
            display: inline-flex;
            gap: 4px;
            padding: 3px;
            border: 1px solid rgba(255, 255, 255, .35);
            border-radius: 8px;
        }

        .dil-secici a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 37px;
            min-height: 30px;
            padding: 5px 9px;
            border-radius: 5px;
            color: white;
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
        }

        .dil-secici a:hover {
            background: #35583b;
        }

        .dil-secici a.aktif {
            background: #eee5d3;
            color: #284b32;
        }

        .ilan-menu {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 4px;
            margin-left: auto;
        }

        .ilan-menu > a,
        .kategori-baslik {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 40px;
            padding: 10px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: white;
            font: normal 14px/20px Arial, sans-serif;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
        }

        .ilan-menu > a:hover,
        .kategori-menu:hover .kategori-baslik,
        .kategori-menu:focus-within .kategori-baslik {
            background: #284b32;
        }

        .ilan-menu > a[aria-current="page"] {
            background: #eee5d3;
            color: #284b32;
            font-weight: bold;
        }

        .kategori-menu {
            position: relative;
        }

        .kategori-acilir {
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 200;
            display: none;
            width: 205px;
            max-width: calc(100vw - 36px);
            padding: 8px;
            border: 1px solid #ded6c6;
            border-radius: 12px;
            background: #fffdf8;
            box-shadow: 0 12px 28px rgba(40, 75, 50, .16);
        }

        .kategori-menu:hover .kategori-acilir,
        .kategori-menu:focus-within .kategori-acilir {
            display: block;
        }

        .kategori-acilir a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 7px;
            font-size: 14px;
            text-decoration: none;
        }

        .kategori-acilir a:hover {
            background: #e6eddf;
        }

        main {
            width: min(1200px, calc(100% - 40px));
            margin: 28px auto 60px;
        }

        .sayfa-yolu {
            margin-bottom: 22px;
            color: #687361;
            font-size: 13px;
            line-height: 1.7;
        }

        .sayfa-yolu a {
            text-decoration: none;
        }

        .blog-giris {
            margin-bottom: 28px;
            padding: 35px;
            border: 1px solid #d7dfcc;
            border-radius: 16px;
            background: #e6eddf;
        }

        .blog-giris small {
            color: #426b46;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .blog-giris h1 {
            margin: 12px 0;
            font-size: clamp(28px, 4vw, 42px);
        }

        .blog-giris p {
            margin: 0;
            color: #687361;
            line-height: 1.8;
        }

        .blog-duzen {
            display: grid;
            grid-template-columns: 225px minmax(0, 1fr);
            align-items: start;
            gap: 25px;
        }

        .yan-panel {
            padding: 20px;
            border: 1px solid #dedccd;
            border-radius: 12px;
            background: #fffdf8;
        }

        .yan-panel h2 {
            margin: 0 0 15px;
            font-size: 18px;
        }

        .blog-kategori {
            display: block;
            margin-bottom: 6px;
            padding: 12px;
            border-radius: 8px;
            color: #687361;
            font-size: 14px;
            text-decoration: none;
        }

        .blog-kategori:hover,
        .blog-kategori.secili {
            background: #e6eddf;
            color: #284b32;
        }

        .blog-kategori.secili {
            font-weight: bold;
        }

        .blog-sonuclar {
            min-width: 0;
        }

        .arama {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
        }

        .arama input {
            flex: 1;
            min-width: 0;
            padding: 13px;
            border: 1px solid #dedccd;
            border-radius: 8px;
            background: #fffdf8;
            color: #284b32;
            font: inherit;
        }

        .arama button {
            padding: 12px 18px;
            border: 0;
            border-radius: 8px;
            background: #426b46;
            color: white;
            font: inherit;
            cursor: pointer;
        }

        .arama button:hover {
            background: #284b32;
        }

        .yazi-sayisi {
            margin: 0 0 18px;
            color: #687361;
            font-size: 13px;
        }

        .blog-kartlar {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .blog-kart {
            display: flex;
            flex-direction: column;
            min-width: 0;
            overflow: hidden;
            border: 1px solid #dedccd;
            border-radius: 12px;
            background: #fffdf8;
        }

        .blog-kart:hover {
            box-shadow: 0 8px 24px rgba(40, 75, 50, .12);
        }

        .kart-resim {
            display: block;
            background: #e6eddf;
        }

        .kart-resim img {
            display: block;
            width: 100%;
            aspect-ratio: 4 / 3;
            object-fit: cover;
        }

        .kart-icerik {
            display: flex;
            flex-direction: column;
            flex: 1;
            padding: 20px;
        }

        .yazi-kategori {
            align-self: flex-start;
            display: inline-block;
            padding: 5px 8px;
            border-radius: 6px;
            background: #e6eddf;
            color: #426b46;
            font-size: 11px;
        }

        .kart-icerik h2 {
            margin: 14px 0 12px;
            font-size: 18px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .kart-icerik h2 a {
            text-decoration: none;
        }

        .kart-icerik p {
            margin: 0 0 20px;
            color: #687361;
            font-size: 13px;
            line-height: 1.8;
        }

        .incele {
            display: block;
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #dedccd;
            color: #426b46;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
        }

        .incele:hover {
            text-decoration: underline;
        }

        .bos-sonuc {
            grid-column: 1 / -1;
            padding: 35px;
            border: 1px solid #dedccd;
            border-radius: 12px;
            background: #fffdf8;
            line-height: 1.8;
            text-align: center;
        }

        .yazi-detay {
            max-width: 850px;
            margin: auto;
            overflow: hidden;
            border: 1px solid #dedccd;
            border-radius: 16px;
            background: #fffdf8;
        }

        .detay-resim {
            display: block;
            width: 100%;
            max-height: 440px;
            object-fit: cover;
            background: #e6eddf;
        }

        .detay-icerik {
            padding: 35px;
        }

        .detay-icerik h1 {
            margin: 18px 0;
            font-size: clamp(25px, 4vw, 36px);
            line-height: 1.4;
        }

        .detay-ozet {
            margin: 25px 0;
            padding: 18px;
            border-left: 4px solid #426b46;
            background: #e6eddf;
            line-height: 1.8;
        }

        .detay-icerik h2 {
            margin: 34px 0 12px;
            font-size: 21px;
            line-height: 1.5;
        }

        .detay-icerik p {
            margin: 0;
            color: #53634e;
            line-height: 1.9;
            overflow-wrap: anywhere;
        }

        .geri-buton {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 18px;
            border-radius: 8px;
            background: #426b46;
            color: white;
            text-decoration: none;
        }

        .geri-buton:hover {
            background: #284b32;
        }

        footer {
            padding: 25px;
            border-top: 1px solid #dedccd;
            color: #687361;
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 1100px) {
            .ust-sag {
                flex-basis: 100%;
            }

            .ilan-menu {
                width: 100%;
            }
        }

        @media (max-width: 1000px) {
            .blog-kartlar {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 750px) {
            .blog-duzen {
                grid-template-columns: 1fr;
            }

            .yan-panel {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }

            .yan-panel h2 {
                width: 100%;
            }

            .blog-kategori {
                margin: 0;
            }

            .blog-giris,
            .detay-icerik {
                padding: 24px;
            }
        }

        @media (max-width: 700px) {
            .ilan-ust {
                padding: 15px 18px;
                gap: 16px;
            }

            .ilan-menu > a,
            .kategori-baslik {
                padding: 8px;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .blog-kartlar {
                grid-template-columns: 1fr;
            }

            .detay-icerik {
                padding: 22px 18px;
            }
        }
    </style>
<link rel="stylesheet" href="site-ust.css?v=20261102-1">
</head>

<body>

<?php require __DIR__ . "/site-ust.php"; ?>

<main>
    <div class="sayfa-yolu">
        <a href="index.php">
            <?= blog_ceviri("Ana Sayfa", "Home") ?>
        </a>
        ›
        <a href="blog.php">Blog</a>

        <?php if ($secili_yazi): ?>
            › <?= blog_guvenli(
                $kategori_adlari[$secili_yazi["kategori"]]
            ) ?>
        <?php endif; ?>
    </div>

    <?php if ($secili_yazi): ?>

        <article class="yazi-detay">
            <img
                src="<?= blog_guvenli($secili_yazi["resim"]) ?>"
                alt="<?= blog_guvenli($secili_yazi["baslik"]) ?>"
                class="detay-resim"
            >

            <div class="detay-icerik">
                <span class="yazi-kategori">
                    <?= blog_guvenli(
                        $kategori_adlari[$secili_yazi["kategori"]]
                    ) ?>
                </span>

                <h1><?= blog_guvenli($secili_yazi["baslik"]) ?></h1>

                <div class="detay-ozet">
                    <?= blog_guvenli($secili_yazi["ozet"]) ?>
                </div>

                <?php foreach ($secili_yazi["bolumler"] as $bolum): ?>
                    <section>
                        <h2>
                            <?= blog_guvenli(
                                blog_ceviri($bolum[0], $bolum[1])
                            ) ?>
                        </h2>

                        <p>
                            <?= blog_guvenli(
                                blog_ceviri($bolum[2], $bolum[3])
                            ) ?>
                        </p>
                    </section>
                <?php endforeach; ?>

                <a href="blog.php" class="geri-buton">
                    <?= blog_ceviri(
                        "← Tüm Blog Yazıları",
                        "← All Blog Posts"
                    ) ?>
                </a>
            </div>
        </article>

    <?php elseif ($bulunamadi): ?>

        <div class="bos-sonuc">
            <h1>
                <?= blog_ceviri("Yazı bulunamadı.", "Post not found.") ?>
            </h1>

            <a href="blog.php">
                <?= blog_ceviri(
                    "Blog yazılarına dön",
                    "Return to blog posts"
                ) ?>
            </a>
        </div>

    <?php else: ?>

        <section class="blog-giris">
            <small>ARİS BLOG</small>

            <h1>
                <?= blog_ceviri(
                    "Minik dostlar, faydalı bilgiler.",
                    "Little companions, helpful advice."
                ) ?>
            </h1>

            <p>
                <?= blog_ceviri(
                    "Sahiplenme, bakım ve birlikte yaşam hakkında yazılarımızı keşfet.",
                    "Explore our articles about adoption, everyday care and living together."
                ) ?>
            </p>
        </section>

        <div class="blog-duzen">
            <aside
                class="yan-panel"
                aria-label="<?= blog_ceviri(
                    "Blog kategorileri",
                    "Blog categories"
                ) ?>"
            >
                <h2>
                    <?= blog_ceviri("Kategoriler", "Categories") ?>
                </h2>

                <?php foreach ($kategori_adlari as $deger => $gorunen_ad): ?>
                    <a
                        href="blog.php?<?= blog_guvenli(http_build_query([
                            "kategori" => $deger,
                            "arama" => $arama
                        ])) ?>"
                        class="blog-kategori <?= $kategori === $deger ? "secili" : "" ?>"
                        <?= $kategori === $deger ? 'aria-current="page"' : "" ?>
                    >
                        <?= blog_guvenli($gorunen_ad) ?>
                    </a>
                <?php endforeach; ?>
            </aside>

            <section
                class="blog-sonuclar"
                aria-label="<?= blog_ceviri("Blog yazıları", "Blog posts") ?>"
            >
                <form action="blog.php" method="GET" class="arama">
                    <input
                        type="hidden"
                        name="kategori"
                        value="<?= blog_guvenli($kategori) ?>"
                    >

                    <input
                        type="search"
                        name="arama"
                        value="<?= blog_guvenli($arama) ?>"
                        placeholder="<?= blog_ceviri(
                            "Blog yazılarında ara...",
                            "Search blog posts..."
                        ) ?>"
                        aria-label="<?= blog_ceviri(
                            "Blog yazılarında ara",
                            "Search blog posts"
                        ) ?>"
                    >

                    <button type="submit">
                        <?= blog_ceviri("Ara", "Search") ?>
                    </button>
                </form>

                <p class="yazi-sayisi">
                    <?php
                    $adet = count($liste);

                    if ($dil === "tr") {
                        echo $adet . " yazı bulundu.";
                    } else {
                        echo $adet . ($adet === 1
                            ? " post found."
                            : " posts found.");
                    }
                    ?>
                </p>

                <div class="blog-kartlar">
                    <?php foreach ($liste as $yazi_id => $yazi): ?>
                        <article class="blog-kart">
                            <a
                                href="blog.php?id=<?= (int)$yazi_id ?>"
                                class="kart-resim"
                                aria-label="<?= blog_guvenli($yazi["baslik"]) ?>"
                            >
                                <img
                                    src="<?= blog_guvenli($yazi["resim"]) ?>"
                                    alt="<?= blog_guvenli($yazi["baslik"]) ?>"
                                    loading="lazy"
                                >
                            </a>

                            <div class="kart-icerik">
                                <span class="yazi-kategori">
                                    <?= blog_guvenli(
                                        $kategori_adlari[$yazi["kategori"]]
                                    ) ?>
                                </span>

                                <h2>
                                    <a href="blog.php?id=<?= (int)$yazi_id ?>">
                                        <?= blog_guvenli($yazi["baslik"]) ?>
                                    </a>
                                </h2>

                                <p><?= blog_guvenli($yazi["ozet"]) ?></p>

                                <a
                                    href="blog.php?id=<?= (int)$yazi_id ?>"
                                    class="incele"
                                >
                                    <?= blog_ceviri(
                                        "Detaylı İncele →",
                                        "Read More →"
                                    ) ?>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <?php if (!$liste): ?>
                        <div class="bos-sonuc">
                            <h2>
                                <?= blog_ceviri(
                                    "Aradığın yazı bulunamadı.",
                                    "No matching posts were found."
                                ) ?>
                            </h2>

                            <p>
                                <?= blog_ceviri(
                                    "Başka bir kelime veya kategori deneyebilirsin.",
                                    "Try another search term or category."
                                ) ?>
                            </p>

                            <a href="blog.php">
                                <?= blog_ceviri(
                                    "Tüm yazıları göster",
                                    "Show all posts"
                                ) ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

    <?php endif; ?>
</main>

<footer>
    © <?= date("Y") ?> Aris Pati Yuvası ·
    <?= blog_ceviri(
        "Her can dostuna bir yuva.",
        "A loving home for every pet."
    ) ?>
</footer>

</body>
</html>