# WhiteLabelServices WHMCS Modülü

WhiteLabelServices VPS hizmetlerini WHMCS üzerinden sağlamak ve yönetmek için sunucu modülü, yönetim paneli eklentisi ve WHMCS hook yükleyicisi.

> Modül bildirimi sürümü: `1.1.0`  
> Lisans: Proprietary

## Gereksinimler

- Çalışan bir WHMCS kurulumu
- WhiteLabelServices API adresi ve reseller kullanıcı bilgileri
- PHP cURL ve JSON desteği
- Kısa `/wls/` adresi kullanılacaksa Apache `mod_rewrite`
- Düzenli çalışan WHMCS cron görevi

Paket belirli bir WHMCS veya PHP sürümü beyan etmiyor. Canlı sisteme geçmeden önce mevcut WHMCS/PHP sürümünüzle bir test ortamında doğrulayın.

## Dosya yapısı

Depo, WHMCS kök dizinini yansıtacak şekilde hazırlanmıştır:

```text
includes/
└── hooks/
    └── whitelabelservices_loader.php
modules/
├── addons/
│   └── wlsportal/
└── servers/
    └── WhiteLabelServices/
docs/
└── apache-wls-panel-rewrite.txt
```

## Kurulum

1. WHMCS dosyalarının ve veritabanının yedeğini alın.
2. Bu depodaki `modules` ve `includes` klasörlerini, klasör yapısını koruyarak WHMCS kök dizinine yükleyin. Depo klasörünün kendisini WHMCS içine koymayın.
3. Aşağıdaki yolların oluştuğunu doğrulayın:

   ```text
   {WHMCS_ROOT}/modules/servers/WhiteLabelServices/WhiteLabelServices.php
   {WHMCS_ROOT}/modules/addons/wlsportal/wlsportal.php
   {WHMCS_ROOT}/includes/hooks/whitelabelservices_loader.php
   ```

4. Dosyaların web sunucusu tarafından okunabildiğini doğrulayın. Gereğinden geniş yazma izinleri (`777` gibi) vermeyin.

`whitelabelservices_loader.php`, WHMCS tarafından hook olarak yüklenir ve sunucu modülündeki `hooks.php` dosyasını çağırır. Bu dosya özellikle `{WHMCS_ROOT}/includes/hooks/` altında kalmalıdır.

## Sunucu bağlantısı (bir kez)

1. WHMCS yönetim alanında **System Settings > Servers** bölümünü açın.
2. Yeni sunucu ekleyin ve modül olarak **WhiteLabelServices** seçin.
3. **Hostname** alanına API sunucusunun alan adını protokol eklemeden yazın. Modül bağlantıyı HTTPS üzerinden kurar.
4. Reseller API kullanıcı adını ve parolasını WHMCS sunucu alanlarına girin.
5. **Test Connection** ile bağlantıyı doğrulayın ve sunucuyu etkinleştirin.

Gerçek kullanıcı adı, parola, erişim tokenı veya yenileme tokenı kaynak dosyalara ve Git deposuna yazılmamalıdır. Kimlik bilgileri WHMCS sunucu yapılandırması üzerinden sağlanır; API tokenları modül tarafından çalışma zamanında alınır ve yönetilir.

Sunucu bağlantısı yalnızca bir kez tanımlanır. Ürünler için ayrıca API adresi, kullanıcı bilgisi veya token girilmez; portal bu bağlantıyı kullanır ve token işlemlerini otomatik yürütür.

## WLS yönetim paneli

Paket, `modules/addons/wlsportal` altında bir yönetim paneli kısayolu içerir.

1. **System Settings > Addon Modules** bölümünden **WLS Panel** eklentisini etkinleştirin.
2. Yönetici rollerine eklenti erişim yetkisi verin.
3. Paneli WHMCS addon sayfasından açın.

## Ürünleri WLS Admin Portal'dan içe aktarma

WHMCS tarafında elle ürün açmanız veya ürünün modül alanlarını tek tek doldurmanız gerekmez.

1. **WLS Admin Portal > Ürün Fiyatlandırma** ekranını açın.
2. **Eşleşmemiş Ürünler** sekmesine geçin. Portal, WLS API'deki ürünleri otomatik olarak listeler.
3. Kullanılacak kâr marjını belirleyin.
4. İçe aktarmak istediğiniz ürünün yanındaki **Ürün Oluştur** düğmesine basın. Bu düğme portal arayüzündeki ürün içe aktarma işlemidir.
5. Ürünün **Eşleşmiş Ürünler** sekmesine geçtiğini doğrulayın.

