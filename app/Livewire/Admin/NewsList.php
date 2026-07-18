<?php

namespace App\Livewire\Admin;

use App\Infrastructure\Persistence\News;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

#[Layout('components.layouts.app')]
class NewsList extends Component
{
    public $news;

    // Form fields
    public $newsId;
    public $title;
    public $content;
    public $published_at;

    // UI state
    public $isEditing = false;
    public $showModal = false;

    protected $rules = [
        'title' => 'required|string|max:255',
        'content' => 'required|string',
        'published_at' => 'nullable|date',
    ];

    public function mount()
    {
        $this->loadNews();
    }

    public function loadNews()
    {
        $this->news = News::orderBy('published_at', 'desc')->get();
    }

    public function create()
    {
        $this->resetForm();
        $this->published_at = now()->format('Y-m-d');
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->resetForm();
        $newsItem = News::findOrFail($id);
        
        $this->newsId = $newsItem->id;
        $this->title = $newsItem->title;
        $this->content = $newsItem->content;
        $this->published_at = $newsItem->published_at ? $newsItem->published_at->format('Y-m-d') : null;
        
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'content' => $this->content,
            'published_at' => $this->published_at ? Carbon::parse($this->published_at) : null,
        ];

        if ($this->isEditing) {
            $newsItem = News::findOrFail($this->newsId);
            $newsItem->update($data);
            session()->flash('message', 'Aktualność zaktualizowana.');
        } else {
            News::create($data);
            session()->flash('message', 'Aktualność utworzona.');
        }

        $this->showModal = false;
        $this->loadNews();
    }

    public function delete($id)
    {
        News::findOrFail($id)->delete();
        session()->flash('message', 'Aktualność usunięta.');
        $this->loadNews();
    }

    private function resetForm()
    {
        $this->newsId = null;
        $this->title = '';
        $this->content = '';
        $this->published_at = null;
    }

    public function render()
    {
        return view('livewire.admin.news-list');
    }
}
