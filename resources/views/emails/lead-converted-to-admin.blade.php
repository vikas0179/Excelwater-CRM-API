@include('emails.header-mail')
	<div style="font-size: 14px; font-weight: 500; margin-bottom: 20px; font-family:Arial,Helvetica,sans-serif;text-align:left">
		<p style="margin-bottom:9px; color:#181C32; font-size: 22px; font-weight:700">Hi {{$admin_name}},</p>
		<p style="margin-bottom:2px; color:#000">Lead is converted by {{$sales_name}}</p>
		<br />
		<p style="margin-bottom:2px; color:#000; font-weight:bold">Lead Details:</p>
		<table style="width:100%;text-align:left">
			<tr>
				<th width="30%">Client Name</th>
				<th width="5%">:</th>
				<td width="65%">{{$client_name}}</td>
			</tr>
			<tr>
				<th>Products Name:</th>
				<th>:</th>
				<td>{{$product_name}}</td>
			</tr>
			<tr>
				<th>Conversion Date:</th>
				<th>:</th>
				<td>{{$date}}</td>
			</tr>
			<tr>
				<th>Revenue:</th>
				<th>:</th>
				<td>{{$revenue}}</td>
			</tr>
			<tr>
				<th>Installation Date:</th>
				<th>:</th>
				<td>{{$installation_date}}</td>
			</tr>
		
		</table>
		
		<?php if(!empty($closed_reason)){ ?>
			<br />
			<strong>Remarks: </strong><br />
			{{$closed_reason}}
		<?php } ?>
	</div>
	
	<div style="font-size: 14px; font-weight: 500; margin-bottom: 20px; font-family:Arial,Helvetica,sans-serif;">
		<p style="margin-bottom:2px; color:#7E8299">Regards, <br><?=getenv("APP_NAME")?> Team </p>
	</div>
@include('emails.footer-mail')