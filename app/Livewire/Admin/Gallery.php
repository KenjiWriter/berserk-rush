<?php

namespace App\Livewire\Admin;

use App\Infrastructure\Persistence\GalleryImage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class Gallery extends Component
{
    use WithFileUploads;

    public $photo;
    public $title;

    public function render()
    {
        return view('livewire.admin.gallery', [
            'images' => GalleryImage::orderBy('order')->get()
        ])->layout('components.layouts.app');
    }

    public function save()
    {
        $this->validate([
            'photo' => 'required|image|max:4096', // 4MB Max
            'title' => 'required|string|max:255',
        ]);

        $path = $this->photo->store('gallery', 'public');

        GalleryImage::create([
            'title' => $this->title,
            'image_path' => 'storage/' . $path,
            'order' => GalleryImage::max('order') + 1,
            'is_active' => true,
        ]);

        $this->photo = null;
        $this->title = null;

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Zdjęcie dodane do galerii!']);
    }

    public function toggleActive($id)
    {
        $image = GalleryImage::findOrFail($id);
        $image->is_active = !$image->is_active;
        $image->save();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Zmieniono status zdjęcia.']);
    }

    public function deleteImage($id)
    {
        $image = GalleryImage::findOrFail($id);
        
        // Remove 'storage/' prefix to get the actual path in 'public' disk
        $actualPath = str_replace('storage/', '', $image->image_path);
        if (Storage::disk('public')->exists($actualPath)) {
            Storage::disk('public')->delete($actualPath);
        }
        
        $image->delete();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Zdjęcie usunięte.']);
    }
}
