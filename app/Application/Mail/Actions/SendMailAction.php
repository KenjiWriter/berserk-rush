<?php

namespace App\Application\Mail\Actions;

use App\Application\Shared\Result;
use App\Domain\Mail\Events\MailReceived;
use App\Infrastructure\Persistence\Mail;
use Illuminate\Support\Facades\Log;

class SendMailAction
{
    public function execute(string $toCharacterId, string $subject, ?string $body = null, ?array $attachments = null): Result
    {
        try {
            $mail = Mail::create([
                'to_character_id' => $toCharacterId,
                'subject' => $subject,
                'body' => $body,
                'attachments' => $attachments,
            ]);

            event(new MailReceived($mail));

            Log::info('Mail sent', [
                'mail_id' => $mail->id,
                'to' => $toCharacterId,
                'subject' => $subject,
            ]);

            return Result::ok(['mail' => $mail]);
        } catch (\Exception $e) {
            Log::error('SendMail failed', [
                'to' => $toCharacterId,
                'error' => $e->getMessage(),
            ]);
            return Result::error('MAIL_FAILED', 'Wystąpił błąd podczas wysyłania poczty.');
        }
    }
}
