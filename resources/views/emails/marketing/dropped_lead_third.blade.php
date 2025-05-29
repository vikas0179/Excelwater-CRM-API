@include('emails.header-mail')
<div style="font-size:14px;font-weight:500;margin-bottom:20px;font-family:Arial,Helvetica,sans-serif;text-align:left">
    <p style="margin-bottom:9px;color:#181C32;font-size:22px;font-weight:700;">Hi {{ $name }},</p>

    <p style="font-family:Arial,Helvetica,sans-serif;">We know choosing a water purification company is a big decision.
        But don't just take our word for it – let our customers share their experiences with you!</p>

    <p>
        <center><a href="https://www.youtube.com/watch?v=6BveDVrk4pc" target="_blank"><img
                    src="https://www.kentwater.ca/images/email-video3.jpg" style="width:100%;height:auto;" /></a></center>
        <br />
        <span style="font-family:Arial,Helvetica,sans-serif;font-size:22px;"><strong>Watch this video to hear how
                families like yours have:</strong></span>
    </p>
    <ul>
        <li>Enjoyed exceptional service and support from our team.</li>
        <li>Gained peace of mind with our reliable water purification systems.</li>
        <li>Experienced healthier living with soft, filtered water.</li>
    </ul>

    <p style="font-family:Arial,Helvetica,sans-serif;">When you choose Kent Water, you're choosing a company that's
        trusted by countless customers for quality, care and expertise.</p>
    <p style="font-family:Arial,Helvetica,sans-serif;">Don't wait to join the Kent Water family – <strong>book your free
            water test today</strong> by calling <strong>647-212-4552</strong> or replying to this email.</p>
    <p style="font-family:Arial,Helvetica,sans-serif;font-size:22px;">
        <center><strong>Your better water experience starts now!</strong></center>
    </p>
</div>

<a href="<?= getenv('APP_URL') ?>emails/marketing/client-interest?t={{ $token }}"
    style="background-color:#94f466;border-radius:6px;display:inline-block;padding:11px 19px;color:#000;font-size:14px;font-weight:500;font-family:Arial,Helvetica,sans-serif;text-decoration:none;">Yes
    I am interested</a>
<div style="font-size:14px;font-weight:500;margin-bottom:20px;font-family:Arial,Helvetica,sans-serif;">
    <p style="margin-bottom:2px;color:#7E8299">Regards, <br><?= getenv('APP_NAME') ?> Team </p>
</div>


@include('emails.marketing.footer-mail')
