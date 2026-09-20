<?php
/**
 * WAPI SaaS - PageManager Class
 * Manages editable CMS pages (Contact Us, About Us, Privacy Policy, Terms, Cookie Policy)
 */

class PageManager {
    private static $initialized = false;

    /**
     * Ensure database table exists and is seeded with defaults
     */
    public static function init() {
        if (self::$initialized) return;

        $db = Database::getInstance();

        $db->query("CREATE TABLE IF NOT EXISTS `cms_pages` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `slug` VARCHAR(50) NOT NULL UNIQUE,
            `title` VARCHAR(255) NOT NULL,
            `subtitle` TEXT DEFAULT NULL,
            `content` LONGTEXT DEFAULT NULL,
            `meta_title` VARCHAR(255) DEFAULT NULL,
            `meta_description` TEXT DEFAULT NULL,
            `extra_data` JSON DEFAULT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Seed default pages if table is empty or missing slugs
        $slugs = ['contact', 'about', 'privacy', 'terms', 'cookies'];
        foreach ($slugs as $slug) {
            $exists = $db->fetchColumn("SELECT COUNT(*) FROM `cms_pages` WHERE `slug` = ?", [$slug]);
            if (!$exists) {
                $defaults = self::getDefaults($slug);
                $db->insert('cms_pages', [
                    'slug'             => $slug,
                    'title'            => $defaults['title'],
                    'subtitle'         => $defaults['subtitle'] ?? null,
                    'content'          => $defaults['content'] ?? null,
                    'meta_title'       => $defaults['meta_title'] ?? null,
                    'meta_description' => $defaults['meta_description'] ?? null,
                    'extra_data'       => json_encode($defaults['extra_data'] ?? []),
                    'is_active'        => 1
                ]);
            }
        }

        self::$initialized = true;
    }

    /**
     * Get a page by slug with safe defaults
     */
    public static function getPage($slug) {
        self::init();
        $db = Database::getInstance();
        $row = $db->fetch("SELECT * FROM `cms_pages` WHERE `slug` = ?", [$slug]);

        $defaults = self::getDefaults($slug);

        if (!$row) {
            return $defaults;
        }

        // Decode extra_data
        $extra = [];
        if (!empty($row['extra_data'])) {
            $decoded = json_decode($row['extra_data'], true);
            if (is_array($decoded)) {
                $extra = $decoded;
            }
        }

        // Merge extra_data with defaults so new keys always exist
        if (!empty($defaults['extra_data'])) {
            $extra = array_merge($defaults['extra_data'], $extra);
        }

        return [
            'id'               => (int)$row['id'],
            'slug'             => $row['slug'],
            'title'            => $row['title'] ?: $defaults['title'],
            'subtitle'         => $row['subtitle'] ?? ($defaults['subtitle'] ?? ''),
            'content'          => $row['content'] ?? ($defaults['content'] ?? ''),
            'meta_title'       => $row['meta_title'] ?? ($defaults['meta_title'] ?? ''),
            'meta_description' => $row['meta_description'] ?? ($defaults['meta_description'] ?? ''),
            'extra_data'       => $extra,
            'is_active'        => (int)($row['is_active'] ?? 1),
            'updated_at'       => $row['updated_at'] ?? null,
        ];
    }

    /**
     * Save/update page data
     */
    public static function savePage($slug, $data) {
        self::init();
        $db = Database::getInstance();

        $title           = trim($data['title'] ?? '');
        $subtitle        = trim($data['subtitle'] ?? '');
        $content         = $data['content'] ?? '';
        $metaTitle       = trim($data['meta_title'] ?? '');
        $metaDescription = trim($data['meta_description'] ?? '');
        $extraData       = is_array($data['extra_data'] ?? null) ? $data['extra_data'] : [];
        $isActive        = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        $exists = $db->fetchColumn("SELECT COUNT(*) FROM `cms_pages` WHERE `slug` = ?", [$slug]);

        $fields = [
            'title'            => $title,
            'subtitle'         => $subtitle,
            'content'          => $content,
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDescription,
            'extra_data'       => json_encode($extraData, JSON_UNESCAPED_UNICODE),
            'is_active'        => $isActive,
        ];

        if ($exists) {
            $db->update('cms_pages', $fields, 'slug = ?', [$slug]);
        } else {
            $fields['slug'] = $slug;
            $db->insert('cms_pages', $fields);
        }

        // If contact email/phone was updated in contact page extra_data, also sync with main settings table
        if ($slug === 'contact' && !empty($extraData)) {
            $settings = new Settings();
            if (!empty($extraData['email'])) {
                $settings->set('contact_email', trim($extraData['email']), 'general', 'text');
            }
            if (!empty($extraData['phone'])) {
                $settings->set('contact_phone', trim($extraData['phone']), 'general', 'text');
            }
        }

        return true;
    }

    /**
     * Reset a page to its built-in default content
     */
    public static function resetToDefault($slug) {
        $defaults = self::getDefaults($slug);
        if (empty($defaults)) return false;

        return self::savePage($slug, $defaults);
    }

    /**
     * Get default structure and content for a given page slug
     */
    public static function getDefaults($slug) {
        $settings = new Settings();
        $contactEmail = $settings->get('contact_email', 'wapiwhatsappapi@gmail.com');
        $contactPhone = $settings->get('contact_phone', '+91 8573934013');

        switch ($slug) {
            case 'contact':
                return [
                    'slug'             => 'contact',
                    'title'            => 'Contact Us',
                    'subtitle'         => "Have questions or feedback? We'd love to hear from you.",
                    'content'          => '',
                    'meta_title'       => 'Contact Us | WAPI - WhatsApp API Platform',
                    'meta_description' => 'Contact the WAPI support team for any questions, custom pricing, or technical assistance with our WhatsApp Business API platform.',
                    'extra_data'       => [
                        'headline'         => 'Chat With Us',
                        'hq_title'         => 'Our Headquarters',
                        'hq_address'       => 'Mumbai, India',
                        'email_title'      => 'Email Support',
                        'email'            => $contactEmail,
                        'phone_title'      => 'Call Us',
                        'phone'            => $contactPhone,
                        'hours_title'      => 'Business Hours',
                        'hours'            => 'Monday - Saturday: 9:00 AM - 7:00 PM IST',
                        'hours_enabled'    => 1,
                        'whatsapp_title'   => 'Quick WhatsApp Chat',
                        'whatsapp_number'  => $contactPhone,
                        'whatsapp_enabled' => 1,
                        'map_embed_url'    => '',
                        'map_enabled'      => 0,
                        'form_title'       => 'Send Message',
                        'form_subtitle'    => 'Fill out the form below and our team will get back to you within 24 hours.',
                    ]
                ];

            case 'about':
                return [
                    'slug'             => 'about',
                    'title'            => 'About Us',
                    'subtitle'         => "Empowering Modern Business Communication",
                    'content'          => "<p class=\"lead text-secondary mb-4\">WAPI is the world's most reliable and scalable WhatsApp Business API platform, designed to help businesses of all sizes grow and succeed.</p>\n<p class=\"text-secondary\">Founded in 2026, we've helped thousands of businesses automate their communication, improve customer engagement, and drive sales through the power of WhatsApp.</p>",
                    'meta_title'       => 'About Us | WAPI - WhatsApp API Platform',
                    'meta_description' => 'Learn about WAPI, our vision, mission, and how we empower businesses worldwide with cutting-edge WhatsApp Business API automation.',
                    'extra_data'       => [
                        'headline'      => 'Empowering Modern Business Communication',
                        'image_url'     => 'assets/img/hero-image.png',
                        'vision_title'  => 'Our Vision',
                        'vision_icon'   => 'bi-eye-fill',
                        'vision_desc'   => 'To become the global standard for business-to-customer messaging and engagement.',
                        'mission_title' => 'Our Mission',
                        'mission_icon'  => 'bi-bullseye',
                        'mission_desc'  => 'To provide powerful, easy-to-use tools that bridge the gap between businesses and their customers.',
                        'values_title'  => 'Our Values',
                        'values_icon'   => 'bi-heart-fill',
                        'values_desc'   => 'Transparency, innovation, and customer-first thinking in everything we build.',
                    ]
                ];

            case 'privacy':
                return [
                    'slug'             => 'privacy',
                    'title'            => 'Privacy Policy',
                    'subtitle'         => 'Effective Date: October 2026',
                    'content'          => '<h4 class="fw-bold mt-4 mb-3">1. Information We Collect</h4>
<p class="text-secondary">We collect personal information that you provide directly to us when you create an account, use our services, or communicate with us.</p>
<ul>
    <li class="text-secondary mb-2">Account information like name, email, and password.</li>
    <li class="text-secondary mb-2">Payment information processed through our secure payment providers.</li>
    <li class="text-secondary mb-2">WhatsApp message data necessary for providing our API services.</li>
</ul>

<h4 class="fw-bold mt-4 mb-3">2. How We Use Your Information</h4>
<p class="text-secondary">We use the information we collect to operate, maintain, and provide the features and functionality of our WAPI platform, including sending alerts, managing subscriptions, and providing customer support.</p>

<h4 class="fw-bold mt-4 mb-3">3. Data Security</h4>
<p class="text-secondary">We implement a variety of security measures to maintain the safety of your personal information. Your sensitive data is encrypted using industry-standard protocols.</p>

<h4 class="fw-bold mt-4 mb-3">4. Cookies</h4>
<p class="text-secondary">We use cookies to improve your user experience and for analytical purposes. You can disable cookies in your browser settings if you wish.</p>

<h4 class="fw-bold mt-4 mb-3">5. Third-Party Services</h4>
<p class="text-secondary">We use third-party services for payments (Razorpay/Stripe) and infrastructure (WhatsApp Cloud API provided by Meta). These services have their own privacy policies.</p>

<h4 class="fw-bold mt-4 mb-3">6. Contact Us</h4>
<p class="text-secondary">If you have any questions about this Privacy Policy, please contact us at ' . htmlspecialchars($contactEmail) . '.</p>',
                    'meta_title'       => 'Privacy Policy | WAPI',
                    'meta_description' => 'Read our Privacy Policy to understand how WAPI collects, uses, protects, and handles your personal information and WhatsApp data.',
                    'extra_data'       => [
                        'effective_date' => 'October 2026'
                    ]
                ];

            case 'terms':
                return [
                    'slug'             => 'terms',
                    'title'            => 'Terms of Service',
                    'subtitle'         => 'Last modified: October 2026',
                    'content'          => '<h4 class="fw-bold mt-4 mb-3">1. Services Provided</h4>
<p class="text-secondary">WAPI provides a SaaS platform for accessing and managing WhatsApp APIs. You are responsible for any activity through your account and for keeping your login credentials secure.</p>

<h4 class="fw-bold mt-4 mb-3">2. User Conduct</h4>
<p class="text-secondary">You agree not to use the services for any unlawful purpose or to send any prohibited content, including spam, phishing, or malicious messages. You must comply with Meta\'s official WhatsApp Business Policy at all times.</p>

<h4 class="fw-bold mt-4 mb-3">3. Payments and Subscriptions</h4>
<p class="text-secondary">Certain services are available on a paid subscription basis. Fees are non-refundable except as required by law. We reserve the right to change our subscription fees upon reasonable notice.</p>

<h4 class="fw-bold mt-4 mb-3">4. Limitation of Liability</h4>
<p class="text-secondary">WAPI shall not be liable for any indirect, incidental, special, or consequential damages resulting from the use or inability to use our services.</p>

<h4 class="fw-bold mt-4 mb-3">5. Termination</h4>
<p class="text-secondary">We may terminate or suspend your access to our services immediately, without prior notice or liability, for any reason, including if you breach the Terms.</p>

<h4 class="fw-bold mt-4 mb-3">6. Governing Law</h4>
<p class="text-secondary">These Terms shall be governed and construed in accordance with the laws of India, without regard to its conflict of law provisions.</p>',
                    'meta_title'       => 'Terms of Service | WAPI',
                    'meta_description' => 'Review the Terms of Service governing your use of WAPI WhatsApp API platform, subscriptions, user responsibilities, and API usage.',
                    'extra_data'       => [
                        'last_modified' => 'October 2026',
                        'intro_notice'  => 'By using the WAPI platform, you agree to these terms. Please read them carefully.'
                    ]
                ];

            case 'cookies':
                return [
                    'slug'             => 'cookies',
                    'title'            => 'Cookie Policy',
                    'subtitle'         => 'October 2026',
                    'content'          => '<h4 class="fw-bold mt-4 mb-3">1. What are Cookies?</h4>
<p class="text-secondary">Cookies are small data files that are placed on your computer or mobile device when you visit a website. They are used to make websites work, or work more efficiently, as well as to provide reporting information.</p>

<h4 class="fw-bold mt-4 mb-3">2. How We Use Cookies</h4>
<p class="text-secondary">We use cookies for several reasons, including:</p>
<ul>
    <li class="text-secondary mb-2"><strong>Essential Cookies:</strong> Required for the session management and authentication of your WAPI account.</li>
    <li class="text-secondary mb-2"><strong>Analytical/Performance Cookies:</strong> Allow us to recognize and count the number of visitors and see how visitors move around our website.</li>
    <li class="text-secondary mb-2"><strong>Functionality Cookies:</strong> Used to recognize you when you return to our website and remember your preferences.</li>
</ul>

<h4 class="fw-bold mt-4 mb-3">3. How Can I Control Cookies?</h4>
<p class="text-secondary">You have the right to decide whether to accept or reject cookies. You can set or amend your web browser controls to accept or refuse cookies. If you choose to reject cookies, you may still use our website though your access to some functionality and areas of our website may be restricted.</p>

<h4 class="fw-bold mt-4 mb-3">4. Cookies Used by Our Partners</h4>
<p class="text-secondary">Third parties (including, for example, advertising networks and providers of external services like web traffic analysis services) may also use cookies, over which we have no control. These cookies are likely to be analytical/performance cookies or targeting cookies.</p>

<h4 class="fw-bold mt-4 mb-3">5. More Information</h4>
<p class="text-secondary">If you have any questions about our use of cookies or other technologies, please contact us at ' . htmlspecialchars($contactEmail) . '.</p>',
                    'meta_title'       => 'Cookie Policy | WAPI',
                    'meta_description' => 'Understand how WAPI uses cookies, tracking technologies, and how you can control your cookie preferences on our website.',
                    'extra_data'       => [
                        'effective_date' => 'October 2026'
                    ]
                ];

            default:
                return [];
        }
    }
}
