<?php

namespace App\Application\Mail\Jobs;

use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireOldMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $expiredMails = Mail::unclaimed()->expired()->get();

        $count = 0;

        foreach ($expiredMails as $mail) {
            try {
                DB::transaction(function () use ($mail) {
                    // Delete item attachments (items are lost)
                    if ($mail->hasAttachments()) {
                        foreach ($mail->attachments as $attachment) {
                            if (($attachment['type'] ?? null) === 'item' && !empty($attachment['id'])) {
                                ItemInstance::where('id', $attachment['id'])->delete();
                            }
                        }
                    }

                    $mail->delete();
                });

                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to expire mail', [
                    'mail_id' => $mail->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($count > 0) {
            Log::info("Cleaned up {$count} expired mail(s).");
        }
    }
}
