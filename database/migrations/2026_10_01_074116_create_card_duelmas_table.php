<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_duelmas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('set_code', 50)->index();          // e.g. DM24-EX1
            $table->string('collector_number', 50)->index();   // e.g. 77/89
            $table->string('card_type', 50)->nullable();      // Creature, Tamaseed, Spell
            $table->string('civilization', 50)->nullable();   // Darkness, Fire, Light, etc.
            $table->string('mana_cost', 20)->nullable();      // 2, 5, etc.
            $table->string('races')->nullable();              // Death Puppet / RexStars
            $table->text('effect_text')->nullable();          // English translated text
            $table->string('image_path')->nullable();         // local relative path e.g. duel_masters/xxx.jpg
            $table->timestamps();

            // Prevent duplicate entries for the exact same card in a set
            $table->unique(['set_code', 'collector_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_duelmas');
    }
};
