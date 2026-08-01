<?php

use Illuminate\Database\Migrations\Migration;
use App\Infrastructure\Persistence\ItemTemplate;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $items = ItemTemplate::all();
        foreach ($items as $item) {
            if ($item->name) {
                $expectedIcon = Str::slug($item->name) . '.png';
                if (empty($item->icon) || $item->icon === '🥚') {
                    $item->update([
                        'icon' => $expectedIcon,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
    }
};
