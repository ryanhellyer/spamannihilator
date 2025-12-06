<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('path_counters', function (Blueprint $table) {
            $table->id();
            $table->string('path', 255)->unique();
            $table->unsignedInteger('hit_count')->default(0);
            $table->timestamps();
            
            $table->index('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('path_counters');
    }
};
