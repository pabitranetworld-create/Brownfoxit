<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // store phone numbers in one form so customers can log in with them
        foreach (DB::table('users')->whereNotNull('phone')->get(['id', 'phone']) as $u) {
            DB::table('users')->where('id', $u->id)->update(['phone' => \App\Support\Phone::normalize($u->phone)]);
        }
        Schema::table('users', fn (Blueprint $t) => $t->index('phone'));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropIndex(['phone']));
    }
};
