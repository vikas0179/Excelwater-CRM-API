@include('emails.api.header-mail')
	<div style="text-align:left;font-size: 14px; font-weight: 500; margin-bottom: 27px; font-family:Arial,Helvetica,sans-serif;">
		<p style="margin-bottom:9px; color:#181C32; font-size: 22px; font-weight:700">Hi {{ucwords($username)}},</p>
		
		<p style="margin-bottom:2px; color:#7E8299">Thanks for creating an account on {{getenv('APP_NAME')}}. Your username is {{$email_address}}. You can access your account area to view orders, change your password, and more at: <a href="{{getenv('APP_URL')}}account/">{{getenv('APP_URL')}}account/</a></p><br />
		
		<strong>Login details: </strong><br />
		<p style="margin-bottom:2px; color:#7E8299"><strong>Your email: </strong> {{$email_address}}</p>
		<p style="margin-bottom:2px; color:#7E8299"><strong>Your password: </strong> {{$password}}</p>
		<br />
		<p style="margin-bottom:2px; color:#7E8299">We look forward to seeing you soon.</p>
		<p style="margin-bottom:2px; color:#7E8299">Regards, <br>{{getenv('APP_NAME')}} Team </p>
	</div>
@include('emails.api.footer-mail')