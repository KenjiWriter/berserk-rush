<?php

namespace App\Jobs;

use App\Application\Guilds\GuildWarService;
use App\Infrastructure\Persistence\GuildWar;
use App\Infrastructure\Persistence\Mail;
use App\Models\GuildMember;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessGuildWarJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $guildWarId;

    public function __construct(string $guildWarId)
    {
        $this->guildWarId = $guildWarId;
    }

    public function handle(GuildWarService $service): void
    {
        $war = GuildWar::find($this->guildWarId);

        if (!$war) {
            Log::warning('Guild war not found for processing', ['id' => $this->guildWarId]);
            return;
        }

        if ($war->status !== 'in_progress') {
            Log::warning('Guild war not in progress', ['id' => $this->guildWarId, 'status' => $war->status]);
            return;
        }

        $result = $service->processWar($war);

        if ($result->isSuccess()) {
            $data = $result->getValue();
            $winner = $data['winner'];
            $loser = $data['loser'];
            $gold = $data['gold_prize'];
            $score = $data['score'];

            // Notify both leaders
            $this->notifyLeader($winner->id, '🏆 Wygrana Wojna Gildii!', "Twoja gildia wygrała wojnę przeciwko {$loser->name} z wynikiem {$score['challenger']}:{$score['defender']}! Zdobyliście {$gold} złota ze skarbca wroga.");
            $this->notifyLeader($loser->id, '💀 Przegrana Wojna Gildii', "Twoja gildia przegrała wojnę przeciwko {$winner->name} z wynikiem {$score['defender']}:{$score['challenger']}... Straciliście całe złoto ze skarbca ({$gold}).");
        } else {
            $war->update(['status' => 'error']);
            Log::error('Guild war processing failed', ['id' => $this->guildWarId, 'error' => $result->getError()]);
        }
    }

    private function notifyLeader(string $guildId, string $subject, string $body): void
    {
        $leader = GuildMember::where('guild_id', $guildId)
            ->where('role', 'leader')
            ->first();

        if ($leader) {
            Mail::create([
                'to_character_id' => $leader->character_id,
                'subject' => $subject,
                'body' => $body,
                'attachments' => [],
            ]);
        }
    }
}
