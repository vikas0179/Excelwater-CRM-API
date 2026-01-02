@include('emails.api.header-mail')
	<div style="text-align:left;font-size: 14px; font-weight: 500; margin-bottom: 27px; font-family:Arial,Helvetica,sans-serif;">
		<p style="margin-bottom:9px; color:#181C32; font-size: 22px; font-weight:700">Dear {{ucwords($admin->name)}},</p>
		<p style="margin-bottom:2px; color:#7E8299">New Partner with Us Request</p><br />
		
		<p style="margin-bottom:2px; color:#7E8299"><strong>First Name : </strong><br />{{$contact->fname}}</p>
		<p style="margin-bottom:2px; color:#7E8299"><strong>Last Name : </strong><br />{{$contact->lname}}</p>
		<p style="margin-bottom:2px; color:#7E8299"><strong>Phone : </strong><br />{{$contact->phone}}</p>
		<p style="margin-bottom:2px; color:#7E8299"><strong>Email : </strong><br />{{$contact->email}}</p>
		<p style="margin-bottom:2px; color:#7E8299"><strong>Company Name : </strong><br />{{$contact->company_name}}</p>
		<p style="margin-bottom:2px; color:#7E8299"><strong>Street Address : </strong><br />{{$contact->address}}</p>
		<p style="margin-bottom:2px; color:#7E8299"><strong>City : </strong><br />{{$contact->city}}</p>
		<p style="margin-bottom:2px; color:#7E8299"><strong>State/Province : </strong><br />{{$contact->state}}</p>
		<p style="margin-bottom:2px; color:#7E8299"><strong>Zip/Postal Code : </strong><br />{{$contact->zipcode}}</p>
		<p style="margin-bottom:2px; color:#7E8299"><strong>About Company : </strong><br />{{$contact->message}}</p>
		
		<p style="margin-bottom:2px; color:#7E8299">Regards, <br>{{getenv('APP_NAME')}} Team </p>
	</div>
@include('emails.api.footer-mail')