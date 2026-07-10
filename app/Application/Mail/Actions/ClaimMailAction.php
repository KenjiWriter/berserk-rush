<?php

namespace App\Application\Mail\Actions;

use App\Application\Shared\Result;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CurrencyLedger;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemLedger;
use App\Infrastructure\Persistence\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ClaimMailAction
{
    public function execute(Character $character, Mail $mail): Result
    {
        if ($mail->to_character_id !== $character->id) {
            return Result::error('NOT_RECIPIENT', 'Ta poczta nie jest zaadresowana do Ciebie.');
        }

        if ($mail->claimed) {
            return Result::error('ALREADY_CLAIMED', 'Załączniki z tej wiadomości zostały już odebrane.');
        }

        if ($mail->isExpired()) {
            return Result::error('MAIL_EXPIRED', 'Ta wiadomość wygasła. Załączniki przepadły.');
        }

        if (!$mail->hasAttachments()) {
            // No attachments, just mark as claimed
            $mail->update(['claimed' => true]);
            return Result::ok(['message' => 'Wiadomość odczytana.']);
        }

        try {
            return DB::transaction(function () use ($character, $mail) {
                $idempotencyKey = 'claim_mail:' . $mail->id . ':' . Str::ulid();
                $claimedItems = [];
                $claimedCurrency = [];

                foreach ($mail->attachments as $index => $attachment) {
                    $type = $attachment['type'] ?? null;

                    if ($type === 'item') {
                        $itemId = $attachment['id'] ?? null;
                        if ($itemId) {
                            $item = ItemInstance::find($itemId);
                            if ($item) {
                                $item->update([
                                    'owner_character_id' => $character->id,
                                    'location' => 'inventory',
                                ]);

                                ItemLedger::create([
                                    'id'               => Str::ulid(),
                                    'character_id'     => $character->id,
                                    'item_instance_id' => $item->id,
                                    'action'           => 'mail_claim',
                                    'ref_type'         => 'mail',
                                    'ref_id'           => $mail->id,
                                    'quantity_change'  => 1,
                                    'idempotency_key'  => 'mail_item_' . $mail->id . '_' . $item->id . '_' . Str::ulid(),
                                    'created_at'       => now(),
                                ]);

                                $claimedItems[] = $item;
                            }
                        }
                    } elseif (in_array($type, ['gold', 'gems'])) {
                        $qty = (int) ($attachment['qty'] ?? 0);
                        if ($qty > 0) {
                            if ($type === 'gold') {
                                $character->gold += $qty;
                                $character->save();
                            } else {
                                $character->user->gems += $qty;
                                $character->user->save();
                            }

                            CurrencyLedger::create([
                                'id'              => Str::ulid(),
                                'idempotency_key' => 'mail_currency_' . $mail->id . '_' . $type . '_' . Str::ulid(),
                                'character_id'    => $character->id,
                                'currency_type'   => $type,
                                'amount'          => $qty,
                                'balance_after'   => $type === 'gold' ? $character->gold : $character->user->gems,
                                'source_type'     => 'mail_claim',
                                'source_id'       => $mail->id,
                                'description'     => 'Odbiór załącznika z poczty',
                                'created_at'      => now(),
                            ]);

                            $claimedCurrency[] = ['type' => $type, 'qty' => $qty];
                        }
                    } elseif ($type === 'guild_invite') {
                        $guildId = $attachment['guild_id'] ?? null;
                        if ($guildId && !$character->guild_id) {
                            $guild = \App\Models\Guild::withCount('members')->find($guildId);
                            if ($guild && $guild->members_count < $guild->getMaxMembers()) {
                                \App\Models\GuildMember::create([
                                    'guild_id' => $guild->id,
                                    'character_id' => $character->id,
                                    'role' => 'member',
                                ]);
                                $character->guild_id = $guild->id;
                                $character->save();

                                \App\Models\GuildLog::create([
                                    'guild_id' => $guild->id,
                                    'character_id' => $character->id,
                                    'action' => 'invite_accepted',
                                    'amount' => 0,
                                ]);
                            }
                        }
                    }
                }

                $mail->update(['claimed' => true]);

                Log::info('Mail claimed', [
                    'mail_id' => $mail->id,
                    'character_id' => $character->id,
                    'items' => count($claimedItems),
                    'currency' => $claimedCurrency,
                ]);

                return Result::ok([
                    'items' => $claimedItems,
                    'currency' => $claimedCurrency,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('ClaimMail failed', [
                'mail_id' => $mail->id,
                'character_id' => $character->id,
                'error' => $e->getMessage(),
            ]);
            return Result::error('CLAIM_FAILED', 'Wystąpił błąd podczas odbioru załączników.');
        }
    }
}
