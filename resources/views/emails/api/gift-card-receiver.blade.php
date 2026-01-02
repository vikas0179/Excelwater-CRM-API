@include('emails.api.header-mail')
	<div style="text-align:left;font-size: 14px; font-weight: 500; margin-bottom: 27px; font-family:Arial,Helvetica,sans-serif;">
		
		<?php
			
			$file = File::get("gift-card.png");
			$img = Image::make($file);
			
			$left_point = 1000/4;
			
			$img->text("AED {$amount}",$left_point + 40,310,function($font){
				$font->file(public_path("NunitoSans_10pt-Regular.ttf"));
				$font->size(50);
				$font->color("#000");
			});
			
			$imageName = $gift_card_number. rand(1111,9999) . ".png";
			
			\Storage::disk('public')->put("gc-images/".$imageName, (string) $img->encode());
		?>
		
		<br /><br />
		
		<center>
			<p style="color:#181C32; font-size: 22px; font-weight:700">GIFT CARD NUMBER : {{$gift_card_number}}</p>
		
			<img style="width:80%" src="{{asset('storage/gc-images/'.$imageName)}}" />
		</center>
		
		<div style="text-align:center">
			
			<p style="color:#181C32; font-size: 22px; font-weight:700">A GIFT FROM {{strtoupper($sender_name)}}</p>
			<p style="color:#7E8299">To redeem this treasure, a journey awaits, to our flagship store in Dubai's vibrant gates.</p>
			<p style="color:#7E8299">Browse through our collection, exquisite and rare, each piece handcrafted with utmost care.</p>
		</div>
		
		@if(!empty($msg))
		<br />
		<br />
		
		<h4>Message from {{strtoupper($sender_name)}}</h4>
		<hr />
		{{$msg}}
		
		@endif
		
		<br />
		<br />
		
		<h4>How to use the Gift Card</h4>
		<hr />
		<p style="margin-bottom:2px; color:#7E8299">You have to use the above given Card number whenever you wish to purchase a product from our website (card number to be added in Checkout page) OR share this number with our staff if you purchase any product from our flagship store.</p><br />
		
		
		<p style="margin-bottom:2px; color:#7E8299">Regards, <br>{{ getenv('APP_NAME') }} Team </p>
	</div>
@include('emails.api.footer-mail')