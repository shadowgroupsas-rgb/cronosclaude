<?php
/**
 * Sistema SEO — Genera meta tags, structured data y snippets de tracking
 * Se incluye en el <head> de todas las páginas públicas
 */

/**
 * Obtiene la configuración SEO para una página específica
 */
function getSeoConfig(string $page): array {
    $row = dbQueryOne(
        'SELECT * FROM ' . DB_PREFIX . 'seo WHERE page = ?',
        [$page]
    );

    // Valores por defecto si no existe configuración
    $defaults = [
        'meta_title'       => getSetting('site_name', 'La Chingada') . ' - Restaurante Mexicano en Cartagena',
        'meta_description' => getSetting('site_description', 'Auténtica comida mexicana en el corazón de Cartagena, Colombia. Sabores, colores y tradición que te transportarán directo a México.'),
        'meta_keywords'    => 'restaurante mexicano cartagena, comida mexicana colombia, tacos cartagena, burritos cartagena',
        'og_title'         => '',
        'og_description'   => '',
        'og_image'         => getSetting('og_default_image', ''),
        'twitter_card'     => 'summary_large_image',
        'canonical_url'    => '',
    ];

    return array_merge($defaults, $row ?: []);
}

/**
 * Genera todos los meta tags SEO para una página
 */
function renderSeoHead(string $page, array $overrides = []): void {
    $seo      = getSeoConfig($page);
    $settings = getSettings([
        'site_name', 'site_url', 'site_description',
        'ga4_id', 'gtm_id', 'fb_pixel_id', 'tiktok_pixel_id',
        'google_ads_snippet', 'gsc_verification', 'head_custom_code',
        'restaurant_phone', 'restaurant_address', 'restaurant_lat',
        'restaurant_lng', 'restaurant_hours_schema',
    ]);

    // Aplicar overrides (útil para páginas dinámicas)
    $seo = array_merge($seo, $overrides);

    $siteUrl    = rtrim(SITE_URL, '/');
    $siteName   = $settings['site_name'] ?: 'La Chingada';
    $title      = $seo['meta_title'] ?: $siteName;
    $desc       = $seo['meta_description'] ?: '';
    $keywords   = $seo['meta_keywords'] ?: '';
    $canonical  = $seo['canonical_url'] ?: $siteUrl . '/' . ($page === 'home' ? '' : $page);
    $ogTitle    = $seo['og_title'] ?: $title;
    $ogDesc     = $seo['og_description'] ?: $desc;
    $ogImage    = $seo['og_image'] ? (strpos($seo['og_image'], 'http') === 0 ? $seo['og_image'] : $siteUrl . '/uploads/' . $seo['og_image']) : '';

    echo '<title>' . e($title) . '</title>' . "\n";
    echo '<meta name="description" content="' . e($desc) . '">' . "\n";

    if ($keywords) {
        echo '<meta name="keywords" content="' . e($keywords) . '">' . "\n";
    }

    echo '<link rel="canonical" href="' . e($canonical) . '">' . "\n";

    // Open Graph
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:site_name" content="' . e($siteName) . '">' . "\n";
    echo '<meta property="og:title" content="' . e($ogTitle) . '">' . "\n";
    echo '<meta property="og:description" content="' . e($ogDesc) . '">' . "\n";
    echo '<meta property="og:url" content="' . e($canonical) . '">' . "\n";
    if ($ogImage) {
        echo '<meta property="og:image" content="' . e($ogImage) . '">' . "\n";
        echo '<meta property="og:image:width" content="1200">' . "\n";
        echo '<meta property="og:image:height" content="630">' . "\n";
    }

    // Twitter Card
    $twitterCard = $seo['twitter_card'] ?: 'summary_large_image';
    echo '<meta name="twitter:card" content="' . e($twitterCard) . '">' . "\n";
    echo '<meta name="twitter:title" content="' . e($ogTitle) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . e($ogDesc) . '">' . "\n";
    if ($ogImage) {
        echo '<meta name="twitter:image" content="' . e($ogImage) . '">' . "\n";
    }

    // Google Search Console verification
    if (!empty($settings['gsc_verification'])) {
        echo '<meta name="google-site-verification" content="' . e($settings['gsc_verification']) . '">' . "\n";
    }

    // Google Tag Manager (en <head>)
    if (!empty($settings['gtm_id'])) {
        $gtmId = e($settings['gtm_id']);
        echo <<<GTM
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{$gtmId}');</script>
<!-- End Google Tag Manager -->

GTM;
    }

    // Google Analytics 4
    if (!empty($settings['ga4_id'])) {
        $ga4Id = e($settings['ga4_id']);
        echo <<<GA4
<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id={$ga4Id}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '{$ga4Id}');
</script>
<!-- End Google Analytics 4 -->

GA4;
    }

    // Meta Pixel de Facebook
    if (!empty($settings['fb_pixel_id'])) {
        $fbPixelId = e($settings['fb_pixel_id']);
        echo <<<FBPIXEL
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{$fbPixelId}');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id={$fbPixelId}&ev=PageView&noscript=1"/></noscript>
<!-- End Meta Pixel Code -->

FBPIXEL;
    }

    // TikTok Pixel
    if (!empty($settings['tiktok_pixel_id'])) {
        $ttPixelId = e($settings['tiktok_pixel_id']);
        echo <<<TTPIXEL
<!-- TikTok Pixel Code -->
<script>
!function (w, d, t) {
  w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
  ttq.load('{$ttPixelId}');
  ttq.page();
}(window, document, 'ttq');
</script>
<!-- End TikTok Pixel Code -->

TTPIXEL;
    }

    // Google Ads snippet
    if (!empty($settings['google_ads_snippet'])) {
        echo "<!-- Google Ads -->\n";
        echo $settings['google_ads_snippet'] . "\n";
        echo "<!-- End Google Ads -->\n\n";
    }

    // Código personalizado del <head>
    if (!empty($settings['head_custom_code'])) {
        echo "<!-- Código personalizado head -->\n";
        echo $settings['head_custom_code'] . "\n";
        echo "<!-- Fin código personalizado head -->\n\n";
    }
}

