<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class GiftCardReceiverMail extends Mailable
{
    use Queueable, SerializesModels;
	
	public $sender_name;
	public $rec_name;
	public $msg;
	public $amount;
	public $gift_card_number;
	
	public function __construct($sender_name, $rec_name, $msg, $amount, $gift_card_number){
		$this->sender_name = $sender_name;
		$this->rec_name = $rec_name;
		$this->msg = $msg;
		$this->amount = $amount;
		$this->gift_card_number = $gift_card_number;
    }
	
	public function envelope(){
        return new Envelope(
            subject: 'A Gift Card from '. $this->sender_name,
        );
    }

    public function content(){
        return new Content(
            view: 'emails.api.gift-card-receiver',
        );
    }

    public function attachments(){
        return [];
    }
}
