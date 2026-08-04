<?php

namespace App\Livewire\Admin;

use App\Infrastructure\Persistence\AntiCheatFlag;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class AntiCheat extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterStatus = 'open';
    public string $filterSeverity = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterSeverity(): void
    {
        $this->resetPage();
    }

    public function markReviewed(int $flagId): void
    {
        $this->setStatus($flagId, 'reviewed');
    }

    public function dismiss(int $flagId): void
    {
        $this->setStatus($flagId, 'dismissed');
    }

    public function reopen(int $flagId): void
    {
        $flag = AntiCheatFlag::find($flagId);
        if ($flag) {
            $flag->update([
                'status' => 'open',
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
            $this->dispatch('notify', type: 'success', message: 'Zgłoszenie przywrócone jako otwarte.');
        }
    }

    private function setStatus(int $flagId, string $status): void
    {
        $flag = AntiCheatFlag::find($flagId);
        if ($flag) {
            $flag->update([
                'status' => $status,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);
            $this->dispatch('notify', type: 'success', message: 'Status zgłoszenia zaktualizowany.');
        }
    }

    public function render()
    {
        $query = AntiCheatFlag::with(['character.user', 'reviewer'])->latest('id');

        if ($this->search !== '') {
            $searchTerm = '%' . $this->search . '%';
            $query->whereHas('character', function ($cq) use ($searchTerm) {
                $cq->where('name', 'ilike', $searchTerm)
                    ->orWhereHas('user', function ($uq) use ($searchTerm) {
                        $uq->where('name', 'ilike', $searchTerm)
                            ->orWhere('email', 'ilike', $searchTerm);
                    });
            });
        }

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterSeverity !== 'all') {
            $query->where('severity', $this->filterSeverity);
        }

        $flags = $query->paginate(15);

        $stats = [
            'open' => AntiCheatFlag::where('status', 'open')->count(),
            'open_high' => AntiCheatFlag::where('status', 'open')->where('severity', 'high')->count(),
            'reviewed_today' => AntiCheatFlag::where('status', 'reviewed')
                ->whereDate('reviewed_at', now()->toDateString())
                ->count(),
            'total' => AntiCheatFlag::count(),
        ];

        return view('livewire.admin.anti-cheat', [
            'flags' => $flags,
            'stats' => $stats,
        ])->layout('components.layouts.app');
    }
}
