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
        Schema::table('url_mappings', function (Blueprint $table) {
            $table->string('admin_hash')->unique()->nullable()->after('url');
            $table->string('email')->nullable()->after('admin_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('url_mappings', function (Blueprint $table) {
            $table->dropColumn(['admin_hash', 'email']);
        });
    }
};
