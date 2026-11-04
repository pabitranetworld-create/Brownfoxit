<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Text and small lists from the current brownfoxit.com home page.
 * Image paths are relative to /public (see `php artisan site:download-images`).
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->settings() as $key => $value) {
            Setting::put($key, $value);
        }
    }

    protected function settings(): array
    {
        return [

            'site' => [
                'name' => 'Brown Fox IT',
                'meta_title' => 'Brown Fox IT - Web Design, Graphic Design, UI/UX & Video Editing',
                'meta_description' => 'Brown Fox IT – Web Design & Development, Graphic Design, UI/UX Design, Video Editing and Digital Marketing by Arjun Kumar (Pabitra).',
                'logo' => 'uploads/2023/07/Brown-Fox-it-logo--scaled.png',
                'favicon' => 'uploads/2026/05/cropped-Brown-Fox-IT-Favion-32x32.png',
                'email' => 'hello@pabitra.net',
                'phone' => '+8801305641548',
                'whatsapp_url' => 'https://wa.me/message/TFMPI57DW4PUM1',
                'address' => 'Noibari, Vadun, Pubail, Gazipur',
                'youtube_video_id' => 'SF4aHwxHtZ0',
                'social' => [
                    ['icon' => 'fa-brands fa-linkedin', 'label' => 'LinkedIn', 'url' => 'https://www.linkedin.com/in/pabitra-sarkar/'],
                    ['icon' => 'fa-brands fa-behance', 'label' => 'Behance', 'url' => 'https://www.behance.net/pabitra_sarkar'],
                    ['icon' => 'fa-brands fa-dribbble', 'label' => 'Dribbble', 'url' => 'https://dribbble.com/Pabitras_Galary'],
                    ['icon' => 'fa-brands fa-whatsapp', 'label' => 'WhatsApp', 'url' => 'https://api.whatsapp.com/message/TFMPI57DW4PUM1'],
                ],
            ],

            // Main menu. "url" can be a path ("/blog") or an anchor on the home page ("/#about").
            'menu' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'About', 'url' => '/#about'],
                ['label' => 'Services', 'url' => '/#services'],
                ['label' => 'Portfolio', 'url' => '/#portfolio'],
                ['label' => 'Shop', 'url' => '#', 'children' => [
                    ['label' => 'Our Product', 'url' => '#'],
                    ['label' => 'Cart', 'url' => '#'],
                    ['label' => 'Checkout', 'url' => '#'],
                    ['label' => 'My account', 'url' => '#'],
                ]],
                ['label' => 'Contact Us', 'url' => '/#contact'],
            ],

            'hero' => [
                'powered_by' => 'Powered by: pabitra.net',
                'title' => 'Brown Fox IT',
                'subtitle' => 'Web Design & Development | Graphic Design | UI/UX Design | Video Editing | Digital Marketing',
                'button_text' => 'Contact Us',
                'button_url' => '/#contact',
                'person_image' => 'uploads/2026/03/Arjun-Kumar-Pabitra-Pabitra-Sarkar-12-222.png',
                'badge_image' => 'uploads/2026/04/h10-banner-2.png',
                'rating_title' => 'Happy Clients & Rating',
                'rating_value' => '4.9 (13,090 )',
                'rating_avatars' => [
                    'uploads/2026/05/127113_70x70-55x55.png',
                    'uploads/2026/05/f0609bc5be8e8d2a61d3f2481ee3d8a0_70x70-55x55.jpg',
                    'uploads/2026/05/45f7344b74553f2b6a1a869f8c98cce4_150x150-55x55.jpg',
                    'uploads/2026/05/563e5588a7679896ab43b65706e37076_70x70-55x55.jpg',
                ],
            ],

            'about' => [
                'subtitle' => 'About Arjun Kumar (Pabitra)',
                'title' => 'Full Stack Digital Visualizer',
                'intro' => 'I’m a <strong>Full Stack Web Developer &amp; Digital Solutions Expert</strong> with 15+ years of experience helping businesses build and grow their digital presence.',
                'body' => 'I create high-performing websites, strong brand identities, and scalable digital systems that drive real results. I provide end-to-end digital solutions tailored to each client’s needs—combining strategy, design, and technology to improve user experience, increase conversions, and support business growth.',
                'image' => 'uploads/2026/05/Arjun-Kumar-Pabitra-007.png',
                'video_label' => 'Learn More About Me',
                'partner_label' => 'Sync Partner',
                'partner_logo' => 'uploads/2026/05/Pabitra-net-logo-Black-300x86.png',
                'button_text' => 'Contact Us',
                'button_url' => '/#contact',
                'skills' => [
                    ['label' => 'Web Design & Development', 'value' => 95, 'color' => 'blue'],
                    ['label' => 'Brand Design', 'value' => 98, 'color' => 'orange'],
                    ['label' => 'Video Editing', 'value' => 90, 'color' => 'green'],
                ],
            ],

            'service_cards' => [
                'subtitle' => 'Services',
                'title' => 'I will provide awesome services',
                'items' => [
                    ['title' => 'UI/UX Design', 'image' => 'uploads/2026/05/gradient-ui-ux-background_23-2149052117.avif', 'url' => '/#portfolio'],
                    ['title' => 'WordPress Web Design', 'image' => 'uploads/2026/05/WordPress.jpg', 'url' => '/#portfolio'],
                    ['title' => 'Landing Page Design', 'image' => 'uploads/2026/05/Landing-Page.jpg', 'url' => '/#portfolio'],
                    ['title' => 'Management System Software', 'image' => 'uploads/2026/05/Management-Software.jpg', 'url' => '/#portfolio'],
                ],
            ],

            'marketplace' => [
                'title' => 'Visit My Portfolio',
                'subtitle' => 'Click on the marketplace logo to see my portfolio',
                'items' => [
                    ['name' => 'Dribbble', 'image' => 'uploads/2026/05/D.png', 'url' => 'https://dribbble.com/Pabitras_Galary'],
                    ['name' => 'Behance', 'image' => 'uploads/2026/05/b.png', 'url' => 'https://www.behance.net/pabitra_sarkar'],
                    ['name' => 'Magnific', 'image' => 'uploads/2026/05/M.png', 'url' => 'https://www.magnific.com/author/pabitrasarkar'],
                    ['name' => 'Creative Market', 'image' => 'uploads/2026/05/Creative-market.png', 'url' => '#'],
                ],
            ],

            'counters' => [
                ['value' => 4, 'suffix' => 'K', 'label' => 'Completed Projects', 'color' => 'blue'],
                ['value' => 1, 'suffix' => 'K', 'label' => 'Happy Clients', 'color' => 'orange'],
                ['value' => 15, 'suffix' => '%', 'label' => 'Positive Feedback', 'color' => 'green'],
                ['value' => 15, 'suffix' => '%', 'label' => 'Success Rate', 'color' => 'purple'],
            ],

            'projects_section' => [
                'subtitle' => 'Pabitra.net Original',
                'title' => 'Some of my projects in marketplace',
                'text' => 'Those products are ongoing selling products at many marketplaces.',
                'button_text' => 'All Projects',
                'button_url' => '#',
            ],

            'testimonials_section' => [
                'subtitle' => 'Testimonials',
                'title' => 'I always dedicated to satisfy the clients',
                'floating_avatars' => [
                    'uploads/2026/05/45f7344b74553f2b6a1a869f8c98cce4_150x150-100x100.jpg',
                    'uploads/2026/05/a9f2db788f16bcf44277d7dc873c0be2_70x70.jpg',
                    'uploads/2026/05/563e5588a7679896ab43b65706e37076_70x70.jpg',
                    'uploads/2026/05/f0609bc5be8e8d2a61d3f2481ee3d8a0_70x70.jpg',
                    'uploads/2026/05/127113_70x70.png',
                    'uploads/2023/11/h10-testimonial6-100x100.jpg',
                ],
            ],

            'steps' => [
                'subtitle' => 'Steps',
                'title' => 'Easy steps to get started for web application development',
                'items' => [
                    ['title' => 'Project Planning', 'text' => 'I start by understanding your business goals, target audience, and requirements to create a clear roadmap for your website.', 'image' => 'uploads/2026/05/9-Factors-to-Consider-When-Planning-Your-Website-226x182.jpg'],
                    ['title' => 'Design & Prototyping', 'text' => 'I crafts modern, user-friendly UI/UX designs and interactive prototypes to visualize your website before development.', 'image' => 'uploads/2026/05/Apps-Nado-Blog-Post-002-226x182.jpg'],
                    ['title' => 'Development & Testing', 'text' => 'We build a fast, responsive, and secure website using the latest technologies, followed by thorough testing across all devices.', 'image' => 'uploads/2026/05/which-development-job-is-right-for-you-226x182.jpg'],
                    ['title' => 'Launch & Support', 'text' => 'After launch, we provide ongoing support, maintenance, and updates to ensure your website performs at its best.', 'image' => 'uploads/2026/05/How-To-Determine-if-Youre-Ready-to-Launch-Your-Website-1024x907-1-226x182.jpg'],
                ],
            ],

            'cta' => [
                'title' => 'Contact With me right now',
                'text' => 'Click on WhatsApp or LinkedIn button',
                'image' => 'uploads/2026/05/Arjun-Kumar-Pabitra-Pabitra-Sarkar-12-1-1-1.png',
                'whatsapp_url' => 'https://wa.me/message/TFMPI57DW4PUM1',
                'linkedin_url' => 'https://www.linkedin.com/in/pabitra-sarkar/',
            ],

            'blog_section' => [
                'subtitle' => 'Latest Blog',
                'title' => 'Learn more from our latest blog',
            ],

            'footer' => [
                'logo' => 'uploads/2023/07/Brown-Fox-it-logo--scaled.png',
                'about_title' => 'Full Stack Web Developer & Digital Solutions Expert',
                'about_text' => 'Web Design & Development | Graphic Design | UI/UX Design | Video Editing | Digital Marketing',
                'links' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Services', 'url' => '/#services'],
                    ['label' => 'About us', 'url' => '/#about'],
                    ['label' => 'Our Team', 'url' => '#'],
                    ['label' => 'Testimonials', 'url' => '#'],
                    ['label' => 'News', 'url' => '#'],
                ],
                'support' => [
                    ['label' => 'Contact Us', 'url' => '/#contact'],
                    ['label' => 'Submit a Ticket', 'url' => '#'],
                    ['label' => 'Visit Knowledge Base', 'url' => '#'],
                    ['label' => 'Support System', 'url' => '#'],
                    ['label' => 'Privacy Policy', 'url' => '#'],
                    ['label' => 'Professional Services', 'url' => '#'],
                ],
                'gallery' => [
                    ['thumb' => 'uploads/2023/10/g-img-7-300x300.jpg', 'full' => 'uploads/2023/10/g-img-7.jpg'],
                    ['thumb' => 'uploads/2023/10/g-img-8-300x300.jpg', 'full' => 'uploads/2023/10/g-img-8.jpg'],
                    ['thumb' => 'uploads/2023/10/g-img-9-300x300.jpg', 'full' => 'uploads/2023/10/g-img-9.jpg'],
                    ['thumb' => 'uploads/2023/10/g-img-10-300x300.jpg', 'full' => 'uploads/2023/10/g-img-10.jpg'],
                    ['thumb' => 'uploads/2023/10/g-img-11-300x300.jpg', 'full' => 'uploads/2023/10/g-img-11.jpg'],
                    ['thumb' => 'uploads/2023/10/g-img-12-300x300.jpg', 'full' => 'uploads/2023/10/g-img-12.jpg'],
                ],
                'payment_logos' => 'uploads/2023/12/Footer-Logo-1-1-1024x82.png',
                'copyright_text' => 'All rights reserved by',
                'copyright_link' => 'brownfoxit.com',
            ],

        ];
    }
}
