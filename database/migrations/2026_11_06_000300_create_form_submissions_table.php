<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('form_submissions')) {
            return;
        }
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();          // contact, quote
            $table->string('status', 20)->default('new')->index(); // new, read, replied, archived, spam
            $table->string('name');
            $table->string('email');
            $table->string('phone', 60)->nullable();
            $table->string('company')->nullable();
            $table->string('subject')->nullable();
            $table->string('service')->nullable();
            $table->string('budget', 100)->nullable();
            $table->string('timeline', 100)->nullable();
            $table->text('message');
            $table->json('data')->nullable();             // anything else the form sent (reference link …)
            $table->string('page_url', 500)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 300)->nullable();
            $table->text('admin_note')->nullable();
            $table->boolean('emailed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
