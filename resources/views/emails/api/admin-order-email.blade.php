@include('emails.api.header-mail')

	<?php
		$currency_symbol = $order->currency_symbol;
	?>

	<div style="font-size: 14px; font-weight: 500; margin-bottom: 20px; font-family:Arial,Helvetica,sans-serif;">
		<p style="text-align:start;margin-bottom:9px; color:#181C32; font-size: 18px; font-weight:600">Hi {{ucfirst($admin->name)}},</p>
		<p style="text-align:start;margin-bottom:2px; color:#7E8299">Order placed by the {{ucfirst($user->first_name)}} {{ucfirst($user->last_name)}} at {{getenv('APP_NAME')}}. </p>
		<p style="text-align:start;margin-bottom:2px; color:#7E8299">Order number <b>{{$order->order_no}}</b> (Placed on {{ date('d-m-Y') }}). </p>
	</div>
	
	<div style="margin:15px 0;display: block; height: 0;border-bottom: 1px solid #e4e6ef;border-bottom-style: solid;border-bottom-color: #e4e6ef"></div>
	
	<div style="text-align:start; font-size: 13px; font-weight: 500; margin-bottom: 27px; font-family:Arial,Helvetica,sans-serif;">
		
		<p style="margin-bottom:9px; color:#181C32; font-size: 18px; font-weight:500">Billing address</p>
		<p style="margin-bottom:2px; color:#7E8299; font-family:Arial,Helvetica,sans-serif;">{{$billing_address->address}}, {{$billing_address->city }}, {{$billing_address->state }}, {{$billing_address->zip_code }}, {{$billing_address->country }} </p>
		<div style="margin:15px 0;display: block; height: 0;border-bottom: 1px solid #e4e6ef;border-bottom-style: solid;border-bottom-color: #e4e6ef"></div>
		<p style="margin-bottom:9px; color:#181C32; font-size: 18px; font-weight:500">Shipping address</p>
		<p style="margin-bottom:2px; color:#7E8299; font-family:Arial,Helvetica,sans-serif;">{{$shipping_address->address}}, {{$shipping_address->city }}, {{$shipping_address->state }}, {{$shipping_address->zip_code }}, {{$shipping_address->country }} </p>
		<div style="margin:15px 0;display: block; height: 0;border-bottom: 1px solid #e4e6ef;border-bottom-style: solid;border-bottom-color: #e4e6ef"></div>
	</div>

	<h3 style="text-align:left; color:#181C32; font-size: 18px; font-weight:500; margin-bottom: 22px">Order summary</h3>
	
	<table width="100%">
        <tr>
			<th style="width:40%;text-align:left;padding-bottom:10px"><div style="font-family:Arial,Helvetica,sans-serif;color:#7E8299; font-size: 14px; font-weight:500;"><b>Product name</b></div></th>
			<th style="width:25%;text-align:center;padding-bottom:10px"><div style="font-family:Arial,Helvetica,sans-serif;color:#7E8299; font-size: 14px; font-weight:500;"><b>Unit price</b></div></th>
			<th style="width:15%;text-align:center;padding-bottom:10px"><div style="font-family:Arial,Helvetica,sans-serif;color:#7E8299; font-size: 14px; font-weight:500;"><b>Quantity</b></div></th>
			<th style="width:20%;text-align:right;padding-bottom:10px"><div style="font-family:Arial,Helvetica,sans-serif;color:#7E8299; font-size: 14px; font-weight:500;"><b>Total</b></div></th>
		</tr>
		
		<tbody>
			@foreach($order_items as $index=>$value) 
			<tr> 
				<td style="width:40%;text-align:left;"><div style="font-family:Arial,Helvetica,sans-serif;color:#7E8299; font-size: 14px; font-weight:500;">{{$value['product_name'] }}</div></td>
				<td style="width:25%;text-align:center;"><div style="font-family:Arial,Helvetica,sans-serif;color:#7E8299; font-size: 14px; font-weight:500;"><span>{!!$currency_symbol!!}</span> {{number_format($value['base_amount'],2)}}</div></td>
				<td style="width:15%;text-align:center;"><div style="font-family:Arial,Helvetica,sans-serif;color:#7E8299; font-size: 14px; font-weight:500;">{{ $value['quantity'] }}</div></td>
				<td style="width:20%;text-align:right;"><div style="font-family:Arial,Helvetica,sans-serif;color:#7E8299; font-size: 14px; font-weight:500;"><span>{!!$currency_symbol!!}</span> {{number_format( ($value['base_amount'] * $value['quantity']),2)}}</div></td>
			</tr>
			@endforeach 
		</tbody>
		<tfoot>
			<tr>
				<td colspan="3" style="text-align:right;padding-top:30px"><div style="font-family:Arial,Helvetica,sans-serif;color:#7E8299;font-size: 14px; font-weight:500;"><b>Sub total :</b></div></td>
				<td style="text-align:right;padding-top:30px"><div style="font-family:Arial,Helvetica,sans-serif;color:#7E8299; font-size: 14px; font-weight:500;"><span>{!!$currency_symbol!!}</span> {{number_format($order['base_amount'],2)}}</div></td>
			</tr>
			<tr>
				<td colspan="3" style="text-align:right;"><div style="font-family:Arial,Helvetica,sans-serif;color:#7E8299;font-size: 14px; font-weight:500;"><b>Discount :</b></div></td>
				<td style="text-align:right;"><div style="font-family:Arial,Helvetica,sans-serif;color:#7E8299; font-size: 14px; font-weight:500;"><span>{!!$currency_symbol!!}</span> {{number_format($order['discount_amount'],2)}}</div></td>
			</tr> 
			<tr>
				<td colspan="3" style="text-align:right"><div style="font-family:Arial,Helvetica,sans-serif;color:#7E8299;font-size: 14px; font-weight:500;"><b>Tax({{$order['tax_percent']}}%) :</b></div></td>
				<td style="text-align:right;"><div style="font-family:Arial,Helvetica,sans-serif;color:#7E8299; font-size: 14px; font-weight:500;"><span>{!!$currency_symbol!!}</span> {{number_format($order['tax_amount'],2)}}</div></td>
			</tr>
		</tfoot>
	</table>
	
	<div style="margin:15px 0;display: block; height: 0;border-bottom: 1px solid #e4e6ef;border-bottom-style: dashed;border-bottom-color: #e4e6ef"></div>
	
	<table width="100%">
		<tr>
			<td style="text-align:right;width:80%;"><div style="font-family:Arial,Helvetica,sans-serif;color:#7E8299;font-size: 14px; font-weight:500;"><b>Total amount :</b></div></td>
			<td style="text-align:right;width:20%;"><div style="font-family:Arial,Helvetica,sans-serif;color:#50cd89; font-size: 14px; font-weight:700;"><span>{!!$currency_symbol!!}</span> {{number_format($order->total_amount,2)}}</div></td>
		</tr>
	</table>
@include('emails.api.footer-mail')