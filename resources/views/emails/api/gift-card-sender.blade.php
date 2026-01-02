@include('emails.header-mail')
	<div style="text-align:left;font-size: 14px; font-weight: 500; margin-bottom: 27px; font-family:Arial,Helvetica,sans-serif;">
		
		<div style="text-align:center">
			<p style="color:#181C32; font-size: 22px; font-weight:700">Dear {{strtoupper($sender_name)}},</p>
			<p style="color:#7E8299">Your gift card order is created and <strong>{{strtoupper($details->to_name)}}</strong> has been notified.</p>
			
			<div style="text-align:center">
				<p style="color:#181C32; font-size: 22px; font-weight:700;margin-bottom:0px">Gift Card Amount</p>
				<h1 style="background: #f1f1f1;display: inline-block;border: 1px dashed gray;padding: 10px 10px 10px 20px;letter-spacing: 10px;">{{$details->amount}}</h1>
			</div>
		</div>
		
		<p style="margin-bottom:2px; color:#7E8299">Regards, <br>{{ getenv('APP_NAME') }} Team </p>
	</div>
@include('emails.api.footer-mail')