/**
 * Genera el Schema.org JSON-LD para el restaurante
 */
function renderRestaurantSchema(): void {
    $s = getSettings([
        'site_name', 'site_description', 'restaurant_address',
        'restaurant_phone', 'restaurant_email', 'restaurant_lat',
        'restaurant_lng', 'restaurant_hours_schema', 'site_url',
        'og_default_image',
    ]);

    $hoursSpec = [];
    if (!empty($s['restaurant_hours_schema'])) {
        $hoursSpec = json_decode($s['restaurant_hours_schema'], true) ?: [];
    }

    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Restaurant',
        'name'        => $s['site_name'] ?: 'La Chingada',
        'description' => $s['site_description'] ?: '',
        'url'         => SITE_URL,
        'telephone'   => $s['restaurant_phone'] ?: '',
        'email'       => $s['restaurant_email'] ?: '',
        'address'     => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $s['restaurant_address'] ?: '',
            'addressLocality' => 'Cartagena',
            'addressRegion'   => 'Bolívar',
            'addressCountry'  => 'CO',
        ],
        'servesCuisine' => ['Mexican', 'Mexicana', 'Tacos', 'Burritos'],
        'priceRange'    => '$$',
        'currenciesAccepted' => 'COP',
        'paymentAccepted'    => 'Cash, Credit Card, Debit Card',
        'menu'  => SITE_URL . '/menu',
        'hasMap' => SITE_URL . '/contacto',
        'image' => $s['og_default_image'] ? SITE_URL . '/uploads/' . $s['og_default_image'] : '',
    ];

    if (!empty($s['restaurant_lat']) && !empty($s['restaurant_lng'])) {
        $schema['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float)$s['restaurant_lat'],
            'longitude' => (float)$s['restaurant_lng'],
        ];
    }

    if (!empty($hoursSpec)) {
        $schema['openingHoursSpecification'] = $hoursSpec;
    }

    echo '<script type="application/ld+json">' . "\n";
    echo json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    echo "\n" . '</script>' . "\n";
}

/**
 * Genera scripts para antes del </body>
 */
function renderBodyScripts(): void {
    $settings = getSettings(['gtm_id', 'body_custom_code']);

    // GTM noscript (va inmediatamente después de <body>)
    if (!empty($settings['gtm_id'])) {
        $gtmId = e($settings['gtm_id']);
        echo <<<GTM_NOSCRIPT
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={$gtmId}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

GTM_NOSCRIPT;
    }

    // Código personalizado antes de </body>
    if (!empty($settings['body_custom_code'])) {
        echo "<!-- Código personalizado body -->\n";
        echo $settings['body_custom_code'] . "\n";
        echo "<!-- Fin código personalizado body -->\n";
    }
}
