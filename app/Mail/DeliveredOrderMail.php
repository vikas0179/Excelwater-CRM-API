<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class DeliveredOrderMail extends Mailable
{
    use Queueable, SerializesModels;
	
	public $user;
	public $order;
	
	public function __construct($user,$order){
		$this->user = $user; 
		$this->order = $order; 
    }
	
	public function envelope(){
        return new Envelope(
            subject: 'Your order with '. getenv("APP_NAME") . ' has been successfully delivered',
        );
    }

    public function content(){
        return new Content(
            view: 'emails.admin.delivered',
        );
    }

    public function attachments(){
        return [];
    }
}
