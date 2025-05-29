@include('emails.header-mail')
	<div style="font-size: 14px; font-weight: 500; margin-bottom: 20px; font-family:Arial,Helvetica,sans-serif;">
		<p style="margin-bottom:9px; color:#181C32; font-size: 22px; font-weight:700">Hi there,</p>
		<p style="margin-bottom:2px; color:#7E8299">You can reset your password from bellow link</p>
	</div>
	<a href="<?=getenv("ADMIN_URL")?>reset-password/{{$token}}" style="background-color:#17114c; border-radius:6px;display:inline-block; padding:11px 19px; color: #FFFFFF; font-size: 14px; font-weight:500; font-family:Arial,Helvetica,sans-serif;text-decoration:none;">Reset Password</a>
	<div style="font-size: 14px; font-weight: 500; margin-bottom: 20px; font-family:Arial,Helvetica,sans-serif;">
		<p style="margin-bottom:2px; color:#7E8299">Regards, <br><?=getenv("APP_NAME")?> Team </p>
	</div>
@include('emails.footer-mail')
						 