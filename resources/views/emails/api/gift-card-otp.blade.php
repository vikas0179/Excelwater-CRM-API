@include('emails.api.header-mail')
	<div style="text-align:left;font-size: 14px; font-weight: 500; margin-bottom: 27px; font-family:Arial,Helvetica,sans-serif;">
		<p style="margin-bottom:9px; color:#181C32; font-size: 22px; font-weight:700">Dear {{ucwords($username)}},</p>
		<p style="margin-bottom:2px; color:#7E8299">Here is your OTP to redeem gift card.</p><br />
		
		<div style="text-align:center">
			<h1 style="background: #f1f1f1;display: inline-block;border: 1px dashed gray;padding: 10px 10px 10px 20px;letter-spacing: 10px;">{{$otp}}</h1>
		</div>
		
		
		<p style="margin-bottom:2px; color:#7E8299">Regards, <br>{{ getenv('APP_NAME') }} Team </p>
	</div>
@include('emails.api.footer-mail')