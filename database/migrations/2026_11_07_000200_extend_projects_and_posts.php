<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Portfolio case studies and blog SEO fields; source_url records where imported content came from. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $t) {
            if (! Schema::hasColumn('projects', 'summary')) {
                $t->text('summary')->nullable()->after('category');
                $t->string('tags')->nullable()->after('summary');
                $t->json('gallery')->nullable()->after('image');
                $t->string('video_url')->nullable()->after('url');
                $t->string('client')->nullable();
                $t->string('year', 10)->nullable();
                $t->string('services')->nullable();
                $t->longText('challenge')->nullable();
                $t->longText('solution')->nullable();
                $t->longText('result')->nullable();
                $t->string('status', 20)->default('published')->index();
                $t->string('seo_title')->nullable();
                $t->string('seo_description', 500)->nullable();
                $t->string('source_url')->nullable();
            }
        });
        Schema::table('posts', function (Blueprint $t) {
            if (! Schema::hasColumn('posts', 'seo_title')) {
                $t->string('seo_title')->nullable();
                $t->string('seo_description', 500)->nullable();
                $t->string('tags')->nullable();
                $t->string('source_url')->nullable();
            }
        });
        if (! Schema::hasTable('import_logs')) {
            Schema::create('import_logs', function (Blueprint $t) {
                $t->id();
                $t->string('source');            // site URL
                $t->string('kind', 20);          // post, project, product, category
                $t->string('source_id', 40);
                $t->string('source_url', 500)->nullable();
                $t->string('title');
                $t->string('target_type', 40)->nullable();
                $t->unsignedBigInteger('target_id')->nullable();
                $t->string('status', 20);        // imported, skipped, missing_images, failed
                $t->text('notes')->nullable();
                $t->timestamps();
                $t->unique(['source', 'kind', 'source_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
