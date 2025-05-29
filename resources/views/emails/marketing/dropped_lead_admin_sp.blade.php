@include('emails.header-mail')
	<div style="font-size: 14px; font-weight: 500; margin-bottom: 20px; font-family:Arial,Helvetica,sans-serif;text-align:left">
		<p style="margin-bottom:9px; color:#181C32; font-size: 22px; font-weight:700">Hi {{$name}},</p>
		<p style="margin-bottom:2px; color:#000">{{$client_name}} has shown interest in the product. Please call/email him on {{$phone}} / {{$email_address}} to discuss.</p>
	</div>
	<div style="font-size: 14px; font-weight: 500; margin-bottom: 20px; font-family:Arial,Helvetica,sans-serif;">
		<p style="margin-bottom:2px; color:#7E8299">Regards, <br><?=getenv("APP_NAME")?> Team </p>
	</div>
@include('emails.marketing.footer-mail')