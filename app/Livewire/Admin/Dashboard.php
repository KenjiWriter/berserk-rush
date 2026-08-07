<?php

namespace App\Livewire\Admin;

use App\Application\Referrals\ReferralService;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Layout;

class Dashboard extends Component
{
    #[Layout('components.layouts.app')]
    public function render()
    {
        $campaignSignups = User::whereIn('signup_source', ReferralService::MARKETING_SOURCES)
            ->selectRaw('signup_source, count(*) as total')
            ->groupBy('signup_source')
            ->pluck('total', 'signup_source');

        return view('livewire.admin.dashboard', [
            'facebookSignups' => $campaignSignups->get('facebook', 0),
            'youtubeSignups' => $campaignSignups->get('youtube', 0),
            'tiktokSignups' => $campaignSignups->get('tiktok', 0),
        ]);
    }
}
