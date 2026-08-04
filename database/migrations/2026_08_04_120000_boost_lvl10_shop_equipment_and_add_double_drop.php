<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ponowne uruchomienie ShopEquipmentSeeder w celu aktualizacji bazy danych
        app(\Database\Seeders\ShopEquipmentSeeder::class)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nie ma potrzeby cofać w dół, ponieważ zbalansowany seeder generuje poprawne rekordy
    }
};
