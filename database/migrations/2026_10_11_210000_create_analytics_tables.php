<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_id')->nullable();
            $table->uuid('session_id')->nullable();
            $table->string('path', 500);
            $table->string('referrer', 500)->nullable();
            $table->string('referrer_host', 190)->nullable();
            $table->string('utm_source', 190)->nullable();
            $table->string('utm_medium', 190)->nullable();
            $table->string('utm_campaign', 190)->nullable();
            $table->string('utm_term', 190)->nullable();
            $table->string('utm_content', 190)->nullable();
            $table->string('device', 10)->nullable();
            $table->string('browser', 40)->nullable();
            $table->string('os', 40)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['visitor_id', 'created_at']);
            $table->index('session_id');
            $table->index('referrer_host');
        });

        Schema::create('site_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_id')->nullable();
            $table->uuid('session_id')->nullable();
            $table->string('type', 30)->index();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['type', 'created_at']);
            $table->index(['visitor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_events');
        Schema::dropIfExists('page_views');
    }
};
