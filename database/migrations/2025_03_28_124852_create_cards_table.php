<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();

            // Core identity
            $table->string('name'); // e.g. "Blue-Eyes White Dragon"
            $table->string('card_code')->nullable(); // e.g. Konami "BODE-001"
            $table->string('barcode')->nullable();

            // Descriptive fields
            $table->text('description')->nullable();
            $table->string('front_image_path')->nullable();
            $table->string('back_image_path')->nullable();

            // Common TCG catalog metadata (helps filtering/searching)
            $table->string('game')->nullable();        // "Yu-Gi-Oh", "Pokemon", "MTG"
            $table->string('brand')->nullable();       // "Konami", "Wizards", etc.
            $table->string('language', 10)->nullable(); // "EN", "JP"
            $table->string('rarity')->nullable();      // "Common", "SR", "UR"
            $table->string('finish')->nullable();      // "Normal", "Foil", "Holo"

            // Optional: if you want to link later via bridge table, keep this nullable (or remove it)
            $table->unsignedBigInteger('set_id')->nullable(); // no FK yet (bridge later)

            // Useful flags
            $table->boolean('is_active')->default(true);
            $table->boolean('is_sealed_product')->default(false); // if you reuse this table for sealed SKU-like items

            // Optional extras that are handy later
            $table->string('collector_number')->nullable(); // e.g. "001/100" (for games that use it)
            $table->date('release_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes / constraints
            $table->unique('card_code'); // if card_code is truly unique in your shop
            $table->unique('barcode');   // optional but helpful if used
            $table->index(['name']);
            $table->index(['game', 'brand']);
            $table->index(['rarity', 'finish', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
