<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Naprawa niedopasowania typów kolumn względem `item_templates.id`.
 *
 * `item_templates.id` to `$table->ulid('id')->primary()` - fizycznie kolumna
 * `char(26)` (stałej długości), mimo że w praktyce jako id szablonów używane są
 * czytelne slugi (np. 'potion-hp-s'), nie prawdziwe 26-znakowe ULID-y.
 * `merchant_items.item_template_id` oraz `item_recipes.result_item_template_id`
 * zostały pierwotnie zadeklarowane jako zwykły `$table->string(...)` (varchar)
 * zamiast `ulid(...)`, więc relacje Eloquent `belongsTo` (porównanie `char(26)`
 * z `varchar`) nigdy nie dopasowują wierszy na PostgreSQL - `MerchantItem::template()`
 * oraz `ItemRecipe::resultItemTemplate()` zawsze zwracają null, mimo że dane
 * wizualnie się zgadzają (Postgres dopełnia `char(26)` spacjami, które są znaczące
 * przy porównaniu z `varchar`/`text`). Skutek: puste "Półki Sklepowe" u każdego
 * handlarza (Wiedźma/Handlarz/Gladiator) oraz "Nieznany" jako wynik receptur
 * w zakładce Warzenia Mikstur.
 *
 * Kolumny są częścią klucza obcego, więc trzeba go zdjąć przed zmianą typu
 * (MySQL odrzuca to wprost, Postgres na wszelki wypadek tak samo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_items', function (Blueprint $table) {
            $table->dropForeign(['item_template_id']);
        });
        Schema::table('item_recipes', function (Blueprint $table) {
            $table->dropForeign(['result_item_template_id']);
        });

        DB::table('merchant_items')->update(['item_template_id' => DB::raw('TRIM(item_template_id)')]);
        DB::table('item_recipes')->update(['result_item_template_id' => DB::raw('TRIM(result_item_template_id)')]);

        Schema::table('merchant_items', function (Blueprint $table) {
            $table->char('item_template_id', 26)->change();
        });
        Schema::table('item_recipes', function (Blueprint $table) {
            $table->char('result_item_template_id', 26)->change();
        });

        Schema::table('merchant_items', function (Blueprint $table) {
            $table->foreign('item_template_id')->references('id')->on('item_templates')->cascadeOnDelete();
        });
        Schema::table('item_recipes', function (Blueprint $table) {
            $table->foreign('result_item_template_id')->references('id')->on('item_templates')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('merchant_items', function (Blueprint $table) {
            $table->dropForeign(['item_template_id']);
        });
        Schema::table('item_recipes', function (Blueprint $table) {
            $table->dropForeign(['result_item_template_id']);
        });

        Schema::table('merchant_items', function (Blueprint $table) {
            $table->string('item_template_id')->change();
        });
        Schema::table('item_recipes', function (Blueprint $table) {
            $table->string('result_item_template_id')->change();
        });

        Schema::table('merchant_items', function (Blueprint $table) {
            $table->foreign('item_template_id')->references('id')->on('item_templates')->cascadeOnDelete();
        });
        Schema::table('item_recipes', function (Blueprint $table) {
            $table->foreign('result_item_template_id')->references('id')->on('item_templates')->cascadeOnDelete();
        });
    }
};
