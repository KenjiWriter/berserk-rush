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
    public $postToDiscord = false;

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
            $data['source'] = 'admin';
            $newsItem = News::create($data);
            session()->flash('message', 'Aktualność utworzona.');

            if ($this->postToDiscord) {
                $this->sendToDiscord($newsItem->id);
            }
        }

        $this->showModal = false;
        $this->loadNews();
    }

    public function sendToDiscord($id)
    {
        $newsItem = News::findOrFail($id);
        $token = config('services.discord.bot_token');
        $channelId = config('services.discord.update_log_channel_id', '899078131728650272');

        if (empty($token) || empty($channelId)) {
            session()->flash('message', 'Błąd: Brak zdefiniowanego DISCORD_BOT_TOKEN w .env');
            return;
        }

        $payload = "@Update-log notification\n" . $newsItem->title . "\n" . $newsItem->content;

        $response = \Illuminate\Support\Facades\Http::withToken($token, 'Bot')
            ->timeout(10)
            ->post("https://discord.com/api/v10/channels/{$channelId}/messages", [
                'content' => $payload,
                'allowed_mentions' => ['parse' => ['roles', 'users']],
            ]);

        if ($response->successful()) {
            $newsItem->update([
                'discord_message_id' => (string) ($response->json('id') ?? ''),
            ]);
            session()->flash('message', 'Wysłano ogłoszenie na kanał Discord update-log!');
        } else {
            session()->flash('message', 'Błąd wysyłania na Discord: ' . $response->body());
        }

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
        $this->postToDiscord = false;
    }

    public function render()
    {
        return view('livewire.admin.news-list');
    }
}
