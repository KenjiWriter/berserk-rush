<?php

namespace App\Livewire\Admin;

use App\Application\Mail\Actions\SendMailAction;
use App\Infrastructure\Persistence\Suggestion;
use Livewire\Component;
use Livewire\WithPagination;

class Suggestions extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterStatus = 'all';
    public string $filterCategory = 'all';

    public ?int $editingSuggestionId = null;
    public string $adminNotesInput = '';

    protected $queryString = [
        'search'         => ['except' => ''],
        'filterStatus'   => ['except' => 'all'],
        'filterCategory' => ['except' => 'all'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updateStatus(int $suggestionId, string $newStatus): void
    {
        if (!in_array($newStatus, ['new', 'in_progress', 'resolved', 'rejected'])) {
            return;
        }

        $suggestion = Suggestion::find($suggestionId);
        if ($suggestion) {
            $oldStatus = $suggestion->status;
            $suggestion->update(['status' => $newStatus]);

            if ($oldStatus !== $newStatus) {
                $targetCharacterId = $suggestion->character_id ?? $suggestion->user?->characters()->first()?->id;

                if ($targetCharacterId) {
                    $statusLabels = [
                        'new'         => 'Nowa',
                        'in_progress' => 'W trakcie',
                        'resolved'    => 'Rozpatrzona',
                        'rejected'    => 'Odrzucona',
                    ];
                    $statusName = $statusLabels[$newStatus] ?? $newStatus;

                    $subject = 'Aktualizacja statusu zgłoszenia';
                    $body = "Twoje zgłoszenie:\n\n" . $suggestion->content . "\n\nzostało zaktualizowane na status: " . $statusName;

                    if (!empty($suggestion->admin_notes)) {
                        $body .= "\n\nNotatka administratora:\n" . $suggestion->admin_notes;
                    }

                    app(SendMailAction::class)->execute(
                        $targetCharacterId,
                        $subject,
                        $body
                    );
                }
            }

            $this->dispatch('notify', type: 'success', message: 'Status sugestii został zaktualizowany.');
        }
    }

    public function openNotesModal(int $suggestionId): void
    {
        $suggestion = Suggestion::find($suggestionId);
        if ($suggestion) {
            $this->editingSuggestionId = $suggestion->id;
            $this->adminNotesInput = $suggestion->admin_notes ?? '';
        }
    }

    public function closeNotesModal(): void
    {
        $this->editingSuggestionId = null;
        $this->adminNotesInput = '';
    }

    public function saveNotes(): void
    {
        if ($this->editingSuggestionId) {
            $suggestion = Suggestion::find($this->editingSuggestionId);
            if ($suggestion) {
                $suggestion->update([
                    'admin_notes' => trim($this->adminNotesInput) ?: null,
                ]);
                $this->dispatch('notify', type: 'success', message: 'Notatka administratora została zapisana.');
            }
        }

        $this->closeNotesModal();
    }

    public function deleteSuggestion(int $suggestionId): void
    {
        $suggestion = Suggestion::find($suggestionId);
        if ($suggestion) {
            $suggestion->delete();
            $this->dispatch('notify', type: 'success', message: 'Sugestia została usunięta.');
        }
    }

    public function render()
    {
        $query = Suggestion::with(['user', 'character'])->latest();

        if ($this->search !== '') {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('content', 'like', $searchTerm)
                  ->orWhere('category', 'like', $searchTerm)
                  ->orWhereHas('user', function ($uq) use ($searchTerm) {
                      $uq->where('email', 'like', $searchTerm)
                         ->orWhere('name', 'like', $searchTerm);
                  })
                  ->orWhereHas('character', function ($cq) use ($searchTerm) {
                      $cq->where('name', 'like', $searchTerm);
                  });
            });
        }

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterCategory !== 'all') {
            $query->where('category', $this->filterCategory);
        }

        $suggestions = $query->paginate(15);

        $stats = [
            'total'       => Suggestion::count(),
            'new'         => Suggestion::where('status', 'new')->count(),
            'in_progress' => Suggestion::where('status', 'in_progress')->count(),
            'resolved'    => Suggestion::where('status', 'resolved')->count(),
        ];

        return view('livewire.admin.suggestions', [
            'suggestions' => $suggestions,
            'stats'       => $stats,
        ])->layout('components.layouts.app');
    }
}
