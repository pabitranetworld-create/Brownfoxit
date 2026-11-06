<?php

namespace App\Admin;

use App\Models\Client;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\Service;
use App\Models\Testimonial;

/**
 * Describes every admin form in one place.
 *
 * Field types:
 *   text, textarea, html, url, email, number, icon, checkbox, datetime,
 *   select (options), image (one picture), images (list of pictures),
 *   repeater (list of rows with sub-fields), model_select (pick a related record)
 *
 * To add a new home-page block or a new content type, add it here; the
 * controllers and views build the forms, validation and saving from this.
 */
class Schema
{
    /* ---------------------------------------------------------------
     | Settings sections (stored in the `settings` table, one JSON per key)
     * ------------------------------------------------------------- */
    public static function sections(): array
    {
        $link = [
            ['key' => 'label', 'label' => 'লেখা', 'type' => 'text', 'required' => true],
            ['key' => 'url', 'label' => 'লিংক', 'type' => 'text', 'help' => 'যেমন /#about, /blog অথবা https://...'],
        ];

        return [
            'site' => [
                'title' => 'সাইট ও যোগাযোগ', 'icon' => 'fa-solid fa-globe',
                'fields' => [
                    ['key' => 'name', 'label' => 'সাইটের নাম', 'type' => 'text', 'required' => true],
                    ['key' => 'meta_title', 'label' => 'SEO Title (ব্রাউজার ট্যাবে দেখায়)', 'type' => 'text'],
                    ['key' => 'meta_description', 'label' => 'SEO Description', 'type' => 'textarea'],
                    ['key' => 'logo', 'label' => 'লোগো', 'type' => 'image'],
                    ['key' => 'favicon', 'label' => 'Favicon (৩২×৩২)', 'type' => 'image'],
                    ['key' => 'email', 'label' => 'ইমেইল', 'type' => 'email'],
                    ['key' => 'phone', 'label' => 'ফোন / WhatsApp নম্বর', 'type' => 'text'],
                    ['key' => 'whatsapp_url', 'label' => 'WhatsApp লিংক', 'type' => 'url'],
                    ['key' => 'address', 'label' => 'ঠিকানা', 'type' => 'text'],
                    ['key' => 'youtube_video_id', 'label' => 'YouTube ভিডিও ID (প্লে বাটনের জন্য)', 'type' => 'text', 'help' => 'youtube.com/watch?v= এর পরের অংশ'],
                    ['key' => 'social', 'label' => 'সোশ্যাল লিংক', 'type' => 'repeater', 'fields' => [
                        ['key' => 'icon', 'label' => 'আইকন', 'type' => 'icon', 'help' => 'যেমন fa-brands fa-facebook'],
                        ['key' => 'label', 'label' => 'নাম', 'type' => 'text'],
                        ['key' => 'url', 'label' => 'লিংক', 'type' => 'url'],
                    ]],
                ],
            ],

            'menu' => [
                'title' => 'মেনু', 'icon' => 'fa-solid fa-bars',
                'root' => ['type' => 'repeater', 'label' => 'মেনুর আইটেম', 'fields' => array_merge($link, [
                    ['key' => 'children', 'label' => 'সাব-মেনু', 'type' => 'repeater', 'fields' => $link],
                ])],
            ],

            'hero' => [
                'title' => 'Hero (প্রথম অংশ)', 'icon' => 'fa-solid fa-star',
                'fields' => [
                    ['key' => 'powered_by', 'label' => 'ছোট লেখা (কমলা)', 'type' => 'text'],
                    ['key' => 'title', 'label' => 'বড় টাইটেল', 'type' => 'text', 'required' => true],
                    ['key' => 'subtitle', 'label' => 'সাবটাইটেল', 'type' => 'textarea'],
                    ['key' => 'button_text', 'label' => 'বাটনের লেখা', 'type' => 'text'],
                    ['key' => 'button_url', 'label' => 'বাটনের লিংক', 'type' => 'text'],
                    ['key' => 'person_image', 'label' => 'আপনার ছবি', 'type' => 'image'],
                    ['key' => 'badge_image', 'label' => 'পাশের ছোট কার্ড', 'type' => 'image'],
                    ['key' => 'rating_title', 'label' => 'রেটিং টাইটেল', 'type' => 'text'],
                    ['key' => 'rating_value', 'label' => 'রেটিং', 'type' => 'text'],
                    ['key' => 'rating_avatars', 'label' => 'রেটিংয়ের ছোট ছবিগুলো', 'type' => 'images'],
                ],
            ],

            'about' => [
                'title' => 'About', 'icon' => 'fa-solid fa-user',
                'fields' => [
                    ['key' => 'subtitle', 'label' => 'ছোট লেখা', 'type' => 'text'],
                    ['key' => 'title', 'label' => 'টাইটেল', 'type' => 'text'],
                    ['key' => 'intro', 'label' => 'প্রথম অনুচ্ছেদ', 'type' => 'html', 'help' => '<strong>…</strong> দিয়ে লেখা মোটা করা যায়'],
                    ['key' => 'body', 'label' => 'দ্বিতীয় অনুচ্ছেদ', 'type' => 'textarea'],
                    ['key' => 'image', 'label' => 'ছবি', 'type' => 'image'],
                    ['key' => 'video_label', 'label' => 'ভিডিও বাটনের লেখা', 'type' => 'text'],
                    ['key' => 'partner_label', 'label' => 'পার্টনার লেখা', 'type' => 'text'],
                    ['key' => 'partner_logo', 'label' => 'পার্টনার লোগো', 'type' => 'image'],
                    ['key' => 'button_text', 'label' => 'বাটনের লেখা', 'type' => 'text'],
                    ['key' => 'button_url', 'label' => 'বাটনের লিংক', 'type' => 'text'],
                    ['key' => 'skills', 'label' => 'স্কিল বার', 'type' => 'repeater', 'fields' => [
                        ['key' => 'label', 'label' => 'নাম', 'type' => 'text', 'required' => true],
                        ['key' => 'value', 'label' => 'শতাংশ (০-১০০)', 'type' => 'number', 'min' => 0, 'max' => 100],
                        ['key' => 'color', 'label' => 'রং', 'type' => 'select', 'options' => self::colors()],
                    ]],
                ],
            ],

            'service_cards' => [
                'title' => 'Services কার্ড (ছবিসহ)', 'icon' => 'fa-solid fa-images',
                'fields' => [
                    ['key' => 'subtitle', 'label' => 'ছোট লেখা', 'type' => 'text'],
                    ['key' => 'title', 'label' => 'টাইটেল', 'type' => 'text'],
                    ['key' => 'items', 'label' => 'কার্ড', 'type' => 'repeater', 'fields' => [
                        ['key' => 'title', 'label' => 'নাম', 'type' => 'text', 'required' => true],
                        ['key' => 'image', 'label' => 'ছবি', 'type' => 'image'],
                        ['key' => 'url', 'label' => 'লিংক', 'type' => 'text'],
                    ]],
                ],
            ],

            'marketplace' => [
                'title' => 'Visit My Portfolio', 'icon' => 'fa-solid fa-store',
                'fields' => [
                    ['key' => 'title', 'label' => 'টাইটেল', 'type' => 'text'],
                    ['key' => 'subtitle', 'label' => 'সাবটাইটেল', 'type' => 'text'],
                    ['key' => 'items', 'label' => 'মার্কেটপ্লেস', 'type' => 'repeater', 'fields' => [
                        ['key' => 'name', 'label' => 'নাম', 'type' => 'text', 'required' => true],
                        ['key' => 'image', 'label' => 'লোগো', 'type' => 'image'],
                        ['key' => 'url', 'label' => 'প্রোফাইল লিংক', 'type' => 'url'],
                    ]],
                ],
            ],

            'counters' => [
                'title' => 'Counter (সংখ্যা)', 'icon' => 'fa-solid fa-chart-simple',
                'root' => ['type' => 'repeater', 'label' => 'Counter', 'fields' => [
                    ['key' => 'value', 'label' => 'সংখ্যা', 'type' => 'number', 'required' => true],
                    ['key' => 'suffix', 'label' => 'শেষে (K, %, +)', 'type' => 'text'],
                    ['key' => 'label', 'label' => 'লেখা', 'type' => 'text', 'required' => true],
                    ['key' => 'color', 'label' => 'রং', 'type' => 'select', 'options' => self::colors()],
                ]],
            ],

            'projects_section' => [
                'title' => 'Projects অংশের লেখা', 'icon' => 'fa-solid fa-briefcase',
                'fields' => [
                    ['key' => 'subtitle', 'label' => 'ছোট লেখা', 'type' => 'text'],
                    ['key' => 'title', 'label' => 'টাইটেল', 'type' => 'text'],
                    ['key' => 'text', 'label' => 'লেখা', 'type' => 'textarea'],
                    ['key' => 'button_text', 'label' => 'বাটনের লেখা', 'type' => 'text'],
                    ['key' => 'button_url', 'label' => 'বাটনের লিংক', 'type' => 'text'],
                ],
            ],

            'testimonials_section' => [
                'title' => 'Testimonials অংশের লেখা', 'icon' => 'fa-solid fa-quote-left',
                'fields' => [
                    ['key' => 'subtitle', 'label' => 'ছোট লেখা', 'type' => 'text'],
                    ['key' => 'title', 'label' => 'টাইটেল', 'type' => 'text'],
                    ['key' => 'floating_avatars', 'label' => 'চারপাশে ভাসমান ছবি (সর্বোচ্চ ৬টা)', 'type' => 'images'],
                ],
            ],

            'steps' => [
                'title' => 'Steps (কাজের ধাপ)', 'icon' => 'fa-solid fa-list-ol',
                'fields' => [
                    ['key' => 'subtitle', 'label' => 'ছোট লেখা', 'type' => 'text'],
                    ['key' => 'title', 'label' => 'টাইটেল', 'type' => 'text'],
                    ['key' => 'items', 'label' => 'ধাপ', 'type' => 'repeater', 'fields' => [
                        ['key' => 'title', 'label' => 'নাম', 'type' => 'text', 'required' => true],
                        ['key' => 'text', 'label' => 'বিবরণ', 'type' => 'textarea'],
                        ['key' => 'image', 'label' => 'হোভার করলে যে ছবি দেখায়', 'type' => 'image'],
                    ]],
                ],
            ],

            'cta' => [
                'title' => 'Contact (CTA)', 'icon' => 'fa-solid fa-headset',
                'fields' => [
                    ['key' => 'title', 'label' => 'টাইটেল', 'type' => 'text'],
                    ['key' => 'text', 'label' => 'লেখা', 'type' => 'text'],
                    ['key' => 'image', 'label' => 'ছবি', 'type' => 'image'],
                    ['key' => 'whatsapp_url', 'label' => 'WhatsApp লিংক', 'type' => 'url'],
                    ['key' => 'linkedin_url', 'label' => 'LinkedIn লিংক', 'type' => 'url'],
                ],
            ],

            'blog_section' => [
                'title' => 'Blog অংশের লেখা', 'icon' => 'fa-solid fa-newspaper',
                'fields' => [
                    ['key' => 'subtitle', 'label' => 'ছোট লেখা', 'type' => 'text'],
                    ['key' => 'title', 'label' => 'টাইটেল', 'type' => 'text'],
                ],
            ],

            'footer' => [
                'title' => 'Footer', 'icon' => 'fa-solid fa-table-columns',
                'fields' => [
                    ['key' => 'logo', 'label' => 'Footer লোগো', 'type' => 'image'],
                    ['key' => 'about_title', 'label' => 'মোটা লেখা', 'type' => 'text'],
                    ['key' => 'about_text', 'label' => 'লেখা', 'type' => 'textarea'],
                    ['key' => 'links', 'label' => 'Links কলাম', 'type' => 'repeater', 'fields' => $link],
                    ['key' => 'support', 'label' => 'Support কলাম', 'type' => 'repeater', 'fields' => $link],
                    ['key' => 'gallery', 'label' => 'Gallery', 'type' => 'repeater', 'fields' => [
                        ['key' => 'thumb', 'label' => 'ছোট ছবি', 'type' => 'image'],
                        ['key' => 'full', 'label' => 'বড় ছবি (ক্লিক করলে খোলে)', 'type' => 'image'],
                    ]],
                    ['key' => 'payment_logos', 'label' => 'পেমেন্ট লোগো', 'type' => 'image'],
                    ['key' => 'copyright_text', 'label' => 'কপিরাইট লেখা', 'type' => 'text'],
                    ['key' => 'copyright_link', 'label' => 'কপিরাইট লিংকের লেখা', 'type' => 'text'],
                ],
            ],

            'store' => [
                'title' => 'দোকানের সেটিংস', 'icon' => 'fa-solid fa-store',
                'intro' => 'কারেন্সি বদলালে পুরোনো প্রোডাক্টের দাম নিজে থেকে বদলায় না — দাম নতুন কারেন্সিতে আবার দিন। পেমেন্ট গেটওয়ে চালু/বন্ধ করতে বাঁ পাশের "Payment gateways" দেখুন।',
                'fields' => [
                    ['key' => 'currency', 'label' => 'কারেন্সি', 'type' => 'select', 'options' => ['BDT' => 'BDT — বাংলাদেশি টাকা', 'USD' => 'USD — US Dollar', 'EUR' => 'EUR — Euro', 'GBP' => 'GBP — Pound', 'INR' => 'INR — Rupee'], 'default' => 'BDT', 'help' => 'PayStation ও EPS শুধু BDT নেয়; aamarPay BDT ও USD নেয়।'],
                    ['key' => 'currency_symbol', 'label' => 'কারেন্সি চিহ্ন (খালি = স্বয়ংক্রিয়)', 'type' => 'text'],
                    ['key' => 'symbol_position', 'label' => 'চিহ্ন কোথায়', 'type' => 'select', 'options' => ['left' => 'বাঁয়ে (৳500)', 'right' => 'ডানে (500 ৳)'], 'default' => 'left'],
                    ['key' => 'decimals', 'label' => 'দশমিকের পরে কয় ঘর', 'type' => 'number', 'min' => 0, 'max' => 2, 'default' => 2],
                    ['key' => 'shop_title', 'label' => 'Shop পেজের টাইটেল', 'type' => 'text', 'default' => 'Shop'],
                    ['key' => 'shop_subtitle', 'label' => 'Shop পেজের সাবটাইটেল', 'type' => 'text'],
                    ['key' => 'shop_meta_description', 'label' => 'Shop পেজের SEO বিবরণ', 'type' => 'textarea'],
                    ['key' => 'shop_empty_text', 'label' => 'কোনো প্রোডাক্ট না থাকলে লেখা', 'type' => 'text'],
                    ['key' => 'per_page', 'label' => 'প্রতি পেজে প্রোডাক্ট', 'type' => 'number', 'min' => 4, 'max' => 60, 'default' => 12],
                    ['key' => 'h_checkout', 'label' => 'চেকআউট', 'type' => 'heading'],
                    ['key' => 'guest_checkout', 'label' => 'অ্যাকাউন্ট ছাড়া কেনা যাবে (Guest checkout)', 'type' => 'checkbox', 'default' => true],
                    ['key' => 'registration', 'label' => 'গ্রাহক অ্যাকাউন্ট খুলতে পারবে', 'type' => 'checkbox', 'default' => true],
                    ['key' => 'require_phone', 'label' => 'ফোন নম্বর বাধ্যতামূলক', 'type' => 'checkbox', 'default' => true],
                    ['key' => 'coupons_enabled', 'label' => 'কুপন চালু', 'type' => 'checkbox', 'default' => true],
                    ['key' => 'show_cart_icon', 'label' => 'হেডারে কার্ট আইকন', 'type' => 'checkbox', 'default' => true],
                    ['key' => 'terms_url', 'label' => 'Terms পেজের লিংক (দিলে চেকআউটে টিক দিতে হবে)', 'type' => 'text', 'help' => 'যেমন /terms-and-conditions — পেজটা Publish করার পরে দিন'],
                    ['key' => 'order_prefix', 'label' => 'অর্ডার নম্বরের শুরু', 'type' => 'text', 'default' => 'BFI'],
                    ['key' => 'h_tax', 'label' => 'ট্যাক্স ও শিপিং (শুধু physical প্রোডাক্টে শিপিং লাগে)', 'type' => 'heading'],
                    ['key' => 'tax_rate', 'label' => 'ট্যাক্স / VAT (%)', 'type' => 'number', 'min' => 0, 'max' => 50, 'default' => 0],
                    ['key' => 'tax_label', 'label' => 'ট্যাক্সের নাম', 'type' => 'text', 'default' => 'VAT'],
                    ['key' => 'shipping_flat', 'label' => 'শিপিং চার্জ (প্রতি অর্ডার)', 'type' => 'number', 'min' => 0, 'max' => 1000000, 'default' => 0],
                    ['key' => 'free_shipping_over', 'label' => 'এর বেশি কিনলে ফ্রি শিপিং (খালি = কখনো না)', 'type' => 'number', 'min' => 0, 'max' => 100000000],
                    ['key' => 'h_dl', 'label' => 'ডাউনলোড (ডিফল্ট — প্রতিটা প্রোডাক্টে আলাদা দেওয়া যায়)', 'type' => 'heading'],
                    ['key' => 'download_limit', 'label' => 'কতবার ডাউনলোড (খালি = সীমা নেই)', 'type' => 'number', 'min' => 0, 'max' => 10000, 'default' => 5],
                    ['key' => 'download_expiry_days', 'label' => 'কত দিন পর লিংক বাতিল (খালি = কখনো না)', 'type' => 'number', 'min' => 0, 'max' => 36500, 'default' => 30],
                ],
            ],

            'store_emails' => [
                'title' => 'অর্ডারের ইমেইল', 'icon' => 'fa-solid fa-envelope-open-text',
                'intro' => 'খালি রাখলে ডিফল্ট লেখা যাবে। ব্যবহারযোগ্য: {name} {order_number} {order_link} {total} {items} {downloads} {status} {payment_instructions} {site_name}। ইমেইল যেতে Settings → ইমেইল (SMTP) সেট করা থাকতে হবে।',
                'fields' => [
                    ['key' => 'received_subject', 'label' => 'অর্ডার পাওয়া গেছে (ম্যানুয়াল পেমেন্ট) — Subject', 'type' => 'text'],
                    ['key' => 'received_body', 'label' => 'অর্ডার পাওয়া গেছে — লেখা', 'type' => 'textarea'],
                    ['key' => 'paid_subject', 'label' => 'পেমেন্ট নিশ্চিত + ডাউনলোড — Subject', 'type' => 'text'],
                    ['key' => 'paid_body', 'label' => 'পেমেন্ট নিশ্চিত — লেখা', 'type' => 'textarea'],
                    ['key' => 'status_subject', 'label' => 'স্ট্যাটাস বদল (Completed / Cancelled / Refunded) — Subject', 'type' => 'text'],
                    ['key' => 'status_body', 'label' => 'স্ট্যাটাস বদল — লেখা', 'type' => 'textarea'],
                ],
            ],

            'portfolio_page' => [
                'title' => 'পোর্টফোলিও পেজ', 'icon' => 'fa-solid fa-briefcase',
                'intro' => '/portfolio পেজের শিরোনাম ও লেখা। প্রজেক্ট যোগ করুন Content → Projects থেকে।',
                'fields' => [
                    ['key' => 'title', 'label' => 'শিরোনাম', 'type' => 'text', 'default' => 'Portfolio'],
                    ['key' => 'subtitle', 'label' => 'ছোট বিবরণ', 'type' => 'textarea'],
                    ['key' => 'meta_description', 'label' => 'SEO description', 'type' => 'textarea'],
                ],
            ],

            'blog_page' => [
                'title' => 'ব্লগ পেজ', 'icon' => 'fa-solid fa-newspaper',
                'intro' => '/blog পেজের শিরোনাম ও লেখা।',
                'fields' => [
                    ['key' => 'title', 'label' => 'শিরোনাম', 'type' => 'text', 'default' => 'Blog & Insights'],
                    ['key' => 'subtitle', 'label' => 'ছোট বিবরণ', 'type' => 'textarea'],
                    ['key' => 'meta_description', 'label' => 'SEO description', 'type' => 'textarea'],
                ],
            ],

            'mail' => [
                'title' => 'ইমেইল (SMTP)', 'icon' => 'fa-solid fa-envelope', 'mail_test' => true,
                'intro' => 'Contact/Quote ফর্মের বার্তা (আর পরে অর্ডারের ইমেইল) পাঠাতে cPanel-এর একটা ইমেইল অ্যাকাউন্ট দিন। cPanel → Email Accounts → Connect Devices-এ Host, Port দেখা যায়। সেট না করলে বার্তা শুধু Admin → Messages-এ জমা হবে।',
                'fields' => [
                    ['key' => 'mailer', 'label' => 'ইমেইল পাঠানো', 'type' => 'select', 'options' => ['off' => 'বন্ধ (শুধু Admin-এ জমা হবে)', 'smtp' => 'SMTP (সুপারিশকৃত)', 'sendmail' => 'সার্ভারের sendmail'], 'default' => 'off'],
                    ['key' => 'host', 'label' => 'SMTP Host', 'type' => 'text', 'help' => 'যেমন mail.brownfoxit.com'],
                    ['key' => 'port', 'label' => 'Port', 'type' => 'number', 'min' => 1, 'max' => 65535, 'default' => 465],
                    ['key' => 'encryption', 'label' => 'Encryption', 'type' => 'select', 'options' => ['ssl' => 'SSL (Port 465)', 'tls' => 'TLS / STARTTLS (Port 587)'], 'default' => 'ssl'],
                    ['key' => 'username', 'label' => 'Username (পুরো ইমেইল ঠিকানা)', 'type' => 'text'],
                    ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'help' => 'এনক্রিপ্ট করে রাখা হয়। খালি রাখলে আগেরটা থাকবে।'],
                    ['key' => 'from_address', 'label' => 'যে ঠিকানা থেকে যাবে', 'type' => 'email', 'help' => 'সাধারণত Username-এর মতোই'],
                    ['key' => 'from_name', 'label' => 'পাঠানোর নাম', 'type' => 'text', 'help' => 'যেমন Brown Fox IT'],
                    ['key' => 'notify_email', 'label' => 'নতুন বার্তা কোন ইমেইলে আসবে', 'type' => 'email', 'help' => 'খালি রাখলে "সাইট ও যোগাযোগ"-এর ইমেইলে'],
                ],
            ],
        ];
    }

    /* ---------------------------------------------------------------
     | Content types with their own table (list / add / edit / delete)
     * ------------------------------------------------------------- */
    public static function resources(): array
    {
        $order = ['key' => 'sort_order', 'label' => 'ক্রম (ছোট সংখ্যা আগে দেখায়)', 'type' => 'number', 'default' => 0];
        $active = ['key' => 'is_active', 'label' => 'সাইটে দেখাবে', 'type' => 'checkbox', 'default' => true];

        return [
            'services' => [
                'model' => Service::class, 'title' => 'Services', 'singular' => 'Service', 'icon' => 'fa-solid fa-laptop-code',
                'slug_from' => 'title', 'sortable' => true,
                'columns' => [['key' => 'icon', 'type' => 'icon'], ['key' => 'title', 'label' => 'নাম'], ['key' => 'url', 'label' => 'পেজ'], ['key' => 'show_on_home', 'label' => 'Home', 'type' => 'bool'], ['key' => 'sort_order', 'label' => 'ক্রম'], ['key' => 'is_active', 'label' => 'সক্রিয়', 'type' => 'bool']],
                'fields' => [
                    ['key' => 'title', 'label' => 'নাম', 'type' => 'text', 'required' => true],
                    ['key' => 'icon', 'label' => 'আইকন', 'type' => 'icon', 'help' => 'Font Awesome class, যেমন fa-solid fa-laptop-code'],
                    ['key' => 'icon_color', 'label' => 'আইকনের রং', 'type' => 'select', 'options' => self::colors()],
                    ['key' => 'short_description', 'label' => 'ছোট বিবরণ', 'type' => 'textarea'],
                    ['key' => 'group', 'label' => 'গ্রুপ', 'type' => 'select', 'options' => ['' => '—'] + Service::GROUPS],
                    ['key' => 'url', 'label' => 'Service পেজের লিংক', 'type' => 'url', 'help' => 'যেমন /services/ai-automation — পেজটা Pages থেকে Builder দিয়ে এডিট করুন'],
                    ['key' => 'image', 'label' => 'ছবি', 'type' => 'image'],
                    ['key' => 'show_on_home', 'label' => 'Home page-এর স্লাইডারে দেখাবে', 'type' => 'checkbox', 'default' => false],
                    ['key' => 'show_in_catalog', 'label' => 'Services পেজ / তালিকায় দেখাবে', 'type' => 'checkbox', 'default' => true],
                    $order, $active,
                ],
            ],

            'projects' => [
                'model' => Project::class, 'title' => 'Portfolio', 'singular' => 'Project', 'icon' => 'fa-solid fa-briefcase',
                'slug_from' => 'title', 'sortable' => true, 'group' => 'content', 'view_url' => '/portfolio/{slug}',
                'columns' => [['key' => 'image', 'type' => 'image'], ['key' => 'title', 'label' => 'নাম'], ['key' => 'category', 'label' => 'ক্যাটাগরি'], ['key' => 'status', 'label' => 'অবস্থা'], ['key' => 'is_featured', 'label' => 'Home-এ', 'type' => 'bool'], ['key' => 'is_active', 'label' => 'সক্রিয়', 'type' => 'bool']],
                'fields' => [
                    ['key' => 'title', 'label' => 'নাম', 'type' => 'text', 'required' => true],
                    ['key' => 'status', 'label' => 'অবস্থা', 'type' => 'select', 'options' => ['published' => 'Published (প্রকাশিত)', 'draft' => 'Draft (লুকানো — তথ্য যাচাই বাকি)'], 'default' => 'published'],
                    ['key' => 'category', 'label' => 'ক্যাটাগরি (একাধিক হলে কমা দিয়ে)', 'type' => 'text', 'help' => 'যেমন Web Design, Development'],
                    ['key' => 'tags', 'label' => 'ট্যাগ (কমা দিয়ে)', 'type' => 'text'],
                    ['key' => 'summary', 'label' => 'এক লাইনের সারাংশ (টাইটেলের নিচে দেখায়)', 'type' => 'textarea'],
                    ['key' => 'image', 'label' => 'প্রধান ছবি', 'type' => 'image'],
                    ['key' => 'gallery', 'label' => 'গ্যালারি', 'type' => 'repeater', 'fields' => [
                        ['key' => 'image', 'label' => 'ছবি', 'type' => 'image'],
                        ['key' => 'caption', 'label' => 'ক্যাপশন', 'type' => 'text'],
                        ['key' => 'alt', 'label' => 'Alt লেখা', 'type' => 'text'],
                    ]],
                    ['key' => 'url', 'label' => 'লাইভ / ডেমো লিংক', 'type' => 'url'],
                    ['key' => 'video_url', 'label' => 'ভিডিও লিংক (YouTube / Vimeo)', 'type' => 'url'],
                    ['key' => 'h_case', 'label' => 'কেস স্টাডি (যা জানা আছে শুধু সেটুকু লিখুন — খালি ঘর সাইটে দেখাবে না)', 'type' => 'heading'],
                    ['key' => 'client', 'label' => 'ক্লায়েন্ট', 'type' => 'text'],
                    ['key' => 'year', 'label' => 'বছর', 'type' => 'text'],
                    ['key' => 'services', 'label' => 'যে সার্ভিস দেওয়া হয়েছে', 'type' => 'text'],
                    ['key' => 'description', 'label' => 'Overview', 'type' => 'html'],
                    ['key' => 'challenge', 'label' => 'The challenge', 'type' => 'html'],
                    ['key' => 'solution', 'label' => 'What we did', 'type' => 'html'],
                    ['key' => 'result', 'label' => 'The result', 'type' => 'html'],
                    ['key' => 'h_seo', 'label' => 'SEO', 'type' => 'heading'],
                    ['key' => 'seo_title', 'label' => 'SEO Title', 'type' => 'text'],
                    ['key' => 'seo_description', 'label' => 'SEO Description', 'type' => 'textarea'],
                    ['key' => 'is_featured', 'label' => 'Home page-এ দেখাবে', 'type' => 'checkbox', 'default' => true],
                    $order, $active,
                ],
            ],

            'testimonials' => [
                'model' => Testimonial::class, 'title' => 'Testimonials', 'singular' => 'Testimonial', 'icon' => 'fa-solid fa-quote-left',
                'sortable' => true,
                'columns' => [['key' => 'avatar', 'type' => 'image'], ['key' => 'name', 'label' => 'নাম'], ['key' => 'designation', 'label' => 'পদবি'], ['key' => 'is_active', 'label' => 'সক্রিয়', 'type' => 'bool']],
                'fields' => [
                    ['key' => 'name', 'label' => 'নাম', 'type' => 'text', 'required' => true],
                    ['key' => 'designation', 'label' => 'পদবি', 'type' => 'text'],
                    ['key' => 'avatar', 'label' => 'ছবি', 'type' => 'image'],
                    ['key' => 'content', 'label' => 'মন্তব্য', 'type' => 'textarea', 'required' => true],
                    ['key' => 'rating', 'label' => 'রেটিং (১-৫)', 'type' => 'number', 'min' => 1, 'max' => 5, 'default' => 5],
                    $order, $active,
                ],
            ],

            'clients' => [
                'model' => Client::class, 'title' => 'Client লোগো', 'singular' => 'Client', 'icon' => 'fa-solid fa-handshake',
                'sortable' => true,
                'columns' => [['key' => 'logo', 'type' => 'image'], ['key' => 'name', 'label' => 'নাম'], ['key' => 'sort_order', 'label' => 'ক্রম'], ['key' => 'is_active', 'label' => 'সক্রিয়', 'type' => 'bool']],
                'fields' => [
                    ['key' => 'name', 'label' => 'নাম', 'type' => 'text', 'required' => true],
                    ['key' => 'logo', 'label' => 'লোগো', 'type' => 'image', 'required' => true],
                    ['key' => 'url', 'label' => 'ওয়েবসাইট', 'type' => 'url'],
                    $order, $active,
                ],
            ],

            'posts' => [
                'model' => Post::class, 'title' => 'Blog', 'singular' => 'Post', 'icon' => 'fa-solid fa-newspaper',
                'slug_from' => 'title', 'sortable' => false, 'order_by' => ['published_at', 'desc'],
                'with' => ['category'],
                'columns' => [['key' => 'image', 'type' => 'image'], ['key' => 'title', 'label' => 'টাইটেল'], ['key' => 'category.name', 'label' => 'ক্যাটাগরি'], ['key' => 'status', 'label' => 'অবস্থা'], ['key' => 'published_at', 'label' => 'তারিখ', 'type' => 'date']],
                'fields' => [
                    ['key' => 'title', 'label' => 'টাইটেল', 'type' => 'text', 'required' => true],
                    ['key' => 'post_category_id', 'label' => 'ক্যাটাগরি', 'type' => 'model_select', 'model' => PostCategory::class, 'display' => 'name', 'allow_new' => true],
                    ['key' => 'image', 'label' => 'ছবি (৬০০×৪১২)', 'type' => 'image'],
                    ['key' => 'excerpt', 'label' => 'ছোট সারাংশ', 'type' => 'textarea'],
                    ['key' => 'body', 'label' => 'পুরো লেখা', 'type' => 'html'],
                    ['key' => 'status', 'label' => 'অবস্থা', 'type' => 'select', 'options' => ['published' => 'Published (প্রকাশিত)', 'draft' => 'Draft (খসড়া)'], 'default' => 'published'],
                    ['key' => 'published_at', 'label' => 'প্রকাশের তারিখ', 'type' => 'datetime'],
                    ['key' => 'tags', 'label' => 'ট্যাগ (কমা দিয়ে)', 'type' => 'text'],
                    ['key' => 'seo_title', 'label' => 'SEO Title', 'type' => 'text'],
                    ['key' => 'seo_description', 'label' => 'SEO Description', 'type' => 'textarea'],
                ],
            ],

            'products' => [
                'model' => Product::class, 'title' => 'Products', 'singular' => 'Product', 'icon' => 'fa-solid fa-box-open',
                'slug_from' => 'name', 'sortable' => false, 'order_by' => ['id', 'desc'], 'group' => 'store', 'with' => ['category'],
                'view_url' => '/shop/{slug}', 'duplicate' => true,
                'columns' => [['key' => 'image', 'type' => 'image'], ['key' => 'name', 'label' => 'নাম'], ['key' => 'category.name', 'label' => 'ক্যাটাগরি'], ['key' => 'price', 'label' => 'দাম'], ['key' => 'status', 'label' => 'অবস্থা'], ['key' => 'type', 'label' => 'ধরন']],
                'fields' => [
                    ['key' => 'name', 'label' => 'প্রোডাক্টের নাম', 'type' => 'text', 'required' => true],
                    ['key' => 'status', 'label' => 'অবস্থা', 'type' => 'select', 'options' => Product::STATUSES, 'default' => 'draft', 'help' => 'যাচাই না করা প্রোডাক্ট Draft রাখুন — দোকানে দেখাবে না।'],
                    ['key' => 'type', 'label' => 'ধরন', 'type' => 'select', 'options' => Product::TYPES, 'default' => 'digital'],
                    ['key' => 'product_category_id', 'label' => 'ক্যাটাগরি', 'type' => 'model_select', 'model' => ProductCategory::class, 'display' => 'name', 'allow_new' => true],
                    ['key' => 'tags', 'label' => 'ট্যাগ (কমা দিয়ে)', 'type' => 'text'],
                    ['key' => 'short_description', 'label' => 'ছোট বিবরণ (দামের নিচে)', 'type' => 'html'],
                    ['key' => 'description', 'label' => 'পুরো বিবরণ', 'type' => 'html'],
                    ['key' => 'image', 'label' => 'প্রধান ছবি', 'type' => 'image'],
                    ['key' => 'gallery', 'label' => 'আরও ছবি (গ্যালারি)', 'type' => 'images'],
                    ['key' => 'video_url', 'label' => 'ভিডিও (YouTube / Vimeo / MP4)', 'type' => 'url'],

                    ['key' => 'h_price', 'label' => 'দাম ও স্টক', 'type' => 'heading', 'help' => 'দাম দোকানের কারেন্সিতে (Settings → দোকান)। চেকআউটে সব হিসাব সার্ভারে আবার করা হয়।'],
                    ['key' => 'price', 'label' => 'নিয়মিত দাম', 'type' => 'number', 'min' => 0, 'max' => 100000000, 'required' => true],
                    ['key' => 'sale_price', 'label' => 'ছাড়ের দাম (ঐচ্ছিক)', 'type' => 'number', 'min' => 0, 'max' => 100000000],
                    ['key' => 'sale_starts_at', 'label' => 'ছাড় শুরু', 'type' => 'datetime'],
                    ['key' => 'sale_ends_at', 'label' => 'ছাড় শেষ', 'type' => 'datetime'],
                    ['key' => 'sku', 'label' => 'SKU', 'type' => 'text'],
                    ['key' => 'manage_stock', 'label' => 'স্টক গুনবে', 'type' => 'checkbox', 'default' => false],
                    ['key' => 'stock', 'label' => 'স্টকে কতগুলো', 'type' => 'number', 'min' => 0, 'max' => 10000000],
                    ['key' => 'weight', 'label' => 'ওজন (kg, physical)', 'type' => 'number', 'min' => 0, 'max' => 100000],
                    ['key' => 'variants', 'label' => 'অপশন / ভ্যারিয়েশন (যেমন Regular license / Extended license)', 'type' => 'repeater', 'help' => 'অপশন থাকলে গ্রাহককে একটা বেছে নিতে হবে, আর অপশনের দামই ধরা হবে।', 'fields' => [
                        ['key' => 'key', 'label' => '', 'type' => 'uid'],
                        ['key' => 'name', 'label' => 'অপশনের নাম', 'type' => 'text'],
                        ['key' => 'price', 'label' => 'দাম', 'type' => 'number', 'min' => 0, 'max' => 100000000],
                        ['key' => 'sale_price', 'label' => 'ছাড়ের দাম', 'type' => 'number', 'min' => 0, 'max' => 100000000],
                        ['key' => 'sku', 'label' => 'SKU', 'type' => 'text'],
                        ['key' => 'stock', 'label' => 'স্টক (খালি = গুনবে না)', 'type' => 'number', 'min' => 0, 'max' => 10000000],
                    ]],

                    ['key' => 'h_files', 'label' => 'ডাউনলোড ফাইল (ডিজিটাল প্রোডাক্ট)', 'type' => 'heading', 'help' => 'পেমেন্ট নিশ্চিত হওয়ার পরেই গ্রাহক ফাইল পায়, নিজস্ব গোপন লিংক দিয়ে। ফাইল সুরক্ষিত ফোল্ডারে থাকে।'],
                    ['key' => 'files', 'label' => 'ফাইল', 'type' => 'repeater', 'fields' => [
                        ['key' => 'key', 'label' => '', 'type' => 'uid'],
                        ['key' => 'name', 'label' => 'ফাইলের নাম (গ্রাহক দেখবে)', 'type' => 'text'],
                        ['key' => 'path', 'label' => 'ফাইল', 'type' => 'private_file'],
                        ['key' => 'version', 'label' => 'ভার্সন', 'type' => 'text'],
                        ['key' => 'variant', 'label' => 'শুধু এই অপশনের জন্য (অপশনের key, খালি = সবার জন্য)', 'type' => 'text'],
                    ]],
                    ['key' => 'download_limit', 'label' => 'কতবার ডাউনলোড করা যাবে (খালি = দোকানের ডিফল্ট)', 'type' => 'number', 'min' => 0, 'max' => 10000],
                    ['key' => 'download_expiry_days', 'label' => 'কত দিন পর লিংক বাতিল (খালি = দোকানের ডিফল্ট)', 'type' => 'number', 'min' => 0, 'max' => 36500],
                    ['key' => 'version', 'label' => 'বর্তমান ভার্সন', 'type' => 'text'],
                    ['key' => 'changelog', 'label' => 'Changelog', 'type' => 'textarea'],

                    ['key' => 'h_details', 'label' => 'বিস্তারিত তথ্য', 'type' => 'heading'],
                    ['key' => 'features', 'label' => 'ফিচার', 'type' => 'repeater', 'fields' => [['key' => 'text', 'label' => 'ফিচার', 'type' => 'text']]],
                    ['key' => 'specifications', 'label' => 'Specifications', 'type' => 'repeater', 'fields' => [['key' => 'label', 'label' => 'বিষয়', 'type' => 'text'], ['key' => 'value', 'label' => 'মান', 'type' => 'text']]],
                    ['key' => 'requirements', 'label' => 'Requirements', 'type' => 'textarea'],
                    ['key' => 'compatibility', 'label' => 'Compatibility', 'type' => 'textarea'],
                    ['key' => 'faqs', 'label' => 'FAQ', 'type' => 'repeater', 'fields' => [['key' => 'q', 'label' => 'প্রশ্ন', 'type' => 'text'], ['key' => 'a', 'label' => 'উত্তর', 'type' => 'textarea']]],
                    ['key' => 'demo_url', 'label' => 'ডেমো লিংক', 'type' => 'url'],
                    ['key' => 'documentation_url', 'label' => 'ডকুমেন্টেশন লিংক', 'type' => 'url'],
                    ['key' => 'is_featured', 'label' => 'Featured (দোকানের ওপরে দেখাবে)', 'type' => 'checkbox', 'default' => false],
                    ['key' => 'related_ids', 'label' => 'Related প্রোডাক্টের ID (কমা দিয়ে, খালি = একই ক্যাটাগরি)', 'type' => 'text'],
                    ['key' => 'sort_order', 'label' => 'ক্রম', 'type' => 'number', 'default' => 0],

                    ['key' => 'h_seo', 'label' => 'SEO', 'type' => 'heading'],
                    ['key' => 'seo_title', 'label' => 'SEO Title', 'type' => 'text'],
                    ['key' => 'seo_description', 'label' => 'SEO Description', 'type' => 'textarea'],
                    ['key' => 'og_image', 'label' => 'Social share ছবি', 'type' => 'image'],
                ],
            ],

            'product_categories' => [
                'model' => ProductCategory::class, 'title' => 'Product categories', 'singular' => 'Category', 'icon' => 'fa-solid fa-tags',
                'slug_from' => 'name', 'sortable' => true, 'group' => 'store',
                'columns' => [['key' => 'image', 'type' => 'image'], ['key' => 'name', 'label' => 'নাম'], ['key' => 'slug', 'label' => 'URL']],
                'fields' => [
                    ['key' => 'name', 'label' => 'নাম', 'type' => 'text', 'required' => true],
                    ['key' => 'description', 'label' => 'বিবরণ', 'type' => 'textarea'],
                    ['key' => 'image', 'label' => 'ছবি', 'type' => 'image'],
                    ['key' => 'sort_order', 'label' => 'ক্রম', 'type' => 'number', 'default' => 0],
                ],
            ],

            'coupons' => [
                'model' => Coupon::class, 'title' => 'Coupons', 'singular' => 'Coupon', 'icon' => 'fa-solid fa-ticket',
                'sortable' => false, 'group' => 'store',
                'columns' => [['key' => 'code', 'label' => 'কোড'], ['key' => 'type', 'label' => 'ধরন'], ['key' => 'value', 'label' => 'মান'], ['key' => 'used_count', 'label' => 'ব্যবহার'], ['key' => 'is_active', 'label' => 'সক্রিয়', 'type' => 'bool']],
                'fields' => [
                    ['key' => 'code', 'label' => 'কোড', 'type' => 'text', 'required' => true, 'help' => 'যেমন WELCOME10 (বড়-ছোট হাতের অক্ষর একই ধরা হয়)'],
                    ['key' => 'type', 'label' => 'ধরন', 'type' => 'select', 'options' => ['percent' => 'শতকরা (%)', 'fixed' => 'নির্দিষ্ট পরিমাণ'], 'default' => 'percent'],
                    ['key' => 'value', 'label' => 'মান', 'type' => 'number', 'min' => 0, 'max' => 100000000, 'required' => true],
                    ['key' => 'min_subtotal', 'label' => 'সর্বনিম্ন অর্ডার (ঐচ্ছিক)', 'type' => 'number', 'min' => 0, 'max' => 100000000],
                    ['key' => 'max_uses', 'label' => 'সর্বোচ্চ কতবার ব্যবহার (ঐচ্ছিক)', 'type' => 'number', 'min' => 1, 'max' => 10000000],
                    ['key' => 'starts_at', 'label' => 'শুরু', 'type' => 'datetime'],
                    ['key' => 'ends_at', 'label' => 'শেষ', 'type' => 'datetime'],
                    ['key' => 'description', 'label' => 'নিজের নোট', 'type' => 'text'],
                    ['key' => 'is_active', 'label' => 'সক্রিয়', 'type' => 'checkbox', 'default' => true],
                ],
            ],
        ];
    }

    public static function colors(): array
    {
        return ['blue' => 'নীল', 'orange' => 'কমলা', 'green' => 'সবুজ', 'purple' => 'বেগুনি'];
    }

    public static function section(string $key): array
    {
        return static::sections()[$key] ?? abort(404);
    }

    public static function resource(string $key): array
    {
        return static::resources()[$key] ?? abort(404);
    }
}
