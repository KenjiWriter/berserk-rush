<?php

namespace App\Livewire\Global;

use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Centered, persistent modal showing the one-time "/discord" link code,
 * so the player has time to read and copy it (replaces the old toast
 * notification, which disappeared too quickly to be usable).
 *
 * Opened from GlobalChatComponent::handleDiscordCommand() via the
 * 'open-discord-link-modal' browser event.
 */
class DiscordLinkModal extends Component
{
    public bool $isOpen = false;
    public string $code = '';
    public string $expiresAt = '';
    public bool $rewardEligible = true;

    #[On('open-discord-link-modal')]
    public function openModal(string $code, string $expiresAt, bool $rewardEligible = true): void
    {
        $this->code = $code;
        $this->expiresAt = $expiresAt;
        $this->rewardEligible = $rewardEligible;
        $this->isOpen = true;
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
    }

    public function render()
    {
        return view('livewire.global.discord-link-modal');
    }
}
