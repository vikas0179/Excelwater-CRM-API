<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin</title>
</head>

<body style="font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #F4F4F4;">
    <section style="padding: 16px; background-color: #FFFFFF; color: #000000;">
        <div style="border: 1px solid #1F2937; padding: 24px;">
            <div style="display: flex; justify-content: center;">
                <img src="https://crm.excelwater.ca/assets/logo.1b249126.webp" alt="Logo" style="width: 150px;">
            </div>
            <div style="text-align:left; margin-top:15px; font-size: 14px; color: #6B7280;">
                "Hi <b> {{ isset($admin->name) ? $admin->name : '' }},</b><br><br>
                Your account has been created.<br><br>

                <b>Login Details:</b><br><br>
                <b>Email:</b> {{ isset($admin->email) ? $admin->email : '' }}<br>
                <b>Password:</b> {{ isset($Pass) ? $Pass : '' }}<br>
                https://crm.excelwater.ca<br><br>
                Thank you!"
            </div>

        </div>
    </section>

</body>

</html>
