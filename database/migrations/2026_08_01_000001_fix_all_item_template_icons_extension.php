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
                $rawIcon = $item->getRawOriginal('icon');
                if (empty($rawIcon) || $rawIcon === '🥚' || $rawIcon === '📦' || !str_contains($rawIcon, '.')) {
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
