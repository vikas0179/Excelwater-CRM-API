<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class GiftCardSenderMail extends Mailable
{
    use Queueable, SerializesModels;
	
	public $sender_name;
	public $details;
	
	public function __construct($sender_name, $details){
		$this->sender_name = $sender_name;
		$this->details = $details;
    }
	
	public function envelope(){
        return new Envelope(
            subject: 'A Gift Card order placed for '. $this->details->to_name,
        );
    }

    public function content(){
        return new Content(
            view: 'emails.api.gift-card-sender',
        );
    }

    public function attachments(){
        return [];
    }
}
