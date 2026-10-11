<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_codes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('placement', 20);
            $table->longText('code');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
            $table->index(['is_active', 'placement', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_codes');
    }
};
