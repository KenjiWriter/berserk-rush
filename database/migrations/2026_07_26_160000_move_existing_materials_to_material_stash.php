<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Move all item instances whose template type is 'material' and current location is 'inventory' to 'material_stash'
        $materialTemplateIds = DB::table('item_templates')
            ->where('type', 'material')
            ->pluck('id');

        if ($materialTemplateIds->isNotEmpty()) {
            DB::table('item_instances')
                ->whereIn('template_id', $materialTemplateIds)
                ->where('location', 'inventory')
                ->update(['location' => 'material_stash']);
        }
    }

    public function down(): void
    {
        $materialTemplateIds = DB::table('item_templates')
            ->where('type', 'material')
            ->pluck('id');

        if ($materialTemplateIds->isNotEmpty()) {
            DB::table('item_instances')
                ->whereIn('template_id', $materialTemplateIds)
                ->where('location', 'material_stash')
                ->update(['location' => 'inventory']);
        }
    }
};
