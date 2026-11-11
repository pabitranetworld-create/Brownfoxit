<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('product_categories', 'parent_id')) {
            Schema::table('product_categories', function (Blueprint $t) {
                $t->foreignId('parent_id')->nullable()->after('id')->constrained('product_categories')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $t) {
            $t->dropConstrainedForeignId('parent_id');
        });
    }
};
