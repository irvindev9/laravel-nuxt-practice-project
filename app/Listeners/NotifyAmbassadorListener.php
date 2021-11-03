<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\OrderCompletedEvent;
use Illuminate\Mail\Message;

class NotifyAmbassadorListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $order = $event->order;

        \Mail::send('ambassador', ['order' => $order], function(Message $message) use ($order) {
            $message->to($order->ambassador->email);
            $message->subject('New Order has been completed');

        });
    }
}
