<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        private string $viewName,
        private string $subjectLine,
        private ?string $fromAddress = null,
        private ?string $fromName = null,
    ) {}

    public function build(): self
    {
        $mail = $this->subject($this->subjectLine)
            ->view($this->viewName, ['order' => $this->order]);

        if ($this->fromAddress) {
            $mail->from($this->fromAddress, $this->fromName ?: null);
        }

        return $mail;
    }
}
