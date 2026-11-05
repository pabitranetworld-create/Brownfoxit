<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'group')) {
                // ai, web, software, media, design, growth – used to group the Services page and menus
                $table->string('group', 20)->nullable()->after('icon_color');
                $table->string('url')->nullable()->after('group');                         // service page, e.g. /services/ai-automation
                $table->boolean('show_on_home')->default(true)->after('is_active');      // the home page slider
                $table->boolean('show_in_catalog')->default(true)->after('show_on_home'); // Services page / grids
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['group', 'url', 'show_on_home', 'show_in_catalog']);
        });
    }
};
