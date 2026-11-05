<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pages', 'color_scheme')) {
            Schema::table('pages', function (Blueprint $table) {
                // Inner pages are white by default; "dark" keeps the home-page look.
                $table->string('color_scheme', 10)->default('light')->after('template');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pages', 'color_scheme')) {
            Schema::table('pages', fn (Blueprint $table) => $table->dropColumn('color_scheme'));
        }
    }
};
