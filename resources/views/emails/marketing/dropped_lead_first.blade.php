@include('emails.header-mail')

<div
    style="font-size:14px;font-weight:500;color:#000;margin-bottom:20px;font-family:Arial,Helvetica,sans-serif;text-align:left">
    <p style="margin-bottom:10px;color:#181C32;font-size:22px;"><strong>Hi {{ $name }},</strong></p>
    <p style="font-size:14px;">Imagine every drop of water in your home is clean, pure, and refreshing – from the kitchen
        to the bathroom.
        A <strong>whole house water treatment system</strong> does just that, transforming your water and your home in
        ways you'll love.</p>

    <p style="font-size:22px;font-family:Arial,Helvetica,sans-serif;"><strong>Why choose Kent Whole House Water
            Treatment?</strong></p>
    <ul style="font-size:14px;">
        <li><strong>Healthier Living:</strong> Remove harmful contaminants from your water for peace of mind.</li>
        <li><strong>Softer Skin & Hair:</strong> Say goodbye to dryness and irritation caused by hard water.</li>
        <li><strong>Protect Your Home:</strong> Prevent scale buildup in your appliances and plumbing, extending their
            life.</li>
        <li><strong>Better Taste, Every Tap:</strong> Enjoy pure, refreshing water for drinking, cooking and cleaning.
        </li>
    </ul>

    <p style="font-size:22px;font-family:Arial,Helvetica,sans-serif;"><strong>But don't just take our word for it - hear
            it from our customers:</strong></p>
    <table style="width:100%;">
        <tr>
            <td style="text-align:center" valign="top">
                <center>
                    <a href="https://www.youtube.com/watch?v=rvS9K7xm5zE" target="_blank"><img
                            src="https://www.kentwater.ca/images/email-video1.jpg"
                            style="width:100%;height:auto;" /></a>
                </center>
                <p style="font-size:14px;font-family:Arial,Helvetica,sans-serif;"><strong>Soft Water
                        Testimonial:</strong><br />Discover how soft water has improved the lives of our clients in this
                    video.</p>
            </td>
        </tr>

        <tr>
            <td style="text-align:center">
                <center><a href="https://www.youtube.com/watch?v=aJP34w_85cs" target="_blank"><img
                            src="https://www.kentwater.ca/images/email-video2.jpg"
                            style="width:100%;height:auto;" /></a></center>
                <p style="font-size:14px;font-family:Arial,Helvetica,sans-serif;"><strong>Filtered Water
                        Testimonial:</strong><br />See why families are switching to filtered water over tap and bottled
                    water in this video.</p>
            </td>
        </tr>
    </table>

    <p style="font-size:22px;font-family:Arial,Helvetica,sans-serif;"><strong>Act Now and Transform Your Home!</strong>
    </p>
    <p style="font-size:14px;">Don't miss out on the opportunity to enjoy these benefits every day. <strong>Call us at
            647-212-4552</strong> or reply to this email to
        <strong>book your free water test</strong>.
    </p>
    <p style="font-size:22px;font-family:Arial,Helvetica,sans-serif;"><strong>Your family deserves the best water –
            start your journey today!</strong></p>
</div>

<a href="<?= getenv('APP_URL') ?>emails/marketing/client-interest?t={{ $token }}"
    style="background-color:#94f466;border-radius:6px;display:inline-block;padding:11px 19px;color:#000;font-size:16px;font-weight:500;font-family:Arial,Helvetica,sans-serif;text-decoration:none;">Yes
    I am Interested</a>
<div style="font-size:14px;margin-bottom:20px;font-family:Arial,Helvetica,sans-serif;">
    <p style="margin-bottom:2px;color:#7E8299">Regards, <br><?= getenv('APP_NAME') ?> Team</p>
</div>

@include('emails.marketing.footer-mail')
