<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Services now follow the five main areas: AI Automation, n8n, Software & Development, Graphic & Video, Digital Marketing. */
return new class extends Migration
{
    public function up(): void
    {
        $map = ['web' => 'software', 'media' => 'creative', 'design' => 'creative', 'growth' => 'marketing'];
        foreach ($map as $from => $to) {
            DB::table('services')->where('group', $from)->update(['group' => $to]);
        }
        DB::table('services')->where('url', '/services/n8n-automation')->update(['group' => 'n8n']);
    }

    public function down(): void
    {
    }
};
