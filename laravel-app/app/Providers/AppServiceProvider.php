<?php

namespace App\Providers;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(MessageSending::class, function (MessageSending $event): void {
            Log::warning('Laravel iniciando envio de correo.', [
                'subject' => $event->message->getSubject(),
                'to' => array_keys($event->message->getTo() ?? []),
            ]);
        });

        Event::listen(MessageSent::class, function (MessageSent $event): void {
            Log::warning('Laravel completo envio de correo.', [
                'subject' => $event->message->getSubject(),
                'to' => array_keys($event->message->getTo() ?? []),
            ]);
        });
    }
}
