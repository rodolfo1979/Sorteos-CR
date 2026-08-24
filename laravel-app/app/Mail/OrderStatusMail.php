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
    ) {}

    public function build(): self
    {
        return $this->subject($this->subjectLine)
            ->view($this->viewName, ['order' => $this->order]);
    }
}
