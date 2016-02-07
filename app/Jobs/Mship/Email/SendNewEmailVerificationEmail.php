<?php

namespace App\Jobs\Mship\Email;

use App\Jobs\Job;
use App\Jobs\Messages\CreateNewMessage;
use App\Models\Mship\Account;
use App\Models\Sys\Token;
use Bus;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendNewEmailVerificationEmail extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    private $recipient = null;
    private $token = null;

    public function __construct(Account $recipient, Token $token)
    {
        $this->recipient = $recipient;
        $this->token = $token;
    }

    public function handle(Mailer $mailer)
    {
        $displayFrom = "VATSIM UK - Community Department";
        $subject = "New Email Added - Verification Required";
        $body = \View::make("emails.mship.account.email_add")
                     ->with("account", $this->recipient)
                     ->with("token", $this->token)
                     ->render();

        $sender = Account::find(VATUK_ACCOUNT_SYSTEM);
        $isHtml = true;
        $systemGenerated = true;
        $createNewMessage = new CreateNewMessage($sender, $this->recipient, $subject, $body, $displayFrom, $isHtml, $systemGenerated);
        Bus::dispatch($createNewMessage->onQueue("emails"));
    }
}