@include('emails.header-mail')

<div style="font-size:14px;font-weight:500;margin-bottom:20px;font-family:Arial,Helvetica,sans-serif;text-align:left">
    <p style="margin-bottom:10px;color:#181C32;font-size:22px;">Hi {{ $name }},</p>
    <p style="font-family:Arial,Helvetica,sans-serif;">At Kent Water, we believe every family deserves access to clean,
        purified water without breaking the bank. That's why we offer flexible plans and financing options to fit your
        needs:</p>
    <p style="font-family:Arial,Helvetica,sans-serif;"><strong>Plans Designed for Every Home</strong></p>
    <p>
        <center><img src="https://www.kentwater.ca/images/drop-email-img.jpg" style="width:100%;height:auto;" /></center>
    </p><br /><br />
    <p style="font-family:Arial,Helvetica,sans-serif;font-size:22px;"><strong>Enjoy Clean Water Now, Pay Later</strong>
    </p>
    <p style="font-family:Arial,Helvetica,sans-serif;">With our <strong>No Payments & No Interest for up to 12
            Months</strong> financing option, you can start enjoying the benefits of purified water today while staying
        within your budget.</p>
    <p style="font-family:Arial,Helvetica,sans-serif;font-size:22px;"><strong>Why Wait? Transform Your Water
            Today!</strong></p>
    <p style="font-family:Arial,Helvetica,sans-serif;">Call us at <strong>647-212-4552</strong> or reply to this email to
        <strong>Book your FREE Water Test</strong> and learn more about these plans.</p>
    <p style="font-family:Arial,Helvetica,sans-serif;">This is your chance to invest in your home’s water quality,
        health, and comfort – all at an affordable price.</p>
</div>

<a href="<?= getenv('APP_URL') ?>emails/marketing/client-interest?t={{ $token }}"
    style="background-color:#94f466;border-radius:6px;display:inline-block;padding:11px 19px;color:#000;font-size:14px;font-weight:500;font-family:Arial,Helvetica,sans-serif;text-decoration:none;">Yes
    I am interested</a>
<div style="font-size:14px;font-weight:500;margin-bottom:20px;font-family:Arial,Helvetica,sans-serif;">
    <p style="margin-bottom:2px;color:#7E8299">Regards, <br><?= getenv('APP_NAME') ?> Team </p>
</div>

@include('emails.marketing.footer-mail')
