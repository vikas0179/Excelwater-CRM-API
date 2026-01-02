<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Order Invoice</title>
    <style>
		body{
			font-family: "Times New Roman", Times, serif;
			font-size:16px;
			margin:0px;
			padding:0px;
		}

		#customers {
		  border-collapse: collapse;
		  width: 100%;
		}

		#customers td, #customers th {
		  border: 1px solid #ddd;
		  padding: 5px;
		}

		#customers th {
		  padding-top: 5px;
		  padding-bottom: 5px;
		  text-align: left;
		  background-color: #f2f2f2;
		  color: #000;
		}

		hr{
			border:0px;
			border-bottom:1px solid gray;
		}

		table, th, td {
		  border-collapse: collapse;
		}

		.totals td {
		  padding: 5px;
		}
	
    </style>
</head>

<?php
	$currency_symbol = $order->currency_symbol=="&#8377; " ? "INR " : $order->currency_symbol;
?>

<body> 
	<table width="100%"> 
		<tr>
			<td width="50%" align="left">
				<img src="{{asset('logo.png')}}" />
			 <br>
			</td>
			<td width="50%" style="margin:0px" align="right">
				<h2>Invoice</h2>
				<b>Invoice No. :</b> {{$order->order_no}} <br> 
				<b>Date :</b>  {{date("d-M-Y",strtotime($order->created_at))}} <br>	
				<br>
			</td>
		</tr>
	</table>
	<hr />
	<br />
	<table width="100%"> 
		<tr>
			<td width="50%" align="left">
				<strong>Invoice To: </strong>
				<br />
				{{ucfirst($user->first_name .' '. $user->last_name)}}<br>
				{{$user->email}}<br>
				{{$user->phone}}<br>
				@if(!empty($billing_address))
					{{$billing_address->address}} <br />{{$billing_address->city }}, {{$billing_address->state }}, <br />{{$billing_address->country }}-{{$billing_address->zip_code }}
				@else
					-
				@endif
			</td>
			<td width="50%" style="margin:0px" align="left">
				<strong>Bill To: </strong>
				<br />
				{{ucfirst($user->first_name .' '. $user->last_name)}}<br>
				{{$user->email}}<br>
				{{$user->phone}}<br>
				@if(!empty($shipping_address))
					{{$shipping_address->address}}<br />{{$shipping_address->city }}, {{$shipping_address->state }}, <br />{{$shipping_address->country }}-{{$shipping_address->zip_code }} 
				@else
					-
				@endif
			</td>
		</tr>
	</table>
	
	<br >
	
	<table width="100%" id="customers">
        <tr>
			<th width="5%">#</th>
			<th width="55%">Product</th>
			<th width="20%">Amount</th>
			<th width="10%">Qty</th>
			<th width="20%">Sub Total</th>
		</tr>
		
		<tbody>
			@foreach($order_items as $index=>$value) 
			<tr> 
				<td>{{$index + 1 }}</td>
				<td>
					<strong>{{$value['product_name'] }}</strong>
					@if(!empty($value["category_name"]))
						<br />{{$value["category_name"]}}
					@endif
					@if(!empty($value["variations"]))
						<br />{!!$value["variations"]!!}
					@endif
				</td>
				<td class="text-right"><span>{!!$currency_symbol!!}</span>{{number_format($value['base_amount'],2)}}</td>
				<td align="center" class="text-center">{{ $value['quantity'] }}</td>
				<td style="text-align:right"><span>{!!$currency_symbol!!}{{number_format( ($value['base_amount'] * $value['quantity']),2)}}</td>
			</tr>
			@endforeach 
			
		</tbody>
		
	</table>
	
	<table class="totals" width="100%">
		<td width="50%"></td>
		<td align="right" width="50%">
			<br />
			<table width="100%">
				<tr>
					<td style="text-align:right"><strong>Sub Total :</strong></td>
					<td style="text-align:right"><span>{!!$currency_symbol!!}{{number_format($order['base_amount'],2)}}</span></td>
				</tr>
				<tr>
					<td style="text-align:right"><strong>Discount :</strong></td>
					<td style="text-align:right"><span>{!!$currency_symbol!!}{{number_format($order['discount_amount'],2)}}</span></td>
				</tr>
				<tr>
					<td style="text-align:right"><strong>Tax({{$order['tax_percent']}}%) :</strong></td>
					<td style="text-align:right"><span>{!!$currency_symbol!!}{{number_format($order['tax_amount'],2)}}</span></td>
				</tr>
				<tr>
					<td colspan="2">
						<hr />
					</td>
				</tr>
				<tr>
					<td style="text-align:right"><strong>Total Amount:</strong></td>
					<td style="text-align:right"><span>{!!$currency_symbol!!}{{number_format($order['total_amount'],2)}}</span></td>
				</tr> 
			</table>
		</td>
	</table>
  
</body>
</html>