<?php

namespace Mrpath\Admin\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewOrderNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The order instance.
     *
     * @var  \Mrpath\Sales\Contracts\Order  $order
     */
    public $order;

    /**
     * Create a new message instance.
     *
     * @param  \Mrpath\Sales\Contracts\Order  $order
     * @return void
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(core()->getSenderEmailDetails()['email'], core()->getSenderEmailDetails()['name'])
                    ->to($this->order->customer_email, $this->order->customer_full_name)
                    ->subject(trans('shop::app.mail.order.subject'))
                    ->view('shop::emails.sales.new-order');
    }
}
