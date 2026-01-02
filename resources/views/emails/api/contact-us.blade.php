@include('emails.api.header-mail')
	<div style="text-align:left;font-size: 14px; font-weight: 500; margin-bottom: 27px; font-family:Arial,Helvetica,sans-serif;">
		<p style="margin-bottom:9px; color:#181C32; font-size: 22px; font-weight:700">Dear {{ucwords($admin->name)}},</p>
		<p style="margin-bottom:2px; color:#7E8299">New Contact Us Request</p><br />
		
		<p style="margin-bottom:2px; color:#7E8299"><strong>Name : </strong><br />{{$contact->name}}</p>
		<p style="margin-bottom:2px; color:#7E8299"><strong>Email : </strong><br />{{$contact->email}}</p>
		<p style="margin-bottom:2px; color:#7E8299"><strong>Reason : </strong><br />{{$contact->reason}}</p>
		<p style="margin-bottom:2px; color:#7E8299"><strong>Message : </strong><br />{{$contact->message}}</p>
		
		<p style="margin-bottom:2px; color:#7E8299">Regards, <br>{{getenv('APP_NAME')}} Team </p>
	</div>
@include('emails.api.footer-mail')