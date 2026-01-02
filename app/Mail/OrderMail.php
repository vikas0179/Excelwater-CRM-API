<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class OrderMail extends Mailable
{
    use Queueable, SerializesModels;

	public $pdf;
	public $user;
	public $order;
	public $order_items;
	public $shipping_address;
	public $billing_address;

    public function __construct($pdf,$user,$order,$order_items,$shipping_address,$billing_address){
       $this->pdf = $pdf; 
       $this->user = $user; 
       $this->order = $order; 
       $this->order_items = $order_items; 
       $this->shipping_address = $shipping_address; 
       $this->billing_address = $billing_address; 
    }

    public function envelope(){
        return new Envelope(
            subject: 'Your order with ' . getenv("APP_NAME") . ' is confirmed',
        );
    }

    public function content(){
        return new Content(
            view: 'emails.api.order-email',
        );
    }

    public function attachments(){
        return [
			Attachment::fromData(fn () => $this->pdf->output(), 'invoice.pdf')
                ->withMime('application/pdf'),
		];
    }
}
