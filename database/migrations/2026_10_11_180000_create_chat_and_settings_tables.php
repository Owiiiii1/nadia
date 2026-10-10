<?php

use App\Support\AssistantDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('visitor_id')->nullable()->index();
            $table->char('ip_hash', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->unsignedInteger('messages_count')->default(0);
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('utm_source', 190)->nullable();
            $table->string('utm_medium', 190)->nullable();
            $table->string('utm_campaign', 190)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('landing_path', 500)->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_session_id');
            $table->string('role', 10);
            $table->text('content');
            $table->string('provider', 20)->nullable();
            $table->string('model', 100)->nullable();
            $table->unsignedInteger('tokens_in')->nullable();
            $table->unsignedInteger('tokens_out')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('error', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['chat_session_id', 'id']);
            $table->foreign('chat_session_id')->references('id')->on('chat_sessions')->cascadeOnDelete();
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->foreign('chat_session_id')->references('id')->on('chat_sessions')->nullOnDelete();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('site_settings')->insert([
            [
                'key' => 'chat_enabled',
                'value' => '1',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'assistant_greeting',
                'value' => AssistantDefaults::greeting(),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'assistant_system_prompt',
                'value' => AssistantDefaults::systemPrompt(),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['chat_session_id']);
        });

        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_sessions');
        Schema::dropIfExists('site_settings');
    }
};
