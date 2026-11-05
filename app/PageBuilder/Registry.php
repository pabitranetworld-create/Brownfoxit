<?php

namespace App\PageBuilder;

/**
 * Every element the page builder offers, with its editable fields.
 * The admin builder (public/assets/admin/builder.js) builds its forms from this,
 * LayoutValidator cleans saved layouts with it, and each widget type has a view
 * in resources/views/pages/widgets/<type>.blade.php. Nothing is offered here
 * that the frontend does not render.
 *
 * Field types: text, textarea, richtext, number, select, color, image, checkbox,
 *              url, icon, code, repeater
 * Groups: content (default), style, motion, advanced
 */
class Registry
{
    public const VERSION = 1;

    public static function widgets(): array
    {
        $btnItems = ['key' => 'buttons', 'label' => 'বাটন', 'type' => 'repeater', 'max' => 4, 'fields' => [
            ['key' => 'text', 'label' => 'লেখা', 'type' => 'text', 'default' => 'Get Started'],
            ['key' => 'url', 'label' => 'লিংক', 'type' => 'url', 'default' => '/contact'],
            ['key' => 'style', 'label' => 'ধরন', 'type' => 'select', 'options' => ['primary' => 'Gradient', 'ghost' => 'Outline', 'link' => 'শুধু লেখা'], 'default' => 'primary'],
            ['key' => 'icon', 'label' => 'আইকন', 'type' => 'icon', 'default' => ''],
            ['key' => 'new_tab', 'label' => 'নতুন ট্যাবে খুলবে', 'type' => 'checkbox', 'default' => false],
        ]];
        $colors = ['blue' => 'নীল', 'orange' => 'কমলা', 'green' => 'সবুজ', 'purple' => 'বেগুনি', 'cycle' => 'পালাক্রমে সব রং'];
        $cols = ['1' => '১', '2' => '২', '3' => '৩', '4' => '৪'];

        return [
            'hero' => [
                'label' => 'Hero', 'icon' => 'fa-solid fa-star', 'category' => 'layout',
                'fields' => [
                    ['key' => 'eyebrow', 'label' => 'ছোট লেখা (ওপরে)', 'type' => 'text', 'default' => 'Brown Fox IT'],
                    ['key' => 'title', 'label' => 'টাইটেল', 'type' => 'text', 'default' => 'Your headline here'],
                    ['key' => 'highlight', 'label' => 'টাইটেলের যে অংশ রঙিন হবে', 'type' => 'text', 'default' => ''],
                    ['key' => 'text', 'label' => 'বিবরণ', 'type' => 'textarea', 'default' => ''],
                    $btnItems,
                    ['key' => 'image', 'label' => 'ছবি', 'type' => 'image', 'default' => ''],
                    ['key' => 'layout', 'label' => 'লেআউট', 'type' => 'select', 'options' => ['split' => 'বাঁয়ে লেখা, ডানে ছবি', 'split-reverse' => 'বাঁয়ে ছবি, ডানে লেখা', 'center' => 'মাঝখানে'], 'default' => 'split', 'group' => 'style'],
                    ['key' => 'title_effect', 'label' => 'টাইটেল ইফেক্ট', 'type' => 'select', 'options' => ['words' => 'শব্দে শব্দে আসা', 'none' => 'নেই'], 'default' => 'words', 'group' => 'motion'],
                    ['key' => 'image_effect', 'label' => 'ছবির ইফেক্ট', 'type' => 'select', 'options' => self::imageEffects(), 'default' => 'float', 'group' => 'motion'],
                ],
            ],

            'heading' => [
                'label' => 'হেডিং', 'icon' => 'fa-solid fa-heading', 'category' => 'basic',
                'fields' => [
                    ['key' => 'eyebrow', 'label' => 'ছোট লেখা (ওপরে, কমলা)', 'type' => 'text', 'default' => ''],
                    ['key' => 'text', 'label' => 'হেডিং', 'type' => 'text', 'default' => 'Section heading'],
                    ['key' => 'tag', 'label' => 'HTML ট্যাগ (SEO)', 'type' => 'select', 'options' => ['h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'p' => 'সাধারণ লেখা'], 'default' => 'h2', 'group' => 'style'],
                    ['key' => 'size', 'label' => 'আকার', 'type' => 'select', 'options' => ['xxl' => 'অনেক বড়', 'xl' => 'বড়', 'l' => 'মাঝারি-বড়', 'm' => 'মাঝারি', 's' => 'ছোট'], 'default' => 'xl', 'group' => 'style'],
                    ['key' => 'color', 'label' => 'রং', 'type' => 'select', 'options' => ['white' => 'সাদা', 'gradient' => 'Gradient', 'accent' => 'কমলা', 'muted' => 'হালকা'], 'default' => 'white', 'group' => 'style'],
                    ['key' => 'effect', 'label' => 'লেখার ইফেক্ট', 'type' => 'select', 'options' => ['none' => 'নেই', 'words' => 'শব্দে শব্দে আসা', 'fill' => 'স্ক্রল করলে আলোকিত', 'sweep' => 'Gradient ঝলক'], 'default' => 'none', 'group' => 'motion'],
                ],
            ],

            'text' => [
                'label' => 'লেখা', 'icon' => 'fa-solid fa-paragraph', 'category' => 'basic',
                'fields' => [
                    ['key' => 'content', 'label' => 'লেখা', 'type' => 'richtext', 'default' => '<p>Write something here.</p>'],
                    ['key' => 'size', 'label' => 'আকার', 'type' => 'select', 'options' => ['normal' => 'সাধারণ', 'large' => 'বড়', 'small' => 'ছোট'], 'default' => 'normal', 'group' => 'style'],
                    ['key' => 'columns', 'label' => 'কলাম', 'type' => 'select', 'options' => ['1' => '১', '2' => '২'], 'default' => '1', 'group' => 'style'],
                ],
            ],

            'image' => [
                'label' => 'ছবি', 'icon' => 'fa-regular fa-image', 'category' => 'basic',
                'fields' => [
                    ['key' => 'image', 'label' => 'ছবি', 'type' => 'image', 'default' => ''],
                    ['key' => 'alt', 'label' => 'Alt লেখা (খালি রাখলে Media library থেকে)', 'type' => 'text', 'default' => ''],
                    ['key' => 'caption', 'label' => 'ক্যাপশন', 'type' => 'text', 'default' => ''],
                    ['key' => 'url', 'label' => 'ক্লিক করলে লিংক', 'type' => 'url', 'default' => ''],
                    ['key' => 'radius', 'label' => 'কোণ', 'type' => 'select', 'options' => ['none' => 'সোজা', 'm' => 'হালকা গোল', 'l' => 'গোল', 'xl' => 'বেশি গোল', 'circle' => 'বৃত্ত'], 'default' => 'l', 'group' => 'style'],
                    ['key' => 'shadow', 'label' => 'ছায়া', 'type' => 'checkbox', 'default' => true, 'group' => 'style'],
                    ['key' => 'max_width', 'label' => 'সর্বোচ্চ প্রস্থ (px, ০ = পূর্ণ)', 'type' => 'number', 'min' => 0, 'max' => 2000, 'default' => 0, 'group' => 'style'],
                    ['key' => 'effect', 'label' => 'ছবির ইফেক্ট', 'type' => 'select', 'options' => self::imageEffects(), 'default' => 'zoom', 'group' => 'motion'],
                ],
            ],

            'buttons' => [
                'label' => 'বাটন', 'icon' => 'fa-solid fa-hand-pointer', 'category' => 'basic',
                'fields' => [
                    array_merge($btnItems, ['default' => [['text' => 'Get Started', 'url' => '/contact', 'style' => 'primary', 'icon' => 'fa-solid fa-arrow-right', 'new_tab' => false]]]),
                    ['key' => 'size', 'label' => 'আকার', 'type' => 'select', 'options' => ['m' => 'মাঝারি', 'l' => 'বড়', 's' => 'ছোট'], 'default' => 'm', 'group' => 'style'],
                ],
            ],

            'icon_boxes' => [
                'label' => 'আইকন কার্ড', 'icon' => 'fa-solid fa-table-cells-large', 'category' => 'content',
                'fields' => [
                    ['key' => 'items', 'label' => 'কার্ড', 'type' => 'repeater', 'max' => 24, 'fields' => [
                        ['key' => 'icon', 'label' => 'আইকন', 'type' => 'icon', 'default' => 'fa-solid fa-bolt'],
                        ['key' => 'title', 'label' => 'টাইটেল', 'type' => 'text', 'default' => 'Feature title'],
                        ['key' => 'text', 'label' => 'লেখা', 'type' => 'textarea', 'default' => ''],
                        ['key' => 'url', 'label' => 'লিংক (ঐচ্ছিক)', 'type' => 'url', 'default' => ''],
                    ], 'default' => [['icon' => 'fa-solid fa-bolt', 'title' => 'Feature title', 'text' => 'Short description.', 'url' => '']]],
                    ['key' => 'columns', 'label' => 'প্রতি সারিতে', 'type' => 'select', 'options' => $cols, 'default' => '3', 'group' => 'style'],
                    ['key' => 'style', 'label' => 'ধরন', 'type' => 'select', 'options' => ['card' => 'কার্ড', 'glass' => 'কাচের মতো', 'plain' => 'সাধারণ'], 'default' => 'card', 'group' => 'style'],
                    ['key' => 'icon_color', 'label' => 'আইকনের রং', 'type' => 'select', 'options' => $colors, 'default' => 'cycle', 'group' => 'style'],
                    ['key' => 'align', 'label' => 'লেখা', 'type' => 'select', 'options' => ['left' => 'বাঁয়ে', 'center' => 'মাঝে'], 'default' => 'left', 'group' => 'style'],
                ],
            ],

            'list' => [
                'label' => 'তালিকা', 'icon' => 'fa-solid fa-list-check', 'category' => 'content',
                'fields' => [
                    ['key' => 'items', 'label' => 'আইটেম', 'type' => 'repeater', 'max' => 40, 'fields' => [
                        ['key' => 'text', 'label' => 'লেখা', 'type' => 'text', 'default' => 'List item'],
                    ], 'default' => [['text' => 'List item']]],
                    ['key' => 'style', 'label' => 'চিহ্ন', 'type' => 'select', 'options' => ['check' => '✓ টিক', 'dot' => '• বিন্দু', 'number' => '১, ২, ৩', 'arrow' => '→ তীর'], 'default' => 'check', 'group' => 'style'],
                    ['key' => 'columns', 'label' => 'কলাম', 'type' => 'select', 'options' => ['1' => '১', '2' => '২', '3' => '৩'], 'default' => '1', 'group' => 'style'],
                ],
            ],

            'video' => [
                'label' => 'ভিডিও', 'icon' => 'fa-solid fa-circle-play', 'category' => 'media',
                'fields' => [
                    ['key' => 'url', 'label' => 'YouTube / Vimeo / MP4 লিংক', 'type' => 'url', 'default' => ''],
                    ['key' => 'poster', 'label' => 'কভার ছবি', 'type' => 'image', 'default' => ''],
                    ['key' => 'title', 'label' => 'শিরোনাম (Accessibility)', 'type' => 'text', 'default' => 'Video'],
                    ['key' => 'mode', 'label' => 'কীভাবে চলবে', 'type' => 'select', 'options' => ['popup' => 'কভারে ক্লিক করলে popup', 'inline' => 'পেজের ভেতরেই'], 'default' => 'popup', 'group' => 'style'],
                    ['key' => 'ratio', 'label' => 'অনুপাত', 'type' => 'select', 'options' => ['16/9' => '16:9', '4/3' => '4:3', '1/1' => '1:1', '9/16' => '9:16 (Reels)'], 'default' => '16/9', 'group' => 'style'],
                ],
            ],

            'gallery' => [
                'label' => 'গ্যালারি', 'icon' => 'fa-solid fa-images', 'category' => 'media',
                'fields' => [
                    ['key' => 'items', 'label' => 'ছবি', 'type' => 'repeater', 'max' => 60, 'fields' => [
                        ['key' => 'image', 'label' => 'ছবি', 'type' => 'image', 'default' => ''],
                        ['key' => 'caption', 'label' => 'ক্যাপশন', 'type' => 'text', 'default' => ''],
                    ], 'default' => []],
                    ['key' => 'columns', 'label' => 'প্রতি সারিতে', 'type' => 'select', 'options' => $cols, 'default' => '3', 'group' => 'style'],
                    ['key' => 'ratio', 'label' => 'ছবির আকার', 'type' => 'select', 'options' => ['auto' => 'আসল', '1/1' => 'বর্গ', '4/3' => '4:3', '16/9' => '16:9'], 'default' => '4/3', 'group' => 'style'],
                    ['key' => 'lightbox', 'label' => 'ক্লিক করলে বড় হবে', 'type' => 'checkbox', 'default' => true, 'group' => 'style'],
                ],
            ],

            'faq' => [
                'label' => 'FAQ (Accordion)', 'icon' => 'fa-solid fa-circle-question', 'category' => 'interactive',
                'fields' => [
                    ['key' => 'items', 'label' => 'প্রশ্ন', 'type' => 'repeater', 'max' => 40, 'fields' => [
                        ['key' => 'q', 'label' => 'প্রশ্ন', 'type' => 'text', 'default' => 'Question?'],
                        ['key' => 'a', 'label' => 'উত্তর', 'type' => 'richtext', 'default' => '<p>Answer.</p>'],
                    ], 'default' => [['q' => 'Question?', 'a' => '<p>Answer.</p>']]],
                    ['key' => 'first_open', 'label' => 'প্রথমটা খোলা থাকবে', 'type' => 'checkbox', 'default' => true, 'group' => 'style'],
                    ['key' => 'schema', 'label' => 'Google-এর জন্য FAQ Schema যোগ করুন', 'type' => 'checkbox', 'default' => true, 'group' => 'advanced'],
                ],
            ],

            'tabs' => [
                'label' => 'ট্যাব', 'icon' => 'fa-solid fa-folder-open', 'category' => 'interactive',
                'fields' => [
                    ['key' => 'items', 'label' => 'ট্যাব', 'type' => 'repeater', 'max' => 10, 'fields' => [
                        ['key' => 'title', 'label' => 'ট্যাবের নাম', 'type' => 'text', 'default' => 'Tab'],
                        ['key' => 'icon', 'label' => 'আইকন', 'type' => 'icon', 'default' => ''],
                        ['key' => 'content', 'label' => 'লেখা', 'type' => 'richtext', 'default' => '<p>Tab content.</p>'],
                        ['key' => 'image', 'label' => 'ছবি (ঐচ্ছিক)', 'type' => 'image', 'default' => ''],
                    ], 'default' => [['title' => 'Tab one', 'icon' => '', 'content' => '<p>Tab content.</p>', 'image' => '']]],
                    ['key' => 'layout', 'label' => 'ট্যাবের অবস্থান', 'type' => 'select', 'options' => ['top' => 'ওপরে', 'left' => 'বাঁয়ে'], 'default' => 'top', 'group' => 'style'],
                ],
            ],

            'cta' => [
                'label' => 'CTA বক্স', 'icon' => 'fa-solid fa-bullhorn', 'category' => 'content',
                'fields' => [
                    ['key' => 'title', 'label' => 'টাইটেল', 'type' => 'text', 'default' => 'Ready to start your project?'],
                    ['key' => 'text', 'label' => 'লেখা', 'type' => 'textarea', 'default' => ''],
                    array_merge($btnItems, ['default' => [['text' => 'Start a Project', 'url' => '/start-a-project', 'style' => 'primary', 'icon' => 'fa-solid fa-arrow-right', 'new_tab' => false]]]),
                    ['key' => 'image', 'label' => 'ছবি (ঐচ্ছিক)', 'type' => 'image', 'default' => ''],
                    ['key' => 'style', 'label' => 'ধরন', 'type' => 'select', 'options' => ['gradient' => 'চলমান Gradient', 'glass' => 'কাচের মতো', 'outline' => 'Glow বর্ডার'], 'default' => 'gradient', 'group' => 'style'],
                ],
            ],

            'counters' => [
                'label' => 'সংখ্যা (Counter)', 'icon' => 'fa-solid fa-chart-simple', 'category' => 'content',
                'fields' => [
                    ['key' => 'items', 'label' => 'সংখ্যা', 'type' => 'repeater', 'max' => 8, 'fields' => [
                        ['key' => 'value', 'label' => 'সংখ্যা', 'type' => 'number', 'min' => 0, 'max' => 100000000, 'default' => 10],
                        ['key' => 'suffix', 'label' => 'শেষে', 'type' => 'text', 'default' => '+'],
                        ['key' => 'label', 'label' => 'লেখা', 'type' => 'text', 'default' => 'Label'],
                    ], 'default' => [['value' => 10, 'suffix' => '+', 'label' => 'Label']]],
                    ['key' => 'help_note', 'label' => '', 'type' => 'note', 'text' => 'শুধু যাচাই করা সত্যিকারের সংখ্যা দিন।'],
                ],
            ],

            'steps' => [
                'label' => 'ধাপ / Process', 'icon' => 'fa-solid fa-list-ol', 'category' => 'content',
                'fields' => [
                    ['key' => 'items', 'label' => 'ধাপ', 'type' => 'repeater', 'max' => 12, 'fields' => [
                        ['key' => 'icon', 'label' => 'আইকন', 'type' => 'icon', 'default' => ''],
                        ['key' => 'title', 'label' => 'টাইটেল', 'type' => 'text', 'default' => 'Step'],
                        ['key' => 'text', 'label' => 'লেখা', 'type' => 'textarea', 'default' => ''],
                    ], 'default' => [['icon' => '', 'title' => 'Step one', 'text' => '']]],
                    ['key' => 'layout', 'label' => 'লেআউট', 'type' => 'select', 'options' => ['horizontal' => 'পাশাপাশি', 'vertical' => 'ওপর-নিচে (Timeline)'], 'default' => 'horizontal', 'group' => 'style'],
                ],
            ],

            'workflow' => [
                'label' => 'Workflow ডায়াগ্রাম', 'icon' => 'fa-solid fa-diagram-project', 'category' => 'interactive',
                'fields' => [
                    ['key' => 'nodes', 'label' => 'ধাপ (node)', 'type' => 'repeater', 'max' => 10, 'fields' => [
                        ['key' => 'icon', 'label' => 'আইকন', 'type' => 'icon', 'default' => 'fa-solid fa-bolt'],
                        ['key' => 'label', 'label' => 'নাম', 'type' => 'text', 'default' => 'Trigger'],
                        ['key' => 'sub', 'label' => 'ছোট বিবরণ', 'type' => 'text', 'default' => ''],
                        ['key' => 'color', 'label' => 'রং', 'type' => 'select', 'options' => ['blue' => 'নীল', 'orange' => 'কমলা', 'green' => 'সবুজ', 'purple' => 'বেগুনি'], 'default' => 'blue'],
                    ], 'default' => [
                        ['icon' => 'fa-solid fa-bolt', 'label' => 'Trigger', 'sub' => 'Form, email or webhook', 'color' => 'orange'],
                        ['icon' => 'fa-solid fa-robot', 'label' => 'AI step', 'sub' => 'Classify & extract', 'color' => 'purple'],
                        ['icon' => 'fa-solid fa-plug', 'label' => 'Integrations', 'sub' => 'CRM, Sheets, Slack', 'color' => 'blue'],
                        ['icon' => 'fa-solid fa-circle-check', 'label' => 'Result', 'sub' => 'Logged & notified', 'color' => 'green'],
                    ]],
                    ['key' => 'animated', 'label' => 'চলমান ডেটা-বিন্দু', 'type' => 'checkbox', 'default' => true, 'group' => 'motion'],
                    ['key' => 'speed', 'label' => 'গতি', 'type' => 'select', 'options' => ['slow' => 'ধীর', 'normal' => 'সাধারণ', 'fast' => 'দ্রুত'], 'default' => 'normal', 'group' => 'motion'],
                ],
            ],

            'comparison' => [
                'label' => 'তুলনা টেবিল', 'icon' => 'fa-solid fa-table', 'category' => 'interactive',
                'fields' => [
                    ['key' => 'col1', 'label' => 'কলাম ১-এর নাম', 'type' => 'text', 'default' => 'Option A'],
                    ['key' => 'col2', 'label' => 'কলাম ২-এর নাম', 'type' => 'text', 'default' => 'Option B'],
                    ['key' => 'col3', 'label' => 'কলাম ৩-এর নাম (ঐচ্ছিক)', 'type' => 'text', 'default' => ''],
                    ['key' => 'highlight', 'label' => 'হাইলাইট কলাম', 'type' => 'select', 'options' => ['0' => 'নেই', '1' => '১', '2' => '২', '3' => '৩'], 'default' => '2'],
                    ['key' => 'rows', 'label' => 'সারি', 'type' => 'repeater', 'max' => 40, 'help' => 'ঘরে "yes" লিখলে ✓, "no" লিখলে ✗ দেখাবে', 'fields' => [
                        ['key' => 'label', 'label' => 'বিষয়', 'type' => 'text', 'default' => 'Feature'],
                        ['key' => 'v1', 'label' => 'কলাম ১', 'type' => 'text', 'default' => 'no'],
                        ['key' => 'v2', 'label' => 'কলাম ২', 'type' => 'text', 'default' => 'yes'],
                        ['key' => 'v3', 'label' => 'কলাম ৩', 'type' => 'text', 'default' => ''],
                    ], 'default' => [['label' => 'Feature', 'v1' => 'no', 'v2' => 'yes', 'v3' => '']]],
                ],
            ],

            'testimonials' => [
                'label' => 'Testimonials', 'icon' => 'fa-solid fa-quote-left', 'category' => 'dynamic',
                'fields' => [
                    ['key' => 'count', 'label' => 'কতগুলো দেখাবে', 'type' => 'number', 'min' => 1, 'max' => 30, 'default' => 6],
                    ['key' => 'style', 'label' => 'ধরন', 'type' => 'select', 'options' => ['grid' => 'গ্রিড', 'slider' => 'স্লাইডার'], 'default' => 'slider', 'group' => 'style'],
                    ['key' => 'note', 'label' => '', 'type' => 'note', 'text' => 'Admin → Testimonials-এর সক্রিয় আসল মন্তব্যগুলো দেখায়।'],
                ],
            ],

            'services_grid' => [
                'label' => 'Services তালিকা', 'icon' => 'fa-solid fa-laptop-code', 'category' => 'dynamic',
                'fields' => [
                    ['key' => 'count', 'label' => 'কতগুলো (০ = সব)', 'type' => 'number', 'min' => 0, 'max' => 60, 'default' => 0],
                    ['key' => 'columns', 'label' => 'প্রতি সারিতে', 'type' => 'select', 'options' => $cols, 'default' => '3', 'group' => 'style'],
                    ['key' => 'note', 'label' => '', 'type' => 'note', 'text' => 'Admin → Services থেকে আসে।'],
                ],
            ],

            'projects_grid' => [
                'label' => 'Portfolio গ্রিড', 'icon' => 'fa-solid fa-briefcase', 'category' => 'dynamic',
                'fields' => [
                    ['key' => 'count', 'label' => 'কতগুলো (০ = সব)', 'type' => 'number', 'min' => 0, 'max' => 120, 'default' => 0],
                    ['key' => 'filter', 'label' => 'ক্যাটাগরি ফিল্টার বাটন', 'type' => 'checkbox', 'default' => true],
                    ['key' => 'category', 'label' => 'শুধু এই ক্যাটাগরি (খালি = সব)', 'type' => 'text', 'default' => ''],
                    ['key' => 'columns', 'label' => 'প্রতি সারিতে', 'type' => 'select', 'options' => $cols, 'default' => '3', 'group' => 'style'],
                ],
            ],

            'posts_grid' => [
                'label' => 'Blog পোস্ট', 'icon' => 'fa-solid fa-newspaper', 'category' => 'dynamic',
                'fields' => [
                    ['key' => 'count', 'label' => 'কতগুলো', 'type' => 'number', 'min' => 1, 'max' => 24, 'default' => 3],
                    ['key' => 'columns', 'label' => 'প্রতি সারিতে', 'type' => 'select', 'options' => $cols, 'default' => '3', 'group' => 'style'],
                ],
            ],

            'divider' => [
                'label' => 'দাগ / ফাঁকা জায়গা', 'icon' => 'fa-solid fa-grip-lines', 'category' => 'basic',
                'fields' => [
                    ['key' => 'style', 'label' => 'ধরন', 'type' => 'select', 'options' => ['space' => 'শুধু ফাঁকা', 'line' => 'দাগ', 'gradient' => 'Gradient দাগ', 'glow' => 'চলমান আলো'], 'default' => 'gradient'],
                    ['key' => 'height', 'label' => 'উচ্চতা (px)', 'type' => 'number', 'min' => 0, 'max' => 400, 'default' => 40],
                ],
            ],

            'html' => [
                'label' => 'HTML / CSS / JS', 'icon' => 'fa-solid fa-code', 'category' => 'advanced',
                'fields' => [
                    ['key' => 'code', 'label' => 'HTML', 'type' => 'code', 'lang' => 'html', 'default' => '', 'help' => 'যেকোনো HTML: নিজের ডিজাইন, Embed কোড (Google Map, ফর্ম, ভিডিও)। শুধু বিশ্বস্ত কোড দিন।'],
                    ['key' => 'css', 'label' => 'CSS (শুধু এই পেজে)', 'type' => 'code', 'lang' => 'css', 'default' => ''],
                    ['key' => 'js', 'label' => 'JavaScript (HTML লোড হওয়ার পরে চলে)', 'type' => 'code', 'lang' => 'js', 'default' => ''],
                    ['key' => 'wrap', 'label' => 'চারপাশের ফাঁক', 'type' => 'select', 'options' => ['none' => 'নেই (কোড যেমন, তেমন)', 'normal' => 'সাধারণ'], 'default' => 'none', 'group' => 'style'],
                ],
            ],

            'row' => [
                'label' => 'ভেতরের কলাম (Nested)', 'icon' => 'fa-solid fa-table-columns', 'category' => 'layout',
                'container' => true,
                'fields' => [
                    ['key' => 'gap', 'label' => 'কলামের ফাঁক', 'type' => 'select', 'options' => ['none' => 'কোনো ফাঁক নেই', 's' => 'কম', 'm' => 'মাঝারি', 'l' => 'বেশি'], 'default' => 'm', 'group' => 'style'],
                    ['key' => 'valign', 'label' => 'উল্লম্ব সারিবদ্ধতা', 'type' => 'select', 'options' => ['start' => 'ওপরে', 'center' => 'মাঝে', 'end' => 'নিচে'], 'default' => 'start', 'group' => 'style'],
                ],
            ],
        ];
    }

    public static function imageEffects(): array
    {
        return ['none' => 'নেই', 'zoom' => 'হোভারে Zoom', 'reveal' => 'পর্দা সরে প্রকাশ (Clip)', 'mask' => 'বৃত্ত থেকে প্রকাশ (Mask)', 'float' => 'ভাসমান', 'tilt' => '3D Tilt (মাউসে)'];
    }

    /** Settings every element shares (motion, spacing, visibility …). */
    public static function motionFields(): array
    {
        return [
            ['key' => 'anim', 'label' => 'প্রবেশ Animation', 'type' => 'select', 'group' => 'motion', 'default' => 'fade-up', 'options' => [
                'none' => 'নেই', 'fade' => 'Fade', 'fade-up' => 'নিচ থেকে', 'fade-down' => 'ওপর থেকে', 'fade-left' => 'ডান থেকে',
                'fade-right' => 'বাঁ থেকে', 'zoom-in' => 'Zoom in', 'zoom-out' => 'Zoom out', 'flip-up' => '3D Flip',
                'blur-in' => 'Blur থেকে স্পষ্ট', 'clip-up' => 'পর্দা সরে (নিচ থেকে)', 'clip-left' => 'পর্দা সরে (বাঁ থেকে)',
            ]],
            ['key' => 'anim_duration', 'label' => 'সময় (ms)', 'type' => 'number', 'min' => 100, 'max' => 3000, 'step' => 50, 'default' => 800, 'group' => 'motion'],
            ['key' => 'anim_delay', 'label' => 'দেরি (ms)', 'type' => 'number', 'min' => 0, 'max' => 3000, 'step' => 50, 'default' => 0, 'group' => 'motion'],
            ['key' => 'anim_stagger', 'label' => 'ভেতরের আইটেমগুলোর মাঝে দেরি (ms)', 'type' => 'number', 'min' => 0, 'max' => 600, 'step' => 10, 'default' => 90, 'group' => 'motion', 'help' => 'কার্ড, তালিকা, গ্রিডের আইটেম একটার পর একটা আসবে'],
            ['key' => 'anim_intensity', 'label' => 'তীব্রতা', 'type' => 'select', 'options' => ['subtle' => 'হালকা', 'normal' => 'সাধারণ', 'bold' => 'জোরালো'], 'default' => 'normal', 'group' => 'motion'],
            ['key' => 'hover', 'label' => 'হোভার ইফেক্ট', 'type' => 'select', 'options' => ['none' => 'নেই', 'lift' => 'ওপরে ওঠা', 'grow' => 'বড় হওয়া', 'glow' => 'আলো', 'tilt' => '3D Tilt', 'shine' => 'ঝলক'], 'default' => 'none', 'group' => 'motion'],
            ['key' => 'parallax', 'label' => 'Parallax (স্ক্রলে গভীরতা)', 'type' => 'select', 'options' => ['none' => 'নেই', 'subtle' => 'হালকা', 'medium' => 'মাঝারি', 'strong' => 'বেশি'], 'default' => 'none', 'group' => 'motion'],
            ['key' => 'anim_mobile', 'label' => 'মোবাইলে', 'type' => 'select', 'options' => ['on' => 'একই রকম', 'simple' => 'শুধু Fade', 'off' => 'বন্ধ'], 'default' => 'on', 'group' => 'motion'],
            ['key' => 'anim_reduced', 'label' => '"কম Animation" চালু থাকা দর্শকের জন্য', 'type' => 'select', 'options' => ['none' => 'কোনো Animation নয়', 'fade' => 'শুধু হালকা Fade'], 'default' => 'none', 'group' => 'motion', 'help' => 'যারা ডিভাইসে Reduce Motion চালু রেখেছেন'],
        ];
    }

    public static function advancedFields(): array
    {
        return [
            ['key' => 'mt', 'label' => 'ওপরের ফাঁক (px)', 'type' => 'number', 'min' => -200, 'max' => 400, 'default' => 0, 'group' => 'advanced'],
            ['key' => 'mb', 'label' => 'নিচের ফাঁক (px)', 'type' => 'number', 'min' => -200, 'max' => 400, 'default' => 0, 'group' => 'advanced'],
            ['key' => 'text_align', 'label' => 'লেখার অবস্থান', 'type' => 'select', 'options' => ['' => 'স্বাভাবিক', 'left' => 'বাঁয়ে', 'center' => 'মাঝে', 'right' => 'ডানে'], 'default' => '', 'group' => 'advanced'],
            ['key' => 'hide_desktop', 'label' => 'ডেস্কটপে লুকান', 'type' => 'checkbox', 'default' => false, 'group' => 'advanced'],
            ['key' => 'hide_tablet', 'label' => 'ট্যাবলেটে লুকান', 'type' => 'checkbox', 'default' => false, 'group' => 'advanced'],
            ['key' => 'hide_mobile', 'label' => 'মোবাইলে লুকান', 'type' => 'checkbox', 'default' => false, 'group' => 'advanced'],
            ['key' => 'anchor', 'label' => 'Anchor ID (লিংক #id)', 'type' => 'text', 'default' => '', 'group' => 'advanced'],
            ['key' => 'css_class', 'label' => 'CSS class', 'type' => 'text', 'default' => '', 'group' => 'advanced'],
        ];
    }

    public static function sectionFields(): array
    {
        return array_merge([
            ['key' => 'width', 'label' => 'কন্টেন্টের প্রস্থ', 'type' => 'select', 'options' => ['boxed' => 'Boxed (১২০০px)', 'narrow' => 'সরু (৮৪০px)', 'wide' => 'চওড়া (১৪৪০px)', 'full' => 'Full width (দুই পাশে অল্প ফাঁকা)', 'bleed' => 'Full width — একদম কিনারা পর্যন্ত (ফাঁকা নেই)'], 'default' => 'boxed', 'group' => 'style', 'help' => 'Section-এর ব্যাকগ্রাউন্ড সবসময় পুরো স্ক্রিন জুড়ে থাকে। এখানে ভেতরের কন্টেন্ট কতটা চওড়া হবে সেটা ঠিক হয়।'],
            ['key' => 'height', 'label' => 'উচ্চতা', 'type' => 'select', 'options' => ['auto' => 'কন্টেন্ট অনুযায়ী', 'screen' => 'পুরো স্ক্রিন (100vh)', 'custom' => 'নির্দিষ্ট সর্বনিম্ন উচ্চতা'], 'default' => 'auto', 'group' => 'style'],
            ['key' => 'min_height', 'label' => 'সর্বনিম্ন উচ্চতা (px)', 'type' => 'number', 'min' => 0, 'max' => 2000, 'default' => 500, 'group' => 'style'],
            ['key' => 'content_pos', 'label' => 'উচ্চতার মধ্যে কন্টেন্টের অবস্থান', 'type' => 'select', 'options' => ['start' => 'ওপরে', 'center' => 'মাঝে', 'end' => 'নিচে'], 'default' => 'center', 'group' => 'style'],
            ['key' => 'pt', 'label' => 'ভেতরে ওপরের ফাঁক (px)', 'type' => 'number', 'min' => 0, 'max' => 400, 'default' => 90, 'group' => 'style'],
            ['key' => 'pb', 'label' => 'ভেতরে নিচের ফাঁক (px)', 'type' => 'number', 'min' => 0, 'max' => 400, 'default' => 90, 'group' => 'style'],
            ['key' => 'bg', 'label' => 'ব্যাকগ্রাউন্ড', 'type' => 'select', 'options' => ['none' => 'নেই (পেজের রং)', 'dark' => 'গাঢ়', 'card' => 'হালকা বেগুনি', 'gradient' => 'Gradient', 'image' => 'ছবি', 'color' => 'নিজের রং'], 'default' => 'none', 'group' => 'style'],
            ['key' => 'bg_color', 'label' => 'নিজের রং', 'type' => 'color', 'default' => '#1d1047', 'group' => 'style'],
            ['key' => 'bg_image', 'label' => 'ব্যাকগ্রাউন্ড ছবি', 'type' => 'image', 'default' => '', 'group' => 'style'],
            ['key' => 'overlay', 'label' => 'ছবির ওপর অন্ধকার (%)', 'type' => 'number', 'min' => 0, 'max' => 95, 'default' => 60, 'group' => 'style'],
            ['key' => 'fx', 'label' => 'চলমান ব্যাকগ্রাউন্ড', 'type' => 'select', 'options' => ['none' => 'নেই', 'gradient' => 'চলমান Gradient', 'orbs' => 'আলোর গোলক', 'lines' => 'চলমান রেখা', 'grid' => 'আলোকিত গ্রিড', 'particles' => 'ভাসমান বিন্দু'], 'default' => 'none', 'group' => 'style'],
            ['key' => 'gap', 'label' => 'কলামের ফাঁক', 'type' => 'select', 'options' => ['none' => 'কোনো ফাঁক নেই', 's' => 'কম', 'm' => 'মাঝারি', 'l' => 'বেশি'], 'default' => 'l', 'group' => 'style'],
            ['key' => 'valign', 'label' => 'কলামের উল্লম্ব সারিবদ্ধতা', 'type' => 'select', 'options' => ['start' => 'ওপরে', 'center' => 'মাঝে', 'end' => 'নিচে'], 'default' => 'center', 'group' => 'style'],
            ['key' => 'reverse_mobile', 'label' => 'মোবাইলে কলামের ক্রম উল্টো', 'type' => 'checkbox', 'default' => false, 'group' => 'style'],
            ['key' => 'divider_top', 'label' => 'ওপরে ঢেউ আকৃতি', 'type' => 'checkbox', 'default' => false, 'group' => 'style'],
        ], self::motionFields(), self::advancedFields());
    }

    public static function columnFields(): array
    {
        return array_merge([
            ['key' => 'w', 'label' => 'প্রস্থ ডেস্কটপে (%)', 'type' => 'number', 'min' => 10, 'max' => 100, 'default' => 100, 'group' => 'style'],
            ['key' => 'w_tablet', 'label' => 'প্রস্থ ট্যাবলেটে (%)', 'type' => 'number', 'min' => 10, 'max' => 100, 'default' => 100, 'group' => 'style'],
            ['key' => 'w_mobile', 'label' => 'প্রস্থ মোবাইলে (%)', 'type' => 'number', 'min' => 10, 'max' => 100, 'default' => 100, 'group' => 'style'],
            ['key' => 'box', 'label' => 'বক্স স্টাইল', 'type' => 'select', 'options' => ['none' => 'নেই', 'card' => 'কার্ড', 'glass' => 'কাচের মতো', 'glow' => 'Glow বর্ডার'], 'default' => 'none', 'group' => 'style'],
            ['key' => 'pad', 'label' => 'ভেতরের ফাঁক (px)', 'type' => 'number', 'min' => 0, 'max' => 120, 'default' => 0, 'group' => 'style'],
        ], self::motionFields(), self::advancedFields());
    }

    public static function widgetCommonFields(): array
    {
        return array_merge(self::motionFields(), self::advancedFields());
    }

    /** All definitions the admin builder needs, as one JSON-able array. */
    public static function forEditor(): array
    {
        $widgets = [];
        foreach (self::widgets() as $type => $w) {
            $widgets[$type] = $w + ['type' => $type];
        }

        return [
            'version' => self::VERSION,
            'widgets' => $widgets,
            'common' => self::widgetCommonFields(),
            'section' => self::sectionFields(),
            'column' => self::columnFields(),
            'categories' => [
                'layout' => 'লেআউট', 'basic' => 'সাধারণ', 'content' => 'কন্টেন্ট',
                'media' => 'মিডিয়া', 'interactive' => 'ইন্টারঅ্যাক্টিভ', 'dynamic' => 'ডেটাবেস থেকে', 'advanced' => 'অ্যাডভান্সড',
            ],
        ];
    }
}
