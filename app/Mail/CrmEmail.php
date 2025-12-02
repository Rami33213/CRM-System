<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CrmEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $bodyContent;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $subjectLine,
        string $bodyContent
    ) {
        $this->bodyContent = $bodyContent;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject($this->subjectLine)
            ->from(config('mail.from.address'), config('mail.from.name'))
            // 👇 أهم سطر: نرسل المحتوى مباشرة كـ HTML بدون View
            ->html(nl2br(e($this->bodyContent)));
    }
}
