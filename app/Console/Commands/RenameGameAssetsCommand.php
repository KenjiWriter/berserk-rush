<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\ItemTemplate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class RenameGameAssetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:rename-assets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Zmienia nazwy fizycznych plików (avatarów i ikon) na slug z nazwy i podmienia w bazie';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Rozpoczynam zmianę nazw avatarów potworów...');
        $this->renameMonsters();

        $this->info('Rozpoczynam zmianę nazw ikon przedmiotów...');
        $this->renameItems();

        $this->info('Zakończono pomyślnie!');
    }

    private function renameMonsters()
    {
        $monsters = Monster::all();
        $baseDir = storage_path('app/assets/monsters/avatars');

        foreach ($monsters as $monster) {
            if (empty($monster->avatar)) {
                continue;
            }

            $basename = basename($monster->avatar);
            $oldPath = $baseDir . DIRECTORY_SEPARATOR . $basename;
            
            if (!File::exists($oldPath)) {
                $this->warn("Plik nie istnieje: {$oldPath} dla potwora {$monster->name}");
                continue;
            }

            $extension = File::extension($oldPath) ?: 'png';
            $newName = Str::slug($monster->name) . '.' . $extension;
            $newPath = $baseDir . DIRECTORY_SEPARATOR . $newName;

            if ($oldPath !== $newPath) {
                // Jeśli taki plik już istnieje, by uniknąć nadpisania można usunąć lub dopisać coś
                if (File::exists($newPath)) {
                    File::delete($newPath); // Usuwamy stary, jeśli istniał taki slug
                }
                
                File::move($oldPath, $newPath);
                $monster->update(['avatar' => $newName]);
                $this->info("Zmieniono: {$basename} -> {$newName}");
            }
        }
    }

    private function renameItems()
    {
        $items = ItemTemplate::all();
        // Zakładam, że ikony itemów są w folderze items/icons, jeśli jest inny - dostosuj to!
        $baseDir = storage_path('app/assets/items/icons'); 

        // Jeśli folder nie istnieje, spróbujmy inny popularny
        if (!File::exists($baseDir)) {
            $baseDir = storage_path('app/assets/items');
        }

        foreach ($items as $item) {
            if (empty($item->icon)) {
                continue;
            }

            $basename = basename($item->icon);
            $oldPath = $baseDir . DIRECTORY_SEPARATOR . $basename;

            if (!File::exists($oldPath)) {
                $this->warn("Plik nie istnieje: {$oldPath} dla przedmiotu {$item->name}");
                continue;
            }

            $extension = File::extension($oldPath) ?: 'png';
            $newName = Str::slug($item->name) . '.' . $extension;
            $newPath = $baseDir . DIRECTORY_SEPARATOR . $newName;

            if ($oldPath !== $newPath) {
                if (File::exists($newPath)) {
                    File::delete($newPath);
                }

                File::move($oldPath, $newPath);
                $item->update(['icon' => $newName]);
                $this->info("Zmieniono: {$basename} -> {$newName}");
            }
        }
    }
}
