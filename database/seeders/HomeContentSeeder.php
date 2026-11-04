<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\Service;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Lists shown on the home page, copied from the current brownfoxit.com.
 */
class HomeContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->services();
        $this->clients();
        $this->projects();
        $this->testimonials();
        $this->posts();
    }

    protected function services(): void
    {
        $items = [
            ['Web Design & Development', 'fa-solid fa-laptop-code', 'blue', 'I can do any kind of web design and development. Like: Custom website, WordPress, WIX, Shopify ETC'],
            ['Graphic Design', 'fa-solid fa-pen-ruler', 'orange', 'I can do any kind of graphic design. Like: Logo, Banner, Poster, UI/UX, ETC'],
            ['Video Editing', 'fa-solid fa-file-video', 'green', 'Any kind of video editing and video creation I can do. Like: Movie or short video editing, Explainer video, ETC'],
            ['Data Science & Analytics', 'fa-solid fa-database', 'purple', 'Any kind of data research and marketing I can do.'],
        ];

        foreach ($items as $i => [$title, $icon, $color, $text]) {
            Service::updateOrCreate(['slug' => Str::slug($title)], [
                'title' => $title,
                'icon' => $icon,
                'icon_color' => $color,
                'short_description' => $text,
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }
    }

    protected function clients(): void
    {
        $logos = [
            'uploads/2023/12/dark-client1.png',
            'uploads/2023/12/dark-client2.png',
            'uploads/2023/12/dark-client3.png',
            'uploads/2023/12/dark-client4.png',
        ];

        foreach ($logos as $i => $logo) {
            Client::updateOrCreate(['name' => 'Client '.($i + 1)], [
                'logo' => $logo,
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }
    }

    protected function projects(): void
    {
        $items = [
            ['Premium Real Estate Website Template', 'Web Design', 'uploads/2026/05/T-6-600x558.jpg'],
            ['Premium Pizza Restaurant Website', 'Web Design', 'uploads/2026/05/T-5-600x558.jpg'],
            ['Outstanding Japanese Sushi Restaurant Website', 'Web Design', 'uploads/2026/05/T-3-600x558.jpg'],
            ['Premium E-Commerce Landing Page', 'Web Design', 'uploads/2026/05/T-1-600x558.jpg'],
            ['Premium Indian Ethnic Fashion Store', 'Development, Web Design', 'uploads/2026/05/T-600x558.jpg'],
            ['Accounting + Inventory management software', 'Development', 'uploads/2023/08/Screenshot_381-600x558.png'],
        ];

        foreach ($items as $i => [$title, $category, $image]) {
            Project::updateOrCreate(['slug' => Str::slug($title)], [
                'title' => $title,
                'category' => $category,
                'image' => $image,
                'is_featured' => true,
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }
    }

    protected function testimonials(): void
    {
        $items = [
            ['Elizabeth K.', 'Managing Director', 'uploads/2026/05/45f7344b74553f2b6a1a869f8c98cce4_150x150-132x132.jpg', 'He is really good person!!!he helps me a lot with my log and this I appreciate!!thanks my friend for all your efforts!!until the next time!!'],
            ['Lockke L.', 'Leadership Team', 'uploads/2026/05/a9f2db788f16bcf44277d7dc873c0be2_70x70.jpg', 'Pabitra did an excellent job on my logo design for my new Abe game. I will definitely recommend this guy for any type of work. Thanks again!'],
            ['Amina A.', 'Senior Consultant', 'uploads/2026/05/563e5588a7679896ab43b65706e37076_70x70.jpg', 'Creative, efficient and friendly- highly recommended!'],
            ['PHIL S.', 'Managing Director', 'uploads/2026/05/f0609bc5be8e8d2a61d3f2481ee3d8a0_70x70.jpg', 'Great Great and great experience to work with Pabitra.'],
            ['Andrea R.', 'Leadership Team', 'uploads/2026/05/127113_70x70.png', 'Will definitely use Pabitra again, very good work. Thank you so much and you will be hearing from me soon'],
            ['Beakz B.', 'Senior Consultant', 'uploads/2023/11/h10-testimonial6-132x132.jpg', 'This guy was very patient with me and rode all the way to the end. Thanks for that, and after all came up with a cool logo for me. Thanks!'],
        ];

        foreach ($items as $i => [$name, $role, $avatar, $content]) {
            Testimonial::updateOrCreate(['name' => $name], [
                'designation' => $role,
                'avatar' => $avatar,
                'content' => $content,
                'rating' => 5,
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }
    }

    protected function posts(): void
    {
        $author = User::where('role', User::ROLE_ADMIN)->first();

        $items = [
            ['Why Every Business Needs a Professional Website in 2026', 'Digital Products', '2023-07-19', 'uploads/2023/08/blog-01-600x412.jpg'],
            ['The Importance of UI/UX Design for Modern Businesses', 'Digital Marketing', '2023-07-19', 'uploads/2023/08/blog-02-600x412.jpg'],
            ['How Graphic Design Helps Businesses Grow Faster', 'Digital SEO', '2023-07-19', 'uploads/2023/07/blog-03-600x412.jpg'],
            ['Why Data Analytics is Essential for Business Growth', 'Apps Design', '2023-07-19', 'uploads/2023/07/blog-04-600x412.jpg'],
            ['10 Digital Marketing Stats That Will Impact Your Business', 'Digital Products', '2023-07-04', 'uploads/2023/07/blog-05-600x412.jpg'],
            ['How to Protect Your Brand Using Reputation Management', 'Digital SEO', '2023-07-04', 'uploads/2023/07/blog-06-600x412.jpg'],
        ];

        // Posts on the same date keep the original order (newest first) via the time part.
        foreach ($items as $i => [$title, $category, $date, $image]) {
            $cat = PostCategory::firstOrCreate(['slug' => Str::slug($category)], ['name' => $category]);

            Post::updateOrCreate(['slug' => Str::slug($title)], [
                'post_category_id' => $cat->id,
                'user_id' => $author?->id,
                'title' => $title,
                'excerpt' => null,
                'body' => '<p>'.e($title).'</p>',
                'image' => $image,
                'status' => Post::STATUS_PUBLISHED,
                'published_at' => Carbon::parse($date)->setTime(12, 0)->subMinutes($i),
            ]);
        }
    }
}
