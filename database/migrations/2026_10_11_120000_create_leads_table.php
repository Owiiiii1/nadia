<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('goal', 20)->nullable();
            $table->text('message')->nullable();
            $table->string('source', 10)->default('form');
            $table->string('status', 20)->default('new');
            $table->text('admin_note')->nullable();
            $table->string('utm_source', 190)->nullable();
            $table->string('utm_medium', 190)->nullable();
            $table->string('utm_campaign', 190)->nullable();
            $table->string('utm_term', 190)->nullable();
            $table->string('utm_content', 190)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('landing_path', 500)->nullable();
            $table->uuid('visitor_id')->nullable()->index();
            $table->unsignedBigInteger('chat_session_id')->nullable()->index();
            $table->char('ip_hash', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['source', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
