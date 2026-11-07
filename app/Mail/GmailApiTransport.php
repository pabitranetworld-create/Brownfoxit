<?php

namespace App\Mail;

use App\Support\GmailOAuth;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

/** Sends the finished MIME message through the Gmail API (users.messages.send) with the connected account. */
class GmailApiTransport extends AbstractTransport
{
    protected function doSend(SentMessage $message): void
    {
        $raw = rtrim(strtr(base64_encode($message->toString()), '+/', '-_'), '=');
        $res = Http::withToken(GmailOAuth::accessToken())->timeout(30)
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', ['raw' => $raw]);
        if (! $res->successful()) {
            throw new RuntimeException('Gmail API: '.($res->json('error.message') ?: 'HTTP '.$res->status()));
        }
    }

    public function __toString(): string
    {
        return 'gmail-api';
    }
}
