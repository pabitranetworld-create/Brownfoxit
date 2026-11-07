<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extra SEO settings for any public page (title/description/og image/noindex stay on the
        // content tables that already have them; everything else lives here).
        if (! Schema::hasTable('seo_meta')) {
            Schema::create('seo_meta', function (Blueprint $t) {
                $t->id();
                $t->string('subject_type', 40);           // page, post, project, product, post_category, product_category, route
                $t->string('subject_key', 120);            // record id, or route name for fixed pages (home, shop, blog …)
                $t->string('title')->nullable();
                $t->string('description', 500)->nullable();
                $t->string('canonical', 500)->nullable();
                $t->boolean('noindex')->default(false);
                $t->boolean('nofollow')->default(false);
                $t->string('robots_extra', 120)->nullable(); // e.g. max-image-preview:large
                $t->string('og_title')->nullable();
                $t->string('og_description', 500)->nullable();
                $t->string('og_image')->nullable();
                $t->string('og_image_alt')->nullable();
                $t->string('twitter_card', 30)->nullable();
                $t->string('focus_keyword', 120)->nullable();
                $t->string('secondary_keywords', 500)->nullable();
                $t->text('schema_override')->nullable();     // validated JSON-LD object, merged into the page graph
                $t->string('breadcrumb_label', 120)->nullable();
                $t->boolean('hide_breadcrumbs')->default(false);
                $t->text('notes')->nullable();
                $t->timestamps();
                $t->unique(['subject_type', 'subject_key']);
            });
        }

        if (! Schema::hasTable('redirects')) {
            Schema::create('redirects', function (Blueprint $t) {
                $t->id();
                $t->string('from_path', 500);
                $t->string('to_url', 500);
                $t->unsignedSmallInteger('status_code')->default(301);
                $t->boolean('is_active')->default(true);
                $t->string('source', 30)->default('manual');  // manual, slug-change, wordpress
                $t->unsignedInteger('hits')->default(0);
                $t->timestamp('last_hit_at')->nullable();
                $t->string('notes', 255)->nullable();
                $t->timestamps();
                $t->index('is_active');
            });
            // unique on a long string needs a prefix on older MySQL; a hash column keeps it portable
            Schema::table('redirects', function (Blueprint $t) {
                $t->char('from_hash', 32)->nullable()->after('from_path');
                $t->unique('from_hash');
            });
        }

        if (! Schema::hasTable('seo_audits')) {
            Schema::create('seo_audits', function (Blueprint $t) {
                $t->id();
                $t->string('url', 500);
                $t->string('subject_type', 40)->nullable();
                $t->string('subject_key', 120)->nullable();
                $t->unsignedSmallInteger('status_code')->nullable();
                $t->string('title', 500)->nullable();
                $t->string('description', 1000)->nullable();
                $t->string('canonical', 500)->nullable();
                $t->string('robots', 120)->nullable();
                $t->unsignedTinyInteger('h1_count')->default(0);
                $t->unsignedSmallInteger('images_without_alt')->default(0);
                $t->unsignedInteger('words')->default(0);
                $t->json('links')->nullable();      // internal links found on the page
                $t->json('issues')->nullable();     // [{level, code, text}]
                $t->boolean('schema_ok')->default(true);
                $t->timestamp('checked_at')->nullable();
                $t->timestamps();
                $t->index('checked_at');
            });
        }

        if (Schema::hasTable('post_categories') && ! Schema::hasColumn('post_categories', 'description')) {
            Schema::table('post_categories', fn (Blueprint $t) => $t->text('description')->nullable());
        }

        // Faster lookups used by the sitemap and the audit.
        foreach (['posts' => 'published_at'] as $table => $col) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $col)) {
                rescue(fn () => Schema::table($table, fn (Blueprint $t) => $t->index($col, $table.'_'.$col.'_seo_idx')), null, false);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_audits');
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('seo_meta');
    }
};