İçe aktarma işlemi WHMCS ürününü ve gerekirse `Cloud VPS` ürün grubunu oluşturur; ürünü **WhiteLabelServices** sunucu modülüne ve WLS ürün kimliğine bağlar. Fiyat dönemleri, sunucu grubu bağlantısı ve API'den gelen yapılandırılabilir seçenekler otomatik hazırlanır. Ürün başına manuel API bağlantısı yapılmaz.

İlk içe aktarmadan sonra bir test müşterisi ve test siparişiyle oluşturma, askıya alma, yeniden etkinleştirme ve sonlandırma akışlarını doğrulayın.

## Apache kısa URL ayarı

Bu adım yalnızca Apache kullanan ve paneli `https://alanadiniz.com/wls/` adresinden açmak isteyen kurulumlar içindir.

WHMCS kök dizinindeki mevcut `.htaccess` dosyasını yedekleyin. Dosyayı değiştirmeyin veya tamamen üzerine yazmayın; `RewriteEngine On` satırından sonra ve WHMCS genel front-controller kuralından önce aşağıdaki bloğu ekleyin:

```apache
RewriteRule ^wls/?$ modules/servers/WhiteLabelServices/admin/index.php [L,QSA]
RewriteRule ^wls/(.+)$ modules/servers/WhiteLabelServices/admin/$1 [L,QSA]
```

Aynı kayıt [`docs/apache-wls-panel-rewrite.txt`](docs/apache-wls-panel-rewrite.txt) dosyasında da bulunur. Kuralları değiştirmeden kullanın.

Nginx veya IIS bu `.htaccess` kurallarını kullanmaz; eşdeğer yönlendirme web sunucusu yapılandırmasına ayrıca eklenmelidir.

## Kurulum kontrolü

- **Test Connection** başarılı oluyor.
- WLS API ürünleri **Ürün Fiyatlandırma > Eşleşmemiş Ürünler** altında listeleniyor.
- İçe aktarılan ürün **Eşleşmiş Ürünler** sekmesine geçiyor ve WHMCS ürün kaydı otomatik oluşuyor.
- `includes/hooks/whitelabelservices_loader.php` yerinde ve okunabilir.
- WHMCS cron görevi hatasız tamamlanıyor.
- Addon modülü yetkili yönetici hesabıyla açılıyor.
- Apache kısa URL kullanılıyorsa `/wls/` yolu 404 vermiyor.
- Test siparişi, canlı müşteri veya ödeme oluşturmadan önce beklenen hizmet akışını tamamlıyor.

## Sorun giderme

- **404 /wls/**: `mod_rewrite` etkinliğini, `.htaccess` desteğini (`AllowOverride`) ve kuralların WHMCS genel yönlendirmesinden önce olduğunu kontrol edin.
- **Authentication failed**: Hostname, reseller kullanıcı adı/parolası, HTTPS erişimi ve sunucu saatini kontrol edin; ardından **Test Connection** çalıştırın.
- **Hook çalışmıyor**: Loader dosyasının tam olarak `includes/hooks/` altında olduğunu ve `modules/servers/WhiteLabelServices/hooks.php` dosyasının bulunduğunu doğrulayın.
- **Kuyruk görevleri ilerlemiyor**: WHMCS cron görevinin düzenli çalıştığını ve WHMCS activity log kayıtlarını kontrol edin.
- **Ürün listesi boş**: WhiteLabelServices sunucusunun aktif olduğunu, API hesabının ürünleri görme yetkisini ve bağlantı testini kontrol edin.

Üretimde yalnızca gerektiğinde debug modunu açın; inceleme tamamlanınca tekrar kapatın.

## Güncelleme

Güncelleme öncesinde dosya ve veritabanı yedeği alın. Yeni `modules` ve `includes` içeriklerini aynı yolları koruyarak yükleyin. Mevcut `.htaccess` dosyanızı paketle değiştirmeyin; gerekiyorsa rewrite bloğunu elle birleştirin. Ardından bağlantı testi ve test siparişi akışını yeniden çalıştırın.
