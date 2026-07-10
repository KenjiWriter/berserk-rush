<?php

namespace App\Domain\Mail\Events;

use App\Infrastructure\Persistence\Mail;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MailReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Mail $mail
    ) {}
}
