<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_duelmas', function (Blueprint $table) {
            $table->longText('payload')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('card_duelmas', function (Blueprint $table) {
            $table->dropColumn('payload');
        });
    }
};
