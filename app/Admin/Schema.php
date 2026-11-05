<?php

namespace App\Admin;

use App\Models\Client;
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
                'model' => Project::class, 'title' => 'Projects', 'singular' => 'Project', 'icon' => 'fa-solid fa-briefcase',
                'slug_from' => 'title', 'sortable' => true,
                'columns' => [['key' => 'image', 'type' => 'image'], ['key' => 'title', 'label' => 'নাম'], ['key' => 'category', 'label' => 'ক্যাটাগরি'], ['key' => 'is_featured', 'label' => 'Home-এ', 'type' => 'bool'], ['key' => 'is_active', 'label' => 'সক্রিয়', 'type' => 'bool']],
                'fields' => [
                    ['key' => 'title', 'label' => 'নাম', 'type' => 'text', 'required' => true],
                    ['key' => 'category', 'label' => 'ক্যাটাগরি', 'type' => 'text', 'help' => 'যেমন Web Design'],
                    ['key' => 'image', 'label' => 'ছবি (৬০০×৫৫৮)', 'type' => 'image'],
                    ['key' => 'url', 'label' => 'লাইভ / প্রোডাক্ট লিংক', 'type' => 'url'],
                    ['key' => 'description', 'label' => 'বিবরণ', 'type' => 'html'],
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
