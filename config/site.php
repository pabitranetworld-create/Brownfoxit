<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image source for `php artisan site:download-images`
    |--------------------------------------------------------------------------
    | Every image path in this project starts with "uploads/YYYY/MM/...", the
    | same layout WordPress uses inside wp-content/. The command downloads
    | <image_source>uploads/YYYY/MM/file into public/uploads/YYYY/MM/file.
    */
    'image_source' => env('SITE_IMAGE_SOURCE', 'https://www.brownfoxit.com/wp-content/'),

    /*
    | Images that do not come from wp-content (saved path => remote URL).
    */
    'extra_images' => [
        'uploads/avatars/admin1.jpg' => 'https://secure.gravatar.com/avatar/d1668eb2d679eaf22c016c62f4a2ec3a093dc3824ffe141b4079fc44f7802ca9?s=120&d=mm&r=g',
    ],

    /*
    | Secret for the GitHub auto-deploy hook (POST /_deploy). Empty = hook disabled.
    */
    'deploy_token' => env('DEPLOY_TOKEN', ''),

    /*
    | First admin user (used by the database seeder).
    */
    'admin' => [
        'name' => env('ADMIN_NAME', 'admin1'),
        'email' => env('ADMIN_EMAIL', 'hello@pabitra.net'),
        'password' => env('ADMIN_PASSWORD', 'ChangeMe@123'),
    ],

];
