<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class GiftCardOTPMail extends Mailable
{
    use Queueable, SerializesModels;
	
	public $username;
	public $otp;
	
	public function __construct($username, $otp){
		$this->username = $username;
		$this->otp = $otp;
    }
	
	public function envelope(){
        return new Envelope(
            subject: 'Gift card OTP verification',
        );
    }

    public function content(){
        return new Content(
            view: 'emails.api.gift-card-otp',
        );
    }

    public function attachments(){
        return [];
    }
}
