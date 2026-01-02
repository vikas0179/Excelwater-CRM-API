<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class RegisterUserMail extends Mailable
{
    use Queueable, SerializesModels;
	
	public $username;
	public $password;
	public $email_address;
	
	public function __construct($username, $email_address, $password){
		$this->username = $username; 
		$this->email_address = $email_address; 
		$this->password = $password; 
    }
	
	public function envelope(){
        return new Envelope(
            subject: 'Your account is created in '. getenv("APP_NAME"),
        );
    }

    public function content(){
        return new Content(
            view: 'emails.api.register-user',
        );
    }

    public function attachments(){
        return [];
    }
}