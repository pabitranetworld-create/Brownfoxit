<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Page layout (like Elementor): default | full_width | canvas,
 * plus page-level custom code for fully custom HTML pages.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('template', 20)->default('default')->after('is_template');
            $table->longText('custom_head')->nullable()->after('header_subtitle');
            $table->longText('custom_css')->nullable()->after('custom_head');
            $table->longText('custom_js')->nullable()->after('custom_css');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['template', 'custom_head', 'custom_css', 'custom_js']);
        });
    }
};
