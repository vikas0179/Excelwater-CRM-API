<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class PartnerWithUsMail extends Mailable
{
    use Queueable, SerializesModels;
	
	public $contact;
	public $admin;
	
	public function __construct($contact,$admin){
		$this->contact = $contact; 
		$this->admin = $admin; 
    }
	
	public function envelope(){
        return new Envelope(
            subject: 'Lead for Partner with Us from '. getenv("APP_NAME"),
        );
    }

    public function content(){
        return new Content(
            view: 'emails.api.partner-with-us',
        );
    }

    public function attachments(){
        return [];
    }
}
