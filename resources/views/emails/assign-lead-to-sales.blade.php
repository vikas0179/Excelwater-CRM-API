@include('emails.header-mail')
	<div style="font-size: 14px; font-weight: 500; margin-bottom: 20px; font-family:Arial,Helvetica,sans-serif;text-align:left">
		<p style="margin-bottom:9px; color:#181C32; font-size: 22px; font-weight:700">Hi {{$admin->name}},</p>
		<p style="margin-bottom:2px; color:#000">New Lead assigned to you.</p>
		<br />
		<table style="width:100%;text-align:left">
			<tr>
				<th width="20%">Name</th>
				<th width="5%">:</th>
				<td width="75%">{{$lead->name}}</td>
			</tr>
			<tr>
				<th>Email</th>
				<th>:</th>
				<td>{{$lead->email}}</td>
			</tr>
			<tr>
				<th>Phone</th>
				<th>:</th>
				{{-- <td><a href="tel:+1{{str_replace("+","", $lead->phone)}}">{{$lead->phone}}</a></td> --}}
				<td><a href="tel:+1{{ str_replace(['+', ' '], "", $lead->phone) }}">{{$lead->phone}}</a></td>
			</tr>
			<tr>
				<th>City</th>
				<th>:</th>
				<td>{{$lead->city}}</td>
			</tr>
		</table>
		<strong>Message :</strong>
		<p style="color:#000;margin: 0;">{{$lead->message}}</p>
	</div>
	<a href="<?=getenv("ADMIN_URL")?>" style="background-color:#17114c; border-radius:6px;display:inline-block; padding:11px 19px; color: #FFFFFF; font-size: 14px; font-weight:500; font-family:Arial,Helvetica,sans-serif;text-decoration:none;">Login</a>
	<div style="font-size: 14px; font-weight: 500; margin-bottom: 20px; font-family:Arial,Helvetica,sans-serif;">
		<p style="margin-bottom:2px; color:#7E8299">Regards, <br><?=getenv("APP_NAME")?> Team </p>
	</div>
@include('emails.footer-mail')